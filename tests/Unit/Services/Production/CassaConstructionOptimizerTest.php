<?php

namespace Tests\Unit\Services\Production;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Services\BinPackingService;
use App\Services\Production\CassaConstructionOptimizer;
use Tests\TestCase;

class CassaConstructionOptimizerTest extends TestCase
{
    public function test_optimizes_reference_case_to_seven_boards_without_coperchio(): void
    {
        $costruzione = new Costruzione([
            'categoria' => 'cassa',
            'config' => [],
        ]);

        $materiale = new Prodotto([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $panelPieces = [
            ['id' => 1, 'description' => 'Parete lunga esterna', 'length' => 1000.0, 'width' => 1000.0, 'quantity' => 2],
            ['id' => 2, 'description' => 'Parete corta interna', 'length' => 460.0, 'width' => 1000.0, 'quantity' => 2],
            ['id' => 3, 'description' => 'Fondo', 'length' => 1000.0, 'width' => 500.0, 'quantity' => 1],
            ['id' => 4, 'description' => 'Coperchio', 'length' => 1000.0, 'width' => 500.0, 'quantity' => 1],
        ];

        $optimizer = new CassaConstructionOptimizer(new BinPackingService());
        $result = $optimizer->optimize($costruzione, $panelPieces, $materiale, 0.0);

        $this->assertSame(7, $result['total_bins']);
        $this->assertEqualsWithDelta(2420.0, (float) $result['total_waste'], 0.0001);
        $this->assertSame('cassa', data_get($result, 'optimizer.name'));
        $this->assertSame('cassa-strips-v1', data_get($result, 'optimizer.version'));
        $this->assertEqualsWithDelta(0.0805, (float) data_get($result, 'cutting_totals.volume_lordo_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.0684, (float) data_get($result, 'cutting_totals.volume_netto_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.0121, (float) data_get($result, 'cutting_totals.volume_scarto_mc'), 0.000001);
        $this->assertNotEmpty(data_get($result, 'trace.component_summary'));

        $componentSummary = collect(data_get($result, 'trace.component_summary', []))
            ->keyBy(fn(array $row): string => (string) ($row['id'] ?? ''));
        $this->assertSame(8, (int) data_get($componentSummary->get('1'), 'requested_strips'));
        $this->assertSame(8, (int) data_get($componentSummary->get('1'), 'produced_strips'));
        $this->assertGreaterThanOrEqual(1, (int) data_get($componentSummary->get('1'), 'assigned_boards_count', 0));
        $this->assertNotEmpty(data_get($componentSummary->get('1'), 'assigned_bins', []));

        $skipped = collect(data_get($result, 'trace.skipped_panels', []));
        $this->assertTrue($skipped->contains(fn (array $row) => str_contains($row['description'], 'Coperchio')));
    }

    public function test_throws_when_panel_length_exceeds_board_length(): void
    {
        $costruzione = new Costruzione([
            'categoria' => 'cassa',
            'config' => ['ha_coperchio' => true],
        ]);

        $materiale = new Prodotto([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $panelPieces = [
            ['id' => 1, 'description' => 'Pannello troppo lungo', 'length' => 2400.0, 'width' => 250.0, 'quantity' => 1],
        ];

        $optimizer = new CassaConstructionOptimizer(new BinPackingService());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('più lungo della lunghezza dell\'asse disponibile');

        $optimizer->optimize($costruzione, $panelPieces, $materiale, 0.0);
    }

    public function test_throws_when_no_compatible_panels_are_available(): void
    {
        $costruzione = new Costruzione([
            'categoria' => 'cassa',
            'config' => ['ha_coperchio' => false],
        ]);

        $materiale = new Prodotto([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $panelPieces = [
            // skipped because coperchio disabled
            ['id' => 1, 'description' => 'Coperchio', 'length' => 1000.0, 'width' => 500.0, 'quantity' => 1],
            // skipped because non-positive length
            ['id' => 2, 'description' => 'Pannello invalido', 'length' => 0.0, 'width' => 500.0, 'quantity' => 1],
        ];

        $optimizer = new CassaConstructionOptimizer(new BinPackingService());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nessun pannello compatibile da ottimizzare per la categoria cassa');

        $optimizer->optimize($costruzione, $panelPieces, $materiale, 0.0);
    }

    public function test_handles_partial_strip_width_and_tracks_panel_summary(): void
    {
        $costruzione = new Costruzione([
            'categoria' => 'cassa',
            'config' => ['ha_coperchio' => true],
        ]);

        $materiale = new Prodotto([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $panelPieces = [
            ['id' => 1, 'description' => 'Pannello con residuo', 'length' => 1000.0, 'width' => 300.0, 'quantity' => 1],
        ];

        $optimizer = new CassaConstructionOptimizer(new BinPackingService());
        $result = $optimizer->optimize($costruzione, $panelPieces, $materiale, 0.0);

        $this->assertSame(1, (int) $result['total_bins']);
        $this->assertEqualsCanonicalizing(
            [250.0, 50.0],
            collect($result['bins'])
                ->flatMap(fn(array $bin) => collect($bin['items'])->pluck('width'))
                ->map(fn($width) => (float) $width)
                ->all()
        );

        $summary = data_get($result, 'trace.panel_summary.0');
        $this->assertSame(2, (int) data_get($summary, 'strips_per_panel'));
        $this->assertSame(1, (int) data_get($summary, 'full_width_strips_per_panel'));
        $this->assertEqualsWithDelta(50.0, (float) data_get($summary, 'last_strip_width_mm'), 0.0001);
        $this->assertSame(2, (int) data_get($summary, 'total_strips'));
    }

    public function test_throws_when_panel_width_is_missing(): void
    {
        $costruzione = new Costruzione([
            'categoria' => 'cassa',
            'config' => ['ha_coperchio' => true],
        ]);

        $materiale = new Prodotto([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $panelPieces = [
            ['id' => 1, 'description' => 'Pannello senza larghezza', 'length' => 1000.0, 'quantity' => 1],
        ];

        $optimizer = new CassaConstructionOptimizer(new BinPackingService());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('formula_larghezza valida');

        $optimizer->optimize($costruzione, $panelPieces, $materiale, 0.0);
    }

    public function test_high_kerf_increases_bins_for_same_requirements(): void
    {
        $costruzione = new Costruzione([
            'categoria' => 'cassa',
            'config' => ['ha_coperchio' => true],
        ]);

        $materiale = new Prodotto([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $panelPieces = [
            ['id' => 1, 'description' => 'Pannello', 'length' => 1000.0, 'width' => 250.0, 'quantity' => 2],
        ];

        $optimizer = new CassaConstructionOptimizer(new BinPackingService());
        $noKerf = $optimizer->optimize($costruzione, $panelPieces, $materiale, 0.0);
        $highKerf = $optimizer->optimize($costruzione, $panelPieces, $materiale, 50.0);

        $this->assertSame(1, (int) $noKerf['total_bins']);
        $this->assertSame(2, (int) $highKerf['total_bins']);
        $this->assertGreaterThan(
            (float) ($noKerf['total_waste'] ?? 0),
            (float) ($highKerf['total_waste'] ?? 0)
        );
    }
}
