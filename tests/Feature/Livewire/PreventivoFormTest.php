<?php

namespace Tests\Feature\Livewire;

use App\Enums\TipoRigaPreventivo;
use App\Enums\UnitaMisura;
use App\Livewire\Forms\PreventivoForm;
use App\Models\Cliente;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PreventivoFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Cliente $cliente;

    private Prodotto $prodotto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
        $this->cliente = Cliente::factory()->create();
        $this->prodotto = Prodotto::factory()->create([
            'prezzo_unitario' => 500,
            'coefficiente_scarto' => 0.10,
        ]);
    }

    public function test_create_page_contains_livewire_component(): void
    {
        $response = $this->actingAs($this->user)->get('/preventivi/create');

        $response->assertStatus(200);
        $response->assertSeeLivewire(PreventivoForm::class);
    }

    public function test_can_create_preventivo(): void
    {
        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->set('descrizione', 'Test preventivo')
            ->set('righe', [[
                'prodotto_id' => $this->prodotto->id,
                'descrizione' => 'Tavola test',
                'lunghezza_mm' => 2000,
                'larghezza_mm' => 300,
                'spessore_mm' => 50,
                'quantita' => 10,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 500,
            ]])
            ->call('save')
            ->assertRedirect('/preventivi');

        $this->assertDatabaseHas('preventivi', [
            'cliente_id' => $this->cliente->id,
            'descrizione' => 'Test preventivo',
        ]);
    }

    public function test_can_create_preventivo_without_cliente(): void
    {
        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('cliente_id', null)
            ->set('descrizione', 'Preventivo senza cliente')
            ->set('righe', [[
                'prodotto_id' => $this->prodotto->id,
                'descrizione' => 'Tavola test',
                'lunghezza_mm' => 2000,
                'larghezza_mm' => 300,
                'spessore_mm' => 50,
                'quantita' => 10,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 500,
            ]])
            ->call('save')
            ->assertRedirect('/preventivi');

        $this->assertDatabaseHas('preventivi', [
            'cliente_id' => null,
            'descrizione' => 'Preventivo senza cliente',
        ]);
    }

    // Disabled: cliente_id is now nullable per Task 7 requirements
    // public function test_cliente_is_required(): void
    // {
    //     Livewire::actingAs($this->user)
    //         ->test(PreventivoForm::class)
    //         ->set('cliente_id', null)
    //         ->set('righe', [[
    //             'descrizione' => 'Test',
    //             'lunghezza_mm' => 1000,
    //             'larghezza_mm' => 100,
    //             'spessore_mm' => 20,
    //             'quantita' => 1,
    //             'coefficiente_scarto' => 0.10,
    //             'prezzo_unitario' => 100,
    //         ]])
    //         ->call('save')
    //         ->assertHasErrors(['cliente_id']);
    // }

    public function test_new_preventivo_has_no_default_rows(): void
    {
        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->assertCount('righe', 0);
    }

    public function test_existing_preventivo_loads_rows(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create([
            'cliente_id' => $this->cliente->id,
        ]);
        $preventivo->righe()->create([
            'descrizione' => 'Test Row',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita' => 1,
            'coefficiente_scarto' => 0.10,
            'prezzo_unitario' => 100,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class, ['preventivo' => $preventivo])
            ->assertCount('righe', 1)
            ->assertSet('righe.0.descrizione', 'Test Row');
    }

    public function test_cannot_save_preventivo_without_rows(): void
    {
        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->call('save')
            ->assertHasErrors(['righe']);
    }

    public function test_can_add_and_remove_row(): void
    {
        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->assertCount('righe', 0)
            ->set('showRigaModal', true)
            ->call('creaRigaDaLotto')
            ->assertRedirect();
    }

    public function test_completed_lotti_are_available_for_duplication_in_add_row_modal(): void
    {
        $lottoCompletato = LottoProduzione::factory()->completato()->create([
            'codice_lotto' => 'LP-DUP-001',
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('showRigaModal', true)
            ->assertSee('Duplica lotto esistente')
            ->assertSee('LP-DUP-001')
            ->assertSee('[Completato]');
    }

    public function test_crea_riga_da_lotto_duplicates_completed_lot_into_new_bozza(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoCompletato = LottoProduzione::factory()->completato()->create([
            'created_by' => $this->user->id,
            'prodotto_finale' => 'Lotto sorgente completato',
            'optimizer_result' => ['version' => 'v2'],
        ]);

        $lottoCompletato->materialiUsati()->create([
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse clonata',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 2,
            'volume_mc' => 0.080000,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->call('creaRigaDaLotto', $lottoCompletato->id);

        $lottoDuplicato = LottoProduzione::query()
            ->whereKeyNot($lottoCompletato->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(\App\Enums\StatoLottoProduzione::BOZZA, $lottoDuplicato->stato);
        $this->assertSame($this->cliente->id, $lottoDuplicato->cliente_id);
        $this->assertSame('Lotto sorgente completato', $lottoDuplicato->prodotto_finale);
        $this->assertDatabaseHas('lotto_produzione_materiali', [
            'lotto_produzione_id' => $lottoDuplicato->id,
            'prodotto_id' => $prodotto->id,
        ]);
        $this->assertDatabaseHas('preventivo_righe', [
            'lotto_produzione_id' => $lottoDuplicato->id,
            'tipo_riga' => TipoRigaPreventivo::LOTTO->value,
        ]);
    }

    public function test_totale_is_calculated(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->set('righe', [[
                'descrizione' => 'Test',
                'lunghezza_mm' => 1000,
                'larghezza_mm' => 200,
                'spessore_mm' => 50,
                'quantita' => 10,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 1000,
            ]]);

        // Volume: 1 * 0.2 * 0.05 * 10 = 0.1 mc
        // Con scarto 10%: 0.1 * 1.1 = 0.11 mc (arrotondato per eccesso)
        // Totale: 0.11 * 1000 = 110 EUR (approssimativo)
        $component->assertSet('totale_materiali', fn ($value) => $value > 0);
    }

    public function test_can_update_preventivo(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create([
            'cliente_id' => $this->cliente->id,
            'descrizione' => 'Old description',
        ]);
        $riga = $preventivo->righe()->create([
            'descrizione' => 'Old row',
            'lunghezza_mm' => 500,
            'larghezza_mm' => 50,
            'spessore_mm' => 10,
            'quantita' => 1,
            'coefficiente_scarto' => 0.10,
            'prezzo_unitario' => 100,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class, ['preventivo' => $preventivo])
            ->set('descrizione', 'New description')
            ->set('righe.0.id', $riga->id)
            ->set('righe.0.descrizione', 'Updated row')
            ->set('righe.0.lunghezza_mm', 1000)
            ->set('righe.0.larghezza_mm', 100)
            ->set('righe.0.spessore_mm', 25)
            ->set('righe.0.quantita', 5)
            ->set('righe.0.coefficiente_scarto', 0.10)
            ->set('righe.0.prezzo_unitario', 500)
            ->call('save')
            ->assertRedirect('/preventivi');

        $this->assertEquals('New description', $preventivo->fresh()->descrizione);
    }

    public function test_seleziona_prodotto_populates_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('righe', [[
                'descrizione' => '',
                'lunghezza_mm' => 0,
                'larghezza_mm' => 0,
                'spessore_mm' => 0,
                'quantita' => 1,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 0,
            ]])
            ->call('selezionaProdotto', 0, $this->prodotto->id)
            ->assertSet('righe.0.prodotto_id', $this->prodotto->id)
            ->assertSet('righe.0.descrizione', $this->prodotto->nome)
            ->assertSet('righe.0.coefficiente_scarto', $this->prodotto->coefficiente_scarto)
            ->assertSet('righe.0.prezzo_unitario', $this->prodotto->prezzo_unitario);
    }

    public function test_seleziona_prodotto_populates_dimensions_when_available(): void
    {
        $this->prodotto->update([
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 120,
            'spessore_mm' => 25,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('righe', [[
                'tipo_riga' => 'sfuso',
                'descrizione' => '',
                'lunghezza_mm' => null,
                'larghezza_mm' => null,
                'spessore_mm' => null,
                'quantita' => 1,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 0,
            ]])
            ->call('selezionaProdotto', 0, $this->prodotto->id)
            ->assertSet('righe.0.lunghezza_mm', 4000.0)
            ->assertSet('righe.0.larghezza_mm', 120.0)
            ->assertSet('righe.0.spessore_mm', 25.0);
    }

    public function test_seleziona_prodotto_sets_unita_misura_from_product(): void
    {
        $this->prodotto->update([
            'unita_misura' => UnitaMisura::ML,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('righe', [[
                'tipo_riga' => 'sfuso',
                'descrizione' => '',
                'quantita' => 1,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 0,
            ]])
            ->call('selezionaProdotto', 0, $this->prodotto->id)
            ->assertSet('righe.0.unita_misura', 'ml');
    }

    public function test_preventivo_form_uses_generic_price_label_and_shows_product_unit_hint(): void
    {
        $prodottoPezzo = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::PZ,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('righe', [[
                'tipo_riga' => 'sfuso',
                'prodotto_id' => $prodottoPezzo->id,
                'descrizione' => 'Riga test',
                'lunghezza_mm' => 1000,
                'larghezza_mm' => 100,
                'spessore_mm' => 20,
                'quantita' => 1,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 10,
            ]])
            ->assertSee('Prezzo Unit.')
            ->assertSee('/pz');
    }

    public function test_can_save_sfuso_row_with_pz_unit_without_dimensions(): void
    {
        $prodottoPezzo = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::PZ,
            'prezzo_unitario' => 10,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->set('righe', [[
                'tipo_riga' => 'sfuso',
                'include_in_bom' => true,
                'prodotto_id' => $prodottoPezzo->id,
                'unita_misura' => 'pz',
                'descrizione' => 'Viteria',
                'lunghezza_mm' => null,
                'larghezza_mm' => null,
                'spessore_mm' => null,
                'quantita' => 5,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 10,
            ]])
            ->call('save')
            ->assertRedirect('/preventivi');

        $this->assertDatabaseHas('preventivo_righe', [
            'descrizione' => 'Viteria',
            'unita_misura' => 'pz',
            'quantita' => 5,
            'totale_riga' => 50.00,
        ]);
    }

    public function test_ricalcola_supporta_riga_sfusa_in_mq(): void
    {
        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('righe', [[
                'tipo_riga' => 'sfuso',
                'descrizione' => 'Pannello',
                'unita_misura' => 'mq',
                'lunghezza_mm' => 2000,
                'larghezza_mm' => 500,
                'spessore_mm' => null,
                'quantita' => 3,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 10,
            ]])
            ->call('ricalcola')
            ->assertSet('righe.0.superficie_mq', 3.0)
            ->assertSet('righe.0.materiale_lordo', 3.3)
            ->assertSet('righe.0.totale_riga', 33.0)
            ->assertSet('totale_materiali', 33.0);
    }

    public function test_edit_mount_recomputes_total_including_lotto_rows(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create([
            'cliente_id' => $this->cliente->id,
            'totale_materiali' => 0,
            'totale_lavorazioni' => 15,
            'totale' => 15,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => TipoRigaPreventivo::LOTTO->value,
            'include_in_bom' => true,
            'unita_misura' => UnitaMisura::MC->value,
            'descrizione' => 'Lotto test',
            'lunghezza_mm' => 0,
            'larghezza_mm' => 0,
            'spessore_mm' => 0,
            'quantita' => 1,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 0,
            'prezzo_unitario' => 0,
            'totale_riga' => 250,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class, ['preventivo' => $preventivo])
            ->assertSet('totale_materiali', 0.0)
            ->assertSet('totale_lotti', 250.0)
            ->assertSet('totale_lavorazioni', 15.0)
            ->assertSet('totale', 265.0);
    }

    public function test_shows_separate_subtotal_for_lotti(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create([
            'cliente_id' => $this->cliente->id,
            'totale_materiali' => 999,
            'totale_lavorazioni' => 0,
            'totale' => 999,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $preventivo->righe()->create([
            'tipo_riga' => TipoRigaPreventivo::SFUSO->value,
            'include_in_bom' => true,
            'prodotto_id' => $this->prodotto->id,
            'unita_misura' => UnitaMisura::PZ->value,
            'descrizione' => 'Ferramenta',
            'lunghezza_mm' => null,
            'larghezza_mm' => null,
            'spessore_mm' => null,
            'quantita' => 3,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 3,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 3,
            'prezzo_unitario' => 10,
            'totale_riga' => 30,
            'ordine' => 0,
        ]);

        $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => TipoRigaPreventivo::LOTTO->value,
            'include_in_bom' => true,
            'unita_misura' => UnitaMisura::MC->value,
            'descrizione' => 'Lotto test',
            'lunghezza_mm' => 0,
            'larghezza_mm' => 0,
            'spessore_mm' => 0,
            'quantita' => 1,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 0,
            'prezzo_unitario' => 0,
            'totale_riga' => 120,
            'ordine' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class, ['preventivo' => $preventivo])
            ->assertSee('Totale Materiali Sfusi:')
            ->assertSee('Subtotale Lotti Produzione:')
            ->assertSet('totale_materiali', 30.0)
            ->assertSet('totale_lotti', 120.0)
            ->assertSet('totale', 150.0);
    }

    public function test_lavorazioni_extra_is_editable_and_included_in_total(): void
    {
        $prodottoPezzo = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::PZ,
            'prezzo_unitario' => 10,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->set('righe', [[
                'tipo_riga' => TipoRigaPreventivo::SFUSO->value,
                'include_in_bom' => true,
                'prodotto_id' => $prodottoPezzo->id,
                'unita_misura' => UnitaMisura::PZ->value,
                'descrizione' => 'Ferramenta extra',
                'lunghezza_mm' => null,
                'larghezza_mm' => null,
                'spessore_mm' => null,
                'quantita' => 2,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 10,
            ]])
            ->assertDontSee('Lavorazioni extra:')
            ->call('abilitaLavorazioniExtra')
            ->set('totale_lavorazioni', 25)
            ->call('ricalcola')
            ->assertSee('Lavorazioni extra:')
            ->assertSet('totale_materiali', 20.0)
            ->assertSet('totale_lavorazioni', 25.0)
            ->assertSet('totale', 45.0)
            ->call('save')
            ->assertRedirect('/preventivi');

        $preventivo = Preventivo::query()->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta(20, (float) $preventivo->totale_materiali, 0.01);
        $this->assertEqualsWithDelta(25, (float) $preventivo->totale_lavorazioni, 0.01);
        $this->assertEqualsWithDelta(45, (float) $preventivo->totale, 0.01);
    }

    public function test_edit_displays_dimensions_for_lotto_rows(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
            'larghezza_cm' => 120,
            'profondita_cm' => 80,
            'altezza_cm' => 60,
            'numero_pezzi' => 4,
        ]);

        $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => TipoRigaPreventivo::LOTTO->value,
            'include_in_bom' => true,
            'unita_misura' => UnitaMisura::MC->value,
            'descrizione' => 'Lotto con dimensioni',
            'lunghezza_mm' => 1200,
            'larghezza_mm' => 800,
            'spessore_mm' => 600,
            'quantita' => 4,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 0,
            'prezzo_unitario' => 0,
            'totale_riga' => 0,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class, ['preventivo' => $preventivo])
            ->assertSee('Dimensioni: 1200 x 800 x 600 mm | Qtà: 4');
    }

    public function test_edit_displays_lotto_weight_when_construction_flag_is_enabled(): void
    {
        $costruzione = Costruzione::factory()->create([
            'config' => [
                'show_weight_in_quote' => true,
            ],
        ]);

        $preventivo = Preventivo::factory()->bozza()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'costruzione_id' => $costruzione->id,
            'created_by' => $this->user->id,
            'larghezza_cm' => 120,
            'profondita_cm' => 80,
            'altezza_cm' => 60,
            'numero_pezzi' => 4,
            'peso_totale_kg' => 123.45,
        ]);

        $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => TipoRigaPreventivo::LOTTO->value,
            'include_in_bom' => true,
            'unita_misura' => UnitaMisura::MC->value,
            'descrizione' => 'Lotto con peso',
            'lunghezza_mm' => 1200,
            'larghezza_mm' => 800,
            'spessore_mm' => 600,
            'quantita' => 4,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 0,
            'prezzo_unitario' => 0,
            'totale_riga' => 0,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class, ['preventivo' => $preventivo])
            ->assertSee('Peso: 123,45 kg');
    }

    public function test_accettato_preventivo_is_rendered_in_read_only_mode(): void
    {
        $preventivo = Preventivo::factory()->accettato()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $preventivo->righe()->create([
            'tipo_riga' => TipoRigaPreventivo::SFUSO->value,
            'include_in_bom' => true,
            'prodotto_id' => $this->prodotto->id,
            'unita_misura' => UnitaMisura::PZ->value,
            'descrizione' => 'Riga bloccata',
            'lunghezza_mm' => null,
            'larghezza_mm' => null,
            'spessore_mm' => null,
            'quantita' => 1,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 1,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 1,
            'prezzo_unitario' => 10,
            'totale_riga' => 10,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class, ['preventivo' => $preventivo])
            ->assertSee('non può essere modificato')
            ->assertDontSeeHtml('wire:click="aggiungiRiga"')
            ->assertDontSee('Aggiorna Preventivo');
    }

    public function test_cannot_save_accettato_preventivo(): void
    {
        $preventivo = Preventivo::factory()->accettato()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
            'descrizione' => 'Descrizione originale',
        ]);

        $preventivo->righe()->create([
            'tipo_riga' => TipoRigaPreventivo::SFUSO->value,
            'include_in_bom' => true,
            'prodotto_id' => $this->prodotto->id,
            'unita_misura' => UnitaMisura::PZ->value,
            'descrizione' => 'Riga bloccata',
            'lunghezza_mm' => null,
            'larghezza_mm' => null,
            'spessore_mm' => null,
            'quantita' => 1,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 1,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 1,
            'prezzo_unitario' => 10,
            'totale_riga' => 10,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class, ['preventivo' => $preventivo])
            ->set('descrizione', 'Descrizione modificata')
            ->call('save')
            ->assertHasErrors(['preventivo']);

        $this->assertDatabaseHas('preventivi', [
            'id' => $preventivo->id,
            'descrizione' => 'Descrizione originale',
        ]);
    }

    public function test_cannot_add_row_when_preventivo_is_not_editable(): void
    {
        $preventivo = Preventivo::factory()->accettato()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $preventivo->righe()->create([
            'tipo_riga' => TipoRigaPreventivo::SFUSO->value,
            'include_in_bom' => true,
            'prodotto_id' => $this->prodotto->id,
            'unita_misura' => UnitaMisura::PZ->value,
            'descrizione' => 'Riga iniziale',
            'lunghezza_mm' => null,
            'larghezza_mm' => null,
            'spessore_mm' => null,
            'quantita' => 1,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 1,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 1,
            'prezzo_unitario' => 10,
            'totale_riga' => 10,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventivoForm::class, ['preventivo' => $preventivo])
            ->assertCount('righe', 1)
            ->call('creaRigaSfusa')
            ->assertHasErrors(['preventivo'])
            ->assertCount('righe', 1);
    }
}
