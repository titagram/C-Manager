<?php

namespace Tests\Feature\Regression;

use App\Enums\StatoLottoProduzione;
use App\Livewire\Tables\LottiProduzioneTable;
use App\Models\Cliente;
use App\Models\ConsumoMateriale;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionFlowRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_parity_flow_completes_lotto_and_generates_expected_artifacts(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $materiale = Prodotto::factory()->fitok()->create([
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
        ]);

        $lottoMateriale = LottoMateriale::factory()->withFitok()->create([
            'prodotto_id' => $materiale->id,
            'quantita_iniziale' => 100,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => 'carico',
            'quantita' => 100,
            'data_movimento' => now(),
            'created_by' => $user->id,
        ]);

        $lotto = LottoProduzione::factory()->inLavorazione()->create([
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
            'fitok_calcolato_at' => null,
        ]);

        ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'quantita' => 10,
        ]);

        $lotto->materialiUsati()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $materiale->id,
            'descrizione' => 'Asse principale',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.080000,
            'scarto_totale_mm' => 600,
            'scarto_percentuale' => 15.0,
            'costo_materiale' => 10.0,
            'prezzo_vendita' => 20.0,
            'ordine' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(LottiProduzioneTable::class)
            ->call('completa', $lotto->id);

        $lotto->refresh();

        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertNotNull($lotto->data_fine);
        $this->assertNotNull($lotto->fitok_calcolato_at);
        $this->assertEquals(100.0, (float) $lotto->fitok_percentuale);

        $this->assertDatabaseHas('movimenti_magazzino', [
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lotto->id,
            'tipo' => 'scarico',
            'quantita' => 10,
        ]);

        $this->assertDatabaseHas('scarti', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);
    }

    public function test_strict_flow_keeps_lotto_in_lavorazione_when_stock_is_insufficient(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $materiale = Prodotto::factory()->create();

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $materiale->id,
            'quantita_iniziale' => 5,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => 'carico',
            'quantita' => 5,
            'data_movimento' => now(),
            'created_by' => $user->id,
        ]);

        $lotto = LottoProduzione::factory()->inLavorazione()->create([
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'quantita' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(LottiProduzioneTable::class)
            ->call('completa', $lotto->id);

        $lotto->refresh();

        $this->assertSame(StatoLottoProduzione::IN_LAVORAZIONE, $lotto->stato);
        $this->assertNull($lotto->data_fine);

        $this->assertDatabaseMissing('movimenti_magazzino', [
            'lotto_produzione_id' => $lotto->id,
            'tipo' => 'scarico',
        ]);
    }
}

