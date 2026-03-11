<?php

namespace Database\Factories;

use App\Enums\TipoRigaPreventivo;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\Prodotto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PreventivoRiga>
 */
class PreventivoRigaFactory extends Factory
{
    protected $model = PreventivoRiga::class;

    public function definition(): array
    {
        $lunghezza = $this->faker->numberBetween(500, 3000);
        $larghezza = $this->faker->numberBetween(100, 500);
        $spessore = $this->faker->numberBetween(20, 100);
        $quantita = $this->faker->numberBetween(1, 10);

        // Calculate volume in cubic meters
        $volume_mc = ($lunghezza / 1000) * ($larghezza / 1000) * ($spessore / 1000) * $quantita;
        $coefficiente_scarto = $this->faker->randomFloat(4, 0.05, 0.15);
        $materiale_netto = $volume_mc;
        $materiale_lordo = ceil($materiale_netto * (1 + $coefficiente_scarto) * 1000) / 1000;
        $prezzo_unitario = $this->faker->randomFloat(2, 100, 800);
        $totale_riga = round($materiale_lordo * $prezzo_unitario, 2);

        return [
            'preventivo_id' => Preventivo::factory(),
            'tipo_riga' => TipoRigaPreventivo::SFUSO,
            'include_in_bom' => true,
            'prodotto_id' => Prodotto::factory(),
            'unita_misura' => 'mc',
            'descrizione' => $this->faker->sentence(),
            'lunghezza_mm' => $lunghezza,
            'larghezza_mm' => $larghezza,
            'spessore_mm' => $spessore,
            'quantita' => $quantita,
            'superficie_mq' => ($lunghezza / 1000) * ($larghezza / 1000) * $quantita,
            'volume_mc' => $volume_mc,
            'materiale_netto' => $materiale_netto,
            'coefficiente_scarto' => $coefficiente_scarto,
            'materiale_lordo' => $materiale_lordo,
            'prezzo_unitario' => $prezzo_unitario,
            'totale_riga' => $totale_riga,
            'ordine' => 0,
        ];
    }
}
