<?php

namespace Tests\Unit\Services\Production;

use App\Models\Costruzione;
use App\Services\Production\BancaleVariantResolver;
use Tests\TestCase;

class BancaleVariantResolverTest extends TestCase
{
    public function test_resolves_standard_bancale_from_slug(): void
    {
        $resolver = app(BancaleVariantResolver::class);

        $costruzione = new Costruzione([
            'categoria' => 'bancale',
            'slug' => 'bancale-standard',
            'config' => [],
        ]);

        $variant = $resolver->resolve($costruzione);
        $this->assertSame('bancale', $variant['routine']);
        $this->assertSame('bancale_standard', $variant['family']);
        $this->assertSame('slug', $variant['source']);
    }

    public function test_resolves_perimetrale_from_slug_and_explicit_config_override(): void
    {
        $resolver = app(BancaleVariantResolver::class);

        $perimetrale = new Costruzione([
            'categoria' => 'bancale',
            'slug' => 'perimetrale-standard',
            'config' => [],
        ]);
        $this->assertSame('perimetrale', $resolver->resolve($perimetrale)['routine']);

        $configured = new Costruzione([
            'categoria' => 'bancale',
            'slug' => 'bancale-standard',
            'config' => ['bancale_routine' => 'perimetrale'],
        ]);
        $variant = $resolver->resolve($configured);
        $this->assertSame('perimetrale', $variant['routine']);
        $this->assertSame('config.bancale_routine', $variant['source']);
    }
}

