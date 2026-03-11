<?php

namespace Tests\Unit\Services\Production;

use App\Services\Production\BancaleLegacyQuantitiesCalculator;
use Tests\TestCase;

class BancaleLegacyQuantitiesCalculatorTest extends TestCase
{
    public function test_calculates_documented_legacy_quantities_for_bancale(): void
    {
        $calculator = app(BancaleLegacyQuantitiesCalculator::class);

        $short = $calculator->calculate(Lcm: 199, Wcm: 43);
        $this->assertSame([
            'D8' => 4, // round(43/10) = 4
            'D9' => 3,
        ], $short);

        $long = $calculator->calculate(Lcm: 200, Wcm: 80);
        $this->assertSame([
            'D8' => 8, // round(80/10) = 8
            'D9' => 4,
        ], $long);
    }

    public function test_rounding_behavior_for_d8_is_isolated_and_explicit(): void
    {
        $calculator = app(BancaleLegacyQuantitiesCalculator::class);

        $this->assertSame(4, $calculator->legacyD8WidthQuantity(35.0));  // half-up
        $this->assertSame(3, $calculator->legacyD8WidthQuantity(34.9));
        $this->assertSame(4, $calculator->legacyD8WidthQuantity(39.0));
    }

    public function test_perimetrale_quantity_classes_match_documented_legacy_bands(): void
    {
        $calculator = app(BancaleLegacyQuantitiesCalculator::class);

        $this->assertSame(3, $calculator->perimetraleD10WidthClass(39.99));
        $this->assertSame(4, $calculator->perimetraleD10WidthClass(40.0));
        $this->assertSame(4, $calculator->perimetraleD10WidthClass(69.0));
        $this->assertSame(5, $calculator->perimetraleD10WidthClass(70.0));
        $this->assertSame(6, $calculator->perimetraleD10WidthClass(90.0));
        $this->assertSame(7, $calculator->perimetraleD10WidthClass(120.0));
        $this->assertSame(8, $calculator->perimetraleD10WidthClass(160.0));
        $this->assertSame(8, $calculator->perimetraleD10WidthClass(220.0)); // current top-class assumption

        $this->assertSame(4, $calculator->perimetraleD11LengthClass(179.99));
        $this->assertSame(6, $calculator->perimetraleD11LengthClass(180.0));
        $this->assertSame(6, $calculator->perimetraleD11LengthClass(249.0));
        $this->assertSame(8, $calculator->perimetraleD11LengthClass(250.0));

        $this->assertSame([
            'D8' => 2,
            'D9' => 2,
            'D10' => 7,
            'D11' => 6,
        ], $calculator->calculatePerimetrale(Lcm: 190, Wcm: 120));
    }
}
