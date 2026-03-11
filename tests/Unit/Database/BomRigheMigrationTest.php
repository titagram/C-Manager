<?php

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BomRigheMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_righe_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('bom_righe'));
    }

    public function test_bom_righe_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('bom_righe', [
            'id',
            'bom_id',
            'prodotto_id',
            'descrizione',
            'quantita',
            'unita_misura',
            'coefficiente_scarto',
            'is_fitok_required',
            'ordine',
            'note',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_bom_righe_cascade_delete(): void
    {
        $bomId = DB::table('bom')->insertGetId([
            'codice' => 'BOM-TEST',
            'anno' => (int) now()->year,
            'progressivo' => 1,
            'nome' => 'Test BOM',
            'versione' => '1.0',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('bom_righe')->insert([
            'bom_id' => $bomId,
            'quantita' => 1.5,
            'ordine' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseCount('bom_righe', 1);

        DB::table('bom')->where('id', $bomId)->delete();

        $this->assertDatabaseCount('bom_righe', 0);
    }
}
