<?php

namespace Database\Factories;

use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComponenteCostruzioneFactory extends Factory
{
    protected $model = ComponenteCostruzione::class;

    public function definition(): array
    {
        return [
            'costruzione_id' => Costruzione::factory(),
            'nome' => $this->faker->words(2, true),
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'W',
            'formula_quantita' => '1',
            'is_internal' => false,
            'allow_rotation' => false,
        ];
    }
}
