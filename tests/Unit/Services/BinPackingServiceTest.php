<?php

namespace Tests\Unit\Services;

use App\Services\BinPackingService;
use PHPUnit\Framework\TestCase;

class BinPackingServiceTest extends TestCase
{
    private BinPackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BinPackingService();
    }

    public function test_it_applies_kerf_consistently_for_fit_and_consumption(): void
    {
        $result = $this->service->pack([
            [
                'id' => 1,
                'description' => 'Pezzo 1000',
                'length' => 1000,
                'quantity' => 3,
            ],
        ], 3000, 5);

        $this->assertSame(2, $result['total_bins']);
        $this->assertCount(2, $result['bins'][0]['items']);
        $this->assertEquals(995.0, $result['bins'][0]['remaining_length']);
        $this->assertEquals(2005.0, $result['bins'][0]['used_length']);
    }

    public function test_it_handles_exact_fit_with_kerf_formula(): void
    {
        // 3*332 + 2*2 = 1000, so one bin should fit exactly.
        $result = $this->service->pack([
            [
                'id' => 1,
                'description' => 'Pezzo 332',
                'length' => 332,
                'quantity' => 3,
            ],
        ], 1000, 2);

        $this->assertSame(1, $result['total_bins']);
        $this->assertEquals(0.0, $result['bins'][0]['remaining_length']);
        $this->assertEquals(1000.0, $result['bins'][0]['used_length']);
        $this->assertEquals(0.0, $result['total_waste']);
    }

    public function test_used_length_follows_nl_plus_n_minus_one_kerf_formula_for_each_bin(): void
    {
        $kerf = 4.0;

        $result = $this->service->pack([
            [
                'id' => 1,
                'description' => 'Pezzo A',
                'length' => 900,
                'quantity' => 3,
            ],
            [
                'id' => 2,
                'description' => 'Pezzo B',
                'length' => 1200,
                'quantity' => 2,
            ],
        ], 3000, $kerf);

        foreach ($result['bins'] as $bin) {
            $count = count($bin['items']);
            $sumLengths = array_sum(array_map(
                fn(array $item): float => (float) $item['length'],
                $bin['items']
            ));

            $expectedUsed = $sumLengths + max(0, $count - 1) * $kerf;

            $this->assertEqualsWithDelta($expectedUsed, $bin['used_length'], 0.0001);
            $this->assertEqualsWithDelta(
                $bin['capacity'] - $expectedUsed,
                $bin['remaining_length'],
                0.0001
            );
        }
    }

    public function test_it_exposes_component_assignments_with_bin_breakdown(): void
    {
        $result = $this->service->pack([
            [
                'id' => 10,
                'description' => 'Parete lunga',
                'length' => 1000,
                'quantity' => 2,
            ],
            [
                'id' => 20,
                'description' => 'Fondo',
                'length' => 900,
                'quantity' => 1,
            ],
        ], 2500, 5);

        $this->assertArrayHasKey('component_assignments', $result);

        $assignments = [];
        foreach ($result['component_assignments'] as $row) {
            $assignments[(string) ($row['component_id'] ?? '')] = $row;
        }

        $parete = $assignments['10'] ?? null;
        $this->assertNotNull($parete);
        $this->assertSame(2, (int) ($parete['produced_strips'] ?? 0));
        $this->assertSame(1, (int) ($parete['assigned_boards_count'] ?? 0));
        $this->assertSame(1, (int) ($parete['assigned_bins'][0]['board_number'] ?? 0));
        $this->assertSame(2, (int) ($parete['assigned_bins'][0]['strips'] ?? 0));
        $this->assertEqualsWithDelta(495.0, (float) ($parete['allocated_waste_mm'] ?? 0), 0.01);

        $fondo = $assignments['20'] ?? null;
        $this->assertNotNull($fondo);
        $this->assertSame(1, (int) ($fondo['produced_strips'] ?? 0));
        $this->assertSame(1, (int) ($fondo['assigned_boards_count'] ?? 0));
        $this->assertSame(2, (int) ($fondo['assigned_bins'][0]['board_number'] ?? 0));
        $this->assertEqualsWithDelta(1600.0, (float) ($fondo['allocated_waste_mm'] ?? 0), 0.01);
    }
}
