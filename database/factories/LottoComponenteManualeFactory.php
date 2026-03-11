<?php

namespace Database\Factories;

use App\Models\ComponenteCostruzione;
use App\Models\LottoComponenteManuale;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use Illuminate\Database\Eloquent\Factories\Factory;

class LottoComponenteManualeFactory extends Factory
{
    protected $model = LottoComponenteManuale::class;

    public function definition(): array
    {
        return [
            'lotto_produzione_id' => LottoProduzione::factory(),
            'componente_costruzione_id' => ComponenteCostruzione::factory(),
            'prodotto_id' => Prodotto::factory(),
            'quantita' => $this->faker->randomFloat(4, 1, 20),
            'prezzo_unitario' => $this->faker->optional()->randomFloat(4, 1, 500),
            'unita_misura' => 'pz',
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}
