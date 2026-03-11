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
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class LottoProduzioneCassaOptimizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cassa_category_uses_category_optimizer_and_matches_reference_case_without_coperchio(): void
    {
        $user = User::factory()->admin()->create();

        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'nome' => 'Cassa Standard Test',
            'config' => [],
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete Lunga Esterna',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete Corta Interna',
            'formula_lunghezza' => 'W - (2 * T)',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Fondo',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'W',
            'formula_quantita' => '1',
        ]);

        // Intentionally present but should be skipped by current cassa optimizer assumptions.
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Coperchio',
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
            'prezzo_unitario' => 1, // should be ignored in favor of prezzo_mc for UoM=mc
            'prezzo_mc' => 540,
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

        $this->assertIsArray($result);
        $this->assertSame('cassa', data_get($result, 'optimizer.name'));
        $this->assertSame(7, (int) ($result['total_bins'] ?? 0));
        $this->assertEqualsWithDelta(0.0805, (float) data_get($result, 'totali.volume_lordo_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.0684, (float) data_get($result, 'totali.volume_netto_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.0121, (float) data_get($result, 'totali.volume_scarto_mc'), 0.000001);
        $this->assertGreaterThanOrEqual(
            (float) data_get($result, 'totali.volume_netto_mc'),
            (float) data_get($result, 'totali.volume_lordo_mc')
        );
        $this->assertEqualsWithDelta(
            (float) data_get($result, 'totali.volume_lordo_mc') - (float) data_get($result, 'totali.volume_netto_mc'),
            (float) data_get($result, 'totali.volume_scarto_mc'),
            0.000001
        );
        $this->assertSame('lordo', data_get($result, 'totali.pricing_volume_basis'));
        $this->assertEqualsWithDelta(32.20, (float) data_get($result, 'totali.costo_totale'), 0.01);
        $this->assertEqualsWithDelta(43.47, (float) data_get($result, 'totali.prezzo_totale'), 0.01);
        $this->assertEqualsWithDelta(36.94, (float) data_get($result, 'totali.prezzo_totale_netto'), 0.01);

    }

    public function test_cassa_mode_legacy_forces_legacy_bin_packing_fallback(): void
    {
        $previousMode = config('production.cassa_optimizer_mode', 'physical');
        config()->set('production.cassa_optimizer_mode', 'legacy');

        try {
            $user = User::factory()->admin()->create();

            $costruzione = Costruzione::factory()->create([
                'categoria' => 'cassa',
                'nome' => 'Cassa Legacy Mode Test',
            ]);

            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Parete Lunga Esterna',
                'formula_lunghezza' => 'L',
                'formula_larghezza' => 'H',
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
                ->set('larghezza_cm', '100')
                ->set('profondita_cm', '50')
                ->set('altezza_cm', '100')
                ->set('numero_pezzi', '1')
                ->call('calcolaMateriali')
                ->assertSet('showOptimizerResults', true);

            $result = $component->get('optimizerResult');

            $this->assertSame('legacy-bin-packing', data_get($result, 'optimizer.name'));
            $this->assertSame('legacy', data_get($result, 'trace.settings_snapshot.cassa_optimizer_mode'));
            $this->assertGreaterThan(0, (int) data_get($result, 'total_bins', 0));
        } finally {
            config()->set('production.cassa_optimizer_mode', $previousMode);
        }
    }

    public function test_cassa_shadow_compare_logs_significant_deltas_when_enabled(): void
    {
        $previousCompareEnabled = config('production.cassa_shadow_compare_enabled', false);
        $previousVolumeDelta = config('production.cassa_shadow_compare_volume_delta_mc', 0.0005);
        $previousWasteDelta = config('production.cassa_shadow_compare_waste_delta_percent', 0.5);

        config()->set('production.cassa_shadow_compare_enabled', true);
        config()->set('production.cassa_shadow_compare_volume_delta_mc', 0.0001);
        config()->set('production.cassa_shadow_compare_waste_delta_percent', 0.1);

        Log::spy();

        try {
            $user = User::factory()->admin()->create();

            $costruzione = Costruzione::factory()->create([
                'categoria' => 'cassa',
                'nome' => 'Cassa Shadow Compare Test',
            ]);

            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Parete Lunga Esterna',
                'formula_lunghezza' => 'L',
                'formula_larghezza' => 'H',
                'formula_quantita' => '2',
            ]);
            ComponenteCostruzione::factory()->create([
                'costruzione_id' => $costruzione->id,
                'nome' => 'Parete Corta Interna',
                'formula_lunghezza' => 'W - (2 * T)',
                'formula_larghezza' => 'H',
                'formula_quantita' => '2',
            ]);
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

            $this->assertSame('cassa', data_get($result, 'optimizer.name'));
            $this->assertSame(true, data_get($result, 'trace.shadow_compare.cassa.enabled'));
            $this->assertSame('ok', data_get($result, 'trace.shadow_compare.cassa.status'));
            $this->assertSame(true, data_get($result, 'trace.shadow_compare.cassa.significant'));
            $this->assertSame('legacy-bin-packing', data_get($result, 'trace.shadow_compare.cassa.legacy.optimizer'));

            Log::shouldHaveReceived('warning')
                ->once()
                ->withArgs(function (string $message, array $context) use ($costruzione): bool {
                    return $message === 'production.cassa_optimizer.shadow_delta'
                        && ($context['costruzione_id'] ?? null) === $costruzione->id;
                });
        } finally {
            config()->set('production.cassa_shadow_compare_enabled', $previousCompareEnabled);
            config()->set('production.cassa_shadow_compare_volume_delta_mc', $previousVolumeDelta);
            config()->set('production.cassa_shadow_compare_waste_delta_percent', $previousWasteDelta);
        }
    }

    public function test_cassa_optimizer_can_save_materiali_usati_with_gross_volume_and_mc_pricing(): void
    {
        $user = User::factory()->admin()->create();
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
        ]);

        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'nome' => 'Cassa Standard Save Test',
            'config' => [],
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete Lunga Esterna',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
        ]);
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete Corta Interna',
            'formula_lunghezza' => 'W - (2 * T)',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
        ]);
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
            ->set('larghezza_cm', '100')
            ->set('profondita_cm', '50')
            ->set('altezza_cm', '100')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true)
            ->call('salvaMateriali');

        $rows = $lotto->fresh()->materialiUsati;
        $this->assertCount(7, $rows);
        $this->assertEqualsWithDelta(0.0805, (float) $rows->sum('volume_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.0684, (float) $rows->sum('volume_netto_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.0121, (float) $rows->sum('volume_scarto_mc'), 0.000001);
        $this->assertEqualsWithDelta(32.20, (float) $rows->sum('costo_materiale'), 0.01);
        $this->assertEqualsWithDelta(43.47, (float) $rows->sum('prezzo_vendita'), 0.01);

        $first = $rows->first();
        $this->assertNotNull($first);
        $this->assertStringContainsString('Asse 1:', (string) $first->descrizione);
        $this->assertGreaterThan(0, (float) ($first->scarto_percentuale ?? 0));
    }
}
