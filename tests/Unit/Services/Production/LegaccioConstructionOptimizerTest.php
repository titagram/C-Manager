<?php

namespace Tests\Unit\Services\Production;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Services\Production\LegaccioConstructionOptimizer;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LegaccioConstructionOptimizerTest extends TestCase
{
    public function test_excel_compatibility_mode_uses_legacci224x60_requirements(): void
    {
        $previousMode = config('production.legaccio_excel_mode', 'preview');
        Config::set('production.legaccio_excel_mode', 'compatibility');

        try {
            $optimizer = app(LegaccioConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'legaccio',
                'slug' => 'legacci-224x60',
                'config' => [],
            ]);

            $materiale = new Prodotto([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $result = $optimizer->optimize(
                costruzione: $costruzione,
                pieces: [
                    ['id' => 1, 'description' => 'Fallback Dummy', 'length' => 1000.0, 'width' => 1000.0, 'quantity' => 1],
                ],
                materiale: $materiale,
                kerfMm: 0.0,
                context: [
                    'larghezza_cm' => 224.0,
                    'profondita_cm' => 60.0,
                    'altezza_cm' => 0.0,
                    'numero_pezzi' => 1,
                ]
            );

            $this->assertSame('legaccio', data_get($result, 'optimizer.name'));
            $this->assertSame('legacci224x60', data_get($result, 'trace.variant_routine'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame('legacci224x60', data_get($result, 'trace.excel_preview.routine'));
            $this->assertSame(20, (int) ($result['total_bins'] ?? 0));
            $this->assertEqualsWithDelta(0.23, (float) data_get($result, 'cutting_totals.volume_lordo_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.16044, (float) data_get($result, 'cutting_totals.volume_netto_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.06956, (float) data_get($result, 'cutting_totals.volume_scarto_mc'), 0.000001);
        } finally {
            Config::set('production.legaccio_excel_mode', $previousMode);
        }
    }

    public function test_excel_strict_mode_uses_excel_pieces_for_legacci224x60(): void
    {
        $previousMode = config('production.legaccio_excel_mode', 'preview');
        Config::set('production.legaccio_excel_mode', 'strict');

        try {
            $optimizer = app(LegaccioConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'legaccio',
                'slug' => 'legacci-224x60',
                'config' => [],
            ]);

            $materiale = new Prodotto([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $result = $optimizer->optimize(
                costruzione: $costruzione,
                pieces: [
                    ['id' => 1, 'description' => 'Fallback Dummy', 'length' => 1000.0, 'width' => 1000.0, 'quantity' => 1],
                ],
                materiale: $materiale,
                kerfMm: 0.0,
                context: [
                    'larghezza_cm' => 224.0,
                    'profondita_cm' => 60.0,
                    'altezza_cm' => 0.0,
                    'numero_pezzi' => 1,
                ]
            );

            $this->assertSame('strict', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_strict_applied'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_strict_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame(20, (int) ($result['total_bins'] ?? 0));
        } finally {
            Config::set('production.legaccio_excel_mode', $previousMode);
        }
    }
}
