<?php

namespace Tests\Unit\Services\Production;

use App\Enums\Categoria;
use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Services\Production\BancaleConstructionOptimizer;
use App\Services\Production\CassaConstructionOptimizer;
use App\Services\Production\ConstructionOptimizerResolver;
use App\Services\Production\GabbiaConstructionOptimizer;
use App\Services\Production\LegaccioConstructionOptimizer;
use Tests\TestCase;

class ConstructionOptimizerResolverTest extends TestCase
{
    public function test_dispatches_to_cassa_optimizer_for_cassa_category(): void
    {
        [$resolver, $spies] = $this->makeResolverWithSpies();

        $result = $resolver->optimizeOrNull(
            $this->makeCostruzione('cassa'),
            $this->samplePieces(),
            $this->makeCompatibleMaterial(),
            0.0,
            []
        );

        $this->assertSame('cassa', data_get($result, 'optimizer.name'));
        $this->assertSame(1, $spies['cassa']->calls);
        $this->assertSame(0, $spies['gabbia']->calls);
        $this->assertSame(0, $spies['bancale']->calls);
        $this->assertSame(0, $spies['legaccio']->calls);
    }

    public function test_cassa_category_returns_null_when_cassa_mode_is_legacy(): void
    {
        $previousMode = config('production.cassa_optimizer_mode', 'category');
        config()->set('production.cassa_optimizer_mode', 'legacy');

        try {
            [$resolver, $spies] = $this->makeResolverWithSpies();

            $result = $resolver->optimizeOrNull(
                $this->makeCostruzione('cassa'),
                $this->samplePieces(),
                $this->makeCompatibleMaterial(),
                0.0,
                []
            );

            $this->assertNull($result);
            $this->assertSame(0, $spies['cassa']->calls);
            $this->assertSame(0, $spies['gabbia']->calls);
            $this->assertSame(0, $spies['bancale']->calls);
            $this->assertSame(0, $spies['legaccio']->calls);
        } finally {
            config()->set('production.cassa_optimizer_mode', $previousMode);
        }
    }

    public function test_dispatches_to_gabbia_optimizer_for_gabbia_category(): void
    {
        [$resolver, $spies] = $this->makeResolverWithSpies();

        $result = $resolver->optimizeOrNull(
            $this->makeCostruzione('gabbia'),
            $this->samplePieces(),
            $this->makeCompatibleMaterial(),
            0.0,
            []
        );

        $this->assertSame('gabbia', data_get($result, 'optimizer.name'));
        $this->assertSame(0, $spies['cassa']->calls);
        $this->assertSame(1, $spies['gabbia']->calls);
        $this->assertSame(0, $spies['bancale']->calls);
        $this->assertSame(0, $spies['legaccio']->calls);
    }

    public function test_dispatches_to_bancale_optimizer_for_bancale_category(): void
    {
        [$resolver, $spies] = $this->makeResolverWithSpies();

        $result = $resolver->optimizeOrNull(
            $this->makeCostruzione('bancale'),
            $this->samplePieces(),
            $this->makeCompatibleMaterial(),
            0.0,
            []
        );

        $this->assertSame('bancale', data_get($result, 'optimizer.name'));
        $this->assertSame(0, $spies['cassa']->calls);
        $this->assertSame(0, $spies['gabbia']->calls);
        $this->assertSame(1, $spies['bancale']->calls);
        $this->assertSame(0, $spies['legaccio']->calls);
    }

    public function test_dispatches_to_legaccio_optimizer_for_legaccio_category(): void
    {
        [$resolver, $spies] = $this->makeResolverWithSpies();

        $result = $resolver->optimizeOrNull(
            $this->makeCostruzione('legaccio'),
            $this->samplePieces(),
            $this->makeCompatibleMaterial(),
            0.0,
            []
        );

        $this->assertSame('legaccio', data_get($result, 'optimizer.name'));
        $this->assertSame(0, $spies['cassa']->calls);
        $this->assertSame(0, $spies['gabbia']->calls);
        $this->assertSame(0, $spies['bancale']->calls);
        $this->assertSame(1, $spies['legaccio']->calls);
    }

    public function test_unsupported_category_returns_null(): void
    {
        [$resolver, $spies] = $this->makeResolverWithSpies();

        $result = $resolver->optimizeOrNull(
            $this->makeCostruzione('custom'),
            $this->samplePieces(),
            $this->makeCompatibleMaterial(),
            0.0,
            []
        );

        $this->assertNull($result);
        $this->assertSame(0, $spies['cassa']->calls);
        $this->assertSame(0, $spies['gabbia']->calls);
        $this->assertSame(0, $spies['bancale']->calls);
        $this->assertSame(0, $spies['legaccio']->calls);
    }

    public function test_slug_and_config_candidates_fallback_to_category_optimizer(): void
    {
        [$resolver, $spies] = $this->makeResolverWithSpies();

        $result = $resolver->optimizeOrNull(
            $this->makeCostruzione(
                category: 'cassa',
                slug: 'cassa-speciale',
                config: ['optimizer_key' => 'custom-v9']
            ),
            $this->samplePieces(),
            $this->makeCompatibleMaterial(),
            0.0,
            []
        );

        $this->assertSame('cassa', data_get($result, 'optimizer.name'));
        $this->assertSame(1, $spies['cassa']->calls);
    }

    public function test_has_category_optimizer_for_supported_and_unsupported_categories(): void
    {
        [$resolver] = $this->makeResolverWithSpies();

        $this->assertTrue($resolver->hasCategoryOptimizer($this->makeCostruzione('cassa')));
        $this->assertTrue($resolver->hasCategoryOptimizer($this->makeCostruzione('gabbia')));
        $this->assertTrue($resolver->hasCategoryOptimizer($this->makeCostruzione('bancale')));
        $this->assertTrue($resolver->hasCategoryOptimizer($this->makeCostruzione('legaccio')));
        $this->assertFalse($resolver->hasCategoryOptimizer($this->makeCostruzione('altro')));
    }

    /**
     * @return array{0: ConstructionOptimizerResolver, 1: array{
     *   cassa: SpyCassaConstructionOptimizer,
     *   gabbia: SpyGabbiaConstructionOptimizer,
     *   bancale: SpyBancaleConstructionOptimizer,
     *   legaccio: SpyLegaccioConstructionOptimizer
     * }}
     */
    private function makeResolverWithSpies(): array
    {
        $cassa = new SpyCassaConstructionOptimizer();
        $gabbia = new SpyGabbiaConstructionOptimizer();
        $bancale = new SpyBancaleConstructionOptimizer();
        $legaccio = new SpyLegaccioConstructionOptimizer();

        $resolver = new ConstructionOptimizerResolver($cassa, $gabbia, $bancale, $legaccio);

        return [
            $resolver,
            [
                'cassa' => $cassa,
                'gabbia' => $gabbia,
                'bancale' => $bancale,
                'legaccio' => $legaccio,
            ],
        ];
    }

    private function makeCostruzione(string $category, string $slug = 'test-slug', array $config = []): Costruzione
    {
        return new Costruzione([
            'categoria' => $category,
            'slug' => $slug,
            'config' => $config,
        ]);
    }

    private function makeCompatibleMaterial(): Prodotto
    {
        return new Prodotto([
            'categoria' => Categoria::MATERIA_PRIMA->value,
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<int, array{id:int, description:string, length:float, quantity:int, width:float}>
     */
    private function samplePieces(): array
    {
        return [
            [
                'id' => 1,
                'description' => 'Piece 1',
                'length' => 1000.0,
                'width' => 250.0,
                'quantity' => 1,
            ],
        ];
    }
}

class SpyCassaConstructionOptimizer extends CassaConstructionOptimizer
{
    public int $calls = 0;

    public function __construct() {}

    public function optimize(
        Costruzione $costruzione,
        array $panelPieces,
        Prodotto $materiale,
        float $kerfMm,
        array $context = []
    ): array {
        $this->calls++;

        return [
            'optimizer' => ['name' => 'cassa'],
            'bins' => [],
            'total_bins' => 0,
        ];
    }
}

class SpyGabbiaConstructionOptimizer extends GabbiaConstructionOptimizer
{
    public int $calls = 0;

    public function __construct() {}

    public function optimize(
        Costruzione $costruzione,
        array $pieces,
        Prodotto $materiale,
        float $kerfMm,
        array $context = []
    ): array {
        $this->calls++;

        return [
            'optimizer' => ['name' => 'gabbia'],
            'bins' => [],
            'total_bins' => 0,
        ];
    }
}

class SpyBancaleConstructionOptimizer extends BancaleConstructionOptimizer
{
    public int $calls = 0;

    public function __construct() {}

    public function optimize(
        Costruzione $costruzione,
        array $pieces,
        Prodotto $materiale,
        float $kerfMm,
        array $context = []
    ): array {
        $this->calls++;

        return [
            'optimizer' => ['name' => 'bancale'],
            'bins' => [],
            'total_bins' => 0,
        ];
    }
}

class SpyLegaccioConstructionOptimizer extends LegaccioConstructionOptimizer
{
    public int $calls = 0;

    public function __construct() {}

    public function optimize(
        Costruzione $costruzione,
        array $pieces,
        Prodotto $materiale,
        float $kerfMm,
        array $context = []
    ): array {
        $this->calls++;

        return [
            'optimizer' => ['name' => 'legaccio'],
            'bins' => [],
            'total_bins' => 0,
        ];
    }
}
