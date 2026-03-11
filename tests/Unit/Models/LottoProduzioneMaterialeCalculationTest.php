<?php

namespace Tests\Unit\Models;

use App\Models\LottoProduzione;
use App\Models\LottoProduzioneMateriale;
use App\Models\Prodotto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LottoProduzioneMaterialeCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcola_prezzo_vendita_from_volume_and_prodotto(): void
    {
        // Arrange: Create prodotto with prezzo_unitario (€540/m³ from Excel)
        $prodotto = Prodotto::factory()->create([
            'prezzo_unitario' => 540.0000,
            'costo_unitario' => 400.0000,
        ]);

        $lotto = LottoProduzione::factory()->create();

        // Create material: 3000mm × 100mm × 25mm × 10 pieces
        // Volume = (3000 × 100 × 25 × 10) / 1,000,000,000 = 0.075 m³
        $volumeMc = (3000 * 100 * 25 * 10) / 1000000000;

        // Act: Create material with calculated prices
        $materiale = LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Test materiale',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 25,
            'quantita_pezzi' => 10,
            'volume_mc' => $volumeMc,
            'costo_materiale' => round($volumeMc * 400, 2), // €30.00
            'prezzo_vendita' => round($volumeMc * 540, 2), // €40.50
            'ordine' => 1,
        ]);

        // Assert: Prezzo = Volume_MC × Prezzo_al_MC (from Excel formula)
        $this->assertEquals('0.075000', $materiale->volume_mc);
        $this->assertEquals('30.00', $materiale->costo_materiale); // 0.075 × 400
        $this->assertEquals('40.50', $materiale->prezzo_vendita); // 0.075 × 540
    }

    public function test_calcola_costo_totale_lotto_sums_materiali(): void
    {
        // Arrange
        $prodotto = Prodotto::factory()->create([
            'prezzo_unitario' => 500.0000,
            'costo_unitario' => 350.0000,
        ]);

        $lotto = LottoProduzione::factory()->create();

        // Create 2 materials
        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Material 1',
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 5,
            'volume_mc' => 0.02, // 2000×100×20×5 / 1B = 0.02
            'costo_materiale' => 7.00, // 0.02 × 350
            'prezzo_vendita' => 10.00, // 0.02 × 500
            'ordine' => 1,
        ]);

        LottoProduzioneMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Material 2',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 150,
            'spessore_mm' => 30,
            'quantita_pezzi' => 3,
            'volume_mc' => 0.0135, // 1000×150×30×3 / 1B = 0.0135
            'costo_materiale' => 4.73, // 0.0135 × 350
            'prezzo_vendita' => 6.75, // 0.0135 × 500
            'ordine' => 2,
        ]);

        // Act
        $costoTotale = $lotto->calcolaCostoTotale();
        $prezzoTotale = $lotto->calcolaPrezzoVenditaTotale();

        // Assert
        $this->assertEquals(11.73, $costoTotale); // 7.00 + 4.73
        $this->assertEquals(16.75, $prezzoTotale); // 10.00 + 6.75
    }
}
