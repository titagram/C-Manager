<?php

namespace Tests\Feature\Commands;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidateCassaRolloutCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_rollout_validation_summary_and_json_report(): void
    {
        config()->set('production.cassa_shadow_compare_volume_delta_mc', 0.0001);
        config()->set('production.cassa_shadow_compare_waste_delta_percent', 0.1);

        $user = User::factory()->create();

        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'nome' => 'Cassa Command Validate Test',
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

        LottoProduzione::factory()->bozza()->create([
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

        $jsonRelativePath = 'storage/app/testing/cassa_rollout_validation_report.json';
        $jsonAbsolutePath = base_path($jsonRelativePath);
        if (is_file($jsonAbsolutePath)) {
            @unlink($jsonAbsolutePath);
        }

        $this->artisan("production:cassa-rollout-validate --limit=10 --json={$jsonRelativePath}")
            ->expectsOutputToContain('Analizzati: 1')
            ->expectsOutputToContain('OK: 1')
            ->expectsOutputToContain('Errori: 0')
            ->expectsOutputToContain('Significativi: 1')
            ->expectsOutputToContain('Report JSON salvato in:')
            ->assertExitCode(0);

        $this->assertFileExists($jsonAbsolutePath);

        $decoded = json_decode((string) file_get_contents($jsonAbsolutePath), true);
        $this->assertIsArray($decoded);
        $this->assertSame(1, (int) data_get($decoded, 'summary.analyzed'));
        $this->assertSame(1, (int) data_get($decoded, 'summary.ok'));
        $this->assertSame(1, (int) data_get($decoded, 'summary.significant'));
        $this->assertSame('ok', data_get($decoded, 'reports.0.status'));
    }
}
