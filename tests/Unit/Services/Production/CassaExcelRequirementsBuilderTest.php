<?php

namespace Tests\Unit\Services\Production;

use App\Services\Production\CassaExcelRequirementsBuilder;
use Tests\TestCase;

class CassaExcelRequirementsBuilderTest extends TestCase
{
    public function test_cassasp25_matches_real_workbook_case_80x80x120(): void
    {
        $builder = app(CassaExcelRequirementsBuilder::class);

        $result = $builder->buildForRoutine('cassasp25', 80, 80, 120, 1);

        $rows = collect($result['rows'])->keyBy('row');

        $this->assertSame('cassasp25', $result['routine']);
        $this->assertSame(17, (int) $rows[8]['D_qty_total']);
        $this->assertSame(24, (int) $rows[9]['D_qty_total']);
        $this->assertSame(24, (int) $rows[10]['D_qty_total']);
        $this->assertSame(3, (int) $rows[11]['D_qty_total']);
        $this->assertSame(14, (int) $rows[12]['D_qty_total']);
        $this->assertSame(3, (int) $rows[13]['D_qty_total']);
        $this->assertSame(40.0, (float) $rows[11]['B_section_mm']);
    }

    public function test_cassasp25fondo40_builds_macro_variant_with_extra_row14(): void
    {
        $builder = app(CassaExcelRequirementsBuilder::class);

        $result = $builder->buildForRoutine('cassasp25fondo40', 80, 80, 120, 1);

        $rows = collect($result['rows'])->keyBy('row');

        $this->assertSame('cassasp25fondo40', $result['routine']);
        $this->assertSame(40.0, (float) $rows[8]['B_section_mm']);
        $this->assertSame(8, (int) $rows[8]['D_qty_total']);
        $this->assertSame(8, (int) $rows[14]['D_qty_total']);
        $this->assertSame('fondo', $rows[8]['profile_key']);
    }
}
