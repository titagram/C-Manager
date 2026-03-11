<?php

namespace App\Services\Production;

use InvalidArgumentException;

class GabbiaExcelRequirementsBuilder
{
    public function __construct(
        private readonly GabbiaSp20LegacyQuantitiesCalculator $sp20Quantities,
        private readonly GabbiaLegacyHeightQuantityTable $heightTable,
        private readonly GabbiaRoutineCatalog $routineCatalog
    ) {}

    /**
     * Build Excel-legacy rows/requirements for supported gabbia routines.
     *
     * Units in input are cm (as documented in Excel). Normalized `pieces` output is in mm.
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
     *   legacy_quantities: array<string, int>,
     *   pieces: array<int, array{id:int, description:string, length:float, width:float, quantity:int}>,
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

        if (!$this->routineCatalog->has($routine)) {
            throw new InvalidArgumentException("Routine gabbia non supportata dal builder Excel: {$routine}");
        }

        $family = $this->routineCatalog->familyFromRoutine($routine);

        return match ($family) {
            'gabbia_sp20' => $this->buildGabbiaSp20Rows(
                routine: $routine,
                Lcm: $Lcm,
                Wcm: $Wcm,
                Hcm: $Hcm,
                numeroPezzi: $numeroPezzi
            ),
            'gabbia_legaccio' => $this->buildGabbiaLegaccioRows(
                routine: $routine,
                Lcm: $Lcm,
                Wcm: $Wcm,
                Hcm: $Hcm,
                numeroPezzi: $numeroPezzi
            ),
            default => throw new InvalidArgumentException("Routine gabbia non supportata dal builder Excel: {$routine}"),
        };
    }

    /**
     * `gabbiasp20` / `gabbiasp20fondo4`
     * Excel docs:
     * - A8..A13 = [L+4, L+8, W, W+4, H+12, W+8]
     * - C8..C13 = [10,8,8,10,8,8]
     * - B standard = [25,20,20,40,25,25]
     * - B fondo4   = [40,20,20,40,25,25]
     */
    private function buildGabbiaSp20Rows(
        string $routine,
        float $Lcm,
        float $Wcm,
        float $Hcm,
        int $numeroPezzi
    ): array {
        $profile = $this->routineCatalog->profile($routine);
        $row8Section = (float) ($profile['row8_section_mm'] ?? 25.0);
        $q = $this->sp20Quantities->calculate($Lcm, $Wcm, $Hcm);

        $a = [
            8 => $Lcm + 4,
            9 => $Lcm + 8,
            10 => $Wcm,
            11 => $Wcm + 4,
            12 => $Hcm + 12,
            13 => $Wcm + 8,
        ];

        $b = [8 => $row8Section, 9 => 20, 10 => 20, 11 => 40, 12 => 25, 13 => 25];

        $c = [8 => 10, 9 => 8, 10 => 8, 11 => 10, 12 => 8, 13 => 8];

        $d = [
            8 => $q['D8'],
            9 => $q['D9'],
            10 => $q['D10'],
            11 => $q['D11'],
            12 => $q['D12'],
            13 => $q['D13'],
        ];

        $rows = [];
        $pieces = [];

        foreach ([8, 9, 10, 11, 12, 13] as $rowIndex) {
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
                // Stable synthetic id for row-based legacy requirements
                'id' => (int) ("1{$rowIndex}"),
                'description' => "Gabbia SP20 riga {$rowIndex}",
                'length' => round(((float) $a[$rowIndex]) * 10, 4), // cm -> mm
                'width' => round(((float) $c[$rowIndex]) * 10, 4),  // cm -> mm
                'quantity' => $qtyTotal,
            ];
        }

        $notes = [
            'Builder Excel v2 preparatorio: righe legacy generate, non ancora usate per il taglio effettivo (optimizer gabbia resta v1 rettangolare).',
            'Colonna B (section mm) e D8 (offset legacy su larghezza) sono mantenuti espliciti per validazione progressiva con Excel.',
        ];

        return [
            'routine' => $routine,
            'family' => 'gabbia_sp20',
            'rows' => $rows,
            'legacy_quantities' => $q,
            'pieces' => $pieces,
            'notes' => $notes,
        ];
    }

    /**
     * `gabbialegaccio*` routines (4/6 piantoni, con/senza fondo4)
     * reverse engineered da macro VBA legacy.
     */
    private function buildGabbiaLegaccioRows(
        string $routine,
        float $Lcm,
        float $Wcm,
        float $Hcm,
        int $numeroPezzi
    ): array {
        $profile = $this->routineCatalog->profile($routine);
        $isSixPiantoni = (int) ($profile['piantoni'] ?? 0) === 6;
        $row8Section = (float) ($profile['row8_section_mm'] ?? 25.0);
        $hasLegacyThreshold219 = (bool) ($profile['legacy_threshold_219'] ?? false);

        $a = [
            8 => $Lcm + 4,
            9 => $Lcm + 8,
            10 => $Wcm,
            11 => $this->a11ClassByHeight($Hcm),
            12 => $Hcm + 25,
            13 => $Hcm - 28,
            14 => $Lcm,
            15 => $Wcm + 20,
            16 => $Hcm + ($isSixPiantoni ? 4 : 5),
        ];

        $b = [
            8 => $row8Section,
            9 => 20,
            10 => 20,
            11 => 40,
            12 => 40,
            13 => 40,
            14 => $isSixPiantoni ? 40 : 25,
            15 => 25,
            16 => 25,
        ];

        $c = [
            8 => 10,
            9 => 8,
            10 => 8,
            11 => 10,
            12 => 8,
            13 => 8,
            14 => 8,
            15 => 8,
            16 => 8,
        ];

        $heightQty = $this->heightQtyByRoutine($Hcm, $hasLegacyThreshold219);
        $d = [
            8 => $this->sp20Quantities->legacyD8WidthQuantity($Wcm),
            9 => $heightQty,
            10 => $heightQty,
            11 => $isSixPiantoni ? 6 : 4,
            12 => $isSixPiantoni ? 6 : 4,
            13 => $isSixPiantoni ? 6 : 4,
            14 => 2,
            15 => $isSixPiantoni ? 3 : 2,
            16 => $isSixPiantoni
                ? ($Wcm >= 40 ? 6 : 4)
                : ($Wcm > 35 ? 6 : 4),
        ];

        $rows = [];
        $pieces = [];

        foreach (range(8, 16) as $rowIndex) {
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
                'id' => $this->routinePieceId($routine, $rowIndex),
                'description' => sprintf('Gabbia Legaccio riga %d (%s)', $rowIndex, $routine),
                'length' => round(((float) $a[$rowIndex]) * 10, 4), // cm -> mm
                'width' => round(((float) $c[$rowIndex]) * 10, 4), // cm -> mm
                'quantity' => $qtyTotal,
            ];
        }

        $notes = [
            'Builder Excel v2 gabbia legaccio attivo: righe legacy A/B/C/D convertite in pieces normalizzati.',
            'D8 usa offset legacy (ceil((W/10)+0.5)); D9/D10 usano classi altezza legacy.',
        ];

        if ($hasLegacyThreshold219) {
            $notes[] = 'Routine legacy con soglia alta incoerente: D9/D10 passano a 18 solo per H>=220.';
        }

        $legacyQuantities = [];
        foreach ($d as $row => $qty) {
            $legacyQuantities["D{$row}"] = (int) $qty;
        }

        return [
            'routine' => $routine,
            'family' => 'gabbia_legaccio',
            'rows' => $rows,
            'legacy_quantities' => $legacyQuantities,
            'pieces' => $pieces,
            'notes' => $notes,
        ];
    }

    private function a11ClassByHeight(float $Hcm): int
    {
        if ($Hcm < 100) {
            return 60;
        }

        if ($Hcm < 130) {
            return 70;
        }

        if ($Hcm < 150) {
            return 100;
        }

        return 130;
    }

    private function heightQtyByRoutine(float $Hcm, bool $legacyThreshold219): int
    {
        if (!$legacyThreshold219) {
            return $this->heightTable->qtyFromHeightCm($Hcm);
        }

        // Legacy macro quirk: in gabbialegaccio6piantonifondo4 la soglia finale
        // passa a 18 solo per H > 219, lasciando 210..219 nella classe 16.
        if ($Hcm >= 220) {
            return 18;
        }

        if ($Hcm >= 180) {
            return 16;
        }

        return $this->heightTable->qtyFromHeightCm($Hcm);
    }

    private function routinePieceId(string $routine, int $rowIndex): int
    {
        $prefix = match ($routine) {
            'gabbialegaccio4piantoni' => 2,
            'gabbialegaccio4piantonifondo4' => 3,
            'gabbialegaccio6piantoni' => 4,
            'gabbialegaccio6piantonifondo4' => 5,
            default => 1,
        };

        return (int) sprintf('%d%02d', $prefix, $rowIndex);
    }
}
