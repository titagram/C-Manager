<?php

namespace App\Services\Production;

use InvalidArgumentException;

class LegaccioExcelRequirementsBuilder
{
    /**
     * Build Excel-legacy rows/requirements for supported legaccio routines.
     *
     * Inputs are cm. Normalized `pieces` output is in mm.
     *
     * @return array{
     *   routine:string,
     *   family:string,
     *   rows: array<int, array{
     *     row:int,
     *     A_length_cm: float,
     *     B_section_mm: float,
     *     C_width_cm: float,
     *     D_qty_per_unit:int,
     *     D_qty_total:int
     *   }>,
     *   pieces: array<int, array{id:int, description:string, length:float, width:float, quantity:int}>,
     *   notes: array<int, string>
     * }
     */
    public function buildForRoutine(string $routine, int $numeroPezzi = 1): array
    {
        $routine = strtolower(trim($routine));
        $numeroPezzi = max(1, $numeroPezzi);

        return match ($routine) {
            'legacci224x60' => $this->buildLegacci224x60Rows($numeroPezzi),
            default => throw new InvalidArgumentException("Routine legaccio non supportata dal builder Excel: {$routine}"),
        };
    }

    /**
     * `legacci224x60` (legacy Excel docs):
     * - A8..A10 = [225,90,57]
     * - B8..B10 = [7.5,7.5,9.5]
     * - C8..C10 = [55,60,40]
     * - D8..D10 = [4,4,4]
     */
    private function buildLegacci224x60Rows(int $numeroPezzi): array
    {
        $a = [8 => 225, 9 => 90, 10 => 57];
        $b = [8 => 7.5, 9 => 7.5, 10 => 9.5];
        $c = [8 => 55, 9 => 60, 10 => 40];
        $d = [8 => 4, 9 => 4, 10 => 4];

        $rows = [];
        $pieces = [];

        foreach ([8, 9, 10] as $rowIndex) {
            $qtyPerUnit = max(0, (int) $d[$rowIndex]);
            $qtyTotal = $qtyPerUnit * $numeroPezzi;

            $rows[] = [
                'row' => $rowIndex,
                'A_length_cm' => round((float) $a[$rowIndex], 4),
                'B_section_mm' => round((float) $b[$rowIndex], 4),
                'C_width_cm' => round((float) $c[$rowIndex], 4),
                'D_qty_per_unit' => $qtyPerUnit,
                'D_qty_total' => $qtyTotal,
            ];

            if ($qtyTotal <= 0) {
                continue;
            }

            $pieces[] = [
                'id' => (int) ("3{$rowIndex}"),
                'description' => "Legacci 224x60 riga {$rowIndex}",
                'length' => round(((float) $a[$rowIndex]) * 10, 4), // cm -> mm
                'width' => round(((float) $c[$rowIndex]) * 10, 4),  // cm -> mm
                'quantity' => $qtyTotal,
            ];
        }

        return [
            'routine' => 'legacci224x60',
            'family' => 'legaccio_224x60',
            'rows' => $rows,
            'pieces' => $pieces,
            'notes' => [
                'Builder Excel v2 preparatorio: routine legacci224x60 tradotta in requirements normalizzati.',
                'Parametri geometrici legacy fissi (A/B/C/D) mantenuti in trace per confronto con fogli storici.',
            ],
        ];
    }
}

