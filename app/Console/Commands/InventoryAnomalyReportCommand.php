<?php

namespace App\Console\Commands;

use App\Services\InventoryAnomalyService;
use Illuminate\Console\Command;

class InventoryAnomalyReportCommand extends Command
{
    protected $signature = 'inventory:anomaly-report
        {--days=30 : Numero di giorni da analizzare}
        {--json= : Path file JSON opzionale per esportare il report}';

    protected $description = 'Genera un report anomalie inventario/scarti (rettifiche, mismatch scarti, consumi senza movimento).';

    public function __construct(
        private readonly InventoryAnomalyService $anomalyService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $report = $this->anomalyService->analyzeLastDays($days);

        $this->info("Report anomalie inventario - ultimi {$days} giorni");
        $this->line("Periodo: {$report['period']['from']} -> {$report['period']['to']}");

        $kpis = $report['kpis'];
        $this->table(
            ['Indicatore', 'Valore'],
            [
                ['Rettifiche negative (n.)', (string) $kpis['rettifiche_negative_count']],
                ['Rettifiche negative (quantita)', number_format((float) $kpis['rettifiche_negative_qty'], 4, ',', '.')],
                ['Copertura causale strutturata', number_format((float) $kpis['rettifiche_negative_reason_coverage_percent'], 2, ',', '.') . '%'],
                ['Sospetto ammanco (quantita)', number_format((float) $kpis['rettifiche_sospetto_ammanco_qty'], 4, ',', '.')],
                ['Lotti con mismatch scarti', (string) $kpis['scarti_mismatch_lotti_count']],
                ['Delta mismatch scarti (mc)', number_format((float) $kpis['scarti_mismatch_delta_mc'], 6, ',', '.')],
                ['Consumi senza movimento', (string) $kpis['consumi_senza_movimento_count']],
            ]
        );

        if (($report['top_lotti_rischio'] ?? []) !== []) {
            $this->line('');
            $this->info('Top lotti a rischio (mismatch scarti):');
            $this->table(
                ['Lotto', 'Delta scarto mc', 'Teorico mc', 'Registrato mc'],
                collect($report['top_lotti_rischio'])->map(function (array $row) {
                    return [
                        $row['codice_lotto'],
                        number_format((float) $row['delta_scarto_mc'], 6, ',', '.'),
                        number_format((float) $row['volume_scarto_teorico_mc'], 6, ',', '.'),
                        number_format((float) $row['volume_scarto_registrato_mc'], 6, ',', '.'),
                    ];
                })->all()
            );
        }

        if (($report['top_materiali_rettifiche'] ?? []) !== []) {
            $this->line('');
            $this->info('Top lotti materiale per rettifiche negative:');
            $this->table(
                ['Lotto materiale', 'Rettifiche qty', 'Sospetto ammanco qty', 'N. movimenti'],
                collect($report['top_materiali_rettifiche'])->map(function (array $row) {
                    return [
                        $row['codice_lotto'],
                        number_format((float) $row['quantita_rettifiche_negative'], 4, ',', '.'),
                        number_format((float) $row['quantita_sospetto_ammanco'], 4, ',', '.'),
                        (string) $row['movimenti_count'],
                    ];
                })->all()
            );
        }

        $jsonPath = (string) $this->option('json');
        if (trim($jsonPath) !== '') {
            $resolvedPath = $this->resolveOutputPath($jsonPath);
            $directory = dirname($resolvedPath);
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }

            $payload = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                $this->error('Impossibile serializzare il report JSON.');
                return self::FAILURE;
            }

            file_put_contents($resolvedPath, $payload);
            $this->info("Report JSON salvato in: {$resolvedPath}");
        }

        return self::SUCCESS;
    }

    private function resolveOutputPath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}

