<?php

namespace Database\Factories;

use App\Models\Costruzione;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CostruzioneFactory extends Factory
{
    protected $model = Costruzione::class;

    public function definition(): array
    {
        $nome = $this->faker->unique()->words(2, true);

        return [
            'categoria' => $this->faker->randomElement(['cassa', 'gabbia', 'legaccio', 'bancale']),
            'nome' => $nome,
            'slug' => Str::slug($nome),
            'descrizione' => $this->faker->optional()->sentence(),
            'config' => [],
            'richiede_lunghezza' => true,
            'richiede_larghezza' => true,
            'richiede_altezza' => true,
            'is_active' => true,
        ];
    }
}
