<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case OPERATORE = 'operatore';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Amministratore',
            self::OPERATORE => 'Operatore',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => ['*'],
            self::OPERATORE => [
                'magazzino.view',
                'lotti.view',
                'lotti.complete',
                'bom.view',
            ],
        };
    }
}
