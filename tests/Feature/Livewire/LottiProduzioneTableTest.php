<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoConsumoMateriale;
use App\Enums\StatoLottoProduzione;
use App\Enums\TipoMovimento;
use App\Enums\UserRole;
use App\Livewire\Tables\LottiProduzioneTable;
use App\Models\ComponenteCostruzione;
use App\Models\ConsumoMateriale;
use App\Models\Costruzione;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LottiProduzioneTableTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_avvia_moves_confermato_lotto_to_in_lavorazione(): void
    {
        $ordine = \App\Models\Ordine::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::CONFERMATO,
            'data_inizio' => null,
            'optimizer_result' => ['version' => 'v2'],
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('avvia', $lotto->id);

        $lotto->refresh();
        $this->assertEquals(StatoLottoProduzione::IN_LAVORAZIONE, $lotto->stato);
        $this->assertNotNull($lotto->data_inizio);
    }

    public function test_avvia_creates_generated_bom_for_standalone_lotto(): void
    {
        $prodotto = Prodotto::factory()->create([
            'nome' => 'Materiale avvio lotto',
        ]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
            'ordine_id' => null,
            'data_inizio' => null,
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse test',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 120,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.096000,
            'pezzi_per_asse' => 1,
            'assi_necessarie' => 1,
            'is_fitok' => false,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('avvia', $lotto->id);

        $bomId = \App\Models\Bom::query()
            ->where('lotto_produzione_id', $lotto->id)
            ->where('source', 'lotto')
            ->value('id');

        $this->assertNotNull($bomId);
        $this->assertDatabaseHas('bom_righe', [
            'bom_id' => $bomId,
            'prodotto_id' => $prodotto->id,
            'source_type' => 'aggregato',
        ]);
    }

    public function test_completa_keeps_bozza_lotto_unchanged_when_transition_is_invalid(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create();

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('completa', $lotto->id);

        $lotto->refresh();
        $this->assertEquals(StatoLottoProduzione::BOZZA, $lotto->stato);
        $this->assertNull($lotto->data_fine);
    }

    public function test_completa_transitions_in_lavorazione_lotto_using_service_logic(): void
    {
        $lotto = LottoProduzione::factory()->inLavorazione()->create([
            'fitok_calcolato_at' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('completa', $lotto->id);

        $lotto->refresh();
        $this->assertEquals(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertNotNull($lotto->fitok_calcolato_at);
        $this->assertNotNull($lotto->data_fine);
    }

    public function test_operatore_can_complete_last_lotto_and_related_order_becomes_pronto(): void
    {
        $operatore = User::factory()->create(['role' => UserRole::OPERATORE]);
        $ordine = \App\Models\Ordine::factory()->create([
            'stato' => \App\Enums\StatoOrdine::IN_PRODUZIONE,
            'created_by' => $this->user->id,
        ]);

        LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::COMPLETATO,
            'created_by' => $this->user->id,
        ]);

        $lottoDaChiudere = LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($operatore)
            ->test(LottiProduzioneTable::class)
            ->call('completa', $lottoDaChiudere->id);

        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lottoDaChiudere->fresh()->stato);
        $this->assertSame(\App\Enums\StatoOrdine::PRONTO, $ordine->fresh()->stato);
    }

    public function test_operatore_lotti_table_shows_only_complete_action_for_in_lavorazione_lotto(): void
    {
        $operatore = User::factory()->create(['role' => UserRole::OPERATORE]);
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($operatore)
            ->test(LottiProduzioneTable::class)
            ->assertSee($lotto->codice_lotto)
            ->assertSeeHtml('title="Completa"')
            ->assertDontSeeHtml('title="Avvia lavorazione"')
            ->assertDontSeeHtml('title="Annulla"')
            ->assertDontSeeHtml('title="Elimina"');
    }

    public function test_completa_consumes_opzionato_materiale_and_creates_scarico(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 10,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 10,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        $lotto = LottoProduzione::factory()->inLavorazione()->create([
            'created_by' => $this->user->id,
        ]);

        $consumo = ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'quantita' => 3,
            'stato' => StatoConsumoMateriale::OPZIONATO,
            'opzionato_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('completa', $lotto->id);

        $consumo->refresh();
        $lotto->refresh();

        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertSame(StatoConsumoMateriale::CONSUMATO, $consumo->stato);
        $this->assertNotNull($consumo->movimento_id);
        $this->assertDatabaseHas('movimenti_magazzino', [
            'id' => $consumo->movimento_id,
            'tipo' => TipoMovimento::SCARICO->value,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lotto->id,
            'quantita' => 3,
        ]);
    }

    public function test_completa_auto_opziona_consumi_when_missing_and_scales_magazzino(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => 'mc',
        ]);
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 10,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 10,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        $lotto = LottoProduzione::factory()->inLavorazione()->create([
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'lotto_materiale_id' => null,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Materiale da opzionare automaticamente',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 2.000000,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('completa', $lotto->id);

        $lotto->refresh();
        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'stato' => StatoConsumoMateriale::CONSUMATO->value,
            'quantita' => 2.0000,
        ]);
        $this->assertDatabaseHas('movimenti_magazzino', [
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lotto->id,
            'tipo' => TipoMovimento::SCARICO->value,
            'quantita' => 2.0000,
        ]);
    }

    public function test_completa_uses_historical_scarico_when_no_stock_is_left_for_replanning(): void
    {
        $prodotto = Prodotto::factory()->create([
            'unita_misura' => 'mc',
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 0,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 1.2,
            'created_by' => $this->user->id,
            'data_movimento' => now()->subDay(),
        ]);

        $lotto = LottoProduzione::factory()->inLavorazione()->create([
            'created_by' => $this->user->id,
        ]);

        $scaricoStorico = MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lotto->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1.2,
            'created_by' => $this->user->id,
            'data_movimento' => now()->subHours(2),
            'causale' => 'Scarico storico',
        ]);

        $lotto->materialiUsati()->create([
            'lotto_materiale_id' => null,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Materiale da storico',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 1.200000,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('completa', $lotto->id);

        $lotto->refresh();
        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'stato' => StatoConsumoMateriale::CONSUMATO->value,
            'quantita' => 1.2000,
            'movimento_id' => $scaricoStorico->id,
        ]);
    }

    public function test_completa_resolves_scarto_lotto_materiale_from_consumi_when_missing_on_material_row(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 10,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 10,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        $lotto = LottoProduzione::factory()->inLavorazione()->create([
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
            'lotto_materiale_id' => $lottoMateriale->id,
            'quantita' => 3,
            'stato' => StatoConsumoMateriale::OPZIONATO,
            'opzionato_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('completa', $lotto->id);

        $lotto->refresh();
        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertDatabaseHas('scarti', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
        ]);
    }

    public function test_annulla_does_not_change_completato_lotto(): void
    {
        $lotto = LottoProduzione::factory()->completato()->create();

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('annulla', $lotto->id);

        $lotto->refresh();
        $this->assertEquals(StatoLottoProduzione::COMPLETATO, $lotto->stato);
    }

    public function test_shows_readiness_badge_for_non_ready_lotto(): void
    {
        $costruzione = Costruzione::factory()->create();
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
        ]);

        LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
            'costruzione_id' => $costruzione->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->assertSee('Da completare');
    }

    public function test_displays_preparazione_avvio_label_instead_of_prontezza(): void
    {
        LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->assertSee('Preparazione avvio')
            ->assertSee('Indica se il lotto ha tutti i dati necessari per essere avviato in produzione (materiali calcolati e componenti manuali completi).')
            ->assertDontSee('Prontezza');
    }

    public function test_displays_mobile_responsive_cards_container(): void
    {
        LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->assertSee('id="lotti-mobile-list"', false);
    }

    public function test_operatore_cannot_annulla_other_users_lotto(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $operatore = User::factory()->create(['role' => UserRole::OPERATORE]);

        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($operatore)
            ->test(LottiProduzioneTable::class)
            ->call('annulla', $lotto->id)
            ->assertForbidden();

        $this->assertEquals(StatoLottoProduzione::IN_LAVORAZIONE, $lotto->fresh()->stato);
    }

    public function test_delete_soft_deletes_lotto_and_removes_linked_preventivo_riga(): void
    {
        $preventivo = Preventivo::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'created_by' => $this->user->id,
        ]);

        $riga = PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'lotto_produzione_id' => $lotto->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LottiProduzioneTable::class)
            ->call('delete', $lotto->id);

        $this->assertSoftDeleted('lotti_produzione', ['id' => $lotto->id]);
        $this->assertDatabaseMissing('preventivo_righe', ['id' => $riga->id]);
    }
}
