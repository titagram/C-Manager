<?php

namespace App\Enums;

enum StatoLottoProduzione: string
{
    case BOZZA = 'bozza';
    case CONFERMATO = 'confermato';
    case IN_LAVORAZIONE = 'in_lavorazione';
    case COMPLETATO = 'completato';
    case ANNULLATO = 'annullato';

    public function label(): string
    {
        return match ($this) {
            self::BOZZA => 'Bozza',
            self::CONFERMATO => 'Confermato',
            self::IN_LAVORAZIONE => 'In Lavorazione',
            self::COMPLETATO => 'Completato',
            self::ANNULLATO => 'Annullato',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BOZZA => 'gray',
            self::CONFERMATO => 'cyan',
            self::IN_LAVORAZIONE => 'blue',
            self::COMPLETATO => 'green',
            self::ANNULLATO => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BOZZA => 'file-text',
            self::CONFERMATO => 'check',
            self::IN_LAVORAZIONE => 'loader',
            self::COMPLETATO => 'check-circle',
            self::ANNULLATO => 'x-circle',
        };
    }
}
