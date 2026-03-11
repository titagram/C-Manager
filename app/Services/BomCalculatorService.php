<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\ConsumoMateriale;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use Illuminate\Support\Facades\DB;

class BomCalculatorService
{
    /**
     * Calcola il fabbisogno di materiali da template BOM per N pezzi.
     * Il risultato e' una stima pianificatoria di baseline.
     */
    public function calcolaFabbisogno(Bom $bom, int $numeroPezzi): array
    {
        // Eager load prodotto to avoid N+1
        $bom->loadMissing('righe.prodotto');

        return $bom->righe->map(function ($riga) use ($numeroPezzi) {
            $quantitaConScarto = $riga->quantita * (1 + $riga->coefficiente_scarto);
            return [
                'prodotto_id' => $riga->prodotto_id,
                'prodotto_nome' => $riga->prodotto?->nome ?? $riga->descrizione,
                'unita_misura' => $riga->unita_misura,
                'quantita_base' => $riga->quantita,
                'coefficiente_scarto' => $riga->coefficiente_scarto,
                'quantita_necessaria' => round($quantitaConScarto * $numeroPezzi, 4),
                'is_fitok_required' => $riga->is_fitok_required,
            ];
        })->toArray();
    }

    /**
     * Verifica disponibilita materiali rispetto al fabbisogno template BOM.
     */
    public function verificaDisponibilita(Bom $bom, int $numeroPezzi): array
    {
        $fabbisogno = $this->calcolaFabbisogno($bom, $numeroPezzi);
        $mancanti = [];
        $disponibile = true;

        foreach ($fabbisogno as $item) {
            if (!$item['prodotto_id']) {
                continue;
            }

            $giacenza = $this->getGiacenzaTotalePerProdotto($item['prodotto_id']);

            if ($giacenza < $item['quantita_necessaria']) {
                $disponibile = false;
                $mancanti[] = [
                    'prodotto_id' => $item['prodotto_id'],
                    'prodotto_nome' => $item['prodotto_nome'],
                    'necessario' => $item['quantita_necessaria'],
                    'disponibile' => $giacenza,
                    'mancante' => $item['quantita_necessaria'] - $giacenza,
                ];
            }
        }

        return [
            'disponibile' => $disponibile,
            'fabbisogno' => $fabbisogno,
            'mancanti' => $mancanti,
        ];
    }

    /**
     * Genera consumi materiale precompilati da template BOM per un lotto produzione.
     * I consumi creati rappresentano una baseline iniziale e possono essere raffinati manualmente.
     */
    public function generaConsumiDaTemplate(Bom $bom, LottoProduzione $lotto, int $numeroPezzi): array
    {
        $fabbisogno = $this->calcolaFabbisogno($bom, $numeroPezzi);
        $consumi = [];

        return DB::transaction(function () use ($fabbisogno, $lotto, &$consumi) {
            foreach ($fabbisogno as $item) {
                if (!$item['prodotto_id']) {
                    continue;
                }

                $quantitaRimanente = $item['quantita_necessaria'];

                // Prendi lotti materiale FIFO (piu vecchi prima) con giacenza pre-calcolata
                $lottiMateriale = LottoMateriale::where('prodotto_id', $item['prodotto_id'])
                    ->conGiacenza()
                    ->orderBy('data_arrivo')
                    ->get();

                foreach ($lottiMateriale as $lottoMat) {
                    if ($quantitaRimanente <= 0) {
                        break;
                    }

                    $giacenza = $lottoMat->giacenza;
                    if ($giacenza <= 0) {
                        continue;
                    }

                    $quantitaDaPrelevare = min($giacenza, $quantitaRimanente);

                    $consumo = ConsumoMateriale::create([
                        'lotto_produzione_id' => $lotto->id,
                        'lotto_materiale_id' => $lottoMat->id,
                        'quantita' => $quantitaDaPrelevare,
                        'note' => 'Auto-generato da template BOM',
                    ]);

                    $consumi[] = $consumo;
                    $quantitaRimanente -= $quantitaDaPrelevare;
                }
            }

            return $consumi;
        });
    }

    /**
     * Backward-compatible wrapper.
     */
    public function generaConsumi(Bom $bom, LottoProduzione $lotto, int $numeroPezzi): array
    {
        return $this->generaConsumiDaTemplate($bom, $lotto, $numeroPezzi);
    }

    private function getGiacenzaTotalePerProdotto(int $prodottoId): float
    {
        return LottoMateriale::where('prodotto_id', $prodottoId)
            ->conGiacenza()
            ->get()
            ->sum(fn ($lotto) => max(0, $lotto->giacenza));
    }
}
