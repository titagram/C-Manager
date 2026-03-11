<?php

namespace App\Services;

use App\Enums\TipoMovimento;
use App\Models\ConsumoMateriale;
use App\Models\LottoProduzioneMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\Scarto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryAnomalyService
{
    /**
     * @return array{
     *   period: array{from:string,to:string},
     *   kpis: array{
     *     rettifiche_negative_count:int,
     *     rettifiche_negative_qty:float,
     *     rettifiche_negative_without_reason_code_count:int,
     *     rettifiche_negative_reason_coverage_percent:float,
     *     rettifiche_sospetto_ammanco_qty:float,
     *     scarti_mismatch_lotti_count:int,
     *     scarti_mismatch_delta_mc:float,
     *     consumi_senza_movimento_count:int
     *   },
     *   top_lotti_rischio: array<int, array{
     *     lotto_produzione_id:int,
     *     codice_lotto:string,
     *     volume_scarto_teorico_mc:float,
     *     volume_scarto_registrato_mc:float,
     *     delta_scarto_mc:float
     *   }>,
     *   top_materiali_rettifiche: array<int, array{
     *     lotto_materiale_id:int,
     *     codice_lotto:string,
     *     quantita_rettifiche_negative:float,
     *     quantita_sospetto_ammanco:float,
     *     movimenti_count:int
     *   }>
     * }
     */
    public function analyzePeriod(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $negativeAdjustments = MovimentoMagazzino::query()
            ->where('tipo', TipoMovimento::RETTIFICA_NEGATIVA->value)
            ->whereBetween('data_movimento', [$from, $to])
            ->get(['id', 'quantita', 'causale_codice']);

        $negativeCount = $negativeAdjustments->count();
        $negativeQty = (float) $negativeAdjustments->sum('quantita');
        $missingReasonCount = $negativeAdjustments
            ->filter(fn ($row) => !MovimentoMagazzino::isValidNegativeAdjustmentReasonCode($row->causale_codice))
            ->count();
        $withReasonCount = max(0, $negativeCount - $missingReasonCount);
        $reasonCoveragePercent = $negativeCount > 0
            ? round(($withReasonCount / $negativeCount) * 100, 2)
            : 100.0;
        $suspectedShortageQty = (float) $negativeAdjustments
            ->where('causale_codice', MovimentoMagazzino::REASON_CODE_SUSPECTED_SHORTAGE)
            ->sum('quantita');

        $scrapMismatches = $this->calculateScrapMismatches($from, $to);
        $consumiSenzaMovimentoCount = $this->calculateConsumiSenzaMovimentoCount($from, $to);
        $topMaterialiRettifiche = $this->buildTopMaterialLotsForNegativeAdjustments($from, $to);

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'kpis' => [
                'rettifiche_negative_count' => $negativeCount,
                'rettifiche_negative_qty' => round($negativeQty, 4),
                'rettifiche_negative_without_reason_code_count' => $missingReasonCount,
                'rettifiche_negative_reason_coverage_percent' => $reasonCoveragePercent,
                'rettifiche_sospetto_ammanco_qty' => round($suspectedShortageQty, 4),
                'scarti_mismatch_lotti_count' => $scrapMismatches->count(),
                'scarti_mismatch_delta_mc' => round((float) $scrapMismatches->sum('delta_scarto_mc'), 6),
                'consumi_senza_movimento_count' => $consumiSenzaMovimentoCount,
            ],
            'top_lotti_rischio' => $scrapMismatches->take(10)->values()->all(),
            'top_materiali_rettifiche' => $topMaterialiRettifiche->all(),
        ];
    }

    public function analyzeLastDays(int $days = 30): array
    {
        $days = max(1, $days);

        return $this->analyzePeriod(
            now()->subDays($days - 1)->startOfDay(),
            now()->endOfDay(),
        );
    }

    /**
     * @return Collection<int, array{
     *   lotto_produzione_id:int,
     *   codice_lotto:string,
     *   volume_scarto_teorico_mc:float,
     *   volume_scarto_registrato_mc:float,
     *   delta_scarto_mc:float
     * }>
     */
    private function calculateScrapMismatches(Carbon $from, Carbon $to): Collection
    {
        $teorico = LottoProduzioneMateriale::query()
            ->join('lotti_produzione', 'lotto_produzione_materiali.lotto_produzione_id', '=', 'lotti_produzione.id')
            ->whereBetween('lotti_produzione.created_at', [$from, $to])
            ->groupBy('lotto_produzione_materiali.lotto_produzione_id', 'lotti_produzione.codice_lotto')
            ->selectRaw('
                lotto_produzione_materiali.lotto_produzione_id as lotto_produzione_id,
                lotti_produzione.codice_lotto as codice_lotto,
                COALESCE(SUM(lotto_produzione_materiali.volume_scarto_mc), 0) as volume_scarto_teorico_mc
            ')
            ->get()
            ->keyBy('lotto_produzione_id');

        $registrato = Scarto::query()
            ->join('lotti_produzione', 'scarti.lotto_produzione_id', '=', 'lotti_produzione.id')
            ->whereBetween('lotti_produzione.created_at', [$from, $to])
            ->groupBy('scarti.lotto_produzione_id')
            ->selectRaw('
                scarti.lotto_produzione_id as lotto_produzione_id,
                COALESCE(SUM(scarti.volume_mc), 0) as volume_scarto_registrato_mc
            ')
            ->get()
            ->keyBy('lotto_produzione_id');

        $lottoIds = collect($teorico->keys())
            ->merge($registrato->keys())
            ->unique()
            ->values();

        return $lottoIds
            ->map(function ($lottoId) use ($teorico, $registrato) {
                $teoricoRow = $teorico->get($lottoId);
                $registratoRow = $registrato->get($lottoId);

                $teoricoVolume = (float) ($teoricoRow->volume_scarto_teorico_mc ?? 0);
                $registratoVolume = (float) ($registratoRow->volume_scarto_registrato_mc ?? 0);
                $delta = round(abs($teoricoVolume - $registratoVolume), 6);

                return [
                    'lotto_produzione_id' => (int) $lottoId,
                    'codice_lotto' => (string) ($teoricoRow->codice_lotto ?? "LP-{$lottoId}"),
                    'volume_scarto_teorico_mc' => round($teoricoVolume, 6),
                    'volume_scarto_registrato_mc' => round($registratoVolume, 6),
                    'delta_scarto_mc' => $delta,
                ];
            })
            ->filter(fn (array $row) => $row['delta_scarto_mc'] > 0.0001)
            ->sortByDesc('delta_scarto_mc')
            ->values();
    }

    private function calculateConsumiSenzaMovimentoCount(Carbon $from, Carbon $to): int
    {
        return ConsumoMateriale::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('movimenti_magazzino')
                    ->whereColumn('movimenti_magazzino.lotto_materiale_id', 'consumi_materiale.lotto_materiale_id')
                    ->whereColumn('movimenti_magazzino.lotto_produzione_id', 'consumi_materiale.lotto_produzione_id')
                    ->whereIn('movimenti_magazzino.tipo', [
                        TipoMovimento::SCARICO->value,
                        TipoMovimento::RETTIFICA_NEGATIVA->value,
                    ]);
            })
            ->count();
    }

    /**
     * @return Collection<int, array{
     *   lotto_materiale_id:int,
     *   codice_lotto:string,
     *   quantita_rettifiche_negative:float,
     *   quantita_sospetto_ammanco:float,
     *   movimenti_count:int
     * }>
     */
    private function buildTopMaterialLotsForNegativeAdjustments(Carbon $from, Carbon $to): Collection
    {
        return MovimentoMagazzino::query()
            ->join('lotti_materiale', 'movimenti_magazzino.lotto_materiale_id', '=', 'lotti_materiale.id')
            ->where('movimenti_magazzino.tipo', TipoMovimento::RETTIFICA_NEGATIVA->value)
            ->whereBetween('movimenti_magazzino.data_movimento', [$from, $to])
            ->groupBy('movimenti_magazzino.lotto_materiale_id', 'lotti_materiale.codice_lotto')
            ->selectRaw('
                movimenti_magazzino.lotto_materiale_id as lotto_materiale_id,
                lotti_materiale.codice_lotto as codice_lotto,
                COALESCE(SUM(movimenti_magazzino.quantita), 0) as quantita_rettifiche_negative,
                COALESCE(SUM(CASE WHEN movimenti_magazzino.causale_codice = ? THEN movimenti_magazzino.quantita ELSE 0 END), 0) as quantita_sospetto_ammanco,
                COUNT(*) as movimenti_count
            ', [MovimentoMagazzino::REASON_CODE_SUSPECTED_SHORTAGE])
            ->orderByDesc('quantita_rettifiche_negative')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'lotto_materiale_id' => (int) $row->lotto_materiale_id,
                    'codice_lotto' => (string) $row->codice_lotto,
                    'quantita_rettifiche_negative' => round((float) $row->quantita_rettifiche_negative, 4),
                    'quantita_sospetto_ammanco' => round((float) $row->quantita_sospetto_ammanco, 4),
                    'movimenti_count' => (int) $row->movimenti_count,
                ];
            })
            ->values();
    }
}

