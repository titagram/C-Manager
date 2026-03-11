<?php

namespace Tests\Feature\Livewire;

use App\Enums\UnitaMisura;
use App\Livewire\Forms\CaricoForm;
use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CaricoFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Prodotto $prodotto;
    private \App\Models\Fornitore $fornitore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
        $this->prodotto = Prodotto::factory()->create();
        $this->fornitore = \App\Models\Fornitore::factory()->create();
    }

    public function test_carico_page_contains_livewire_component(): void
    {
        $response = $this->actingAs($this->user)->get('/magazzino/carico');

        $response->assertStatus(200);
        $response->assertSeeLivewire(CaricoForm::class);
    }

    public function test_can_create_carico(): void
    {
        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('codice_lotto', 'TEST-LOT-001')
            ->set('prodotto_id', $this->prodotto->id)
            ->set('fornitore_id', $this->fornitore->id)
            ->set('data_arrivo', now()->format('Y-m-d'))
            ->set('quantita', '100')
            ->call('save')
            ->assertRedirect('/magazzino');

        $this->assertDatabaseHas('lotti_materiale', [
            'codice_lotto' => 'TEST-LOT-001',
            'prodotto_id' => $this->prodotto->id,
        ]);

        $lotto = LottoMateriale::where('codice_lotto', 'TEST-LOT-001')->first();
        $this->assertEquals(1, $lotto->movimenti()->count());
    }

    public function test_carico_uses_product_dimensions_without_manual_dimension_inputs(): void
    {
        $prodottoConDimensioni = Prodotto::factory()->create([
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 120,
            'spessore_mm' => 25,
        ]);

        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('codice_lotto', 'TEST-LOT-DIM-001')
            ->set('prodotto_id', $prodottoConDimensioni->id)
            ->set('fornitore_id', $this->fornitore->id)
            ->set('data_arrivo', now()->format('Y-m-d'))
            ->set('quantita', '5')
            ->call('save')
            ->assertRedirect('/magazzino');

        $lotto = LottoMateriale::where('codice_lotto', 'TEST-LOT-DIM-001')->firstOrFail();
        $this->assertEquals(4000.0, (float) $lotto->lunghezza_mm);
        $this->assertEquals(120.0, (float) $lotto->larghezza_mm);
        $this->assertEquals(25.0, (float) $lotto->spessore_mm);
    }

    public function test_codice_lotto_must_be_unique(): void
    {
        LottoMateriale::factory()->create(['codice_lotto' => 'EXISTING']);

        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('codice_lotto', 'EXISTING')
            ->set('prodotto_id', $this->prodotto->id)
            ->set('fornitore_id', $this->fornitore->id)
            ->set('data_arrivo', now()->format('Y-m-d'))
            ->set('quantita', '100')
            ->call('save')
            ->assertHasErrors(['codice_lotto']);
    }

    public function test_prodotto_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('codice_lotto', 'TEST')
            ->set('prodotto_id', '')
            ->set('fornitore_id', $this->fornitore->id)
            ->set('data_arrivo', now()->format('Y-m-d'))
            ->set('quantita', '100')
            ->call('save')
            ->assertHasErrors(['prodotto_id' => 'required']);
    }

    public function test_quantita_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('codice_lotto', 'TEST')
            ->set('prodotto_id', $this->prodotto->id)
            ->set('fornitore_id', $this->fornitore->id)
            ->set('data_arrivo', now()->format('Y-m-d'))
            ->set('quantita', '')
            ->call('save')
            ->assertHasErrors(['quantita' => 'required']);
    }

    public function test_quantita_must_be_positive(): void
    {
        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('codice_lotto', 'TEST')
            ->set('prodotto_id', $this->prodotto->id)
            ->set('fornitore_id', $this->fornitore->id)
            ->set('data_arrivo', now()->format('Y-m-d'))
            ->set('quantita', '-10')
            ->call('save')
            ->assertHasErrors(['quantita']);
    }

    public function test_generate_codice_lotto(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(CaricoForm::class);

        $initialCode = $component->get('codice_lotto');

        $component->call('generateCodiceLotto');

        $this->assertNotEmpty($component->get('codice_lotto'));
    }

    public function test_fitok_fields_shown_for_fitok_product(): void
    {
        $prodottoFitok = Prodotto::factory()->create(['soggetto_fitok' => true]);

        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('prodotto_id', $prodottoFitok->id)
            ->assertSet('showFitok', true);
    }

    public function test_fitok_fields_hidden_for_non_fitok_product(): void
    {
        $prodottoNonFitok = Prodotto::factory()->create(['soggetto_fitok' => false]);

        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('prodotto_id', $prodottoNonFitok->id)
            ->assertSet('showFitok', false);
    }

    public function test_quantita_label_changes_with_selected_product_unit(): void
    {
        $prodottoMc = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC->value,
        ]);

        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('prodotto_id', $prodottoMc->id)
            ->assertSee('Quantita (m³) *');
    }

    public function test_generate_codice_lotto_refresh_generates_new_code_each_click(): void
    {
        $component = Livewire::actingAs($this->user)->test(CaricoForm::class);

        $first = $component->get('codice_lotto');
        $component->call('generateCodiceLotto');
        $second = $component->get('codice_lotto');
        $component->call('generateCodiceLotto');
        $third = $component->get('codice_lotto');

        $this->assertNotSame($first, $second);
        $this->assertNotSame($second, $third);
    }

    public function test_generate_codice_lotto_avoids_existing_codes(): void
    {
        LottoMateriale::factory()->create(['codice_lotto' => now()->format('\\Ly\\m-0001')]);

        $component = Livewire::actingAs($this->user)->test(CaricoForm::class);
        $generated = $component->get('codice_lotto');

        $this->assertNotSame(now()->format('\\Ly\\m-0001'), $generated);
    }

    public function test_calcola_peso_totale_da_peso_specifico_per_prodotti_mc(): void
    {
        $prodottoMc = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC->value,
            'peso_specifico_kg_mc' => 420,
        ]);

        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('codice_lotto', 'TEST-LOT-PESO-MC')
            ->set('prodotto_id', $prodottoMc->id)
            ->set('fornitore_id', $this->fornitore->id)
            ->set('data_arrivo', now()->format('Y-m-d'))
            ->set('quantita', '2')
            ->call('save')
            ->assertRedirect('/magazzino');

        $this->assertDatabaseHas('lotti_materiale', [
            'codice_lotto' => 'TEST-LOT-PESO-MC',
            'peso_totale_kg' => 840.000,
        ]);
    }

    public function test_per_prodotti_kg_il_peso_totale_corrisponde_alla_quantita(): void
    {
        $prodottoKg = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::KG->value,
            'peso_specifico_kg_mc' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(CaricoForm::class)
            ->set('codice_lotto', 'TEST-LOT-PESO-KG')
            ->set('prodotto_id', $prodottoKg->id)
            ->set('fornitore_id', $this->fornitore->id)
            ->set('data_arrivo', now()->format('Y-m-d'))
            ->set('quantita', '125.5')
            ->call('save')
            ->assertRedirect('/magazzino');

        $this->assertDatabaseHas('lotti_materiale', [
            'codice_lotto' => 'TEST-LOT-PESO-KG',
            'peso_totale_kg' => 125.500,
        ]);
    }
}
