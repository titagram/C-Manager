<?php

namespace Database\Factories;

use App\Enums\StatoLottoProduzione;
use App\Models\Cliente;
use App\Models\LottoProduzione;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LottoProduzione>
 */
class LottoProduzioneFactory extends Factory
{
    protected $model = LottoProduzione::class;

    public function definition(): array
    {
        $stato = $this->faker->randomElement(StatoLottoProduzione::cases());
        $dataInizio = $stato !== StatoLottoProduzione::BOZZA
            ? $this->faker->dateTimeBetween('-3 months', 'now')
            : null;
        $dataFine = $stato === StatoLottoProduzione::COMPLETATO && $dataInizio
            ? $this->faker->dateTimeBetween($dataInizio, 'now')
            : null;

        return [
            'codice_lotto' => null, // Auto-generated
            'cliente_id' => $this->faker->optional(0.7)->passthrough(Cliente::factory()),
            'preventivo_id' => null,
            'prodotto_finale' => $this->faker->words(rand(3, 6), true),
            'descrizione' => $this->faker->optional(0.6)->sentence(),
            'stato' => $stato,
            'data_inizio' => $dataInizio,
            'data_fine' => $dataFine,
            'created_by' => User::factory(),
        ];
    }

    public function bozza(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoLottoProduzione::BOZZA,
            'data_inizio' => null,
            'data_fine' => null,
        ]);
    }

    public function inLavorazione(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'data_inizio' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'data_fine' => null,
        ]);
    }

    public function completato(): static
    {
        return $this->state(fn(array $attributes) => [
            'stato' => StatoLottoProduzione::COMPLETATO,
            'data_inizio' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
            'data_fine' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
