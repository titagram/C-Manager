<?php

namespace Database\Factories;

use App\Models\Bom;
use App\Models\Prodotto;
use Illuminate\Database\Eloquent\Factories\Factory;

class BomFactory extends Factory
{
    protected $model = Bom::class;

    public function definition(): array
    {
        return [
            'codice' => null,
            'nome' => $this->faker->words(3, true),
            'prodotto_id' => null,
            'lotto_produzione_id' => null,
            'ordine_id' => null,
            'categoria_output' => null,
            'versione' => '1.0',
            'is_active' => true,
            'generated_at' => null,
            'source' => 'template',
            'note' => $this->faker->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function withProdotto(): static
    {
        return $this->state(fn () => [
            'prodotto_id' => Prodotto::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function generatedFromOrder(): static
    {
        return $this->state(fn () => [
            'source' => 'ordine',
            'generated_at' => now(),
        ]);
    }
}
