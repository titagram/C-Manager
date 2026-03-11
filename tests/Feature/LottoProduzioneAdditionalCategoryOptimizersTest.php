<?php

namespace Tests\Feature;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Livewire\Forms\LottoProduzioneForm;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class LottoProduzioneAdditionalCategoryOptimizersTest extends TestCase
{
    use RefreshDatabase;

    public function test_gabbia_category_uses_category_optimizer_v1_and_can_skip_coperchio_via_config(): void
    {
        $user = User::factory()->admin()->create();

        $costruzione = Costruzione::factory()->create([
            'categoria' => 'gabbia',
            'nome' => 'Gabbia Test',
            'slug' => 'gabbia-standard',
            'config' => ['ha_coperchio' => false],
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Fondo',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'W',
            'formula_quantita' => '1',
        ]);
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Coperchio',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'W',
            'formula_quantita' => '1',
        ]);
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Montanti',
            'formula_lunghezza' => 'H',
            'formula_larghezza' => '100',
            'formula_quantita' => '4',
        ]);

        $materiale = Prodotto::factory()->create([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'is_active' => true,
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $component = Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '100')
            ->set('profondita_cm', '50')
            ->set('altezza_cm', '100')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true);

        $result = $component->get('optimizerResult');
        $this->assertSame('gabbia', data_get($result, 'optimizer.name'));
        $this->assertGreaterThan(0, (int) ($result['total_bins'] ?? 0));
        $this->assertGreaterThan(0, (float) data_get($result, 'totali.volume_lordo_mc', 0));
        $this->assertSame('gabbiasp20', data_get($result, 'trace.variant_routine'));
        $this->assertSame('rectangular_v1_fallback', data_get($result, 'trace.optimizer_mode'));
        $this->assertSame('gabbia-strips-v1', data_get($result, 'optimizer.version'));
        $this->assertSame('gabbia-strips-v1', data_get($result, 'trace.category_optimizer_version'));
        $this->assertSame('gabbiasp20', data_get($result, 'trace.excel_preview.routine'));
        // W=50cm -> ceil((50/10)+0.5) = 6 (current documented legacy assumption for D8)
        $this->assertSame(6, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D8'));
        $this->assertSame(6, count(data_get($result, 'trace.excel_preview.rows', [])));
        $this->assertNotEmpty(data_get($result, 'trace.component_summary'));
        $this->assertNotEmpty(data_get($result, 'trace.component_assignments'));
        $this->assertNotEmpty(data_get($result, 'trace.component_summary.0.assigned_bins'));

        $skipped = collect(data_get($result, 'trace.skipped_panels', []));
        $this->assertTrue($skipped->contains(fn (array $row) => str_contains($row['description'], 'Coperchio')));
    }

    public function test_gabbia_can_use_excel_compatibility_mode_piece_source(): void
    {
        $previousMode = config('production.gabbia_excel_mode', 'preview');
        Config::set('production.gabbia_excel_mode', 'compatibility');

        try {
            $user = User::factory()->admin()->create();

            $costruzione = Costruzione::factory()->create([
                'categoria' => 'gabbia',
                'nome' => 'Gabbia Compatibility Mode',
                'slug' => 'gabbia-standard',
                'config' => ['ha_coperchio' => false],
            ]);

            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Fondo',
                'formula_lunghezza' => 'L',
                'formula_larghezza' => 'W',
                'formula_quantita' => '1',
            ]);
            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Montanti',
                'formula_lunghezza' => 'H',
                'formula_larghezza' => '100',
                'formula_quantita' => '4',
            ]);

            $materiale = Prodotto::factory()->create([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'is_active' => true,
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $component = Livewire::actingAs($user)
                ->test(LottoProduzioneForm::class)
                ->set('costruzione_id', $costruzione->id)
                ->set('materiale_id', $materiale->id)
                ->set('larghezza_cm', '100')
                ->set('profondita_cm', '50')
                ->set('altezza_cm', '100')
                ->set('numero_pezzi', '1')
                ->call('calcolaMateriali')
                ->assertSet('showOptimizerResults', true);

            $result = $component->get('optimizerResult');
            $this->assertSame('gabbia', data_get($result, 'optimizer.name'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'optimizer.version'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'trace.category_optimizer_version'));
        } finally {
            Config::set('production.gabbia_excel_mode', $previousMode);
        }
    }

    public function test_gabbia_legaccio_variant_can_use_excel_compatibility_mode_piece_source(): void
    {
        $previousMode = config('production.gabbia_excel_mode', 'preview');
        Config::set('production.gabbia_excel_mode', 'compatibility');

        try {
            $user = User::factory()->admin()->create();

            $costruzione = Costruzione::factory()->create([
                'categoria' => 'gabbia',
                'nome' => 'Gabbia Legaccio Compatibility',
                'slug' => 'gabbia-legaccio',
                'config' => [
                    'piantoni' => 6,
                    'fondo4' => true,
                    'ha_coperchio' => false,
                ],
            ]);

            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Telaio Dummy',
                'formula_lunghezza' => 'L',
                'formula_larghezza' => 'W',
                'formula_quantita' => '1',
            ]);

            $materiale = Prodotto::factory()->create([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'is_active' => true,
                'lunghezza_mm' => 3000,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $component = Livewire::actingAs($user)
                ->test(LottoProduzioneForm::class)
                ->set('costruzione_id', $costruzione->id)
                ->set('materiale_id', $materiale->id)
                ->set('larghezza_cm', '120')
                ->set('profondita_cm', '40')
                ->set('altezza_cm', '215')
                ->set('numero_pezzi', '1')
                ->call('calcolaMateriali')
                ->assertSet('showOptimizerResults', true);

            $result = $component->get('optimizerResult');
            $this->assertSame('gabbia', data_get($result, 'optimizer.name'));
            $this->assertSame('gabbialegaccio6piantonifondo4', data_get($result, 'trace.variant_routine'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'optimizer.version'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'trace.category_optimizer_version'));
            $this->assertSame(16, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D9'));
        } finally {
            Config::set('production.gabbia_excel_mode', $previousMode);
        }
    }

    public function test_gabbia_excel_compatibility_mode_can_save_materiali_usati(): void
    {
        $previousMode = config('production.gabbia_excel_mode', 'preview');
        Config::set('production.gabbia_excel_mode', 'compatibility');

        try {
            $user = User::factory()->admin()->create();
            $lotto = LottoProduzione::factory()->bozza()->create([
                'created_by' => $user->id,
            ]);

            $costruzione = Costruzione::factory()->create([
                'categoria' => 'gabbia',
                'nome' => 'Gabbia Save Compatibility',
                'slug' => 'gabbia-standard',
                'config' => ['ha_coperchio' => false],
            ]);

            // Keep at least one calculated component active for formula validation flow.
            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Fondo',
                'formula_lunghezza' => 'L',
                'formula_larghezza' => 'W',
                'formula_quantita' => '1',
            ]);

            $materiale = Prodotto::factory()->create([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'is_active' => true,
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
                'costo_unitario' => 400,
                'prezzo_mc' => 540,
                'prezzo_unitario' => 1,
            ]);

            Livewire::actingAs($user)
                ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
                ->set('costruzione_id', $costruzione->id)
                ->set('materiale_id', $materiale->id)
                ->set('larghezza_cm', '84')
                ->set('profondita_cm', '43')
                ->set('altezza_cm', '55')
                ->set('numero_pezzi', '1')
                ->call('calcolaMateriali')
                ->assertSet('showOptimizerResults', true)
                ->call('salvaMateriali');

            $rows = $lotto->fresh()->materialiUsati;
            $this->assertCount(12, $rows);
            $this->assertEqualsWithDelta(0.138, (float) $rows->sum('volume_mc'), 0.000001);
            $this->assertEqualsWithDelta(55.20, (float) $rows->sum('costo_materiale'), 0.01);
            $this->assertEqualsWithDelta(74.52, (float) $rows->sum('prezzo_vendita'), 0.01);

            $first = $rows->first();
            $this->assertNotNull($first);
            $this->assertStringContainsString('Asse 1:', (string) $first->descrizione);
        } finally {
            Config::set('production.gabbia_excel_mode', $previousMode);
        }
    }

    public function test_bancale_category_uses_category_optimizer_v1_without_required_altezza(): void
    {
        $user = User::factory()->admin()->create();

        $costruzione = Costruzione::factory()->create([
            'categoria' => 'bancale',
            'nome' => 'Bancale Test',
            'richiede_altezza' => false,
            'config' => [],
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Morali',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => '80',
            'formula_quantita' => '3',
        ]);
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Doghe',
            'formula_lunghezza' => 'W',
            'formula_larghezza' => '100',
            'formula_quantita' => 'ceil(L / 250)',
        ]);

        $materiale = Prodotto::factory()->create([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'is_active' => true,
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $component = Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '120')
            ->set('profondita_cm', '80')
            ->set('altezza_cm', '')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true);

        $result = $component->get('optimizerResult');
        $this->assertSame('bancale', data_get($result, 'optimizer.name'));
        $this->assertGreaterThan(0, (int) ($result['total_bins'] ?? 0));
        $this->assertGreaterThan(0, (float) data_get($result, 'totali.volume_lordo_mc', 0));
    }

    public function test_bancale_can_use_excel_compatibility_mode_piece_source(): void
    {
        $previousMode = config('production.bancale_excel_mode', 'preview');
        Config::set('production.bancale_excel_mode', 'compatibility');

        try {
            $user = User::factory()->admin()->create();

            $costruzione = Costruzione::factory()->create([
                'categoria' => 'bancale',
                'nome' => 'Bancale Compatibility Mode',
                'slug' => 'bancale-standard',
                'richiede_altezza' => false,
                'config' => [],
            ]);

            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Morali',
                'formula_lunghezza' => 'L',
                'formula_larghezza' => '80',
                'formula_quantita' => '3',
            ]);

            $materiale = Prodotto::factory()->create([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'is_active' => true,
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $component = Livewire::actingAs($user)
                ->test(LottoProduzioneForm::class)
                ->set('costruzione_id', $costruzione->id)
                ->set('materiale_id', $materiale->id)
                ->set('larghezza_cm', '84')
                ->set('profondita_cm', '43')
                ->set('altezza_cm', '')
                ->set('numero_pezzi', '1')
                ->call('calcolaMateriali')
                ->assertSet('showOptimizerResults', true);

            $result = $component->get('optimizerResult');
            $this->assertSame('bancale', data_get($result, 'optimizer.name'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame('bancale', data_get($result, 'trace.excel_preview.routine'));
            $this->assertSame(4, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D8'));
            $this->assertSame(3, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D9'));
        } finally {
            Config::set('production.bancale_excel_mode', $previousMode);
        }
    }

    public function test_bancale_perimetrale_can_use_excel_compatibility_mode_piece_source(): void
    {
        $previousMode = config('production.bancale_excel_mode', 'preview');
        Config::set('production.bancale_excel_mode', 'compatibility');

        try {
            $user = User::factory()->admin()->create();

            $costruzione = Costruzione::factory()->create([
                'categoria' => 'bancale',
                'nome' => 'Perimetrale Compatibility Mode',
                'slug' => 'perimetrale-standard',
                'richiede_altezza' => true,
                'config' => [],
            ]);

            // Keep at least one calculated component active for formula validation flow.
            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Perimetrale Dummy',
                'formula_lunghezza' => 'L',
                'formula_larghezza' => 'H',
                'formula_quantita' => '1',
            ]);

            $materiale = Prodotto::factory()->create([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'is_active' => true,
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $component = Livewire::actingAs($user)
                ->test(LottoProduzioneForm::class)
                ->set('costruzione_id', $costruzione->id)
                ->set('materiale_id', $materiale->id)
                ->set('larghezza_cm', '190')
                ->set('profondita_cm', '120')
                ->set('altezza_cm', '80')
                ->set('numero_pezzi', '1')
                ->call('calcolaMateriali')
                ->assertSet('showOptimizerResults', true);

            $result = $component->get('optimizerResult');
            $this->assertSame('bancale', data_get($result, 'optimizer.name'));
            $this->assertSame('perimetrale', data_get($result, 'trace.variant_routine'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame(7, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D10'));
            $this->assertSame(6, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D11'));
        } finally {
            Config::set('production.bancale_excel_mode', $previousMode);
        }
    }

    public function test_legaccio_category_uses_category_optimizer_v1(): void
    {
        $user = User::factory()->admin()->create();

        $costruzione = Costruzione::factory()->create([
            'categoria' => 'legaccio',
            'nome' => 'Legaccio Test',
            'config' => [],
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Longherone',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => '80',
            'formula_quantita' => '2',
        ]);
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Piantone',
            'formula_lunghezza' => 'H',
            'formula_larghezza' => '80',
            'formula_quantita' => '4',
        ]);

        $materiale = Prodotto::factory()->create([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'is_active' => true,
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $component = Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '100')
            ->set('profondita_cm', '60')
            ->set('altezza_cm', '90')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true);

        $result = $component->get('optimizerResult');
        $this->assertSame('legaccio', data_get($result, 'optimizer.name'));
        $this->assertGreaterThan(0, (int) ($result['total_bins'] ?? 0));
    }

    public function test_legaccio_can_use_excel_compatibility_mode_piece_source(): void
    {
        $previousMode = config('production.legaccio_excel_mode', 'preview');
        Config::set('production.legaccio_excel_mode', 'compatibility');

        try {
            $user = User::factory()->admin()->create();

            $costruzione = Costruzione::factory()->create([
                'categoria' => 'legaccio',
                'nome' => 'Legaccio Compatibility Mode',
                'slug' => 'legacci-224x60',
                'config' => [],
            ]);

            // Keep at least one calculated component active for formula validation flow.
            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Longherone',
                'formula_lunghezza' => 'L',
                'formula_larghezza' => '80',
                'formula_quantita' => '2',
            ]);

            $materiale = Prodotto::factory()->create([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'is_active' => true,
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $component = Livewire::actingAs($user)
                ->test(LottoProduzioneForm::class)
                ->set('costruzione_id', $costruzione->id)
                ->set('materiale_id', $materiale->id)
                ->set('larghezza_cm', '224')
                ->set('profondita_cm', '60')
                ->set('altezza_cm', '1')
                ->set('numero_pezzi', '1')
                ->call('calcolaMateriali')
                ->assertSet('showOptimizerResults', true);

            $result = $component->get('optimizerResult');
            $this->assertSame('legaccio', data_get($result, 'optimizer.name'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame('legacci224x60', data_get($result, 'trace.excel_preview.routine'));
            $this->assertSame(20, (int) ($result['total_bins'] ?? 0));
        } finally {
            Config::set('production.legaccio_excel_mode', $previousMode);
        }
    }
}
