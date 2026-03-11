<?php

namespace Tests\Unit\Services\Production;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Services\Production\GabbiaConstructionOptimizer;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GabbiaConstructionOptimizerTest extends TestCase
{
    public function test_excel_compatibility_mode_uses_sp20_requirements_with_expected_bins_and_volumes(): void
    {
        $previousMode = config('production.gabbia_excel_mode', 'preview');
        Config::set('production.gabbia_excel_mode', 'compatibility');

        try {
            $optimizer = app(GabbiaConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'gabbia',
                'slug' => 'gabbia-standard',
                'config' => ['ha_coperchio' => false],
            ]);

            $materiale = new Prodotto([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'lunghezza_mm' => 2300,
                'larghezza_mm' => 250,
                'spessore_mm' => 20,
            ]);

            $fallbackPieces = [
                ['id' => 1, 'description' => 'Fallback Dummy', 'length' => 1000.0, 'width' => 1000.0, 'quantity' => 1],
            ];

            $result = $optimizer->optimize(
                costruzione: $costruzione,
                pieces: $fallbackPieces,
                materiale: $materiale,
                kerfMm: 0.0,
                context: [
                    'larghezza_cm' => 84.0,
                    'profondita_cm' => 43.0,
                    'altezza_cm' => 55.0,
                    'numero_pezzi' => 1,
                ]
            );

            $this->assertSame('gabbia', data_get($result, 'optimizer.name'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_routine_supported'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'optimizer.version'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'trace.category_optimizer_version'));
            $this->assertSame('excel-requirements-to-strips-then-1d-bfd', data_get($result, 'optimizer.strategy'));
            $this->assertSame('excel-requirements-to-strips-then-1d-bfd', data_get($result, 'trace.category_optimizer_strategy'));
            $this->assertSame('gabbiasp20', data_get($result, 'trace.variant_routine'));
            $this->assertSame('gabbiasp20', data_get($result, 'trace.excel_preview.routine'));
            $this->assertSame([
                'D8' => 5,
                'D9' => 6,
                'D10' => 6,
                'D11' => 3,
                'D12' => 14,
                'D13' => 3,
            ], data_get($result, 'trace.excel_preview.legacy_quantities'));
            $this->assertSame(12, (int) ($result['total_bins'] ?? 0));

            $totalPackedPieces = collect((array) ($result['bins'] ?? []))
                ->sum(fn (array $bin): int => count((array) ($bin['items'] ?? [])));
            $this->assertSame(37, (int) $totalPackedPieces);

            $this->assertEqualsWithDelta(0.138, (float) data_get($result, 'cutting_totals.volume_lordo_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.042036, (float) data_get($result, 'cutting_totals.volume_netto_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.095964, (float) data_get($result, 'cutting_totals.volume_scarto_mc'), 0.000001);
            $this->assertEqualsWithDelta(69.54, (float) data_get($result, 'cutting_totals.scarto_volume_percentuale'), 0.01);
        } finally {
            Config::set('production.gabbia_excel_mode', $previousMode);
        }
    }

    public function test_excel_compatibility_mode_maps_fondo4_variant_and_keeps_expected_totals(): void
    {
        $previousMode = config('production.gabbia_excel_mode', 'preview');
        Config::set('production.gabbia_excel_mode', 'compatibility');

        try {
            $optimizer = app(GabbiaConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'gabbia',
                'slug' => 'gabbia-sp20-fondo4',
                'config' => ['ha_coperchio' => false],
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
                    'altezza_cm' => 55.0,
                    'numero_pezzi' => 1,
                ]
            );

            $this->assertSame('gabbiasp20fondo4', data_get($result, 'trace.variant_routine'));
            $this->assertSame('gabbiasp20fondo4', data_get($result, 'trace.excel_preview.routine'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'optimizer.version'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'trace.category_optimizer_version'));
            $this->assertSame('excel-requirements-to-strips-then-1d-bfd', data_get($result, 'optimizer.strategy'));
            $this->assertSame('excel-requirements-to-strips-then-1d-bfd', data_get($result, 'trace.category_optimizer_strategy'));
            $this->assertSame([
                'D8' => 5,
                'D9' => 6,
                'D10' => 6,
                'D11' => 3,
                'D12' => 14,
                'D13' => 3,
            ], data_get($result, 'trace.excel_preview.legacy_quantities'));
            $this->assertEqualsWithDelta(
                40.0,
                (float) data_get($result, 'trace.excel_preview.rows.0.B_section_mm'),
                0.0001
            );
            $this->assertSame(12, (int) ($result['total_bins'] ?? 0));
            $totalPackedPieces = collect((array) ($result['bins'] ?? []))
                ->sum(fn (array $bin): int => count((array) ($bin['items'] ?? [])));
            $this->assertSame(37, (int) $totalPackedPieces);
            $this->assertEqualsWithDelta(0.138, (float) data_get($result, 'cutting_totals.volume_lordo_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.042036, (float) data_get($result, 'cutting_totals.volume_netto_mc'), 0.000001);
            $this->assertEqualsWithDelta(0.095964, (float) data_get($result, 'cutting_totals.volume_scarto_mc'), 0.000001);
            $this->assertEqualsWithDelta(69.54, (float) data_get($result, 'cutting_totals.scarto_volume_percentuale'), 0.01);
        } finally {
            Config::set('production.gabbia_excel_mode', $previousMode);
        }
    }

    public function test_excel_strict_mode_uses_excel_pieces_for_supported_routine(): void
    {
        $previousMode = config('production.gabbia_excel_mode', 'preview');
        Config::set('production.gabbia_excel_mode', 'strict');

        try {
            $optimizer = app(GabbiaConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'gabbia',
                'slug' => 'gabbia-standard',
                'config' => ['ha_coperchio' => false],
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
                    'altezza_cm' => 55.0,
                    'numero_pezzi' => 1,
                ]
            );

            $this->assertSame('strict', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_strict_applied'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_strict_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'optimizer.version'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'trace.category_optimizer_version'));
            $this->assertSame(12, (int) ($result['total_bins'] ?? 0));
        } finally {
            Config::set('production.gabbia_excel_mode', $previousMode);
        }
    }

    public function test_excel_strict_mode_throws_when_supported_routine_has_missing_dimensions(): void
    {
        $previousMode = config('production.gabbia_excel_mode', 'preview');
        Config::set('production.gabbia_excel_mode', 'strict');

        try {
            $optimizer = app(GabbiaConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'gabbia',
                'slug' => 'gabbia-standard',
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
            $this->expectExceptionMessage('Strict mode gabbia attivo');

            $optimizer->optimize(
                costruzione: $costruzione,
                pieces: [
                    ['id' => 1, 'description' => 'Fallback Dummy', 'length' => 900.0, 'width' => 900.0, 'quantity' => 1],
                ],
                materiale: $materiale,
                kerfMm: 0.0,
                context: [
                    'larghezza_cm' => 84.0,
                    'profondita_cm' => 43.0,
                    'altezza_cm' => 0.0, // invalid for supported routine
                    'numero_pezzi' => 1,
                ]
            );
        } finally {
            Config::set('production.gabbia_excel_mode', $previousMode);
        }
    }

    public function test_excel_compatibility_mode_uses_gabbia_legaccio_6_piantoni_fondo4_requirements(): void
    {
        $previousMode = config('production.gabbia_excel_mode', 'preview');
        Config::set('production.gabbia_excel_mode', 'compatibility');

        try {
            $optimizer = app(GabbiaConstructionOptimizer::class);

            $costruzione = new Costruzione([
                'categoria' => 'gabbia',
                'slug' => 'gabbia-legaccio',
                'config' => [
                    'piantoni' => 6,
                    'fondo4' => true,
                    'ha_coperchio' => false,
                ],
            ]);

            $materiale = new Prodotto([
                'categoria' => Categoria::ASSE,
                'unita_misura' => UnitaMisura::MC,
                'lunghezza_mm' => 3000,
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
                    'larghezza_cm' => 120.0,
                    'profondita_cm' => 40.0,
                    'altezza_cm' => 215.0,
                    'numero_pezzi' => 1,
                ]
            );

            $this->assertSame('gabbia', data_get($result, 'optimizer.name'));
            $this->assertSame('gabbialegaccio6piantonifondo4', data_get($result, 'trace.variant_routine'));
            $this->assertSame('compatibility', data_get($result, 'trace.excel_mode_requested'));
            $this->assertTrue((bool) data_get($result, 'trace.excel_preview_applied'));
            $this->assertSame('excel_preview', data_get($result, 'trace.piece_source'));
            $this->assertSame('excel_compatibility_v2', data_get($result, 'trace.optimizer_mode'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'optimizer.version'));
            $this->assertSame('gabbia-excel-v2', data_get($result, 'trace.category_optimizer_version'));
            $this->assertSame(16, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D9'));
            $this->assertSame(16, (int) data_get($result, 'trace.excel_preview.legacy_quantities.D10'));
            $this->assertGreaterThan(0, (int) ($result['total_bins'] ?? 0));
        } finally {
            Config::set('production.gabbia_excel_mode', $previousMode);
        }
    }
}
