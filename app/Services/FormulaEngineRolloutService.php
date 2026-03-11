<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class FormulaEngineRolloutService
{
    public function __construct(
        private FormulaEvaluatorService $newEngine,
        private LegacyFormulaEvaluatorService $legacyEngine
    ) {}

    /**
     * @param array<string, float|int> $variables
     * @param array<string, mixed> $context
     */
    public function evaluate(string $formula, array $variables = [], array $context = []): float
    {
        $rolloutKey = (string) ($context['rollout_key'] ?? 'global');
        $activeEngine = $this->useNewEngine($rolloutKey) ? 'new' : 'legacy';

        $activeValue = $this->evaluateWithEngine($activeEngine, $formula, $variables);

        if ($this->shadowCompareEnabled()) {
            $shadowEngine = $activeEngine === 'new' ? 'legacy' : 'new';
            $this->monitorDrift(
                activeEngine: $activeEngine,
                shadowEngine: $shadowEngine,
                formula: $formula,
                variables: $variables,
                activeValue: $activeValue,
                context: $context
            );
        }

        return $activeValue;
    }

    public function useNewEngine(string $rolloutKey): bool
    {
        if (!(bool) config('features.formula_engine.enabled', true)) {
            return false;
        }

        $percentage = $this->normalizedRolloutPercentage();
        if ($percentage <= 0) {
            return false;
        }

        if ($percentage >= 100) {
            return true;
        }

        return $this->rolloutBucket($rolloutKey) < $percentage;
    }

    /**
     * @param array<string, float|int> $variables
     */
    private function evaluateWithEngine(string $engine, string $formula, array $variables): float
    {
        if ($engine === 'legacy') {
            return $this->legacyEngine->evaluate($formula, $this->legacyVariables($variables));
        }

        return $this->newEngine->evaluate($formula, $variables);
    }

    /**
     * @param array<string, float|int> $variables
     * @param array<string, mixed> $context
     */
    private function monitorDrift(
        string $activeEngine,
        string $shadowEngine,
        string $formula,
        array $variables,
        float $activeValue,
        array $context
    ): void {
        try {
            $shadowValue = $this->evaluateWithEngine($shadowEngine, $formula, $variables);
            $delta = abs($activeValue - $shadowValue);
            $threshold = max(0.0, (float) config('features.formula_engine.monitoring.delta_threshold', 0.01));

            if ($delta > $threshold) {
                Log::warning('formula_engine.rollout.delta', [
                    'active_engine' => $activeEngine,
                    'shadow_engine' => $shadowEngine,
                    'formula' => $formula,
                    'active_value' => $activeValue,
                    'shadow_value' => $shadowValue,
                    'delta' => $delta,
                    'threshold' => $threshold,
                    'rollout_key' => $context['rollout_key'] ?? null,
                    'formula_type' => $context['formula_type'] ?? null,
                    'component_id' => $context['component_id'] ?? null,
                    'component_name' => $context['component_name'] ?? null,
                ]);
            }
        } catch (Throwable $shadowError) {
            Log::notice('formula_engine.rollout.shadow_error', [
                'active_engine' => $activeEngine,
                'shadow_engine' => $shadowEngine,
                'formula' => $formula,
                'error' => $shadowError->getMessage(),
                'rollout_key' => $context['rollout_key'] ?? null,
                'formula_type' => $context['formula_type'] ?? null,
                'component_id' => $context['component_id'] ?? null,
                'component_name' => $context['component_name'] ?? null,
            ]);
        }
    }

    private function shadowCompareEnabled(): bool
    {
        return (bool) config('features.formula_engine.monitoring.shadow_compare', false);
    }

    private function normalizedRolloutPercentage(): int
    {
        $percentage = (int) config('features.formula_engine.rollout_percentage', 100);
        return max(0, min(100, $percentage));
    }

    private function rolloutBucket(string $rolloutKey): int
    {
        if ($rolloutKey === '') {
            $rolloutKey = 'global';
        }

        $hash = (int) sprintf('%u', crc32($rolloutKey));
        return $hash % 100;
    }

    /**
     * @param array<string, float|int> $variables
     * @return array<string, float>
     */
    private function legacyVariables(array $variables): array
    {
        $allowed = ['L', 'P', 'A', 'S'];
        $legacy = [];

        foreach ($variables as $name => $value) {
            $upper = strtoupper($name);
            if (in_array($upper, $allowed, true)) {
                $legacy[$upper] = (float) $value;
            }
        }

        return $legacy;
    }
}
