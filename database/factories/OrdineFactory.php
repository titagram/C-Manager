<?php

namespace Database\Factories;

use App\Enums\StatoOrdine;
use App\Models\Cliente;
use App\Models\Ordine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ordine>
 */
class OrdineFactory extends Factory
{
    protected $model = Ordine::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'data_ordine' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'data_consegna_prevista' => $this->faker->dateTimeBetween('now', '+30 days'),
            'stato' => StatoOrdine::CONFERMATO,
            'descrizione' => $this->faker->optional()->sentence(),
            'totale' => $this->faker->randomFloat(2, 100, 10000),
            'created_by' => User::factory(),
        ];
    }

    public function confermato(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoOrdine::CONFERMATO,
        ]);
    }

    public function inProduzione(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoOrdine::IN_PRODUZIONE,
        ]);
    }

    public function pronto(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoOrdine::PRONTO,
        ]);
    }

    public function consegnato(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoOrdine::CONSEGNATO,
            'data_consegna_effettiva' => now(),
        ]);
    }

    public function fatturato(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoOrdine::FATTURATO,
            'data_consegna_effettiva' => now()->subDays(7),
        ]);
    }

    public function annullato(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoOrdine::ANNULLATO,
        ]);
    }
}
