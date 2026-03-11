<?php

namespace App\Services\Production;

class GabbiaSp20LegacyQuantitiesCalculator
{
    public function __construct(
        private readonly GabbiaLegacyHeightQuantityTable $heightTable
    ) {}

    /**
     * Calculate legacy Excel quantities for the core gabbiasp20 / gabbiasp20fondo4 routine.
     *
     * Returns the "D" values documented in `DOCUMENTAZIONE_LOGICA_EXCEL.md`.
     * Units are cm, aligned with the Excel business documentation.
     *
     * @return array{D8:int,D9:int,D10:int,D11:int,D12:int,D13:int}
     */
    public function calculate(float $Lcm, float $Wcm, float $Hcm): array
    {
        $heightQty = $this->heightTable->qtyFromHeightCm($Hcm);
        $isLong = $Lcm >= 200;

        return [
            'D8' => $this->legacyD8WidthQuantity($Wcm),
            'D9' => $heightQty,
            'D10' => $heightQty,
            'D11' => $isLong ? 4 : 3,
            'D12' => $isLong ? 16 : 14,
            'D13' => $isLong ? 4 : 3,
        ];
    }

    /**
     * Excel docs describe: "round_up(W/10) con offset legacy".
     *
     * Current v2 assumption: ceil((W/10)+0.5). Kept isolated so we can swap it
     * if deeper workbook reverse-engineering finds a different exact behavior.
     */
    public function legacyD8WidthQuantity(float $Wcm): int
    {
        return max(0, (int) ceil(($Wcm / 10) + 0.5));
    }
}

