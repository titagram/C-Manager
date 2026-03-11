<?php

namespace App\Services\Production;

use InvalidArgumentException;

class CassaExcelRequirementsBuilder
{
    public function __construct(
        private readonly CassaLegacyQuantitiesCalculator $quantitiesCalculator
    ) {}

    /**
     * @return array{
     *   routine:string,
     *   family:string,
     *   rows: array<int, array{
     *     row:int,
     *     profile_key:string,
     *     A_length_cm: float,
     *     B_section_mm: float,
     *     C_width_cm: float,
     *     D_qty_per_unit:int,
     *     D_qty_total:int
     *   }>,
     *   pieces: array<int, array{
     *     id:int,
     *     description:string,
     *     length:float,
     *     width:float,
     *     quantity:int,
     *     source_profile:string,
     *     section_mm:float,
     *     legacy_row:int
     *   }>,
     *   notes: array<int, string>
     * }
     */
    public function buildForRoutine(
        string $routine,
        float $Lcm,
        float $Wcm,
        float $Hcm,
        int $numeroPezzi = 1
    ): array {
        $routine = strtolower(trim($routine));
        $numeroPezzi = max(1, $numeroPezzi);

        return match ($routine) {
            'cassasp25' => $this->buildCassasp25Rows($Lcm, $Wcm, $Hcm, $numeroPezzi),
            'cassasp25fondo40' => $this->buildCassasp25Fondo40Rows($Lcm, $Wcm, $Hcm, $numeroPezzi),
            default => throw new InvalidArgumentException("Routine cassa non supportata dal builder Excel: {$routine}"),
        };
    }

    /**
     * Real workbook parity (golden cases) shows B11=40 and no row 14 despite some macro variants.
     */
    private function buildCassasp25Rows(float $Lcm, float $Wcm, float $Hcm, int $numeroPezzi): array
    {
        $quantities = $this->quantitiesCalculator->calculate('cassasp25', $Lcm, $Wcm, $Hcm);

        $rows = [
            8 => [$Lcm + 5, 25.0, 10.0, $quantities['D8']],
            9 => [$Lcm + 10, 25.0, 10.0, $quantities['D9']],
            10 => [$Wcm, 25.0, 10.0, $quantities['D10']],
            11 => [$Wcm + 5, 40.0, 10.0, $quantities['D11']],
            12 => [$Hcm + 13, 25.0, 8.0, $quantities['D12']],
            13 => [$Wcm + 10, 25.0, 8.0, $quantities['D13']],
        ];

        return $this->finalizeRows(
            routine: 'cassasp25',
            family: 'cassa_sp25',
            rows: $rows,
            numeroPezzi: $numeroPezzi,
            notes: [
                'Builder allineato ai workbook reali estratti: SP25 usa B11=40 e non materializza la riga 14.',
                'Il profilo fondo copre le righe con sezione 40 mm; il profilo base copre le righe 25 mm.',
            ]
        );
    }

    /**
     * Macro parity: SP25 Fondo 40 alza anche la riga 8 a 40 mm e aggiunge la riga 14.
     */
    private function buildCassasp25Fondo40Rows(float $Lcm, float $Wcm, float $Hcm, int $numeroPezzi): array
    {
        $quantities = $this->quantitiesCalculator->calculate('cassasp25fondo40', $Lcm, $Wcm, $Hcm);

        $rows = [
            8 => [$Lcm + 5, 40.0, 10.0, $quantities['D8']],
            9 => [$Lcm + 10, 25.0, 10.0, $quantities['D9']],
            10 => [$Wcm, 25.0, 10.0, $quantities['D10']],
            11 => [$Wcm + 5, 40.0, 10.0, $quantities['D11']],
            12 => [$Hcm + 13, 25.0, 8.0, $quantities['D12']],
            13 => [$Wcm + 10, 25.0, 8.0, $quantities['D13']],
            14 => [$Lcm + 5, 25.0, 10.0, $quantities['D14']],
        ];

        return $this->finalizeRows(
            routine: 'cassasp25fondo40',
            family: 'cassa_sp25_fondo40',
            rows: $rows,
            numeroPezzi: $numeroPezzi,
            notes: [
                'Builder allineato alla macro legacy: SP25 Fondo 40 usa B8=40 e la riga 14 aggiuntiva.',
                'Quando serve confronto con workbook reali, il trace segnala l eventuale mismatch documentazione/workbook.',
            ]
        );
    }

    /**
     * @param  array<int, array{0:float,1:float,2:float,3:int}>  $rows
     * @param  array<int, string>  $notes
     * @return array{
     *   routine:string,
     *   family:string,
     *   rows: array<int, array{
     *     row:int,
     *     profile_key:string,
     *     A_length_cm: float,
     *     B_section_mm: float,
     *     C_width_cm: float,
     *     D_qty_per_unit:int,
     *     D_qty_total:int
     *   }>,
     *   pieces: array<int, array{
     *     id:int,
     *     description:string,
     *     length:float,
     *     width:float,
     *     quantity:int,
     *     source_profile:string,
     *     section_mm:float,
     *     legacy_row:int
     *   }>,
     *   notes: array<int, string>
     * }
     */
    private function finalizeRows(
        string $routine,
        string $family,
        array $rows,
        int $numeroPezzi,
        array $notes
    ): array {
        $normalizedRows = [];
        $pieces = [];

        foreach ($rows as $rowIndex => [$lengthCm, $sectionMm, $widthCm, $qtyPerUnit]) {
            $qtyPerUnit = max(0, (int) $qtyPerUnit);
            $qtyTotal = $qtyPerUnit * $numeroPezzi;
            $profileKey = $sectionMm >= 40 ? 'fondo' : 'base';

            $normalizedRows[] = [
                'row' => $rowIndex,
                'profile_key' => $profileKey,
                'A_length_cm' => round($lengthCm, 4),
                'B_section_mm' => round($sectionMm, 4),
                'C_width_cm' => round($widthCm, 4),
                'D_qty_per_unit' => $qtyPerUnit,
                'D_qty_total' => $qtyTotal,
            ];

            if ($qtyTotal <= 0) {
                continue;
            }

            $pieces[] = [
                'id' => (int) ("5{$rowIndex}"),
                'description' => sprintf('Cassa %s riga %d', strtoupper($routine), $rowIndex),
                'length' => round($lengthCm * 10, 4),
                'width' => round($widthCm * 10, 4),
                'quantity' => $qtyTotal,
                'source_profile' => $profileKey,
                'section_mm' => round($sectionMm, 4),
                'legacy_row' => $rowIndex,
            ];
        }

        return [
            'routine' => $routine,
            'family' => $family,
            'rows' => $normalizedRows,
            'pieces' => $pieces,
            'notes' => $notes,
        ];
    }
}
