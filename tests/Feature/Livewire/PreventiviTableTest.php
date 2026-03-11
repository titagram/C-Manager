<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoOrdine;
use App\Enums\StatoPreventivo;
use App\Enums\UserRole;
use App\Livewire\Tables\PreventiviTable;
use App\Models\Cliente;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PreventiviTableTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
    }

    public function test_preventivi_page_contains_livewire_component(): void
    {
        $response = $this->actingAs($this->user)->get('/preventivi');

        $response->assertStatus(200);
        $response->assertSeeLivewire(PreventiviTable::class);
    }

    public function test_component_renders_preventivi(): void
    {
        $cliente = Cliente::factory()->create(['ragione_sociale' => 'Cliente Test']);
        Preventivo::factory()->create([
            'cliente_id' => $cliente->id,
            'descrizione' => 'Preventivo di test',
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->assertSee('Cliente Test');
    }

    public function test_search_filter_works(): void
    {
        $cliente1 = Cliente::factory()->create(['ragione_sociale' => 'Rossi Costruzioni']);
        $cliente2 = Cliente::factory()->create(['ragione_sociale' => 'Verdi Legnami']);

        Preventivo::factory()->create(['cliente_id' => $cliente1->id, 'descrizione' => 'Lavoro per Rossi']);
        Preventivo::factory()->create(['cliente_id' => $cliente2->id, 'descrizione' => 'Lavoro per Verdi']);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->set('search', 'Rossi')
            ->assertSee('Rossi Costruzioni')
            ->assertDontSee('Verdi Legnami');
    }

    public function test_stato_filter_works(): void
    {
        $cliente = Cliente::factory()->create();

        Preventivo::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoPreventivo::BOZZA,
            'descrizione' => 'Prev Bozza',
        ]);
        Preventivo::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoPreventivo::ACCETTATO,
            'descrizione' => 'Prev Accettato',
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->set('stato', 'bozza')
            ->assertSee('Prev Bozza')
            ->assertDontSee('Prev Accettato');
    }

    public function test_can_change_stato(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create([
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->call('cambiaStato', $preventivo->id, 'inviato');

        $this->assertEquals(StatoPreventivo::INVIATO, $preventivo->fresh()->stato);
    }

    public function test_accepting_preventivo_auto_creates_ordine(): void
    {
        $preventivo = Preventivo::factory()->inviato()->create([
            'created_by' => $this->user->id,
            'cliente_id' => Cliente::factory()->create()->id,
            'totale' => 1200,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'totale_riga' => 1200,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->call('cambiaStato', $preventivo->id, 'accettato');

        $preventivo->refresh();

        $this->assertEquals(StatoPreventivo::ACCETTATO, $preventivo->stato);
        $this->assertDatabaseHas('ordini', [
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'stato' => StatoOrdine::CONFERMATO->value,
        ]);
        $this->assertDatabaseMissing('ordini', [
            'preventivo_id' => $preventivo->id,
            'stato' => StatoOrdine::PRONTO->value,
        ]);
    }

    public function test_accepting_preventivo_does_not_create_duplicate_ordine(): void
    {
        $preventivo = Preventivo::factory()->inviato()->create([
            'created_by' => $this->user->id,
            'cliente_id' => Cliente::factory()->create()->id,
            'totale' => 900,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'totale_riga' => 900,
        ]);

        $ordineEsistente = Ordine::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->call('cambiaStato', $preventivo->id, 'accettato');

        $preventivo->refresh();
        $this->assertEquals(StatoPreventivo::ACCETTATO, $preventivo->stato);
        $this->assertDatabaseCount('ordini', 1);
        $this->assertDatabaseHas('ordini', [
            'id' => $ordineEsistente->id,
            'preventivo_id' => $preventivo->id,
        ]);
    }

    public function test_can_duplicate_preventivo(): void
    {
        $preventivo = Preventivo::factory()->create();

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->call('duplica', $preventivo->id);

        $this->assertDatabaseCount('preventivi', 2);

        $duplicato = Preventivo::query()
            ->whereKeyNot($preventivo->id)
            ->first();

        $this->assertNotNull($duplicato);
        $this->assertNotSame($preventivo->numero, $duplicato->numero);
        $this->assertNotSame($preventivo->progressivo, $duplicato->progressivo);
        $this->assertEquals(StatoPreventivo::BOZZA, $duplicato->stato);
    }

    public function test_can_delete_bozza_preventivo(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create([
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->call('delete', $preventivo->id);

        $this->assertSoftDeleted($preventivo);
    }

    public function test_reset_filters(): void
    {
        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->set('search', 'test')
            ->set('stato', 'bozza')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('stato', '');
    }

    public function test_invalid_preventivo_transition_is_rejected(): void
    {
        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->call('cambiaStato', $preventivo->id, 'bozza')
            ->assertHasErrors('stato');

        $this->assertEquals(StatoPreventivo::ACCETTATO, $preventivo->fresh()->stato);
    }

    public function test_operatore_cannot_convert_other_users_preventivo(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $operatore = User::factory()->create(['role' => UserRole::OPERATORE]);

        $preventivo = Preventivo::factory()->accettato()->create([
            'created_by' => $admin->id,
        ]);
        PreventivoRiga::factory()->create(['preventivo_id' => $preventivo->id]);

        Livewire::actingAs($operatore)
            ->test(PreventiviTable::class)
            ->call('convertiInOrdine', $preventivo->id)
            ->assertForbidden();

        $this->assertDatabaseCount('ordini', 0);
    }

    public function test_admin_can_convert_accettato_preventivo_to_ordine(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $preventivo = Preventivo::factory()->accettato()->create([
            'created_by' => $admin->id,
            'totale' => 1500.00,
        ]);
        PreventivoRiga::factory()->create(['preventivo_id' => $preventivo->id]);

        Livewire::actingAs($admin)
            ->test(PreventiviTable::class)
            ->call('convertiInOrdine', $preventivo->id)
            ->assertRedirect();

        $this->assertDatabaseCount('ordini', 1);

        $ordine = Ordine::first();
        $this->assertEquals(StatoOrdine::CONFERMATO, $ordine->stato);
        $this->assertEquals($preventivo->id, $ordine->preventivo_id);
        $this->assertEquals($preventivo->cliente_id, $ordine->cliente_id);
    }

    public function test_converti_in_ordine_preserva_volume_e_totale_delle_righe_lotto(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $preventivo = Preventivo::factory()->accettato()->create([
            'created_by' => $admin->id,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'tipo_riga' => \App\Enums\TipoRigaPreventivo::LOTTO->value,
            'unita_misura' => \App\Enums\UnitaMisura::MC->value,
            'descrizione' => 'Lotto E2E',
            'volume_mc' => 0.0115,
            'materiale_lordo' => 0.0115,
            'prezzo_unitario' => 500,
            'totale_riga' => 5.75,
        ]);

        Livewire::actingAs($admin)
            ->test(PreventiviTable::class)
            ->call('convertiInOrdine', $preventivo->id)
            ->assertRedirect();

        $ordine = Ordine::with('righe')->firstOrFail();
        $riga = $ordine->righe->firstOrFail();

        $this->assertEqualsWithDelta(0.0115, (float) $riga->volume_mc_finale, 0.000001);
        $this->assertEqualsWithDelta(5.75, (float) $riga->totale_riga, 0.01);
        $this->assertEqualsWithDelta(5.75, (float) $ordine->totale, 0.01);
    }

    public function test_duplicated_preventivo_righe_point_to_a_new_cloned_lotto(): void
    {
        $prodotto = \App\Models\Prodotto::factory()->create();
        $lotto = \App\Models\LottoProduzione::factory()->completato()->create([
            'optimizer_result' => ['version' => 'v2'],
        ]);
        $lotto->materialiUsati()->create([
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse duplicabile',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 2,
            'volume_mc' => 0.080000,
            'ordine' => 0,
        ]);

        $preventivo = Preventivo::factory()->create([
            'created_by' => $this->user->id,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'lotto_produzione_id' => $lotto->id,
            'quantita' => 5,
            'totale_riga' => 100.00,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->call('duplica', $preventivo->id);

        $this->assertDatabaseCount('preventivi', 2);

        $duplicato = Preventivo::query()->whereKeyNot($preventivo->id)->first();
        $rigaDuplicata = $duplicato->righe->first();
        $rigaOriginale = $preventivo->fresh()->righe->first();
        $lottoDuplicato = \App\Models\LottoProduzione::query()->find($rigaDuplicata->lotto_produzione_id);

        $this->assertNotNull($lottoDuplicato);
        $this->assertNotSame($lotto->id, $lottoDuplicato->id);
        $this->assertSame($duplicato->id, $lottoDuplicato->preventivo_id);
        $this->assertSame(\App\Enums\StatoLottoProduzione::BOZZA, $lottoDuplicato->stato);
        $this->assertTrue($lottoDuplicato->hasTechnicalDefinition());
        $this->assertDatabaseHas('lotto_produzione_materiali', [
            'lotto_produzione_id' => $lottoDuplicato->id,
            'prodotto_id' => $prodotto->id,
        ]);

        $this->assertEquals($lotto->id, $rigaOriginale->lotto_produzione_id);
        $this->assertEquals(5, $rigaDuplicata->quantita);
        $this->assertEquals(100.00, (float) $rigaDuplicata->totale_riga);
    }

    public function test_cannot_delete_preventivo_with_active_lotto(): void
    {
        $lotto = \App\Models\LottoProduzione::factory()->bozza()->create();

        $preventivo = Preventivo::factory()->bozza()->create([
            'created_by' => $this->user->id,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'lotto_produzione_id' => $lotto->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PreventiviTable::class)
            ->call('delete', $preventivo->id);

        // Il preventivo NON deve essere cancellato
        $this->assertNotSoftDeleted($preventivo);

        // La riga deve esistere ancora con il lotto collegato
        $this->assertDatabaseHas('preventivo_righe', [
            'preventivo_id' => $preventivo->id,
            'lotto_produzione_id' => $lotto->id,
        ]);
    }
}
