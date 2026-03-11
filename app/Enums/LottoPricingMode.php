<?php

namespace App\Enums;

enum LottoPricingMode: string
{
    case TARIFFA_MC = 'tariffa_mc';
    case COSTO_RICARICO = 'costo_ricarico';

    public function label(): string
    {
        return match ($this) {
            self::TARIFFA_MC => 'Tariffa €/m³',
            self::COSTO_RICARICO => 'Costo + Ricarico',
        };
    }
}
