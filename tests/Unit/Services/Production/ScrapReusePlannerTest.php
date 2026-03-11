<?php

namespace Tests\Unit\Services\Production;

use App\Models\LottoMateriale;
use App\Models\Prodotto;
use App\Models\Scarto;
use App\Services\Production\ScrapReusePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScrapReusePlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_reuses_residual_of_same_scrap_within_single_run(): void
    {
        $materiale = Prodotto::factory()->legname()->create([
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $materiale->id,
        ]);

        $scarto = Scarto::factory()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 170,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => round((170 * 75 * 35) / 1000000000, 6),
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        $plan = app(ScrapReusePlanner::class)->plan(
            materiale: $materiale,
            pieces: [[
                'id' => 1,
                'description' => 'Listello',
                'length' => 80,
                'width' => 75,
                'quantity' => 2,
            ]],
            kerfMm: 3,
            minReusableLengthMm: 10
        );

        $this->assertSame(2, $plan['required_count']);
        $this->assertSame(2, $plan['matched_count']);
        $this->assertSame([$scarto->id], $plan['used_scrap_ids']);
        $this->assertSame([], $plan['pieces_after_reuse']);
        $this->assertEqualsWithDelta(4.0, (float) data_get($plan, 'source_summaries.0.remaining_length_mm', 0), 0.001);
        $this->assertFalse((bool) data_get($plan, 'source_summaries.0.remaining_riutilizzabile', true));
    }

    public function test_plan_requires_exact_fit_or_length_covering_piece_plus_kerf(): void
    {
        $materiale = Prodotto::factory()->legname()->create([
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $materiale->id,
        ]);

        Scarto::factory()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 82,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => round((82 * 75 * 35) / 1000000000, 6),
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        $exact = Scarto::factory()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 80,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => round((80 * 75 * 35) / 1000000000, 6),
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        $plan = app(ScrapReusePlanner::class)->plan(
            materiale: $materiale,
            pieces: [[
                'id' => 1,
                'description' => 'Taglio',
                'length' => 80,
                'width' => 75,
                'quantity' => 1,
            ]],
            kerfMm: 3,
            minReusableLengthMm: 10
        );

        $this->assertSame(1, $plan['matched_count']);
        $this->assertSame([$exact->id], $plan['used_scrap_ids']);
        $this->assertEqualsWithDelta(0.0, (float) data_get($plan, 'source_summaries.0.remaining_length_mm', 0), 0.001);
    }

    public function test_plan_uses_dimension_derived_volume_when_stored_scrap_volume_is_inconsistent(): void
    {
        $materiale = Prodotto::factory()->legname()->create([
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'peso_specifico_kg_mc' => 360,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $materiale->id,
        ]);

        $scarto = Scarto::factory()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => 0.026250,
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        $plan = app(ScrapReusePlanner::class)->plan(
            materiale: $materiale,
            pieces: [[
                'id' => 1,
                'description' => 'Fondo',
                'length' => 800,
                'width' => 75,
                'quantity' => 1,
            ]],
            kerfMm: 3,
            minReusableLengthMm: 500
        );

        $this->assertSame([$scarto->id], $plan['used_scrap_ids']);
        $this->assertEqualsWithDelta(0.002625, (float) data_get($plan, 'matches.0.volume_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.945, (float) data_get($plan, 'matches.0.peso_kg'), 0.001);
    }
}
