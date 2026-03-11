<?php

namespace App\Services\Production;

use App\Models\Costruzione;

class BancaleVariantResolver
{
    /**
     * Resolve intended Excel legacy routine for bancale/perimetrale family.
     *
     * @return array{
     *   routine:string,
     *   family:string,
     *   source:string,
     *   fallback_to_v1_rectangular:bool,
     *   notes: array<int, string>
     * }
     */
    public function resolve(Costruzione $costruzione): array
    {
        $slug = strtolower((string) ($costruzione->slug ?? ''));
        $config = is_array($costruzione->config) ? $costruzione->config : [];

        if (isset($config['bancale_routine']) && is_string($config['bancale_routine']) && $config['bancale_routine'] !== '') {
            $routine = strtolower($config['bancale_routine']);

            return [
                'routine' => $routine,
                'family' => $this->familyFromRoutine($routine),
                'source' => 'config.bancale_routine',
                'fallback_to_v1_rectangular' => true,
                'notes' => ['Routine imposta da config; optimizer Excel-specific non ancora attivo in strict mode.'],
            ];
        }

        if (str_contains($slug, 'perimetrale')) {
            return [
                'routine' => 'perimetrale',
                'family' => 'bancale_perimetrale',
                'source' => 'slug',
                'fallback_to_v1_rectangular' => true,
                'notes' => ['Routine perimetrale rilevata da slug; builder dedicato non ancora implementato.'],
            ];
        }

        if ($slug !== '' && str_contains($slug, 'bancale')) {
            return [
                'routine' => 'bancale',
                'family' => 'bancale_standard',
                'source' => 'slug',
                'fallback_to_v1_rectangular' => true,
                'notes' => ['Routine bancale standard rilevata da slug/config.'],
            ];
        }

        return [
            'routine' => 'bancale',
            'family' => 'bancale_standard',
            'source' => 'fallback',
            'fallback_to_v1_rectangular' => true,
            'notes' => ['Slug non riconosciuto; uso routine bancale standard con fallback rettangolare v1.'],
        ];
    }

    private function familyFromRoutine(string $routine): string
    {
        return match (true) {
            $routine === 'perimetrale' => 'bancale_perimetrale',
            default => 'bancale_standard',
        };
    }
}

