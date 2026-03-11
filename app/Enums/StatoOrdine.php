<?php

namespace App\Enums;

enum StatoOrdine: string
{
    case CONFERMATO = 'confermato';
    case IN_PRODUZIONE = 'in_produzione';
    case PRONTO = 'pronto';
    case CONSEGNATO = 'consegnato';
    case FATTURATO = 'fatturato';
    case ANNULLATO = 'annullato';

    public function label(): string
    {
        return match ($this) {
            self::CONFERMATO => 'Confermato',
            self::IN_PRODUZIONE => 'In Produzione',
            self::PRONTO => 'Pronto',
            self::CONSEGNATO => 'Consegnato',
            self::FATTURATO => 'Fatturato',
            self::ANNULLATO => 'Annullato',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CONFERMATO => 'blue',
            self::IN_PRODUZIONE => 'yellow',
            self::PRONTO => 'green',
            self::CONSEGNATO => 'teal',
            self::FATTURATO => 'gray',
            self::ANNULLATO => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CONFERMATO => 'check',
            self::IN_PRODUZIONE => 'cog',
            self::PRONTO => 'package',
            self::CONSEGNATO => 'truck',
            self::FATTURATO => 'file-text',
            self::ANNULLATO => 'x-circle',
        };
    }

    public function canTransitionTo(StatoOrdine $newState): bool
    {
        $transitions = [
            self::CONFERMATO->value => [self::IN_PRODUZIONE, self::ANNULLATO],
            self::IN_PRODUZIONE->value => [self::PRONTO, self::ANNULLATO],
            self::PRONTO->value => [self::CONSEGNATO, self::ANNULLATO],
            self::CONSEGNATO->value => [self::FATTURATO],
            self::FATTURATO->value => [],
            self::ANNULLATO->value => [],
        ];

        return in_array($newState, $transitions[$this->value] ?? []);
    }
}
