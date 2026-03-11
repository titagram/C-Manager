<?php

namespace Tests\Unit\Services\Production;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\Production\CassaRolloutValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CassaRolloutValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_delta_report_for_cassa_lotto(): void
    {
        config()->set('production.cassa_shadow_compare_volume_delta_mc', 0.0001);
        config()->set('production.cassa_shadow_compare_waste_delta_percent', 0.1);

        $user = User::factory()->create();

        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'nome' => 'Cassa Validate Service Test',
            'config' => [],
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete Lunga Esterna',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
        ]);
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete Corta Interna',
            'formula_lunghezza' => 'W - (2 * T)',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
        ]);
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Fondo',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'W',
            'formula_quantita' => '1',
        ]);

        $materiale = Prodotto::factory()->create([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'is_active' => true,
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'costruzione_id' => $costruzione->id,
            'larghezza_cm' => 100,
            'profondita_cm' => 50,
            'altezza_cm' => 100,
            'numero_pezzi' => 1,
            'optimizer_result' => [
                'materiale' => [
                    'id' => $materiale->id,
                ],
            ],
        ]);

        $service = app(CassaRolloutValidationService::class);
        $report = $service->analyzeLotto($lotto->fresh('costruzione.componenti'));

        $this->assertSame('ok', $report['status']);
        $this->assertTrue((bool) $report['significant']);
        $this->assertSame('cassa', data_get($report, 'active.optimizer'));
        $this->assertSame('legacy-bin-packing', data_get($report, 'legacy.optimizer'));
        $this->assertGreaterThan(0, (int) data_get($report, 'active.total_bins', 0));
        $this->assertGreaterThan(0, (int) data_get($report, 'legacy.total_bins', 0));
        $this->assertIsArray(data_get($report, 'deltas'));
        $this->assertArrayHasKey('volume_lordo_mc', $report['deltas']);
        $this->assertArrayHasKey('volume_netto_mc', $report['deltas']);
    }
}
