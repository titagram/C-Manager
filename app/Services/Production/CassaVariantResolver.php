<?php

namespace App\Services\Production;

use App\Models\Costruzione;

class CassaVariantResolver
{
    public function __construct(
        private readonly CassaRoutineCatalog $routineCatalog
    ) {}

    /**
     * @return array{
     *   routine:string,
     *   family:string,
     *   label:string,
     *   source:string,
     *   uses_excel_builder:bool,
     *   required_profiles: array<int, array{
     *     key:string,
     *     label:string,
     *     thickness_mm:float,
     *     min_width_mm:float
     *   }>,
     *   notes: array<int, string>
     * }
     */
    public function resolve(Costruzione $costruzione): array
    {
        $slug = strtolower((string) ($costruzione->slug ?? ''));
        $config = is_array($costruzione->config) ? $costruzione->config : [];
        $optimizerKey = strtolower(trim((string) ($config['optimizer_key'] ?? '')));
        $notes = [];

        if ($optimizerKey !== '') {
            $routine = $this->normalizeOptimizerKey($optimizerKey);
            $profile = $this->routineCatalog->profile($routine);

            return [
                'routine' => $routine,
                'family' => (string) $profile['family'],
                'label' => (string) $profile['label'],
                'source' => 'config.optimizer_key',
                'uses_excel_builder' => (bool) $profile['uses_excel_builder'],
                'required_profiles' => $this->routineCatalog->requiredProfiles($routine),
                'notes' => ['Routine cassa risolta da config.optimizer_key.'],
            ];
        }

        if (str_contains($slug, 'sp25-fondo40') || str_contains($slug, 'sp25fondo40')) {
            $routine = 'cassasp25fondo40';
            $profile = $this->routineCatalog->profile($routine);
            $notes[] = 'Routine cassa SP25 Fondo 40 rilevata da slug.';

            return [
                'routine' => $routine,
                'family' => (string) $profile['family'],
                'label' => (string) $profile['label'],
                'source' => 'slug',
                'uses_excel_builder' => (bool) $profile['uses_excel_builder'],
                'required_profiles' => $this->routineCatalog->requiredProfiles($routine),
                'notes' => $notes,
            ];
        }

        if (str_contains($slug, 'sp25')) {
            $routine = 'cassasp25';
            $profile = $this->routineCatalog->profile($routine);
            $notes[] = 'Routine cassa SP25 rilevata da slug.';

            return [
                'routine' => $routine,
                'family' => (string) $profile['family'],
                'label' => (string) $profile['label'],
                'source' => 'slug',
                'uses_excel_builder' => (bool) $profile['uses_excel_builder'],
                'required_profiles' => $this->routineCatalog->requiredProfiles($routine),
                'notes' => $notes,
            ];
        }

        $routine = 'geometrica';
        $profile = $this->routineCatalog->profile($routine);

        return [
            'routine' => $routine,
            'family' => (string) $profile['family'],
            'label' => (string) $profile['label'],
            'source' => 'fallback',
            'uses_excel_builder' => false,
            'required_profiles' => $this->routineCatalog->requiredProfiles($routine),
            'notes' => ['Costruzione cassa gestita con authoring geometrico componenti.'],
        ];
    }

    private function normalizeOptimizerKey(string $optimizerKey): string
    {
        return match ($optimizerKey) {
            'excel_sp25' => 'cassasp25',
            'excel_sp25_fondo40' => 'cassasp25fondo40',
            default => $this->routineCatalog->has($optimizerKey) ? $optimizerKey : 'geometrica',
        };
    }
}
