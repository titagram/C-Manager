<?php

namespace Tests\Unit\Models;

use App\Models\Ordine;
use App\Models\OrdineRiga;
use App\Models\Prodotto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdineRigaTest extends TestCase
{
    use RefreshDatabase;

    public function test_riga_appartiene_a_ordine(): void
    {
        $ordine = Ordine::factory()->create();
        $riga = OrdineRiga::factory()->create(['ordine_id' => $ordine->id]);

        $this->assertInstanceOf(Ordine::class, $riga->ordineParent);
        $this->assertEquals($ordine->id, $riga->ordineParent->id);
    }

    public function test_riga_appartiene_a_prodotto(): void
    {
        $prodotto = Prodotto::factory()->create();
        $riga = OrdineRiga::factory()->create(['prodotto_id' => $prodotto->id]);

        $this->assertInstanceOf(Prodotto::class, $riga->prodotto);
        $this->assertEquals($prodotto->id, $riga->prodotto->id);
    }

    public function test_calcola_valori_from_dimensions(): void
    {
        $riga = new OrdineRiga([
            'larghezza_mm' => 1000,
            'profondita_mm' => 800,
            'altezza_mm' => 600,
            'quantita' => 2,
            'prezzo_mc' => 250,
        ]);

        $riga->calcolaValori();

        // Volume = 1.0m * 0.8m * 0.6m = 0.48 mc per piece
        $this->assertEquals(0.48, $riga->volume_mc_calcolato);
        // Total volume = 0.48 * 2 = 0.96 mc
        $this->assertEquals(0.96, $riga->volume_mc_finale);
        // Total = 0.96 * 250 = 240.00
        $this->assertEquals(240.00, $riga->totale_riga);
    }

    public function test_volume_finale_can_be_overridden(): void
    {
        $riga = new OrdineRiga([
            'larghezza_mm' => 1000,
            'profondita_mm' => 800,
            'altezza_mm' => 600,
            'quantita' => 1,
            'prezzo_mc' => 250,
            'volume_mc_finale' => 0.5, // Override
        ]);

        $riga->calcolaTotale();

        $this->assertEquals(125.00, $riga->totale_riga); // 0.5 * 250
    }

    public function test_ordine_has_many_righe(): void
    {
        $ordine = Ordine::factory()->create();
        OrdineRiga::factory()->count(3)->create(['ordine_id' => $ordine->id]);

        $this->assertCount(3, $ordine->righe);
    }

    public function test_righe_cascade_delete_with_ordine(): void
    {
        $ordine = Ordine::factory()->create();
        $righe = OrdineRiga::factory()->count(2)->create(['ordine_id' => $ordine->id]);

        $ordine->forceDelete();

        foreach ($righe as $riga) {
            $this->assertDatabaseMissing('ordine_righe', ['id' => $riga->id]);
        }
    }
}
