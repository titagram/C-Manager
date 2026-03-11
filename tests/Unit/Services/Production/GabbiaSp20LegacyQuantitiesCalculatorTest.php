<?php

namespace Tests\Unit\Services\Production;

use App\Services\Production\GabbiaLegacyHeightQuantityTable;
use App\Services\Production\GabbiaSp20LegacyQuantitiesCalculator;
use Tests\TestCase;

class GabbiaSp20LegacyQuantitiesCalculatorTest extends TestCase
{
    public function test_height_quantity_table_matches_documented_legacy_bands(): void
    {
        $table = app(GabbiaLegacyHeightQuantityTable::class);

        $this->assertSame(4, $table->qtyFromHeightCm(39.99));
        $this->assertSame(6, $table->qtyFromHeightCm(40));
        $this->assertSame(6, $table->qtyFromHeightCm(64));
        $this->assertSame(8, $table->qtyFromHeightCm(65));
        $this->assertSame(8, $table->qtyFromHeightCm(99));
        $this->assertSame(10, $table->qtyFromHeightCm(100));
        $this->assertSame(10, $table->qtyFromHeightCm(119));
        $this->assertSame(12, $table->qtyFromHeightCm(120));
        $this->assertSame(12, $table->qtyFromHeightCm(144));
        $this->assertSame(14, $table->qtyFromHeightCm(145));
        $this->assertSame(14, $table->qtyFromHeightCm(179));
        $this->assertSame(16, $table->qtyFromHeightCm(180));
        $this->assertSame(16, $table->qtyFromHeightCm(209));
        $this->assertSame(18, $table->qtyFromHeightCm(209.01));
    }

    public function test_calculates_documented_gabbiasp20_quantities_for_short_and_long_lengths(): void
    {
        $calculator = app(GabbiaSp20LegacyQuantitiesCalculator::class);

        $short = $calculator->calculate(Lcm: 199, Wcm: 80, Hcm: 120);
        $this->assertSame([
            'D8' => 9,   // ceil((80/10)+0.5) = 9 (current documented legacy assumption)
            'D9' => 12,
            'D10' => 12,
            'D11' => 3,
            'D12' => 14,
            'D13' => 3,
        ], $short);

        $long = $calculator->calculate(Lcm: 200, Wcm: 43, Hcm: 55);
        $this->assertSame([
            'D8' => 5,   // ceil((43/10)+0.5) = 5
            'D9' => 6,
            'D10' => 6,
            'D11' => 4,
            'D12' => 16,
            'D13' => 4,
        ], $long);
    }
}

