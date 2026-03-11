<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Forms\PreventivoForm;
use App\Models\Cliente;
use App\Models\LottoProduzione;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PreventivoFormLottoClientePropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lotto_creato_da_preventivo_eredita_cliente(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        Livewire::actingAs($user)
            ->test(PreventivoForm::class)
            ->set('cliente_id', $cliente->id)
            ->set('descrizione', 'Preventivo test')
            ->call('creaRigaDaLotto')
            ->assertRedirect();

        $lotto = LottoProduzione::query()->latest('id')->first();

        $this->assertNotNull($lotto);
        $this->assertSame($cliente->id, $lotto->cliente_id);
        $this->assertNotNull($lotto->preventivo_id);

        $this->assertDatabaseHas('preventivo_righe', [
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => 'lotto',
            'include_in_bom' => true,
        ]);
    }
}
