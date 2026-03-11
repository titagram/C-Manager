<?php

namespace Tests\Unit\Models;

use App\Enums\StatoConsumoMateriale;
use App\Models\ConsumoMateriale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumoMaterialeStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumo_supporta_stati_pianificazione_e_opzione(): void
    {
        $consumo = ConsumoMateriale::factory()->create();

        $this->assertSame(StatoConsumoMateriale::PIANIFICATO, $consumo->stato);
        $this->assertTrue($consumo->isConsumabile());

        $consumo->update([
            'stato' => StatoConsumoMateriale::OPZIONATO,
            'opzionato_at' => now(),
        ]);

        $consumo->refresh();

        $this->assertTrue($consumo->isOpzionato());
        $this->assertTrue($consumo->isConsumabile());
    }
}
