<?php

namespace App\Services\Production;

use InvalidArgumentException;

class BancaleExcelRequirementsBuilder
{
    public function __construct(
        private readonly BancaleLegacyQuantitiesCalculator $quantitiesCalculator
    ) {}

    /**
     * Build Excel-legacy rows/requirements for supported bancale routines.
     *
     * Units in input are cm. Normalized `pieces` output is in mm.
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
     *   legacy_quantities: array<string,int>,
     *   pieces: array<int, array{id:int, description:string, length:float, width:float, quantity:int}>,
     *   notes: array<int, string>
     * }
     */
    public function buildForRoutine(
        string $routine,
        float $Lcm,
        float $Wcm,
        ?float $Hcm = null,
        int $numeroPezzi = 1
    ): array {
        $routine = strtolower(trim($routine));
        $numeroPezzi = max(1, $numeroPezzi);

        return match ($routine) {
            'bancale' => $this->buildBancaleRows(
                Lcm: $Lcm,
                Wcm: $Wcm,
                numeroPezzi: $numeroPezzi
            ),
            'perimetrale' => $this->buildPerimetraleRows(
                Lcm: $Lcm,
                Wcm: $Wcm,
                Hcm: $Hcm,
                numeroPezzi: $numeroPezzi
            ),
            default => throw new InvalidArgumentException("Routine bancale non supportata dal builder Excel: {$routine}"),
        };
    }

    /**
     * `bancale` (legacy Excel docs):
     * - A8=L, A9=W
     * - B8=25, B9=40
     * - C8=10, C9=10
     * - D8=round(W/10), D9=4 if L>199 else 3
     */
    private function buildBancaleRows(float $Lcm, float $Wcm, int $numeroPezzi): array
    {
        $q = $this->quantitiesCalculator->calculate($Lcm, $Wcm);

        $a = [8 => $Lcm, 9 => $Wcm];
        $b = [8 => 25, 9 => 40];
        $c = [8 => 10, 9 => 10];
        $d = [8 => $q['D8'], 9 => $q['D9']];

        $rows = [];
        $pieces = [];

        foreach ([8, 9] as $rowIndex) {
            $qtyPerUnit = max(0, (int) $d[$rowIndex]);
            $qtyTotal = $qtyPerUnit * $numeroPezzi;

            $rows[] = [
                'row' => $rowIndex,
                'A_length_cm' => round((float) $a[$rowIndex], 4),
                'B_section_mm' => (float) $b[$rowIndex],
                'C_width_cm' => (float) $c[$rowIndex],
                'D_qty_per_unit' => $qtyPerUnit,
                'D_qty_total' => $qtyTotal,
            ];

            if ($qtyTotal <= 0) {
                continue;
            }

            $pieces[] = [
                'id' => (int) ("2{$rowIndex}"),
                'description' => "Bancale riga {$rowIndex}",
                'length' => round(((float) $a[$rowIndex]) * 10, 4), // cm -> mm
                'width' => round(((float) $c[$rowIndex]) * 10, 4),  // cm -> mm
                'quantity' => $qtyTotal,
            ];
        }

        return [
            'routine' => 'bancale',
            'family' => 'bancale_standard',
            'rows' => $rows,
            'legacy_quantities' => $q,
            'pieces' => $pieces,
            'notes' => [
                'Builder Excel v2 preparatorio: righe legacy generate, taglio effettivo controllato da bancale_excel_mode.',
                'Colonna B (section mm) resta in trace per validazione progressiva rispetto ai fogli Excel.',
            ],
        ];
    }

    /**
     * `perimetrale` (legacy Excel docs):
     * - A8=L+5, A9=W, A10=L+5, A11=W+5
     * - B8..B11=[25,25,20,20]
     * - C8=H, C9=H, C10=8, C11=8
     * - D8=2, D9=2
     * - D10 class from W, D11 class from L
     */
    private function buildPerimetraleRows(float $Lcm, float $Wcm, ?float $Hcm, int $numeroPezzi): array
    {
        if ($Hcm === null || $Hcm <= 0) {
            throw new InvalidArgumentException('La routine perimetrale richiede altezza (H) valida.');
        }

        $q = $this->quantitiesCalculator->calculatePerimetrale($Lcm, $Wcm);

        $a = [8 => $Lcm + 5, 9 => $Wcm, 10 => $Lcm + 5, 11 => $Wcm + 5];
        $b = [8 => 25, 9 => 25, 10 => 20, 11 => 20];
        $c = [8 => $Hcm, 9 => $Hcm, 10 => 8, 11 => 8];
        $d = [8 => $q['D8'], 9 => $q['D9'], 10 => $q['D10'], 11 => $q['D11']];

        $rows = [];
        $pieces = [];

        foreach ([8, 9, 10, 11] as $rowIndex) {
            $qtyPerUnit = max(0, (int) $d[$rowIndex]);
            $qtyTotal = $qtyPerUnit * $numeroPezzi;

            $rows[] = [
                'row' => $rowIndex,
                'A_length_cm' => round((float) $a[$rowIndex], 4),
                'B_section_mm' => (float) $b[$rowIndex],
                'C_width_cm' => round((float) $c[$rowIndex], 4),
                'D_qty_per_unit' => $qtyPerUnit,
                'D_qty_total' => $qtyTotal,
            ];

            if ($qtyTotal <= 0) {
                continue;
            }

            $pieces[] = [
                'id' => (int) ("4{$rowIndex}"),
                'description' => "Perimetrale riga {$rowIndex}",
                'length' => round(((float) $a[$rowIndex]) * 10, 4), // cm -> mm
                'width' => round(((float) $c[$rowIndex]) * 10, 4),  // cm -> mm
                'quantity' => $qtyTotal,
            ];
        }

        return [
            'routine' => 'perimetrale',
            'family' => 'bancale_perimetrale',
            'rows' => $rows,
            'legacy_quantities' => $q,
            'pieces' => $pieces,
            'notes' => [
                'Builder Excel v2 preparatorio: perimetrale tradotto in requirements normalizzati.',
                'Per W >= 180 la classe D10 mantiene il massimo legacy noto (8) finche non emerge una regola diversa dai workbook.',
            ],
        ];
    }
}
