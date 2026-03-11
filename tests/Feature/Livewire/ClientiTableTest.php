<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Tables\ClientiTable;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientiTableTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
    }

    public function test_clienti_page_contains_livewire_component(): void
    {
        $response = $this->actingAs($this->user)->get('/clienti');

        $response->assertStatus(200);
        $response->assertSeeLivewire(ClientiTable::class);
    }

    public function test_component_renders_clienti(): void
    {
        $cliente = Cliente::factory()->create(['ragione_sociale' => 'Azienda Test']);

        Livewire::actingAs($this->user)
            ->test(ClientiTable::class)
            ->assertSee('Azienda Test');
    }

    public function test_search_filter_works(): void
    {
        Cliente::factory()->create(['ragione_sociale' => 'ABC Company']);
        Cliente::factory()->create(['ragione_sociale' => 'XYZ Industries']);

        Livewire::actingAs($this->user)
            ->test(ClientiTable::class)
            ->set('search', 'ABC')
            ->assertSee('ABC Company')
            ->assertDontSee('XYZ Industries');
    }

    public function test_toggle_active(): void
    {
        $cliente = Cliente::factory()->create(['is_active' => true]);

        Livewire::actingAs($this->user)
            ->test(ClientiTable::class)
            ->call('toggleActive', $cliente->id);

        $this->assertFalse($cliente->fresh()->is_active);
    }

    public function test_delete_cliente(): void
    {
        // Delete requires ADMIN role
        $admin = User::factory()->admin()->create();
        $cliente = Cliente::factory()->create();

        Livewire::actingAs($admin)
            ->test(ClientiTable::class)
            ->call('delete', $cliente->id);

        $this->assertSoftDeleted($cliente);
    }
}
