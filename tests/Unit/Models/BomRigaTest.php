<?php

namespace Tests\Unit\Models;

use App\Models\Bom;
use App\Models\BomRiga;
use App\Models\Prodotto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomRigaTest extends TestCase
{
    use RefreshDatabase;

    public function test_riga_belongs_to_bom(): void
    {
        $bom = Bom::factory()->create();
        $riga = BomRiga::factory()->create(['bom_id' => $bom->id]);

        $this->assertEquals($bom->id, $riga->bom->id);
    }

    public function test_riga_belongs_to_prodotto(): void
    {
        $prodotto = Prodotto::factory()->create();
        $riga = BomRiga::factory()->create(['prodotto_id' => $prodotto->id]);

        $this->assertEquals($prodotto->id, $riga->prodotto->id);
    }

    public function test_riga_calcola_quantita_con_scarto(): void
    {
        $riga = BomRiga::factory()->create([
            'quantita' => 2.0,
            'coefficiente_scarto' => 0.15,
        ]);

        // 2.0 * (1 + 0.15) = 2.3
        $this->assertEquals(2.3, $riga->quantitaConScarto());
    }

    public function test_riga_is_fitok_required_default_false(): void
    {
        $riga = BomRiga::factory()->create();

        $this->assertFalse($riga->is_fitok_required);
    }

    public function test_riga_cascade_deletes_with_bom(): void
    {
        $bom = Bom::factory()->create();
        BomRiga::factory()->count(3)->create(['bom_id' => $bom->id]);

        $this->assertDatabaseCount('bom_righe', 3);

        $bom->forceDelete();

        $this->assertDatabaseCount('bom_righe', 0);
    }
}
