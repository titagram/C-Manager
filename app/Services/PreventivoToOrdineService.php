<?php

namespace App\Services;

use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\StatoPreventivo;
use App\Models\Ordine;
use App\Models\OrdineRiga;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PreventivoToOrdineService
{
    /**
     * Convert a Preventivo to an Ordine
     *
     * @throws \InvalidArgumentException
     */
    public function convert(Preventivo $preventivo): Ordine
    {
        $preventivo->loadMissing([
            'righe.prodotto',
            'righe.lottoProduzione.materialiUsati',
            'righe.lottoProduzione.componentiManuali',
        ]);

        // Validate preventivo state
        if ($preventivo->stato !== StatoPreventivo::ACCETTATO) {
            throw new \InvalidArgumentException(
                'Solo i preventivi accettati possono essere convertiti in ordini.'
            );
        }

        // Check if already converted
        if (Ordine::where('preventivo_id', $preventivo->id)->exists()) {
            throw new \InvalidArgumentException(
                'Questo preventivo è già stato convertito in ordine.'
            );
        }

        if ($preventivo->righe->isEmpty()) {
            throw new \InvalidArgumentException(
                'Il preventivo non contiene righe: impossibile generare un ordine.'
            );
        }

        return DB::transaction(function () use ($preventivo) {
            $ordine = Ordine::create([
                'preventivo_id' => $preventivo->id,
                'cliente_id' => $preventivo->cliente_id,
                'data_ordine' => now(),
                'stato' => StatoOrdine::CONFERMATO,
                'descrizione' => $preventivo->descrizione,
                'totale' => $preventivo->totale,
                'created_by' => auth()->id(),
            ]);

            // Copy righe E converti lotti BOZZA → CONFERMATO
            $righeCreate = 0;
            foreach ($preventivo->righe as $i => $prevRiga) {
                $ordineRiga = OrdineRiga::create(
                    $this->mapPreventivoRigaToOrdineData($ordine->id, $prevRiga, $i)
                );
                $righeCreate++;

                // IMPORTANTE: Converti lotto da BOZZA a CONFERMATO
                if ($prevRiga->lottoProduzione) {
                    $lotto = $prevRiga->lottoProduzione;

                    if (! in_array($lotto->stato, [
                        StatoLottoProduzione::BOZZA,
                        StatoLottoProduzione::CONFERMATO,
                    ], true)) {
                        throw new \RuntimeException(
                            "Il lotto {$lotto->codice_lotto} è in stato {$lotto->stato->label()} e non può restare collegato a un preventivo da convertire."
                        );
                    }

                    $lotto->update([
                        'stato' => $lotto->hasTechnicalDefinition()
                            ? StatoLottoProduzione::CONFERMATO
                            : StatoLottoProduzione::BOZZA,
                        'preventivo_id' => $preventivo->id,
                        'ordine_id' => $ordine->id,
                        'ordine_riga_id' => $ordineRiga->id,
                        'cliente_id' => $lotto->cliente_id ?: $ordine->cliente_id,
                    ]);
                }
            }

            $ordine->ricalcolaTotale();

            if ($righeCreate === 0) {
                throw new \RuntimeException('Nessuna riga ordine e\' stata generata dal preventivo.');
            }

            $currentUser = auth()->user();
            if ($currentUser instanceof User) {
                app(OrderProductionService::class)->sincronizzaOrdineConfermato($ordine, $currentUser);
            }

            return $ordine->load('righe');
        });
    }

    /**
     * Map preventivo row fields to ordine row schema.
     *
     * Note: ordine dimensions keep historical compatibility where
     * preventivo(lunghezza, larghezza, spessore) -> ordine(larghezza, profondita, altezza).
     *
     * @return array<string, int|float|string|null>
     */
    private function mapPreventivoRigaToOrdineData(int $ordineId, PreventivoRiga $prevRiga, int $ordine): array
    {
        $volumeCalcolato = (float) ($prevRiga->volume_mc ?? 0);
        $volumeFinale = $this->resolveVolumeFinale($prevRiga, $volumeCalcolato);
        $prezzoMc = $this->resolvePrezzoMc($prevRiga);
        $totaleRiga = $this->resolveTotaleRiga($prevRiga, $volumeFinale, $prezzoMc);

        return [
            'ordine_id' => $ordineId,
            'prodotto_id' => $prevRiga->prodotto_id,
            'descrizione' => $prevRiga->descrizione ?? $prevRiga->prodotto?->nome ?? 'Prodotto',
            'larghezza_mm' => $this->toIntOrNull($prevRiga->lunghezza_mm),
            'profondita_mm' => $this->toIntOrNull($prevRiga->larghezza_mm),
            'altezza_mm' => $this->toIntOrNull($prevRiga->spessore_mm),
            'quantita' => max(1, (int) ($prevRiga->quantita ?? 0)),
            'volume_mc_calcolato' => $volumeCalcolato,
            'volume_mc_finale' => $volumeFinale,
            'prezzo_mc' => $prezzoMc,
            'totale_riga' => $totaleRiga,
            'ordine' => $ordine,
        ];
    }

    private function resolveVolumeFinale(PreventivoRiga $prevRiga, float $volumeCalcolato): float
    {
        if ($prevRiga->materiale_lordo !== null && (float) $prevRiga->materiale_lordo > 0) {
            return (float) $prevRiga->materiale_lordo;
        }

        if (($prevRiga->lottoProduzione?->volume_totale_mc ?? 0) > 0) {
            return (float) $prevRiga->lottoProduzione->volume_totale_mc;
        }

        return $volumeCalcolato;
    }

    private function resolvePrezzoMc(PreventivoRiga $prevRiga): float
    {
        if ($prevRiga->prezzo_unitario !== null && (float) $prevRiga->prezzo_unitario > 0) {
            return (float) $prevRiga->prezzo_unitario;
        }

        $lottoTotale = $this->resolveLottoTotale($prevRiga);
        $lottoVolume = $this->resolveVolumeFinale($prevRiga, (float) ($prevRiga->volume_mc ?? 0));
        if ($lottoTotale !== null && $lottoVolume > 0) {
            return round($lottoTotale / $lottoVolume, 4);
        }

        if ($prevRiga->prodotto !== null) {
            $unitaMisura = $prevRiga->unita_misura ?: $prevRiga->prodotto->unita_misura?->value;

            return $prevRiga->prodotto->prezzoListinoPerUnita($unitaMisura);
        }

        return 0.0;
    }

    private function resolveTotaleRiga(PreventivoRiga $prevRiga, float $volumeFinale, float $prezzoMc): float
    {
        if ($prevRiga->totale_riga !== null && (float) $prevRiga->totale_riga > 0) {
            return (float) $prevRiga->totale_riga;
        }

        $lottoTotale = $this->resolveLottoTotale($prevRiga);
        if ($lottoTotale !== null) {
            return $lottoTotale;
        }

        return round($volumeFinale * $prezzoMc, 2);
    }

    private function resolveLottoTotale(PreventivoRiga $prevRiga): ?float
    {
        $lotto = $prevRiga->lottoProduzione;
        if ($lotto === null) {
            return null;
        }

        $prezzoFinale = (float) ($lotto->prezzo_finale ?? 0);
        if ($prezzoFinale > 0) {
            return round($prezzoFinale, 2);
        }

        $prezzoCalcolato = (float) ($lotto->prezzo_calcolato ?? 0);

        return $prezzoCalcolato > 0
            ? round($prezzoCalcolato, 2)
            : null;
    }

    private function toIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) ((float) $value);
    }
}
