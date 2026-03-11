<?php

namespace App\Services\Calcolo\DTO;

class RigaOutput
{
    public function __construct(
        public readonly float $superficie_mq,
        public readonly float $volume_mc,
        public readonly float $materiale_netto,
        public readonly float $materiale_lordo,
        public readonly float $totale,
    ) {}

    public function toArray(): array
    {
        return [
            'superficie_mq' => $this->superficie_mq,
            'volume_mc' => $this->volume_mc,
            'materiale_netto' => $this->materiale_netto,
            'materiale_lordo' => $this->materiale_lordo,
            'totale' => $this->totale,
        ];
    }
}
