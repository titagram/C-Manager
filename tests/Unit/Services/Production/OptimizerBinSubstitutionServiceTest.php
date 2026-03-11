<?php

namespace Tests\Unit\Services\Production;

use App\Models\Prodotto;
use App\Services\Production\OptimizerBinSubstitutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptimizerBinSubstitutionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_repacks_selected_bins_with_same_width_material_and_reduces_bin_count(): void
    {
        $primary = Prodotto::factory()->legname()->create([
            'nome' => 'Abete 150x100x30',
            'lunghezza_mm' => 1500,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
            'costo_unitario' => 300,
            'prezzo_unitario' => 500,
            'prezzo_mc' => 500,
            'soggetto_fitok' => true,
        ]);

        $candidate = Prodotto::factory()->legname()->create([
            'nome' => 'Abete 300x100x30',
            'lunghezza_mm' => 3000,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
            'costo_unitario' => 320,
            'prezzo_unitario' => 520,
            'prezzo_mc' => 520,
            'soggetto_fitok' => false,
        ]);

        $payload = [
            'materiale' => [
                'id' => $primary->id,
                'nome' => $primary->nome,
                'lunghezza_mm' => 1500,
                'larghezza_mm' => 100,
                'spessore_mm' => 30,
                'unita_misura' => 'mc',
                'costo_unitario' => 300,
                'prezzo_unitario' => 500,
                'prezzo_mc' => 500,
                'soggetto_fitok' => true,
            ],
            'kerf' => 3,
            'bins' => [
                [
                    'capacity' => 1500,
                    'used_length' => 1403,
                    'waste' => 97,
                    'waste_percent' => 6.47,
                    'items' => [
                        ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                        ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                    ],
                ],
                [
                    'capacity' => 1500,
                    'used_length' => 1403,
                    'waste' => 97,
                    'waste_percent' => 6.47,
                    'items' => [
                        ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                        ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                    ],
                ],
            ],
        ];

        $service = app(OptimizerBinSubstitutionService::class);
        $updated = $service->substitute($payload, [0, 1], $candidate);

        $this->assertSame(1, (int) $updated['total_bins']);
        $this->assertSame($candidate->id, (int) data_get($updated, 'bins.0.source_material_id'));
        $this->assertSame('substituted', data_get($updated, 'bins.0.source_type'));
        $this->assertSame($candidate->nome, data_get($updated, 'bins.0.source_material.nome'));
        $this->assertEqualsWithDelta(0.009, (float) data_get($updated, 'totali.volume_lordo_mc'), 0.000001);
        $this->assertSame('non_fitok', data_get($updated, 'fitok_preview.status'));
    }

    public function test_it_rejects_candidate_with_different_thickness(): void
    {
        $primary = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 2400,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
        ]);

        $candidate = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 2400,
            'larghezza_mm' => 200,
            'spessore_mm' => 35,
        ]);

        $payload = [
            'materiale' => [
                'id' => $primary->id,
                'spessore_mm' => 30,
            ],
            'kerf' => 3,
            'bins' => [
                [
                    'capacity' => 2400,
                    'used_length' => 2003,
                    'waste' => 397,
                    'waste_percent' => 16.54,
                    'items' => [
                        ['id' => 1, 'description' => 'Fondo', 'length' => 1000, 'width' => 100],
                    ],
                ],
            ],
        ];

        $this->expectExceptionMessage('stesso spessore');

        app(OptimizerBinSubstitutionService::class)->substitute($payload, [0], $candidate);
    }

    public function test_it_rejects_candidate_with_different_width(): void
    {
        $primary = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 2400,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
        ]);

        $candidate = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 2400,
            'larghezza_mm' => 120,
            'spessore_mm' => 30,
        ]);

        $payload = [
            'materiale' => [
                'id' => $primary->id,
                'spessore_mm' => 30,
            ],
            'kerf' => 3,
            'bins' => [
                [
                    'capacity' => 2400,
                    'used_length' => 2003,
                    'waste' => 397,
                    'waste_percent' => 16.54,
                    'items' => [
                        ['id' => 1, 'description' => 'Fondo', 'length' => 1000, 'width' => 100],
                        ['id' => 1, 'description' => 'Fondo', 'length' => 1000, 'width' => 100],
                    ],
                ],
            ],
        ];

        $this->expectExceptionMessage('stessa larghezza');

        app(OptimizerBinSubstitutionService::class)->substitute($payload, [0], $candidate);
    }

    public function test_it_rejects_candidate_that_cannot_repack_selected_items_even_with_same_width(): void
    {
        $primary = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 1500,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
        ]);

        $candidate = Prodotto::factory()->legname()->create([
            'lunghezza_mm' => 650,
            'larghezza_mm' => 100,
            'spessore_mm' => 30,
        ]);

        $payload = [
            'materiale' => [
                'id' => $primary->id,
                'larghezza_mm' => 100,
                'spessore_mm' => 30,
            ],
            'kerf' => 3,
            'bins' => [
                [
                    'capacity' => 1500,
                    'used_length' => 1403,
                    'waste' => 97,
                    'waste_percent' => 6.47,
                    'items' => [
                        ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                        ['id' => 1, 'description' => 'Fondo', 'length' => 700, 'width' => 100],
                    ],
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);

        app(OptimizerBinSubstitutionService::class)->substitute($payload, [0], $candidate);
    }
}
