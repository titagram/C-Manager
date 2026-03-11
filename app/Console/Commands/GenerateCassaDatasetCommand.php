<?php

namespace App\Console\Commands;

use App\Enums\Categoria;
use App\Enums\StatoLottoProduzione;
use App\Models\Cliente;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\Production\CassaRolloutValidationService;
use Illuminate\Console\Command;

class GenerateCassaDatasetCommand extends Command
{
    protected $signature = 'production:generate-cassa-dataset
        {--count=30 : Numero target lotti con il marker specificato}
        {--marker=DATASET_ROLLOUT_CASSA : Marker da inserire in descrizione}
        {--costruzione-slug=cassa-standard : Slug costruzione cassa da utilizzare}
        {--materiale-id= : ID materiale asse da utilizzare}
        {--user-id= : ID utente creatore lotti}
        {--cliente-id= : ID cliente da associare ai lotti}
        {--seed=20260302 : Seed random per riproducibilita}
        {--max-attempts=0 : Tentativi massimi per raggiungere il target (0 = auto)}
        {--only-missing : Crea solo i lotti mancanti fino al target}
        {--fresh : Elimina prima i lotti gia presenti con lo stesso marker}
        {--skip-validate : Salta validazione tecnica automatica post creazione}
        {--dry-run : Simula senza scrivere dati}
        {--json= : Salva report JSON (path assoluto o relativo alla root progetto)}';

    protected $description = 'Genera dataset sintetico cassa per validazione rollout su campione esteso';

    /**
     * @var array<int, array{0:float,1:float,2:float}>
     */
    private const DIMENSION_POOL_CM = [
        [80, 40, 80],
        [100, 50, 100],
        [120, 60, 100],
        [120, 80, 120],
        [140, 90, 120],
        [160, 80, 100],
        [180, 100, 120],
        [200, 100, 140],
        [220, 120, 140],
        [240, 120, 160],
        [260, 120, 160],
        [280, 140, 180],
    ];

    public function handle(CassaRolloutValidationService $validationService): int
    {
        $targetCount = max(1, (int) $this->option('count'));
        $marker = trim((string) $this->option('marker'));
        $costruzioneSlug = trim((string) $this->option('costruzione-slug'));
        $dryRun = (bool) $this->option('dry-run');
        $fresh = (bool) $this->option('fresh');
        $onlyMissing = (bool) $this->option('only-missing');
        $skipValidate = (bool) $this->option('skip-validate');
        $jsonPath = $this->option('json');
        $seed = (int) $this->option('seed');
        $maxAttemptsOption = (int) $this->option('max-attempts');

        if ($marker === '') {
            $this->error('Marker non valido.');
            return self::FAILURE;
        }

        mt_srand($seed);

        $costruzione = $this->resolveCostruzione($costruzioneSlug);
        if ($costruzione === null) {
            $this->error('Costruzione cassa non trovata. Verificare --costruzione-slug o seed costruzioni.');
            return self::FAILURE;
        }

        $materiale = $this->resolveMateriale();
        if ($materiale === null) {
            $this->error('Materiale asse compatibile non trovato. Specificare --materiale-id o creare materiale con dimensioni.');
            return self::FAILURE;
        }

        $user = $this->resolveUser();
        if ($user === null) {
            $this->error('Utente creatore non trovato. Specificare --user-id o creare almeno un utente.');
            return self::FAILURE;
        }

        $cliente = $this->resolveCliente();
        $markerQuery = LottoProduzione::withTrashed()
            ->where('descrizione', 'like', '%' . $marker . '%');

        $existingBefore = (int) $markerQuery->count();

        if ($fresh && $existingBefore > 0) {
            if ($dryRun) {
                $this->warn("Dry-run: eliminazione prevista di {$existingBefore} lotti marker.");
            } else {
                $markerQuery->get()->each(function (LottoProduzione $lotto): void {
                    $lotto->forceDelete();
                });
            }
        }

        $existingAfterCleanup = $fresh && !$dryRun
            ? 0
            : (int) LottoProduzione::withTrashed()
                ->where('descrizione', 'like', '%' . $marker . '%')
                ->count();

        $toCreate = $onlyMissing
            ? max(0, $targetCount - $existingAfterCleanup)
            : $targetCount;

        $this->info("Marker: {$marker}");
        $this->info('Seed: ' . $seed);
        $this->info('Modalita: ' . ($dryRun ? 'dry-run' : 'write'));
        $this->info('Validazione automatica: ' . ($skipValidate ? 'off' : 'on'));
        $this->info("Esistenti prima: {$existingBefore}");
        $this->info("Da creare: {$toCreate}");

        if ($toCreate === 0) {
            $this->info('Nessun lotto da creare (target gia raggiunto).');
            return self::SUCCESS;
        }

        $boardVolumeMc = $this->boardVolumeMc($materiale);
        if ($boardVolumeMc <= 0) {
            $this->error('Volume asse non valido: materiale con dimensioni mancanti/non coerenti.');
            return self::FAILURE;
        }

        $dimensionPool = $this->dimensionPoolForMaterial($materiale);
        $maxAttempts = $maxAttemptsOption > 0
            ? $maxAttemptsOption
            : max($toCreate * 10, $toCreate);

        $rows = [];
        $created = 0;
        $invalid = 0;
        $attempts = 0;
        $validationErrors = [];

        while ($created < $toCreate) {
            if (!$dryRun && $attempts >= $maxAttempts) {
                break;
            }

            if ($dryRun && $attempts >= $toCreate) {
                break;
            }

            $attempts++;

            [$larghezzaCm, $profonditaCm, $altezzaCm] = $dimensionPool[array_rand($dimensionPool)];
            $numeroPezzi = mt_rand(1, 5);
            $progressive = $existingAfterCleanup + $attempts;

            $payload = [
                'cliente_id' => $cliente?->id,
                'prodotto_finale' => "Cassa dataset {$marker} #{$progressive}",
                'descrizione' => "{$marker} | synthetic rollout dataset",
                'stato' => StatoLottoProduzione::BOZZA->value,
                'created_by' => $user->id,
                'costruzione_id' => $costruzione->id,
                'larghezza_cm' => $larghezzaCm,
                'profondita_cm' => $profonditaCm,
                'altezza_cm' => $altezzaCm,
                'tipo_prodotto' => 'CASSA STANDARD',
                'numero_pezzi' => $numeroPezzi,
            ];

            if ($dryRun) {
                $rows[] = [
                    '-',
                    "{$larghezzaCm}x{$profonditaCm}x{$altezzaCm}",
                    (string) $numeroPezzi,
                    'planned',
                ];
                continue;
            }

            $lotto = LottoProduzione::create($payload);

            $lotto->materialiUsati()->create([
                'prodotto_id' => $materiale->id,
                'descrizione' => "Dataset materiale {$marker}",
                'lunghezza_mm' => (float) $materiale->lunghezza_mm,
                'larghezza_mm' => (float) $materiale->larghezza_mm,
                'spessore_mm' => (float) $materiale->spessore_mm,
                'quantita_pezzi' => 1,
                'volume_mc' => round($boardVolumeMc, 6),
                'costo_materiale' => 0,
                'prezzo_vendita' => 0,
                'ordine' => 0,
            ]);

            if (!$skipValidate) {
                $report = $validationService->analyzeLotto($lotto->fresh(['costruzione.componenti']));
                if (($report['status'] ?? null) !== 'ok') {
                    $invalid++;
                    $validationErrors[] = [
                        'lotto_id' => $lotto->id,
                        'error' => (string) ($report['error'] ?? 'Errore validazione sconosciuto'),
                    ];
                    $lotto->forceDelete();

                    $rows[] = [
                        (string) $lotto->id,
                        "{$larghezzaCm}x{$profonditaCm}x{$altezzaCm}",
                        (string) $numeroPezzi,
                        'invalid',
                    ];
                    continue;
                }
            }

            $created++;
            $rows[] = [
                (string) $lotto->id,
                "{$larghezzaCm}x{$profonditaCm}x{$altezzaCm}",
                (string) $numeroPezzi,
                'created',
            ];
        }

        if ($rows !== []) {
            $this->table(['Lotto ID', 'Dimensioni cm', 'N pezzi', 'Status'], array_slice($rows, 0, 20));
            if (count($rows) > 20) {
                $this->info('Tabella troncata ai primi 20 record.');
            }
        }

        $finalCount = (int) LottoProduzione::withTrashed()
            ->where('descrizione', 'like', '%' . $marker . '%')
            ->count();

        $this->info("Creati: {$created}");
        $this->info("Invalidi scartati: {$invalid}");
        $this->info("Tentativi: {$attempts}");
        $this->info("Totale marker finale: {$finalCount}");

        if ($validationErrors !== []) {
            $this->warn('Validazioni fallite (prime 5):');
            foreach (array_slice($validationErrors, 0, 5) as $error) {
                $this->line("- lotto {$error['lotto_id']}: {$error['error']}");
            }
        }

        if (is_string($jsonPath) && trim($jsonPath) !== '') {
            $resolvedPath = $this->resolveOutputPath($jsonPath);
            $encoded = json_encode([
                'generated_at' => now()->toIso8601String(),
                'summary' => [
                    'marker' => $marker,
                    'mode' => $dryRun ? 'dry-run' : 'write',
                    'seed' => $seed,
                    'target_count' => $targetCount,
                    'existing_before' => $existingBefore,
                    'planned_create' => $toCreate,
                    'created' => $created,
                    'invalid_discarded' => $invalid,
                    'attempts' => $attempts,
                    'max_attempts' => $maxAttempts,
                    'final_count' => $finalCount,
                    'validation_enabled' => !$skipValidate,
                ],
                'context' => [
                    'costruzione_id' => $costruzione->id,
                    'costruzione_slug' => $costruzione->slug,
                    'materiale_id' => $materiale->id,
                    'materiale_codice' => $materiale->codice,
                    'user_id' => $user->id,
                    'cliente_id' => $cliente?->id,
                ],
                'validation_errors' => $validationErrors,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($encoded === false) {
                $this->error('Impossibile serializzare il report JSON.');
                return self::FAILURE;
            }

            $directory = dirname($resolvedPath);
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }

            file_put_contents($resolvedPath, $encoded);
            $this->info("Report JSON salvato in: {$resolvedPath}");
        }

        if (!$dryRun && $created === 0) {
            $this->error('Nessun lotto valido creato.');
            return self::FAILURE;
        }

        if (!$dryRun && $created < $toCreate) {
            $this->error("Target non raggiunto: creati {$created}/{$toCreate}. Aumentare --max-attempts o verificare regole/dimensioni.");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function resolveCostruzione(string $slug): ?Costruzione
    {
        if ($slug !== '') {
            $costruzione = Costruzione::query()
                ->where('categoria', 'cassa')
                ->where('slug', $slug)
                ->first();
            if ($costruzione !== null) {
                return $costruzione;
            }
        }

        return Costruzione::query()
            ->where('categoria', 'cassa')
            ->orderBy('id')
            ->first();
    }

    private function resolveMateriale(): ?Prodotto
    {
        $materialeId = $this->option('materiale-id');
        if (is_numeric($materialeId)) {
            $materiale = Prodotto::query()->find((int) $materialeId);
            if ($materiale !== null) {
                return $materiale;
            }
        }

        return Prodotto::query()
            ->whereIn('categoria', [
                Categoria::ASSE->value,
                Categoria::MATERIA_PRIMA->value,
                Categoria::LISTELLO->value,
            ])
            ->whereNotNull('lunghezza_mm')
            ->whereNotNull('larghezza_mm')
            ->whereNotNull('spessore_mm')
            ->where('lunghezza_mm', '>', 0)
            ->where('larghezza_mm', '>', 0)
            ->where('spessore_mm', '>', 0)
            ->orderByRaw("CASE WHEN categoria = 'asse' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    private function resolveUser(): ?User
    {
        $userId = $this->option('user-id');
        if (is_numeric($userId)) {
            $user = User::query()->find((int) $userId);
            if ($user !== null) {
                return $user;
            }
        }

        return User::query()->orderBy('id')->first();
    }

    private function resolveCliente(): ?Cliente
    {
        $clienteId = $this->option('cliente-id');
        if (is_numeric($clienteId)) {
            return Cliente::query()->find((int) $clienteId);
        }

        return Cliente::query()->orderBy('id')->first();
    }

    private function boardVolumeMc(Prodotto $materiale): float
    {
        $lengthMm = (float) ($materiale->lunghezza_mm ?? 0);
        $widthMm = (float) ($materiale->larghezza_mm ?? 0);
        $thicknessMm = (float) ($materiale->spessore_mm ?? 0);

        if ($lengthMm <= 0 || $widthMm <= 0 || $thicknessMm <= 0) {
            return 0.0;
        }

        return ($lengthMm * $widthMm * $thicknessMm) / 1000000000;
    }

    /**
     * @return array<int, array{0:float,1:float,2:float}>
     */
    private function dimensionPoolForMaterial(Prodotto $materiale): array
    {
        $boardLengthMm = (float) ($materiale->lunghezza_mm ?? 0);
        if ($boardLengthMm <= 0) {
            return self::DIMENSION_POOL_CM;
        }

        $filtered = array_values(array_filter(
            self::DIMENSION_POOL_CM,
            fn(array $triple): bool => ($triple[0] * 10) <= $boardLengthMm
                && ($triple[1] * 10) <= $boardLengthMm
                && ($triple[2] * 10) <= $boardLengthMm
        ));

        return $filtered !== [] ? $filtered : self::DIMENSION_POOL_CM;
    }

    private function resolveOutputPath(string $jsonPath): string
    {
        $trimmed = trim($jsonPath);
        if ($trimmed === '') {
            return base_path('storage/app/cassa_dataset_generation_report.json');
        }

        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        return base_path($trimmed);
    }
}
