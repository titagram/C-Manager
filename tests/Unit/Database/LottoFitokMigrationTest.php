<?php

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoFitokMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lotti_produzione_has_fitok_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('lotti_produzione', [
            'fitok_percentuale',
            'fitok_volume_mc',
            'non_fitok_volume_mc',
            'fitok_calcolato_at',
        ]));
    }
}
