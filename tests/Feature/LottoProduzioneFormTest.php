<?php

namespace Tests\Feature;

use App\Enums\LottoPricingMode;
use App\Enums\TipoMovimento;
use App\Livewire\Forms\LottoProduzioneForm;
use App\Models\Cliente;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Models\Scarto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class LottoProduzioneFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->admin()->create();
        $this->cliente = Cliente::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_save_lotto_with_dimensions(): void
    {
        Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Test Product')
            ->set('preventivoId', Preventivo::factory()->create()->id)
            ->set('larghezza_cm', '80')
            ->set('profondita_cm', '80')
            ->set('altezza_cm', '120')
            ->set('tipo_prodotto', 'CASSA SP 25')
            ->set('spessore_base_mm', '25')
            ->set('numero_pezzi', '10')
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $this->assertDatabaseHas('lotti_produzione', [
            'prodotto_finale' => 'Test Product',
            'larghezza_cm' => 80,
            'profondita_cm' => 80,
            'altezza_cm' => 120,
            'tipo_prodotto' => 'CASSA SP 25',
            'spessore_base_mm' => 25,
            'numero_pezzi' => 10,
        ]);
    }

    /** @test */
    public function it_requires_single_origin_association_for_manual_lotto(): void
    {
        Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto senza origine')
            ->call('save')
            ->assertHasErrors(['associazione']);
    }

    /** @test */
    public function it_rejects_incoherent_double_origin_association_preventivo_and_ordine(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
        ]);
        $ordine = \App\Models\Ordine::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto doppia origine')
            ->set('preventivoId', $preventivo->id)
            ->set('ordineId', $ordine->id)
            ->call('save')
            ->assertHasErrors(['associazione']);
    }

    /** @test */
    public function it_allows_consistent_preventivo_and_ordine_traceability_when_order_comes_from_same_preventivo(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
        ]);
        $ordine = \App\Models\Ordine::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto con doppio riferimento coerente')
            ->set('preventivoId', $preventivo->id)
            ->set('ordineId', $ordine->id)
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $this->assertDatabaseHas('lotti_produzione', [
            'prodotto_finale' => 'Lotto con doppio riferimento coerente',
            'preventivo_id' => $preventivo->id,
            'ordine_id' => $ordine->id,
        ]);
    }

    /** @test */
    public function it_can_save_manual_lotto_associated_only_to_ordine(): void
    {
        $ordine = \App\Models\Ordine::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto ordine manuale')
            ->set('ordineId', $ordine->id)
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $this->assertDatabaseHas('lotti_produzione', [
            'prodotto_finale' => 'Lotto ordine manuale',
            'ordine_id' => $ordine->id,
            'preventivo_id' => null,
            'cliente_id' => $this->cliente->id,
        ]);
    }

    /** @test */
    public function it_can_save_placeholder_bozza_without_technical_definition(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Placeholder tecnico')
            ->set('preventivoId', $preventivo->id)
            ->set('stato', 'bozza')
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $this->assertDatabaseHas('lotti_produzione', [
            'prodotto_finale' => 'Placeholder tecnico',
            'preventivo_id' => $preventivo->id,
            'stato' => 'bozza',
            'costruzione_id' => null,
        ]);
    }

    /** @test */
    public function it_rejects_non_bozza_lotto_without_technical_definition(): void
    {
        $ordine = \App\Models\Ordine::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto incompleto')
            ->set('ordineId', $ordine->id)
            ->set('stato', 'confermato')
            ->call('save')
            ->assertHasErrors(['costruzione_id']);
    }

    /** @test */
    public function it_rejects_non_bozza_lotto_linked_only_to_preventivo(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);
        $costruzione = Costruzione::factory()->create();

        Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto da confermare su preventivo')
            ->set('preventivoId', $preventivo->id)
            ->set('costruzione_id', $costruzione->id)
            ->set('stato', 'confermato')
            ->call('save')
            ->assertHasErrors(['stato']);
    }

    /** @test */
    public function it_can_update_an_existing_standalone_bozza_lotto_without_origin_links(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $this->user->id,
            'preventivo_id' => null,
            'ordine_id' => null,
            'cliente_id' => null,
            'prodotto_finale' => 'Lotto standalone originale',
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('prodotto_finale', 'Lotto standalone aggiornato')
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $this->assertDatabaseHas('lotti_produzione', [
            'id' => $lotto->id,
            'prodotto_finale' => 'Lotto standalone aggiornato',
            'preventivo_id' => null,
            'ordine_id' => null,
        ]);
    }

    /** @test */
    public function completed_lotto_is_rendered_read_only_and_cannot_be_saved(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'created_by' => $this->user->id,
            'stato' => \App\Enums\StatoLottoProduzione::COMPLETATO,
            'prodotto_finale' => 'Lotto completato non modificabile',
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSee('non può essere modificato')
            ->assertDontSee('Aggiorna Lotto')
            ->set('prodotto_finale', 'Tentativo modifica')
            ->call('save')
            ->assertHasErrors(['lotto']);

        $this->assertDatabaseHas('lotti_produzione', [
            'id' => $lotto->id,
            'prodotto_finale' => 'Lotto completato non modificabile',
        ]);
    }

    /** @test */
    public function read_only_lotto_keeps_visible_preventivo_and_final_order_references(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);
        $ordine = \App\Models\Ordine::factory()->pronto()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->completato()->create([
            'created_by' => $this->user->id,
            'preventivo_id' => $preventivo->id,
            'ordine_id' => $ordine->id,
            'prodotto_finale' => 'Lotto readonly con riferimenti storici',
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSet('preventivoId', $preventivo->id)
            ->assertSet('ordineId', $ordine->id)
            ->assertSee($preventivo->numero)
            ->assertSee($ordine->numero)
            ->assertSee($ordine->stato->label());
    }

    /** @test */
    public function it_calculates_materials_with_canonical_formula_variables(): void
    {
        $costruzione = Costruzione::create([
            // Use a non-migrated category to keep this test focused on formula variables,
            // not on category-specific strip optimization.
            'categoria' => 'formula-test',
            'nome' => 'Test Canonical Formula',
            'slug' => 'test-canonical-formula',
            'descrizione' => 'Test',
            'config' => [],
            'richiede_lunghezza' => true,
            'richiede_larghezza' => true,
            'richiede_altezza' => true,
            'is_active' => true,
        ]);

        ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete',
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
            'formula_lunghezza' => 'W - (2 * T)',
            'formula_larghezza' => 'H',
            'formula_quantita' => 'ceil(L / 130)',
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
        ]);

        $component = Livewire::test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '100') // L = 1000
            ->set('profondita_cm', '80') // W = 800
            ->set('altezza_cm', '60') // H = 600
            ->set('numero_pezzi', '2')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true);

        $result = $component->get('optimizerResult');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('bins', $result);
        $this->assertSame('v2', data_get($result, 'version'));
        $this->assertSame('legacy-bin-packing', data_get($result, 'optimizer.name'));
        $this->assertNotEmpty(data_get($result, 'trace.audit.logical_timestamp'));
        $this->assertSame('legacy-bin-packing', data_get($result, 'trace.audit.algorithm.name'));

        $totalPackedPieces = collect($result['bins'])->sum(
            fn (array $bin) => count($bin['items'])
        );
        $this->assertSame(16, $totalPackedPieces); // ceil(1000/130)=8 per pezzo * 2

        $lengths = collect($result['bins'])
            ->flatMap(fn (array $bin) => collect($bin['items'])->pluck('length'))
            ->unique()
            ->values();
        $this->assertCount(1, $lengths);
        $this->assertEquals(760.0, (float) $lengths->first()); // 800 - (2*20)
    }

    /** @test */
    public function it_calculates_materials_with_legacy_formula_aliases(): void
    {
        $costruzione = Costruzione::create([
            // Use a non-migrated category to keep this test focused on legacy formula aliases,
            // not on category-specific strip optimization.
            'categoria' => 'formula-test',
            'nome' => 'Test Legacy Formula',
            'slug' => 'test-legacy-formula',
            'descrizione' => 'Test',
            'config' => [],
            'richiede_lunghezza' => true,
            'richiede_larghezza' => true,
            'richiede_altezza' => true,
            'is_active' => true,
        ]);

        ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Pannello',
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
            'formula_lunghezza' => 'P - (2 * S)',
            'formula_larghezza' => 'A',
            'formula_quantita' => 'ceil($L / 250)',
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 25,
        ]);

        $component = Livewire::test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '100') // L = 1000
            ->set('profondita_cm', '90') // P alias -> W = 900
            ->set('altezza_cm', '70') // A alias -> H = 700
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true);

        $result = $component->get('optimizerResult');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('bins', $result);
        $this->assertSame('v2', data_get($result, 'version'));
        $this->assertSame('legacy-bin-packing', data_get($result, 'optimizer.name'));
        $this->assertNotEmpty(data_get($result, 'trace.audit.logical_timestamp'));
        $this->assertSame('legacy-bin-packing', data_get($result, 'trace.audit.algorithm.name'));

        $totalPackedPieces = collect($result['bins'])->sum(
            fn (array $bin) => count($bin['items'])
        );
        $this->assertSame(4, $totalPackedPieces); // ceil(1000/250)=4

        $lengths = collect($result['bins'])
            ->flatMap(fn (array $bin) => collect($bin['items'])->pluck('length'))
            ->unique()
            ->values();
        $this->assertCount(1, $lengths);
        $this->assertEquals(850.0, (float) $lengths->first()); // 900 - (2*25)
    }

    /** @test */
    public function it_shows_warning_when_primary_material_stock_is_insufficient_during_optimization(): void
    {
        Config::set('production.material_calculation_cooldown_seconds', 0);

        $costruzione = Costruzione::create([
            'categoria' => 'formula-test',
            'nome' => 'Test disponibilita materiale',
            'slug' => 'test-disponibilita-materiale',
            'descrizione' => 'Test',
            'config' => [],
            'richiede_lunghezza' => true,
            'richiede_larghezza' => true,
            'richiede_altezza' => true,
            'is_active' => true,
        ]);

        ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete',
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
            'formula_lunghezza' => 'W - (2 * T)',
            'formula_larghezza' => 'H',
            'formula_quantita' => 'ceil(L / 130)',
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'unita_misura' => 'mc',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $materiale->id,
            'quantita_iniziale' => 0.001,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 0.001,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        $component = Livewire::test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '100')
            ->set('profondita_cm', '80')
            ->set('altezza_cm', '60')
            ->set('numero_pezzi', '2')
            ->call('calcolaMateriali');

        $result = $component->get('optimizerResult');
        $this->assertIsArray($result);
        $this->assertFalse((bool) data_get($result, 'trace.stock_check.enough', true));
        $this->assertGreaterThan(
            (float) data_get($result, 'trace.stock_check.available_qty', 0),
            (float) data_get($result, 'trace.stock_check.required_qty', 0)
        );
    }

    /** @test */
    public function it_validates_larghezza_formula_during_material_calculation(): void
    {
        $costruzione = Costruzione::create([
            'categoria' => 'cassa',
            'nome' => 'Test Invalid Width Formula',
            'slug' => 'test-invalid-width-formula',
            'descrizione' => 'Test',
            'config' => [],
            'richiede_lunghezza' => true,
            'richiede_larghezza' => true,
            'richiede_altezza' => true,
            'is_active' => true,
        ]);

        ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Pannello',
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
            'formula_lunghezza' => 'W - (2 * T)',
            'formula_larghezza' => 'UNKNOWN + 10',
            'formula_quantita' => '2',
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '100')
            ->set('profondita_cm', '80')
            ->set('altezza_cm', '60')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', false)
            ->assertSet('optimizerResult', null)
            ->assertSee('Nessun componente calcolato trovato o formule non valide.')
            ->assertSee('Variabile sconosciuta');
    }

    /** @test */
    public function it_validates_required_dimensions_during_material_calculation(): void
    {
        $costruzione = Costruzione::create([
            'categoria' => 'cassa',
            'nome' => 'Test Required Dimensions',
            'slug' => 'test-required-dimensions',
            'descrizione' => 'Test',
            'config' => [],
            'richiede_lunghezza' => true,
            'richiede_larghezza' => true,
            'richiede_altezza' => true,
            'is_active' => true,
        ]);

        ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Pannello',
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '1',
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '0')
            ->set('profondita_cm', '-10')
            ->set('altezza_cm', '100')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertHasErrors(['larghezza_cm', 'profondita_cm']);
    }

    /** @test */
    public function it_rate_limits_repeated_material_calculation_requests(): void
    {
        $previousCooldown = config('production.material_calculation_cooldown_seconds', 0);
        Config::set('production.material_calculation_cooldown_seconds', 5);

        try {
            $costruzione = Costruzione::create([
                'categoria' => 'formula-test',
                'nome' => 'Test Cooldown',
                'slug' => 'test-cooldown',
                'descrizione' => 'Test',
                'config' => [],
                'richiede_lunghezza' => true,
                'richiede_larghezza' => true,
                'richiede_altezza' => true,
                'is_active' => true,
            ]);

            ComponenteCostruzione::create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Pannello',
                'calcolato' => true,
                'tipo_dimensionamento' => 'CALCOLATO',
                'formula_lunghezza' => 'L',
                'formula_larghezza' => 'H',
                'formula_quantita' => '1',
            ]);

            $materiale = Prodotto::factory()->legname()->create([
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $component = Livewire::test(LottoProduzioneForm::class)
                ->set('costruzione_id', $costruzione->id)
                ->set('materiale_id', $materiale->id)
                ->set('larghezza_cm', '100')
                ->set('profondita_cm', '50')
                ->set('altezza_cm', '100')
                ->set('numero_pezzi', '1')
                ->call('calcolaMateriali')
                ->assertSet('showOptimizerResults', true);

            $firstResult = $component->get('optimizerResult');
            $this->assertIsArray($firstResult);

            $component
                ->call('calcolaMateriali')
                ->assertSet('showOptimizerResults', true)
                ->assertSee('Calcolo materiali troppo ravvicinato');

            $this->assertSame($firstResult['total_bins'] ?? null, data_get($component->get('optimizerResult'), 'total_bins'));
        } finally {
            Config::set('production.material_calculation_cooldown_seconds', $previousCooldown);
        }
    }

    /** @test */
    public function it_automatically_reuses_compatible_scraps_when_checkbox_is_enabled(): void
    {
        $costruzione = Costruzione::create([
            'categoria' => 'formula-test',
            'nome' => 'Test Scarti Compatibili',
            'slug' => 'test-scarti-compatibili',
            'descrizione' => 'Test',
            'config' => [],
            'richiede_lunghezza' => true,
            'richiede_larghezza' => true,
            'richiede_altezza' => true,
            'is_active' => true,
        ]);

        ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Pannello',
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '1',
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $materiale->id,
        ]);

        Scarto::factory()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 1000,
            'spessore_mm' => 20,
            'volume_mc' => 0.02,
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        $component = Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto con riuso scarti')
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '100')
            ->set('profondita_cm', '90')
            ->set('altezza_cm', '100')
            ->set('numero_pezzi', '2')
            ->set('controllaScarti', true)
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true)
            ->assertSee('Scarti compatibili rilevati')
            ->assertSee('Riutilizzo applicato al calcolo');

        $preview = $component->get('scartiCompatibiliPreview');
        $this->assertIsArray($preview);
        $this->assertGreaterThanOrEqual(1, (int) ($preview['matched_count'] ?? 0));
        $this->assertGreaterThanOrEqual(
            (int) ($preview['matched_count'] ?? 0),
            (int) ($preview['required_count'] ?? 0)
        );
        $this->assertTrue((bool) ($preview['used'] ?? false));

        $optimizerResult = $component->get('optimizerResult');
        $this->assertTrue((bool) data_get($optimizerResult, 'trace.scrap_reuse.used'));
        $this->assertSame(
            (int) ($preview['matched_count'] ?? 0),
            (int) data_get($optimizerResult, 'trace.scrap_reuse.matched_count')
        );
        $this->assertSame(
            (int) ($preview['required_count'] ?? 0),
            (int) data_get($optimizerResult, 'trace.scrap_reuse.required_count')
        );
        $this->assertNotEmpty(data_get($optimizerResult, 'trace.scrap_reuse.matches', []));
        $this->assertGreaterThanOrEqual(
            (float) data_get($optimizerResult, 'totali.volume_netto_mc', 0),
            (float) data_get($optimizerResult, 'totali.volume_lordo_mc', 0)
        );
        $this->assertEqualsWithDelta(
            (float) data_get($optimizerResult, 'totali.volume_lordo_mc', 0) - (float) data_get($optimizerResult, 'totali.volume_netto_mc', 0),
            (float) data_get($optimizerResult, 'totali.volume_scarto_mc', 0),
            0.000001
        );
    }

    /** @test */
    public function it_marks_selected_scraps_as_reused_when_lotto_is_saved(): void
    {
        $costruzione = Costruzione::create([
            'categoria' => 'formula-test',
            'nome' => 'Test Save Scrap Reuse',
            'slug' => 'test-save-scrap-reuse',
            'descrizione' => 'Test',
            'config' => [],
            'richiede_lunghezza' => true,
            'richiede_larghezza' => true,
            'richiede_altezza' => true,
            'is_active' => true,
        ]);

        ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Pannello',
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '1',
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $materiale->id,
        ]);

        $scarto = Scarto::factory()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 1000,
            'spessore_mm' => 20,
            'volume_mc' => 0.02,
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);
        $preventivo = Preventivo::factory()->create([
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto salva riuso scarti')
            ->set('preventivoId', $preventivo->id)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '100')
            ->set('profondita_cm', '90')
            ->set('altezza_cm', '100')
            ->set('numero_pezzi', '1')
            ->set('controllaScarti', true)
            ->call('calcolaMateriali')
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $lotto = LottoProduzione::query()
            ->where('prodotto_finale', 'Lotto salva riuso scarti')
            ->firstOrFail();

        $scarto->refresh();
        $this->assertTrue((bool) $scarto->riutilizzato);
        $this->assertSame($lotto->id, $scarto->riutilizzato_in_lotto_id);
    }

    /** @test */
    public function it_displays_reused_scraps_in_edit_mode(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $prodotto = Prodotto::factory()->legname()->create([
            'nome' => 'Abete riuso edit',
            'peso_specifico_kg_mc' => 360,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LOT-SCARTO-EDIT',
        ]);

        $lottoOrigine = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-ORIGINE-SCARTO',
            'created_by' => $this->user->id,
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lottoOrigine->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1200,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
            'volume_mc' => 0.006,
            'riutilizzabile' => true,
            'riutilizzato' => true,
            'riutilizzato_in_lotto_id' => $lotto->id,
            'note' => 'Scarto riusato in test',
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSee('Scarti riutilizzati in questo lotto')
            ->assertSee('LOT-SCARTO-EDIT')
            ->assertSee('LP-ORIGINE-SCARTO')
            ->assertSee('Scarto riusato in test');
    }

    /** @test */
    public function it_displays_dimension_derived_volume_and_weight_for_reused_scraps_in_edit_mode(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $prodotto = Prodotto::factory()->legname()->create([
            'nome' => 'Abete volume corretto',
            'peso_specifico_kg_mc' => 360,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LOT-SCARTO-VOLUME',
        ]);

        $lottoOrigine = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-ORIGINE-VOLUME',
            'created_by' => $this->user->id,
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lottoOrigine->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => 0.026250,
            'riutilizzabile' => true,
            'riutilizzato' => true,
            'riutilizzato_in_lotto_id' => $lotto->id,
            'note' => 'Scarto volume incoerente da correggere in view',
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSee('Scarti riutilizzati in questo lotto')
            ->assertSee('LOT-SCARTO-VOLUME')
            ->assertSee('LP-ORIGINE-VOLUME')
            ->assertSee('0,0026 m³')
            ->assertSee('0,945 kg')
            ->assertDontSee('0,0263 m³')
            ->assertDontSee('9,450 kg');
    }

    /** @test */
    public function it_reuses_compatible_scraps_for_cassa_optimizer_after_strip_expansion(): void
    {
        Config::set('production.cutting_kerf_mm', 3);

        $costruzione = Costruzione::create([
            'categoria' => 'cassa',
            'nome' => 'Cassa test scarti strip',
            'slug' => 'cassa-test-scarti-strip',
            'descrizione' => 'Test',
            'config' => ['ha_coperchio' => false],
            'richiede_lunghezza' => true,
            'richiede_larghezza' => true,
            'richiede_altezza' => true,
            'is_active' => true,
        ]);

        ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Pannello laterale',
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '1',
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'nome' => 'Abete 35x75 test scarti',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $materiale->id,
            'quantita_iniziale' => 10,
        ]);

        Scarto::factory()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => round((1000 * 75 * 35) / 1000000000, 6),
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        $component = Livewire::test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto cassa con riuso scarti')
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '80')
            ->set('profondita_cm', '80')
            ->set('altezza_cm', '80')
            ->set('numero_pezzi', '1')
            ->set('controllaScarti', true)
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true)
            ->assertSee('Scarti compatibili rilevati')
            ->assertSee('Riutilizzo applicato al calcolo');

        $preview = $component->get('scartiCompatibiliPreview');
        $this->assertIsArray($preview);
        $this->assertSame(1, (int) ($preview['available_scraps_count'] ?? 0));
        $this->assertGreaterThanOrEqual(1, (int) ($preview['matched_count'] ?? 0));
        $this->assertTrue((bool) ($preview['used'] ?? false));
        $this->assertEqualsWithDelta(197.0, (float) data_get($preview, 'matches.0.remaining_length_mm', 0), 0.001);

        $optimizerResult = $component->get('optimizerResult');
        $this->assertTrue((bool) data_get($optimizerResult, 'trace.scrap_reuse.used'));
        $this->assertGreaterThanOrEqual(1, (int) data_get($optimizerResult, 'trace.scrap_reuse.matched_count', 0));
        $this->assertEqualsWithDelta(197.0, (float) data_get($optimizerResult, 'trace.scrap_reuse.source_summaries.0.remaining_length_mm', 0), 0.001);
    }

    /** @test */
    public function material_dropdown_shows_only_materials_with_positive_stock(): void
    {
        $costruzione = Costruzione::factory()->create([
            'is_active' => true,
        ]);

        $materialeDisponibile = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Materiale disponibile',
        ]);
        $materialeEsaurito = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Materiale esaurito',
        ]);

        LottoMateriale::factory()->create([
            'prodotto_id' => $materialeDisponibile->id,
            'quantita_iniziale' => 3,
        ]);
        LottoMateriale::factory()->create([
            'prodotto_id' => $materialeEsaurito->id,
            'quantita_iniziale' => 0,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->assertSee('Materiale disponibile')
            ->assertDontSee('Materiale esaurito');
    }

    /** @test */
    public function material_dropdown_keeps_current_selected_material_visible_in_edit_mode_even_if_stock_is_zero(): void
    {
        $costruzione = Costruzione::factory()->create([
            'is_active' => true,
        ]);

        $materialeDisponibile = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Materiale disponibile edit',
        ]);
        $materialeEsaurito = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Materiale esaurito edit',
        ]);

        LottoMateriale::factory()->create([
            'prodotto_id' => $materialeDisponibile->id,
            'quantita_iniziale' => 2,
        ]);
        LottoMateriale::factory()->create([
            'prodotto_id' => $materialeEsaurito->id,
            'quantita_iniziale' => 0,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $this->user->id,
            'costruzione_id' => $costruzione->id,
            'optimizer_result' => [
                'version' => 'v2',
                'materiale' => [
                    'id' => $materialeEsaurito->id,
                    'nome' => $materialeEsaurito->nome,
                ],
                'bins' => [],
                'totali' => [
                    'costo_totale' => 0,
                    'prezzo_totale' => 0,
                    'volume_totale_mc' => 0,
                ],
            ],
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSet('materiale_id', $materialeEsaurito->id)
            ->assertSee('Materiale disponibile edit')
            ->assertSee('Materiale esaurito edit');
    }

    // TODO: Update this test to use new costruzione_id + materiale_id API
    // /** @test */
    // public function it_can_calculate_materials_using_optimizer(): void
    // {
    //     $component = Livewire::test(LottoProduzioneForm::class)
    //         ->set('prodotto_finale', 'Test Product')
    //         ->set('numero_pezzi', '100')
    //         ->set('lunghezza_asse_mm', '4000')
    //         ->set('lunghezza_pezzo_mm', '800')
    //         ->set('larghezza_lama_mm', '5')
    //         ->call('calcolaMateriali');

    //     $component->assertSet('showOptimizerResults', true);
    //     $this->assertNotNull($component->get('optimizerResult'));
    //     $this->assertArrayHasKey('pezzi_per_asse', $component->get('optimizerResult'));
    //     $this->assertArrayHasKey('assi_necessarie', $component->get('optimizerResult'));
    // }

    // TODO: Update this test to use new costruzione_id + materiale_id API
    // /** @test */
    // public function it_can_save_calculated_materials_to_lotto(): void
    // {
    //     // First create a lotto
    //     $lotto = LottoProduzione::factory()->create([
    //         'created_by' => $this->user->id,
    //     ]);

    //     // Then test saving materials
    //     Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
    //         ->set('numero_pezzi', '100')
    //         ->set('lunghezza_asse_mm', '4000')
    //         ->set('lunghezza_pezzo_mm', '800')
    //         ->set('larghezza_lama_mm', '5')
    //         ->set('larghezza_cm', '10')
    //         ->set('spessore_base_mm', '25')
    //         ->set('tipo_prodotto', 'CASSA SP 25')
    //         ->call('calcolaMateriali')
    //         ->call('salvaMateriali');

    //     $this->assertDatabaseHas('lotto_produzione_materiali', [
    //         'lotto_produzione_id' => $lotto->id,
    //         'lunghezza_mm' => 800,
    //         'quantita_pezzi' => 100,
    //     ]);

    //     // Verify optimizer results were saved
    //     $materiale = $lotto->materialiUsati()->first();
    //     $this->assertNotNull($materiale->pezzi_per_asse);
    //     $this->assertNotNull($materiale->assi_necessarie);
    //     $this->assertNotNull($materiale->scarto_percentuale);
    // }

    // TODO: Update this test to use new costruzione_id + materiale_id API
    // /** @test */
    // public function it_validates_optimizer_inputs(): void
    // {
    //     Livewire::test(LottoProduzioneForm::class)
    //         ->set('prodotto_finale', 'Test Product')
    //         ->set('lunghezza_asse_mm', '')
    //         ->set('lunghezza_pezzo_mm', '')
    //         ->set('numero_pezzi', '')
    //         ->call('calcolaMateriali')
    //         ->assertHasErrors([
    //             'lunghezza_asse_mm',
    //             'lunghezza_pezzo_mm',
    //             'numero_pezzi',
    //         ]);
    // }

    /** @test */
    public function it_prevents_saving_materials_without_calculation(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->call('salvaMateriali');

        // Should not create any materials
        $this->assertDatabaseCount('lotto_produzione_materiali', 0);
    }

    /** @test */
    public function test_lotto_form_does_not_show_cliente_dropdown(): void
    {
        Livewire::test(LottoProduzioneForm::class)
            ->assertDontSee('Cliente')
            ->assertDontSee('cliente_id');
    }

    /** @test */
    public function test_lotto_cliente_is_derived_from_preventivo(): void
    {
        $cliente = Cliente::factory()->create();
        $preventivo = \App\Models\Preventivo::factory()->create(['cliente_id' => $cliente->id]);
        $lotto = LottoProduzione::factory()->create(['preventivo_id' => $preventivo->id, 'cliente_id' => null]);

        $this->assertEquals($cliente->id, $lotto->preventivo->cliente_id);
        $this->assertNull($lotto->cliente_id);
    }

    /** @test */
    public function test_lotto_form_does_not_show_bom_template_section(): void
    {
        Livewire::test(LottoProduzioneForm::class)
            ->assertDontSee('Template Distinta Base')
            ->assertDontSee('bom_template_id');
    }

    public function test_ricalcola_totali_uses_weighted_scrap_percentage(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'descrizione' => 'Asse A',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.020000,
            'assi_necessarie' => 1,
            'scarto_totale_mm' => 100,
            'scarto_percentuale' => 10,
            'costo_materiale' => 10,
            'prezzo_vendita' => 20,
            'ordine' => 1,
        ]);

        $lotto->materialiUsati()->create([
            'descrizione' => 'Asse B',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 4,
            'volume_mc' => 0.080000,
            'assi_necessarie' => 4,
            'scarto_totale_mm' => 100,
            'scarto_percentuale' => 2.5,
            'costo_materiale' => 40,
            'prezzo_vendita' => 80,
            'ordine' => 2,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->call('ricalcolaTotali')
            ->assertSet('scarto_totale_percentuale', 4.0);
    }

    public function test_ricalcola_totali_prioritizes_material_volume_and_syncs_lotto_field(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'created_by' => $this->user->id,
            'larghezza_cm' => 100,
            'profondita_cm' => 100,
            'altezza_cm' => 100,
            'numero_pezzi' => 1,
            'volume_totale_mc' => 1.000000,
        ]);

        $lotto->materialiUsati()->create([
            'descrizione' => 'Asse A',
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 100,
            'spessore_mm' => 25,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.100000,
            'assi_necessarie' => 1,
            'scarto_totale_mm' => 50,
            'scarto_percentuale' => 2.5,
            'costo_materiale' => 10,
            'prezzo_vendita' => 20,
            'ordine' => 1,
        ]);

        $lotto->materialiUsati()->create([
            'descrizione' => 'Asse B',
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 100,
            'spessore_mm' => 25,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.200000,
            'assi_necessarie' => 1,
            'scarto_totale_mm' => 100,
            'scarto_percentuale' => 5,
            'costo_materiale' => 20,
            'prezzo_vendita' => 40,
            'ordine' => 2,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->call('ricalcolaTotali')
            ->assertSet('volume_totale_mc', 0.3);

        $this->assertEquals(0.3, (float) $lotto->fresh()->volume_totale_mc);
    }

    public function test_totali_e_costi_are_hidden_until_cutting_plan_is_saved(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertDontSee('Totali e Costi');
    }

    public function test_totali_e_costi_are_visible_after_cutting_plan_is_saved(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'descrizione' => 'Asse test',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.060000,
            'costo_materiale' => 10,
            'prezzo_vendita' => 15,
            'ordine' => 0,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSee('Totali e Costi');
    }

    public function test_mount_restores_materiale_id_from_optimizer_result(): void
    {
        $materiale = Prodotto::factory()->create([
            'is_active' => true,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'created_by' => $this->user->id,
            'optimizer_result' => [
                'materiale' => [
                    'id' => $materiale->id,
                    'nome' => $materiale->nome,
                ],
                'totali' => [
                    'costo_totale' => 0,
                    'prezzo_totale' => 0,
                    'volume_totale_mc' => 0,
                ],
            ],
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSet('materiale_id', $materiale->id)
            ->assertSet('optimizerResult.version', 'legacy-v1');
    }

    public function test_mount_restores_materiale_id_from_saved_materials_when_optimizer_is_missing(): void
    {
        $materiale = Prodotto::factory()->create([
            'is_active' => true,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'created_by' => $this->user->id,
            'optimizer_result' => null,
        ]);

        $lotto->materialiUsati()->create([
            'prodotto_id' => $materiale->id,
            'descrizione' => 'Asse test',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.060000,
            'costo_materiale' => 10,
            'prezzo_vendita' => 15,
            'ordine' => 0,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSet('materiale_id', $materiale->id);
    }

    public function test_operatore_sees_existing_lotto_as_read_only_and_cannot_save(): void
    {
        $operatore = User::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'stato' => \App\Enums\StatoLottoProduzione::BOZZA,
            'prodotto_finale' => 'Lotto originale',
        ]);

        Livewire::actingAs($operatore)
            ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSet('isReadOnly', true)
            ->set('prodotto_finale', 'Modifica non consentita')
            ->call('save')
            ->assertHasErrors('lotto');

        $this->assertSame('Lotto originale', $lotto->fresh()->prodotto_finale);
    }

    public function test_non_admin_does_not_see_optimizer_debug_panel(): void
    {
        $operatore = User::factory()->create();

        Livewire::actingAs($operatore)
            ->test(LottoProduzioneForm::class)
            ->set('optimizerResult', [
                'version' => 'v2',
                'optimizer' => [
                    'name' => 'cassa',
                    'version' => 'cassa-strips-v1',
                ],
                'trace' => [
                    'audit' => [
                        'logical_timestamp' => '2026-03-02T10:00:00+00:00',
                    ],
                ],
                'bins' => [
                    [
                        'used_length' => 2200,
                        'waste' => 100,
                        'waste_percent' => 4.35,
                        'items' => [],
                    ],
                ],
            ])
            ->assertDontSee('Debug Optimizer (admin)')
            ->assertDontSee('calculation_trace');
    }

    public function test_admin_sees_optimizer_debug_panel_with_trace_and_cutting_plan(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(LottoProduzioneForm::class)
            ->set('optimizerResult', [
                'version' => 'v2',
                'optimizer' => [
                    'name' => 'cassa',
                    'version' => 'cassa-strips-v1',
                ],
                'trace' => [
                    'audit' => [
                        'logical_timestamp' => '2026-03-02T10:00:00+00:00',
                    ],
                    'variant_routine' => 'cassa_standard',
                    'optimizer_mode' => 'category-v1',
                    'piece_source' => 'panel_requirements',
                    'component_summary' => [
                        [
                            'id' => 1,
                            'description' => 'Parete lunga',
                            'requested_strips' => 2,
                            'produced_strips' => 2,
                            'assigned_bins' => [
                                ['board_number' => 1, 'strips' => 2],
                            ],
                        ],
                    ],
                ],
                'bins' => [
                    [
                        'used_length' => 2200,
                        'waste' => 100,
                        'waste_percent' => 4.35,
                        'items' => [
                            [
                                'description' => 'Parete lunga',
                                'length' => 1000,
                                'width' => 250,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertSee('Debug Optimizer (admin)')
            ->assertSee('Mostra dettagli')
            ->assertDontSee('Debug piano di taglio')
            ->call('toggleOptimizerDebugPanel')
            ->assertSee('Nascondi dettagli')
            ->assertSee('calculation_trace')
            ->assertSee('Debug componenti')
            ->assertSee('Debug piano di taglio')
            ->assertSee('Asse #1')
            ->assertSee('Parete lunga')
            ->call('toggleOptimizerDebugPanel')
            ->assertSee('Mostra dettagli')
            ->assertDontSee('Debug piano di taglio');
    }

    public function test_it_opens_substitution_modal_with_only_compatible_materials(): void
    {
        $primary = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete principale 150x100x30',
            'lunghezza_mm' => 1500,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
        ]);
        $candidateCompatible = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete sostitutivo 300x100x30',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
        ]);
        $candidateWrongWidth = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete errato 300x120x30',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 120,
            'spessore_mm' => 30,
        ]);
        $candidateWrongThickness = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete errato 300x100x35',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 35,
        ]);

        LottoMateriale::factory()->create([
            'prodotto_id' => $primary->id,
            'quantita_iniziale' => 2,
        ]);
        LottoMateriale::factory()->create([
            'prodotto_id' => $candidateCompatible->id,
            'quantita_iniziale' => 2,
        ]);
        LottoMateriale::factory()->create([
            'prodotto_id' => $candidateWrongWidth->id,
            'quantita_iniziale' => 2,
        ]);
        LottoMateriale::factory()->create([
            'prodotto_id' => $candidateWrongThickness->id,
            'quantita_iniziale' => 2,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('showOptimizerResults', true)
            ->set('optimizerResult', [
                'materiale' => [
                    'id' => $primary->id,
                    'nome' => $primary->nome,
                    'lunghezza_mm' => 1500,
                    'larghezza_mm' => 100,
                    'spessore_mm' => 30,
                    'unita_misura' => 'mc',
                    'costo_unitario' => 300,
                    'prezzo_unitario' => 500,
                    'prezzo_mc' => 500,
                    'soggetto_fitok' => true,
                ],
                'kerf' => 3,
                'bins' => [
                    [
                        'capacity' => 1500,
                        'used_length' => 1403,
                        'waste' => 97,
                        'waste_percent' => 6.47,
                        'items' => [
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                        ],
                    ],
                    [
                        'capacity' => 1500,
                        'used_length' => 1403,
                        'waste' => 97,
                        'waste_percent' => 6.47,
                        'items' => [
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                        ],
                    ],
                ],
            ])
            ->call('toggleOptimizerBinSelection', 0)
            ->call('toggleOptimizerBinSelection', 1)
            ->call('openSubstitutionModal')
            ->assertSet('showSubstitutionModal', true)
            ->assertSet('compatibleSubstitutionMaterialIds', function (array $ids) use ($primary, $candidateCompatible, $candidateWrongWidth, $candidateWrongThickness): bool {
                return ! in_array($primary->id, $ids, true)
                    && in_array($candidateCompatible->id, $ids, true)
                    && ! in_array($candidateWrongWidth->id, $ids, true)
                    && ! in_array($candidateWrongThickness->id, $ids, true);
            })
            ->assertSee($candidateCompatible->nome)
            ->assertSet('substitutionPreview.payload.total_bins', 1);
    }

    public function test_it_does_not_open_substitution_modal_when_no_compatible_material_exists(): void
    {
        $primary = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete principale 150x100x30',
            'lunghezza_mm' => 1500,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
        ]);
        $candidateWrongWidth = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete errato 300x120x30',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 120,
            'spessore_mm' => 30,
        ]);

        LottoMateriale::factory()->create([
            'prodotto_id' => $primary->id,
            'quantita_iniziale' => 2,
        ]);
        LottoMateriale::factory()->create([
            'prodotto_id' => $candidateWrongWidth->id,
            'quantita_iniziale' => 2,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('showOptimizerResults', true)
            ->set('optimizerResult', [
                'materiale' => [
                    'id' => $primary->id,
                    'nome' => $primary->nome,
                    'lunghezza_mm' => 1500,
                    'larghezza_mm' => 100,
                    'spessore_mm' => 30,
                    'unita_misura' => 'mc',
                    'costo_unitario' => 300,
                    'prezzo_unitario' => 500,
                    'prezzo_mc' => 500,
                    'soggetto_fitok' => true,
                ],
                'kerf' => 3,
                'bins' => [
                    [
                        'capacity' => 1500,
                        'used_length' => 1403,
                        'waste' => 97,
                        'waste_percent' => 6.47,
                        'items' => [
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                        ],
                    ],
                ],
            ])
            ->call('toggleOptimizerBinSelection', 0)
            ->call('openSubstitutionModal')
            ->assertSet('showSubstitutionModal', false)
            ->assertSet('compatibleSubstitutionMaterialIds', []);
    }

    public function test_it_applies_material_substitution_to_optimizer_result_preview(): void
    {
        $primary = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete fitok 150x100x30',
            'lunghezza_mm' => 1500,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
            'soggetto_fitok' => true,
        ]);
        $candidateCompatible = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete non fitok 300x100x30',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
            'soggetto_fitok' => false,
        ]);

        LottoMateriale::factory()->create([
            'prodotto_id' => $primary->id,
            'quantita_iniziale' => 2,
        ]);
        LottoMateriale::factory()->create([
            'prodotto_id' => $candidateCompatible->id,
            'quantita_iniziale' => 2,
        ]);

        Livewire::test(LottoProduzioneForm::class)
            ->set('showOptimizerResults', true)
            ->set('optimizerResult', [
                'materiale' => [
                    'id' => $primary->id,
                    'nome' => $primary->nome,
                    'lunghezza_mm' => 1500,
                    'larghezza_mm' => 100,
                    'spessore_mm' => 30,
                    'unita_misura' => 'mc',
                    'costo_unitario' => 300,
                    'prezzo_unitario' => 500,
                    'prezzo_mc' => 500,
                    'soggetto_fitok' => true,
                ],
                'kerf' => 3,
                'bins' => [
                    [
                        'capacity' => 1500,
                        'used_length' => 1403,
                        'waste' => 97,
                        'waste_percent' => 6.47,
                        'items' => [
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                        ],
                    ],
                    [
                        'capacity' => 1500,
                        'used_length' => 1403,
                        'waste' => 97,
                        'waste_percent' => 6.47,
                        'items' => [
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                            ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                        ],
                    ],
                ],
            ])
            ->call('toggleOptimizerBinSelection', 0)
            ->call('toggleOptimizerBinSelection', 1)
            ->call('openSubstitutionModal')
            ->set('substitutionMaterialId', $candidateCompatible->id)
            ->call('applySubstitution')
            ->assertSet('optimizerResult.total_bins', 1)
            ->assertSet('optimizerResult.fitok_preview.status', 'non_fitok')
            ->assertSet('optimizerResult.bins.0.source_material_id', $candidateCompatible->id)
            ->assertSet('selectedOptimizerBins', []);
    }

    public function test_salva_materiali_persists_effective_material_for_each_bin(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $this->user->id,
            'prodotto_finale' => 'Lotto multi-materiale',
        ]);
        $primary = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete principale 240x100x30',
            'lunghezza_mm' => 2400,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
            'costo_unitario' => 300,
            'prezzo_unitario' => 500,
            'prezzo_mc' => 500,
            'soggetto_fitok' => true,
        ]);
        $candidate = Prodotto::factory()->legname()->active()->create([
            'nome' => 'Abete sostitutivo 300x100x30',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
            'costo_unitario' => 320,
            'prezzo_unitario' => 520,
            'prezzo_mc' => 520,
            'soggetto_fitok' => false,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('showOptimizerResults', true)
            ->set('optimizerResult', [
                'materiale' => [
                    'id' => $primary->id,
                    'nome' => $primary->nome,
                    'lunghezza_mm' => 2400,
                    'larghezza_mm' => 100,
                    'spessore_mm' => 30,
                    'unita_misura' => 'mc',
                    'costo_unitario' => 300,
                    'prezzo_unitario' => 500,
                    'prezzo_mc' => 500,
                    'soggetto_fitok' => true,
                ],
                'kerf' => 3,
                'totali' => [
                    'volume_totale_mc' => 0.0216,
                    'volume_lordo_mc' => 0.0216,
                    'volume_netto_mc' => 0.012,
                    'volume_scarto_mc' => 0.0096,
                    'costo_totale' => 6.74,
                    'prezzo_totale' => 11.02,
                ],
                'bins' => [
                    [
                        'capacity' => 2400,
                        'used_length' => 2000,
                        'waste' => 400,
                        'waste_percent' => 16.67,
                        'source_material_id' => $primary->id,
                        'source_material' => [
                            'id' => $primary->id,
                            'nome' => $primary->nome,
                            'larghezza_mm' => 100,
                            'spessore_mm' => 30,
                            'soggetto_fitok' => true,
                        ],
                        'items' => [
                            ['id' => 1, 'description' => 'Parete', 'length' => 1000, 'width' => 100],
                            ['id' => 1, 'description' => 'Parete', 'length' => 1000, 'width' => 100],
                        ],
                        'volume_lordo_mc' => 0.0072,
                        'volume_netto_mc' => 0.006,
                        'volume_scarto_mc' => 0.0012,
                    ],
                    [
                        'capacity' => 2400,
                        'used_length' => 1000,
                        'waste' => 1400,
                        'waste_percent' => 58.33,
                        'source_material_id' => $candidate->id,
                        'source_material' => [
                            'id' => $candidate->id,
                            'nome' => $candidate->nome,
                            'larghezza_mm' => 100,
                            'spessore_mm' => 30,
                            'soggetto_fitok' => false,
                        ],
                        'items' => [
                            ['id' => 2, 'description' => 'Fondo', 'length' => 1000, 'width' => 100],
                            ['id' => 2, 'description' => 'Fondo', 'length' => 1000, 'width' => 100],
                        ],
                        'volume_lordo_mc' => 0.0144,
                        'volume_netto_mc' => 0.006,
                        'volume_scarto_mc' => 0.0084,
                    ],
                ],
            ])
            ->call('salvaMateriali')
            ->assertSet('showOptimizerResults', false)
            ->assertSet('optimizerResult', null);

        $this->assertDatabaseHas('lotto_produzione_materiali', [
            'lotto_produzione_id' => $lotto->id,
            'prodotto_id' => $primary->id,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
            'is_fitok' => true,
        ]);
        $this->assertDatabaseHas('lotto_produzione_materiali', [
            'lotto_produzione_id' => $lotto->id,
            'prodotto_id' => $candidate->id,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
            'is_fitok' => false,
        ]);
    }

    public function test_torna_al_preventivo_non_reindirizza_silenziosamente_se_il_lotto_e_non_valido(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
            'prodotto_finale' => 'Lotto valido inizialmente',
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('returnTo', 'preventivo')
            ->set('preventivoId', $preventivo->id)
            ->set('prodotto_finale', '')
            ->call('tornaAlPreventivo')
            ->assertHasErrors(['prodotto_finale']);
    }

    public function test_torna_al_preventivo_aggiorna_la_riga_preventivo_con_totale_da_optimizer_preview(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
            'prodotto_finale' => 'Cassa test',
            'pricing_mode' => LottoPricingMode::COSTO_RICARICO,
            'ricarico_percentuale' => 0,
        ]);

        $rigaPreventivo = $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => 'lotto',
            'include_in_bom' => true,
            'descrizione' => 'Lotto da aggiornare',
            'lunghezza_mm' => 0,
            'larghezza_mm' => 0,
            'spessore_mm' => 0,
            'quantita' => 1,
            'coefficiente_scarto' => 0.10,
            'prezzo_unitario' => 0,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'materiale_lordo' => 0,
            'totale_riga' => 0,
            'ordine' => 0,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('returnTo', 'preventivo')
            ->set('preventivoId', $preventivo->id)
            ->set('pricing_mode', LottoPricingMode::COSTO_RICARICO->value)
            ->set('ricarico_percentuale', 25)
            ->set('showOptimizerResults', true)
            ->set('optimizerResult', [
                'totali' => [
                    'costo_totale' => 100,
                    'prezzo_totale' => 120,
                    'volume_totale_mc' => 0.2,
                ],
                'total_waste_percent' => 5.5,
                'materiale' => [
                    'id' => 123,
                    'nome' => 'Anteprima materiale',
                ],
            ])
            ->call('tornaAlPreventivo')
            ->assertRedirect(route('preventivi.edit', $preventivo->id));

        $rigaPreventivo->refresh();
        $lotto->refresh();

        $this->assertEqualsWithDelta(0.2, (float) $rigaPreventivo->volume_mc, 0.0001);
        $this->assertEqualsWithDelta(0.2, (float) $rigaPreventivo->materiale_lordo, 0.0001);
        $this->assertEqualsWithDelta(0.2, (float) $rigaPreventivo->materiale_netto, 0.0001);
        $this->assertEqualsWithDelta(625, (float) $rigaPreventivo->prezzo_unitario, 0.01);
        $this->assertEqualsWithDelta(125, (float) $rigaPreventivo->totale_riga, 0.01);
        $this->assertEqualsWithDelta(125, (float) $lotto->prezzo_finale, 0.01);
        $this->assertEqualsWithDelta(0.2, (float) $lotto->volume_totale_mc, 0.0001);
    }

    public function test_torna_al_preventivo_usa_prezzo_materiali_come_fallback_se_tariffa_mc_non_impostata(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
            'prodotto_finale' => 'Cassa fallback pricing',
            'pricing_mode' => LottoPricingMode::TARIFFA_MC,
            'tariffa_mc' => null,
        ]);

        $rigaPreventivo = $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => 'lotto',
            'include_in_bom' => true,
            'descrizione' => 'Lotto fallback pricing',
            'lunghezza_mm' => 0,
            'larghezza_mm' => 0,
            'spessore_mm' => 0,
            'quantita' => 1,
            'coefficiente_scarto' => 0.10,
            'prezzo_unitario' => 0,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'materiale_lordo' => 0,
            'totale_riga' => 0,
            'ordine' => 0,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('returnTo', 'preventivo')
            ->set('preventivoId', $preventivo->id)
            ->set('pricing_mode', LottoPricingMode::TARIFFA_MC->value)
            ->set('tariffa_mc', null)
            ->set('showOptimizerResults', true)
            ->set('optimizerResult', [
                'totali' => [
                    'costo_totale' => 100,
                    'prezzo_totale' => 120,
                    'volume_totale_mc' => 0.2,
                    'volume_lordo_mc' => 0.2,
                    'volume_netto_mc' => 0.18,
                ],
                'total_waste_percent' => 10,
                'materiale' => [
                    'id' => 123,
                    'nome' => 'Materiale fallback',
                ],
            ])
            ->call('tornaAlPreventivo')
            ->assertRedirect(route('preventivi.edit', $preventivo->id));

        $rigaPreventivo->refresh();
        $lotto->refresh();

        $this->assertEqualsWithDelta(120, (float) $lotto->prezzo_calcolato, 0.01);
        $this->assertEqualsWithDelta(120, (float) $lotto->prezzo_finale, 0.01);
        $this->assertEqualsWithDelta(600, (float) $rigaPreventivo->prezzo_unitario, 0.01);
        $this->assertEqualsWithDelta(120, (float) $rigaPreventivo->totale_riga, 0.01);
    }

    public function test_torna_al_preventivo_recalculates_parent_preventivo_totals(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
            'totale_materiali' => 999,
            'totale_lavorazioni' => 20,
            'totale' => 1019,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
            'prodotto_finale' => 'Cassa ricalcolo',
            'pricing_mode' => LottoPricingMode::COSTO_RICARICO,
            'ricarico_percentuale' => 0,
        ]);

        $preventivo->righe()->create([
            'tipo_riga' => 'sfuso',
            'include_in_bom' => true,
            'descrizione' => 'Materiale sfuso',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita' => 1,
            'coefficiente_scarto' => 0.10,
            'prezzo_unitario' => 0,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'materiale_lordo' => 0,
            'totale_riga' => 30,
            'ordine' => 0,
        ]);

        $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => 'lotto',
            'include_in_bom' => true,
            'descrizione' => 'Lotto da ricalcolare',
            'lunghezza_mm' => 0,
            'larghezza_mm' => 0,
            'spessore_mm' => 0,
            'quantita' => 1,
            'coefficiente_scarto' => 0.10,
            'prezzo_unitario' => 0,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'materiale_lordo' => 0,
            'totale_riga' => 0,
            'ordine' => 1,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('returnTo', 'preventivo')
            ->set('preventivoId', $preventivo->id)
            ->set('pricing_mode', LottoPricingMode::COSTO_RICARICO->value)
            ->set('ricarico_percentuale', 25)
            ->set('showOptimizerResults', true)
            ->set('optimizerResult', [
                'totali' => [
                    'costo_totale' => 100,
                    'prezzo_totale' => 120,
                    'volume_totale_mc' => 0.2,
                ],
                'total_waste_percent' => 5.5,
                'materiale' => [
                    'id' => 123,
                    'nome' => 'Anteprima materiale',
                ],
            ])
            ->call('tornaAlPreventivo')
            ->assertRedirect(route('preventivi.edit', $preventivo->id));

        $preventivo->refresh();

        $this->assertEqualsWithDelta(155, (float) $preventivo->totale_materiali, 0.01);
        $this->assertEqualsWithDelta(20, (float) $preventivo->totale_lavorazioni, 0.01);
        $this->assertEqualsWithDelta(175, (float) $preventivo->totale, 0.01);
    }

    public function test_torna_al_preventivo_syncs_linked_riga_dimensions_from_lotto(): void
    {
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
            'prodotto_finale' => 'Lotto sync dimensioni',
            'larghezza_cm' => null,
            'profondita_cm' => null,
            'altezza_cm' => null,
            'numero_pezzi' => 1,
            'pricing_mode' => LottoPricingMode::COSTO_RICARICO,
            'ricarico_percentuale' => 0,
        ]);

        $rigaPreventivo = $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => 'lotto',
            'include_in_bom' => true,
            'descrizione' => 'Riga lotto da sincronizzare',
            'lunghezza_mm' => 0,
            'larghezza_mm' => 0,
            'spessore_mm' => 0,
            'quantita' => 1,
            'coefficiente_scarto' => 0.10,
            'prezzo_unitario' => 0,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'materiale_lordo' => 0,
            'totale_riga' => 0,
            'ordine' => 0,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('returnTo', 'preventivo')
            ->set('preventivoId', $preventivo->id)
            ->set('larghezza_cm', '120')
            ->set('profondita_cm', '80')
            ->set('altezza_cm', '60')
            ->set('numero_pezzi', '4')
            ->set('pricing_mode', LottoPricingMode::COSTO_RICARICO->value)
            ->set('ricarico_percentuale', 25)
            ->set('showOptimizerResults', true)
            ->set('optimizerResult', [
                'totali' => [
                    'costo_totale' => 100,
                    'prezzo_totale' => 120,
                    'volume_totale_mc' => 0.2,
                ],
                'total_waste_percent' => 5.5,
                'materiale' => [
                    'id' => 123,
                    'nome' => 'Anteprima materiale',
                ],
            ])
            ->call('tornaAlPreventivo')
            ->assertRedirect(route('preventivi.edit', $preventivo->id));

        $rigaPreventivo->refresh();
        $this->assertEqualsWithDelta(1200, (float) $rigaPreventivo->lunghezza_mm, 0.01);
        $this->assertEqualsWithDelta(800, (float) $rigaPreventivo->larghezza_mm, 0.01);
        $this->assertEqualsWithDelta(600, (float) $rigaPreventivo->spessore_mm, 0.01);
        $this->assertSame(4, (int) $rigaPreventivo->quantita);
    }

    public function test_ricalcola_totali_usa_dimensioni_live_se_non_ci_sono_materiali_salvati(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $this->user->id,
            'larghezza_cm' => 100,
            'profondita_cm' => 100,
            'altezza_cm' => 100,
            'numero_pezzi' => 1,
            'volume_totale_mc' => 1.000000,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('larghezza_cm', '200')
            ->set('profondita_cm', '100')
            ->set('altezza_cm', '50')
            ->set('numero_pezzi', '2')
            ->call('ricalcolaTotali')
            ->assertSet('volume_totale_mc', 2.0);

        $this->assertEqualsWithDelta(2.0, (float) $lotto->fresh()->volume_totale_mc, 0.0001);
    }

    public function test_pricing_tariffa_mc_non_usa_volume_geometrico_finche_non_esiste_un_piano_taglio(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $this->user->id,
            'larghezza_cm' => 80,
            'profondita_cm' => 80,
            'altezza_cm' => 120,
            'numero_pezzi' => 2,
            'pricing_mode' => LottoPricingMode::TARIFFA_MC->value,
            'tariffa_mc' => 250,
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->assertSet('volume_totale_mc', 1.536)
            ->assertSet('prezzo_calcolato', 0.0)
            ->assertSee('Volume pricing disponibile dopo il salvataggio del piano di taglio.');
    }

    public function test_save_upgrades_legacy_optimizer_result_to_current_payload_version(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $this->user->id,
            'prodotto_finale' => 'Lotto legacy payload',
            'preventivo_id' => Preventivo::factory()->create([
                'created_by' => $this->user->id,
            ])->id,
            'optimizer_result' => [
                'optimizer' => [
                    'name' => 'legacy-bin-packing',
                    'version' => 'legacy-1d-v1',
                    'strategy' => 'direct-1d-bfd',
                ],
                'materiale' => ['id' => 999],
                'bins' => [],
                'totali' => [
                    'costo_totale' => 0,
                    'prezzo_totale' => 0,
                    'volume_totale_mc' => 0,
                ],
            ],
        ]);

        Livewire::test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('prodotto_finale', 'Lotto legacy payload aggiornato')
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $savedPayload = $lotto->fresh()->optimizer_result;
        $this->assertIsArray($savedPayload);
        $this->assertSame('v2', data_get($savedPayload, 'version'));
        $this->assertSame('legacy-bin-packing', data_get($savedPayload, 'trace.audit.algorithm.name'));
        $this->assertTrue((bool) data_get($savedPayload, 'trace.audit.compatibility.legacy_read_applied'));
    }
}
