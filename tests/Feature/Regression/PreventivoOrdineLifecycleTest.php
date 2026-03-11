<?php

namespace Tests\Feature\Regression;

use App\Enums\TipoMovimento;
use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\StatoPreventivo;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\OrderProductionService;
use App\Services\PreventivoToOrdineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventivoOrdineLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_preventivo_to_order_to_production_lifecycle(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
            'created_by' => $user->id,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'stato' => StatoLottoProduzione::BOZZA,
            'optimizer_result' => ['version' => 'v2', 'total_bins' => 1],
            'created_by' => $user->id,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'lotto_produzione_id' => $lotto->id,
            'descrizione' => 'Lotto lifecycle',
            'quantita' => 1,
            'volume_mc' => 0.0644,
            'materiale_lordo' => 0.0644,
            'prezzo_unitario' => 100,
            'totale_riga' => 6.44,
        ]);

        $ordine = app(PreventivoToOrdineService::class)->convert($preventivo);
        $lotto->refresh();

        $this->assertSame(StatoOrdine::CONFERMATO, $ordine->stato);
        $this->assertSame(StatoLottoProduzione::CONFERMATO, $lotto->stato);
        $this->assertSame($preventivo->id, $lotto->preventivo_id);
        $this->assertSame($ordine->id, $lotto->ordine_id);

        $result = app(OrderProductionService::class)->avviaProduzione($ordine, $user);
        $ordine->refresh();
        $lotto->refresh();

        $this->assertSame(StatoOrdine::IN_PRODUZIONE, $ordine->stato);
        $this->assertSame(StatoLottoProduzione::IN_LAVORAZIONE, $lotto->stato);
        $this->assertDatabaseHas('bom', [
            'id' => $result['bom']->id,
            'ordine_id' => $ordine->id,
            'source' => 'ordine',
        ]);
    }

    public function test_full_flow_options_consumes_and_tracks_scrap_in_inventory(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
            'created_by' => $user->id,
        ]);

        $prodotto = Prodotto::factory()->fitok()->create([
            'unita_misura' => 'mc',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
        ]);

        $lottoMateriale = LottoMateriale::factory()->withFitok()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 10,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 10,
            'data_movimento' => now()->subDay(),
            'created_by' => $user->id,
            'causale' => 'Carico lifecycle test',
        ]);

        $lotto = LottoProduzione::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'stato' => StatoLottoProduzione::BOZZA,
            'optimizer_result' => ['version' => 'v2', 'total_bins' => 1],
            'created_by' => $user->id,
        ]);

        $lotto->materialiUsati()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse lifecycle',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.250000,
            'scarto_totale_mm' => 600,
            'scarto_percentuale' => 15.0,
            'ordine' => 0,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'lotto_produzione_id' => $lotto->id,
            'descrizione' => 'Lotto lifecycle completo',
            'quantita' => 1,
            'volume_mc' => 0.25,
            'materiale_lordo' => 0.25,
            'prezzo_unitario' => 120,
            'totale_riga' => 30.00,
        ]);

        $ordine = app(PreventivoToOrdineService::class)->convert($preventivo);
        $lotto->refresh();

        $this->assertDatabaseHas('bom', [
            'ordine_id' => $ordine->id,
            'source' => 'ordine',
        ]);
        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'stato' => 'opzionato',
            'note' => "Opzionato da avvio produzione ordine {$ordine->numero}",
        ]);

        app(OrderProductionService::class)->avviaProduzione($ordine, $user);
        $ordine->refresh();
        $lotto->refresh();

        $this->assertSame(StatoOrdine::IN_PRODUZIONE, $ordine->stato);
        $this->assertSame(StatoLottoProduzione::IN_LAVORAZIONE, $lotto->stato);

        $completion = app(OrderProductionService::class)->completaProduzione($ordine, $user);
        $ordine->refresh();
        $lotto->refresh();

        $this->assertSame(1, $completion['lotti_completati']);
        $this->assertSame(StatoOrdine::PRONTO, $ordine->stato);
        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertNotNull($lotto->completato_at);
        $this->assertDatabaseHas('movimenti_magazzino', [
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lotto->id,
            'tipo' => TipoMovimento::SCARICO->value,
            'quantita' => 0.25,
            'causale' => "Scarico per lotto produzione {$lotto->codice_lotto}",
        ]);
        $this->assertDatabaseHas('scarti', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        $this->assertSame(100.0, (float) $lotto->fitok_percentuale);
        $this->assertSame(
            1,
            MovimentoMagazzino::query()
                ->where('lotto_produzione_id', $lotto->id)
                ->where('tipo', TipoMovimento::SCARICO->value)
                ->count()
        );
    }
}
