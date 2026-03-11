<?php

namespace App\Enums;

enum TipoCostruzione: string
{
    case CASSA = 'cassa';
    case GABBIA = 'gabbia';
    case LEGACCIO = 'legaccio';
    case BANCALE = 'bancale';

    public function label(): string
    {
        return match ($this) {
            self::CASSA => 'Cassa',
            self::GABBIA => 'Gabbia',
            self::LEGACCIO => 'Legaccio',
            self::BANCALE => 'Bancale',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CASSA => 'box',
            self::GABBIA => 'grid-3x3',
            self::LEGACCIO => 'move-horizontal',
            self::BANCALE => 'layers',
        };
    }
}
