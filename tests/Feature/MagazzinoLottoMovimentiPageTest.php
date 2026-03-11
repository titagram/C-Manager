<?php

namespace Tests\Feature;

use App\Enums\TipoMovimento;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Ordine;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazzinoLottoMovimentiPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_lists_manual_and_production_outbound_movements_for_material_lot(): void
    {
        $user = User::factory()->create();
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LOT-MOV-PAGE-001',
        ]);

        $ordine = Ordine::factory()->create([
            'numero' => 'ORD-MOV-001',
        ]);
        $lottoProduzione = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-MOV-001',
            'ordine_id' => $ordine->id,
            'created_by' => $user->id,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 5,
            'created_by' => $user->id,
            'data_movimento' => now()->subDays(2),
            'causale' => 'Carico iniziale da ignorare',
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1.25,
            'created_by' => $user->id,
            'data_movimento' => now()->subDay(),
            'causale' => 'Scarico manuale di test',
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lottoProduzione->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 0.75,
            'created_by' => $user->id,
            'data_movimento' => now(),
            'causale' => 'Scarico da consumo di test',
        ]);

        $response = $this->actingAs($user)
            ->get(route('magazzino.movimenti', $lottoMateriale));

        $response->assertOk();
        $response->assertSee('LOT-MOV-PAGE-001');
        $response->assertSee('Scarico manuale di test');
        $response->assertSee('Scarico da consumo di test');
        $response->assertSee('Scarico manuale');
        $response->assertSee('Consumo lotto LP-MOV-001');
        $response->assertSee('ORD-MOV-001');
        $response->assertDontSee('Carico iniziale da ignorare');
    }
}
