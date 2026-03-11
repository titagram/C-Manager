<?php

namespace App\Services\Production;

use App\Models\ProductionSetting;
use App\Models\ProductionSettingHistory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ProductionSettingsService
{
    private const DB_CACHE_KEY = 'production_settings:db_values:v1';

    private ?bool $settingsTableExists = null;

    private ?bool $historyTableExists = null;

    /**
     * @return array<string, array{
     *   type:string,
     *   config:string,
     *   default:mixed,
     *   env:string,
     *   options?:array<int, string>
     * }>
     */
    public function definitions(): array
    {
        return [
            'cutting_kerf_mm' => [
                'type' => 'float',
                'config' => 'production.cutting_kerf_mm',
                'default' => 0.0,
                'env' => 'PRODUCTION_CUTTING_KERF_MM',
            ],
            'cassa_optimizer_mode' => [
                'type' => 'string',
                'config' => 'production.cassa_optimizer_mode',
                'default' => 'physical',
                'env' => 'PRODUCTION_CASSA_OPTIMIZER_MODE',
                'options' => ['physical', 'excel_strict', 'legacy', 'category'],
            ],
            'gabbia_excel_mode' => [
                'type' => 'string',
                'config' => 'production.gabbia_excel_mode',
                'default' => 'preview',
                'env' => 'PRODUCTION_GABBIA_EXCEL_MODE',
                'options' => ['preview', 'compatibility', 'strict'],
            ],
            'bancale_excel_mode' => [
                'type' => 'string',
                'config' => 'production.bancale_excel_mode',
                'default' => 'preview',
                'env' => 'PRODUCTION_BANCALE_EXCEL_MODE',
                'options' => ['preview', 'compatibility', 'strict'],
            ],
            'legaccio_excel_mode' => [
                'type' => 'string',
                'config' => 'production.legaccio_excel_mode',
                'default' => 'preview',
                'env' => 'PRODUCTION_LEGACCIO_EXCEL_MODE',
                'options' => ['preview', 'compatibility', 'strict'],
            ],
            'scrap_reusable_min_length_mm' => [
                'type' => 'int',
                'config' => 'production.scrap_reusable_min_length_mm',
                'default' => 500,
                'env' => 'SCRAP_REUSABLE_MIN_LENGTH_MM',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $definitions = $this->definitions();
        $dbValues = $this->dbValues();
        $resolved = [];

        foreach ($definitions as $key => $definition) {
            $options = $definition['options'] ?? [];
            if (array_key_exists($key, $dbValues)) {
                $resolved[$key] = $this->castValue(
                    raw: $dbValues[$key],
                    type: $definition['type'],
                    default: $definition['default'],
                    options: $options
                );

                continue;
            }

            $resolved[$key] = $this->castValue(
                raw: config($definition['config'], $definition['default']),
                type: $definition['type'],
                default: $definition['default'],
                options: $options
            );
        }

        return $resolved;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $definitions = $this->definitions();
        if (! isset($definitions[$key])) {
            return $fallback;
        }

        return $this->all()[$key] ?? $fallback;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function snapshotForTrace(array $keys = []): array
    {
        $all = $this->all();

        if ($keys === []) {
            return $all;
        }

        return Arr::only($all, $keys);
    }

    public function cuttingKerfMm(): float
    {
        return max(0, (float) $this->get('cutting_kerf_mm', 0.0));
    }

    public function gabbiaExcelMode(): string
    {
        return (string) $this->get('gabbia_excel_mode', 'preview');
    }

    public function cassaOptimizerMode(): string
    {
        $mode = strtolower((string) $this->get('cassa_optimizer_mode', 'physical'));

        return $mode === 'category' ? 'physical' : $mode;
    }

    public function cassaCategoryOptimizerEnabled(): bool
    {
        return $this->cassaOptimizerMode() !== 'legacy';
    }

    public function bancaleExcelMode(): string
    {
        return (string) $this->get('bancale_excel_mode', 'preview');
    }

    public function legaccioExcelMode(): string
    {
        return (string) $this->get('legaccio_excel_mode', 'preview');
    }

    public function scrapReusableMinLengthMm(): int
    {
        return max(0, (int) $this->get('scrap_reusable_min_length_mm', 500));
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{saved:array<string, mixed>, locked:array<int, string>}
     */
    public function updateMany(array $values, ?int $userId = null, ?string $reason = null): array
    {
        if (! (bool) config('production.settings_db_enabled', true)) {
            throw new \RuntimeException('Persistenza settings produzione disabilitata da configurazione.');
        }

        if (! $this->settingsTableExists()) {
            throw new \RuntimeException('Tabella production_settings non disponibile. Eseguire le migration.');
        }

        $definitions = $this->definitions();
        $lockedKeys = $this->lockedKeys();

        $saved = [];
        $locked = [];

        foreach ($values as $key => $rawValue) {
            if (! isset($definitions[$key])) {
                continue;
            }

            if (in_array($key, $lockedKeys, true)) {
                $locked[] = $key;

                continue;
            }

            $definition = $definitions[$key];
            $existingSetting = ProductionSetting::query()->where('key', $key)->first();
            $oldStoredValue = $existingSetting?->value;
            $normalized = $this->normalizeForStorage(
                raw: $rawValue,
                type: $definition['type'],
                default: $definition['default'],
                options: $definition['options'] ?? []
            );

            if ($oldStoredValue !== null && $oldStoredValue === $normalized['stored']) {
                continue;
            }

            ProductionSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $normalized['stored'],
                    'type' => $definition['type'],
                    'updated_by' => $userId,
                ]
            );

            $this->writeHistoryRow(
                key: $key,
                type: $definition['type'],
                oldValue: $oldStoredValue,
                newValue: $normalized['stored'],
                userId: $userId,
                reason: $reason
            );

            $saved[$key] = $normalized['typed'];
        }

        $this->forgetCache();

        return [
            'saved' => $saved,
            'locked' => $locked,
        ];
    }

    public function isLockPolicyActive(): bool
    {
        $enabled = (bool) config('production.settings_lock_enabled', false);
        if (! $enabled) {
            return false;
        }

        $onlyProduction = (bool) config('production.settings_lock_only_production', true);
        if (! $onlyProduction) {
            return true;
        }

        return app()->environment('production');
    }

    /**
     * @return array<int, string>
     */
    public function lockedKeys(): array
    {
        if (! $this->isLockPolicyActive()) {
            return [];
        }

        $allowedKeys = array_keys($this->definitions());
        $configuredKeys = (array) config('production.settings_locked_keys', []);
        $configuredKeys = array_map(static fn ($key) => (string) $key, $configuredKeys);

        return array_values(array_intersect($allowedKeys, $configuredKeys));
    }

    public function isLocked(string $key): bool
    {
        return in_array($key, $this->lockedKeys(), true);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::DB_CACHE_KEY);
    }

    /**
     * @return Collection<int, ProductionSettingHistory>
     */
    public function recentHistory(int $limit = 20): Collection
    {
        if (! $this->historyTableExists()) {
            return collect();
        }

        return ProductionSettingHistory::query()
            ->with('changedBy:id,name,email')
            ->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function dbValues(): array
    {
        if (! (bool) config('production.settings_db_enabled', true)) {
            return [];
        }

        if (! $this->settingsTableExists()) {
            return [];
        }

        if (app()->runningUnitTests()) {
            return $this->queryDbValues();
        }

        /** @var array<string, string> $values */
        $values = Cache::remember(self::DB_CACHE_KEY, now()->addHours(12), function () {
            return $this->queryDbValues();
        });

        return $values;
    }

    /**
     * @return array<string, string>
     */
    private function queryDbValues(): array
    {
        return ProductionSetting::query()
            ->whereIn('key', array_keys($this->definitions()))
            ->pluck('value', 'key')
            ->mapWithKeys(static fn ($value, $key) => [(string) $key => (string) $value])
            ->all();
    }

    private function settingsTableExists(): bool
    {
        if ($this->settingsTableExists !== null) {
            return $this->settingsTableExists;
        }

        try {
            $this->settingsTableExists = Schema::hasTable('production_settings');
        } catch (\Throwable) {
            $this->settingsTableExists = false;
        }

        return $this->settingsTableExists;
    }

    private function historyTableExists(): bool
    {
        if ($this->historyTableExists !== null) {
            return $this->historyTableExists;
        }

        try {
            $this->historyTableExists = Schema::hasTable('production_setting_histories');
        } catch (\Throwable) {
            $this->historyTableExists = false;
        }

        return $this->historyTableExists;
    }

    /**
     * @param  array<int, string>  $options
     */
    private function castValue(mixed $raw, string $type, mixed $default, array $options = []): mixed
    {
        if ($raw === null || $raw === '') {
            return $default;
        }

        return match ($type) {
            'float' => is_numeric($raw) ? (float) $raw : (float) $default,
            'int' => is_numeric($raw) ? (int) $raw : (int) $default,
            'bool' => filter_var($raw, FILTER_VALIDATE_BOOL),
            default => $this->normalizeString((string) $raw, (string) $default, $options),
        };
    }

    /**
     * @param  array<int, string>  $options
     * @return array{typed:mixed, stored:string}
     */
    private function normalizeForStorage(mixed $raw, string $type, mixed $default, array $options = []): array
    {
        $typed = match ($type) {
            'float' => $this->normalizeFloat($raw, (float) $default),
            'int' => $this->normalizeInt($raw, (int) $default),
            'bool' => filter_var($raw, FILTER_VALIDATE_BOOL),
            default => $this->normalizeString((string) $raw, (string) $default, $options),
        };

        $stored = match ($type) {
            'float' => $this->floatToStorageString((float) $typed),
            'int' => (string) $typed,
            'bool' => $typed ? '1' : '0',
            default => (string) $typed,
        };

        return [
            'typed' => $typed,
            'stored' => $stored,
        ];
    }

    /**
     * @param  array<int, string>  $options
     */
    private function normalizeString(string $value, string $default, array $options = []): string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            $normalized = strtolower(trim($default));
        }

        if ($options !== [] && ! in_array($normalized, $options, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Valore "%s" non valido. Opzioni ammesse: %s',
                $value,
                implode(', ', $options)
            ));
        }

        return $normalized;
    }

    private function normalizeFloat(mixed $value, float $default): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('Valore numerico non valido.');
        }

        return (float) $value;
    }

    private function normalizeInt(mixed $value, int $default): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('Valore intero non valido.');
        }

        return (int) $value;
    }

    private function floatToStorageString(float $value): string
    {
        $string = number_format($value, 6, '.', '');

        return rtrim(rtrim($string, '0'), '.');
    }

    private function writeHistoryRow(
        string $key,
        string $type,
        ?string $oldValue,
        string $newValue,
        ?int $userId,
        ?string $reason
    ): void {
        if (! $this->historyTableExists()) {
            return;
        }

        ProductionSettingHistory::query()->create([
            'key' => $key,
            'type' => $type,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'source' => 'admin_panel',
            'changed_reason' => $reason ? trim($reason) : null,
            'changed_by' => $userId,
            'created_at' => now(),
        ]);
    }
}
