<?php

namespace App\Services\Production;

class BancaleLegacyQuantitiesCalculator
{
    /**
     * Calculate legacy Excel quantities for the core bancale routine.
     *
     * @return array{D8:int,D9:int}
     */
    public function calculate(float $Lcm, float $Wcm): array
    {
        return [
            'D8' => $this->legacyD8WidthQuantity($Wcm),
            'D9' => $Lcm > 199 ? 4 : 3,
        ];
    }

    /**
     * Excel docs describe D8 as round(W/10).
     * Kept isolated so we can swap behavior if workbook reverse-engineering
     * reveals ROUNDUP/other legacy nuances.
     */
    public function legacyD8WidthQuantity(float $Wcm): int
    {
        return max(0, (int) round($Wcm / 10, 0, PHP_ROUND_HALF_UP));
    }

    /**
     * Calculate legacy Excel quantities for perimetrale routine.
     *
     * @return array{D8:int,D9:int,D10:int,D11:int}
     */
    public function calculatePerimetrale(float $Lcm, float $Wcm): array
    {
        return [
            'D8' => 2,
            'D9' => 2,
            'D10' => $this->perimetraleD10WidthClass($Wcm),
            'D11' => $this->perimetraleD11LengthClass($Lcm),
        ];
    }

    /**
     * Per legacy docs:
     * - <40 => 3
     * - 40..69 => 4
     * - 70..89 => 5
     * - 90..119 => 6
     * - 120..159 => 7
     * - 160..179 => 8
     * Current assumption for >=180: keep top class (8).
     */
    public function perimetraleD10WidthClass(float $Wcm): int
    {
        if ($Wcm < 40) {
            return 3;
        }

        if ($Wcm <= 69) {
            return 4;
        }

        if ($Wcm <= 89) {
            return 5;
        }

        if ($Wcm <= 119) {
            return 6;
        }

        if ($Wcm <= 159) {
            return 7;
        }

        if ($Wcm <= 179) {
            return 8;
        }

        return 8;
    }

    /**
     * Per legacy docs:
     * - <180 => 4
     * - 180..249 => 6
     * - >=250 => 8
     */
    public function perimetraleD11LengthClass(float $Lcm): int
    {
        if ($Lcm < 180) {
            return 4;
        }

        if ($Lcm <= 249) {
            return 6;
        }

        return 8;
    }
}
