<?php

namespace App\Enums;

enum TipoDocumento: string
{
    case DDT_INGRESSO = 'ddt_ingresso';
    case DDT_USCITA = 'ddt_uscita';
    case FATTURA = 'fattura';
    case BOLLA_INTERNA = 'bolla_interna';
    case RETTIFICA = 'rettifica';

    public function label(): string
    {
        return match ($this) {
            self::DDT_INGRESSO => 'DDT Ingresso',
            self::DDT_USCITA => 'DDT Uscita',
            self::FATTURA => 'Fattura',
            self::BOLLA_INTERNA => 'Bolla Interna',
            self::RETTIFICA => 'Rettifica',
        };
    }

    public function isIngresso(): bool
    {
        return match ($this) {
            self::DDT_INGRESSO => true,
            default => false,
        };
    }

    public function isUscita(): bool
    {
        return match ($this) {
            self::DDT_USCITA, self::FATTURA => true,
            default => false,
        };
    }
}
