<?php

namespace Tests\Unit\Services;

use App\Models\Prodotto;
use App\Services\CassaOptimizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CassaOptimizerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_calculations_scale_with_quantita(): void
    {
        $materiale = Prodotto::factory()->create([
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 10,
        ]);

        $optimizer = new CassaOptimizerService();

        // Calculate for 1 cassa
        $result1 = $optimizer->calcola(
            larghezza_mm: 800,
            profondita_mm: 600,
            altezza_mm: 400,
            quantita: 1,
            materiale: $materiale
        );

        // Calculate for 5 casse
        $result5 = $optimizer->calcola(
            larghezza_mm: 800,
            profondita_mm: 600,
            altezza_mm: 400,
            quantita: 5,
            materiale: $materiale
        );

        // Verify that pezzi_totali scales with quantita
        foreach ($result1['piano_taglio'] as $index => $faccia1) {
            $faccia5 = $result5['piano_taglio'][$index];

            // pezzi_totali for 5 casse should be 5x the pezzi_totali for 1 cassa
            $this->assertEquals(
                $faccia1['pezzi_totali'] * 5,
                $faccia5['pezzi_totali'],
                "Pezzi totali should scale with quantita for {$faccia1['tipo_faccia']}"
            );
        }

        // Verify total assi is at least as much as for 1 cassa
        // Note: Due to optimizer efficiency, 5 casse might need the same or only slightly
        // more assi than 1 cassa, as multiple pieces fit on a single board
        $this->assertGreaterThanOrEqual(
            $result1['riepilogo']['totale_assi_necessarie'],
            $result5['riepilogo']['totale_assi_necessarie'],
            'Assi for 5 casse should be at least as many as for 1 cassa'
        );
    }

    public function test_it_applies_kerf_only_between_real_cuts(): void
    {
        $materiale = Prodotto::factory()->create([
            'lunghezza_mm' => 3010,
            'larghezza_mm' => 2000,
            'spessore_mm' => 10,
        ]);

        $optimizer = new CassaOptimizerService();
        $result = $optimizer->calcola(
            larghezza_mm: 500,
            profondita_mm: 400,
            altezza_mm: 1000,
            quantita: 1,
            materiale: $materiale
        );

        $latoEsterno = collect($result['piano_taglio'])
            ->firstWhere('tipo_faccia', 'lato_esterno');

        $this->assertNotNull($latoEsterno);
        $this->assertSame(3, $latoEsterno['pezzi_per_asse_lunghezza']);
        $this->assertEquals(1005.0, $latoEsterno['scarto_per_asse_mm']);
    }

    public function test_it_calculates_total_waste_with_partial_last_board(): void
    {
        $materiale = Prodotto::factory()->create([
            'lunghezza_mm' => 3010,
            'larghezza_mm' => 2000,
            'spessore_mm' => 10,
        ]);

        $optimizer = new CassaOptimizerService();
        $result = $optimizer->calcola(
            larghezza_mm: 500,
            profondita_mm: 400,
            altezza_mm: 1000,
            quantita: 2,
            materiale: $materiale
        );

        $latoEsterno = collect($result['piano_taglio'])
            ->firstWhere('tipo_faccia', 'lato_esterno');

        $this->assertNotNull($latoEsterno);
        $this->assertSame(2, $latoEsterno['assi_necessarie']);
        $this->assertEquals(2010.0, $latoEsterno['scarto_totale_mm']);
        $this->assertEquals(1005.0, $latoEsterno['scarto_per_asse_mm']);
    }

    public function test_it_throws_when_piece_cannot_fit_in_board_length(): void
    {
        $materiale = Prodotto::factory()->create([
            'lunghezza_mm' => 900,
            'larghezza_mm' => 2000,
            'spessore_mm' => 10,
        ]);

        $optimizer = new CassaOptimizerService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non entra nella lunghezza asse disponibile');

        $optimizer->calcola(
            larghezza_mm: 500,
            profondita_mm: 400,
            altezza_mm: 1000,
            quantita: 1,
            materiale: $materiale
        );
    }
}
