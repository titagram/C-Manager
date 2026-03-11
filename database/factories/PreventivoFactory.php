<?php

namespace Database\Factories;

use App\Enums\StatoPreventivo;
use App\Models\Cliente;
use App\Models\Preventivo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Preventivo>
 */
class PreventivoFactory extends Factory
{
    protected $model = Preventivo::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'data' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'validita_fino' => $this->faker->dateTimeBetween('now', '+30 days'),
            'stato' => StatoPreventivo::BOZZA,
            'descrizione' => $this->faker->optional()->sentence(),
            'engine_version' => '1.0.0',
            'totale_materiali' => $this->faker->randomFloat(2, 100, 10000),
            'totale_lavorazioni' => 0,
            'totale' => function (array $attributes) {
                return $attributes['totale_materiali'] + $attributes['totale_lavorazioni'];
            },
            'created_by' => User::factory(),
        ];
    }

    public function bozza(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoPreventivo::BOZZA,
        ]);
    }

    public function inviato(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoPreventivo::INVIATO,
        ]);
    }

    public function accettato(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoPreventivo::ACCETTATO,
        ]);
    }

    public function rifiutato(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoPreventivo::RIFIUTATO,
        ]);
    }

    public function scaduto(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoPreventivo::SCADUTO,
            'validita_fino' => now()->subDays(10),
        ]);
    }
}
