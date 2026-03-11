<?php

namespace Tests\Unit\Models;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Prodotto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdottoTest extends TestCase
{
    use RefreshDatabase;

    public function test_prodotto_ha_attributi_corretti(): void
    {
        $prodotto = Prodotto::factory()->create([
            'codice' => 'TEST-001',
            'nome' => 'Prodotto Test',
            'unita_misura' => UnitaMisura::MQ,
            'categoria' => Categoria::MATERIA_PRIMA,
            'soggetto_fitok' => true,
            'prezzo_unitario' => 25.50,
            'is_active' => true,
        ]);

        $this->assertEquals('TEST-001', $prodotto->codice);
        $this->assertEquals('Prodotto Test', $prodotto->nome);
        $this->assertEquals(UnitaMisura::MQ, $prodotto->unita_misura);
        $this->assertEquals(Categoria::MATERIA_PRIMA, $prodotto->categoria);
        $this->assertTrue($prodotto->soggetto_fitok);
        $this->assertEquals(25.50, $prodotto->prezzo_unitario);
        $this->assertTrue($prodotto->is_active);
    }

    public function test_scope_active_filtra_prodotti_attivi(): void
    {
        Prodotto::factory()->count(3)->create(['is_active' => true]);
        Prodotto::factory()->count(2)->create(['is_active' => false]);

        $this->assertCount(3, Prodotto::active()->get());
    }

    public function test_scope_fitok_filtra_prodotti_fitok(): void
    {
        Prodotto::factory()->count(2)->create(['soggetto_fitok' => true]);
        Prodotto::factory()->count(3)->create(['soggetto_fitok' => false]);

        $this->assertCount(2, Prodotto::fitok()->get());
    }

    public function test_scope_search_trova_per_codice(): void
    {
        Prodotto::factory()->create(['codice' => 'ABC-123', 'nome' => 'Test']);
        Prodotto::factory()->create(['codice' => 'XYZ-789', 'nome' => 'Altro']);

        $risultati = Prodotto::search('ABC')->get();

        $this->assertCount(1, $risultati);
        $this->assertEquals('ABC-123', $risultati->first()->codice);
    }

    public function test_scope_search_trova_per_nome(): void
    {
        Prodotto::factory()->create(['codice' => 'P1', 'nome' => 'Tavola Abete']);
        Prodotto::factory()->create(['codice' => 'P2', 'nome' => 'Pannello MDF']);

        $risultati = Prodotto::search('Abete')->get();

        $this->assertCount(1, $risultati);
        $this->assertEquals('Tavola Abete', $risultati->first()->nome);
    }

    public function test_scope_by_categoria(): void
    {
        Prodotto::factory()->create(['categoria' => Categoria::ASSE]);
        Prodotto::factory()->create(['categoria' => Categoria::LISTELLO]);
        Prodotto::factory()->create(['categoria' => Categoria::ASSE]);

        $this->assertCount(2, Prodotto::byCategoria(Categoria::ASSE)->get());
        $this->assertCount(1, Prodotto::byCategoria(Categoria::LISTELLO)->get());
    }

    public function test_prodotto_soft_delete(): void
    {
        $prodotto = Prodotto::factory()->create();
        $prodotto->delete();

        $this->assertSoftDeleted($prodotto);
        $this->assertCount(0, Prodotto::all());
        $this->assertCount(1, Prodotto::withTrashed()->get());
    }

    public function test_mc_products_backfill_prezzo_mc_from_prezzo_unitario_on_save(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC,
            'prezzo_unitario' => 540.1234,
            'prezzo_mc' => null,
        ]);

        $this->assertEquals(540.12, (float) $prodotto->prezzo_mc);
        $this->assertEquals(540.1200, (float) $prodotto->prezzo_unitario);
        $this->assertEquals(540.12, $prodotto->prezzoListinoPerMc());
        $this->assertEquals(540.12, $prodotto->prezzoListinoPerUnita(UnitaMisura::MC));
    }

    public function test_mc_products_keep_legacy_price_mirrored_when_prezzo_mc_is_provided(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC,
            'prezzo_unitario' => 1,
            'prezzo_mc' => 575.55,
        ]);

        $this->assertEquals(575.55, (float) $prodotto->prezzo_mc);
        $this->assertEquals(575.5500, (float) $prodotto->prezzo_unitario);
    }

    public function test_non_mc_products_clear_prezzo_mc_on_save(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::PZ,
            'prezzo_unitario' => 25.5,
            'prezzo_mc' => 999.99,
        ]);

        $this->assertNull($prodotto->prezzo_mc);
        $this->assertEquals(25.5, $prodotto->prezzoListinoPerUnita(UnitaMisura::PZ));
    }

    public function test_mc_products_default_prezzo_mc_to_zero_when_prices_are_missing(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC,
            'prezzo_unitario' => null,
            'prezzo_mc' => null,
        ]);

        $this->assertEquals(0.0, (float) $prodotto->prezzo_mc);
        $this->assertEquals(0.0, (float) $prodotto->prezzo_unitario);
        $this->assertEquals(0.0, $prodotto->prezzoListinoPerMc());
    }
}
