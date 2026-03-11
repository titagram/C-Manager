<?php

namespace App\Services;

use InvalidArgumentException;

class LegacyFormulaEvaluatorService
{
    public function __construct(
        private FormulaEvaluatorService $formulaEvaluatorService
    ) {}

    /**
     * @param array<string, float|int> $variables
     */
    public function evaluate(string $formula, array $variables = []): float
    {
        $formula = trim($formula);

        if ($formula === '') {
            return 0.0;
        }

        // Legacy engine never supported function calls (e.g. ceil, floor, round).
        if (preg_match('/[A-Za-z_][A-Za-z0-9_]*\s*\(/', $formula) === 1) {
            throw new InvalidArgumentException('Funzioni non supportate dal motore legacy.');
        }

        return $this->formulaEvaluatorService->evaluate($formula, $variables);
    }
}
