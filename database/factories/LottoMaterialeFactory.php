<?php

namespace Database\Factories;

use App\Models\LottoMateriale;
use App\Models\Prodotto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LottoMateriale>
 */
class LottoMaterialeFactory extends Factory
{
    protected $model = LottoMateriale::class;

    public function definition(): array
    {
        return [
            'codice_lotto' => strtoupper($this->faker->unique()->lexify('L??-####')),
            'prodotto_id' => Prodotto::factory(),
            'data_arrivo' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'fornitore' => $this->faker->optional(0.8)->company(),
            'numero_ddt' => $this->faker->optional(0.7)->numerify('DDT-####/##'),
            'quantita_iniziale' => $this->faker->randomFloat(2, 10, 500),
            'fitok_certificato' => $this->faker->optional(0.3)->regexify('FITOK-IT-[0-9]{4}-[0-9]{4}'),
            'fitok_data_trattamento' => $this->faker->optional(0.3)->dateTimeBetween('-1 year', 'now'),
            'fitok_tipo_trattamento' => $this->faker->optional(0.3)->randomElement(['HT', 'MB', 'KD', 'DH']),
            'fitok_paese_origine' => $this->faker->optional(0.3)->randomElement(['Italia', 'Austria', 'Germania', 'Slovenia']),
            'lunghezza_mm' => $this->faker->optional(0.6)->randomFloat(0, 1000, 6000),
            'larghezza_mm' => $this->faker->optional(0.6)->randomFloat(0, 100, 1000),
            'spessore_mm' => $this->faker->optional(0.6)->randomFloat(0, 10, 100),
            'note' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    public function withFitok(): static
    {
        return $this->state(fn(array $attributes) => [
            'fitok_certificato' => $this->faker->regexify('FITOK-IT-[0-9]{4}-[0-9]{4}'),
            'fitok_data_trattamento' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'fitok_tipo_trattamento' => $this->faker->randomElement(['HT', 'MB', 'KD', 'DH']),
            'fitok_paese_origine' => $this->faker->randomElement(['Italia', 'Austria', 'Germania']),
        ]);
    }
}
