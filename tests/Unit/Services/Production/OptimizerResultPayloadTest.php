<?php

namespace Tests\Unit\Services\Production;

use App\Models\Costruzione;
use App\Models\ComponenteCostruzione;
use App\Models\Prodotto;
use App\Services\Production\DTO\OptimizationInput;
use App\Services\Production\DTO\OptimizerResultPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptimizerResultPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_computation_creates_current_version_payload_with_audit_trace(): void
    {
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'slug' => 'cassa-standard-test',
            'config' => ['ha_coperchio' => false],
        ]);
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete lunga',
            'tipo_dimensionamento' => 'CALCOLATO',
            'calcolato' => true,
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
        ]);

        $materiale = Prodotto::factory()->legname()->create();

        $input = OptimizationInput::fromRuntime(
            costruzione: $costruzione,
            materiale: $materiale,
            kerfMm: 0.0,
            dimensions: [
                'larghezza_cm' => 100,
                'profondita_cm' => 50,
                'altezza_cm' => 100,
                'numero_pezzi' => 1,
            ],
            pieces: [
                [
                    'id' => 10,
                    'description' => 'Parete lunga',
                    'length' => 1000.0,
                    'width' => 250.0,
                    'quantity' => 2,
                ],
            ]
        );

        $payload = OptimizerResultPayload::fromComputation([
            'optimizer' => [
                'name' => 'cassa',
                'version' => 'cassa-strips-v1',
                'strategy' => 'panel-to-strips-then-1d-bfd',
            ],
            'bins' => [],
            'totali' => [
                'costo_totale' => 1.0,
                'prezzo_totale' => 2.0,
                'volume_totale_mc' => 0.1,
            ],
        ], $input);

        $this->assertSame('v2', data_get($payload, 'version'));
        $this->assertSame('cassa', data_get($payload, 'trace.audit.algorithm.name'));
        $this->assertNotEmpty(data_get($payload, 'trace.audit.logical_timestamp'));
        $this->assertSame($costruzione->id, (int) data_get($payload, 'trace.audit.input.costruzione.id'));
        $this->assertSame(1, (int) data_get($payload, 'trace.audit.input.requirements.pieces_count'));
        $this->assertSame('rules-', substr((string) data_get($payload, 'trace.audit.input.rules.version', ''), 0, 6));
        $this->assertNotEmpty(data_get($payload, 'trace.audit.input.rules.fingerprint_sha256'));
        $this->assertSame(1, (int) data_get($payload, 'trace.audit.input.rules.snapshot.summary.total'));
        $this->assertNotEmpty(data_get($payload, 'trace.audit.result.signature_sha256'));
    }

    public function test_normalize_for_runtime_marks_legacy_payloads_as_compatible(): void
    {
        $runtime = OptimizerResultPayload::normalizeForRuntime([
            'materiale' => ['id' => 100],
            'totali' => [
                'costo_totale' => 0,
                'prezzo_totale' => 0,
                'volume_totale_mc' => 0,
            ],
        ]);

        $this->assertIsArray($runtime);
        $this->assertSame('legacy-v1', data_get($runtime, 'version'));
        $this->assertTrue((bool) data_get($runtime, 'trace.audit.compatibility.legacy_read_applied'));
        $this->assertSame('legacy-bin-packing', data_get($runtime, 'optimizer.name'));
    }

    public function test_normalize_for_persistence_upgrades_legacy_payload_to_current_version(): void
    {
        $persisted = OptimizerResultPayload::normalizeForPersistence([
            'optimizer' => [
                'name' => 'legacy-bin-packing',
                'version' => 'legacy-1d-v1',
                'strategy' => 'direct-1d-bfd',
            ],
            'bins' => [],
            'totali' => [
                'costo_totale' => 0,
                'prezzo_totale' => 0,
                'volume_totale_mc' => 0,
            ],
        ]);

        $this->assertIsArray($persisted);
        $this->assertSame('v2', data_get($persisted, 'version'));
        $this->assertTrue((bool) data_get($persisted, 'trace.audit.compatibility.legacy_read_applied'));
        $this->assertSame('persisted_write', data_get($persisted, 'trace.audit.compatibility.source'));
        $this->assertNotEmpty(data_get($persisted, 'trace.audit.result.signature_sha256'));
    }

    public function test_normalize_for_runtime_clamps_invalid_volume_invariants(): void
    {
        $runtime = OptimizerResultPayload::normalizeForRuntime([
            'optimizer' => [
                'name' => 'cassa',
                'version' => 'cassa-strips-v1',
                'strategy' => 'panel-to-strips-then-1d-bfd',
            ],
            'totali' => [
                'volume_totale_mc' => 0.0115,
                'volume_lordo_mc' => 0.0115,
                'volume_netto_mc' => 0.0200,
                'volume_scarto_mc' => -0.0085,
            ],
        ]);

        $this->assertEqualsWithDelta(0.0115, (float) data_get($runtime, 'totali.volume_lordo_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.0115, (float) data_get($runtime, 'totali.volume_netto_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.0, (float) data_get($runtime, 'totali.volume_scarto_mc'), 0.000001);
        $this->assertEqualsWithDelta(0.0115, (float) data_get($runtime, 'totali.volume_totale_mc'), 0.000001);
    }

    public function test_normalize_for_runtime_backfills_primary_source_material_into_bins(): void
    {
        $runtime = OptimizerResultPayload::normalizeForRuntime([
            'materiale' => [
                'id' => 55,
                'nome' => 'Abete 240x100x30',
                'spessore_mm' => 30,
            ],
            'bins' => [
                [
                    'items' => [
                        ['id' => 1, 'description' => 'Fondo', 'length' => 1000, 'width' => 100],
                    ],
                ],
            ],
            'totali' => [
                'costo_totale' => 0,
                'prezzo_totale' => 0,
                'volume_totale_mc' => 0.01,
            ],
        ]);

        $this->assertSame(55, (int) data_get($runtime, 'bins.0.source_material_id'));
        $this->assertSame('primary', data_get($runtime, 'bins.0.source_type'));
        $this->assertSame('Abete 240x100x30', data_get($runtime, 'bins.0.source_material.nome'));
        $this->assertIsArray(data_get($runtime, 'bins.0.substitution_meta'));
    }

    public function test_optimization_input_rules_fingerprint_changes_when_component_rules_change(): void
    {
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'slug' => 'cassa-rules-fingerprint-test',
        ]);
        $componente = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete lunga',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
        ]);
        $materiale = Prodotto::factory()->legname()->create();

        $first = OptimizationInput::fromRuntime(
            costruzione: $costruzione->fresh('componenti'),
            materiale: $materiale,
            kerfMm: 0.0,
            dimensions: [
                'larghezza_cm' => 100,
                'profondita_cm' => 50,
                'altezza_cm' => 100,
                'numero_pezzi' => 1,
            ],
            pieces: []
        );

        $componente->update([
            'formula_quantita' => '3',
        ]);

        $second = OptimizationInput::fromRuntime(
            costruzione: $costruzione->fresh('componenti'),
            materiale: $materiale,
            kerfMm: 0.0,
            dimensions: [
                'larghezza_cm' => 100,
                'profondita_cm' => 50,
                'altezza_cm' => 100,
                'numero_pezzi' => 1,
            ],
            pieces: []
        );

        $this->assertNotSame($first->rulesFingerprint, $second->rulesFingerprint);
        $this->assertNotSame($first->rulesVersion, $second->rulesVersion);
    }
}
