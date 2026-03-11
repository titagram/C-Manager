<?php

namespace App\Services;

use InvalidArgumentException;

class FormulaEvaluatorService
{
    /**
     * @var array<int, array{type: string, value: string}>
     */
    private array $tokens = [];

    private int $position = 0;

    /**
     * @var array<string, float>
     */
    private array $variables = [];

    /**
     * Evaluate a mathematical formula in a safe way (no eval).
     *
     * @param array<string, int|float> $variables
     */
    public function evaluate(string $formula, array $variables = []): float
    {
        $formula = trim($formula);

        if ($formula === '') {
            return 0.0;
        }

        $this->variables = [];
        foreach ($variables as $name => $value) {
            $this->variables[strtoupper($name)] = (float) $value;
        }

        $this->tokens = $this->tokenize($formula);
        $this->position = 0;

        $result = $this->parseExpression();

        if (!$this->isAtEnd()) {
            throw new InvalidArgumentException(
                sprintf('Token inatteso: "%s"', $this->peek()['value'])
            );
        }

        if (!is_finite($result)) {
            throw new InvalidArgumentException('Risultato non valido.');
        }

        return $result;
    }

    /**
     * @return array<int, array{type: string, value: string}>
     */
    private function tokenize(string $formula): array
    {
        $length = strlen($formula);
        $tokens = [];
        $offset = 0;

        while ($offset < $length) {
            $char = $formula[$offset];

            if (ctype_space($char)) {
                $offset++;
                continue;
            }

            if (preg_match('/\G(?:\d+(?:\.\d+)?|\.\d+)/A', $formula, $match, 0, $offset) === 1) {
                $tokens[] = ['type' => 'number', 'value' => $match[0]];
                $offset += strlen($match[0]);
                continue;
            }

            if (preg_match('/\G[A-Za-z_][A-Za-z0-9_]*/A', $formula, $match, 0, $offset) === 1) {
                $tokens[] = ['type' => 'identifier', 'value' => $match[0]];
                $offset += strlen($match[0]);
                continue;
            }

            if (in_array($char, ['+', '-', '*', '/', '(', ')', ','], true)) {
                $tokens[] = ['type' => 'symbol', 'value' => $char];
                $offset++;
                continue;
            }

            throw new InvalidArgumentException(
                sprintf('Carattere non consentito nella formula: "%s"', $char)
            );
        }

        return $tokens;
    }

    private function parseExpression(): float
    {
        $value = $this->parseTerm();

        while ($this->matchSymbol('+') || $this->matchSymbol('-')) {
            $operator = $this->previous()['value'];
            $right = $this->parseTerm();
            $value = $operator === '+' ? $value + $right : $value - $right;
        }

        return $value;
    }

    private function parseTerm(): float
    {
        $value = $this->parseUnary();

        while ($this->matchSymbol('*') || $this->matchSymbol('/')) {
            $operator = $this->previous()['value'];
            $right = $this->parseUnary();

            if ($operator === '/') {
                if (abs($right) < 1e-12) {
                    throw new InvalidArgumentException('Divisione per zero non consentita.');
                }
                $value /= $right;
            } else {
                $value *= $right;
            }
        }

        return $value;
    }

    private function parseUnary(): float
    {
        if ($this->matchSymbol('+')) {
            return $this->parseUnary();
        }

        if ($this->matchSymbol('-')) {
            return -$this->parseUnary();
        }

        return $this->parsePrimary();
    }

    private function parsePrimary(): float
    {
        if ($this->matchType('number')) {
            return (float) $this->previous()['value'];
        }

        if ($this->matchType('identifier')) {
            $identifierToken = $this->previous();
            $identifier = $identifierToken['value'];

            if ($this->matchSymbol('(')) {
                $args = [];
                if (!$this->checkSymbol(')')) {
                    do {
                        $args[] = $this->parseExpression();
                    } while ($this->matchSymbol(','));
                }

                $this->consumeSymbol(')');
                return $this->applyFunction($identifier, $args);
            }

            $variableName = strtoupper($identifier);
            if (!array_key_exists($variableName, $this->variables)) {
                throw new InvalidArgumentException(
                    sprintf('Variabile sconosciuta: "%s"', $identifier)
                );
            }

            return $this->variables[$variableName];
        }

        if ($this->matchSymbol('(')) {
            $value = $this->parseExpression();
            $this->consumeSymbol(')');
            return $value;
        }

        if ($this->isAtEnd()) {
            throw new InvalidArgumentException('Formula incompleta.');
        }

        throw new InvalidArgumentException(
            sprintf('Token non valido: "%s"', $this->peek()['value'])
        );
    }

    /**
     * @param array<int, float> $args
     */
    private function applyFunction(string $functionName, array $args): float
    {
        $function = strtolower($functionName);

        return match ($function) {
            'ceil' => $this->applyUnaryFunction($functionName, $args, fn(float $v) => ceil($v)),
            'floor' => $this->applyUnaryFunction($functionName, $args, fn(float $v) => floor($v)),
            'abs' => $this->applyUnaryFunction($functionName, $args, fn(float $v) => abs($v)),
            'round' => $this->applyRound($functionName, $args),
            'min' => $this->applyMinMax($functionName, $args, true),
            'max' => $this->applyMinMax($functionName, $args, false),
            default => throw new InvalidArgumentException(
                sprintf('Funzione non consentita: "%s"', $functionName)
            ),
        };
    }

    /**
     * @param array<int, float> $args
     */
    private function applyUnaryFunction(string $functionName, array $args, callable $callable): float
    {
        if (count($args) !== 1) {
            throw new InvalidArgumentException(
                sprintf('La funzione "%s" richiede esattamente 1 argomento.', $functionName)
            );
        }

        return (float) $callable($args[0]);
    }

    /**
     * @param array<int, float> $args
     */
    private function applyRound(string $functionName, array $args): float
    {
        if (count($args) < 1 || count($args) > 2) {
            throw new InvalidArgumentException(
                sprintf('La funzione "%s" richiede 1 o 2 argomenti.', $functionName)
            );
        }

        $precision = count($args) === 2 ? (int) $args[1] : 0;
        return round($args[0], $precision);
    }

    /**
     * @param array<int, float> $args
     */
    private function applyMinMax(string $functionName, array $args, bool $isMin): float
    {
        if (count($args) < 1) {
            throw new InvalidArgumentException(
                sprintf('La funzione "%s" richiede almeno 1 argomento.', $functionName)
            );
        }

        return $isMin ? min($args) : max($args);
    }

    private function matchType(string $type): bool
    {
        if ($this->checkType($type)) {
            $this->position++;
            return true;
        }

        return false;
    }

    private function matchSymbol(string $symbol): bool
    {
        if ($this->checkSymbol($symbol)) {
            $this->position++;
            return true;
        }

        return false;
    }

    private function checkType(string $type): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->peek()['type'] === $type;
    }

    private function checkSymbol(string $symbol): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        $token = $this->peek();
        return $token['type'] === 'symbol' && $token['value'] === $symbol;
    }

    private function consumeSymbol(string $symbol): void
    {
        if ($this->matchSymbol($symbol)) {
            return;
        }

        if ($this->isAtEnd()) {
            throw new InvalidArgumentException(
                sprintf('Atteso simbolo "%s", trovata fine formula.', $symbol)
            );
        }

        throw new InvalidArgumentException(
            sprintf('Atteso simbolo "%s", trovato "%s".', $symbol, $this->peek()['value'])
        );
    }

    /**
     * @return array{type: string, value: string}
     */
    private function peek(): array
    {
        return $this->tokens[$this->position];
    }

    /**
     * @return array{type: string, value: string}
     */
    private function previous(): array
    {
        return $this->tokens[$this->position - 1];
    }

    private function isAtEnd(): bool
    {
        return $this->position >= count($this->tokens);
    }
}
