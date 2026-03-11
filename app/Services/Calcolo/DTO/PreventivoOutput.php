<?php

namespace App\Services\Calcolo\DTO;

class PreventivoOutput
{
    /**
     * @param RigaOutput[] $righe
     */
    public function __construct(
        public readonly array $righe,
        public readonly float $totaleMateriali,
        public readonly float $totaleLavorazioni,
        public readonly float $totale,
        public readonly string $engineVersion,
    ) {}

    public function toArray(): array
    {
        return [
            'righe' => array_map(fn($r) => $r->toArray(), $this->righe),
            'totale_materiali' => $this->totaleMateriali,
            'totale_lavorazioni' => $this->totaleLavorazioni,
            'totale' => $this->totale,
            'engine_version' => $this->engineVersion,
        ];
    }
}
