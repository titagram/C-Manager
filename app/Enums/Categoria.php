<?php

namespace App\Enums;

enum Categoria: string
{
    // Materie prime e semilavorati
    case MATERIA_PRIMA = 'materia_prima';
    case ASSE = 'asse';
    case LISTELLO = 'listello';

    // Accessori
    case FERRAMENTA = 'ferramenta';
    case ALTRO = 'altro';

    public function label(): string
    {
        return match ($this) {
            self::MATERIA_PRIMA => 'Materia Prima',
            self::ASSE => 'Asse',
            self::LISTELLO => 'Listello',
            self::FERRAMENTA => 'Ferramenta',
            self::ALTRO => 'Altro',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MATERIA_PRIMA => 'tree-pine',
            self::ASSE => 'minus',
            self::LISTELLO => 'grip-horizontal',
            self::FERRAMENTA => 'wrench',
            self::ALTRO => 'circle-help',
        };
    }

    /**
     * Indica se questa categoria è materia prima/semilavorato
     */
    public function isMateriaPrima(): bool
    {
        return in_array($this, [
            self::MATERIA_PRIMA,
            self::ASSE,
            self::LISTELLO,
        ]);
    }

    /**
     * Get all material categories
     */
    public static function materiali(): array
    {
        return [
            self::MATERIA_PRIMA,
            self::ASSE,
            self::LISTELLO,
            self::FERRAMENTA,
            self::ALTRO,
        ];
    }

    /**
     * Check if this is a material category
     */
    public function isMateriale(): bool
    {
        return in_array($this, self::materiali());
    }
}
