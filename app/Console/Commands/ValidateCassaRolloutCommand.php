<?php

namespace App\Console\Commands;

use App\Models\LottoProduzione;
use App\Services\Production\CassaRolloutValidationService;
use Illuminate\Console\Command;

class ValidateCassaRolloutCommand extends Command
{
    protected $signature = 'production:cassa-rollout-validate
        {--lotto-id= : Analizza un singolo lotto}
        {--from-id= : Analizza solo lotti con ID >= valore}
        {--limit=200 : Numero massimo di lotti da analizzare}
        {--only-significant : Mostra in tabella solo i casi significativi}
        {--json= : Salva report JSON (path assoluto o relativo alla root progetto)}';

    protected $description = 'Confronta optimizer cassa vs legacy su dataset lotti reali e produce delta report';

    public function handle(CassaRolloutValidationService $validationService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $lottoId = $this->option('lotto-id');
        $fromId = $this->option('from-id');
        $onlySignificant = (bool) $this->option('only-significant');
        $jsonPath = $this->option('json');

        $query = LottoProduzione::query()
            ->with(['costruzione.componenti'])
            ->whereHas('costruzione', fn ($q) => $q->where('categoria', 'cassa'))
            ->orderBy('id');

        if (is_numeric($lottoId)) {
            $query->whereKey((int) $lottoId);
        }

        if (is_numeric($fromId)) {
            $query->where('id', '>=', (int) $fromId);
        }

        /** @var \Illuminate\Support\Collection<int, LottoProduzione> $lotti */
        $lotti = $query->limit($limit)->get();

        if ($lotti->isEmpty()) {
            $this->warn('Nessun lotto cassa trovato con i filtri specificati.');
            return self::SUCCESS;
        }

        $reports = [];
        $rowsForTable = [];
        $analyzed = 0;
        $ok = 0;
        $errors = 0;
        $significant = 0;

        foreach ($lotti as $lotto) {
            $report = $validationService->analyzeLotto($lotto);
            $reports[] = $report;
            $analyzed++;

            if (($report['status'] ?? null) !== 'ok') {
                $errors++;

                if (!$onlySignificant) {
                    $rowsForTable[] = [
                        (string) data_get($report, 'lotto.id', '-'),
                        (string) data_get($report, 'lotto.codice_lotto', '-'),
                        'error',
                        '-',
                        '-',
                        '-',
                        substr((string) ($report['error'] ?? ''), 0, 80),
                    ];
                }

                continue;
            }

            $ok++;
            $isSignificant = (bool) ($report['significant'] ?? false);
            if ($isSignificant) {
                $significant++;
            }

            if ($onlySignificant && !$isSignificant) {
                continue;
            }

            $rowsForTable[] = [
                (string) data_get($report, 'lotto.id', '-'),
                (string) data_get($report, 'lotto.codice_lotto', '-'),
                $isSignificant ? 'yes' : 'no',
                (string) data_get($report, 'deltas.total_bins', 0),
                (string) data_get($report, 'deltas.total_waste_percent', 0),
                (string) data_get($report, 'deltas.volume_lordo_mc', 0),
                (string) data_get($report, 'deltas.volume_netto_mc', 0),
            ];
        }

        $this->info("Analizzati: {$analyzed}");
        $this->info("OK: {$ok}");
        $this->info("Errori: {$errors}");
        $this->info("Significativi: {$significant}");

        if ($rowsForTable !== []) {
            $this->table(
                ['Lotto ID', 'Codice', 'Signif.', 'Delta bins', 'Delta scarto %', 'Delta vol lordo', 'Delta vol netto'],
                $rowsForTable
            );
        }

        if (is_string($jsonPath) && trim($jsonPath) !== '') {
            $resolvedPath = $this->resolveOutputPath($jsonPath);
            $encoded = json_encode([
                'generated_at' => now()->toIso8601String(),
                'summary' => [
                    'analyzed' => $analyzed,
                    'ok' => $ok,
                    'errors' => $errors,
                    'significant' => $significant,
                ],
                'reports' => $reports,
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

        return self::SUCCESS;
    }

    private function resolveOutputPath(string $jsonPath): string
    {
        $trimmed = trim($jsonPath);
        if ($trimmed === '') {
            return base_path('storage/app/cassa_rollout_validation_report.json');
        }

        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        return base_path($trimmed);
    }
}
