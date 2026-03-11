<?php

namespace Tests\Feature\Commands;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Cliente;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\Production\CassaRolloutValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateCassaDatasetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_validated_dataset_with_target_count_and_json_report(): void
    {
        [$user, $cliente, $costruzione, $materiale] = $this->createDatasetScenario(
            slug: 'cassa-dataset-test',
            boardLengthMm: 3000
        );

        $jsonRelativePath = 'storage/app/testing/cassa_dataset_generation_report.json';
        $jsonAbsolutePath = base_path($jsonRelativePath);
        if (is_file($jsonAbsolutePath)) {
            @unlink($jsonAbsolutePath);
        }

        $this->artisan(
            "production:generate-cassa-dataset --count=4 --marker=TEST_DATASET_CASSA --costruzione-slug={$costruzione->slug} --materiale-id={$materiale->id} --user-id={$user->id} --cliente-id={$cliente->id} --seed=20260302 --json={$jsonRelativePath}"
        )
            ->expectsOutputToContain('Creati: 4')
            ->expectsOutputToContain('Invalidi scartati: 0')
            ->expectsOutputToContain('Totale marker finale: 4')
            ->expectsOutputToContain('Report JSON salvato in:')
            ->assertExitCode(0);

        $this->assertFileExists($jsonAbsolutePath);
        $decoded = json_decode((string) file_get_contents($jsonAbsolutePath), true);
        $this->assertIsArray($decoded);
        $this->assertSame(4, (int) data_get($decoded, 'summary.created'));
        $this->assertSame(4, (int) data_get($decoded, 'summary.final_count'));
        $this->assertTrue((bool) data_get($decoded, 'summary.validation_enabled'));

        $lotti = LottoProduzione::query()
            ->with(['costruzione.componenti', 'materialiUsati'])
            ->where('descrizione', 'like', '%TEST_DATASET_CASSA%')
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $lotti);

        $validator = app(CassaRolloutValidationService::class);

        foreach ($lotti as $lotto) {
            $this->assertSame($costruzione->id, $lotto->costruzione_id);
            $this->assertSame($user->id, $lotto->created_by);
            $this->assertCount(1, $lotto->materialiUsati);
            $this->assertSame($materiale->id, (int) $lotto->materialiUsati->first()->prodotto_id);

            $report = $validator->analyzeLotto($lotto);
            $this->assertSame('ok', $report['status'] ?? null);
        }
    }

    public function test_it_creates_only_missing_records_when_only_missing_option_is_used(): void
    {
        [$user, $cliente, $costruzione, $materiale] = $this->createDatasetScenario(
            slug: 'cassa-dataset-only-missing',
            boardLengthMm: 3000
        );

        LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'cliente_id' => $cliente->id,
            'costruzione_id' => $costruzione->id,
            'larghezza_cm' => 100,
            'profondita_cm' => 50,
            'altezza_cm' => 100,
            'numero_pezzi' => 1,
            'descrizione' => 'ONLY_MISSING_MARKER | pre-existing 1',
        ]);
        LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
            'cliente_id' => $cliente->id,
            'costruzione_id' => $costruzione->id,
            'larghezza_cm' => 120,
            'profondita_cm' => 60,
            'altezza_cm' => 100,
            'numero_pezzi' => 1,
            'descrizione' => 'ONLY_MISSING_MARKER | pre-existing 2',
        ]);

        $this->artisan(
            "production:generate-cassa-dataset --count=4 --only-missing --marker=ONLY_MISSING_MARKER --costruzione-slug={$costruzione->slug} --materiale-id={$materiale->id} --user-id={$user->id} --cliente-id={$cliente->id} --seed=20260302"
        )
            ->expectsOutputToContain('Da creare: 2')
            ->expectsOutputToContain('Creati: 2')
            ->assertExitCode(0);

        $this->assertSame(
            4,
            LottoProduzione::query()
                ->where('descrizione', 'like', '%ONLY_MISSING_MARKER%')
                ->count()
        );

        $this->artisan(
            "production:generate-cassa-dataset --count=4 --only-missing --marker=ONLY_MISSING_MARKER --costruzione-slug={$costruzione->slug} --materiale-id={$materiale->id} --user-id={$user->id} --cliente-id={$cliente->id} --seed=20260302"
        )
            ->expectsOutputToContain('Da creare: 0')
            ->expectsOutputToContain('Nessun lotto da creare (target gia raggiunto).')
            ->assertExitCode(0);
    }

    public function test_it_does_not_persist_records_in_dry_run_mode(): void
    {
        [$user, $cliente, $costruzione, $materiale] = $this->createDatasetScenario(
            slug: 'cassa-dataset-dry-run',
            boardLengthMm: 3000
        );

        $this->artisan(
            "production:generate-cassa-dataset --count=3 --dry-run --marker=DRY_RUN_CASSA --costruzione-slug={$costruzione->slug} --materiale-id={$materiale->id} --user-id={$user->id} --cliente-id={$cliente->id} --seed=20260302"
        )
            ->expectsOutputToContain('Modalita: dry-run')
            ->expectsOutputToContain('Creati: 0')
            ->assertExitCode(0);

        $this->assertSame(
            0,
            LottoProduzione::query()
                ->where('descrizione', 'like', '%DRY_RUN_CASSA%')
                ->count()
        );
    }

    public function test_it_fails_when_target_cannot_be_reached_with_validation_enabled(): void
    {
        [$user, $cliente, $materiale] = $this->createInvalidScenario();

        $this->artisan(
            "production:generate-cassa-dataset --count=2 --max-attempts=2 --marker=INVALID_DATASET_CASSA --costruzione-slug=cassa-invalid-dataset --materiale-id={$materiale->id} --user-id={$user->id} --cliente-id={$cliente->id} --seed=20260302"
        )
            ->expectsOutputToContain('Invalidi scartati:')
            ->expectsOutputToContain('Nessun lotto valido creato.')
            ->assertExitCode(1);

        $this->assertSame(
            0,
            LottoProduzione::query()
                ->where('descrizione', 'like', '%INVALID_DATASET_CASSA%')
                ->count()
        );
    }

    /**
     * @return array{User, Cliente, Costruzione, Prodotto}
     */
    private function createDatasetScenario(string $slug, int $boardLengthMm): array
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'slug' => $slug,
            'nome' => "Costruzione {$slug}",
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete lunga esterna',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
        ]);
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete corta interna',
            'formula_lunghezza' => 'W - (2 * T)',
            'formula_larghezza' => 'H',
            'formula_quantita' => '2',
            'is_internal' => true,
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
            'lunghezza_mm' => $boardLengthMm,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        return [$user, $cliente, $costruzione, $materiale];
    }

    /**
     * @return array{User, Cliente, Prodotto}
     */
    private function createInvalidScenario(): array
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'slug' => 'cassa-invalid-dataset',
            'nome' => 'Costruzione cassa invalid dataset',
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Componente invalido',
            'formula_lunghezza' => 'L + 5000',
            'formula_larghezza' => 'H',
            'formula_quantita' => '1',
        ]);

        $materiale = Prodotto::factory()->create([
            'categoria' => Categoria::ASSE,
            'unita_misura' => UnitaMisura::MC,
            'is_active' => true,
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        return [$user, $cliente, $materiale];
    }
}
