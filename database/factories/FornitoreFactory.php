<?php

namespace Database\Factories;

use App\Models\Fornitore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fornitore>
 */
class FornitoreFactory extends Factory
{
    protected $model = Fornitore::class;

    public function definition(): array
    {
        return [
            'codice' => strtoupper($this->faker->lexify('FOR???')),
            'ragione_sociale' => $this->faker->company(),
            'partita_iva' => $this->faker->optional(0.8)->numerify('###########'),
            'codice_fiscale' => $this->faker->optional(0.5)->regexify('[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]'),
            'indirizzo' => $this->faker->streetAddress(),
            'cap' => $this->faker->postcode(),
            'citta' => $this->faker->city(),
            'provincia' => strtoupper($this->faker->lexify('??')),
            'nazione' => $this->faker->randomElement(['IT', 'AT', 'DE', 'SI', 'CH', 'FR']),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional(0.9)->companyEmail(),
            'note' => $this->faker->optional(0.3)->sentence(),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }
}
