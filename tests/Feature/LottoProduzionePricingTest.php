<?php

namespace Tests\Feature;

use App\Enums\LottoPricingMode;
use App\Livewire\Forms\LottoProduzioneForm;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LottoProduzionePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcola_prezzo_lotto_con_tariffa_mc_e_override_manuale(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->create([
            'created_by' => $user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'volume_totale_mc' => 1.5,
            'preventivo_id' => $preventivo->id,
        ]);

        $lotto->materialiUsati()->create([
            'descrizione' => 'Materiale pricing base',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 1.500000,
            'costo_materiale' => 100,
            'prezzo_vendita' => 150,
            'ordine' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('pricing_mode', LottoPricingMode::TARIFFA_MC->value)
            ->set('tariffa_mc', 200)
            ->set('prezzo_finale_override', null)
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $lotto->refresh();

        $this->assertSame(LottoPricingMode::TARIFFA_MC, $lotto->pricing_mode);
        $this->assertEqualsWithDelta(200, (float) $lotto->tariffa_mc, 0.01);
        $this->assertEqualsWithDelta(300, (float) $lotto->prezzo_calcolato, 0.01);
        $this->assertEqualsWithDelta(300, (float) $lotto->prezzo_finale, 0.01);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('pricing_mode', LottoPricingMode::TARIFFA_MC->value)
            ->set('tariffa_mc', 200)
            ->set('prezzo_finale_override', 170)
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $this->assertEqualsWithDelta(170, (float) $lotto->fresh()->prezzo_finale, 0.01);
    }

    public function test_tariffa_mc_null_usa_prezzo_materiali_dopo_salvataggio_piano_taglio(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->create([
            'created_by' => $user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'volume_totale_mc' => 1.5,
            'preventivo_id' => $preventivo->id,
            'pricing_mode' => LottoPricingMode::TARIFFA_MC,
            'tariffa_mc' => null,
        ]);

        $lotto->materialiUsati()->create([
            'descrizione' => 'Materiale con fallback automatico',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 1.500000,
            'costo_materiale' => 100,
            'prezzo_vendita' => 150,
            'ordine' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSee('Prezzo derivato automaticamente dal listino materiali')
            ->assertSet('prezzo_calcolato', 150.0)
            ->assertSet('prezzo_finale', 150.0)
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $lotto->refresh();

        $this->assertEqualsWithDelta(150, (float) $lotto->prezzo_calcolato, 0.01);
        $this->assertEqualsWithDelta(150, (float) $lotto->prezzo_finale, 0.01);
        $this->assertSame('fallback_materiali_listino', data_get($lotto->pricing_snapshot, 'pricing_source'));
    }

    public function test_saved_cutting_plan_shows_material_cost_as_not_available_when_cost_listino_is_missing(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->create([
            'created_by' => $user->id,
        ]);

        $materiale = Prodotto::factory()->create([
            'unita_misura' => \App\Enums\UnitaMisura::MC,
            'prezzo_unitario' => 545,
            'prezzo_mc' => 545,
            'costo_unitario' => null,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'preventivo_id' => $preventivo->id,
            'pricing_mode' => LottoPricingMode::TARIFFA_MC,
            'tariffa_mc' => null,
            'volume_totale_mc' => 0.1155,
            'prezzo_calcolato' => 62.92,
            'prezzo_finale' => 62.92,
        ]);

        $lotto->materialiUsati()->create([
            'prodotto_id' => $materiale->id,
            'descrizione' => 'Asse senza costo listino',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.115500,
            'costo_materiale' => 0,
            'prezzo_vendita' => 62.92,
            'ordine' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSee('Costo Materiali')
            ->assertSee('N/D')
            ->assertSee('Costo materiali non disponibile');
    }

    public function test_saved_cutting_plan_keeps_material_cost_visible_when_cost_listino_exists(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->create([
            'created_by' => $user->id,
        ]);

        $materiale = Prodotto::factory()->create([
            'unita_misura' => \App\Enums\UnitaMisura::MC,
            'prezzo_unitario' => 545,
            'prezzo_mc' => 545,
            'costo_unitario' => 320,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'preventivo_id' => $preventivo->id,
            'pricing_mode' => LottoPricingMode::TARIFFA_MC,
            'tariffa_mc' => null,
            'volume_totale_mc' => 0.1155,
            'prezzo_calcolato' => 62.92,
            'prezzo_finale' => 62.92,
        ]);

        $lotto->materialiUsati()->create([
            'prodotto_id' => $materiale->id,
            'descrizione' => 'Asse con costo listino',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.115500,
            'costo_materiale' => 36.96,
            'prezzo_vendita' => 62.92,
            'ordine' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSee('Costo Materiali')
            ->assertSee('€ 36.96')
            ->assertDontSee('Costo materiali non disponibile');
    }

    public function test_calcola_prezzo_lotto_con_ricarico(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->create([
            'created_by' => $user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'preventivo_id' => $preventivo->id,
        ]);

        $lotto->materialiUsati()->create([
            'descrizione' => 'Asse costo',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.060000,
            'costo_materiale' => 100,
            'prezzo_vendita' => 150,
            'ordine' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('pricing_mode', LottoPricingMode::COSTO_RICARICO->value)
            ->set('ricarico_percentuale', 25)
            ->set('prezzo_finale_override', null)
            ->call('save');

        $lotto->refresh();

        $this->assertSame(LottoPricingMode::COSTO_RICARICO, $lotto->pricing_mode);
        $this->assertNull($lotto->tariffa_mc);
        $this->assertEqualsWithDelta(125, (float) $lotto->prezzo_calcolato, 0.01);
        $this->assertEqualsWithDelta(125, (float) $lotto->prezzo_finale, 0.01);
    }

    public function test_persist_prezzo_su_nuovo_lotto_con_componenti_manuali_in_modalita_ricarico(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->create([
            'created_by' => $user->id,
        ]);

        $costruzione = Costruzione::factory()->create();
        $componenteManuale = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
        ]);

        $materiale = Prodotto::factory()->create([
            'costo_unitario' => 10,
            'prezzo_unitario' => 20,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Nuovo lotto con manuali')
            ->set('preventivoId', $preventivo->id)
            ->set('costruzione_id', $costruzione->id)
            ->set('pricing_mode', LottoPricingMode::COSTO_RICARICO->value)
            ->set('ricarico_percentuale', 50)
            ->set('componentiManuali.0.componente_costruzione_id', $componenteManuale->id)
            ->set('componentiManuali.0.prodotto_id', $materiale->id)
            ->set('componentiManuali.0.quantita', 3)
            ->set('componentiManuali.0.unita_misura', 'pz')
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $lotto = LottoProduzione::query()->latest('id')->first();
        $this->assertNotNull($lotto);
        $this->assertSame(LottoPricingMode::COSTO_RICARICO, $lotto->pricing_mode);
        $this->assertEqualsWithDelta(105, (float) $lotto->prezzo_calcolato, 0.01);
        $this->assertEqualsWithDelta(105, (float) $lotto->prezzo_finale, 0.01);
    }

    public function test_prezzo_unitario_custom_dei_componenti_manuali_sovrascrive_il_listino_materiale(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->create([
            'created_by' => $user->id,
        ]);

        $costruzione = Costruzione::factory()->create();
        $componenteManuale = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
        ]);

        $materiale = Prodotto::factory()->create([
            'costo_unitario' => 10,
            'prezzo_unitario' => 99,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto con prezzo componente custom')
            ->set('preventivoId', $preventivo->id)
            ->set('costruzione_id', $costruzione->id)
            ->set('pricing_mode', LottoPricingMode::TARIFFA_MC->value)
            ->set('tariffa_mc', 0)
            ->set('componentiManuali.0.componente_costruzione_id', $componenteManuale->id)
            ->set('componentiManuali.0.prodotto_id', $materiale->id)
            ->set('componentiManuali.0.quantita', 2)
            ->set('componentiManuali.0.prezzo_unitario', 15)
            ->set('componentiManuali.0.unita_misura', 'pz')
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $lotto = LottoProduzione::query()->latest('id')->first();
        $this->assertNotNull($lotto);
        $this->assertEqualsWithDelta(30, (float) $lotto->prezzo_calcolato, 0.01);
        $this->assertEqualsWithDelta(30, (float) $lotto->prezzo_finale, 0.01);
    }

    public function test_componenti_manuali_mostrano_totale_riga_e_sorgente_prezzo(): void
    {
        $user = User::factory()->admin()->create();

        $costruzione = Costruzione::factory()->create();
        $componenteManuale = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
        ]);

        $materiale = Prodotto::factory()->create([
            'prezzo_unitario' => 12.5,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto test UX manuali')
            ->set('costruzione_id', $costruzione->id)
            ->set('componentiManuali.0.componente_costruzione_id', $componenteManuale->id)
            ->set('componentiManuali.0.prodotto_id', $materiale->id)
            ->set('componentiManuali.0.quantita', 4)
            ->set('componentiManuali.0.prezzo_unitario', null)
            ->assertSee('50.00')
            ->assertSee('listino')
            ->set('componentiManuali.0.prezzo_unitario', 15)
            ->assertSee('60.00')
            ->assertSee('manuale');
    }

    public function test_componenti_manuali_aggiornano_i_totali_lotto_in_edit_prima_del_salvataggio(): void
    {
        $user = User::factory()->admin()->create();

        $costruzione = Costruzione::factory()->create();
        $componenteManuale = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
        ]);

        $materiale = Prodotto::factory()->create([
            'prezzo_unitario' => 12,
            'costo_unitario' => 5,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'created_by' => $user->id,
            'costruzione_id' => $costruzione->id,
            'pricing_mode' => LottoPricingMode::TARIFFA_MC,
            'tariffa_mc' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('componentiManuali.0.componente_costruzione_id', $componenteManuale->id)
            ->set('componentiManuali.0.prodotto_id', $materiale->id)
            ->set('componentiManuali.0.quantita', 2)
            ->set('componentiManuali.0.prezzo_unitario', 15)
            ->assertSet('totale_componenti_manuali_prezzo', 30.0)
            ->assertSet('prezzo_calcolato', 30.0)
            ->assertSet('prezzo_finale', 30.0);
    }
}
