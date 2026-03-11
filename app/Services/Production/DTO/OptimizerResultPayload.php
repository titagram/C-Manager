<?php

namespace App\Services\Production\DTO;

class OptimizerResultPayload
{
    public const CURRENT_VERSION = 'v2';
    public const LEGACY_VERSION = 'legacy-v1';

    /**
     * Builds the persisted runtime payload for a fresh optimizer computation.
     *
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public static function fromComputation(array $result, OptimizationInput $input): array
    {
        $normalized = self::normalizeBase($result);
        $normalized['version'] = self::CURRENT_VERSION;

        $normalized['trace'] = self::mergeTraceAudit(
            trace: (array) ($normalized['trace'] ?? []),
            input: $input,
            algorithm: self::algorithmFromPayload($normalized),
            compatibility: [
                'source' => 'computed',
                'legacy_read_applied' => false,
            ]
        );

        if (!isset($normalized['component_summary'])) {
            $normalized['component_summary'] = (array) data_get($normalized, 'trace.panel_summary', []);
        }

        $normalized = self::attachResultAudit($normalized);

        return $normalized;
    }

    /**
     * Normalizes payload loaded from DB for in-memory runtime use.
     * Legacy payloads (without top-level version) remain readable and are flagged.
     *
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>|null
     */
    public static function normalizeForRuntime(?array $payload): ?array
    {
        if (!is_array($payload) || $payload === []) {
            return null;
        }

        $normalized = self::normalizeBase($payload);
        $incomingVersion = (string) ($payload['version'] ?? '');
        $isLegacy = $incomingVersion === '';

        $normalized['version'] = $isLegacy ? self::LEGACY_VERSION : $incomingVersion;
        $normalized['trace'] = self::mergeTraceAudit(
            trace: (array) ($normalized['trace'] ?? []),
            input: null,
            algorithm: self::algorithmFromPayload($normalized),
            compatibility: [
                'source' => 'runtime_read',
                'legacy_read_applied' => $isLegacy,
            ]
        );

        if (!isset($normalized['component_summary'])) {
            $normalized['component_summary'] = (array) data_get($normalized, 'trace.panel_summary', []);
        }

        $normalized = self::attachResultAudit($normalized);

        return $normalized;
    }

    /**
     * Normalizes payload before persistence and upgrades legacy payloads to current version.
     *
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>|null
     */
    public static function normalizeForPersistence(?array $payload): ?array
    {
        $runtime = self::normalizeForRuntime($payload);
        if ($runtime === null) {
            return null;
        }

        $wasLegacy = ((string) ($runtime['version'] ?? '')) === self::LEGACY_VERSION;
        $runtime['version'] = self::CURRENT_VERSION;
        $runtime['trace'] = self::mergeTraceAudit(
            trace: (array) ($runtime['trace'] ?? []),
            input: null,
            algorithm: self::algorithmFromPayload($runtime),
            compatibility: [
                'source' => 'persisted_write',
                'legacy_read_applied' => $wasLegacy,
            ]
        );

        $runtime = self::attachResultAudit($runtime);

        return $runtime;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function normalizeBase(array $payload): array
    {
        if (!isset($payload['optimizer']) || !is_array($payload['optimizer'])) {
            $payload['optimizer'] = [
                'name' => 'legacy-bin-packing',
                'version' => 'legacy-1d-v1',
                'strategy' => 'direct-1d-bfd',
            ];
        }

        if (!isset($payload['trace']) || !is_array($payload['trace'])) {
            $payload['trace'] = [];
        }

        if (!isset($payload['bins']) || !is_array($payload['bins'])) {
            $payload['bins'] = [];
        }

        $payload['bins'] = self::normalizeBins($payload['bins'], $payload['materiale'] ?? null);

        if (!isset($payload['total_bins'])) {
            $payload['total_bins'] = count($payload['bins']);
        }

        if (!isset($payload['totali']) || !is_array($payload['totali'])) {
            $payload['totali'] = [];
        }

        if (!array_key_exists('volume_totale_mc', $payload['totali'])) {
            $payload['totali']['volume_totale_mc'] = (float) (
                $payload['totali']['volume_lordo_mc']
                    ?? data_get($payload, 'cutting_totals.volume_lordo_mc', 0)
            );
        }

        if (!array_key_exists('volume_lordo_mc', $payload['totali'])) {
            $payload['totali']['volume_lordo_mc'] = (float) ($payload['totali']['volume_totale_mc'] ?? 0);
        }

        if (!array_key_exists('pricing_volume_basis', $payload['totali'])) {
            $payload['totali']['pricing_volume_basis'] = 'lordo';
        }

        $payload['totali'] = self::normalizeVolumeTotals($payload['totali']);

        return $payload;
    }

    /**
     * @param  array<int, mixed>  $bins
     * @param  mixed  $topLevelMaterial
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeBins(array $bins, mixed $topLevelMaterial): array
    {
        $materialSnapshot = is_array($topLevelMaterial) ? $topLevelMaterial : null;

        return array_values(array_map(function (mixed $bin) use ($materialSnapshot): array {
            $normalized = is_array($bin) ? $bin : [];

            if (! isset($normalized['source_material_id']) && is_array($normalized['source_material'] ?? null)) {
                $normalized['source_material_id'] = $normalized['source_material']['id'] ?? null;
            }

            if (! is_array($normalized['source_material'] ?? null) && $materialSnapshot !== null) {
                $normalized['source_material'] = $materialSnapshot;
            }

            if (! isset($normalized['source_material_id']) && $materialSnapshot !== null) {
                $normalized['source_material_id'] = $materialSnapshot['id'] ?? null;
            }

            if (! isset($normalized['source_type'])) {
                $normalized['source_type'] = 'primary';
            }

            if (! is_array($normalized['substitution_meta'] ?? null)) {
                $normalized['substitution_meta'] = [];
            }

            return $normalized;
        }, $bins));
    }

    /**
     * @param array<string, mixed> $totali
     * @return array<string, mixed>
     */
    private static function normalizeVolumeTotals(array $totali): array
    {
        $volumeLordo = round(max(0, (float) ($totali['volume_lordo_mc'] ?? $totali['volume_totale_mc'] ?? 0)), 6);

        $volumeNetto = array_key_exists('volume_netto_mc', $totali)
            ? round(max(0, (float) ($totali['volume_netto_mc'] ?? 0)), 6)
            : null;

        if ($volumeNetto !== null) {
            $volumeNetto = min($volumeLordo, $volumeNetto);
        }

        $volumeScarto = array_key_exists('volume_scarto_mc', $totali)
            ? round(max(0, (float) ($totali['volume_scarto_mc'] ?? 0)), 6)
            : null;

        if ($volumeNetto !== null) {
            $volumeScarto = round(max(0, $volumeLordo - $volumeNetto), 6);
        } elseif ($volumeScarto !== null) {
            $volumeScarto = min($volumeLordo, $volumeScarto);
        }

        $totali['volume_totale_mc'] = $volumeLordo;
        $totali['volume_lordo_mc'] = $volumeLordo;
        $totali['volume_netto_mc'] = $volumeNetto;
        $totali['volume_scarto_mc'] = $volumeScarto;

        return $totali;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{name: string, version: string, strategy: string}
     */
    private static function algorithmFromPayload(array $payload): array
    {
        return [
            'name' => (string) data_get($payload, 'optimizer.name', 'legacy-bin-packing'),
            'version' => (string) data_get($payload, 'optimizer.version', 'legacy-1d-v1'),
            'strategy' => (string) data_get($payload, 'optimizer.strategy', 'direct-1d-bfd'),
        ];
    }

    /**
     * @param array<string, mixed> $trace
     * @param array{name: string, version: string, strategy: string} $algorithm
     * @param array{source: string, legacy_read_applied: bool} $compatibility
     * @return array<string, mixed>
     */
    private static function mergeTraceAudit(
        array $trace,
        ?OptimizationInput $input,
        array $algorithm,
        array $compatibility
    ): array {
        $audit = is_array($trace['audit'] ?? null) ? $trace['audit'] : [];

        if (!isset($audit['logical_timestamp'])) {
            $audit['logical_timestamp'] = now()->toIso8601String();
        }

        $audit['algorithm'] = array_merge(
            is_array($audit['algorithm'] ?? null) ? $audit['algorithm'] : [],
            $algorithm
        );

        $audit['compatibility'] = array_merge(
            is_array($audit['compatibility'] ?? null) ? $audit['compatibility'] : [],
            $compatibility
        );

        if ($input !== null) {
            $audit['input'] = $input->toTraceArray();
        }

        $trace['audit'] = $audit;

        return $trace;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function attachResultAudit(array $payload): array
    {
        $trace = is_array($payload['trace'] ?? null) ? $payload['trace'] : [];
        $audit = is_array($trace['audit'] ?? null) ? $trace['audit'] : [];
        $existing = is_array($audit['result'] ?? null) ? $audit['result'] : [];

        $summary = [
            'total_bins' => (int) ($payload['total_bins'] ?? count((array) ($payload['bins'] ?? []))),
            'total_waste_percent' => round(max(0, (float) ($payload['total_waste_percent'] ?? 0)), 4),
            'volume_lordo_mc' => round(max(0, (float) data_get($payload, 'totali.volume_lordo_mc', data_get($payload, 'totali.volume_totale_mc', 0))), 6),
            'volume_netto_mc' => data_get($payload, 'totali.volume_netto_mc'),
            'optimizer' => [
                'name' => (string) data_get($payload, 'optimizer.name', 'legacy-bin-packing'),
                'version' => (string) data_get($payload, 'optimizer.version', 'legacy-1d-v1'),
                'strategy' => (string) data_get($payload, 'optimizer.strategy', 'direct-1d-bfd'),
            ],
        ];

        $signature = hash('sha256', json_encode(self::normalizeForResultSignature($summary), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $audit['result'] = array_merge($existing, $summary, [
            'signature_sha256' => $signature,
        ]);
        $trace['audit'] = $audit;
        $payload['trace'] = $trace;

        return $payload;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function normalizeForResultSignature(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $isAssoc = array_keys($value) !== range(0, count($value) - 1);
        if ($isAssoc) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::normalizeForResultSignature($item);
        }

        return $value;
    }
}
