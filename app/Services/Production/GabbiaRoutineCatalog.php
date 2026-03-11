<?php

namespace App\Services\Production;

class GabbiaRoutineCatalog
{
    /**
     * @var array<string, array{
     *   family:string,
     *   legaccio:bool,
     *   fondo4:bool,
     *   piantoni:int|null,
     *   row8_section_mm:float,
     *   legacy_threshold_219:bool
     * }>
     */
    private const ROUTINES = [
        'gabbiasp20' => [
            'family' => 'gabbia_sp20',
            'legaccio' => false,
            'fondo4' => false,
            'piantoni' => null,
            'row8_section_mm' => 25.0,
            'legacy_threshold_219' => false,
        ],
        'gabbiasp20fondo4' => [
            'family' => 'gabbia_sp20',
            'legaccio' => false,
            'fondo4' => true,
            'piantoni' => null,
            'row8_section_mm' => 40.0,
            'legacy_threshold_219' => false,
        ],
        'gabbialegaccio4piantoni' => [
            'family' => 'gabbia_legaccio',
            'legaccio' => true,
            'fondo4' => false,
            'piantoni' => 4,
            'row8_section_mm' => 25.0,
            'legacy_threshold_219' => false,
        ],
        'gabbialegaccio4piantonifondo4' => [
            'family' => 'gabbia_legaccio',
            'legaccio' => true,
            'fondo4' => true,
            'piantoni' => 4,
            'row8_section_mm' => 40.0,
            'legacy_threshold_219' => false,
        ],
        'gabbialegaccio6piantoni' => [
            'family' => 'gabbia_legaccio',
            'legaccio' => true,
            'fondo4' => false,
            'piantoni' => 6,
            'row8_section_mm' => 25.0,
            'legacy_threshold_219' => false,
        ],
        'gabbialegaccio6piantonifondo4' => [
            'family' => 'gabbia_legaccio',
            'legaccio' => true,
            'fondo4' => true,
            'piantoni' => 6,
            'row8_section_mm' => 40.0,
            'legacy_threshold_219' => true,
        ],
    ];

    /**
     * @return array<int, string>
     */
    public function allRoutines(): array
    {
        return array_keys(self::ROUTINES);
    }

    public function has(string $routine): bool
    {
        return array_key_exists(strtolower(trim($routine)), self::ROUTINES);
    }

    /**
     * @return array{
     *   family:string,
     *   legaccio:bool,
     *   fondo4:bool,
     *   piantoni:int|null,
     *   row8_section_mm:float,
     *   legacy_threshold_219:bool
     * }
     */
    public function profile(string $routine): array
    {
        $key = strtolower(trim($routine));

        if (!$this->has($key)) {
            throw new \InvalidArgumentException("Routine gabbia non supportata dal catalogo: {$routine}");
        }

        return self::ROUTINES[$key];
    }

    public function familyFromRoutine(string $routine): string
    {
        $key = strtolower(trim($routine));
        if ($this->has($key)) {
            return (string) self::ROUTINES[$key]['family'];
        }

        return match (true) {
            str_starts_with($key, 'gabbiasp20') => 'gabbia_sp20',
            str_starts_with($key, 'gabbialegaccio') => 'gabbia_legaccio',
            default => 'gabbia_generic',
        };
    }

    public function resolveFromVariantFlags(bool $isLegaccio, bool $isFondo4, int $piantoni): string
    {
        if (!$isLegaccio) {
            return $isFondo4 ? 'gabbiasp20fondo4' : 'gabbiasp20';
        }

        if ($piantoni === 6) {
            return $isFondo4 ? 'gabbialegaccio6piantonifondo4' : 'gabbialegaccio6piantoni';
        }

        return $isFondo4 ? 'gabbialegaccio4piantonifondo4' : 'gabbialegaccio4piantoni';
    }
}

