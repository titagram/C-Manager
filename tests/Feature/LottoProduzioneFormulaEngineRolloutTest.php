<?php

namespace Tests\Feature;

use App\Livewire\Forms\LottoProduzioneForm;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LottoProduzioneFormulaEngineRolloutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_it_uses_new_formula_engine_when_rollout_is_100_percent(): void
    {
        config()->set('features.formula_engine.enabled', true);
        config()->set('features.formula_engine.rollout_percentage', 100);
        config()->set('features.formula_engine.monitoring.shadow_compare', false);
        config()->set('production.cassa_optimizer_mode', 'category');

        [$costruzione, $materiale] = $this->seedFormulaScenario();

        $component = Livewire::test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $materiale->id)
            ->set('larghezza_cm', '100')
            ->set('profondita_cm', '80')
            ->set('altezza_cm', '60')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true);

        $result = $component->get('optimizerResult');
        $this->assertIsArray($result);
        $this->assertSame('cassa', (string) data_get($result, 'optimizer.name'));
        $this->assertGreaterThan(0, (int) data_get($result, 'total_bins', 0));
    }

    public function test_it_uses_legacy_formula_engine_when_rollout_is_0_percent(): void
    {
        config()->set('features.formula_engine.enabled', true);
        config()->set('features.formula_engine.rollout_percentage', 0);
        config()->set('features.formula_engine.monitoring.shadow_compare', false);

        [$costruzione, $materiale] = $this->seedFormulaScenario();

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

    /**
     * @return array{0: Costruzione, 1: Prodotto}
     */
    private function seedFormulaScenario(): array
    {
        $costruzione = Costruzione::create([
            'categoria' => 'cassa',
            'nome' => 'Rollout Formula Test',
            'slug' => 'rollout-formula-test',
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
            'formula_larghezza' => 'H',
            'formula_quantita' => 'ceil(L / 500)',
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
        ]);

        return [$costruzione, $materiale];
    }
}
