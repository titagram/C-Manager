<?php

namespace Database\Factories;

use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\LottoProduzioneMateriale;
use App\Models\Prodotto;
use Illuminate\Database\Eloquent\Factories\Factory;

class LottoProduzioneMaterialeFactory extends Factory
{
    protected $model = LottoProduzioneMateriale::class;

    public function definition(): array
    {
        return [
            'lotto_produzione_id' => LottoProduzione::factory(),
            'lotto_materiale_id' => LottoMateriale::factory(),
            'prodotto_id' => Prodotto::factory(),
            'descrizione' => fake()->words(3, true),
            'lunghezza_mm' => fake()->numberBetween(500, 3000),
            'larghezza_mm' => fake()->numberBetween(50, 300),
            'spessore_mm' => fake()->numberBetween(10, 50),
            'quantita_pezzi' => fake()->numberBetween(1, 100),
            'volume_mc' => fake()->randomFloat(6, 0.001, 10),
            'volume_netto_mc' => fake()->randomFloat(6, 0.001, 10),
            'volume_scarto_mc' => fake()->randomFloat(6, 0.0001, 1),
            'pezzi_per_asse' => fake()->numberBetween(1, 10),
            'assi_necessarie' => fake()->numberBetween(1, 50),
            'scarto_per_asse_mm' => fake()->randomFloat(2, 0, 500),
            'scarto_totale_mm' => fake()->randomFloat(2, 0, 5000),
            'scarto_percentuale' => fake()->randomFloat(2, 0, 20),
            'costo_materiale' => fake()->randomFloat(2, 10, 1000),
            'prezzo_vendita' => fake()->randomFloat(2, 50, 5000),
            'is_fitok' => fake()->boolean(),
            'ordine' => 0,
        ];
    }
}
