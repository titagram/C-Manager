<?php

namespace App\Enums;

enum TipoRigaPreventivo: string
{
    case LOTTO = 'lotto';
    case SFUSO = 'sfuso';

    public function label(): string
    {
        return match ($this) {
            self::LOTTO => 'Lotto produzione',
            self::SFUSO => 'Materiale sfuso',
        };
    }
}
