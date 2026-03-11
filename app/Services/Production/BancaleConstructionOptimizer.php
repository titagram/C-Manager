<?php

namespace App\Services\Production;

use App\Models\Costruzione;
use App\Models\Prodotto;

class BancaleConstructionOptimizer
{
    public function __construct(
        private readonly RectangularStripPackingOptimizer $optimizer,
        private readonly BancaleVariantResolver $variantResolver,
        private readonly BancaleExcelRequirementsBuilder $excelRequirementsBuilder,
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
        $supportedRoutines = ['bancale', 'perimetrale'];
        $routine = (string) ($variant['routine'] ?? '');
        $isSupportedRoutine = in_array($routine, $supportedRoutines, true);
        $excelPreview = $this->buildExcelPreviewIfSupported($variant, $context);
        $excelMode = strtolower($this->productionSettings->bancaleExcelMode());
        $excelCompatibilityEnabled = $excelMode === 'compatibility';
        $excelStrictEnabled = $excelMode === 'strict';

        $hasExcelPieces = is_array($excelPreview)
            && isset($excelPreview['pieces'])
            && is_array($excelPreview['pieces'])
            && $excelPreview['pieces'] !== [];

        if ($excelStrictEnabled && $isSupportedRoutine && !$hasExcelPieces) {
            throw new \InvalidArgumentException(sprintf(
                'Strict mode bancale attivo: routine "%s" richiede requirements Excel validi (controllare L/W/H e numero pezzi).',
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
            category: 'bancale',
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
                'bancale_excel_mode',
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
        if (!in_array($routine, ['bancale', 'perimetrale'], true)) {
            return null;
        }

        $Lcm = isset($context['larghezza_cm']) ? (float) $context['larghezza_cm'] : null;
        $Wcm = isset($context['profondita_cm']) ? (float) $context['profondita_cm'] : null;
        $Hcm = isset($context['altezza_cm']) ? (float) $context['altezza_cm'] : null;
        $numeroPezzi = isset($context['numero_pezzi']) ? (int) $context['numero_pezzi'] : 1;

        if ($Lcm === null || $Wcm === null || $Lcm <= 0 || $Wcm <= 0) {
            return null;
        }

        if ($routine === 'perimetrale' && ($Hcm === null || $Hcm <= 0)) {
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
