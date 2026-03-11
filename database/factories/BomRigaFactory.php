<?php

namespace Database\Factories;

use App\Enums\UnitaMisura;
use App\Models\Bom;
use App\Models\BomRiga;
use App\Models\Prodotto;
use Illuminate\Database\Eloquent\Factories\Factory;

class BomRigaFactory extends Factory
{
    protected $model = BomRiga::class;

    public function definition(): array
    {
        return [
            'bom_id' => Bom::factory(),
            'prodotto_id' => null,
            'source_type' => null,
            'source_id' => null,
            'descrizione' => $this->faker->optional()->words(3, true),
            'quantita' => $this->faker->randomFloat(4, 0.1, 10),
            'unita_misura' => UnitaMisura::MC,
            'coefficiente_scarto' => 0.10,
            'is_fitok_required' => false,
            'is_optional' => false,
            'ordine' => 0,
            'note' => null,
        ];
    }

    public function withProdotto(): static
    {
        return $this->state(fn () => [
            'prodotto_id' => Prodotto::factory(),
        ]);
    }

    public function fitokRequired(): static
    {
        return $this->state(fn () => [
            'is_fitok_required' => true,
        ]);
    }
}
