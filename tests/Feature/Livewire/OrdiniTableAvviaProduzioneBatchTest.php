<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\UserRole;
use App\Livewire\Tables\OrdiniTable;
use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrdiniTableAvviaProduzioneBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_avvia_produzione_da_ordine_avvia_tutti_i_lotti_collegati(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $ordine = Ordine::factory()->confermato()->create([
            'created_by' => $user->id,
        ]);

        $lottoA = LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::BOZZA,
            'optimizer_result' => ['version' => 'v2'],
            'created_by' => $user->id,
        ]);

        $lottoB = LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::CONFERMATO,
            'optimizer_result' => ['version' => 'v2'],
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(OrdiniTable::class)
            ->call('cambiaStato', $ordine->id, 'in_produzione');

        $ordine->refresh();
        $lottoA->refresh();
        $lottoB->refresh();

        $this->assertSame(StatoOrdine::IN_PRODUZIONE, $ordine->stato);
        $this->assertSame(StatoLottoProduzione::IN_LAVORAZIONE, $lottoA->stato);
        $this->assertSame(StatoLottoProduzione::IN_LAVORAZIONE, $lottoB->stato);

        $this->assertDatabaseHas('bom', [
            'ordine_id' => $ordine->id,
            'source' => 'ordine',
        ]);
    }
}
