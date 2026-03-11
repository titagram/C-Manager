<?php

namespace Database\Factories;

use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\Scarto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScartoFactory extends Factory
{
    public function definition(): array
    {
        $lunghezzaMm = fake()->numberBetween(100, 1000);
        $larghezzaMm = fake()->numberBetween(50, 200);
        $spessoreMm = fake()->numberBetween(5, 30);

        return [
            'lotto_produzione_id' => LottoProduzione::factory(),
            'lotto_materiale_id' => LottoMateriale::factory(),
            'lunghezza_mm' => $lunghezzaMm,
            'larghezza_mm' => $larghezzaMm,
            'spessore_mm' => $spessoreMm,
            'volume_mc' => round(Scarto::calculateVolumeMcFromDimensions($lunghezzaMm, $larghezzaMm, $spessoreMm), 6),
            'riutilizzabile' => fake()->boolean(),
            'riutilizzato' => false,
            'riutilizzato_in_lotto_id' => null,
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function riutilizzabile(): static
    {
        return $this->state(fn (array $attributes) => [
            'riutilizzabile' => true,
        ]);
    }

    public function riutilizzato(): static
    {
        return $this->state(fn (array $attributes) => [
            'riutilizzabile' => true,
            'riutilizzato' => true,
            'riutilizzato_in_lotto_id' => LottoProduzione::factory(),
        ]);
    }
}
