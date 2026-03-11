<?php

namespace App\Services;

class BinPackingService
{
    /**
     * Pack pieces into bins using Best Fit Decreasing algorithm.
     *
     * @param array $pieces Array of pieces ['id' => mixed, 'length' => float, 'quantity' => int]
     * @param float $binLength Length of the stock board
     * @param float $kerf Width of the saw blade
     * @return array Result structure with bins, waste, etc.
     */
    public function pack(array $pieces, float $binLength, float $kerf = 4.0): array
    {
        // 1. Flatten the list of items to pack (handle quantities)
        $itemsToPack = [];
        foreach ($pieces as $piece) {
            for ($i = 0; $i < $piece['quantity']; $i++) {
                $item = [
                    'id' => $piece['id'], // Identifier to track back to component
                    'description' => $piece['description'] ?? 'Piece',
                    'length' => (float) $piece['length'],
                ];

                if (array_key_exists('width', $piece)) {
                    $item['width'] = (float) $piece['width'];
                }

                $itemsToPack[] = $item;
            }
        }

        // 2. Sort items by length descending (Decreasing)
        usort($itemsToPack, function ($a, $b) {
            return $b['length'] <=> $a['length'];
        });

        $bins = []; // Array of bins. Each bin: ['remaining_length' => float, 'items' => [], 'cuts' => []]

        // 3. Process each item
        foreach ($itemsToPack as $item) {
            if ($item['length'] > $binLength) {
                throw new \InvalidArgumentException(sprintf(
                    "Il pezzo '%s' (%.0fmm) è più lungo della lunghezza dell'asse disponibile (%.0fmm).",
                    $item['description'],
                    $item['length'],
                    $binLength
                ));
            }

            $bestBinIndex = -1;
            $minRemaining = $binLength + 1; // Initialize with value larger than any possible remaining

            // Find best fit bin
            foreach ($bins as $index => $bin) {
                // Kerf formula: n*L + (n-1)*kerf.
                // For each additional piece in the same bin, required space is L + kerf.
                $requiredSpace = $this->requiredSpace(
                    $item['length'],
                    count($bin['items']),
                    $kerf
                );

                if ($bin['remaining_length'] >= $requiredSpace) {
                    $potentialRemaining = $bin['remaining_length'] - $requiredSpace;
                    if ($potentialRemaining < $minRemaining) {
                        $minRemaining = $potentialRemaining;
                        $bestBinIndex = $index;
                    }
                }
            }

            if ($bestBinIndex !== -1) {
                // Add to existing bin
                $requiredSpace = $this->requiredSpace(
                    $item['length'],
                    count($bins[$bestBinIndex]['items']),
                    $kerf
                );

                $bins[$bestBinIndex]['items'][] = $item;
                $bins[$bestBinIndex]['remaining_length'] -= $requiredSpace;
                $bins[$bestBinIndex]['used_length'] += $requiredSpace;
            } else {
                // Open new bin
                $requiredSpace = $this->requiredSpace($item['length'], 0, $kerf);

                $bins[] = [
                    'items' => [$item],
                    'remaining_length' => $binLength - $requiredSpace,
                    'used_length' => $requiredSpace,
                    'capacity' => $binLength
                ];
            }
        }

        // 4. Calculate stats and clean up
        $totalWaste = 0;
        $totalUsedLength = 0; // Sum of bin lengths used

        foreach ($bins as &$bin) {
            // The "waste" is the remaining length on the board
            // Note: The calculation above subtracted kerf for 2nd+ items.
            // So remaining_length is truly what's left physically.

            $bin['waste'] = $bin['remaining_length'];
            $bin['waste_percent'] = ($bin['capacity'] > 0) ? round(($bin['waste'] / $bin['capacity']) * 100, 2) : 0;

            $totalWaste += $bin['waste'];
            $totalUsedLength += $bin['capacity'];
        }

        return [
            'bins' => $bins,
            'total_bins' => count($bins),
            'bin_length' => $binLength,
            'total_waste' => $totalWaste,
            'total_waste_percent' => ($totalUsedLength > 0) ? round(($totalWaste / $totalUsedLength) * 100, 2) : 0,
            'kerf' => $kerf,
            'component_assignments' => $this->buildComponentAssignments($bins, $kerf),
        ];
    }

    private function requiredSpace(float $itemLength, int $itemsAlreadyInBin, float $kerf): float
    {
        return $itemLength + ($itemsAlreadyInBin > 0 ? $kerf : 0.0);
    }

    /**
     * @param array<int, array<string, mixed>> $bins
     * @return array<int, array<string, mixed>>
     */
    public function buildComponentAssignmentsForBins(array $bins, float $kerf): array
    {
        return $this->buildComponentAssignments($bins, $kerf);
    }

    /**
     * @param array<int, array<string, mixed>> $bins
     * @return array<int, array<string, mixed>>
     */
    private function buildComponentAssignments(array $bins, float $kerf): array
    {
        $assignments = [];

        foreach ($bins as $binIndex => $bin) {
            if (!is_array($bin)) {
                continue;
            }

            $items = is_array($bin['items'] ?? null) ? array_values($bin['items']) : [];
            $binUsedLength = max(0.0, (float) ($bin['used_length'] ?? 0));
            $binWaste = max(0.0, (float) ($bin['waste'] ?? 0));

            foreach ($items as $itemPosition => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $componentId = $this->normalizeComponentId($item['id'] ?? null);
                $componentKey = $componentId === null ? '__null__' : (string) $componentId;
                $description = (string) ($item['description'] ?? 'Piece');
                $length = max(0.0, (float) ($item['length'] ?? 0));
                $requiredSpace = $this->requiredSpace($length, $itemPosition, $kerf);
                $allocatedWaste = $binUsedLength > 0
                    ? ($requiredSpace / $binUsedLength) * $binWaste
                    : 0.0;

                if (!isset($assignments[$componentKey])) {
                    $assignments[$componentKey] = [
                        'component_id' => $componentId,
                        'description' => $description,
                        'produced_strips' => 0,
                        'produced_length_mm' => 0.0,
                        'produced_required_space_mm' => 0.0,
                        'allocated_waste_mm' => 0.0,
                        'assigned_bins' => [],
                    ];
                }

                $assignments[$componentKey]['produced_strips']++;
                $assignments[$componentKey]['produced_length_mm'] += $length;
                $assignments[$componentKey]['produced_required_space_mm'] += $requiredSpace;
                $assignments[$componentKey]['allocated_waste_mm'] += $allocatedWaste;

                if (!isset($assignments[$componentKey]['assigned_bins'][$binIndex])) {
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
                $assignments[$componentKey]['assigned_bins'][$binIndex]['length_mm'] += $length;
                $assignments[$componentKey]['assigned_bins'][$binIndex]['required_space_mm'] += $requiredSpace;
                $assignments[$componentKey]['assigned_bins'][$binIndex]['allocated_waste_mm'] += $allocatedWaste;
            }
        }

        foreach ($assignments as &$assignment) {
            $assignment['produced_length_mm'] = round((float) $assignment['produced_length_mm'], 2);
            $assignment['produced_required_space_mm'] = round((float) $assignment['produced_required_space_mm'], 2);
            $assignment['allocated_waste_mm'] = round((float) $assignment['allocated_waste_mm'], 2);

            $assignment['assigned_bins'] = array_values($assignment['assigned_bins']);
            usort($assignment['assigned_bins'], function (array $left, array $right): int {
                return (int) ($left['bin_index'] ?? 0) <=> (int) ($right['bin_index'] ?? 0);
            });

            foreach ($assignment['assigned_bins'] as $index => $binEntry) {
                $assignment['assigned_bins'][$index]['length_mm'] = round((float) ($binEntry['length_mm'] ?? 0), 2);
                $assignment['assigned_bins'][$index]['required_space_mm'] = round((float) ($binEntry['required_space_mm'] ?? 0), 2);
                $assignment['assigned_bins'][$index]['allocated_waste_mm'] = round((float) ($binEntry['allocated_waste_mm'] ?? 0), 2);
            }

            $assignment['assigned_boards_count'] = count($assignment['assigned_bins']);
        }
        unset($assignment);

        return array_values($assignments);
    }

    private function normalizeComponentId(mixed $componentId): int|string|null
    {
        if ($componentId === null || $componentId === '') {
            return null;
        }

        if (is_int($componentId)) {
            return $componentId;
        }

        if (is_float($componentId) && floor($componentId) === $componentId) {
            return (int) $componentId;
        }

        if (is_string($componentId) && preg_match('/^-?\d+$/', $componentId) === 1) {
            return (int) $componentId;
        }

        return (string) $componentId;
    }
}
