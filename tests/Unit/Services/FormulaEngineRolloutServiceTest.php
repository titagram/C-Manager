<?php

namespace Tests\Unit\Services;

use App\Services\FormulaEngineRolloutService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Tests\TestCase;

class FormulaEngineRolloutServiceTest extends TestCase
{
    public function test_it_uses_new_engine_when_rollout_is_fully_enabled(): void
    {
        config()->set('features.formula_engine.enabled', true);
        config()->set('features.formula_engine.rollout_percentage', 100);
        config()->set('features.formula_engine.monitoring.shadow_compare', false);

        $service = app(FormulaEngineRolloutService::class);
        $result = $service->evaluate(
            'ceil(L / 3)',
            ['L' => 10],
            ['rollout_key' => 'lotto:full']
        );

        $this->assertSame(4.0, $result);
        $this->assertTrue($service->useNewEngine('lotto:full'));
    }

    public function test_it_uses_legacy_engine_when_feature_is_disabled(): void
    {
        config()->set('features.formula_engine.enabled', false);
        config()->set('features.formula_engine.monitoring.shadow_compare', false);

        $service = app(FormulaEngineRolloutService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Funzioni non supportate dal motore legacy.');

        $service->evaluate(
            'ceil(L / 3)',
            ['L' => 10, 'P' => 10, 'A' => 10, 'S' => 1],
            ['rollout_key' => 'lotto:legacy']
        );
    }

    public function test_it_logs_shadow_errors_when_monitoring_is_enabled(): void
    {
        config()->set('features.formula_engine.enabled', true);
        config()->set('features.formula_engine.rollout_percentage', 100);
        config()->set('features.formula_engine.monitoring.shadow_compare', true);

        Log::spy();

        $service = app(FormulaEngineRolloutService::class);
        $result = $service->evaluate(
            'W + 10',
            ['W' => 100, 'L' => 100, 'P' => 100, 'A' => 80, 'S' => 20],
            [
                'rollout_key' => 'lotto:shadow',
                'formula_type' => 'length',
                'component_id' => 99,
                'component_name' => 'Parete',
            ]
        );

        $this->assertSame(110.0, $result);

        Log::shouldHaveReceived('notice')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'formula_engine.rollout.shadow_error'
                    && ($context['shadow_engine'] ?? null) === 'legacy'
                    && ($context['formula_type'] ?? null) === 'length';
            });
    }
}
