<?php

namespace App\Enums;

enum UnitaMisura: string
{
    case PZ = 'pz';
    case MQ = 'mq';
    case MC = 'mc';
    case ML = 'ml';
    case KG = 'kg';

    public function label(): string
    {
        return match ($this) {
            self::PZ => 'Pezzi',
            self::MQ => 'Metri Quadri',
            self::MC => 'Metri Cubi',
            self::ML => 'Metri Lineari',
            self::KG => 'Chilogrammi',
        };
    }

    public function abbreviation(): string
    {
        return match ($this) {
            self::PZ => 'pz',
            self::MQ => 'm²',
            self::MC => 'm³',
            self::ML => 'm',
            self::KG => 'kg',
        };
    }
}
