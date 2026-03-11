<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Tables\LottiProduzioneTable;
use App\Models\Cliente;
use App\Models\LottoProduzione;
use App\Models\Preventivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LottiProduzioneTableClienteFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_mostra_cliente_da_preventivo_se_cliente_lotto_nullo(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['ragione_sociale' => 'Cliente Preventivo']);

        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $cliente->id,
        ]);

        LottoProduzione::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(LottiProduzioneTable::class)
            ->assertSee('Cliente Preventivo');
    }
}
