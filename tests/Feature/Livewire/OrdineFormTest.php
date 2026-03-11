<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Forms\OrdineForm;
use App\Models\Cliente;
use App\Models\Ordine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrdineFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
        $this->cliente = Cliente::factory()->create();
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(OrdineForm::class)
            ->assertStatus(200);
    }

    public function test_can_create_ordine_with_righe(): void
    {
        Livewire::actingAs($this->user)
            ->test(OrdineForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->set('descrizione', 'Test ordine')
            ->set('data_consegna_prevista', now()->addDays(14)->format('Y-m-d'))
            ->set('righe.0.descrizione', 'Cassa legno')
            ->set('righe.0.larghezza_mm', 1200)
            ->set('righe.0.profondita_mm', 800)
            ->set('righe.0.altezza_mm', 600)
            ->set('righe.0.quantita', 5)
            ->set('righe.0.prezzo_mc', 350)
            ->call('save')
            ->assertRedirect('/ordini');

        $this->assertDatabaseHas('ordini', [
            'cliente_id' => $this->cliente->id,
            'descrizione' => 'Test ordine',
        ]);

        $this->assertDatabaseHas('ordine_righe', [
            'descrizione' => 'Cassa legno',
            'larghezza_mm' => 1200,
            'profondita_mm' => 800,
            'altezza_mm' => 600,
            'quantita' => 5,
        ]);
    }

    public function test_cliente_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(OrdineForm::class)
            ->set('cliente_id', null)
            ->set('righe.0.descrizione', 'Test')
            ->set('righe.0.larghezza_mm', 1000)
            ->set('righe.0.profondita_mm', 500)
            ->set('righe.0.altezza_mm', 400)
            ->set('righe.0.quantita', 1)
            ->set('righe.0.prezzo_mc', 100)
            ->call('save')
            ->assertHasErrors(['cliente_id']);
    }

    public function test_can_add_riga(): void
    {
        Livewire::actingAs($this->user)
            ->test(OrdineForm::class)
            ->assertCount('righe', 1)
            ->call('aggiungiRiga')
            ->assertCount('righe', 2);
    }

    public function test_can_remove_riga(): void
    {
        Livewire::actingAs($this->user)
            ->test(OrdineForm::class)
            ->call('aggiungiRiga')
            ->assertCount('righe', 2)
            ->call('rimuoviRiga', 0)
            ->assertCount('righe', 1);
    }

    public function test_cannot_remove_last_riga(): void
    {
        Livewire::actingAs($this->user)
            ->test(OrdineForm::class)
            ->assertCount('righe', 1)
            ->call('rimuoviRiga', 0)
            ->assertCount('righe', 1);
    }

    public function test_ricalcola_updates_totals(): void
    {
        // Volume: 1.2m * 0.8m * 0.6m = 0.576 mc per unit
        // Total volume: 0.576 * 5 = 2.88 mc
        // Total price: 2.88 * 350 = 1008.00 EUR
        $component = Livewire::actingAs($this->user)
            ->test(OrdineForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->set('righe.0.descrizione', 'Cassa legno')
            ->set('righe.0.larghezza_mm', 1200)
            ->set('righe.0.profondita_mm', 800)
            ->set('righe.0.altezza_mm', 600)
            ->set('righe.0.quantita', 5)
            ->set('righe.0.prezzo_mc', 350);

        // The volume_mc should be calculated
        $component->assertSet('righe.0.volume_mc', fn($value) => $value > 0);
        $component->assertSet('totale', fn($value) => $value > 0);
    }

    public function test_can_update_ordine(): void
    {
        $ordine = Ordine::factory()->confermato()->create([
            'cliente_id' => $this->cliente->id,
            'descrizione' => 'Old description',
        ]);

        Livewire::actingAs($this->user)
            ->test(OrdineForm::class, ['ordine' => $ordine])
            ->set('descrizione', 'New description')
            ->set('righe.0.descrizione', 'Updated row')
            ->set('righe.0.larghezza_mm', 1000)
            ->set('righe.0.profondita_mm', 500)
            ->set('righe.0.altezza_mm', 400)
            ->set('righe.0.quantita', 2)
            ->set('righe.0.prezzo_mc', 400)
            ->call('save')
            ->assertRedirect('/ordini');

        $this->assertEquals('New description', $ordine->fresh()->descrizione);
    }

    public function test_mount_supporta_ordine_id_come_stringa(): void
    {
        $ordine = Ordine::factory()->confermato()->create([
            'cliente_id' => $this->cliente->id,
            'descrizione' => 'Ordine esistente',
        ]);

        $ordine->righe()->create([
            'descrizione' => 'Riga da caricamento',
            'larghezza_mm' => 1200,
            'profondita_mm' => 800,
            'altezza_mm' => 600,
            'quantita' => 2,
            'volume_mc_calcolato' => 0.576,
            'volume_mc_finale' => 1.152,
            'prezzo_mc' => 100,
            'totale_riga' => 115.2,
            'ordine' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(OrdineForm::class, ['ordine' => (string) $ordine->id])
            ->assertSet('ordineId', $ordine->id)
            ->assertSet('cliente_id', $this->cliente->id)
            ->assertCount('righe', 1)
            ->assertSet('righe.0.descrizione', 'Riga da caricamento');
    }

    public function test_edit_route_ordini_risponde_senza_type_error_livewire(): void
    {
        $ordine = Ordine::factory()->confermato()->create([
            'cliente_id' => $this->cliente->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('ordini.edit', $ordine))
            ->assertOk();
    }
}
