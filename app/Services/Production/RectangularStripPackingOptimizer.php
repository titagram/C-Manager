<?php

namespace App\Services\Production;

use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Services\BinPackingService;

class RectangularStripPackingOptimizer
{
    public function __construct(
        private readonly BinPackingService $binPackingService,
        private readonly ?ScrapReusePlanner $scrapReusePlanner = null
    ) {}

    /**
     * Generic rectangular-piece optimizer:
     * - expands each rectangular requirement into strips based on stock board width
     * - packs strips in 1D on stock board length (BFD)
     *
     * @param array<int, array{id:int, description:string, length:float, quantity:int, width?:float}> $panelPieces
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function optimize(
        string $category,
        Costruzione $costruzione,
        array $panelPieces,
        Prodotto $materiale,
        float $kerfMm,
        array $options = []
    ): array {
        $boardLengthMm = (float) ($materiale->lunghezza_mm ?? 0);
        $boardWidthMm = (float) ($materiale->larghezza_mm ?? 0);
        $boardThicknessMm = (float) ($materiale->spessore_mm ?? 0);

        if ($boardLengthMm <= 0 || $boardWidthMm <= 0 || $boardThicknessMm <= 0) {
            throw new \InvalidArgumentException(sprintf(
                'Il materiale selezionato non e compatibile con l\'optimizer %s: lunghezza/larghezza/spessore asse mancanti.',
                $category
            ));
        }

        $config = is_array($costruzione->config) ? $costruzione->config : [];

        $respectHaCoperchioConfig = (bool) ($options['respect_ha_coperchio_config'] ?? false);
        $includeCoperchio = array_key_exists('ha_coperchio', $config)
            ? (bool) $config['ha_coperchio']
            : true;
        $rotationAllowed = (bool) ($options['rotation_allowed'] ?? false);

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

            if ($respectHaCoperchioConfig && $this->isCoperchio($panelName) && !$includeCoperchio) {
                $skippedPanels[] = [
                    'id' => $panel['id'] ?? null,
                    'description' => $panelName,
                    'reason' => 'coperchio_disabilitato_da_config',
                ];
                continue;
            }

            if ($panelWidthMm === null || $panelWidthMm <= 0) {
                throw new \InvalidArgumentException(sprintf(
                    'Il componente "%s" richiede formula_larghezza valida per l\'optimizer %s.',
                    $panelName,
                    $category
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

        if ($stripPieces === []) {
            throw new \InvalidArgumentException(sprintf(
                'Nessun componente compatibile da ottimizzare per la categoria %s.',
                $category
            ));
        }

        $scrapReuseConfig = is_array($options['scrap_reuse'] ?? null) ? $options['scrap_reuse'] : [];
        $scrapSuggestion = null;
        if ((bool) ($scrapReuseConfig['enabled'] ?? false)) {
            $scrapSuggestion = ($this->scrapReusePlanner ?? app(ScrapReusePlanner::class))->plan(
                materiale: $materiale,
                pieces: $stripPieces,
                kerfMm: max(0, $kerfMm),
                minReusableLengthMm: max(0, (int) ($scrapReuseConfig['min_reusable_length_mm'] ?? 0))
            );

            $shouldUseScraps = (int) ($scrapSuggestion['matched_count'] ?? 0) > 0
                && ! ((bool) ($scrapReuseConfig['ignore'] ?? false));
            $scrapSuggestion['used'] = $shouldUseScraps;

            if ($shouldUseScraps) {
                $stripPieces = $scrapSuggestion['pieces_after_reuse'];
            }
        }

        $packed = $stripPieces !== []
            ? $this->binPackingService->pack($stripPieces, $boardLengthMm, max(0, $kerfMm))
            : [
                'bins' => [],
                'total_bins' => 0,
                'bin_length' => $boardLengthMm,
                'total_waste' => 0,
                'total_waste_percent' => 0,
                'kerf' => max(0, $kerfMm),
                'component_assignments' => [],
            ];
        $packed = $this->enrichWithVolumeMetrics($packed, $boardWidthMm, $boardThicknessMm);
        $componentAssignments = $this->mergeComponentAssignments(
            is_array($packed['component_assignments'] ?? null) ? $packed['component_assignments'] : [],
            $this->buildScrapAssignments($scrapSuggestion)
        );
        $componentSummary = $this->buildComponentSummary(
            $panelSummary,
            $componentAssignments
        );
        $packed['component_assignments'] = $componentAssignments;

        $packed['optimizer'] = [
            'name' => $category,
            'version' => (string) ($options['optimizer_version'] ?? "{$category}-strips-v1"),
            'strategy' => (string) ($options['strategy'] ?? 'panel-to-strips-then-1d-bfd'),
        ];

        $packed['trace'] = [
            'category' => $category,
            'assumptions' => [
                'rotation_allowed' => $rotationAllowed,
                'stripization_from_panel_width' => true,
                'respect_ha_coperchio_config' => $respectHaCoperchioConfig,
                'include_coperchio' => $respectHaCoperchioConfig ? $includeCoperchio : null,
            ],
            'board' => [
                'length_mm' => $boardLengthMm,
                'width_mm' => $boardWidthMm,
                'thickness_mm' => $boardThicknessMm,
                'kerf_mm' => max(0, $kerfMm),
            ],
            'scrap_reuse' => $scrapSuggestion !== null
                ? [
                    'check_enabled' => true,
                    'used' => (bool) ($scrapSuggestion['used'] ?? false),
                    'matched_count' => (int) ($scrapSuggestion['matched_count'] ?? 0),
                    'required_count' => (int) ($scrapSuggestion['required_count'] ?? 0),
                    'used_scrap_ids' => $scrapSuggestion['used_scrap_ids'] ?? [],
                    'matches' => $scrapSuggestion['matches'] ?? [],
                    'available_scraps_count' => (int) ($scrapSuggestion['available_scraps_count'] ?? 0),
                    'source_summaries' => $scrapSuggestion['source_summaries'] ?? [],
                ]
                : null,
            'panel_summary' => $panelSummary,
            'component_summary' => $componentSummary,
            'component_assignments' => $componentAssignments,
            'skipped_panels' => $skippedPanels,
        ];

        return $packed;
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
                fn(array $piece): int => (int) ($piece['quantity'] ?? 0),
                $stripPieces
            )),
            'strip_pieces' => $stripPieces,
        ];
    }

    /**
     * @param array<string, mixed> $packed
     * @return array<string, mixed>
     */
    private function enrichWithVolumeMetrics(array $packed, float $boardWidthMm, float $boardThicknessMm): array
    {
        if (!isset($packed['bins']) || !is_array($packed['bins'])) {
            return $packed;
        }

        $grossTotal = 0.0;
        $netTotal = 0.0;
        $scrapTotal = 0.0;

        foreach ($packed['bins'] as $index => $bin) {
            if (!is_array($bin)) {
                continue;
            }

            $boardLengthMm = (float) ($bin['capacity'] ?? $packed['bin_length'] ?? 0);
            $grossVolumeMc = $this->mm3ToMc($boardLengthMm * $boardWidthMm * $boardThicknessMm);

            $netVolumeMc = 0.0;
            foreach (($bin['items'] ?? []) as $item) {
                if (!is_array($item)) {
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
     * @param array<int, array<string, mixed>> $panelSummary
     * @param array<int, array<string, mixed>> $componentAssignments
     * @return array<int, array<string, mixed>>
     */
    private function buildComponentSummary(array $panelSummary, array $componentAssignments): array
    {
        $assignmentByComponent = [];
        foreach ($componentAssignments as $assignment) {
            if (!is_array($assignment)) {
                continue;
            }

            $componentId = $assignment['component_id'] ?? null;
            $assignmentByComponent[$componentId === null ? '__null__' : (string) $componentId] = $assignment;
        }

        $summary = [];
        $seenKeys = [];

        foreach ($panelSummary as $panel) {
            if (!is_array($panel)) {
                continue;
            }

            $panelId = $panel['id'] ?? null;
            $panelKey = $panelId === null ? '__null__' : (string) $panelId;
            $seenKeys[$panelKey] = true;
            $assignment = $assignmentByComponent[$panelKey] ?? [];

            $requestedStrips = max(0, (int) ($panel['total_strips'] ?? 0));
            $producedStrips = max(0, (int) ($assignment['produced_strips'] ?? 0));

            $summary[] = [
                'id' => $panelId,
                'description' => (string) ($panel['description'] ?? 'Componente'),
                'requested_strips' => $requestedStrips,
                'produced_strips' => $producedStrips,
                'missing_strips' => max(0, $requestedStrips - $producedStrips),
                'extra_strips' => max(0, $producedStrips - $requestedStrips),
                'allocated_waste_mm' => round((float) ($assignment['allocated_waste_mm'] ?? 0), 2),
                'assigned_boards_count' => (int) ($assignment['assigned_boards_count'] ?? 0),
                'assigned_bins' => is_array($assignment['assigned_bins'] ?? null) ? $assignment['assigned_bins'] : [],
            ];
        }

        foreach ($assignmentByComponent as $componentKey => $assignment) {
            if (isset($seenKeys[$componentKey])) {
                continue;
            }

            $producedStrips = max(0, (int) ($assignment['produced_strips'] ?? 0));
            $summary[] = [
                'id' => $assignment['component_id'] ?? null,
                'description' => (string) ($assignment['description'] ?? 'Componente'),
                'requested_strips' => 0,
                'produced_strips' => $producedStrips,
                'missing_strips' => 0,
                'extra_strips' => $producedStrips,
                'allocated_waste_mm' => round((float) ($assignment['allocated_waste_mm'] ?? 0), 2),
                'assigned_boards_count' => (int) ($assignment['assigned_boards_count'] ?? 0),
                'assigned_bins' => is_array($assignment['assigned_bins'] ?? null) ? $assignment['assigned_bins'] : [],
            ];
        }

        return $summary;
    }
}
