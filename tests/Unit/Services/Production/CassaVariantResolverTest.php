<?php

namespace Tests\Unit\Services\Production;

use App\Models\Costruzione;
use App\Services\Production\CassaVariantResolver;
use Tests\TestCase;

class CassaVariantResolverTest extends TestCase
{
    public function test_resolves_excel_sp25_fondo40_from_config_optimizer_key(): void
    {
        $resolver = app(CassaVariantResolver::class);

        $resolved = $resolver->resolve(new Costruzione([
            'categoria' => 'cassa',
            'slug' => 'cassa-custom',
            'config' => [
                'optimizer_key' => 'excel_sp25_fondo40',
            ],
        ]));

        $this->assertSame('cassasp25fondo40', $resolved['routine']);
        $this->assertTrue((bool) $resolved['uses_excel_builder']);
        $this->assertSame(['base', 'fondo'], collect($resolved['required_profiles'])->pluck('key')->all());
    }

    public function test_falls_back_to_geometric_mode_for_standard_cassa_without_config(): void
    {
        $resolver = app(CassaVariantResolver::class);

        $resolved = $resolver->resolve(new Costruzione([
            'categoria' => 'cassa',
            'slug' => 'cassa-standard',
            'config' => [],
        ]));

        $this->assertSame('geometrica', $resolved['routine']);
        $this->assertFalse((bool) $resolved['uses_excel_builder']);
        $this->assertSame(['base'], collect($resolved['required_profiles'])->pluck('key')->all());
    }
}
