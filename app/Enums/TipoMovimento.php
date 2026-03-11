<?php

namespace App\Enums;

enum TipoMovimento: string
{
    case CARICO = 'carico';
    case SCARICO = 'scarico';
    case RETTIFICA_POSITIVA = 'rettifica_positiva';
    case RETTIFICA_NEGATIVA = 'rettifica_negativa';

    public function label(): string
    {
        return match ($this) {
            self::CARICO => 'Carico',
            self::SCARICO => 'Scarico',
            self::RETTIFICA_POSITIVA => 'Rettifica Positiva',
            self::RETTIFICA_NEGATIVA => 'Rettifica Negativa',
        };
    }

    public function isPositive(): bool
    {
        return match ($this) {
            self::CARICO, self::RETTIFICA_POSITIVA => true,
            self::SCARICO, self::RETTIFICA_NEGATIVA => false,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CARICO => 'arrow-down-circle',
            self::SCARICO => 'arrow-up-circle',
            self::RETTIFICA_POSITIVA => 'plus-circle',
            self::RETTIFICA_NEGATIVA => 'minus-circle',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CARICO, self::RETTIFICA_POSITIVA => 'green',
            self::SCARICO, self::RETTIFICA_NEGATIVA => 'red',
        };
    }
}
