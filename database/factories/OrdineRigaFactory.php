<?php

namespace Database\Factories;

use App\Models\Ordine;
use App\Models\OrdineRiga;
use App\Models\Prodotto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrdineRiga>
 */
class OrdineRigaFactory extends Factory
{
    protected $model = OrdineRiga::class;

    public function definition(): array
    {
        $larghezza = fake()->numberBetween(500, 2000);
        $profondita = fake()->numberBetween(500, 1500);
        $altezza = fake()->numberBetween(300, 1200);
        $quantita = fake()->numberBetween(1, 10);
        $prezzo_mc = fake()->randomFloat(4, 200, 400);

        $volume = ($larghezza / 1000) * ($profondita / 1000) * ($altezza / 1000);
        $volume_finale = $volume * $quantita;

        return [
            'ordine_id' => Ordine::factory(),
            'prodotto_id' => Prodotto::factory(),
            'descrizione' => fake()->words(3, true),
            'tipo_costruzione' => fake()->randomElement(['cassa_sp25', 'cassa_sp30', 'gabbia_sp20']),
            'larghezza_mm' => $larghezza,
            'profondita_mm' => $profondita,
            'altezza_mm' => $altezza,
            'riferimento_volume' => 'esterno',
            'quantita' => $quantita,
            'volume_mc_calcolato' => round($volume, 6),
            'volume_mc_finale' => round($volume_finale, 6),
            'prezzo_mc' => $prezzo_mc,
            'totale_riga' => round($volume_finale * $prezzo_mc, 2),
            'ordine' => 0,
        ];
    }
}
