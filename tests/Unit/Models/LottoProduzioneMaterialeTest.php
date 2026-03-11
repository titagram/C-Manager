<?php

namespace Tests\Unit\Models;

use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\LottoProduzioneMateriale;
use App\Models\Prodotto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LottoProduzioneMaterialeTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_lotto_produzione(): void
    {
        $lotto = LottoProduzione::factory()->create();
        $materiale = LottoProduzioneMateriale::factory()->create([
            'lotto_produzione_id' => $lotto->id,
        ]);

        $this->assertInstanceOf(LottoProduzione::class, $materiale->lottoProduzione);
        $this->assertEquals($lotto->id, $materiale->lottoProduzione->id);
    }

    public function test_belongs_to_lotto_materiale(): void
    {
        $lottoMat = LottoMateriale::factory()->create();
        $materiale = LottoProduzioneMateriale::factory()->create([
            'lotto_materiale_id' => $lottoMat->id,
        ]);

        $this->assertInstanceOf(LottoMateriale::class, $materiale->lottoMateriale);
        $this->assertEquals($lottoMat->id, $materiale->lottoMateriale->id);
    }

    public function test_belongs_to_prodotto(): void
    {
        $prodotto = Prodotto::factory()->create();
        $materiale = LottoProduzioneMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
        ]);

        $this->assertInstanceOf(Prodotto::class, $materiale->prodotto);
    }

    public function test_casts_decimals_correctly(): void
    {
        $materiale = LottoProduzioneMateriale::factory()->create([
            'lunghezza_mm' => 1500.50,
            'volume_mc' => 1.234567,
            'volume_netto_mc' => 1.000000,
            'volume_scarto_mc' => 0.234567,
        ]);

        // Laravel decimal cast returns string
        $this->assertIsString($materiale->lunghezza_mm);
        $this->assertEquals('1500.50', $materiale->lunghezza_mm);
        $this->assertIsString($materiale->volume_mc);
        $this->assertEquals('1.234567', $materiale->volume_mc);
        $this->assertIsString($materiale->volume_netto_mc);
        $this->assertEquals('1.000000', $materiale->volume_netto_mc);
        $this->assertIsString($materiale->volume_scarto_mc);
        $this->assertEquals('0.234567', $materiale->volume_scarto_mc);
    }

}
