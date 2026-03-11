<?php

namespace Tests\Unit\Services;

use App\Enums\StatoLottoProduzione;
use App\Models\Cliente;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\LottoProduzioneMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\Scarto;
use App\Models\User;
use App\Services\ProductionLotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionLotServiceScrapTrackingTest extends TestCase
{
    use RefreshDatabase;

    private ProductionLotService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProductionLotService::class);
        $this->user = User::factory()->create();
    }

    public function test_registra_scarti_creates_scarto_records_for_materials_with_scrap(): void
    {
        // Arrange
        $prodotto = Prodotto::factory()->create(['soggetto_fitok' => true]);
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        // Create LottoProduzioneMateriale with scrap
        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 18,
            'quantita_pezzi' => 10,
            'volume_mc' => (3000 * 100 * 18 * 10) / 1000000000,
            'scarto_totale_mm' => 600, // 600mm of scrap
            'ordine' => 1,
        ]);

        // Act
        $scarti = $this->service->registraScarti($lotto);

        // Assert
        $this->assertCount(1, $scarti);

        $scarto = $scarti->first();
        $this->assertEquals($lotto->id, $scarto->lotto_produzione_id);
        $this->assertEquals($lottoMateriale->id, $scarto->lotto_materiale_id);
        $this->assertEquals(600, $scarto->lunghezza_mm);
        $this->assertEquals(100, $scarto->larghezza_mm);
        $this->assertEquals(18, $scarto->spessore_mm);
        $this->assertTrue($scarto->riutilizzabile); // >= 500mm
        $this->assertFalse($scarto->riutilizzato);
        $this->assertStringContainsString($lotto->codice_lotto, $scarto->note);
    }

    public function test_registra_scarti_calculates_volume_correctly(): void
    {
        // Arrange
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        // Create material with specific dimensions
        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale',
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 200,
            'spessore_mm' => 20,
            'quantita_pezzi' => 5,
            'volume_mc' => (2000 * 200 * 20 * 5) / 1000000000,
            'scarto_totale_mm' => 800,
            'ordine' => 1,
        ]);

        // Act
        $scarti = $this->service->registraScarti($lotto);

        // Assert
        $scarto = $scarti->first();
        // Volume = 800mm * 200mm * 20mm = 3,200,000 mm³ = 0.0032 m³
        $expectedVolume = (800 * 200 * 20) / 1000000000;
        $this->assertEquals(round($expectedVolume, 6), (float) $scarto->volume_mc);
    }

    public function test_registra_scarti_marks_small_scraps_as_not_reusable(): void
    {
        // Arrange
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        // Create material with small scrap
        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 100,
            'spessore_mm' => 18,
            'quantita_pezzi' => 2,
            'volume_mc' => (1000 * 100 * 18 * 2) / 1000000000,
            'scarto_totale_mm' => 300, // Less than 500mm
            'ordine' => 1,
        ]);

        // Act
        $scarti = $this->service->registraScarti($lotto);

        // Assert
        $scarto = $scarti->first();
        $this->assertFalse($scarto->riutilizzabile);
    }

    public function test_registra_scarti_does_not_create_records_for_zero_scrap(): void
    {
        // Arrange
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        // Create material with NO scrap
        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 100,
            'spessore_mm' => 18,
            'quantita_pezzi' => 2,
            'volume_mc' => (1000 * 100 * 18 * 2) / 1000000000,
            'scarto_totale_mm' => 0, // No scrap
            'ordine' => 1,
        ]);

        // Act
        $scarti = $this->service->registraScarti($lotto);

        // Assert
        $this->assertCount(0, $scarti);
        $this->assertEquals(0, Scarto::count());
    }

    public function test_registra_scarti_handles_multiple_materials(): void
    {
        // Arrange
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale1 = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);
        $lottoMateriale2 = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        // Create multiple materials with different scrap amounts
        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale1->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale 1',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 18,
            'quantita_pezzi' => 5,
            'volume_mc' => (3000 * 100 * 18 * 5) / 1000000000,
            'scarto_totale_mm' => 700,
            'ordine' => 1,
        ]);

        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale2->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale 2',
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 150,
            'spessore_mm' => 20,
            'quantita_pezzi' => 3,
            'volume_mc' => (2000 * 150 * 20 * 3) / 1000000000,
            'scarto_totale_mm' => 400,
            'ordine' => 2,
        ]);

        // Act
        $scarti = $this->service->registraScarti($lotto);

        // Assert
        $this->assertCount(2, $scarti);
        $this->assertEquals(2, Scarto::count());

        // Verify both materials have scrap records
        $this->assertTrue(
            $scarti->contains('lotto_materiale_id', $lottoMateriale1->id)
        );
        $this->assertTrue(
            $scarti->contains('lotto_materiale_id', $lottoMateriale2->id)
        );
    }

    public function test_conferma_lotto_calls_registra_scarti(): void
    {
        // Arrange
        $prodotto = Prodotto::factory()->create(['soggetto_fitok' => true]);
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        // Create stock movement
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => 'carico',
            'quantita' => 100,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        // Create consumption (for inventory check)
        \App\Models\ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'quantita' => 10,
        ]);

        // Create material usage with scrap
        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 18,
            'quantita_pezzi' => 10,
            'volume_mc' => (3000 * 100 * 18 * 10) / 1000000000,
            'scarto_totale_mm' => 550,
            'ordine' => 1,
        ]);

        // Act
        $this->service->confermaLotto($lotto, $this->user);

        // Assert
        $lotto->refresh();

        // Verify lot is completed
        $this->assertEquals(StatoLottoProduzione::COMPLETATO, $lotto->stato);

        // Verify scrap was created
        $this->assertEquals(1, Scarto::count());
        $scarto = Scarto::first();
        $this->assertEquals($lotto->id, $scarto->lotto_produzione_id);
        $this->assertEquals(550, $scarto->lunghezza_mm);
    }

    public function test_registra_scarti_returns_empty_collection_when_no_materials(): void
    {
        // Arrange
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        // Act
        $scarti = $this->service->registraScarti($lotto);

        // Assert
        $this->assertCount(0, $scarti);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $scarti);
    }

    public function test_registra_scarti_handles_exact_500mm_threshold(): void
    {
        // Arrange
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        // Create material with exactly 500mm scrap
        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 100,
            'spessore_mm' => 18,
            'quantita_pezzi' => 2,
            'volume_mc' => (1000 * 100 * 18 * 2) / 1000000000,
            'scarto_totale_mm' => 500, // Exactly 500mm
            'ordine' => 1,
        ]);

        // Act
        $scarti = $this->service->registraScarti($lotto);

        // Assert
        $scarto = $scarti->first();
        $this->assertTrue($scarto->riutilizzabile); // >= 500mm should be reusable
    }

    public function test_registra_scarti_uses_configurable_reuse_threshold(): void
    {
        $originalThreshold = config('production.scrap_reusable_min_length_mm');
        config()->set('production.scrap_reusable_min_length_mm', 900);

        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale',
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 100,
            'spessore_mm' => 18,
            'quantita_pezzi' => 1,
            'volume_mc' => (2000 * 100 * 18) / 1000000000,
            'scarto_totale_mm' => 800,
            'ordine' => 1,
        ]);

        $scarti = $this->service->registraScarti($lotto);

        $scarto = $scarti->first();
        $this->assertFalse($scarto->riutilizzabile);

        config()->set('production.scrap_reusable_min_length_mm', $originalThreshold);
    }

    public function test_registra_scarti_creates_residual_scrap_from_reused_scrap_trace(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        $lottoOrigine = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::COMPLETATO,
        ]);

        $scartoOrigine = Scarto::factory()->create([
            'lotto_produzione_id' => $lottoOrigine->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 100,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => round((100 * 75 * 35) / 1000000000, 6),
            'riutilizzabile' => true,
            'riutilizzato' => true,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'optimizer_result' => [
                'version' => 'v2',
                'trace' => [
                    'scrap_reuse' => [
                        'source_summaries' => [[
                            'source_scrap_id' => $scartoOrigine->id,
                            'lotto_materiale_id' => $lottoMateriale->id,
                            'source_lotto_produzione_code' => $lottoOrigine->codice_lotto,
                            'remaining_length_mm' => 17,
                            'remaining_width_mm' => 75,
                            'remaining_thickness_mm' => 35,
                        ]],
                    ],
                ],
            ],
        ]);

        $scarti = $this->service->registraScarti($lotto);

        $this->assertCount(1, $scarti);

        $residuo = $scarti->first();
        $this->assertSame($lotto->id, $residuo->lotto_produzione_id);
        $this->assertSame($lottoMateriale->id, $residuo->lotto_materiale_id);
        $this->assertEqualsWithDelta(17.0, (float) $residuo->lunghezza_mm, 0.001);
        $this->assertEqualsWithDelta(75.0, (float) $residuo->larghezza_mm, 0.001);
        $this->assertEqualsWithDelta(35.0, (float) $residuo->spessore_mm, 0.001);
        $this->assertFalse((bool) $residuo->riutilizzabile);
        $this->assertStringContainsString('Residuo da riuso scarto', (string) $residuo->note);
    }
}
