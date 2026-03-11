<?php

namespace Tests\Unit\Services;

use App\Enums\StatoConsumoMateriale;
use App\Enums\StatoLottoProduzione;
use App\Enums\TipoMovimento;
use App\Exceptions\InsufficientStockException;
use App\Models\Bom;
use App\Models\Cliente;
use App\Models\ComponenteCostruzione;
use App\Models\ConsumoMateriale;
use App\Models\Costruzione;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\ProductionLotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionLotServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductionLotService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProductionLotService::class);
        $this->user = User::factory()->create();
    }

    public function test_conferma_lotto_genera_scarichi(): void
    {
        $prodotto = Prodotto::factory()->create(['soggetto_fitok' => true]);
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 100,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $cliente = Cliente::factory()->create();

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'quantita' => 10,
        ]);

        $this->service->confermaLotto($lotto, $this->user);

        $lotto->refresh();

        $this->assertEquals(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertNotNull($lotto->completato_at);
        $this->assertDatabaseHas('movimenti_magazzino', [
            'lotto_materiale_id' => $lottoMat->id,
            'lotto_produzione_id' => $lotto->id,
            'tipo' => 'scarico',
            'quantita' => 10,
        ]);
    }

    public function test_conferma_lotto_auto_opziona_consumi_quando_mancano(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => 'mc',
            'soggetto_fitok' => false,
        ]);
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 50,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 50,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'lotto_materiale_id' => null,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Materiale senza consumi pre-pianificati',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 1.250000,
            'scarto_per_asse_mm' => 500,
            'scarto_totale_mm' => 500,
            'scarto_percentuale' => 12.5,
            'ordine' => 0,
        ]);

        $this->service->confermaLotto($lotto, $this->user);
        $lotto->refresh();

        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'stato' => StatoConsumoMateriale::CONSUMATO->value,
            'quantita' => 1.2500,
        ]);
        $this->assertDatabaseHas('movimenti_magazzino', [
            'lotto_materiale_id' => $lottoMat->id,
            'lotto_produzione_id' => $lotto->id,
            'tipo' => 'scarico',
            'quantita' => 1.2500,
        ]);
        $this->assertDatabaseHas('scarti', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
        ]);
    }

    public function test_conferma_lotto_auto_opziona_consumi_su_lotto_legacy_senza_movimento_carico(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => 'mc',
            'soggetto_fitok' => false,
        ]);

        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 10,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'lotto_materiale_id' => null,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Materiale legacy senza movimento carico',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.750000,
            'scarto_per_asse_mm' => 100,
            'scarto_totale_mm' => 100,
            'scarto_percentuale' => 2.5,
            'ordine' => 0,
        ]);

        $this->service->confermaLotto($lotto, $this->user);
        $lotto->refresh();

        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'stato' => StatoConsumoMateriale::CONSUMATO->value,
            'quantita' => 0.7500,
        ]);
        $this->assertDatabaseHas('movimenti_magazzino', [
            'lotto_materiale_id' => $lottoMat->id,
            'lotto_produzione_id' => $lotto->id,
            'tipo' => 'scarico',
            'quantita' => 0.7500,
        ]);
    }

    public function test_conferma_lotto_blocca_completamento_quando_non_trova_materiale_opzionabile(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => 'mc',
            'soggetto_fitok' => false,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'lotto_materiale_id' => null,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Materiale senza disponibilita in magazzino',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.750000,
            'ordine' => 0,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Nessun materiale opzionabile trovato in magazzino');

        $this->service->confermaLotto($lotto, $this->user);
    }

    public function test_avvia_lavorazione_blocca_lotti_collegati_solo_a_preventivo(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => \App\Models\Preventivo::factory()->create()->id,
            'ordine_id' => null,
            'optimizer_result' => ['version' => 'v2'],
            'created_by' => $this->user->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Associare il lotto');

        $this->service->avviaLavorazione($lotto, $this->user);
    }

    public function test_conferma_lavorazione_blocca_lotti_collegati_solo_a_preventivo(): void
    {
        $lotto = LottoProduzione::factory()->inLavorazione()->create([
            'preventivo_id' => \App\Models\Preventivo::factory()->create()->id,
            'ordine_id' => null,
            'optimizer_result' => ['version' => 'v2'],
            'created_by' => $this->user->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Associare il lotto');

        $this->service->confermaLotto($lotto, $this->user);
    }

    public function test_conferma_lotto_ricostruisce_consumi_da_scarichi_storici_quando_non_ha_opzioni(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => 'mc',
            'soggetto_fitok' => false,
        ]);

        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 0,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $this->user->id,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => TipoMovimento::CARICO->value,
            'quantita' => 2.5,
            'data_movimento' => now()->subDay(),
            'created_by' => $this->user->id,
        ]);

        $scaricoStorico = MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'lotto_produzione_id' => $lotto->id,
            'tipo' => TipoMovimento::SCARICO->value,
            'quantita' => 2.5,
            'data_movimento' => now()->subHours(2),
            'created_by' => $this->user->id,
            'causale' => 'Scarico storico legacy',
        ]);

        $lotto->materialiUsati()->create([
            'lotto_materiale_id' => null,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Materiale legacy già scaricato',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 2.500000,
            'ordine' => 0,
        ]);

        $this->service->confermaLotto($lotto, $this->user);
        $lotto->refresh();

        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'stato' => StatoConsumoMateriale::CONSUMATO->value,
            'quantita' => 2.5000,
            'movimento_id' => $scaricoStorico->id,
        ]);

        $this->assertSame(
            1,
            MovimentoMagazzino::query()
                ->where('lotto_produzione_id', $lotto->id)
                ->where('tipo', TipoMovimento::SCARICO->value)
                ->count()
        );
    }

    public function test_conferma_lotto_resolves_scarto_lotto_materiale_from_consumi_when_missing_on_material_row(): void
    {
        $prodotto = Prodotto::factory()->create(['soggetto_fitok' => false]);
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 100,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'lotto_materiale_id' => null,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Materiale con scarto',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.080000,
            'scarto_per_asse_mm' => 500,
            'scarto_totale_mm' => 500,
            'scarto_percentuale' => 12.5,
            'ordine' => 0,
        ]);

        ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'quantita' => 10,
            'stato' => StatoConsumoMateriale::OPZIONATO,
            'opzionato_at' => now(),
        ]);

        $this->service->confermaLotto($lotto, $this->user);
        $lotto->refresh();

        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertDatabaseHas('scarti', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'riutilizzabile' => true,
        ]);
    }

    public function test_conferma_lotto_calcola_fitok(): void
    {
        $prodottoFitok = Prodotto::factory()->create(['soggetto_fitok' => true]);
        $lottoMatFitok = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoFitok->id,
            'quantita_iniziale' => 100,
        ]);
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMatFitok->id,
            'tipo' => 'carico',
            'quantita' => 100,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $cliente = Cliente::factory()->create();

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMatFitok->id,
            'quantita' => 10,
        ]);

        $this->service->confermaLotto($lotto, $this->user);

        $lotto->refresh();

        $this->assertEquals(100.00, $lotto->fitok_percentuale);
        $this->assertEquals(10, $lotto->fitok_volume_mc);
        $this->assertNotNull($lotto->fitok_calcolato_at);
    }

    public function test_blocca_conferma_senza_disponibilita(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 5,
        ]);
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 5,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $cliente = Cliente::factory()->create();

        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'quantita' => 10, // More than available (5)
        ]);

        $this->expectException(InsufficientStockException::class);

        $this->service->confermaLotto($lotto, $this->user);
    }

    public function test_avvia_lavorazione_permette_stato_confermato(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
            'data_inizio' => null,
            'optimizer_result' => ['version' => 'v2'],
        ]);

        $updated = $this->service->avviaLavorazione($lotto);

        $this->assertEquals(StatoLottoProduzione::IN_LAVORAZIONE, $updated->stato);
        $this->assertNotNull($updated->data_inizio);
        $this->assertNotNull($updated->avviato_at);
    }

    public function test_avvia_lavorazione_blocca_lotto_non_pronto(): void
    {
        $costruzione = Costruzione::factory()->create();
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
        ]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
            'costruzione_id' => $costruzione->id,
            'data_inizio' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Lotto non pronto');

        $this->service->avviaLavorazione($lotto);
    }

    public function test_avvia_lavorazione_generates_bom_for_standalone_lotto(): void
    {
        $prodotto = Prodotto::factory()->create([
            'nome' => 'Abete BOM lotto',
            'soggetto_fitok' => true,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
            'ordine_id' => null,
            'data_inizio' => null,
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse abete',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.080000,
            'pezzi_per_asse' => 1,
            'assi_necessarie' => 1,
            'is_fitok' => true,
            'ordine' => 0,
        ]);

        $updated = $this->service->avviaLavorazione($lotto, $this->user);

        $this->assertEquals(StatoLottoProduzione::IN_LAVORAZIONE, $updated->stato);

        $bom = Bom::query()
            ->where('lotto_produzione_id', $lotto->id)
            ->where('source', 'lotto')
            ->first();

        $this->assertNotNull($bom);
        $this->assertSame($this->user->id, $bom->created_by);
        $this->assertDatabaseHas('bom_righe', [
            'bom_id' => $bom->id,
            'prodotto_id' => $prodotto->id,
            'source_type' => 'aggregato',
            'unita_misura' => $prodotto->unita_misura->value,
        ]);
    }

    public function test_avvia_lavorazione_opziona_consumi_per_lotto_standalone(): void
    {
        $prodotto = Prodotto::factory()->create([
            'nome' => 'Abete opzionato',
            'unita_misura' => 'mc',
        ]);

        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 10,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 10,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
            'ordine_id' => null,
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse opzionata',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 120,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.400000,
            'pezzi_per_asse' => 1,
            'assi_necessarie' => 1,
            'is_fitok' => false,
            'ordine' => 0,
        ]);

        $this->service->avviaLavorazione($lotto, $this->user);

        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'stato' => StatoConsumoMateriale::OPZIONATO->value,
            'quantita' => 0.4000,
        ]);
    }

    public function test_annulla_lotto_rilascia_consumi_opzionati(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 10,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 10,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $this->user->id,
        ]);

        $consumo = ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'quantita' => 2,
            'stato' => StatoConsumoMateriale::OPZIONATO,
            'opzionato_at' => now(),
        ]);

        $updated = $this->service->annullaLotto($lotto);
        $consumo->refresh();

        $this->assertEquals(StatoLottoProduzione::ANNULLATO, $updated->stato);
        $this->assertSame(StatoConsumoMateriale::RILASCIATO, $consumo->stato);
        $this->assertNotNull($consumo->released_at);
    }

    public function test_conferma_lotto_marca_consumi_come_consumati(): void
    {
        $prodotto = Prodotto::factory()->create(['soggetto_fitok' => true]);
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 20,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 20,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->inLavorazione()->create([
            'created_by' => $this->user->id,
        ]);

        $consumo = ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'quantita' => 5,
            'stato' => StatoConsumoMateriale::OPZIONATO,
            'opzionato_at' => now(),
        ]);

        $this->service->confermaLotto($lotto, $this->user);
        $consumo->refresh();
        $lotto->refresh();

        $this->assertSame(StatoConsumoMateriale::CONSUMATO, $consumo->stato);
        $this->assertNotNull($consumo->movimento_id);
        $this->assertNotNull($consumo->consumato_at);
        $this->assertNotNull($lotto->completato_at);
    }

    public function test_annulla_lotto_rilascia_anche_consumi_pianificati(): void
    {
        $prodottoA = Prodotto::factory()->create();
        $lottoMatA = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoA->id,
            'quantita_iniziale' => 10,
        ]);

        $prodottoB = Prodotto::factory()->create();
        $lottoMatB = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoB->id,
            'quantita_iniziale' => 10,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $this->user->id,
        ]);

        $consumoPianificato = ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMatA->id,
            'quantita' => 3,
            'stato' => StatoConsumoMateriale::PIANIFICATO,
        ]);

        $consumoOpzionato = ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMatB->id,
            'quantita' => 2,
            'stato' => StatoConsumoMateriale::OPZIONATO,
            'opzionato_at' => now(),
        ]);

        $updated = $this->service->annullaLotto($lotto);

        $consumoPianificato->refresh();
        $consumoOpzionato->refresh();

        // Lotto annullato
        $this->assertEquals(StatoLottoProduzione::ANNULLATO, $updated->stato);

        // Entrambi i consumi rilasciati
        $this->assertSame(StatoConsumoMateriale::RILASCIATO, $consumoPianificato->stato);
        $this->assertNotNull($consumoPianificato->released_at);
        $this->assertSame(StatoConsumoMateriale::RILASCIATO, $consumoOpzionato->stato);
        $this->assertNotNull($consumoOpzionato->released_at);

        // Idempotenza: seconda chiamata non deve fallire
        $releasedAt = $consumoPianificato->released_at;
        $this->service->annullaLotto($lotto->fresh());
        $consumoPianificato->refresh();
        $this->assertSame(StatoConsumoMateriale::RILASCIATO, $consumoPianificato->stato);
    }
}
