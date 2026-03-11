<?php

namespace Tests\Feature\Livewire;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Livewire\Forms\ProdottoForm;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProdottoFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
    }

    public function test_create_page_contains_livewire_component(): void
    {
        $response = $this->actingAs($this->user)->get('/prodotti/create');

        $response->assertStatus(200);
        $response->assertSeeLivewire(ProdottoForm::class);
    }

    public function test_can_create_prodotto(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'TEST-001')
            ->set('nome', 'Prodotto Test')
            ->set('unita_misura', 'pz')
            ->set('categoria', 'altro')
            ->set('is_active', true)
            ->call('save')
            ->assertRedirect('/prodotti');

        $this->assertDatabaseHas('prodotti', [
            'codice' => 'TEST-001',
            'nome' => 'Prodotto Test',
        ]);
    }

    public function test_shows_prezzo_mc_input_and_pricing_tooltips_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->assertSee('id="prezzo_mc"', false)
            ->assertSee('Prezzo dedicato al m³')
            ->assertSee('Prezzo base del prodotto per l\'unità di misura selezionata')
            ->assertSee('Prezzo effettivo nei calcoli');
    }

    public function test_displays_effective_price_source_for_mc_products(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('unita_misura', UnitaMisura::MC->value)
            ->set('prezzo_unitario', 100)
            ->set('prezzo_mc', 540.55)
            ->assertSee('€ 540,5500')
            ->assertSee('Fonte: Prezzo dedicato m³');
    }

    public function test_displays_effective_price_source_for_non_mc_products(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('unita_misura', UnitaMisura::PZ->value)
            ->set('prezzo_unitario', 12.5)
            ->set('prezzo_mc', 999.99)
            ->assertSee('€ 12,5000')
            ->assertSee('Fonte: Prezzo listino');
    }

    public function test_codice_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', '')
            ->set('nome', 'Test')
            ->set('unita_misura', 'pz')
            ->set('categoria', 'altro')
            ->call('save')
            ->assertHasErrors(['codice' => 'required']);
    }

    public function test_codice_must_be_unique(): void
    {
        Prodotto::factory()->create(['codice' => 'EXISTING']);

        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'EXISTING')
            ->set('nome', 'Test')
            ->set('unita_misura', 'pz')
            ->set('categoria', 'altro')
            ->call('save')
            ->assertHasErrors(['codice']);
    }

    public function test_nome_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'TEST')
            ->set('nome', '')
            ->set('unita_misura', 'pz')
            ->set('categoria', 'altro')
            ->call('save')
            ->assertHasErrors(['nome' => 'required']);
    }

    public function test_can_update_prodotto(): void
    {
        $prodotto = Prodotto::factory()->create([
            'codice' => 'OLD-CODE',
            'nome' => 'Old Name',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class, ['prodotto' => $prodotto])
            ->set('nome', 'New Name')
            ->call('save')
            ->assertRedirect('/prodotti');

        $this->assertEquals('New Name', $prodotto->fresh()->nome);
    }

    public function test_can_update_codice_to_same_value(): void
    {
        $prodotto = Prodotto::factory()->create(['codice' => 'SAME-CODE']);

        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class, ['prodotto' => $prodotto])
            ->set('codice', 'SAME-CODE')
            ->set('nome', 'Updated')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_prezzo_unitario_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'TEST')
            ->set('nome', 'Test')
            ->set('unita_misura', 'pz')
            ->set('categoria', 'altro')
            ->set('prezzo_unitario', '-10')
            ->call('save')
            ->assertHasErrors(['prezzo_unitario']);
    }

    public function test_dimensions_are_cleared_if_usa_dimensioni_is_false(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'TEST-NO-DIMS')
            ->set('nome', 'No Dims')
            ->set('unita_misura', 'pz')
            ->set('categoria', 'altro')
            ->set('usa_dimensioni', false)
            ->set('lunghezza_mm', 100)
            ->set('larghezza_mm', 50)
            ->set('spessore_mm', 20)
            ->call('save');

        $this->assertDatabaseHas('prodotti', [
            'codice' => 'TEST-NO-DIMS',
            'usa_dimensioni' => false,
            'lunghezza_mm' => null,
            'larghezza_mm' => null,
            'spessore_mm' => null,
        ]);
    }

    public function test_dimensions_are_saved_if_usa_dimensioni_is_true(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'TEST-DIMS')
            ->set('nome', 'Yes Dims')
            ->set('unita_misura', 'pz')
            ->set('categoria', 'altro')
            ->set('usa_dimensioni', true)
            ->set('lunghezza_mm', 100)
            ->set('larghezza_mm', 50)
            ->set('spessore_mm', 20)
            ->call('save');

        $this->assertDatabaseHas('prodotti', [
            'codice' => 'TEST-DIMS',
            'usa_dimensioni' => true,
            'lunghezza_mm' => 100,
            'larghezza_mm' => 50,
            'spessore_mm' => 20,
        ]);
    }

    public function test_prezzo_unitario_e_costo_unitario_vengono_salvati(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'TEST-PRICE')
            ->set('nome', 'Prodotto Prezzo')
            ->set('unita_misura', 'mc')
            ->set('categoria', 'altro')
            ->set('prezzo_unitario', 123.4567)
            ->set('costo_unitario', 77.1234)
            ->call('save');

        $this->assertDatabaseHas('prodotti', [
            'codice' => 'TEST-PRICE',
            'prezzo_unitario' => 123.46,
            'prezzo_mc' => 123.46,
            'costo_unitario' => 77.1234,
        ]);
    }

    public function test_prezzo_mc_dedicato_viene_salvato_per_prodotti_mc_e_allinea_il_legacy_price(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'TEST-MC-PRICE')
            ->set('nome', 'Prodotto MC')
            ->set('unita_misura', 'mc')
            ->set('categoria', 'asse')
            ->set('prezzo_unitario', 1)
            ->set('prezzo_mc', 540.55)
            ->call('save');

        $this->assertDatabaseHas('prodotti', [
            'codice' => 'TEST-MC-PRICE',
            'prezzo_mc' => 540.55,
            'prezzo_unitario' => 540.55,
        ]);
    }

    public function test_can_save_peso_specifico_kg_mc(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'TEST-PESO-SPEC')
            ->set('nome', 'Abete Peso')
            ->set('unita_misura', UnitaMisura::MC->value)
            ->set('categoria', Categoria::ASSE->value)
            ->set('peso_specifico_kg_mc', 385.75)
            ->call('save')
            ->assertRedirect('/prodotti');

        $this->assertDatabaseHas('prodotti', [
            'codice' => 'TEST-PESO-SPEC',
            'peso_specifico_kg_mc' => 385.750,
        ]);
    }

    public function test_defaults_prezzo_mc_to_zero_for_mc_products_without_explicit_price(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottoForm::class)
            ->set('codice', 'TEST-MC-DEFAULT-ZERO')
            ->set('nome', 'Prodotto MC default')
            ->set('unita_misura', UnitaMisura::MC->value)
            ->set('categoria', Categoria::ASSE->value)
            ->set('prezzo_unitario', null)
            ->set('prezzo_mc', null)
            ->call('save')
            ->assertRedirect('/prodotti');

        $this->assertDatabaseHas('prodotti', [
            'codice' => 'TEST-MC-DEFAULT-ZERO',
            'prezzo_mc' => 0,
            'prezzo_unitario' => 0,
        ]);
    }
}
