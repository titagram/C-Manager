<?php

namespace Tests\Unit\Services\Production;

use App\Models\Costruzione;
use App\Services\Production\LegaccioVariantResolver;
use Tests\TestCase;

class LegaccioVariantResolverTest extends TestCase
{
    public function test_resolves_legacci224x60_from_slug_or_explicit_config(): void
    {
        $resolver = app(LegaccioVariantResolver::class);

        $fromSlug = new Costruzione([
            'categoria' => 'legaccio',
            'slug' => 'legacci-224x60',
            'config' => [],
        ]);
        $this->assertSame('legacci224x60', $resolver->resolve($fromSlug)['routine']);

        $fromConfig = new Costruzione([
            'categoria' => 'legaccio',
            'slug' => 'legaccio-standard',
            'config' => ['legaccio_routine' => 'legacci224x60'],
        ]);

        $variant = $resolver->resolve($fromConfig);
        $this->assertSame('legacci224x60', $variant['routine']);
        $this->assertSame('config.legaccio_routine', $variant['source']);
    }

    public function test_falls_back_to_generic_when_no_known_routine_is_detected(): void
    {
        $resolver = app(LegaccioVariantResolver::class);

        $fallback = new Costruzione([
            'categoria' => 'legaccio',
            'slug' => 'legaccio-standard',
            'config' => [],
        ]);

        $variant = $resolver->resolve($fallback);
        $this->assertSame('legaccio-generic-v1', $variant['routine']);
        $this->assertSame('legaccio_generic', $variant['family']);
    }

    public function test_falls_back_to_generic_when_config_routine_is_not_supported(): void
    {
        $resolver = app(LegaccioVariantResolver::class);

        $unsupported = new Costruzione([
            'categoria' => 'legaccio',
            'slug' => 'legaccio-custom',
            'config' => ['legaccio_routine' => 'cassa-legaccio-4piantoni'],
        ]);

        $variant = $resolver->resolve($unsupported);
        $this->assertSame('legaccio-generic-v1', $variant['routine']);
        $this->assertSame('legaccio_generic', $variant['family']);
        $this->assertSame('config.legaccio_routine.unsupported', $variant['source']);
    }
}
