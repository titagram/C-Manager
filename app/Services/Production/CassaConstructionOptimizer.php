<?php

namespace App\Services\Production;

use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Services\BinPackingService;

class CassaConstructionOptimizer
{
    private readonly BinPackingService $binPackingService;

    private readonly CassaVariantResolver $variantResolver;

    private readonly CassaExcelRequirementsBuilder $excelRequirementsBuilder;

    private readonly ?ProductionSettingsService $productionSettings;

    private readonly ?ScrapReusePlanner $scrapReusePlanner;

    public function __construct(
        BinPackingService $binPackingService,
        ?CassaVariantResolver $variantResolver = null,
        ?CassaExcelRequirementsBuilder $excelRequirementsBuilder = null,
        ?ProductionSettingsService $productionSettings = null,
        ?ScrapReusePlanner $scrapReusePlanner = null
    ) {
        $this->binPackingService = $binPackingService;
        $this->variantResolver = $variantResolver ?? app(CassaVariantResolver::class);
        $this->excelRequirementsBuilder = $excelRequirementsBuilder ?? app(CassaExcelRequirementsBuilder::class);
        $this->productionSettings = $productionSettings;
        $this->scrapReusePlanner = $scrapReusePlanner;
    }

    /**
     * Category-specific optimization for casse.
     *
     * - `physical`: uses real board dimensions/profile materials and enforces physical invariants.
     * - `excel_strict`: reproduces legacy Excel totals through virtual bins per legacy row.
     * - `legacy`: handled outside this optimizer by resolver fallback to 1D bin packing.
     *
     * @param  array<int, array{id:int, description:string, length:float, quantity:int, width?:float}>  $panelPieces
     * @return array<string, mixed>
     */
    public function optimize(
        Costruzione $costruzione,
        array $panelPieces,
        Prodotto $materiale,
        float $kerfMm,
        array $context = []
    ): array {
        $variant = $this->variantResolver->resolve($costruzione);
        $mode = $this->normalizeOptimizerMode(
            $this->productionSettings?->cassaOptimizerMode()
                ?? (string) config('production.cassa_optimizer_mode', 'physical')
        );

        if ($variant['uses_excel_builder']) {
            $dimensions = $this->resolveDimensionsContext($context);
            $excelRequirements = $this->excelRequirementsBuilder->buildForRoutine(
                routine: $variant['routine'],
                Lcm: $dimensions['larghezza_cm'],
                Wcm: $dimensions['profondita_cm'],
                Hcm: $dimensions['altezza_cm'],
                numeroPezzi: $dimensions['numero_pezzi']
            );

            if ($mode === 'excel_strict') {
                return $this->buildExcelStrictResult(
                    variant: $variant,
                    excelRequirements: $excelRequirements,
                    defaultMateriale: $materiale,
                    context: $context,
                    kerfMm: $kerfMm
                );
            }

            return $this->buildPhysicalResult(
                costruzione: $costruzione,
                variant: $variant,
                pieces: $excelRequirements['pieces'],
                defaultMateriale: $materiale,
                kerfMm: $kerfMm,
                context: $context,
                pieceSource: 'excel_requirements',
                extraTrace: [
                    'excel_rows' => $excelRequirements['rows'],
                    'excel_notes' => $excelRequirements['notes'],
                    'excel_reference' => $this->buildExcelReferenceSummary(
                        $excelRequirements['rows'],
                        $variant['routine']
                    ),
                    'workbook_alignment' => [
                        'strict_reference' => 'legacy_excel_rows',
                        'note' => 'Le routine cassa Excel usano il builder legacy come riferimento di audit per physical.',
                    ],
                ]
            );
        }

        if ($mode === 'excel_strict') {
            throw new \InvalidArgumentException(
                'La modalità excel_strict richiede una routine cassa supportata dal builder Excel.'
            );
        }

        return $this->buildPhysicalResult(
            costruzione: $costruzione,
            variant: $variant,
            pieces: $panelPieces,
            defaultMateriale: $materiale,
            kerfMm: $kerfMm,
            context: $context,
            pieceSource: 'costruzione_componenti',
            extraTrace: []
        );
    }

    /**
     * @param  array<string, mixed>  $variant
     * @param  array<string, mixed>  $excelRequirements
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function buildExcelStrictResult(
        array $variant,
        array $excelRequirements,
        Prodotto $defaultMateriale,
        array $context,
        float $kerfMm
    ): array {
        $profileMaterials = $this->resolveProfileMaterials(
            variant: $variant,
            defaultMateriale: $defaultMateriale,
            context: $context
        );
        $bins = [];
        $totalWasteMm = 0.0;

        foreach ($excelRequirements['rows'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $qtyTotal = max(0, (int) ($row['D_qty_total'] ?? 0));
            if ($qtyTotal <= 0) {
                continue;
            }

            $profileKey = (string) ($row['profile_key'] ?? 'base');
            $materiale = $profileMaterials[$profileKey] ?? $defaultMateriale;
            $snapshot = $this->materialSnapshot($materiale);
            $lengthMm = round((float) ($row['A_length_cm'] ?? 0) * 10, 4);
            $widthMm = round((float) ($row['C_width_cm'] ?? 0) * 10, 4);
            $sectionMm = round((float) ($row['B_section_mm'] ?? 0), 4);
            $volumePerUnitMc = $this->mm3ToMc($lengthMm * $widthMm * $sectionMm);

            for ($index = 0; $index < $qtyTotal; $index++) {
                $item = [
                    'id' => (int) ('9'.(string) ($row['row'] ?? 0)),
                    'description' => sprintf(
                        'Excel riga %d (%s)',
                        (int) ($row['row'] ?? 0),
                        $variant['label']
                    ),
                    'length' => $lengthMm,
                    'width' => $widthMm,
                    'source_profile' => $profileKey,
                    'legacy_row' => (int) ($row['row'] ?? 0),
                ];

                $bins[] = [
                    'items' => [$item],
                    'remaining_length' => 0.0,
                    'used_length' => $lengthMm,
                    'capacity' => $lengthMm,
                    'waste' => 0.0,
                    'waste_percent' => 0.0,
                    'source_profile' => $profileKey,
                    'source_type' => 'primary',
                    'source_material_id' => $materiale->id,
                    'source_material' => $snapshot,
                    'volume_lordo_mc' => round($volumePerUnitMc, 6),
                    'volume_netto_mc' => round($volumePerUnitMc, 6),
                    'volume_scarto_mc' => 0.0,
                ];
            }
        }

        return [
            'bins' => $bins,
            'total_bins' => count($bins),
            'bin_length' => 0.0,
            'total_waste' => $totalWasteMm,
            'total_waste_percent' => 0.0,
            'kerf' => max(0, $kerfMm),
            'component_assignments' => [],
            'cutting_totals' => [
                'volume_lordo_mc' => round((float) collect($bins)->sum('volume_lordo_mc'), 6),
                'volume_netto_mc' => round((float) collect($bins)->sum('volume_netto_mc'), 6),
                'volume_scarto_mc' => 0.0,
                'scarto_volume_percentuale' => 0.0,
            ],
            'optimizer' => [
                'name' => 'cassa-excel-strict',
                'version' => 'cassa-excel-strict-v1',
                'strategy' => 'excel-legacy-virtual-bins',
            ],
            'trace' => [
                'category' => 'cassa',
                'variant_routine' => $variant['routine'],
                'variant_label' => $variant['label'],
                'optimizer_mode' => 'excel_strict_v2',
                'piece_source' => 'excel_requirements',
                'excel_strict_applied' => true,
                'required_profiles' => $variant['required_profiles'],
                'resolved_material_profiles' => $this->materialProfilesTrace($profileMaterials),
                'excel_rows' => $excelRequirements['rows'],
                'excel_notes' => $excelRequirements['notes'],
                'settings_snapshot' => [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $variant
     * @param  array<int, array<string, mixed>>  $pieces
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $extraTrace
     * @return array<string, mixed>
     */
    private function buildPhysicalResult(
        Costruzione $costruzione,
        array $variant,
        array $pieces,
        Prodotto $defaultMateriale,
        float $kerfMm,
        array $context,
        string $pieceSource,
        array $extraTrace
    ): array {
        $profileMaterials = $this->resolveProfileMaterials(
            variant: $variant,
            defaultMateriale: $defaultMateriale,
            context: $context
        );

        $groupedPieces = $this->groupPiecesByProfile($pieces, $variant['required_profiles']);
        $mergedBins = [];
        $scrapSuggestions = [];
        $panelSummary = [];
        $skippedPanels = [];

        foreach ($groupedPieces as $profileKey => $profilePieces) {
            if ($profilePieces === []) {
                continue;
            }

            $materiale = $profileMaterials[$profileKey] ?? $defaultMateriale;
            $profileMeta = $this->profileMeta($variant, $profileKey);
            if ($variant['uses_excel_builder']) {
                $piecesForPacking = $this->prepareExcelPiecesForPacking($profilePieces);
            } else {
                $geometricPreparation = $this->prepareGeometricPiecesForPacking(
                    $profilePieces,
                    $materiale,
                    $costruzione,
                    $context
                );
                $piecesForPacking = $geometricPreparation['pieces'];
                $panelSummary = array_merge($panelSummary, $geometricPreparation['panel_summary']);
                $skippedPanels = array_merge($skippedPanels, $geometricPreparation['skipped_panels']);
            }
            $this->assertProfileMaterialCompatible($profileKey, $materiale, $profileMeta, $piecesForPacking);

            $scrapSuggestion = $this->buildScrapSuggestion(
                materiale: $materiale,
                piecesForPacking: $piecesForPacking,
                kerfMm: $kerfMm,
                context: $context
            );

            if ($scrapSuggestion !== null) {
                $scrapSuggestions[] = $scrapSuggestion;

                if ((bool) ($scrapSuggestion['used'] ?? false)) {
                    $piecesForPacking = is_array($scrapSuggestion['pieces_after_reuse'] ?? null)
                        ? $scrapSuggestion['pieces_after_reuse']
                        : [];
                }
            }

            if ($piecesForPacking === []) {
                continue;
            }

            $packed = $this->binPackingService->pack(
                $piecesForPacking,
                (float) ($materiale->lunghezza_mm ?? 0),
                max(0, $kerfMm)
            );
            $packed['bins'] = $this->attachSourceMaterialToBins(
                $packed['bins'] ?? [],
                $materiale,
                $profileKey
            );

            $mergedBins = array_merge($mergedBins, is_array($packed['bins'] ?? null) ? $packed['bins'] : []);
        }

        $mergedScrapSuggestion = $this->mergeScrapSuggestions($scrapSuggestions);

        if ($mergedBins === [] && ! (bool) ($mergedScrapSuggestion['used'] ?? false)) {
            throw new \InvalidArgumentException('Nessun pannello compatibile da ottimizzare per la categoria cassa.');
        }

        $merged = $mergedBins === []
            ? $this->emptyPhysicalResult(max(0, $kerfMm))
            : $this->finalizeMergedPhysicalResult($mergedBins, max(0, $kerfMm));
        $componentAssignments = $this->mergeComponentAssignments(
            $this->binPackingService->buildComponentAssignmentsForBins($merged['bins'], max(0, $kerfMm)),
            $this->buildScrapAssignments($mergedScrapSuggestion)
        );
        $merged['component_assignments'] = $componentAssignments;
        $merged['trace'] = array_merge([
            'category' => 'cassa',
            'variant_routine' => $variant['routine'],
            'variant_label' => $variant['label'],
            'optimizer_mode' => 'physical_v2',
            'piece_source' => $pieceSource,
            'excel_strict_applied' => false,
            'required_profiles' => $variant['required_profiles'],
            'resolved_material_profiles' => $this->materialProfilesTrace($profileMaterials),
            'scrap_reuse' => $this->buildScrapTrace($mergedScrapSuggestion),
            'panel_summary' => $panelSummary,
            'skipped_panels' => $skippedPanels,
            'component_summary' => $this->buildComponentSummaryFromAssignments($componentAssignments),
            'settings_snapshot' => [],
        ], $extraTrace);

        $merged['optimizer'] = [
            'name' => 'cassa',
            'version' => $variant['uses_excel_builder'] ? 'cassa-physical-v2' : 'cassa-strips-v1',
            'strategy' => $variant['uses_excel_builder']
                ? 'excel-requirements-by-profile-then-1d-bfd'
                : 'panel-to-strips-by-profile-then-1d-bfd',
        ];

        return $merged;
    }

    /**
     * @param  array<int, array<string, mixed>>  $piecesForPacking
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    private function buildScrapSuggestion(
        Prodotto $materiale,
        array $piecesForPacking,
        float $kerfMm,
        array $context
    ): ?array {
        $scrapReuseConfig = is_array($context['scrap_reuse'] ?? null) ? $context['scrap_reuse'] : [];

        if (! (bool) ($scrapReuseConfig['enabled'] ?? false)) {
            return null;
        }

        $suggestion = ($this->scrapReusePlanner ?? app(ScrapReusePlanner::class))->plan(
            materiale: $materiale,
            pieces: $piecesForPacking,
            kerfMm: max(0, $kerfMm),
            minReusableLengthMm: max(0, (int) ($scrapReuseConfig['min_reusable_length_mm'] ?? 0))
        );

        $suggestion['used'] = (int) ($suggestion['matched_count'] ?? 0) > 0
            && ! ((bool) ($scrapReuseConfig['ignore'] ?? false));

        return $suggestion;
    }

    /**
     * @param  array<string, mixed>  $variant
     * @param  array<string, mixed>  $context
     * @return array<string, Prodotto>
     */
    private function resolveProfileMaterials(array $variant, Prodotto $defaultMateriale, array $context): array
    {
        $selectedProfiles = is_array($context['selected_primary_materials'] ?? null)
            ? $context['selected_primary_materials']
            : [];
        $resolved = [];

        foreach ($variant['required_profiles'] as $profileMeta) {
            if (! is_array($profileMeta)) {
                continue;
            }

            $profileKey = (string) ($profileMeta['key'] ?? 'base');
            $selected = $selectedProfiles[$profileKey] ?? null;

            if ($selected instanceof Prodotto) {
                $resolved[$profileKey] = $selected;

                continue;
            }

            if ($profileKey === 'base') {
                $resolved[$profileKey] = $defaultMateriale;

                continue;
            }

            throw new \InvalidArgumentException(
                sprintf('Seleziona un materiale valido per il profilo "%s".', $profileMeta['label'] ?? $profileKey)
            );
        }

        if ($resolved === []) {
            $resolved['base'] = $defaultMateriale;
        }

        return $resolved;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pieces
     * @param  array<int, array<string, mixed>>  $requiredProfiles
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupPiecesByProfile(array $pieces, array $requiredProfiles): array
    {
        $profileKeys = collect($requiredProfiles)
            ->filter(fn ($profile) => is_array($profile))
            ->map(fn (array $profile) => (string) ($profile['key'] ?? 'base'))
            ->values()
            ->all();

        if ($profileKeys === []) {
            $profileKeys = ['base'];
        }

        $grouped = array_fill_keys($profileKeys, []);

        foreach ($pieces as $piece) {
            if (! is_array($piece)) {
                continue;
            }

            $profileKey = (string) ($piece['source_profile'] ?? 'base');
            if (! array_key_exists($profileKey, $grouped)) {
                $grouped[$profileKey] = [];
            }

            $grouped[$profileKey][] = $piece;
        }

        return $grouped;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pieces
     * @return array<int, array<string, mixed>>
     */
    private function prepareExcelPiecesForPacking(array $pieces): array
    {
        return array_values(array_map(function (array $piece): array {
            return [
                'id' => (int) ($piece['id'] ?? 0),
                'description' => (string) ($piece['description'] ?? 'Componente'),
                'length' => (float) ($piece['length'] ?? 0),
                'width' => (float) ($piece['width'] ?? 0),
                'quantity' => max(0, (int) ($piece['quantity'] ?? 0)),
            ];
        }, $pieces));
    }

    /**
     * @param  array<int, array<string, mixed>>  $panelPieces
     * @param  array<string, mixed>  $context
     * @return array{
     *   pieces: array<int, array<string, mixed>>,
     *   panel_summary: array<int, array<string, mixed>>,
     *   skipped_panels: array<int, array<string, mixed>>
     * }
     */
    private function prepareGeometricPiecesForPacking(
        array $panelPieces,
        Prodotto $materiale,
        Costruzione $costruzione,
        array $context
    ): array {
        $boardWidthMm = (float) ($materiale->larghezza_mm ?? 0);
        $boardThicknessMm = (float) ($materiale->spessore_mm ?? 0);

        if ($boardWidthMm <= 0 || $boardThicknessMm <= 0 || (float) ($materiale->lunghezza_mm ?? 0) <= 0) {
            throw new \InvalidArgumentException(
                'Il materiale selezionato non e compatibile con l optimizer cassa: lunghezza/larghezza/spessore asse mancanti.'
            );
        }

        $config = is_array($costruzione->config) ? $costruzione->config : [];
        $includeCoperchio = (bool) ($config['ha_coperchio'] ?? false);
        $stripPieces = [];
        $panelSummary = [];
        $skippedPanels = [];

        foreach ($panelPieces as $panel) {
            $panelLengthMm = (float) ($panel['length'] ?? 0);
            $panelWidthMm = isset($panel['width']) ? (float) $panel['width'] : null;
            $panelQty = max(0, (int) ($panel['quantity'] ?? 0));
            $panelName = (string) ($panel['description'] ?? 'Componente');

            if ($panelLengthMm <= 0 || $panelQty <= 0) {
                continue;
            }

            if ($this->isCoperchio($panelName) && ! $includeCoperchio) {
                $skippedPanels[] = [
                    'id' => (int) ($panel['id'] ?? 0),
                    'description' => $panelName,
                    'reason' => 'coperchio_disabilitato',
                ];

                continue;
            }

            if ($panelWidthMm === null || $panelWidthMm <= 0) {
                throw new \InvalidArgumentException(sprintf(
                    'Il componente "%s" richiede formula_larghezza valida per l optimizer cassa.',
                    $panelName
                ));
            }

            $expanded = $this->expandPanelToStrips(
                panelId: (int) ($panel['id'] ?? 0),
                panelName: $panelName,
                panelLengthMm: $panelLengthMm,
                panelWidthMm: $panelWidthMm,
                panelQty: $panelQty,
                boardWidthMm: $boardWidthMm
            );

            array_push($stripPieces, ...$expanded['strip_pieces']);
            $panelSummary[] = [
                'id' => (int) ($panel['id'] ?? 0),
                'description' => $panelName,
                'panel_length_mm' => $panelLengthMm,
                'panel_width_mm' => $panelWidthMm,
                'panel_quantity' => $panelQty,
                'strips_per_panel' => $expanded['strips_per_panel'],
                'full_width_strips_per_panel' => $expanded['full_strips_per_panel'],
                'last_strip_width_mm' => $expanded['last_strip_width_mm'],
                'total_strips' => $expanded['total_strips'],
            ];
        }

        return [
            'pieces' => $stripPieces,
            'panel_summary' => $panelSummary,
            'skipped_panels' => $skippedPanels,
        ];
    }

    /**
     * @param  array<int, mixed>  $bins
     * @return array<int, array<string, mixed>>
     */
    private function attachSourceMaterialToBins(array $bins, Prodotto $materiale, string $profileKey): array
    {
        $snapshot = $this->materialSnapshot($materiale);

        return array_values(array_map(function (mixed $bin) use ($snapshot, $profileKey, $materiale): array {
            $normalized = is_array($bin) ? $bin : [];
            $normalized['source_profile'] = $profileKey;
            $normalized['source_type'] = 'primary';
            $normalized['source_material_id'] = $materiale->id;
            $normalized['source_material'] = $snapshot;

            $normalized['items'] = array_values(array_map(function (mixed $item) use ($profileKey): array {
                $normalizedItem = is_array($item) ? $item : [];
                $normalizedItem['source_profile'] = $profileKey;

                return $normalizedItem;
            }, is_array($normalized['items'] ?? null) ? $normalized['items'] : []));

            return $normalized;
        }, $bins));
    }

    /**
     * @param  array<int, array<string, mixed>>  $bins
     * @return array<string, mixed>
     */
    private function finalizeMergedPhysicalResult(array $bins, float $kerfMm): array
    {
        $totalWaste = collect($bins)->sum(fn (array $bin): float => (float) ($bin['waste'] ?? 0));
        $totalCapacity = collect($bins)->sum(fn (array $bin): float => (float) ($bin['capacity'] ?? 0));
        $result = [
            'bins' => array_values($bins),
            'total_bins' => count($bins),
            'bin_length' => 0.0,
            'total_waste' => round($totalWaste, 2),
            'total_waste_percent' => $totalCapacity > 0
                ? round(($totalWaste / $totalCapacity) * 100, 2)
                : 0.0,
            'kerf' => $kerfMm,
        ];

        $result = $this->enrichWithVolumeMetrics($result);

        $gross = (float) data_get($result, 'cutting_totals.volume_lordo_mc', 0);
        $net = (float) data_get($result, 'cutting_totals.volume_netto_mc', 0);
        if ($gross + 0.000001 < $net) {
            throw new \RuntimeException('Optimizer cassa physical ha prodotto un volume lordo inferiore al netto.');
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPhysicalResult(float $kerfMm): array
    {
        return [
            'bins' => [],
            'total_bins' => 0,
            'bin_length' => 0.0,
            'total_waste' => 0.0,
            'total_waste_percent' => 0.0,
            'kerf' => $kerfMm,
            'cutting_totals' => [
                'volume_lordo_mc' => 0.0,
                'volume_netto_mc' => 0.0,
                'volume_scarto_mc' => 0.0,
                'scarto_volume_percentuale' => 0.0,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $variant
     * @param  array<string, mixed>  $profileMeta
     * @param  array<int, array<string, mixed>>  $pieces
     */
    private function assertProfileMaterialCompatible(
        string $profileKey,
        Prodotto $materiale,
        array $profileMeta,
        array $pieces
    ): void {
        $boardLengthMm = (float) ($materiale->lunghezza_mm ?? 0);
        $boardWidthMm = (float) ($materiale->larghezza_mm ?? 0);
        $boardThicknessMm = (float) ($materiale->spessore_mm ?? 0);

        if ($boardLengthMm <= 0 || $boardWidthMm <= 0 || $boardThicknessMm <= 0) {
            throw new \InvalidArgumentException(sprintf(
                'Il materiale del profilo "%s" deve avere lunghezza, larghezza e spessore valorizzati.',
                $profileKey
            ));
        }

        $expectedThickness = round((float) ($profileMeta['thickness_mm'] ?? 0), 2);
        if ($expectedThickness > 0 && round($boardThicknessMm, 2) !== $expectedThickness) {
            throw new \InvalidArgumentException(sprintf(
                'Il materiale "%s" non e compatibile con il profilo "%s": spessore richiesto %.0f mm, trovato %.0f mm.',
                $materiale->nome,
                $profileMeta['label'] ?? $profileKey,
                $expectedThickness,
                $boardThicknessMm
            ));
        }

        $maxPieceWidth = collect($pieces)->max(fn (array $piece): float => (float) ($piece['width'] ?? 0)) ?? 0.0;
        $requiredWidth = max(
            (float) ($profileMeta['min_width_mm'] ?? 0),
            (float) $maxPieceWidth
        );

        if ($requiredWidth > 0 && $boardWidthMm + 0.0001 < $requiredWidth) {
            throw new \InvalidArgumentException(sprintf(
                'Il materiale "%s" non copre il profilo "%s": larghezza minima richiesta %.0f mm, disponibile %.0f mm.',
                $materiale->nome,
                $profileMeta['label'] ?? $profileKey,
                $requiredWidth,
                $boardWidthMm
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $variant
     * @return array<string, mixed>
     */
    private function profileMeta(array $variant, string $profileKey): array
    {
        foreach ($variant['required_profiles'] as $profile) {
            if (is_array($profile) && (string) ($profile['key'] ?? '') === $profileKey) {
                return $profile;
            }
        }

        return [
            'key' => $profileKey,
            'label' => $profileKey,
            'thickness_mm' => 0.0,
            'min_width_mm' => 0.0,
        ];
    }

    /**
     * @param  array<string, Prodotto>  $profileMaterials
     * @return array<string, mixed>
     */
    private function materialProfilesTrace(array $profileMaterials): array
    {
        $trace = [];

        foreach ($profileMaterials as $profileKey => $materiale) {
            $trace[$profileKey] = $this->materialSnapshot($materiale);
        }

        return $trace;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function buildExcelReferenceSummary(array $rows, string $routine): array
    {
        $volumeLordo = 0.0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $volumeLordo += $this->mm3ToMc(
                ((float) ($row['A_length_cm'] ?? 0) * 10)
                * (float) ($row['B_section_mm'] ?? 0)
                * ((float) ($row['C_width_cm'] ?? 0) * 10)
                * (float) ($row['D_qty_total'] ?? 0)
            );
        }

        return [
            'routine' => $routine,
            'volume_lordo_mc' => round($volumeLordo, 6),
            'volume_netto_mc' => round($volumeLordo, 6),
            'volume_scarto_mc' => 0.0,
        ];
    }

    /**
     * @return array{larghezza_cm:float,profondita_cm:float,altezza_cm:float,numero_pezzi:int}
     */
    private function resolveDimensionsContext(array $context): array
    {
        $larghezzaCm = (float) ($context['larghezza_cm'] ?? 0);
        $profonditaCm = (float) ($context['profondita_cm'] ?? 0);
        $altezzaCm = (float) ($context['altezza_cm'] ?? 0);
        $numeroPezzi = max(1, (int) ($context['numero_pezzi'] ?? 1));

        if ($larghezzaCm <= 0 || $profonditaCm <= 0 || $altezzaCm <= 0) {
            throw new \InvalidArgumentException('Dimensioni cassa mancanti o non valide per il builder Excel.');
        }

        return [
            'larghezza_cm' => $larghezzaCm,
            'profondita_cm' => $profonditaCm,
            'altezza_cm' => $altezzaCm,
            'numero_pezzi' => $numeroPezzi,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function materialSnapshot(Prodotto $materiale): array
    {
        return [
            'id' => $materiale->id,
            'nome' => $materiale->nome,
            'codice' => $materiale->codice,
            'lunghezza_mm' => (float) ($materiale->lunghezza_mm ?? 0),
            'larghezza_mm' => (float) ($materiale->larghezza_mm ?? 0),
            'spessore_mm' => (float) ($materiale->spessore_mm ?? 0),
            'unita_misura' => $materiale->unita_misura?->value ?? 'mc',
            'costo_unitario' => $materiale->costo_unitario,
            'prezzo_unitario' => $materiale->prezzo_unitario,
            'prezzo_mc' => $materiale->prezzo_mc,
            'soggetto_fitok' => (bool) ($materiale->soggetto_fitok ?? false),
        ];
    }

    /**
     * @param  array<string, mixed>  $packed
     * @return array<string, mixed>
     */
    private function enrichWithVolumeMetrics(array $packed): array
    {
        if (! isset($packed['bins']) || ! is_array($packed['bins'])) {
            return $packed;
        }

        $grossTotal = 0.0;
        $netTotal = 0.0;
        $scrapTotal = 0.0;

        foreach ($packed['bins'] as $index => $bin) {
            if (! is_array($bin)) {
                continue;
            }

            $materialSnapshot = is_array($bin['source_material'] ?? null) ? $bin['source_material'] : [];
            $boardLengthMm = (float) ($bin['capacity'] ?? 0);
            $boardWidthMm = (float) ($materialSnapshot['larghezza_mm'] ?? 0);
            $boardThicknessMm = (float) ($materialSnapshot['spessore_mm'] ?? 0);
            $grossVolumeMc = $this->mm3ToMc($boardLengthMm * $boardWidthMm * $boardThicknessMm);

            $netVolumeMc = 0.0;
            foreach (($bin['items'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $itemLengthMm = max(0.0, (float) ($item['length'] ?? 0));
                $itemWidthMm = max(0.0, (float) ($item['width'] ?? $boardWidthMm));
                $netVolumeMc += $this->mm3ToMc($itemLengthMm * $itemWidthMm * $boardThicknessMm);
            }

            $scrapVolumeMc = max(0.0, $grossVolumeMc - $netVolumeMc);

            $packed['bins'][$index]['volume_lordo_mc'] = round($grossVolumeMc, 6);
            $packed['bins'][$index]['volume_netto_mc'] = round($netVolumeMc, 6);
            $packed['bins'][$index]['volume_scarto_mc'] = round($scrapVolumeMc, 6);

            $grossTotal += $grossVolumeMc;
            $netTotal += $netVolumeMc;
            $scrapTotal += $scrapVolumeMc;
        }

        $packed['cutting_totals'] = [
            'volume_lordo_mc' => round($grossTotal, 6),
            'volume_netto_mc' => round($netTotal, 6),
            'volume_scarto_mc' => round($scrapTotal, 6),
            'scarto_volume_percentuale' => $grossTotal > 0
                ? round(($scrapTotal / $grossTotal) * 100, 2)
                : 0.0,
        ];

        return $packed;
    }

    private function normalizeOptimizerMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return match ($mode) {
            'category' => 'physical',
            'legacy', 'excel_strict', 'physical' => $mode,
            default => 'physical',
        };
    }

    private function isCoperchio(string $name): bool
    {
        return str_contains(strtolower($name), 'coperchio');
    }

    /**
     * @return array{
     *   strips_per_panel:int,
     *   full_strips_per_panel:int,
     *   last_strip_width_mm: float,
     *   total_strips:int,
     *   strip_pieces: array<int, array{id:int, description:string, length:float, quantity:int, width:float}>
     * }
     */
    private function expandPanelToStrips(
        int $panelId,
        string $panelName,
        float $panelLengthMm,
        float $panelWidthMm,
        int $panelQty,
        float $boardWidthMm
    ): array {
        $stripsPerPanel = (int) ceil($panelWidthMm / $boardWidthMm);
        $fullStripsPerPanel = (int) floor($panelWidthMm / $boardWidthMm);
        $remainderWidth = round($panelWidthMm - ($fullStripsPerPanel * $boardWidthMm), 4);

        $stripPieces = [];

        if ($fullStripsPerPanel > 0) {
            $stripPieces[] = [
                'id' => $panelId,
                'description' => $panelName,
                'length' => $panelLengthMm,
                'quantity' => $fullStripsPerPanel * $panelQty,
                'width' => $boardWidthMm,
            ];
        }

        if ($remainderWidth > 0.0001) {
            $stripPieces[] = [
                'id' => $panelId,
                'description' => $panelName,
                'length' => $panelLengthMm,
                'quantity' => $panelQty,
                'width' => $remainderWidth,
            ];
        }

        if ($stripPieces === []) {
            $stripPieces[] = [
                'id' => $panelId,
                'description' => $panelName,
                'length' => $panelLengthMm,
                'quantity' => $panelQty,
                'width' => min($panelWidthMm, $boardWidthMm),
            ];
        }

        return [
            'strips_per_panel' => max(1, $stripsPerPanel),
            'full_strips_per_panel' => max(0, $fullStripsPerPanel),
            'last_strip_width_mm' => $remainderWidth > 0.0001 ? $remainderWidth : (float) $boardWidthMm,
            'total_strips' => array_sum(array_map(
                fn (array $piece): int => (int) ($piece['quantity'] ?? 0),
                $stripPieces
            )),
            'strip_pieces' => $stripPieces,
        ];
    }

    private function mm3ToMc(float $mm3): float
    {
        return $mm3 / 1000000000;
    }

    /**
     * @param  array<string, mixed>|null  $scrapSuggestion
     * @return array<int, array<string, mixed>>
     */
    private function buildScrapAssignments(?array $scrapSuggestion): array
    {
        if (! is_array($scrapSuggestion) || ! ($scrapSuggestion['used'] ?? false)) {
            return [];
        }

        return collect($scrapSuggestion['matches'] ?? [])
            ->filter(fn ($match) => is_array($match) && array_key_exists('component_id', $match))
            ->groupBy(fn (array $match) => (string) ($match['component_id'] ?? '__null__'))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'component_id' => $first['component_id'] ?? null,
                    'description' => (string) ($first['piece_label'] ?? 'Componente'),
                    'produced_strips' => $group->count(),
                    'produced_length_mm' => round((float) $group->sum('required_length_mm'), 2),
                    'produced_required_space_mm' => round((float) $group->sum('required_length_mm'), 2),
                    'allocated_waste_mm' => 0.0,
                    'assigned_bins' => [],
                    'assigned_boards_count' => 0,
                    'source' => 'scrap_reuse',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $scrapSuggestions
     * @return array<string, mixed>|null
     */
    private function mergeScrapSuggestions(array $scrapSuggestions): ?array
    {
        $validSuggestions = collect($scrapSuggestions)
            ->filter(fn ($suggestion) => is_array($suggestion))
            ->values();

        if ($validSuggestions->isEmpty()) {
            return null;
        }

        $sourceSummaries = $validSuggestions
            ->flatMap(fn (array $suggestion) => is_array($suggestion['source_summaries'] ?? null) ? $suggestion['source_summaries'] : [])
            ->filter(fn ($row) => is_array($row))
            ->unique(fn (array $row): string => (string) ($row['scrap_id'] ?? spl_object_hash((object) $row)))
            ->values()
            ->all();

        return [
            'required_count' => (int) $validSuggestions->sum(fn (array $suggestion): int => (int) ($suggestion['required_count'] ?? 0)),
            'available_scraps_count' => count($sourceSummaries),
            'matched_count' => (int) $validSuggestions->sum(fn (array $suggestion): int => (int) ($suggestion['matched_count'] ?? 0)),
            'used_scrap_ids' => $validSuggestions
                ->flatMap(fn (array $suggestion) => $suggestion['used_scrap_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all(),
            'matches' => $validSuggestions
                ->flatMap(fn (array $suggestion) => $suggestion['matches'] ?? [])
                ->filter(fn ($row) => is_array($row))
                ->values()
                ->all(),
            'used' => $validSuggestions->contains(fn (array $suggestion): bool => (bool) ($suggestion['used'] ?? false)),
            'source_summaries' => $sourceSummaries,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $scrapSuggestion
     * @return array<string, mixed>|null
     */
    private function buildScrapTrace(?array $scrapSuggestion): ?array
    {
        if (! is_array($scrapSuggestion)) {
            return null;
        }

        return [
            'check_enabled' => true,
            'used' => (bool) ($scrapSuggestion['used'] ?? false),
            'matched_count' => (int) ($scrapSuggestion['matched_count'] ?? 0),
            'required_count' => (int) ($scrapSuggestion['required_count'] ?? 0),
            'used_scrap_ids' => $scrapSuggestion['used_scrap_ids'] ?? [],
            'matches' => $scrapSuggestion['matches'] ?? [],
            'available_scraps_count' => (int) ($scrapSuggestion['available_scraps_count'] ?? 0),
            'source_summaries' => $scrapSuggestion['source_summaries'] ?? [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  ...$assignmentSets
     * @return array<int, array<string, mixed>>
     */
    private function mergeComponentAssignments(array ...$assignmentSets): array
    {
        $merged = [];

        foreach ($assignmentSets as $assignmentSet) {
            foreach ($assignmentSet as $assignment) {
                if (! is_array($assignment)) {
                    continue;
                }

                $key = array_key_exists('component_id', $assignment) && $assignment['component_id'] !== null
                    ? (string) $assignment['component_id']
                    : '__null__';

                if (! isset($merged[$key])) {
                    $merged[$key] = [
                        'component_id' => $assignment['component_id'] ?? null,
                        'description' => (string) ($assignment['description'] ?? 'Componente'),
                        'produced_strips' => 0,
                        'produced_length_mm' => 0.0,
                        'produced_required_space_mm' => 0.0,
                        'allocated_waste_mm' => 0.0,
                        'assigned_bins' => [],
                        'assigned_boards_count' => 0,
                    ];
                }

                $merged[$key]['produced_strips'] += (int) ($assignment['produced_strips'] ?? 0);
                $merged[$key]['produced_length_mm'] += (float) ($assignment['produced_length_mm'] ?? 0);
                $merged[$key]['produced_required_space_mm'] += (float) ($assignment['produced_required_space_mm'] ?? 0);
                $merged[$key]['allocated_waste_mm'] += (float) ($assignment['allocated_waste_mm'] ?? 0);
                $merged[$key]['assigned_bins'] = array_values(array_merge(
                    $merged[$key]['assigned_bins'],
                    is_array($assignment['assigned_bins'] ?? null) ? $assignment['assigned_bins'] : []
                ));
                $merged[$key]['assigned_boards_count'] = count($merged[$key]['assigned_bins']);
            }
        }

        return array_values($merged);
    }

    /**
     * @param  array<int, array<string, mixed>>  $componentAssignments
     * @return array<int, array<string, mixed>>
     */
    private function buildComponentSummaryFromAssignments(array $componentAssignments): array
    {
        return collect($componentAssignments)
            ->filter(fn ($assignment) => is_array($assignment))
            ->map(function (array $assignment): array {
                return [
                    'id' => $assignment['component_id'] ?? null,
                    'description' => (string) ($assignment['description'] ?? 'Componente'),
                    'requested_strips' => (int) ($assignment['produced_strips'] ?? 0),
                    'produced_strips' => (int) ($assignment['produced_strips'] ?? 0),
                    'missing_strips' => 0,
                    'extra_strips' => 0,
                    'allocated_waste_mm' => round((float) ($assignment['allocated_waste_mm'] ?? 0), 2),
                    'assigned_boards_count' => (int) ($assignment['assigned_boards_count'] ?? 0),
                    'assigned_bins' => is_array($assignment['assigned_bins'] ?? null) ? $assignment['assigned_bins'] : [],
                ];
            })
            ->values()
            ->all();
    }
}
