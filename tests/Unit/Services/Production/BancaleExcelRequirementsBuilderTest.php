<?php

namespace Tests\Unit\Services\Production;

use App\Services\Production\BancaleExcelRequirementsBuilder;
use Tests\TestCase;

class BancaleExcelRequirementsBuilderTest extends TestCase
{
    public function test_builds_bancale_rows_and_normalized_pieces_from_excel_rules(): void
    {
        $builder = app(BancaleExcelRequirementsBuilder::class);

        $result = $builder->buildForRoutine(
            routine: 'bancale',
            Lcm: 120,
            Wcm: 80,
            numeroPezzi: 2
        );

        $this->assertSame('bancale', $result['routine']);
        $this->assertSame('bancale_standard', $result['family']);
        $this->assertCount(2, $result['rows']);
        $this->assertCount(2, $result['pieces']);
        $this->assertSame([
            'D8' => 8,
            'D9' => 3,
        ], $result['legacy_quantities']);

        $row8 = collect($result['rows'])->firstWhere('row', 8);
        $this->assertNotNull($row8);
        $this->assertEqualsWithDelta(120.0, (float) $row8['A_length_cm'], 0.0001);
        $this->assertEqualsWithDelta(25.0, (float) $row8['B_section_mm'], 0.0001);
        $this->assertEqualsWithDelta(10.0, (float) $row8['C_width_cm'], 0.0001);
        $this->assertSame(8, (int) $row8['D_qty_per_unit']);
        $this->assertSame(16, (int) $row8['D_qty_total']);

        $piece9 = collect($result['pieces'])->firstWhere('description', 'Bancale riga 9');
        $this->assertNotNull($piece9);
        $this->assertEqualsWithDelta(800.0, (float) $piece9['length'], 0.0001); // 80cm -> mm
        $this->assertEqualsWithDelta(100.0, (float) $piece9['width'], 0.0001); // 10cm -> mm
        $this->assertSame(6, (int) $piece9['quantity']); // D9=3 * 2
    }

    public function test_builds_perimetrale_rows_and_normalized_pieces_from_excel_rules(): void
    {
        $builder = app(BancaleExcelRequirementsBuilder::class);

        $result = $builder->buildForRoutine(
            routine: 'perimetrale',
            Lcm: 190,
            Wcm: 120,
            Hcm: 80,
            numeroPezzi: 1
        );

        $this->assertSame('perimetrale', $result['routine']);
        $this->assertSame('bancale_perimetrale', $result['family']);
        $this->assertCount(4, $result['rows']);
        $this->assertCount(4, $result['pieces']);
        $this->assertSame([
            'D8' => 2,
            'D9' => 2,
            'D10' => 7,
            'D11' => 6,
        ], $result['legacy_quantities']);

        $row8 = collect($result['rows'])->firstWhere('row', 8);
        $this->assertNotNull($row8);
        $this->assertEqualsWithDelta(195.0, (float) $row8['A_length_cm'], 0.0001); // L+5
        $this->assertEqualsWithDelta(25.0, (float) $row8['B_section_mm'], 0.0001);
        $this->assertEqualsWithDelta(80.0, (float) $row8['C_width_cm'], 0.0001); // H
        $this->assertSame(2, (int) $row8['D_qty_per_unit']);
        $this->assertSame(2, (int) $row8['D_qty_total']);

        $row11 = collect($result['rows'])->firstWhere('row', 11);
        $this->assertNotNull($row11);
        $this->assertEqualsWithDelta(125.0, (float) $row11['A_length_cm'], 0.0001); // W+5
        $this->assertEqualsWithDelta(8.0, (float) $row11['C_width_cm'], 0.0001);
        $this->assertSame(6, (int) $row11['D_qty_total']);
    }
}
