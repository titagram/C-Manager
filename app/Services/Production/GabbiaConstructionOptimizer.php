<?php

namespace App\Services\Production;

use App\Models\Costruzione;
use App\Models\Prodotto;

class GabbiaConstructionOptimizer
{
    private const VERSION_V1_FALLBACK = 'gabbia-strips-v1';
    private const VERSION_V2_EXCEL = 'gabbia-excel-v2';

    private const STRATEGY_V1_FALLBACK = 'panel-to-strips-then-1d-bfd';
    private const STRATEGY_V2_EXCEL = 'excel-requirements-to-strips-then-1d-bfd';

    public function __construct(
        private readonly RectangularStripPackingOptimizer $optimizer,
        private readonly GabbiaVariantResolver $variantResolver,
        private readonly GabbiaExcelRequirementsBuilder $excelRequirementsBuilder,
        private readonly GabbiaRoutineCatalog $routineCatalog,
        private readonly ProductionSettingsService $productionSettings
    ) {}

    /**
     * @param array<int, array{id:int, description:string, length:float, quantity:int, width?:float}> $pieces
     * @return array<string, mixed>
     */
    public function optimize(
        Costruzione $costruzione,
        array $pieces,
        Prodotto $materiale,
        float $kerfMm,
        array $context = []
    ): array
    {
        $variant = $this->variantResolver->resolve($costruzione);
        $routine = (string) ($variant['routine'] ?? '');
        $isSupportedRoutine = $this->routineCatalog->has($routine);
        $excelPreview = $this->buildExcelPreviewIfSupported($variant, $context);
        $excelMode = strtolower($this->productionSettings->gabbiaExcelMode());
        $excelCompatibilityEnabled = $excelMode === 'compatibility';
        $excelStrictEnabled = $excelMode === 'strict';

        $hasExcelPieces = is_array($excelPreview)
            && isset($excelPreview['pieces'])
            && is_array($excelPreview['pieces'])
            && $excelPreview['pieces'] !== [];

        if ($excelStrictEnabled && $isSupportedRoutine && !$hasExcelPieces) {
            throw new \InvalidArgumentException(sprintf(
                'Strict mode gabbia attivo: routine "%s" richiede requirements Excel validi (controllare L/W/H e numero pezzi).',
                $routine
            ));
        }

        $useExcelPreviewPieces = ($excelCompatibilityEnabled || ($excelStrictEnabled && $isSupportedRoutine))
            && is_array($excelPreview)
            && isset($excelPreview['pieces'])
            && is_array($excelPreview['pieces'])
            && $excelPreview['pieces'] !== [];

        $optimizerMode = $useExcelPreviewPieces
            ? ($excelStrictEnabled ? 'excel_strict_v2' : 'excel_compatibility_v2')
            : (($variant['fallback_to_v1_rectangular'] ?? true)
            ? 'rectangular_v1_fallback'
            : 'excel_specific_v2');

        $piecesForPacking = $useExcelPreviewPieces ? $excelPreview['pieces'] : $pieces;

        $result = $this->optimizer->optimize(
            category: 'gabbia',
            costruzione: $costruzione,
            panelPieces: $piecesForPacking,
            materiale: $materiale,
            kerfMm: $kerfMm,
            options: [
                // If future constructions set config.ha_coperchio = false, respect it.
                'respect_ha_coperchio_config' => true,
                'optimizer_version' => $useExcelPreviewPieces
                    ? self::VERSION_V2_EXCEL
                    : self::VERSION_V1_FALLBACK,
                'strategy' => $useExcelPreviewPieces
                    ? self::STRATEGY_V2_EXCEL
                    : self::STRATEGY_V1_FALLBACK,
            ]
        );

        $result['trace']['variant'] = $variant;
        $result['trace']['variant_routine'] = $variant['routine'] ?? null;
        $result['trace']['variant_family'] = $variant['family'] ?? null;
        $result['trace']['optimizer_mode'] = $optimizerMode;
        $result['trace']['excel_mode_requested'] = $excelMode;
        $result['trace']['excel_preview_applied'] = $useExcelPreviewPieces;
        $result['trace']['excel_strict_applied'] = $excelStrictEnabled && $isSupportedRoutine;
        $result['trace']['excel_routine_supported'] = $isSupportedRoutine;
        $result['trace']['piece_source'] = $useExcelPreviewPieces
            ? 'excel_preview'
            : 'component_requirements_builder';
        $result['trace']['category_optimizer_version'] = (string) data_get($result, 'optimizer.version', '');
        $result['trace']['category_optimizer_strategy'] = (string) data_get($result, 'optimizer.strategy', '');
        $result['trace']['settings_snapshot'] = array_merge(
            (array) ($result['trace']['settings_snapshot'] ?? []),
            $this->productionSettings->snapshotForTrace([
                'cutting_kerf_mm',
                'gabbia_excel_mode',
            ])
        );

        if ($excelPreview !== null) {
            $result['trace']['excel_preview'] = $excelPreview;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $variant
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null
     */
    private function buildExcelPreviewIfSupported(array $variant, array $context): ?array
    {
        $routine = (string) ($variant['routine'] ?? '');
        if (!$this->routineCatalog->has($routine)) {
            return null;
        }

        $Lcm = isset($context['larghezza_cm']) ? (float) $context['larghezza_cm'] : null;
        $Wcm = isset($context['profondita_cm']) ? (float) $context['profondita_cm'] : null;
        $Hcm = isset($context['altezza_cm']) ? (float) $context['altezza_cm'] : null;
        $numeroPezzi = isset($context['numero_pezzi']) ? (int) $context['numero_pezzi'] : 1;

        if ($Lcm === null || $Wcm === null || $Hcm === null || $Lcm <= 0 || $Wcm <= 0 || $Hcm <= 0) {
            return null;
        }

        return $this->excelRequirementsBuilder->buildForRoutine(
            routine: $routine,
            Lcm: $Lcm,
            Wcm: $Wcm,
            Hcm: $Hcm,
            numeroPezzi: max(1, $numeroPezzi)
        );
    }
}
