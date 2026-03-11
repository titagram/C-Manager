<?php

namespace App\Console\Commands;

use App\Models\LottoProduzione;
use App\Models\LottoProduzioneMateriale;
use App\Services\Production\DTO\OptimizerResultPayload;
use Illuminate\Console\Command;

class BackfillLottoMaterialVolumesCommand extends Command
{
    protected $signature = 'production:backfill-lotto-material-volumes
        {--lotto-id= : Backfill di un singolo lotto}
        {--from-id= : Backfill solo lotti con ID >= valore}
        {--limit=500 : Numero massimo di lotti da processare}
        {--dry-run : Simula il backfill senza salvare modifiche}
        {--force : Aggiorna anche righe con valori gia valorizzati}
        {--json= : Salva report JSON (path assoluto o relativo alla root progetto)}';

    protected $description = 'Backfill dei campi volume_netto_mc / volume_scarto_mc in lotto_produzione_materiali da optimizer_result';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $lottoId = $this->option('lotto-id');
        $fromId = $this->option('from-id');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $jsonPath = $this->option('json');

        $query = LottoProduzione::withTrashed()
            ->with(['materialiUsati' => fn ($q) => $q->orderBy('ordine')])
            ->has('materialiUsati')
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
            $this->warn('Nessun lotto trovato con optimizer_result e materialiUsati da backfillare.');

            return self::SUCCESS;
        }

        $reports = [];
        $rowsForTable = [];
        $analyzed = 0;
        $updatedRows = 0;
        $skippedRows = 0;
        $errors = 0;

        foreach ($lotti as $lotto) {
            $analyzed++;

            try {
                $report = $this->processLotto($lotto, $dryRun, $force);
                $reports[] = $report;

                $updatedRows += (int) ($report['rows_updated'] ?? 0);
                $skippedRows += (int) ($report['rows_skipped'] ?? 0);

                $rowsForTable[] = [
                    (string) ($report['lotto_id'] ?? '-'),
                    (string) ($report['codice_lotto'] ?? '-'),
                    (string) ($report['status'] ?? 'ok'),
                    (string) ($report['rows_updated'] ?? 0),
                    (string) ($report['rows_skipped'] ?? 0),
                    (string) ($report['note'] ?? '-'),
                ];
            } catch (\Throwable $e) {
                $errors++;
                $reports[] = [
                    'lotto_id' => $lotto->id,
                    'codice_lotto' => $lotto->codice_lotto,
                    'status' => 'error',
                    'rows_updated' => 0,
                    'rows_skipped' => count($lotto->materialiUsati),
                    'note' => $e->getMessage(),
                ];

                $rowsForTable[] = [
                    (string) $lotto->id,
                    (string) ($lotto->codice_lotto ?? '-'),
                    'error',
                    '0',
                    (string) count($lotto->materialiUsati),
                    substr($e->getMessage(), 0, 80),
                ];
            }
        }

        $this->info("Analizzati: {$analyzed}");
        $this->info('Modalita: ' . ($dryRun ? 'dry-run' : 'write'));
        $this->info("Righe aggiornate: {$updatedRows}");
        $this->info("Righe saltate: {$skippedRows}");
        $this->info("Errori: {$errors}");

        if ($rowsForTable !== []) {
            $this->table(
                ['Lotto ID', 'Codice', 'Status', 'Rows updated', 'Rows skipped', 'Note'],
                $rowsForTable
            );
        }

        if (is_string($jsonPath) && trim($jsonPath) !== '') {
            $resolvedPath = $this->resolveOutputPath($jsonPath);
            $encoded = json_encode([
                'generated_at' => now()->toIso8601String(),
                'summary' => [
                    'analyzed' => $analyzed,
                    'updated_rows' => $updatedRows,
                    'skipped_rows' => $skippedRows,
                    'errors' => $errors,
                    'dry_run' => $dryRun,
                    'force' => $force,
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

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{
     *   lotto_id:int,
     *   codice_lotto:?string,
     *   status:string,
     *   rows_updated:int,
     *   rows_skipped:int,
     *   note:string
     * }
     */
    private function processLotto(LottoProduzione $lotto, bool $dryRun, bool $force): array
    {
        $payload = OptimizerResultPayload::normalizeForRuntime($lotto->optimizer_result);
        $payload = is_array($payload) ? $payload : [];
        $bins = is_array($payload['bins'] ?? null) ? array_values(array_filter($payload['bins'], 'is_array')) : [];

        $rows = $lotto->materialiUsati->sortBy('ordine')->values();
        $rowsUpdated = 0;
        $rowsSkipped = 0;
        $fallbackRowsUpdated = 0;
        $pairCount = min(count($rows), count($bins));

        for ($index = 0; $index < $pairCount; $index++) {
            /** @var LottoProduzioneMateriale $row */
            $row = $rows[$index];
            $bin = $bins[$index];

            if (
                !$force
                && $row->volume_netto_mc !== null
                && $row->volume_scarto_mc !== null
            ) {
                $rowsSkipped++;
                continue;
            }

            $resolved = $this->resolveVolumesForRow(
                payload: $payload,
                bin: $bin,
                row: $row
            );

            if ($resolved === null) {
                $resolved = $this->resolveVolumesFromRowFields($row);
            }

            if ($resolved === null) {
                $rowsSkipped++;
                continue;
            }

            $updates = [];
            if ($force || $row->volume_netto_mc === null) {
                $updates['volume_netto_mc'] = $resolved['volume_netto_mc'];
            }
            if ($force || $row->volume_scarto_mc === null) {
                $updates['volume_scarto_mc'] = $resolved['volume_scarto_mc'];
            }

            if ($updates === []) {
                $rowsSkipped++;
                continue;
            }

            if (!$dryRun) {
                $row->update($updates);
            }

            $rowsUpdated++;
            if (!isset($bin['volume_netto_mc']) || !isset($bin['volume_scarto_mc'])) {
                $fallbackRowsUpdated++;
            }
        }

        if (count($rows) > $pairCount) {
            for ($index = $pairCount; $index < count($rows); $index++) {
                /** @var LottoProduzioneMateriale $row */
                $row = $rows[$index];

                if (
                    !$force
                    && $row->volume_netto_mc !== null
                    && $row->volume_scarto_mc !== null
                ) {
                    $rowsSkipped++;
                    continue;
                }

                $resolved = $this->resolveVolumesFromRowFields($row);
                if ($resolved === null) {
                    $rowsSkipped++;
                    continue;
                }

                $updates = [];
                if ($force || $row->volume_netto_mc === null) {
                    $updates['volume_netto_mc'] = $resolved['volume_netto_mc'];
                }
                if ($force || $row->volume_scarto_mc === null) {
                    $updates['volume_scarto_mc'] = $resolved['volume_scarto_mc'];
                }

                if ($updates === []) {
                    $rowsSkipped++;
                    continue;
                }

                if (!$dryRun) {
                    $row->update($updates);
                }

                $rowsUpdated++;
                $fallbackRowsUpdated++;
            }
        }

        $status = $rowsUpdated > 0 ? 'updated' : 'skipped';
        $noteParts = [];
        if (count($rows) !== count($bins)) {
            $noteParts[] = sprintf('mismatch rows/bins (%d/%d)', count($rows), count($bins));
        }
        if ($fallbackRowsUpdated > 0) {
            $noteParts[] = "fallback_row_fields={$fallbackRowsUpdated}";
        }
        $note = $noteParts === [] ? '-' : implode('; ', $noteParts);

        return [
            'lotto_id' => $lotto->id,
            'codice_lotto' => $lotto->codice_lotto,
            'status' => $status,
            'rows_updated' => $rowsUpdated,
            'rows_skipped' => $rowsSkipped,
            'note' => $note,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $bin
     * @return array{volume_netto_mc: float, volume_scarto_mc: float}|null
     */
    private function resolveVolumesForRow(array $payload, array $bin, LottoProduzioneMateriale $row): ?array
    {
        $grossVolumeMc = max(0.0, (float) ($row->volume_mc ?? 0));

        $net = isset($bin['volume_netto_mc']) ? max(0.0, (float) $bin['volume_netto_mc']) : null;
        $scrap = isset($bin['volume_scarto_mc']) ? max(0.0, (float) $bin['volume_scarto_mc']) : null;

        if ($net === null || $scrap === null) {
            $derived = $this->deriveVolumesFromItemsAndWaste($payload, $bin, $row, $grossVolumeMc);
            $net ??= $derived['volume_netto_mc'] ?? null;
            $scrap ??= $derived['volume_scarto_mc'] ?? null;
        }

        if ($net === null && $scrap !== null) {
            $net = max(0.0, $grossVolumeMc - $scrap);
        }

        if ($scrap === null && $net !== null) {
            $scrap = max(0.0, $grossVolumeMc - $net);
        }

        if ($net === null || $scrap === null) {
            return null;
        }

        return [
            'volume_netto_mc' => round(max(0.0, $net), 6),
            'volume_scarto_mc' => round(max(0.0, $scrap), 6),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $bin
     * @return array{volume_netto_mc?: float, volume_scarto_mc?: float}
     */
    private function deriveVolumesFromItemsAndWaste(
        array $payload,
        array $bin,
        LottoProduzioneMateriale $row,
        float $grossVolumeMc
    ): array {
        $derived = [];

        $thicknessMm = max(0.0, (float) ($row->spessore_mm ?? 0));
        $items = is_array($bin['items'] ?? null) ? $bin['items'] : [];

        if ($items !== [] && $thicknessMm > 0) {
            $netFromItems = 0.0;
            $allWithWidth = true;

            foreach ($items as $item) {
                if (!is_array($item) || !isset($item['width'])) {
                    $allWithWidth = false;
                    break;
                }

                $itemLength = max(0.0, (float) ($item['length'] ?? 0));
                $itemWidth = max(0.0, (float) ($item['width'] ?? 0));
                $netFromItems += ($itemLength * $itemWidth * $thicknessMm) / 1000000000;
            }

            if ($allWithWidth) {
                $derived['volume_netto_mc'] = max(0.0, $netFromItems);
                $derived['volume_scarto_mc'] = max(0.0, $grossVolumeMc - $netFromItems);
            }
        }

        if (!isset($derived['volume_netto_mc']) || !isset($derived['volume_scarto_mc'])) {
            $wasteMm = max(0.0, (float) ($bin['waste'] ?? 0));
            $boardWidthMm = max(0.0, (float) ($row->larghezza_mm ?? 0));
            $boardThicknessMm = max(0.0, (float) ($row->spessore_mm ?? 0));

            if ($wasteMm > 0 && $boardWidthMm > 0 && $boardThicknessMm > 0) {
                $scrapFromWaste = ($wasteMm * $boardWidthMm * $boardThicknessMm) / 1000000000;
                $derived['volume_scarto_mc'] = max(0.0, $scrapFromWaste);
                $derived['volume_netto_mc'] = max(0.0, $grossVolumeMc - $scrapFromWaste);
            }
        }

        if (!isset($derived['volume_netto_mc']) && isset($payload['bin_length'])) {
            $capacityMm = max(0.0, (float) ($bin['capacity'] ?? $payload['bin_length']));
            $usedMm = max(0.0, $capacityMm - max(0.0, (float) ($bin['waste'] ?? 0)));
            $boardWidthMm = max(0.0, (float) ($row->larghezza_mm ?? 0));
            $boardThicknessMm = max(0.0, (float) ($row->spessore_mm ?? 0));

            if ($usedMm > 0 && $boardWidthMm > 0 && $boardThicknessMm > 0) {
                $netFromUsedLength = ($usedMm * $boardWidthMm * $boardThicknessMm) / 1000000000;
                $derived['volume_netto_mc'] = max(0.0, $netFromUsedLength);
                $derived['volume_scarto_mc'] = max(0.0, $grossVolumeMc - $netFromUsedLength);
            }
        }

        return $derived;
    }

    /**
     * @return array{volume_netto_mc: float, volume_scarto_mc: float}|null
     */
    private function resolveVolumesFromRowFields(LottoProduzioneMateriale $row): ?array
    {
        $grossVolumeMc = max(0.0, (float) ($row->volume_mc ?? 0));
        if ($grossVolumeMc <= 0) {
            return null;
        }

        $boardWidthMm = max(0.0, (float) ($row->larghezza_mm ?? 0));
        $boardThicknessMm = max(0.0, (float) ($row->spessore_mm ?? 0));

        // 1) Prefer explicit total waste length if available.
        $scartoTotaleMm = max(0.0, (float) ($row->scarto_totale_mm ?? 0));
        if ($scartoTotaleMm > 0 && $boardWidthMm > 0 && $boardThicknessMm > 0) {
            $scrap = ($scartoTotaleMm * $boardWidthMm * $boardThicknessMm) / 1000000000;
            $net = max(0.0, $grossVolumeMc - $scrap);

            return [
                'volume_netto_mc' => round($net, 6),
                'volume_scarto_mc' => round(max(0.0, $scrap), 6),
            ];
        }

        // 2) Fallback to waste-per-board * boards if available.
        $scartoPerAsseMm = max(0.0, (float) ($row->scarto_per_asse_mm ?? 0));
        $assiNecessarie = max(1, (int) ($row->assi_necessarie ?? 1));
        if ($scartoPerAsseMm > 0 && $boardWidthMm > 0 && $boardThicknessMm > 0) {
            $scrapLengthMm = $scartoPerAsseMm * $assiNecessarie;
            $scrap = ($scrapLengthMm * $boardWidthMm * $boardThicknessMm) / 1000000000;
            $net = max(0.0, $grossVolumeMc - $scrap);

            return [
                'volume_netto_mc' => round($net, 6),
                'volume_scarto_mc' => round(max(0.0, $scrap), 6),
            ];
        }

        // 3) Last resort: derive from stored scrap percentage.
        $scartoPercentuale = max(0.0, (float) ($row->scarto_percentuale ?? 0));
        if ($scartoPercentuale > 0) {
            $scrap = $grossVolumeMc * ($scartoPercentuale / 100);
            $net = max(0.0, $grossVolumeMc - $scrap);

            return [
                'volume_netto_mc' => round($net, 6),
                'volume_scarto_mc' => round(max(0.0, $scrap), 6),
            ];
        }

        return null;
    }

    private function resolveOutputPath(string $jsonPath): string
    {
        $trimmed = trim($jsonPath);
        if ($trimmed === '') {
            return base_path('storage/app/lotto_material_volumes_backfill_report.json');
        }

        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        return base_path($trimmed);
    }
}
