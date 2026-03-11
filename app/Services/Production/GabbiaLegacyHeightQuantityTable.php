<?php

namespace App\Services\Production;

class GabbiaLegacyHeightQuantityTable
{
    /**
     * Legacy Excel class table shared by gabbiasp20 and some gabbia legaccio routines.
     *
     * Input/output use cm semantics (same unit family as the Excel docs).
     */
    public function qtyFromHeightCm(float $heightCm): int
    {
        if ($heightCm < 40) {
            return 4;
        }

        if ($heightCm <= 64) {
            return 6;
        }

        if ($heightCm <= 99) {
            return 8;
        }

        if ($heightCm <= 119) {
            return 10;
        }

        if ($heightCm <= 144) {
            return 12;
        }

        if ($heightCm <= 179) {
            return 14;
        }

        if ($heightCm <= 209) {
            return 16;
        }

        return 18;
    }
}

