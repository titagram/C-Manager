<?php

namespace Tests\Unit\Services\Production;

use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Services\Production\ComponentRequirementsBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentRequirementsBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('features.formula_engine.enabled', true);
        config()->set('features.formula_engine.rollout_percentage', 100);
        config()->set('features.formula_engine.monitoring.shadow_compare', false);
    }

    public function test_it_builds_calculated_pieces_with_canonical_and_legacy_aliases(): void
    {
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Canonical',
            'formula_lunghezza' => 'W - (2 * T)',
            'formula_larghezza' => 'H',
            'formula_quantita' => 'ceil(L / 500)',
            'is_internal' => true,
            'allow_rotation' => false,
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Legacy Alias',
            'formula_lunghezza' => 'P - (2 * S)',
            'formula_larghezza' => 'A',
            'formula_quantita' => '2',
            'is_internal' => false,
            'allow_rotation' => true,
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'spessore_mm' => 20,
        ]);

        $builder = app(ComponentRequirementsBuilder::class);
        $result = $builder->buildCalculatedPieces(
            costruzione: $costruzione->load('componenti'),
            materiale: $materiale,
            larghezzaCm: 100,
            profonditaCm: 80,
            altezzaCm: 60,
            numeroPezzi: 2
        );

        $this->assertCount(2, $result['pieces']);
        $this->assertSame([], $result['errors']);

        $first = collect($result['pieces'])->firstWhere('description', 'Canonical');
        $this->assertNotNull($first);
        $this->assertEquals(760.0, (float) $first['length']);
        $this->assertEquals(600.0, (float) ($first['width'] ?? 0));
        $this->assertSame(4, (int) $first['quantity']);
        $this->assertTrue((bool) ($first['is_internal'] ?? false));
        $this->assertFalse((bool) ($first['allow_rotation'] ?? true));

        $second = collect($result['pieces'])->firstWhere('description', 'Legacy Alias');
        $this->assertNotNull($second);
        $this->assertEquals(760.0, (float) $second['length']);
        $this->assertEquals(600.0, (float) ($second['width'] ?? 0));
        $this->assertSame(4, (int) $second['quantity']);
        $this->assertFalse((bool) ($second['is_internal'] ?? true));
        $this->assertTrue((bool) ($second['allow_rotation'] ?? false));
    }

    public function test_it_returns_domain_error_when_length_formula_is_non_positive(): void
    {
        [$costruzione, $materiale] = $this->seedSingleComponentScenario(
            formulaLunghezza: 'W - W',
            formulaLarghezza: 'H',
            formulaQuantita: '1'
        );

        $result = app(ComponentRequirementsBuilder::class)->buildCalculatedPieces(
            costruzione: $costruzione->load('componenti'),
            materiale: $materiale,
            larghezzaCm: 100,
            profonditaCm: 80,
            altezzaCm: 60,
            numeroPezzi: 1
        );

        $this->assertSame([], $result['pieces']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('formula_lunghezza', $result['errors'][0]);
        $this->assertStringContainsString('> 0', $result['errors'][0]);
    }

    public function test_it_returns_domain_error_when_width_formula_is_non_positive(): void
    {
        [$costruzione, $materiale] = $this->seedSingleComponentScenario(
            formulaLunghezza: 'L',
            formulaLarghezza: 'H - H',
            formulaQuantita: '1'
        );

        $result = app(ComponentRequirementsBuilder::class)->buildCalculatedPieces(
            costruzione: $costruzione->load('componenti'),
            materiale: $materiale,
            larghezzaCm: 100,
            profonditaCm: 80,
            altezzaCm: 60,
            numeroPezzi: 1
        );

        $this->assertSame([], $result['pieces']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('formula_larghezza', $result['errors'][0]);
        $this->assertStringContainsString('> 0', $result['errors'][0]);
    }

    public function test_it_returns_domain_error_when_quantity_formula_is_fractional(): void
    {
        [$costruzione, $materiale] = $this->seedSingleComponentScenario(
            formulaLunghezza: 'L',
            formulaLarghezza: 'H',
            formulaQuantita: '1.5'
        );

        $result = app(ComponentRequirementsBuilder::class)->buildCalculatedPieces(
            costruzione: $costruzione->load('componenti'),
            materiale: $materiale,
            larghezzaCm: 100,
            profonditaCm: 80,
            altezzaCm: 60,
            numeroPezzi: 1
        );

        $this->assertSame([], $result['pieces']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('formula_quantita', $result['errors'][0]);
        $this->assertStringContainsString('intero', $result['errors'][0]);
    }

    public function test_it_propagates_formula_engine_errors_with_component_context(): void
    {
        [$costruzione, $materiale] = $this->seedSingleComponentScenario(
            formulaLunghezza: 'UNKNOWN + 10',
            formulaLarghezza: 'H',
            formulaQuantita: '1'
        );

        $result = app(ComponentRequirementsBuilder::class)->buildCalculatedPieces(
            costruzione: $costruzione->load('componenti'),
            materiale: $materiale,
            larghezzaCm: 100,
            profonditaCm: 80,
            altezzaCm: 60,
            numeroPezzi: 1
        );

        $this->assertSame([], $result['pieces']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Componente "Test Component"', $result['errors'][0]);
        $this->assertStringContainsString('Variabile sconosciuta', $result['errors'][0]);
    }

    /**
     * @return array{0: Costruzione, 1: Prodotto}
     */
    private function seedSingleComponentScenario(
        string $formulaLunghezza,
        string $formulaLarghezza,
        string $formulaQuantita
    ): array {
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Test Component',
            'formula_lunghezza' => $formulaLunghezza,
            'formula_larghezza' => $formulaLarghezza,
            'formula_quantita' => $formulaQuantita,
        ]);

        $materiale = Prodotto::factory()->legname()->create([
            'spessore_mm' => 20,
        ]);

        return [$costruzione, $materiale];
    }
}
