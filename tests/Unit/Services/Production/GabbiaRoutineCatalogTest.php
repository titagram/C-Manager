<?php

namespace Tests\Unit\Services\Production;

use App\Services\Production\GabbiaRoutineCatalog;
use Tests\TestCase;

class GabbiaRoutineCatalogTest extends TestCase
{
    public function test_returns_expected_profile_for_fondo4_routine(): void
    {
        $catalog = app(GabbiaRoutineCatalog::class);

        $profile = $catalog->profile('gabbiasp20fondo4');

        $this->assertSame('gabbia_sp20', $profile['family']);
        $this->assertTrue((bool) $profile['fondo4']);
        $this->assertEqualsWithDelta(40.0, (float) $profile['row8_section_mm'], 0.0001);
    }

    public function test_resolves_routine_from_variant_flags_without_string_parsing(): void
    {
        $catalog = app(GabbiaRoutineCatalog::class);

        $this->assertSame(
            'gabbialegaccio6piantonifondo4',
            $catalog->resolveFromVariantFlags(true, true, 6)
        );

        $this->assertSame(
            'gabbialegaccio4piantoni',
            $catalog->resolveFromVariantFlags(true, false, 4)
        );

        $this->assertSame(
            'gabbiasp20',
            $catalog->resolveFromVariantFlags(false, false, 0)
        );
    }
}

