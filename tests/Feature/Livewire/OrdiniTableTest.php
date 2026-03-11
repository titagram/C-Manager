<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoConsumoMateriale;
use App\Enums\StatoOrdine;
use App\Enums\TipoMovimento;
use App\Enums\UserRole;
use App\Livewire\Tables\OrdiniTable;
use App\Models\Cliente;
use App\Models\ComponenteCostruzione;
use App\Models\ConsumoMateriale;
use App\Models\Costruzione;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Ordine;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrdiniTableTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->assertStatus(200);
    }

    public function test_component_shows_ordini(): void
    {
        $cliente = Cliente::factory()->create(['ragione_sociale' => 'Cliente Test']);
        Ordine::factory()->create([
            'cliente_id' => $cliente->id,
            'descrizione' => 'Ordine di test',
        ]);

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->assertSee('Cliente Test');
    }

    public function test_search_filter_works(): void
    {
        $cliente1 = Cliente::factory()->create(['ragione_sociale' => 'Rossi Costruzioni']);
        $cliente2 = Cliente::factory()->create(['ragione_sociale' => 'Verdi Legnami']);

        Ordine::factory()->create(['cliente_id' => $cliente1->id, 'descrizione' => 'Lavoro per Rossi']);
        Ordine::factory()->create(['cliente_id' => $cliente2->id, 'descrizione' => 'Lavoro per Verdi']);

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->set('search', 'Rossi')
            ->assertSee('Rossi Costruzioni')
            ->assertDontSee('Verdi Legnami');
    }

    public function test_stato_filter_works(): void
    {
        $cliente = Cliente::factory()->create();

        Ordine::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoOrdine::CONFERMATO,
            'descrizione' => 'Ordine Confermato',
        ]);
        Ordine::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoOrdine::PRONTO,
            'descrizione' => 'Ordine Pronto',
        ]);

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->set('stato', 'confermato')
            ->assertSee('Ordine Confermato')
            ->assertDontSee('Ordine Pronto');
    }

    public function test_can_change_stato_with_valid_transition(): void
    {
        $ordine = Ordine::factory()->confermato()->create();

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->call('cambiaStato', $ordine->id, 'in_produzione');

        $this->assertEquals(StatoOrdine::IN_PRODUZIONE, $ordine->fresh()->stato);
        $this->assertDatabaseHas('bom', [
            'ordine_id' => $ordine->id,
            'source' => 'ordine',
        ]);
    }

    public function test_invalid_stato_transition_is_rejected(): void
    {
        $ordine = Ordine::factory()->confermato()->create();

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->call('cambiaStato', $ordine->id, 'consegnato')
            ->assertHasErrors('stato')
            ->assertSee('Transizione non valida');

        $this->assertEquals(StatoOrdine::CONFERMATO, $ordine->fresh()->stato);
    }

    public function test_can_delete_confermato_ordine(): void
    {
        $ordine = Ordine::factory()->confermato()->create();

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->call('delete', $ordine->id);

        $this->assertSoftDeleted($ordine);
    }

    public function test_reset_filters_works(): void
    {
        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->set('search', 'test')
            ->set('stato', 'confermato')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('stato', '');
    }

    public function test_sorting_works(): void
    {
        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->assertSet('sortField', 'data_ordine')
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'numero')
            ->assertSet('sortField', 'numero')
            ->assertSet('sortDirection', 'asc')
            ->call('sortBy', 'numero')
            ->assertSet('sortDirection', 'desc');
    }

    public function test_operatore_cannot_delete_other_users_ordine(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $operatore = User::factory()->create(['role' => UserRole::OPERATORE]);

        $ordine = Ordine::factory()->confermato()->create([
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($operatore)
            ->test(OrdiniTable::class)
            ->call('delete', $ordine->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($ordine);
    }

    public function test_operatore_cannot_delete_own_ordine(): void
    {
        $operatore = User::factory()->create(['role' => UserRole::OPERATORE]);

        $ordine = Ordine::factory()->confermato()->create([
            'created_by' => $operatore->id,
        ]);

        Livewire::actingAs($operatore)
            ->test(OrdiniTable::class)
            ->call('delete', $ordine->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($ordine);
    }

    public function test_shows_readiness_badge_for_non_ready_order(): void
    {
        $ordine = Ordine::factory()->confermato()->create();

        $costruzione = Costruzione::factory()->create();
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
        ]);

        LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'costruzione_id' => $costruzione->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->assertSee('Da completare');
    }

    public function test_displays_preparazione_avvio_label_instead_of_prontezza(): void
    {
        $ordine = Ordine::factory()->confermato()->create();

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->assertSee('Preparazione avvio')
            ->assertSee('Non coincide con lo stato ordine')
            ->assertDontSee('Prontezza');
    }

    public function test_readiness_badge_is_not_relevant_once_order_is_already_in_produzione(): void
    {
        $ordine = Ordine::factory()->inProduzione()->create();

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->assertSee('Non rilevante');
    }

    public function test_marking_order_pronto_completes_in_lavorazione_lotti(): void
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
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $ordine = Ordine::factory()->create([
            'stato' => StatoOrdine::IN_PRODUZIONE,
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => \App\Enums\StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $this->user->id,
        ]);

        $lotto->materialiUsati()->create([
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse test scarto',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.080000,
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
            ->test(OrdiniTable::class)
            ->call('cambiaStato', $ordine->id, 'pronto');

        $ordine->refresh();
        $lotto->refresh();

        $this->assertSame(StatoOrdine::PRONTO, $ordine->stato);
        $this->assertSame(\App\Enums\StatoLottoProduzione::COMPLETATO, $lotto->stato);

        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'stato' => StatoConsumoMateriale::CONSUMATO->value,
        ]);
    }

    public function test_marking_order_pronto_uses_historical_scarico_when_replanning_is_not_possible(): void
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
            'quantita' => 2,
            'data_movimento' => now()->subDay(),
            'created_by' => $this->user->id,
        ]);

        $ordine = Ordine::factory()->create([
            'stato' => StatoOrdine::IN_PRODUZIONE,
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => \App\Enums\StatoLottoProduzione::IN_LAVORAZIONE,
            'created_by' => $this->user->id,
        ]);

        $scaricoStorico = MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lotto->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 2,
            'data_movimento' => now()->subHours(1),
            'created_by' => $this->user->id,
            'causale' => 'Scarico storico ordine',
        ]);

        $lotto->materialiUsati()->create([
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse storico ordine',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 2.000000,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->call('cambiaStato', $ordine->id, 'pronto');

        $ordine->refresh();
        $lotto->refresh();

        $this->assertSame(StatoOrdine::PRONTO, $ordine->stato);
        $this->assertSame(\App\Enums\StatoLottoProduzione::COMPLETATO, $lotto->stato);
        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'stato' => StatoConsumoMateriale::CONSUMATO->value,
            'movimento_id' => $scaricoStorico->id,
        ]);
    }

    public function test_cannot_delete_ordine_with_active_lotti(): void
    {
        $ordine = Ordine::factory()->confermato()->create();

        LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => \App\Enums\StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        Livewire::actingAs($this->user)
            ->test(OrdiniTable::class)
            ->call('delete', $ordine->id);

        $this->assertNotSoftDeleted($ordine);
    }
}
