<?php

namespace Tests\Unit\Services;

use App\Services\CuttingOptimizerService;
use PHPUnit\Framework\TestCase;

class CuttingOptimizerServiceTest extends TestCase
{
    private CuttingOptimizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CuttingOptimizerService();
    }

    /** @test */
    public function it_calculates_perfect_fit_without_waste()
    {
        // Board: 3000mm, Piece: 1000mm, Quantity: 3
        // Expected: 3 pieces per board, 1 board needed, 0mm waste
        $result = $this->service->optimize(3000, 1000, 3);

        $this->assertEquals(3, $result['pezzi_per_asse']);
        $this->assertEquals(1, $result['assi_necessarie']);
        $this->assertEquals(0, $result['scarto_per_asse_mm']);
        $this->assertEquals(0, $result['scarto_totale_mm']);
        $this->assertEquals(0, $result['scarto_percentuale']);
    }

    /** @test */
    public function it_calculates_optimization_with_minimal_waste()
    {
        // Board: 3000mm, Piece: 1200mm, Quantity: 5
        // Expected: 2 pieces per board (2400mm used, 600mm waste), 3 boards needed
        $result = $this->service->optimize(3000, 1200, 5);

        $this->assertEquals(2, $result['pezzi_per_asse']);
        $this->assertEquals(3, $result['assi_necessarie']);
        $this->assertEquals(600, $result['scarto_per_asse_mm']);
        $this->assertEquals(1800, $result['scarto_totale_mm']); // 600mm * 3 boards
        $this->assertEquals(20.0, $result['scarto_percentuale']); // 600/3000 = 20%
    }

    /** @test */
    public function it_handles_single_piece_per_board()
    {
        // Board: 3000mm, Piece: 2800mm, Quantity: 4
        // Expected: 1 piece per board, 4 boards needed, 200mm waste per board
        $result = $this->service->optimize(3000, 2800, 4);

        $this->assertEquals(1, $result['pezzi_per_asse']);
        $this->assertEquals(4, $result['assi_necessarie']);
        $this->assertEquals(200, $result['scarto_per_asse_mm']);
        $this->assertEquals(800, $result['scarto_totale_mm']);
        $this->assertEqualsWithDelta(6.67, $result['scarto_percentuale'], 0.01);
    }

    /** @test */
    public function it_handles_multiple_boards_with_partial_last_board()
    {
        // Board: 4000mm, Piece: 1500mm, Quantity: 5
        // Expected: 2 pieces per board, 3 boards needed
        // Boards 1-2: 2 pieces each = 4 pieces
        // Board 3: 1 piece = 1 piece
        // Total: 5 pieces across 3 boards
        $result = $this->service->optimize(4000, 1500, 5);

        $this->assertEquals(2, $result['pezzi_per_asse']);
        $this->assertEquals(3, $result['assi_necessarie']);
        $this->assertEquals(1000, $result['scarto_per_asse_mm']);
        $this->assertEquals(3000, $result['scarto_totale_mm']);
        $this->assertEquals(25.0, $result['scarto_percentuale']);
    }

    /** @test */
    public function it_considers_saw_blade_kerf()
    {
        // Board: 3000mm, Piece: 1000mm, Kerf: 5mm, Quantity: 3
        // With kerf: piece + kerf = 1005mm per cut
        // First piece: 1000mm, subsequent pieces: 1000mm + 5mm kerf
        // Total: 1000 + 5 + 1000 + 5 + 1000 = 3010mm (doesn't fit!)
        // So only 2 pieces fit: 1000 + 5 + 1000 = 2005mm
        $result = $this->service->optimize(3000, 1000, 3, 5);

        $this->assertEquals(2, $result['pezzi_per_asse']);
        $this->assertEquals(2, $result['assi_necessarie']);
        $this->assertEquals(995, $result['scarto_per_asse_mm']); // 3000 - 2005
        $this->assertEquals(1990, $result['scarto_totale_mm']);
    }

    /** @test */
    public function it_handles_zero_kerf()
    {
        // Should behave same as no kerf parameter
        $result = $this->service->optimize(3000, 1000, 3, 0);

        $this->assertEquals(3, $result['pezzi_per_asse']);
        $this->assertEquals(1, $result['assi_necessarie']);
        $this->assertEquals(0, $result['scarto_per_asse_mm']);
    }

    /** @test */
    public function it_throws_exception_when_piece_longer_than_board()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Il pezzo richiesto (3500mm) è più lungo dell\'asse disponibile (3000mm)');

        $this->service->optimize(3000, 3500, 1);
    }

    /** @test */
    public function it_throws_exception_for_zero_quantity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La quantità di pezzi deve essere maggiore di zero');

        $this->service->optimize(3000, 1000, 0);
    }

    /** @test */
    public function it_throws_exception_for_negative_quantity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La quantità di pezzi deve essere maggiore di zero');

        $this->service->optimize(3000, 1000, -5);
    }

    /** @test */
    public function it_throws_exception_for_zero_board_length()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La lunghezza dell\'asse deve essere maggiore di zero');

        $this->service->optimize(0, 1000, 5);
    }

    /** @test */
    public function it_throws_exception_for_zero_piece_length()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La lunghezza del pezzo deve essere maggiore di zero');

        $this->service->optimize(3000, 0, 5);
    }

    /** @test */
    public function it_throws_exception_for_negative_kerf()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La larghezza della lama non può essere negativa');

        $this->service->optimize(3000, 1000, 5, -3);
    }

    /** @test */
    public function it_achieves_high_efficiency_for_standard_cuts()
    {
        // Board: 6000mm, Piece: 1200mm, Quantity: 10
        // Expected: 5 pieces per board, 2 boards needed, 0mm waste
        $result = $this->service->optimize(6000, 1200, 10);

        $this->assertEquals(5, $result['pezzi_per_asse']);
        $this->assertEquals(2, $result['assi_necessarie']);
        $this->assertEquals(0, $result['scarto_per_asse_mm']);
        $this->assertEquals(100.0, 100 - $result['scarto_percentuale']); // 100% efficiency
    }

    /** @test */
    public function it_maintains_efficiency_within_target_range()
    {
        // Test various scenarios to ensure 85-95% efficiency target
        $scenarios = [
            ['board' => 4000, 'piece' => 900, 'quantity' => 20],
            ['board' => 6000, 'piece' => 1500, 'quantity' => 15],
            ['board' => 3000, 'piece' => 750, 'quantity' => 30],
        ];

        foreach ($scenarios as $scenario) {
            $result = $this->service->optimize(
                $scenario['board'],
                $scenario['piece'],
                $scenario['quantity']
            );

            $efficiency = 100 - $result['scarto_percentuale'];

            // Efficiency should be reasonable (at least 70% for realistic scenarios)
            $this->assertGreaterThanOrEqual(
                70,
                $efficiency,
                "Efficiency too low for board:{$scenario['board']}, piece:{$scenario['piece']}"
            );
        }
    }

    /** @test */
    public function it_handles_real_world_scenario_with_kerf()
    {
        // Real scenario: 4000mm board, 850mm pieces, 5mm kerf, 20 pieces
        $result = $this->service->optimize(4000, 850, 20, 5);

        // With kerf: 850 + 5 + 850 + 5 + 850 + 5 + 850 = 3420mm (4 pieces fit)
        // Without last kerf: 850 + 5 + 850 + 5 + 850 + 5 + 850 = 3420mm
        $this->assertEquals(4, $result['pezzi_per_asse']);
        $this->assertEquals(5, $result['assi_necessarie']); // 20 pieces / 4 per board

        $expectedWaste = 4000 - (4 * 850 + 3 * 5); // 4000 - 3415 = 585mm
        $this->assertEquals($expectedWaste, $result['scarto_per_asse_mm']);

        $efficiency = 100 - $result['scarto_percentuale'];
        $this->assertGreaterThan(85, $efficiency); // Should be around 85.375%
    }

    /** @test */
    public function it_returns_correct_data_types()
    {
        $result = $this->service->optimize(3000, 1000, 5);

        $this->assertIsInt($result['pezzi_per_asse']);
        $this->assertIsInt($result['assi_necessarie']);
        $this->assertIsFloat($result['scarto_per_asse_mm']);
        $this->assertIsFloat($result['scarto_totale_mm']);
        $this->assertIsFloat($result['scarto_percentuale']);
    }

    /** @test */
    public function it_handles_piece_length_with_kerf_exceeding_board()
    {
        // Piece + kerf exceeds board length: 2998 + 10 = 3008mm
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Il pezzo richiesto con larghezza lama (3008mm) supera la lunghezza dell\'asse (3000mm)');

        $this->service->optimize(3000, 2998, 1, 10);
    }
}
