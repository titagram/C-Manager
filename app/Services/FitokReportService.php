<?php

namespace App\Services;

use App\Enums\StatoLottoProduzione;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FitokReportService
{
    /**
     * Ottiene il registro FITOK per un periodo specifico
     */
    public function getRegistro(Carbon $dataInizio, Carbon $dataFine): Collection
    {
        return MovimentoMagazzino::query()
            ->join('lotti_materiale', 'movimenti_magazzino.lotto_materiale_id', '=', 'lotti_materiale.id')
            ->join('prodotti', 'lotti_materiale.prodotto_id', '=', 'prodotti.id')
            ->leftJoin('lotti_produzione', 'movimenti_magazzino.lotto_produzione_id', '=', 'lotti_produzione.id')
            ->where('prodotti.soggetto_fitok', true)
            ->whereBetween('movimenti_magazzino.data_movimento', [$dataInizio, $dataFine])
            ->select([
                'movimenti_magazzino.id',
                'movimenti_magazzino.data_movimento',
                'movimenti_magazzino.tipo',
                'movimenti_magazzino.quantita',
                'movimenti_magazzino.causale',
                'movimenti_magazzino.lotto_produzione_id',
                'lotti_materiale.codice_lotto',
                'lotti_materiale.fitok_certificato',
                'lotti_materiale.fitok_data_trattamento',
                'lotti_materiale.fitok_tipo_trattamento',
                'lotti_materiale.fitok_paese_origine',
                'lotti_materiale.fornitore',
                'prodotti.nome as prodotto_nome',
                'prodotti.codice as prodotto_codice',
                'prodotti.unita_misura',
                'lotti_produzione.codice_lotto as lotto_produzione_codice',
                'lotti_produzione.fitok_percentuale as lotto_produzione_fitok_percentuale',
            ])
            ->leftJoin('documenti', 'movimenti_magazzino.documento_id', '=', 'documenti.id')
            ->addSelect([
                'documenti.numero as documento_numero',
                'documenti.tipo as documento_tipo',
                'documenti.data as documento_data',
            ])
            ->orderBy('movimenti_magazzino.data_movimento')
            ->orderBy('movimenti_magazzino.id')
            ->get();
    }

    /**
     * Ottiene il riepilogo movimenti FITOK raggruppato per tipo
     */
    public function getRiepilogoPerTipo(Carbon $dataInizio, Carbon $dataFine): array
    {
        $movimenti = $this->getRegistro($dataInizio, $dataFine);

        return [
            'carichi' => $movimenti->filter(
                fn ($m) => $this->movementTypeValue($m->tipo ?? null) === 'carico'
            )->sum('quantita'),
            'scarichi' => $movimenti->filter(
                fn ($m) => $this->movementTypeValue($m->tipo ?? null) === 'scarico'
            )->sum('quantita'),
            'rettifiche_positive' => $movimenti->filter(
                fn ($m) => $this->movementTypeValue($m->tipo ?? null) === 'rettifica_positiva'
            )->sum('quantita'),
            'rettifiche_negative' => $movimenti->filter(
                fn ($m) => $this->movementTypeValue($m->tipo ?? null) === 'rettifica_negativa'
            )->sum('quantita'),
            'saldo' => $movimenti->sum(function ($m) {
                return in_array($this->movementTypeValue($m->tipo ?? null), ['carico', 'rettifica_positiva'], true)
                    ? $m->quantita
                    : -$m->quantita;
            }),
        ];
    }

    /**
     * Ottiene i movimenti FITOK raggruppati per prodotto
     */
    public function getMovimentiPerProdotto(Carbon $dataInizio, Carbon $dataFine): Collection
    {
        return $this->getRegistro($dataInizio, $dataFine)
            ->groupBy('prodotto_codice')
            ->map(function ($movimenti, $codice) {
                $primo = $movimenti->first();
                return [
                    'codice' => $codice,
                    'nome' => $primo->prodotto_nome,
                    'unita_misura' => $primo->unita_misura,
                    'totale_carichi' => $movimenti->filter(
                        fn ($m) => $this->movementTypeValue($m->tipo ?? null) === 'carico'
                    )->sum('quantita'),
                    'totale_scarichi' => $movimenti->filter(
                        fn ($m) => $this->movementTypeValue($m->tipo ?? null) === 'scarico'
                    )->sum('quantita'),
                    'movimenti_count' => $movimenti->count(),
                ];
            });
    }

    /**
     * Mappa tracciabilita destinazioni FITOK: lotto carico -> lotto produzione.
     */
    public function getFitokDestinationMap(Carbon $dataInizio, Carbon $dataFine): Collection
    {
        return $this->buildFitokDestinationMapFromMovimenti(
            $this->getRegistro($dataInizio, $dataFine)
        );
    }

    /**
     * Costruisce la mappa destinazioni partendo da un set di movimenti gia filtrati.
     */
    public function buildFitokDestinationMapFromMovimenti(Collection $movimenti): Collection
    {
        return $movimenti
            ->filter(function ($movimento) {
                return $this->movementTypeValue($movimento->tipo ?? null) === 'scarico'
                    && !empty($movimento->lotto_produzione_id);
            })
            ->groupBy(function ($movimento) {
                return implode('|', [
                    (string) ($movimento->codice_lotto ?? '-'),
                    (string) ($movimento->lotto_produzione_id ?? '-'),
                    (string) ($movimento->prodotto_codice ?? '-'),
                ]);
            })
            ->map(function (Collection $items) {
                $first = $items->first();
                $fitokPercentuale = $first->lotto_produzione_fitok_percentuale !== null
                    ? (float) $first->lotto_produzione_fitok_percentuale
                    : null;

                return [
                    'lotto_carico_codice' => (string) ($first->codice_lotto ?? '-'),
                    'lotto_produzione_id' => (int) ($first->lotto_produzione_id ?? 0),
                    'lotto_produzione_codice' => (string) ($first->lotto_produzione_codice ?? '-'),
                    'prodotto_codice' => (string) ($first->prodotto_codice ?? '-'),
                    'prodotto_nome' => (string) ($first->prodotto_nome ?? '-'),
                    'quantita_destinata' => (float) $items->sum('quantita'),
                    'unita_misura' => (string) ($first->unita_misura ?? ''),
                    'fitok_percentuale_lotto_uscente' => $fitokPercentuale,
                    'stato_certificazione_uscita' => $this->getStatoCertificazioneUscitaLabel(
                        lottoProduzioneId: $first->lotto_produzione_id
                            ? (int) $first->lotto_produzione_id
                            : null,
                        fitokPercentuale: $fitokPercentuale,
                    ),
                    'movimenti_count' => $items->count(),
                ];
            })
            ->sortBy([
                ['lotto_carico_codice', 'asc'],
                ['lotto_produzione_codice', 'asc'],
                ['prodotto_codice', 'asc'],
            ])
            ->values();
    }

    /**
     * Ottiene i lotti FITOK con certificato scaduto o in scadenza
     */
    public function getLottiInScadenza(int $giorniPreavviso = 30): Collection
    {
        $oggi = now()->startOfDay();
        $dataLimite = $oggi->copy()->addDays($giorniPreavviso);

        $lotti = LottoMateriale::query()
            ->join('prodotti', 'lotti_materiale.prodotto_id', '=', 'prodotti.id')
            ->where('prodotti.soggetto_fitok', true)
            ->whereNotNull('lotti_materiale.fitok_certificato')
            ->whereNotNull('lotti_materiale.fitok_data_trattamento')
            ->select([
                'lotti_materiale.*',
                'prodotti.nome as prodotto_nome',
                'prodotti.codice as prodotto_codice',
            ])
            ->get();

        return $lotti
            ->map(function ($lotto) use ($oggi) {
                $dataTrattamento = $lotto->fitok_data_trattamento instanceof Carbon
                    ? $lotto->fitok_data_trattamento->copy()->startOfDay()
                    : Carbon::parse($lotto->fitok_data_trattamento)->startOfDay();

                $validitaGiorni = $this->getValiditaGiorniPerTrattamento($lotto->fitok_tipo_trattamento);
                $dataScadenza = $dataTrattamento->copy()->addDays($validitaGiorni);
                $giorniAllaScadenza = $oggi->diffInDays($dataScadenza, false);

                $lotto->setAttribute('fitok_validita_giorni', $validitaGiorni);
                $lotto->setAttribute('fitok_data_scadenza', $dataScadenza->toDateString());
                $lotto->setAttribute('fitok_giorni_alla_scadenza', $giorniAllaScadenza);

                return $lotto;
            })
            ->filter(function ($lotto) use ($dataLimite) {
                return Carbon::parse($lotto->fitok_data_scadenza)->lte($dataLimite);
            })
            ->sortBy('fitok_data_scadenza')
            ->values();
    }

    /**
     * Genera i dati per l'export del registro FITOK
     */
    public function getDataForExport(Carbon $dataInizio, Carbon $dataFine): array
    {
        $movimenti = $this->getRegistro($dataInizio, $dataFine);
        $riepilogo = $this->getRiepilogoPerTipo($dataInizio, $dataFine);

        return [
            'periodo' => [
                'da' => $dataInizio->format('d/m/Y'),
                'a' => $dataFine->format('d/m/Y'),
            ],
            'generato_il' => now()->format('d/m/Y H:i'),
            'riepilogo' => $riepilogo,
            'movimenti' => $movimenti->map(function ($m) {
                $statoCertificazioneUscita = $this->getStatoCertificazioneUscitaLabel(
                    lottoProduzioneId: $m->lotto_produzione_id ? (int) $m->lotto_produzione_id : null,
                    fitokPercentuale: $m->lotto_produzione_fitok_percentuale !== null
                        ? (float) $m->lotto_produzione_fitok_percentuale
                        : null
                );

                return [
                    'data' => Carbon::parse($m->data_movimento)->format('d/m/Y'),
                    'tipo' => $this->getTipoLabel($m->tipo),
                    'lotto' => $m->codice_lotto,
                    'lotto_carico' => $m->codice_lotto,
                    'lotto_produzione_destinazione' => $m->lotto_produzione_codice ?? '-',
                    'stato_certificazione_uscita' => $statoCertificazioneUscita,
                    'prodotto' => "{$m->prodotto_codice} - {$m->prodotto_nome}",
                    'quantita' => number_format($m->quantita, 2, ',', '.'),
                    'unita' => $m->unita_misura,
                    'certificato_fitok' => $m->fitok_certificato ?? '-',
                    'data_trattamento' => $m->fitok_data_trattamento
                        ? Carbon::parse($m->fitok_data_trattamento)->format('d/m/Y')
                        : '-',
                    'tipo_trattamento' => $m->fitok_tipo_trattamento ?? '-',
                    'paese_origine' => $m->fitok_paese_origine ?? '-',
                    'documento' => $m->documento_numero
                        ? "{$m->documento_tipo} n. {$m->documento_numero}"
                        : '-',
                    'causale' => $m->causale ?? '-',
                ];
            })->toArray(),
        ];
    }

    /**
     * Get completed production lots with FITOK data for period
     */
    public function getLottiProduzioneFitok(Carbon $dataInizio, Carbon $dataFine): Collection
    {
        return LottoProduzione::with(['cliente', 'ordine'])
            ->where('stato', StatoLottoProduzione::COMPLETATO)
            ->whereBetween('data_fine', [$dataInizio, $dataFine])
            ->whereNotNull('fitok_calcolato_at')
            ->orderBy('data_fine', 'desc')
            ->get();
    }

    /**
     * Get summary of FITOK production for period
     */
    public function getRiepilogoFitokProduzione(Carbon $dataInizio, Carbon $dataFine): array
    {
        $lotti = $this->getLottiProduzioneFitok($dataInizio, $dataFine);
        $lottiByCertification = $lotti->groupBy(
            fn (LottoProduzione $lotto) => $lotto->getFitokCertificationStatus()
        );
        $lottiCertificabili = (int) $lottiByCertification
            ->get(LottoProduzione::FITOK_CERT_STATUS_CERTIFIABLE, collect())
            ->count();
        $lottiMisti = (int) $lottiByCertification
            ->get(LottoProduzione::FITOK_CERT_STATUS_MIXED, collect())
            ->count();
        $lottiNonFitok = (int) $lottiByCertification
            ->get(LottoProduzione::FITOK_CERT_STATUS_NON_FITOK, collect())
            ->count();
        $lottiInAttesa = (int) $lottiByCertification
            ->get(LottoProduzione::FITOK_CERT_STATUS_PENDING, collect())
            ->count();

        return [
            'totale_lotti' => $lotti->count(),
            'lotti_100_fitok' => $lottiCertificabili,
            'lotti_parziali' => $lottiMisti,
            'lotti_non_fitok' => $lottiNonFitok,
            'lotti_certificabili_fitok' => $lottiCertificabili,
            'lotti_non_certificabili_fitok' => $lottiMisti + $lottiNonFitok,
            'lotti_in_attesa_calcolo_fitok' => $lottiInAttesa,
            'volume_fitok_totale' => $lotti->sum('fitok_volume_mc'),
            'volume_non_fitok_totale' => $lotti->sum('non_fitok_volume_mc'),
            'percentuale_media' => $lotti->avg('fitok_percentuale') ?? 0,
        ];
    }

    private function getTipoLabel(string|\App\Enums\TipoMovimento $tipo): string
    {
        // Se è un enum, usa il valore stringa
        $tipoValue = $tipo instanceof \App\Enums\TipoMovimento ? $tipo->value : $tipo;
        
        return match ($tipoValue) {
            'carico' => 'Carico',
            'scarico' => 'Scarico',
            'rettifica_positiva' => 'Rettifica +',
            'rettifica_negativa' => 'Rettifica -',
            default => $tipoValue,
        };
    }

    private function getValiditaGiorniPerTrattamento(?string $tipoTrattamento): int
    {
        $default = max(1, (int) config('fitok.validita_default_giorni', 365));
        if (!$tipoTrattamento) {
            return $default;
        }

        $mappa = (array) config('fitok.validita_trattamenti', []);
        $key = strtoupper(trim($tipoTrattamento));
        if (!array_key_exists($key, $mappa)) {
            return $default;
        }

        return max(1, (int) $mappa[$key]);
    }

    private function getStatoCertificazioneUscitaLabel(?int $lottoProduzioneId, ?float $fitokPercentuale): string
    {
        if (!$lottoProduzioneId) {
            return '-';
        }

        return LottoProduzione::resolveFitokCertificationLabelFromPercentuale($fitokPercentuale);
    }

    private function movementTypeValue(mixed $tipo): string
    {
        if ($tipo instanceof \App\Enums\TipoMovimento) {
            return $tipo->value;
        }

        return strtolower(trim((string) $tipo));
    }
}
