<?php

namespace Tests\Unit\Services\Production;

use App\Services\Production\LegaccioExcelRequirementsBuilder;
use Tests\TestCase;

class LegaccioExcelRequirementsBuilderTest extends TestCase
{
    public function test_builds_legacci224x60_rows_and_normalized_pieces(): void
    {
        $builder = app(LegaccioExcelRequirementsBuilder::class);

        $result = $builder->buildForRoutine(
            routine: 'legacci224x60',
            numeroPezzi: 2
        );

        $this->assertSame('legacci224x60', $result['routine']);
        $this->assertSame('legaccio_224x60', $result['family']);
        $this->assertCount(3, $result['rows']);
        $this->assertCount(3, $result['pieces']);

        $row8 = collect($result['rows'])->firstWhere('row', 8);
        $this->assertNotNull($row8);
        $this->assertEqualsWithDelta(225.0, (float) $row8['A_length_cm'], 0.0001);
        $this->assertEqualsWithDelta(7.5, (float) $row8['B_section_mm'], 0.0001);
        $this->assertEqualsWithDelta(55.0, (float) $row8['C_width_cm'], 0.0001);
        $this->assertSame(4, (int) $row8['D_qty_per_unit']);
        $this->assertSame(8, (int) $row8['D_qty_total']);

        $piece10 = collect($result['pieces'])->firstWhere('description', 'Legacci 224x60 riga 10');
        $this->assertNotNull($piece10);
        $this->assertEqualsWithDelta(570.0, (float) $piece10['length'], 0.0001);
        $this->assertEqualsWithDelta(400.0, (float) $piece10['width'], 0.0001);
        $this->assertSame(8, (int) $piece10['quantity']);
    }
}

