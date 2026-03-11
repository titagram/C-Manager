<?php

namespace App\Enums;

enum StatoPreventivo: string
{
    case BOZZA = 'bozza';
    case INVIATO = 'inviato';
    case ACCETTATO = 'accettato';
    case RIFIUTATO = 'rifiutato';
    case SCADUTO = 'scaduto';

    public function label(): string
    {
        return match ($this) {
            self::BOZZA => 'Bozza',
            self::INVIATO => 'Inviato',
            self::ACCETTATO => 'Accettato',
            self::RIFIUTATO => 'Rifiutato',
            self::SCADUTO => 'Scaduto',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BOZZA => 'gray',
            self::INVIATO => 'blue',
            self::ACCETTATO => 'green',
            self::RIFIUTATO => 'red',
            self::SCADUTO => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BOZZA => 'file-text',
            self::INVIATO => 'send',
            self::ACCETTATO => 'check-circle',
            self::RIFIUTATO => 'x-circle',
            self::SCADUTO => 'clock',
        };
    }

    public function canTransitionTo(StatoPreventivo $newState): bool
    {
        $transitions = [
            self::BOZZA->value     => [self::INVIATO],
            self::INVIATO->value   => [self::ACCETTATO, self::RIFIUTATO, self::SCADUTO],
            self::ACCETTATO->value => [],
            self::RIFIUTATO->value => [self::BOZZA],
            self::SCADUTO->value   => [self::BOZZA],
        ];

        return in_array($newState, $transitions[$this->value] ?? []);
    }
}