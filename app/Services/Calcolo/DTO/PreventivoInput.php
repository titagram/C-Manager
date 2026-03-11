<?php

namespace App\Services\Calcolo\DTO;

class PreventivoInput
{
    /**
     * @param RigaInput[] $righe
     */
    public function __construct(
        public readonly int $cliente_id,
        public readonly ?string $descrizione,
        public readonly array $righe,
    ) {}

    public static function fromArray(array $data): self
    {
        $righe = array_map(
            fn($r) => $r instanceof RigaInput ? $r : RigaInput::fromArray($r),
            $data['righe'] ?? []
        );

        return new self(
            cliente_id: (int) $data['cliente_id'],
            descrizione: $data['descrizione'] ?? null,
            righe: $righe,
        );
    }

    public function toArray(): array
    {
        return [
            'cliente_id' => $this->cliente_id,
            'descrizione' => $this->descrizione,
            'righe' => array_map(fn($r) => $r->toArray(), $this->righe),
        ];
    }
}
