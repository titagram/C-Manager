<?php

namespace App\Services\Production;

use App\Models\Costruzione;
use App\Models\Prodotto;

class LegaccioConstructionOptimizer
{
    public function __construct(
        private readonly RectangularStripPackingOptimizer $optimizer,
        private readonly LegaccioVariantResolver $variantResolver,
        private readonly LegaccioExcelRequirementsBuilder $excelRequirementsBuilder,
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
        $supportedRoutines = ['legacci224x60'];
        $routine = (string) ($variant['routine'] ?? '');
        $isSupportedRoutine = in_array($routine, $supportedRoutines, true);
        $excelPreview = $this->buildExcelPreviewIfSupported($variant, $context);
        $excelMode = strtolower($this->productionSettings->legaccioExcelMode());
        $excelCompatibilityEnabled = $excelMode === 'compatibility';
        $excelStrictEnabled = $excelMode === 'strict';

        $hasExcelPieces = is_array($excelPreview)
            && isset($excelPreview['pieces'])
            && is_array($excelPreview['pieces'])
            && $excelPreview['pieces'] !== [];

        if ($excelStrictEnabled && $isSupportedRoutine && !$hasExcelPieces) {
            throw new \InvalidArgumentException(sprintf(
                'Strict mode legaccio attivo: routine "%s" richiede requirements Excel validi (controllare numero pezzi e configurazione routine).',
                $routine
            ));
        }

        $useExcelPreviewPieces = ($excelCompatibilityEnabled || ($excelStrictEnabled && $isSupportedRoutine))
            && is_array($excelPreview)
            && isset($excelPreview['pieces'])
            && is_array($excelPreview['pieces'])
            && $excelPreview['pieces'] !== [];

        $piecesForPacking = $useExcelPreviewPieces ? $excelPreview['pieces'] : $pieces;

        $result = $this->optimizer->optimize(
            category: 'legaccio',
            costruzione: $costruzione,
            panelPieces: $piecesForPacking,
            materiale: $materiale,
            kerfMm: $kerfMm
        );

        $result['trace']['variant'] = $variant;
        $result['trace']['variant_routine'] = $variant['routine'] ?? null;
        $result['trace']['variant_family'] = $variant['family'] ?? null;
        $result['trace']['optimizer_mode'] = $useExcelPreviewPieces
            ? ($excelStrictEnabled ? 'excel_strict_v2' : 'excel_compatibility_v2')
            : (($variant['fallback_to_v1_rectangular'] ?? true)
            ? 'rectangular_v1_fallback'
            : 'excel_specific_v2');
        $result['trace']['excel_mode_requested'] = $excelMode;
        $result['trace']['excel_preview_applied'] = $useExcelPreviewPieces;
        $result['trace']['excel_strict_applied'] = $excelStrictEnabled && $isSupportedRoutine;
        $result['trace']['piece_source'] = $useExcelPreviewPieces
            ? 'excel_preview'
            : 'component_requirements_builder';
        $result['trace']['settings_snapshot'] = array_merge(
            (array) ($result['trace']['settings_snapshot'] ?? []),
            $this->productionSettings->snapshotForTrace([
                'cutting_kerf_mm',
                'legaccio_excel_mode',
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
        if ($routine !== 'legacci224x60') {
            return null;
        }

        $numeroPezzi = isset($context['numero_pezzi']) ? (int) $context['numero_pezzi'] : 1;

        return $this->excelRequirementsBuilder->buildForRoutine(
            routine: $routine,
            numeroPezzi: max(1, $numeroPezzi)
        );
    }
}
