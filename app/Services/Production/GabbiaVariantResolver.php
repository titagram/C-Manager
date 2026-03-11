<?php

namespace App\Services\Production;

use App\Models\Costruzione;

class GabbiaVariantResolver
{
    public function __construct(
        private readonly GabbiaRoutineCatalog $routineCatalog
    ) {}

    /**
     * Resolve the intended Excel legacy routine for a gabbia costruzione.
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
        $notes = [];

        if (isset($config['gabbia_routine']) && is_string($config['gabbia_routine']) && $config['gabbia_routine'] !== '') {
            $routine = strtolower($config['gabbia_routine']);

            return [
                'routine' => $routine,
                'family' => $this->routineCatalog->familyFromRoutine($routine),
                'source' => 'config.gabbia_routine',
                'fallback_to_v1_rectangular' => true,
                'notes' => ['Routine imposta da config; preview fallback rettangolare, compatibility/strict con builder Excel se supportata.'],
            ];
        }

        if (str_contains($slug, 'gabbia')) {
            $isLegaccio = str_contains($slug, 'legaccio');
            $isFondo4 = str_contains($slug, 'fondo-4') || str_contains($slug, 'fondo4') || (bool) ($config['fondo4'] ?? false);
            $piantoni = (int) ($config['piantoni'] ?? 0);

            if ($isLegaccio) {
                $piantoniFromSlug = str_contains($slug, '6-piantoni') || str_contains($slug, '6piantoni');
                $effectivePiantoni = ($piantoni === 6 || $piantoniFromSlug) ? 6 : 4;
                $routine = $this->routineCatalog->resolveFromVariantFlags(
                    isLegaccio: true,
                    isFondo4: $isFondo4,
                    piantoni: $effectivePiantoni
                );

                $notes[] = 'Routine gabbia legaccio risolta da slug/config (v2 pianificato, v1 rettangolare attivo).';
                $notes[] = 'Per questa routine: preview usa fallback rettangolare, compatibility/strict usa builder Excel.';

                return [
                    'routine' => $routine,
                    'family' => 'gabbia_legaccio',
                    'source' => 'slug/config',
                    'fallback_to_v1_rectangular' => true,
                    'notes' => $notes,
                ];
            }

            $routine = $this->routineCatalog->resolveFromVariantFlags(
                isLegaccio: false,
                isFondo4: $isFondo4,
                piantoni: 0
            );
            $notes[] = 'Routine gabbia SP20 risolta da slug/config (preview fallback rettangolare, compatibility/strict Excel).';

            return [
                'routine' => $routine,
                'family' => 'gabbia_sp20',
                'source' => 'slug/config',
                'fallback_to_v1_rectangular' => true,
                'notes' => $notes,
            ];
        }

        return [
            'routine' => 'gabbia-generic-v1',
            'family' => 'gabbia_generic',
            'source' => 'fallback',
            'fallback_to_v1_rectangular' => true,
            'notes' => ['Slug non riconosciuto come variante gabbia Excel; uso optimizer rettangolare v1.'],
        ];
    }
}
