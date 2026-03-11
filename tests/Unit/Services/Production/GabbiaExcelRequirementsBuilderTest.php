<?php

namespace Tests\Unit\Services\Production;

use App\Services\Production\GabbiaExcelRequirementsBuilder;
use Tests\TestCase;

class GabbiaExcelRequirementsBuilderTest extends TestCase
{
    public function test_builds_gabbiasp20_rows_and_normalized_pieces_from_excel_rules(): void
    {
        $builder = app(GabbiaExcelRequirementsBuilder::class);

        $result = $builder->buildForRoutine(
            routine: 'gabbiasp20',
            Lcm: 84,
            Wcm: 43,
            Hcm: 55,
            numeroPezzi: 2
        );

        $this->assertSame('gabbiasp20', $result['routine']);
        $this->assertSame('gabbia_sp20', $result['family']);
        $this->assertCount(6, $result['rows']);
        $this->assertCount(6, $result['pieces']);

        $this->assertSame([
            'D8' => 5,
            'D9' => 6,
            'D10' => 6,
            'D11' => 3,
            'D12' => 14,
            'D13' => 3,
        ], $result['legacy_quantities']);

        $row8 = collect($result['rows'])->firstWhere('row', 8);
        $this->assertNotNull($row8);
        $this->assertEqualsWithDelta(88.0, (float) $row8['A_length_cm'], 0.0001); // L+4
        $this->assertEqualsWithDelta(25.0, (float) $row8['B_section_mm'], 0.0001); // standard
        $this->assertEqualsWithDelta(10.0, (float) $row8['C_width_cm'], 0.0001);
        $this->assertSame(5, (int) $row8['D_qty_per_unit']);
        $this->assertSame(10, (int) $row8['D_qty_total']);

        $piece12 = collect($result['pieces'])->firstWhere('description', 'Gabbia SP20 riga 12');
        $this->assertNotNull($piece12);
        $this->assertEqualsWithDelta(670.0, (float) $piece12['length'], 0.0001); // (55+12) cm -> mm
        $this->assertEqualsWithDelta(80.0, (float) $piece12['width'], 0.0001); // 8 cm -> mm
        $this->assertSame(28, (int) $piece12['quantity']); // D12=14 * 2
    }

    public function test_builds_gabbiasp20_fondo4_with_expected_legacy_quantities_and_row8_section(): void
    {
        $builder = app(GabbiaExcelRequirementsBuilder::class);

        $result = $builder->buildForRoutine(
            routine: 'gabbiasp20fondo4',
            Lcm: 84,
            Wcm: 43,
            Hcm: 55,
            numeroPezzi: 1
        );

        $this->assertSame('gabbiasp20fondo4', $result['routine']);
        $this->assertSame('gabbia_sp20', $result['family']);
        $this->assertCount(6, $result['rows']);
        $this->assertCount(6, $result['pieces']);

        $this->assertSame([
            'D8' => 5,
            'D9' => 6,
            'D10' => 6,
            'D11' => 3,
            'D12' => 14,
            'D13' => 3,
        ], $result['legacy_quantities']);

        $row8 = collect($result['rows'])->firstWhere('row', 8);
        $this->assertNotNull($row8);
        $this->assertEqualsWithDelta(40.0, (float) $row8['B_section_mm'], 0.0001); // fondo4 diff
        $this->assertSame(5, (int) $row8['D_qty_per_unit']);

        $totalQty = collect($result['rows'])->sum(fn (array $row): int => (int) ($row['D_qty_total'] ?? 0));
        $this->assertSame(37, (int) $totalQty);
    }

    public function test_builds_gabbia_legaccio_4_piantoni_fondo4_rows_and_quantities(): void
    {
        $builder = app(GabbiaExcelRequirementsBuilder::class);

        $result = $builder->buildForRoutine(
            routine: 'gabbialegaccio4piantonifondo4',
            Lcm: 120,
            Wcm: 36,
            Hcm: 130,
            numeroPezzi: 2
        );

        $this->assertSame('gabbialegaccio4piantonifondo4', $result['routine']);
        $this->assertSame('gabbia_legaccio', $result['family']);
        $this->assertCount(9, $result['rows']);
        $this->assertCount(9, $result['pieces']);

        $this->assertSame(5, (int) data_get($result, 'legacy_quantities.D8'));
        $this->assertSame(12, (int) data_get($result, 'legacy_quantities.D9'));
        $this->assertSame(4, (int) data_get($result, 'legacy_quantities.D11'));
        $this->assertSame(2, (int) data_get($result, 'legacy_quantities.D15'));
        $this->assertSame(6, (int) data_get($result, 'legacy_quantities.D16')); // W > 35

        $row8 = collect($result['rows'])->firstWhere('row', 8);
        $this->assertNotNull($row8);
        $this->assertEqualsWithDelta(40.0, (float) $row8['B_section_mm'], 0.0001); // fondo4

        $row11 = collect($result['rows'])->firstWhere('row', 11);
        $this->assertNotNull($row11);
        $this->assertEqualsWithDelta(100.0, (float) $row11['A_length_cm'], 0.0001); // H=130 => class 100
    }

    public function test_gabbia_legaccio_6_piantoni_fondo4_keeps_legacy_height_threshold_219_quirk(): void
    {
        $builder = app(GabbiaExcelRequirementsBuilder::class);

        $result215 = $builder->buildForRoutine(
            routine: 'gabbialegaccio6piantonifondo4',
            Lcm: 120,
            Wcm: 40,
            Hcm: 215,
            numeroPezzi: 1
        );
        $result220 = $builder->buildForRoutine(
            routine: 'gabbialegaccio6piantonifondo4',
            Lcm: 120,
            Wcm: 40,
            Hcm: 220,
            numeroPezzi: 1
        );

        $this->assertSame(16, (int) data_get($result215, 'legacy_quantities.D9'));
        $this->assertSame(16, (int) data_get($result215, 'legacy_quantities.D10'));
        $this->assertSame(18, (int) data_get($result220, 'legacy_quantities.D9'));
        $this->assertSame(18, (int) data_get($result220, 'legacy_quantities.D10'));
    }
}
