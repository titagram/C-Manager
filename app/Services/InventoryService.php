<?php

namespace App\Services;

use App\Enums\TipoMovimento;
use App\Models\Documento;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Registra un carico di materiale
     */
    public function carico(
        LottoMateriale $lotto,
        float $quantita,
        ?Documento $documento,
        User $user,
        ?string $causale = null
    ): MovimentoMagazzino {
        return DB::transaction(function () use ($lotto, $quantita, $documento, $user, $causale) {
            return MovimentoMagazzino::create([
                'lotto_materiale_id' => $lotto->id,
                'tipo' => TipoMovimento::CARICO,
                'quantita' => $quantita,
                'documento_id' => $documento?->id,
                'causale' => $causale,
                'created_by' => $user->id,
                'data_movimento' => now(),
            ]);
        });
    }

    /**
     * Registra uno scarico di materiale
     *
     * @throws \Exception Se la giacenza è insufficiente
     */
    public function scarico(
        LottoMateriale $lotto,
        float $quantita,
        ?LottoProduzione $lottoProduzione,
        ?Documento $documento,
        User $user,
        ?string $causale = null
    ): MovimentoMagazzino {
        return DB::transaction(function () use ($lotto, $quantita, $lottoProduzione, $documento, $user, $causale) {
            // Lock the material lot row to prevent concurrent stock operations
            LottoMateriale::where('id', $lotto->id)->lockForUpdate()->first();

            if (!$this->verificaDisponibilita($lotto, $quantita)) {
                throw new \Exception(
                    "Giacenza insufficiente per il lotto {$lotto->codice_lotto}. " .
                    "Disponibile: {$this->calcolaGiacenza($lotto)}, Richiesto: {$quantita}"
                );
            }

            return MovimentoMagazzino::create([
                'lotto_materiale_id' => $lotto->id,
                'tipo' => TipoMovimento::SCARICO,
                'quantita' => $quantita,
                'documento_id' => $documento?->id,
                'lotto_produzione_id' => $lottoProduzione?->id,
                'causale' => $causale,
                'created_by' => $user->id,
                'data_movimento' => now(),
            ]);
        });
    }

    /**
     * Registra una rettifica (positiva o negativa)
     */
    public function rettifica(
        LottoMateriale $lotto,
        float $quantita,
        bool $positiva,
        User $user,
        string $causale,
        ?string $causaleCodice = null
    ): MovimentoMagazzino {
        return DB::transaction(function () use ($lotto, $quantita, $positiva, $user, $causale, $causaleCodice) {
            // Lock the material lot row to prevent concurrent stock operations
            LottoMateriale::where('id', $lotto->id)->lockForUpdate()->first();

            $tipo = $positiva
                ? TipoMovimento::RETTIFICA_POSITIVA
                : TipoMovimento::RETTIFICA_NEGATIVA;

            if (
                !$positiva
                && !MovimentoMagazzino::isValidNegativeAdjustmentReasonCode($causaleCodice)
            ) {
                throw new \InvalidArgumentException(
                    'Per rettifiche negative e obbligatorio un codice causale strutturato valido.'
                );
            }

            if (!$positiva && !$this->verificaDisponibilita($lotto, $quantita)) {
                throw new \Exception(
                    "Giacenza insufficiente per rettifica negativa sul lotto {$lotto->codice_lotto}."
                );
            }

            return MovimentoMagazzino::create([
                'lotto_materiale_id' => $lotto->id,
                'tipo' => $tipo,
                'quantita' => $quantita,
                'causale' => $causale,
                'causale_codice' => !$positiva ? $causaleCodice : null,
                'created_by' => $user->id,
                'data_movimento' => now(),
            ]);
        });
    }

    /**
     * Calcola la giacenza attuale di un lotto
     */
    public function calcolaGiacenza(LottoMateriale $lotto): float
    {
        $movimentiQuery = MovimentoMagazzino::where('lotto_materiale_id', $lotto->id);
        $saldoMovimenti = (float) ($movimentiQuery
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN tipo IN ('carico', 'rettifica_positiva') THEN quantita
                    ELSE -quantita
                END), 0) as giacenza
            ")
            ->value('giacenza') ?? 0);

        $haCaricoIniziale = MovimentoMagazzino::query()
            ->where('lotto_materiale_id', $lotto->id)
            ->where('tipo', TipoMovimento::CARICO->value)
            ->exists();

        if ($haCaricoIniziale) {
            return $saldoMovimenti;
        }

        // Legacy fallback: if there is no explicit initial load movement,
        // use quantita_iniziale as baseline and apply existing movements as delta.
        $baseline = (float) ($lotto->quantita_iniziale ?? 0);

        return round($baseline + $saldoMovimenti, 4);
    }

    /**
     * Verifica se c'è disponibilità sufficiente per uno scarico
     */
    public function verificaDisponibilita(LottoMateriale $lotto, float $quantitaRichiesta): bool
    {
        return $this->calcolaGiacenza($lotto) >= $quantitaRichiesta;
    }

    /**
     * Ottiene il riepilogo giacenze per tutti i lotti attivi
     */
    public function getRiepilogoGiacenze(): array
    {
        return LottoMateriale::with('prodotto')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($lotto) {
                return [
                    'lotto' => $lotto,
                    'giacenza' => $this->calcolaGiacenza($lotto),
                ];
            })
            ->filter(fn($item) => $item['giacenza'] > 0)
            ->values()
            ->toArray();
    }

    /**
     * Ottiene i lotti con giacenza sotto una certa soglia
     */
    public function getLottiSottoSoglia(float $soglia = 10): array
    {
        return LottoMateriale::with('prodotto')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($lotto) {
                return [
                    'lotto' => $lotto,
                    'giacenza' => $this->calcolaGiacenza($lotto),
                ];
            })
            ->filter(fn($item) => $item['giacenza'] > 0 && $item['giacenza'] <= $soglia)
            ->values()
            ->toArray();
    }
}
