<?php

namespace App\Services\Production;

use App\Models\Costruzione;

class LegaccioVariantResolver
{
    /**
     * Routines Excel-specific currently supported for categoria=legaccio.
     *
     * @var array<int, string>
     */
    private const SUPPORTED_ROUTINES = [
        'legacci224x60',
    ];

    /**
     * Resolve intended Excel legacy routine for legaccio category.
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

        if (isset($config['legaccio_routine']) && is_string($config['legaccio_routine']) && $config['legaccio_routine'] !== '') {
            $routine = strtolower(trim($config['legaccio_routine']));

            if (!$this->isSupportedRoutine($routine)) {
                return [
                    'routine' => 'legaccio-generic-v1',
                    'family' => 'legaccio_generic',
                    'source' => 'config.legaccio_routine.unsupported',
                    'fallback_to_v1_rectangular' => true,
                    'notes' => [sprintf(
                        'Routine config "%s" non supportata per categoria legaccio: uso fallback rettangolare v1.',
                        $routine
                    )],
                ];
            }

            return [
                'routine' => $routine,
                'family' => $this->familyFromRoutine($routine),
                'source' => 'config.legaccio_routine',
                'fallback_to_v1_rectangular' => true,
                'notes' => ['Routine imposta da config.'],
            ];
        }

        if (str_contains($slug, '224x60') || str_contains($slug, 'legacci-224')) {
            return [
                'routine' => 'legacci224x60',
                'family' => 'legaccio_224x60',
                'source' => 'slug',
                'fallback_to_v1_rectangular' => true,
                'notes' => ['Routine legacci224x60 rilevata da slug.'],
            ];
        }

        return [
            'routine' => 'legaccio-generic-v1',
            'family' => 'legaccio_generic',
            'source' => 'fallback',
            'fallback_to_v1_rectangular' => true,
            'notes' => ['Nessuna routine Excel specifica rilevata; uso fallback rettangolare v1.'],
        ];
    }

    private function familyFromRoutine(string $routine): string
    {
        return match ($routine) {
            'legacci224x60' => 'legaccio_224x60',
            default => 'legaccio_generic',
        };
    }

    private function isSupportedRoutine(string $routine): bool
    {
        return in_array($routine, self::SUPPORTED_ROUTINES, true);
    }
}
