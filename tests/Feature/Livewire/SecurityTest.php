<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Tables\ClientiTable;
use App\Livewire\Tables\PreventiviTable;
use App\Livewire\Tables\ProdottiTable;
use App\Models\Cliente;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function unauthenticated_users_cannot_access_tables()
    {
        $this->get(route('prodotti.index'))->assertRedirect('login');
        $this->get(route('clienti.index'))->assertRedirect('login');
        $this->get(route('preventivi.index'))->assertRedirect('login');
    }

    /** @test */
    public function unauthorized_users_cannot_delete_products()
    {
        // Create user without delete permission (assuming 'user' role doesn't have it, or mocking policy)
        // Since we are using UserRole enum, we need to ensuring a Role that CANNOT delete.
        // If ADMIN is the only role, we might need to mock policies or create a restricted user.
        
        // Strategy: Create a user and mock the Policy to DENY delete.
        $user = User::factory()->create();
        $prodotto = Prodotto::factory()->create();

        // Acting as a user who is NOT authorized
        // We simulate this by ensuring the Policy returns false.
        // For this test, we assume a standard user might not have permission.
        // If policies check isAdmin(), we make sure this user is NOT admin.
        
        $this->actingAs($user);

        // Mock Policy via Gate if complex, or rely on logic. 
        // Let's assume non-admins cannot delete. If they can, we'll need to adjust.
        // Based on User model, `hasPermission` checks role. 
        
        // Attempt to call delete method
        Livewire::test(ProdottiTable::class)
            ->call('delete', $prodotto)
            ->assertForbidden(); // Should return 403
            
        $this->assertDatabaseHas('prodotti', ['id' => $prodotto->id]);
    }

    /** @test */
    public function unauthorized_users_cannot_delete_clients()
    {
        $user = User::factory()->create(); // Non-admin
        $cliente = Cliente::factory()->create();

        $this->actingAs($user);

        Livewire::test(ClientiTable::class)
            ->call('delete', $cliente)
            ->assertForbidden();

        $this->assertDatabaseHas('clienti', ['id' => $cliente->id]);
    }

    /** @test */
    public function unauthorized_users_cannot_delete_preventivi()
    {
        $user = User::factory()->create();
        $preventivo = Preventivo::factory()->create();

        $this->actingAs($user);

        Livewire::test(PreventiviTable::class)
            ->call('delete', $preventivo)
            ->assertForbidden();

        $this->assertDatabaseHas('preventivi', ['id' => $preventivo->id]);
    }

    /** @test */
    public function authorized_users_can_delete_products()
    {
        // Admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $prodotto = Prodotto::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ProdottiTable::class)
            ->call('delete', $prodotto)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('prodotti', ['id' => $prodotto->id]);
    }
}
