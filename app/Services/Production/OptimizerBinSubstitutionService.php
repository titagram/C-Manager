<?php

namespace App\Services\Production;

use App\Enums\UnitaMisura;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use App\Services\BinPackingService;
use Illuminate\Support\Arr;

class OptimizerBinSubstitutionService
{
    public function __construct(
        private readonly BinPackingService $binPackingService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, int|string>  $selectedBinIndexes
     * @return array<string, mixed>
     */
    public function substitute(array $payload, array $selectedBinIndexes, Prodotto $candidateMaterial): array
    {
        $topLevelMaterial = is_array($payload['materiale'] ?? null) ? $payload['materiale'] : null;
        $bins = array_values(array_map(function (mixed $bin) use ($topLevelMaterial): array {
            $normalized = is_array($bin) ? $bin : [];
            if (! is_array($normalized['source_material'] ?? null) && $topLevelMaterial !== null) {
                $normalized['source_material'] = $topLevelMaterial;
                $normalized['source_material_id'] = $topLevelMaterial['id'] ?? null;
                $normalized['source_type'] = $normalized['source_type'] ?? 'primary';
            }

            return $normalized;
        }, array_filter(
            is_array($payload['bins'] ?? null) ? $payload['bins'] : [],
            fn (mixed $bin): bool => is_array($bin)
        )));

        if ($bins === []) {
            throw new \InvalidArgumentException('Nessun bin disponibile per la sostituzione.');
        }

        $selectedIndexes = collect($selectedBinIndexes)
            ->map(fn ($index) => (int) $index)
            ->filter(fn (int $index) => $index >= 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($selectedIndexes === []) {
            throw new \InvalidArgumentException('Seleziona almeno un asse da sostituire.');
        }

        $primaryThickness = round((float) data_get($payload, 'materiale.spessore_mm', 0), 2);
        $candidateThickness = round((float) ($candidateMaterial->spessore_mm ?? 0), 2);
        $primaryWidth = round((float) data_get($payload, 'materiale.larghezza_mm', 0), 2);
        $candidateWidth = round((float) ($candidateMaterial->larghezza_mm ?? 0), 2);

        if ($primaryThickness <= 0 || $candidateThickness <= 0 || abs($primaryThickness - $candidateThickness) > 0.0001) {
            throw new \InvalidArgumentException(
                'Il materiale sostitutivo deve avere lo stesso spessore del materiale principale del lotto.'
            );
        }

        if ($primaryWidth <= 0 || $candidateWidth <= 0 || abs($primaryWidth - $candidateWidth) > 0.0001) {
            throw new \InvalidArgumentException(
                'Il materiale sostitutivo deve avere la stessa larghezza del materiale principale del lotto.'
            );
        }

        $candidateLengthMm = (float) ($candidateMaterial->lunghezza_mm ?? 0);
        $candidateWidthMm = (float) ($candidateMaterial->larghezza_mm ?? 0);
        if ($candidateLengthMm <= 0 || $candidateWidthMm <= 0) {
            throw new \InvalidArgumentException(
                'Il materiale sostitutivo deve avere lunghezza e larghezza definite.'
            );
        }

        $selectedItems = [];
        $untouchedBins = [];
        $selectedBins = [];

        foreach ($bins as $index => $bin) {
            if (in_array($index, $selectedIndexes, true)) {
                $items = is_array($bin['items'] ?? null) ? array_values($bin['items']) : [];
                if ($items === []) {
                    throw new \InvalidArgumentException(
                        sprintf('Il bin #%d non contiene tagli sostituibili.', $index + 1)
                    );
                }

                $selectedBins[] = $bin;

                foreach ($items as $itemIndex => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $selectedItems[] = [
                        'id' => $item['id'] ?? null,
                        'description' => (string) ($item['description'] ?? ('Pezzo ' . ($itemIndex + 1))),
                        'length' => (float) ($item['length'] ?? 0),
                        'width' => array_key_exists('width', $item) ? (float) $item['width'] : null,
                        'quantity' => 1,
                    ];
                }

                continue;
            }

            $untouchedBins[] = $bin;
        }

        if ($selectedBins === []) {
            throw new \InvalidArgumentException('I bin selezionati non esistono più nel piano corrente.');
        }

        $kerf = max(0.0, (float) ($payload['kerf'] ?? 0));
        $packed = $this->packSelectedItemsForMaterial($selectedItems, $candidateMaterial, $kerf);
        $replacementBins = $this->decorateBinsForMaterial(
            bins: is_array($packed['bins'] ?? null) ? $packed['bins'] : [],
            material: $candidateMaterial,
            sourceType: 'substituted'
        );

        $insertAt = min($selectedIndexes);
        $mergedBins = [];
        $untouchedPointer = 0;
        $untouchedCount = count($untouchedBins);

        for ($position = 0; $position <= count($bins); $position++) {
            if ($position === $insertAt) {
                array_push($mergedBins, ...$replacementBins);
            }

            if ($untouchedPointer < $untouchedCount) {
                $mergedBins[] = $untouchedBins[$untouchedPointer++];
            }
        }

        $updated = $payload;
        $updated['bins'] = array_values($mergedBins);
        $updated['total_bins'] = count($updated['bins']);
        $updated['total_waste'] = round($this->sumWaste($updated['bins']), 2);
        $updated['total_waste_percent'] = round($this->calculateWastePercent($updated['bins']), 2);
        $updated['component_assignments'] = $this->buildComponentAssignments($updated['bins']);
        $updated['trace']['component_assignments'] = $updated['component_assignments'];
        $updated['trace']['component_summary'] = $this->buildComponentSummary($updated['component_assignments']);
        $updated['trace']['material_substitutions'] = array_values(array_merge(
            is_array(data_get($payload, 'trace.material_substitutions')) ? data_get($payload, 'trace.material_substitutions') : [],
            [[
                'selected_bins' => $selectedIndexes,
                'replacement_material_id' => (int) $candidateMaterial->id,
                'replacement_material_name' => (string) $candidateMaterial->nome,
                'replacement_bins_count' => count($replacementBins),
                'replaced_bins_count' => count($selectedBins),
                'applied_at' => now()->toIso8601String(),
            ]]
        ));

        $updated['totali'] = $this->recalculateTotals($updated['bins']);
        $updated['fitok_preview'] = $this->buildFitokPreview($updated['bins']);

        return $updated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $bins
     * @return array<int, array<string, mixed>>
     */
    public function compatibleCandidatesForSelection(array $payload, array $selectedBinIndexes, iterable $materials): array
    {
        $compatible = [];

        foreach ($materials as $material) {
            if (! $material instanceof Prodotto) {
                continue;
            }

            try {
                $this->substitute($payload, $selectedBinIndexes, $material);
                $compatible[] = $material;
            } catch (\Throwable) {
                continue;
            }
        }

        return $compatible;
    }

    /**
     * @param  array<int, array<string, mixed>>  $bins
     * @return array<string, mixed>
     */
    private function recalculateTotals(array $bins): array
    {
        $volumeLordo = 0.0;
        $volumeNetto = 0.0;
        $costoTotale = 0.0;
        $prezzoTotale = 0.0;

        foreach ($bins as $bin) {
            $sourceMaterial = $this->materialSnapshotFromBin($bin);
            $boardVolumeLordo = (float) ($bin['volume_lordo_mc'] ?? $this->calculateBoardGrossVolume($bin, $sourceMaterial));
            $boardVolumeNetto = (float) ($bin['volume_netto_mc'] ?? $this->calculateBoardNetVolume($bin, $sourceMaterial));

            $volumeLordo += $boardVolumeLordo;
            $volumeNetto += $boardVolumeNetto;

            [$costo, $prezzo] = $this->calculateBinValuation($bin, $sourceMaterial, $boardVolumeLordo);
            $costoTotale += $costo;
            $prezzoTotale += $prezzo;
        }

        $volumeLordo = round(max(0.0, $volumeLordo), 6);
        $volumeNetto = round(max(0.0, min($volumeLordo, $volumeNetto)), 6);

        return [
            'volume_totale_mc' => $volumeLordo,
            'volume_lordo_mc' => $volumeLordo,
            'volume_netto_mc' => $volumeNetto,
            'volume_scarto_mc' => round(max(0.0, $volumeLordo - $volumeNetto), 6),
            'costo_totale' => round(max(0.0, $costoTotale), 2),
            'prezzo_totale' => round(max(0.0, $prezzoTotale), 2),
            'pricing_volume_basis' => 'lordo',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $bins
     * @return array{fitok_percentuale: ?float, fitok_volume_mc: float, non_fitok_volume_mc: float, status: string, label: string}
     */
    private function buildFitokPreview(array $bins): array
    {
        $fitokVolume = 0.0;
        $nonFitokVolume = 0.0;

        foreach ($bins as $bin) {
            $snapshot = $this->materialSnapshotFromBin($bin);
            $volumeLordo = (float) ($bin['volume_lordo_mc'] ?? $this->calculateBoardGrossVolume($bin, $snapshot));
            if ((bool) ($snapshot['soggetto_fitok'] ?? false)) {
                $fitokVolume += $volumeLordo;
            } else {
                $nonFitokVolume += $volumeLordo;
            }
        }

        $total = $fitokVolume + $nonFitokVolume;
        $fitokPercentuale = $total > 0 ? round(($fitokVolume / $total) * 100, 2) : null;
        $status = LottoProduzione::resolveFitokCertificationStatusFromPercentuale($fitokPercentuale);

        return [
            'fitok_percentuale' => $fitokPercentuale,
            'fitok_volume_mc' => round($fitokVolume, 6),
            'non_fitok_volume_mc' => round($nonFitokVolume, 6),
            'status' => $status,
            'label' => LottoProduzione::resolveFitokCertificationLabelFromPercentuale($fitokPercentuale),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $bins
     * @return array<int, array<string, mixed>>
     */
    private function decorateBinsForMaterial(array $bins, Prodotto $material, string $sourceType): array
    {
        $snapshot = $this->materialSnapshot($material);

        foreach ($bins as $index => $bin) {
            $gross = $this->calculateBoardGrossVolume($bin, $snapshot);
            $net = $this->calculateBoardNetVolume($bin, $snapshot);

            $bins[$index]['source_material_id'] = $material->id;
            $bins[$index]['source_material'] = $snapshot;
            $bins[$index]['source_type'] = $sourceType;
            $bins[$index]['volume_lordo_mc'] = $gross;
            $bins[$index]['volume_netto_mc'] = $net;
            $bins[$index]['volume_scarto_mc'] = round(max(0.0, $gross - $net), 6);
            $bins[$index]['substitution_meta'] = [
                'source_type' => $sourceType,
                'applied_at' => now()->toIso8601String(),
            ];
        }

        return $bins;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function packSelectedItemsForMaterial(array $items, Prodotto $material, float $kerf): array
    {
        $boardLength = max(0.0, (float) ($material->lunghezza_mm ?? 0));
        $boardWidth = max(0.0, (float) ($material->larghezza_mm ?? 0));

        if ($boardLength <= 0 || $boardWidth <= 0) {
            throw new \InvalidArgumentException('Il materiale sostitutivo deve avere dimensioni valide.');
        }

        usort($items, function (array $left, array $right): int {
            $widthCompare = ((float) ($right['width'] ?? 0)) <=> ((float) ($left['width'] ?? 0));
            if ($widthCompare !== 0) {
                return $widthCompare;
            }

            return ((float) ($right['length'] ?? 0)) <=> ((float) ($left['length'] ?? 0));
        });

        $boards = [];

        foreach ($items as $item) {
            $length = max(0.0, (float) ($item['length'] ?? 0));
            $width = max(0.0, (float) ($item['width'] ?? 0));

            if ($length <= 0 || $width <= 0) {
                throw new \InvalidArgumentException('Ogni taglio selezionato deve avere lunghezza e larghezza valide.');
            }

            if ($length > $boardLength) {
                throw new \InvalidArgumentException(
                    sprintf("Il pezzo '%s' non entra nella lunghezza del materiale selezionato.", (string) ($item['description'] ?? 'Pezzo'))
                );
            }

            if ($width > $boardWidth) {
                throw new \InvalidArgumentException(
                    sprintf("Il pezzo '%s' non entra nella larghezza del materiale selezionato.", (string) ($item['description'] ?? 'Pezzo'))
                );
            }

            $bestLane = null;
            $bestLaneResidual = null;

            foreach ($boards as $boardIndex => $board) {
                foreach ($board['lanes'] as $laneIndex => $lane) {
                    if ((float) $lane['width'] < $width) {
                        continue;
                    }

                    $required = $this->requiredSpace($length, (int) $lane['items_count'], $kerf);
                    if ((float) $lane['remaining_length'] < $required) {
                        continue;
                    }

                    $residual = (float) $lane['remaining_length'] - $required;
                    if ($bestLane === null || $residual < $bestLaneResidual) {
                        $bestLane = [$boardIndex, $laneIndex];
                        $bestLaneResidual = $residual;
                    }
                }
            }

            if ($bestLane !== null) {
                [$boardIndex, $laneIndex] = $bestLane;
                $required = $this->requiredSpace($length, (int) $boards[$boardIndex]['lanes'][$laneIndex]['items_count'], $kerf);
                $boards[$boardIndex]['lanes'][$laneIndex]['items'][] = [
                    'id' => $item['id'] ?? null,
                    'description' => (string) ($item['description'] ?? 'Pezzo'),
                    'length' => $length,
                    'width' => $width,
                ];
                $boards[$boardIndex]['lanes'][$laneIndex]['items_count']++;
                $boards[$boardIndex]['lanes'][$laneIndex]['used_length'] += $required;
                $boards[$boardIndex]['lanes'][$laneIndex]['remaining_length'] -= $required;

                continue;
            }

            $bestBoardIndex = null;
            $bestWidthResidual = null;

            foreach ($boards as $boardIndex => $board) {
                if ((float) $board['remaining_width'] < $width) {
                    continue;
                }

                $residual = (float) $board['remaining_width'] - $width;
                if ($bestBoardIndex === null || $residual < $bestWidthResidual) {
                    $bestBoardIndex = $boardIndex;
                    $bestWidthResidual = $residual;
                }
            }

            if ($bestBoardIndex === null) {
                $boards[] = [
                    'remaining_width' => $boardWidth,
                    'lanes' => [],
                ];
                $bestBoardIndex = array_key_last($boards);
            }

            $boards[$bestBoardIndex]['lanes'][] = [
                'width' => $width,
                'used_length' => $length,
                'remaining_length' => $boardLength - $length,
                'items_count' => 1,
                'items' => [[
                    'id' => $item['id'] ?? null,
                    'description' => (string) ($item['description'] ?? 'Pezzo'),
                    'length' => $length,
                    'width' => $width,
                ]],
            ];
            $boards[$bestBoardIndex]['remaining_width'] -= $width;
        }

        $bins = [];
        foreach ($boards as $board) {
            $itemsForBin = [];
            $usedArea = 0.0;

            foreach ($board['lanes'] as $lane) {
                $usedArea += (float) $lane['used_length'] * (float) $lane['width'];
                foreach ($lane['items'] as $item) {
                    $itemsForBin[] = $item;
                }
            }

            $equivalentUsedLength = $boardWidth > 0 ? $usedArea / $boardWidth : 0.0;
            $waste = max(0.0, $boardLength - $equivalentUsedLength);

            $bins[] = [
                'items' => $itemsForBin,
                'remaining_length' => $waste,
                'used_length' => round($equivalentUsedLength, 2),
                'capacity' => $boardLength,
                'waste' => round($waste, 2),
                'waste_percent' => $boardLength > 0 ? round(($waste / $boardLength) * 100, 2) : 0.0,
                'packing_lanes' => count($board['lanes']),
            ];
        }

        return [
            'bins' => $bins,
            'total_bins' => count($bins),
            'bin_length' => $boardLength,
            'total_waste' => round($this->sumWaste($bins), 2),
            'total_waste_percent' => round($this->calculateWastePercent($bins), 2),
            'kerf' => $kerf,
        ];
    }

    /**
     * @param  array<string, mixed>  $bin
     * @param  array<string, mixed>  $material
     * @return array{0: float, 1: float}
     */
    private function calculateBinValuation(array $bin, array $material, float $boardVolumeLordo): array
    {
        $uom = strtolower((string) ($material['unita_misura'] ?? UnitaMisura::MC->value));
        if ($uom === UnitaMisura::PZ->value) {
            return [
                round(max(0.0, (float) ($material['costo_unitario'] ?? 0)), 2),
                round(max(0.0, (float) ($material['prezzo_unitario'] ?? 0)), 2),
            ];
        }

        $costoMc = max(0.0, (float) ($material['costo_unitario'] ?? 0));
        $prezzoMc = max(0.0, (float) (($material['prezzo_mc'] ?? null) ?? ($material['prezzo_unitario'] ?? 0)));

        return [
            round($boardVolumeLordo * $costoMc, 2),
            round($boardVolumeLordo * $prezzoMc, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $bin
     * @param  array<string, mixed>  $material
     */
    private function calculateBoardGrossVolume(array $bin, array $material): float
    {
        $capacity = max(0.0, (float) ($bin['capacity'] ?? 0));
        $width = max(0.0, (float) ($material['larghezza_mm'] ?? 0));
        $thickness = max(0.0, (float) ($material['spessore_mm'] ?? 0));

        return round(($capacity * $width * $thickness) / 1000000000, 6);
    }

    /**
     * @param  array<string, mixed>  $bin
     * @param  array<string, mixed>  $material
     */
    private function calculateBoardNetVolume(array $bin, array $material): float
    {
        $thickness = max(0.0, (float) ($material['spessore_mm'] ?? 0));
        if ($thickness <= 0) {
            return 0.0;
        }

        $net = 0.0;
        foreach (Arr::wrap($bin['items'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $length = max(0.0, (float) ($item['length'] ?? 0));
            $width = max(0.0, (float) ($item['width'] ?? 0));
            $net += ($length * $width * $thickness) / 1000000000;
        }

        return round($net, 6);
    }

    /**
     * @param  array<int, array<string, mixed>>  $bins
     */
    private function sumWaste(array $bins): float
    {
        return array_reduce($bins, function (float $carry, array $bin): float {
            return $carry + max(0.0, (float) ($bin['waste'] ?? 0));
        }, 0.0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $bins
     */
    private function calculateWastePercent(array $bins): float
    {
        $capacity = array_reduce($bins, function (float $carry, array $bin): float {
            return $carry + max(0.0, (float) ($bin['capacity'] ?? 0));
        }, 0.0);

        if ($capacity <= 0) {
            return 0.0;
        }

        return ($this->sumWaste($bins) / $capacity) * 100;
    }

    private function requiredSpace(float $itemLength, int $itemsAlreadyInLane, float $kerf): float
    {
        return $itemLength + ($itemsAlreadyInLane > 0 ? $kerf : 0.0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $bins
     * @return array<int, array<string, mixed>>
     */
    private function buildComponentAssignments(array $bins): array
    {
        $assignments = [];

        foreach ($bins as $binIndex => $bin) {
            foreach (Arr::wrap($bin['items'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $componentKey = (string) ($item['id'] ?? '__null__');
                if (! isset($assignments[$componentKey])) {
                    $assignments[$componentKey] = [
                        'component_id' => $item['id'] ?? null,
                        'description' => (string) ($item['description'] ?? 'Componente'),
                        'produced_strips' => 0,
                        'produced_length_mm' => 0.0,
                        'produced_required_space_mm' => 0.0,
                        'allocated_waste_mm' => 0.0,
                        'assigned_bins' => [],
                    ];
                }

                $assignments[$componentKey]['produced_strips']++;
                $assignments[$componentKey]['produced_length_mm'] += (float) ($item['length'] ?? 0);
                $assignments[$componentKey]['produced_required_space_mm'] += (float) ($item['length'] ?? 0);

                if (! isset($assignments[$componentKey]['assigned_bins'][$binIndex])) {
                    $assignments[$componentKey]['assigned_bins'][$binIndex] = [
                        'bin_index' => $binIndex,
                        'board_number' => $binIndex + 1,
                        'strips' => 0,
                        'length_mm' => 0.0,
                        'required_space_mm' => 0.0,
                        'allocated_waste_mm' => 0.0,
                    ];
                }

                $assignments[$componentKey]['assigned_bins'][$binIndex]['strips']++;
                $assignments[$componentKey]['assigned_bins'][$binIndex]['length_mm'] += (float) ($item['length'] ?? 0);
                $assignments[$componentKey]['assigned_bins'][$binIndex]['required_space_mm'] += (float) ($item['length'] ?? 0);
            }
        }

        foreach ($assignments as &$assignment) {
            $assignment['produced_length_mm'] = round((float) $assignment['produced_length_mm'], 2);
            $assignment['produced_required_space_mm'] = round((float) $assignment['produced_required_space_mm'], 2);
            $assignment['allocated_waste_mm'] = round((float) $assignment['allocated_waste_mm'], 2);
            $assignment['assigned_bins'] = array_values($assignment['assigned_bins']);
            $assignment['assigned_boards_count'] = count($assignment['assigned_bins']);
        }
        unset($assignment);

        return array_values($assignments);
    }

    /**
     * @param  array<int, array<string, mixed>>  $componentAssignments
     * @return array<int, array<string, mixed>>
     */
    private function buildComponentSummary(array $componentAssignments): array
    {
        return array_map(function (array $assignment): array {
            return [
                'id' => $assignment['component_id'] ?? null,
                'description' => $assignment['description'] ?? 'Componente',
                'requested_strips' => (int) ($assignment['produced_strips'] ?? 0),
                'produced_strips' => (int) ($assignment['produced_strips'] ?? 0),
                'allocated_waste_mm' => (float) ($assignment['allocated_waste_mm'] ?? 0),
                'assigned_bins' => $assignment['assigned_bins'] ?? [],
            ];
        }, $componentAssignments);
    }

    /**
     * @param  array<string, mixed>  $bin
     * @return array<string, mixed>
     */
    private function materialSnapshotFromBin(array $bin): array
    {
        $snapshot = $bin['source_material'] ?? null;
        return is_array($snapshot) ? $snapshot : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function materialSnapshot(Prodotto $material): array
    {
        return [
            'id' => (int) $material->id,
            'nome' => (string) $material->nome,
            'codice' => (string) ($material->codice ?? ''),
            'lunghezza_mm' => round((float) ($material->lunghezza_mm ?? 0), 2),
            'larghezza_mm' => round((float) ($material->larghezza_mm ?? 0), 2),
            'spessore_mm' => round((float) ($material->spessore_mm ?? 0), 2),
            'unita_misura' => $material->unita_misura?->value ?? UnitaMisura::MC->value,
            'costo_unitario' => round((float) ($material->costo_unitario ?? 0), 4),
            'prezzo_unitario' => round((float) ($material->prezzo_unitario ?? 0), 4),
            'prezzo_mc' => round((float) ($material->prezzo_mc ?? 0), 2),
            'soggetto_fitok' => (bool) ($material->soggetto_fitok ?? false),
            'peso_specifico_kg_mc' => round((float) ($material->peso_specifico_kg_mc ?? 0), 3),
        ];
    }
}
