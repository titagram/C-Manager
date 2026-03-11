<?php

namespace Tests\Unit\Models;

use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use App\Models\Scarto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScartoTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_lotto_produzione(): void
    {
        $lotto = LottoProduzione::factory()->create();
        $scarto = Scarto::factory()->create(['lotto_produzione_id' => $lotto->id]);

        $this->assertInstanceOf(LottoProduzione::class, $scarto->lottoProduzione);
    }

    public function test_belongs_to_lotto_materiale(): void
    {
        $lottoMat = LottoMateriale::factory()->create();
        $scarto = Scarto::factory()->create(['lotto_materiale_id' => $lottoMat->id]);

        $this->assertInstanceOf(LottoMateriale::class, $scarto->lottoMateriale);
    }

    public function test_riutilizzabile_is_boolean(): void
    {
        $scarto = Scarto::factory()->create(['riutilizzabile' => true]);

        $this->assertIsBool($scarto->riutilizzabile);
        $this->assertTrue($scarto->riutilizzabile);
    }

    public function test_calculated_volume_mc_uses_dimensions_even_when_stored_value_is_wrong(): void
    {
        $scarto = Scarto::factory()->create([
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => 0.026250,
        ]);

        $this->assertEqualsWithDelta(0.002625, $scarto->calculatedVolumeMc(), 0.000001);
    }

    public function test_estimated_weight_kg_uses_dimension_derived_volume(): void
    {
        $prodotto = Prodotto::factory()->create([
            'peso_specifico_kg_mc' => 360,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
        ]);

        $scarto = Scarto::factory()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => 0.026250,
        ]);

        $scarto->load('lottoMateriale.prodotto');

        $this->assertEqualsWithDelta(0.945, (float) $scarto->estimatedWeightKg(), 0.001);
    }
}
