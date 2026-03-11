<?php

namespace App\Services\Calcolo\DTO;

class RigaInput
{
    public function __construct(
        public readonly ?int $prodotto_id,
        public readonly string $descrizione,
        public readonly float $lunghezza_mm,
        public readonly float $larghezza_mm,
        public readonly float $spessore_mm,
        public readonly int $quantita,
        public readonly float $coefficienteScarto,
        public readonly float $prezzoUnitario,
        public readonly string $unitaMisura = 'mc',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            prodotto_id: $data['prodotto_id'] ?? null,
            descrizione: $data['descrizione'],
            lunghezza_mm: (float) $data['lunghezza_mm'],
            larghezza_mm: (float) $data['larghezza_mm'],
            spessore_mm: (float) $data['spessore_mm'],
            quantita: (int) $data['quantita'],
            coefficienteScarto: (float) ($data['coefficiente_scarto'] ?? 0.10),
            prezzoUnitario: (float) ($data['prezzo_unitario'] ?? 0),
            unitaMisura: strtolower((string) ($data['unita_misura'] ?? 'mc')),
        );
    }

    public function toArray(): array
    {
        return [
            'prodotto_id' => $this->prodotto_id,
            'descrizione' => $this->descrizione,
            'lunghezza_mm' => $this->lunghezza_mm,
            'larghezza_mm' => $this->larghezza_mm,
            'spessore_mm' => $this->spessore_mm,
            'quantita' => $this->quantita,
            'coefficiente_scarto' => $this->coefficienteScarto,
            'prezzo_unitario' => $this->prezzoUnitario,
            'unita_misura' => $this->unitaMisura,
        ];
    }
}
