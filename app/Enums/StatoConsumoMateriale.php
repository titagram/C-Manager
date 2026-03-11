<?php

namespace App\Enums;

enum StatoConsumoMateriale: string
{
    case PIANIFICATO = 'pianificato';
    case OPZIONATO = 'opzionato';
    case CONSUMATO = 'consumato';
    case RILASCIATO = 'rilasciato';

    public function label(): string
    {
        return match ($this) {
            self::PIANIFICATO => 'Pianificato',
            self::OPZIONATO => 'Opzionato',
            self::CONSUMATO => 'Consumato',
            self::RILASCIATO => 'Rilasciato',
        };
    }
}
