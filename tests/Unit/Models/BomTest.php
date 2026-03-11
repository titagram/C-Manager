<?php

namespace Tests\Unit\Models;

use App\Models\Bom;
use App\Models\BomRiga;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_has_many_righe(): void
    {
        $bom = Bom::factory()->create();
        BomRiga::factory()->count(3)->create(['bom_id' => $bom->id]);

        $this->assertCount(3, $bom->righe);
    }

    public function test_bom_belongs_to_prodotto(): void
    {
        $prodotto = Prodotto::factory()->create();
        $bom = Bom::factory()->create(['prodotto_id' => $prodotto->id]);

        $this->assertEquals($prodotto->id, $bom->prodotto->id);
    }

    public function test_bom_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $bom = Bom::factory()->create(['created_by' => $user->id]);

        $this->assertEquals($user->id, $bom->createdBy->id);
    }

    public function test_bom_scope_active(): void
    {
        Bom::factory()->create(['is_active' => true]);
        Bom::factory()->create(['is_active' => false]);

        $this->assertCount(1, Bom::active()->get());
    }

    public function test_bom_soft_delete(): void
    {
        $bom = Bom::factory()->create();
        $bom->delete();

        $this->assertSoftDeleted($bom);
        $this->assertCount(0, Bom::all());
        $this->assertCount(1, Bom::withTrashed()->get());
    }

    public function test_bom_auto_generates_codice(): void
    {
        $bom = Bom::factory()->create(['codice' => null]);

        $this->assertNotNull($bom->codice);
        $this->assertStringStartsWith('BOM-', $bom->codice);
    }

    public function test_bom_calcola_quantita_totale(): void
    {
        $bom = Bom::factory()->create();
        BomRiga::factory()->create(['bom_id' => $bom->id, 'quantita' => 2.0, 'coefficiente_scarto' => 0.10]);
        BomRiga::factory()->create(['bom_id' => $bom->id, 'quantita' => 1.5, 'coefficiente_scarto' => 0.05]);

        // (2.0 * 1.10) + (1.5 * 1.05) = 2.2 + 1.575 = 3.775
        $this->assertEqualsWithDelta(3.775, $bom->calcolaQuantitaTotale(), 0.0001);
        $this->assertEqualsWithDelta(3.775, $bom->calcolaQuantitaTotaleTemplate(), 0.0001);
    }
}
