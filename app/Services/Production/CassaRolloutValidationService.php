<?php

namespace App\Services\Production;

use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use App\Services\BinPackingService;

class CassaRolloutValidationService
{
    public function __construct(
        private readonly ComponentRequirementsBuilder $componentRequirementsBuilder,
        private readonly CassaConstructionOptimizer $cassaOptimizer,
        private readonly BinPackingService $binPackingService,
        private readonly ProductionSettingsService $productionSettings
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyzeLotto(LottoProduzione $lotto): array
    {
        try {
            $costruzione = $this->resolveCostruzione($lotto);
            $materiale = $this->resolveMateriale($lotto);

            if ($materiale === null) {
                throw new \InvalidArgumentException('Materiale asse non risolvibile dal lotto.');
            }

            [$larghezzaCm, $profonditaCm, $altezzaCm, $numeroPezzi] = $this->resolveDimensions($lotto);

            $requirements = $this->componentRequirementsBuilder->buildCalculatedPieces(
                costruzione: $costruzione->loadMissing('componenti'),
                materiale: $materiale,
                larghezzaCm: $larghezzaCm,
                profonditaCm: $profonditaCm,
                altezzaCm: $altezzaCm,
                numeroPezzi: $numeroPezzi,
                userId: $lotto->created_by
            );

            $piecesToPack = $requirements['pieces'] ?? [];
            if (! is_array($piecesToPack) || $piecesToPack === []) {
                $firstError = (string) ($requirements['errors'][0] ?? 'Nessun componente calcolato disponibile.');
                throw new \InvalidArgumentException($firstError);
            }

            $binLengthMm = (float) ($materiale->lunghezza_mm ?? 0);
            if ($binLengthMm <= 0) {
                throw new \InvalidArgumentException('Materiale con lunghezza asse non valida.');
            }

            $kerfMm = $this->productionSettings->cuttingKerfMm();

            $categoryResult = $this->cassaOptimizer->optimize(
                costruzione: $costruzione,
                panelPieces: $piecesToPack,
                materiale: $materiale,
                kerfMm: $kerfMm,
                context: [
                    'larghezza_cm' => $larghezzaCm,
                    'profondita_cm' => $profonditaCm,
                    'altezza_cm' => $altezzaCm,
                    'numero_pezzi' => $numeroPezzi,
                    'selected_primary_materials' => $this->resolveSelectedPrimaryMaterials($lotto, $materiale),
                ]
            );

            $legacyResult = $this->binPackingService->pack($piecesToPack, $binLengthMm, max(0, $kerfMm));

            $categoryMetrics = $this->buildMetrics(
                result: $categoryResult,
                materiale: $materiale,
                optimizerName: 'cassa'
            );
            $legacyMetrics = $this->buildMetrics(
                result: $legacyResult,
                materiale: $materiale,
                optimizerName: 'legacy-bin-packing'
            );

            $deltas = [
                'total_bins' => abs((int) $categoryMetrics['total_bins'] - (int) $legacyMetrics['total_bins']),
                'total_waste_percent' => abs((float) $categoryMetrics['total_waste_percent'] - (float) $legacyMetrics['total_waste_percent']),
                'volume_lordo_mc' => abs((float) $categoryMetrics['volume_lordo_mc'] - (float) $legacyMetrics['volume_lordo_mc']),
                'volume_netto_mc' => abs((float) ($categoryMetrics['volume_netto_mc'] ?? 0.0) - (float) ($legacyMetrics['volume_netto_mc'] ?? 0.0)),
            ];

            $thresholds = [
                'total_bins_delta' => 1,
                'waste_percent_delta' => $this->wasteDeltaThresholdPercent(),
                'volume_lordo_delta_mc' => $this->volumeDeltaThresholdMc(),
                'volume_netto_delta_mc' => $this->volumeDeltaThresholdMc(),
            ];

            $significant = $deltas['total_bins'] >= $thresholds['total_bins_delta']
                || $deltas['total_waste_percent'] >= $thresholds['waste_percent_delta']
                || $deltas['volume_lordo_mc'] >= $thresholds['volume_lordo_delta_mc']
                || $deltas['volume_netto_mc'] >= $thresholds['volume_netto_delta_mc'];

            return [
                'status' => 'ok',
                'significant' => $significant,
                'lotto' => [
                    'id' => $lotto->id,
                    'codice_lotto' => $lotto->codice_lotto,
                    'costruzione_id' => $costruzione->id,
                    'costruzione_slug' => $costruzione->slug,
                    'materiale_id' => $materiale->id,
                    'dimensions_cm' => [
                        'larghezza' => $larghezzaCm,
                        'profondita' => $profonditaCm,
                        'altezza' => $altezzaCm,
                        'numero_pezzi' => $numeroPezzi,
                    ],
                ],
                'pieces_count' => count($piecesToPack),
                'active' => $categoryMetrics,
                'legacy' => $legacyMetrics,
                'deltas' => $deltas,
                'thresholds' => $thresholds,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'significant' => false,
                'lotto' => [
                    'id' => $lotto->id,
                    'codice_lotto' => $lotto->codice_lotto,
                ],
                'error' => $e->getMessage(),
            ];
        }
    }

    private function resolveCostruzione(LottoProduzione $lotto): Costruzione
    {
        $costruzione = $lotto->costruzione;
        if (! $costruzione) {
            throw new \InvalidArgumentException('Costruzione non presente sul lotto.');
        }

        if (strtolower((string) $costruzione->categoria) !== 'cassa') {
            throw new \InvalidArgumentException('Il lotto non appartiene alla categoria cassa.');
        }

        return $costruzione;
    }

    private function resolveMateriale(LottoProduzione $lotto): ?Prodotto
    {
        $profileMaterialId = $lotto->primaryMaterialProfiles()
            ->where('profile_key', 'base')
            ->value('prodotto_id');
        if (is_numeric($profileMaterialId)) {
            $materiale = Prodotto::query()->find((int) $profileMaterialId);
            if ($materiale) {
                return $materiale;
            }
        }

        $optimizerMaterialId = data_get($lotto->optimizer_result, 'materiale.id');
        if (is_numeric($optimizerMaterialId)) {
            $materiale = Prodotto::query()->find((int) $optimizerMaterialId);
            if ($materiale) {
                return $materiale;
            }
        }

        $materialeId = $lotto->materialiUsati()->value('prodotto_id');
        if (is_numeric($materialeId)) {
            return Prodotto::query()->find((int) $materialeId);
        }

        return null;
    }

    /**
     * @return array<string, Prodotto>
     */
    private function resolveSelectedPrimaryMaterials(LottoProduzione $lotto, Prodotto $fallbackMateriale): array
    {
        $selected = [];

        foreach ($lotto->primaryMaterialProfiles()->with('prodotto')->get() as $profile) {
            if ($profile->prodotto) {
                $selected[(string) $profile->profile_key] = $profile->prodotto;
            }
        }

        if ($selected === []) {
            $selected['base'] = $fallbackMateriale;
        }

        return $selected;
    }

    /**
     * @return array{0:float,1:float,2:float,3:int}
     */
    private function resolveDimensions(LottoProduzione $lotto): array
    {
        $larghezzaCm = (float) ($lotto->larghezza_cm ?? 0);
        $profonditaCm = (float) ($lotto->profondita_cm ?? 0);
        $altezzaCm = (float) ($lotto->altezza_cm ?? 0);
        $numeroPezzi = max(1, (int) ($lotto->numero_pezzi ?? 1));

        if ($larghezzaCm <= 0 || $profonditaCm <= 0 || $altezzaCm <= 0) {
            throw new \InvalidArgumentException('Dimensioni lotto mancanti o non valide.');
        }

        return [$larghezzaCm, $profonditaCm, $altezzaCm, $numeroPezzi];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function buildMetrics(array $result, Prodotto $materiale, string $optimizerName): array
    {
        $binLengthMm = (float) ($materiale->lunghezza_mm ?? 0);
        $boardWidthMm = (float) ($materiale->larghezza_mm ?? 0);
        $thicknessMm = (float) ($materiale->spessore_mm ?? 0);
        $boardVolumeMc = ($binLengthMm > 0 && $boardWidthMm > 0 && $thicknessMm > 0)
            ? ($binLengthMm * $boardWidthMm * $thicknessMm) / 1000000000
            : 0.0;

        $boardsCount = (int) ($result['total_bins'] ?? 0);
        $volumeLordo = data_get($result, 'cutting_totals.volume_lordo_mc');
        if ($volumeLordo === null) {
            $volumeLordo = $boardsCount * $boardVolumeMc;
        }
        $volumeLordo = round(max(0, (float) $volumeLordo), 6);

        $volumeNetto = data_get($result, 'cutting_totals.volume_netto_mc');
        if ($volumeNetto === null) {
            $volumeNetto = $this->deriveNetVolumeFromBins($result['bins'] ?? null, $thicknessMm);
        }
        $volumeNetto = $volumeNetto !== null ? round(max(0, (float) $volumeNetto), 6) : null;

        return [
            'optimizer' => $optimizerName,
            'total_bins' => $boardsCount,
            'total_waste_percent' => round(max(0, (float) ($result['total_waste_percent'] ?? 0)), 2),
            'total_waste_mm' => round(max(0, (float) ($result['total_waste'] ?? 0)), 2),
            'volume_lordo_mc' => $volumeLordo,
            'volume_netto_mc' => $volumeNetto,
        ];
    }

    private function deriveNetVolumeFromBins(mixed $bins, float $thicknessMm): ?float
    {
        if (! is_array($bins) || $thicknessMm <= 0) {
            return null;
        }

        $hasItems = false;
        $netVolume = 0.0;

        foreach ($bins as $bin) {
            if (! is_array($bin)) {
                continue;
            }

            foreach (($bin['items'] ?? []) as $item) {
                if (! is_array($item) || ! array_key_exists('width', $item)) {
                    return null;
                }

                $lengthMm = max(0.0, (float) ($item['length'] ?? 0));
                $widthMm = max(0.0, (float) ($item['width'] ?? 0));
                $netVolume += ($lengthMm * $widthMm * $thicknessMm) / 1000000000;
                $hasItems = true;
            }
        }

        return $hasItems ? $netVolume : null;
    }

    private function volumeDeltaThresholdMc(): float
    {
        return max(0.0, (float) config('production.cassa_shadow_compare_volume_delta_mc', 0.0005));
    }

    private function wasteDeltaThresholdPercent(): float
    {
        return max(0.0, (float) config('production.cassa_shadow_compare_waste_delta_percent', 0.5));
    }
}
