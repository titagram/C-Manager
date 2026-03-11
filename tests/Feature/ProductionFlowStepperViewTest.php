<?php

namespace Tests\Feature;

use App\Enums\TipoMovimento;
use App\Models\LottoProduzione;
use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Models\Scarto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProductionFlowStepperViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preventivo_show_renders_production_flow_stepper(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->bozza()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('preventivi.show', $preventivo->id))
            ->assertOk()
            ->assertSee('Processo operativo')
            ->assertSee('Preventivo')
            ->assertSee('Ordine')
            ->assertSee('Lotto')
            ->assertSee('BOM')
            ->assertSee('Magazzino');
    }

    public function test_preventivo_edit_route_uses_model_binding_and_renders_stepper(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->bozza()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('preventivi.edit', $preventivo->id))
            ->assertOk()
            ->assertSee('Processo operativo')
            ->assertSee('Preventivo');
    }

    public function test_ordine_show_renders_stepper_with_optional_preventivo_message(): void
    {
        $user = User::factory()->admin()->create();
        $ordine = Ordine::factory()->confermato()->create([
            'preventivo_id' => null,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('ordini.show', $ordine))
            ->assertOk()
            ->assertSee('Processo operativo')
            ->assertSee('Non previsto in questo flusso')
            ->assertSee($ordine->numero);
    }

    public function test_lotto_show_renders_stepper_context_label(): void
    {
        $user = User::factory()->admin()->create();
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'preventivo_id' => Preventivo::factory()->create([
                'created_by' => $user->id,
            ])->id,
        ]);

        $this->actingAs($user)
            ->get(route('lotti.show', $lotto->id))
            ->assertOk()
            ->assertSee('Processo operativo')
            ->assertSee("Lotto {$lotto->codice_lotto}");
    }

    public function test_lotto_show_does_not_render_sibling_order_and_bom_as_completed_for_new_bozza_lotto(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->accettato()->create([
            'created_by' => $user->id,
        ]);

        $ordine = Ordine::factory()->pronto()->create([
            'preventivo_id' => $preventivo->id,
            'created_by' => $user->id,
        ]);

        $lottoOperativo = LottoProduzione::factory()->completato()->create([
            'ordine_id' => $ordine->id,
            'preventivo_id' => $preventivo->id,
            'created_by' => $user->id,
        ]);

        \App\Models\Bom::factory()->generatedFromOrder()->create([
            'ordine_id' => $ordine->id,
            'lotto_produzione_id' => $lottoOperativo->id,
            'source' => 'ordine',
            'created_by' => $user->id,
        ]);

        $lottoNuovo = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'ordine_id' => null,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('lotti.show', $lottoNuovo->id))
            ->assertOk()
            ->assertSee('Da generare da preventivo accettato')
            ->assertSee('Distinta da generare')
            ->assertDontSee($ordine->numero)
            ->assertDontSee('Distinta generata');
    }

    public function test_ordine_show_renders_audit_timeline_with_inventory_movements(): void
    {
        $user = User::factory()->admin()->create();
        $ordine = Ordine::factory()->confermato()->create([
            'created_by' => $user->id,
        ]);
        $lotto = LottoProduzione::factory()->bozza()->create([
            'ordine_id' => $ordine->id,
            'created_by' => $user->id,
        ]);

        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::SCARICO->value,
            'quantita' => 1.2500,
            'lotto_produzione_id' => $lotto->id,
            'causale' => 'Scarico test timeline ordine',
            'created_by' => $user->id,
            'data_movimento' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('ordini.show', $ordine))
            ->assertOk()
            ->assertSee('Timeline audit ordine')
            ->assertSee('Lotto collegato')
            ->assertSee('Movimento magazzino');
    }

    public function test_lotto_show_renders_audit_timeline_with_material_and_scrap_events(): void
    {
        $user = User::factory()->admin()->create();
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'preventivo_id' => Preventivo::factory()->create([
                'created_by' => $user->id,
            ])->id,
        ]);

        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
        ]);

        $lotto->consumiMateriale()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'stato' => \App\Enums\StatoConsumoMateriale::OPZIONATO->value,
            'opzionato_at' => now(),
            'quantita' => 0.5000,
            'note' => 'Opzione test timeline lotto',
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 500,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'volume_mc' => 0.0100,
        ]);

        $this->actingAs($user)
            ->get(route('lotti.show', $lotto->id))
            ->assertOk()
            ->assertSee('Timeline audit lotto')
            ->assertSee('Materiale opzionato')
            ->assertSee('Scarto registrato');
    }

    public function test_lotto_show_timeline_uses_real_operational_timestamps_when_available(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->create([
            'created_by' => $user->id,
        ]);
        $lotto = LottoProduzione::factory()->completato()->create([
            'created_by' => $user->id,
            'preventivo_id' => $preventivo->id,
            'avviato_at' => Carbon::parse('2026-03-05 14:35:00'),
            'completato_at' => Carbon::parse('2026-03-05 16:10:00'),
        ]);

        $this->actingAs($user)
            ->get(route('lotti.show', $lotto->id))
            ->assertOk()
            ->assertSee('05/03/2026 14:35')
            ->assertSee('05/03/2026 16:10');
    }

    public function test_ordine_show_timeline_uses_real_lotto_operational_timestamps_when_available(): void
    {
        $user = User::factory()->admin()->create();
        $ordine = Ordine::factory()->inProduzione()->create([
            'created_by' => $user->id,
        ]);
        LottoProduzione::factory()->inLavorazione()->create([
            'ordine_id' => $ordine->id,
            'created_by' => $user->id,
            'avviato_at' => Carbon::parse('2026-03-05 09:20:00'),
        ]);

        $this->actingAs($user)
            ->get(route('ordini.show', $ordine))
            ->assertOk()
            ->assertSee('05/03/2026 09:20');
    }

    public function test_lotto_timeline_groups_identical_scrap_events(): void
    {
        $user = User::factory()->admin()->create();
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'preventivo_id' => Preventivo::factory()->create([
                'created_by' => $user->id,
            ])->id,
        ]);

        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
        ]);

        $timestamp = Carbon::parse('2026-03-05 10:15:00');

        Scarto::factory()->create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 500,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'volume_mc' => 0.0010,
            'riutilizzabile' => false,
            'riutilizzato' => false,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 500,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'volume_mc' => 0.0012,
            'riutilizzabile' => false,
            'riutilizzato' => false,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $this->actingAs($user)
            ->get(route('lotti.show', $lotto->id))
            ->assertOk()
            ->assertSee('Scarti registrati (2)')
            ->assertSee('Pezzi 2')
            ->assertSee('Volume totale 0,0020 m³');
    }
}
