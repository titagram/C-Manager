<?php

namespace Tests\Unit\Services\Production;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Services\Production\BancaleConstructionOptimizer;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BancaleConstructionOptimizerTest extends TestCase
{
    public function test_excel_compatibility_mode_uses_bancale_requirements_with_expected_bins_and_volumes(): void
    {
        $previousMode = config('production.bancale_excel_mode', 'preview');
        Config::set('production.bancale_excel_mode', 'compatibility');

        try {
            $optimizer = app(BancaleConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'bancale',
                'slug' => 'bancale-standard',
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
                    ['id' => 1, 'description' => 'Fallback Dummy', 'length' => 900.0, 'width' => 900.0, 'quantity' => 1],
                ],
                materiale: $materiale,
                kerfMm: 0.0,
                context: [
                    'larghezza_cm' => 84.0,
                    'profondita_cm' => 43.0,
                    'altezza_cm' => 0.0,
                    'numero_pezzi' => 1,
                ]
            );

            $this->assertSame('bancale', data_get($result, 'optimizer.name'));
            $this->assertSame('bancale', data_get($result, 'trace.variant_routine'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame(4, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D8'));
            $this->assertSame(3, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D9'));
            $this->assertSame(3, (int) ($result['total_bins'] ?? 0));
            $this->assertEqualsWithDelta(0.0345, (float) data_get($result, 'cutting_totals.volume_lordo_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.0093, (float) data_get($result, 'cutting_totals.volume_netto_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.0252, (float) data_get($result, 'cutting_totals.volume_scarto_mc'), 0.000001);
        } finally {
            Config::set('production.bancale_excel_mode', $previousMode);
        }
    }

    public function test_excel_compatibility_mode_supports_perimetrale_routine(): void
    {
        $previousMode = config('production.bancale_excel_mode', 'preview');
        Config::set('production.bancale_excel_mode', 'compatibility');

        try {
            $optimizer = app(BancaleConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'bancale',
                'slug' => 'perimetrale-standard',
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
                    'larghezza_cm' => 190.0,
                    'profondita_cm' => 120.0,
                    'altezza_cm' => 80.0,
                    'numero_pezzi' => 1,
                ]
            );

            $this->assertSame('bancale', data_get($result, 'optimizer.name'));
            $this->assertSame('perimetrale', data_get($result, 'trace.variant_routine'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame('perimetrale', data_get($result, 'trace.excel_preview.routine'));
            $this->assertSame(7, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D10'));
            $this->assertSame(6, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D11'));
            $this->assertSame(29, (int) ($result['total_bins'] ?? 0));
            $this->assertEqualsWithDelta(0.3335, (float) data_get($result, 'cutting_totals.volume_lordo_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.13464, (float) data_get($result, 'cutting_totals.volume_netto_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.19886, (float) data_get($result, 'cutting_totals.volume_scarto_mc'), 0.000001);
        } finally {
            Config::set('production.bancale_excel_mode', $previousMode);
        }
    }

    public function test_excel_strict_mode_uses_excel_pieces_for_perimetrale(): void
    {
        $previousMode = config('production.bancale_excel_mode', 'preview');
        Config::set('production.bancale_excel_mode', 'strict');

        try {
            $optimizer = app(BancaleConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'bancale',
                'slug' => 'perimetrale-standard',
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
                    'larghezza_cm' => 190.0,
                    'profondita_cm' => 120.0,
                    'altezza_cm' => 80.0,
                    'numero_pezzi' => 1,
                ]
            );

            $this->assertSame('strict', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_strict_applied'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_strict_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame(29, (int) ($result['total_bins'] ?? 0));
        } finally {
            Config::set('production.bancale_excel_mode', $previousMode);
        }
    }

    public function test_excel_strict_mode_throws_when_perimetrale_has_missing_height(): void
    {
        $previousMode = config('production.bancale_excel_mode', 'preview');
        Config::set('production.bancale_excel_mode', 'strict');

        try {
            $optimizer = app(BancaleConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'bancale',
                'slug' => 'perimetrale-standard',
                'config' => [],
            ]);

            $materiale = new Prodotto([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Strict mode bancale attivo');

            $optimizer->optimize(
                costruzione: $costruzione,
                pieces: [
                    ['id' => 1, 'description' => 'Fallback Dummy', 'length' => 1000.0, 'width' => 1000.0, 'quantity' => 1],
                ],
                materiale: $materiale,
                kerfMm: 0.0,
                context: [
                    'larghezza_cm' => 190.0,
                    'profondita_cm' => 120.0,
                    'altezza_cm' => 0.0, // invalid for perimetrale strict
                    'numero_pezzi' => 1,
                ]
            );
        } finally {
            Config::set('production.bancale_excel_mode', $previousMode);
        }
    }
}
