<?php

namespace Tests\Unit\Services\Production;

use App\Models\Costruzione;
use App\Services\Production\GabbiaVariantResolver;
use Tests\TestCase;

class GabbiaVariantResolverTest extends TestCase
{
    public function test_resolves_gabbia_standard_to_gabbiasp20(): void
    {
        $costruzione = new Costruzione([
            'categoria' => 'gabbia',
            'slug' => 'gabbia-standard',
            'config' => [],
        ]);

        $resolved = app(GabbiaVariantResolver::class)->resolve($costruzione);

        $this->assertSame('gabbiasp20', $resolved['routine']);
        $this->assertSame('gabbia_sp20', $resolved['family']);
        $this->assertSame('slug/config', $resolved['source']);
        $this->assertTrue($resolved['fallback_to_v1_rectangular']);
    }

    public function test_resolves_gabbia_fondo4_variant_from_slug_or_config(): void
    {
        $fromSlug = new Costruzione([
            'categoria' => 'gabbia',
            'slug' => 'gabbia-sp20-fondo4',
            'config' => [],
        ]);

        $fromConfig = new Costruzione([
            'categoria' => 'gabbia',
            'slug' => 'gabbia-standard',
            'config' => ['fondo4' => true],
        ]);

        $resolver = app(GabbiaVariantResolver::class);

        $this->assertSame('gabbiasp20fondo4', $resolver->resolve($fromSlug)['routine']);
        $this->assertSame('gabbiasp20fondo4', $resolver->resolve($fromConfig)['routine']);
    }

    public function test_resolves_gabbia_legaccio_variants_and_piantoni(): void
    {
        $fourPiantoni = new Costruzione([
            'categoria' => 'gabbia',
            'slug' => 'gabbia-legaccio-standard',
            'config' => [],
        ]);

        $sixPiantoniFondo4 = new Costruzione([
            'categoria' => 'gabbia',
            'slug' => 'gabbia-legaccio',
            'config' => [
                'piantoni' => 6,
                'fondo4' => true,
            ],
        ]);

        $resolver = app(GabbiaVariantResolver::class);

        $this->assertSame('gabbialegaccio4piantoni', $resolver->resolve($fourPiantoni)['routine']);
        $this->assertSame('gabbialegaccio6piantonifondo4', $resolver->resolve($sixPiantoniFondo4)['routine']);
    }

    public function test_allows_explicit_config_routine_override(): void
    {
        $costruzione = new Costruzione([
            'categoria' => 'gabbia',
            'slug' => 'gabbia-standard',
            'config' => ['gabbia_routine' => 'gabbiasp20fondo4'],
        ]);

        $resolved = app(GabbiaVariantResolver::class)->resolve($costruzione);

        $this->assertSame('gabbiasp20fondo4', $resolved['routine']);
        $this->assertSame('config.gabbia_routine', $resolved['source']);
    }
}

