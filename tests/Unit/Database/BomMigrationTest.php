<?php

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BomMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('bom'));
    }

    public function test_bom_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('bom', [
            'id',
            'codice',
            'nome',
            'prodotto_id',
            'categoria_output',
            'versione',
            'is_active',
            'note',
            'created_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ]));
    }

    public function test_bom_codice_is_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('bom')->insert([
            'codice' => 'BOM-001',
            'nome' => 'Test 1',
            'versione' => '1.0',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('bom')->insert([
            'codice' => 'BOM-001',
            'nome' => 'Test 2',
            'versione' => '1.0',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
