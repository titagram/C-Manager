<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Tables\LottiMaterialeTable;
use App\Models\LottoMateriale;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LottiMaterialeTableTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_magazzino_page_contains_livewire_component(): void
    {
        $response = $this->actingAs($this->user)->get('/magazzino');

        $response->assertStatus(200);
        $response->assertSeeLivewire(LottiMaterialeTable::class);
    }

    public function test_component_renders_lotti(): void
    {
        $prodotto = Prodotto::factory()->create(['nome' => 'Tavola Test']);
        LottoMateriale::factory()->create([
            'codice_lotto' => 'LOT-TEST-001',
            'prodotto_id' => $prodotto->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiMaterialeTable::class)
            ->assertSee('LOT-TEST-001')
            ->assertSee('Tavola Test');
    }

    public function test_search_filter_works(): void
    {
        LottoMateriale::factory()->create(['codice_lotto' => 'ABC-001']);
        LottoMateriale::factory()->create(['codice_lotto' => 'XYZ-002']);

        Livewire::actingAs($this->user)
            ->test(LottiMaterialeTable::class)
            ->set('search', 'ABC')
            ->assertSee('ABC-001')
            ->assertDontSee('XYZ-002');
    }

    public function test_prodotto_filter_works(): void
    {
        $prodotto1 = Prodotto::factory()->create();
        $prodotto2 = Prodotto::factory()->create();

        LottoMateriale::factory()->create([
            'codice_lotto' => 'LOT-A',
            'prodotto_id' => $prodotto1->id,
        ]);
        LottoMateriale::factory()->create([
            'codice_lotto' => 'LOT-B',
            'prodotto_id' => $prodotto2->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiMaterialeTable::class)
            ->set('prodotto', $prodotto1->id)
            ->assertSee('LOT-A')
            ->assertDontSee('LOT-B');
    }

    public function test_solo_fitok_filter(): void
    {
        $prodottoFitok = Prodotto::factory()->create(['soggetto_fitok' => true]);
        $prodottoNonFitok = Prodotto::factory()->create(['soggetto_fitok' => false]);

        LottoMateriale::factory()->create([
            'codice_lotto' => 'FITOK-LOT',
            'prodotto_id' => $prodottoFitok->id,
        ]);
        LottoMateriale::factory()->create([
            'codice_lotto' => 'NORMAL-LOT',
            'prodotto_id' => $prodottoNonFitok->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiMaterialeTable::class)
            ->set('soloFitok', true)
            ->assertSee('FITOK-LOT')
            ->assertDontSee('NORMAL-LOT');
    }

    public function test_reset_filters(): void
    {
        Livewire::actingAs($this->user)
            ->test(LottiMaterialeTable::class)
            ->set('search', 'test')
            ->set('soloFitok', true)
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('soloFitok', false);
    }

    public function test_shows_peso_totale_when_available(): void
    {
        $prodotto = Prodotto::factory()->create(['nome' => 'Abete']);
        LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'peso_totale_kg' => 840.125,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiMaterialeTable::class)
            ->assertSee('Peso')
            ->assertSee('840,13')
            ->assertSee('kg');
    }

    public function test_calcola_e_mostra_peso_con_fallback_su_giacenza_e_peso_specifico(): void
    {
        $prodotto = Prodotto::factory()->create([
            'nome' => 'Abete fallback peso',
            'unita_misura' => \App\Enums\UnitaMisura::MC,
            'peso_specifico_kg_mc' => null,
        ]);

        $lotto = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 2.5,
            'peso_totale_kg' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiMaterialeTable::class)
            ->assertSee($lotto->codice_lotto)
            ->assertSee('900,00')
            ->assertSee('kg');
    }

    public function test_shows_link_to_material_lot_movements_page(): void
    {
        $lotto = LottoMateriale::factory()->create([
            'codice_lotto' => 'LOT-MOV-001',
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiMaterialeTable::class)
            ->assertSee(route('magazzino.movimenti', $lotto), false)
            ->assertSee('Movimenti lotto');
    }
}
