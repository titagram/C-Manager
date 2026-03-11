<?php

namespace Database\Factories;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Prodotto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prodotto>
 */
class ProdottoFactory extends Factory
{
    protected $model = Prodotto::class;

    public function definition(): array
    {
        return [
            'codice' => strtoupper($this->faker->unique()->lexify('???-???')),
            'nome' => $this->faker->words(3, true),
            'descrizione' => $this->faker->optional()->sentence(),
            'unita_misura' => $this->faker->randomElement(UnitaMisura::cases()),
            'categoria' => $this->faker->randomElement(Categoria::cases()),
            'soggetto_fitok' => $this->faker->boolean(30),
            'prezzo_unitario' => $this->faker->optional()->randomFloat(2, 5, 500),
            'coefficiente_scarto' => $this->faker->randomFloat(4, 0.01, 0.20),
            'is_active' => $this->faker->boolean(90),
            'usa_dimensioni' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function fitok(): static
    {
        return $this->state(fn(array $attributes) => [
            'soggetto_fitok' => true,
            'categoria' => Categoria::MATERIA_PRIMA,
        ]);
    }

    public function legname(): static
    {
        return $this->state(fn(array $attributes) => [
            'categoria' => Categoria::MATERIA_PRIMA,
            'unita_misura' => UnitaMisura::MC,
        ]);
    }
}
