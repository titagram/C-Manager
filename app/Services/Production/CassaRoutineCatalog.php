<?php

namespace App\Services\Production;

class CassaRoutineCatalog
{
    /**
     * @var array<string, array{
     *   family:string,
     *   uses_excel_builder:bool,
     *   label:string,
     *   required_profiles: array<int, array{
     *     key:string,
     *     label:string,
     *     thickness_mm:float,
     *     min_width_mm:float
     *   }>
     * }>
     */
    private const ROUTINES = [
        'geometrica' => [
            'family' => 'cassa_geometrica',
            'uses_excel_builder' => false,
            'label' => 'Geometrica',
            'required_profiles' => [
                [
                    'key' => 'base',
                    'label' => 'Materiale base',
                    'thickness_mm' => 0.0,
                    'min_width_mm' => 0.0,
                ],
            ],
        ],
        'cassasp25' => [
            'family' => 'cassa_sp25',
            'uses_excel_builder' => true,
            'label' => 'Excel SP25',
            'required_profiles' => [
                [
                    'key' => 'base',
                    'label' => 'Materiale base',
                    'thickness_mm' => 25.0,
                    'min_width_mm' => 100.0,
                ],
                [
                    'key' => 'fondo',
                    'label' => 'Materiale fondo',
                    'thickness_mm' => 40.0,
                    'min_width_mm' => 100.0,
                ],
            ],
        ],
        'cassasp25fondo40' => [
            'family' => 'cassa_sp25_fondo40',
            'uses_excel_builder' => true,
            'label' => 'Excel SP25 Fondo 40',
            'required_profiles' => [
                [
                    'key' => 'base',
                    'label' => 'Materiale base',
                    'thickness_mm' => 25.0,
                    'min_width_mm' => 100.0,
                ],
                [
                    'key' => 'fondo',
                    'label' => 'Materiale fondo',
                    'thickness_mm' => 40.0,
                    'min_width_mm' => 100.0,
                ],
            ],
        ],
    ];

    public function has(string $routine): bool
    {
        return array_key_exists(strtolower(trim($routine)), self::ROUTINES);
    }

    /**
     * @return array{
     *   family:string,
     *   uses_excel_builder:bool,
     *   label:string,
     *   required_profiles: array<int, array{
     *     key:string,
     *     label:string,
     *     thickness_mm:float,
     *     min_width_mm:float
     *   }>
     * }
     */
    public function profile(string $routine): array
    {
        $key = strtolower(trim($routine));

        if (! $this->has($key)) {
            throw new \InvalidArgumentException("Routine cassa non supportata dal catalogo: {$routine}");
        }

        return self::ROUTINES[$key];
    }

    public function label(string $routine): string
    {
        return (string) $this->profile($routine)['label'];
    }

    /**
     * @return array<int, array{key:string,label:string,thickness_mm:float,min_width_mm:float}>
     */
    public function requiredProfiles(string $routine): array
    {
        return array_values($this->profile($routine)['required_profiles']);
    }
}
