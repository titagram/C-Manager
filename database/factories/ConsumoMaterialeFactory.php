<?php

namespace Database\Factories;

use App\Enums\StatoConsumoMateriale;
use App\Models\ConsumoMateriale;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConsumoMateriale>
 */
class ConsumoMaterialeFactory extends Factory
{
    protected $model = ConsumoMateriale::class;

    public function definition(): array
    {
        return [
            'lotto_produzione_id' => LottoProduzione::factory(),
            'lotto_materiale_id' => LottoMateriale::factory(),
            'movimento_id' => null,
            'stato' => StatoConsumoMateriale::PIANIFICATO,
            'opzionato_at' => null,
            'consumato_at' => null,
            'released_at' => null,
            'quantita' => $this->faker->randomFloat(4, 0.1, 10),
            'note' => $this->faker->optional(0.3)->sentence(),
        ];
    }
}
