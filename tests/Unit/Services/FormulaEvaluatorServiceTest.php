<?php

namespace Tests\Unit\Services;

use App\Services\FormulaEvaluatorService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FormulaEvaluatorServiceTest extends TestCase
{
    private FormulaEvaluatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FormulaEvaluatorService();
    }

    public function test_evaluates_basic_arithmetic_with_precedence(): void
    {
        $result = $this->service->evaluate('2 + 3 * 4');
        $this->assertSame(14.0, $result);
    }

    public function test_evaluates_parentheses_and_unary_operators(): void
    {
        $result = $this->service->evaluate('-(2 + 3) * 4');
        $this->assertSame(-20.0, $result);
    }

    public function test_evaluates_whitelisted_functions(): void
    {
        $result = $this->service->evaluate('max(ceil(4.1), floor(5.9), round(4.45, 1))');
        $this->assertSame(5.0, $result);
    }

    public function test_evaluates_formula_with_variables(): void
    {
        $result = $this->service->evaluate('L - (2 * T)', [
            'L' => 1000,
            'T' => 25,
        ]);

        $this->assertSame(950.0, $result);
    }

    public function test_throws_exception_for_unknown_function(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Funzione non consentita');

        $this->service->evaluate('sqrt(16)');
    }

    public function test_throws_exception_for_unknown_variable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Variabile sconosciuta');

        $this->service->evaluate('L + X', ['L' => 10]);
    }

    public function test_throws_exception_for_division_by_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Divisione per zero');

        $this->service->evaluate('10 / (5 - 5)');
    }

    public function test_throws_exception_for_non_allowed_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Carattere non consentito');

        $this->service->evaluate('2 + [3]');
    }
}
