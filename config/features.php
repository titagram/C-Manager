<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Formula Engine Rollout
    |--------------------------------------------------------------------------
    |
    | Gradual rollout controls for the new formula engine used in lotti
    | calculations.
    |
    */
    'formula_engine' => [
        // Master switch: false forces legacy evaluator for all requests.
        'enabled' => env('FEATURE_FORMULA_ENGINE_ENABLED', true),

        // Percentage of traffic served by the new evaluator (0-100).
        'rollout_percentage' => (int) env('FEATURE_FORMULA_ENGINE_ROLLOUT_PERCENTAGE', 100),

        'monitoring' => [
            // Run shadow comparison against the inactive engine and log drifts/errors.
            'shadow_compare' => env('FEATURE_FORMULA_ENGINE_SHADOW_COMPARE', false),

            // Minimum absolute delta to report a numeric mismatch.
            'delta_threshold' => (float) env('FEATURE_FORMULA_ENGINE_DELTA_THRESHOLD', 0.01),
        ],
    ],
];
