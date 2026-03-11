<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_operatore_does_not_see_anagrafica_section(): void
    {
        $operatore = User::factory()->create(['role' => UserRole::OPERATORE]);

        $response = $this->actingAs($operatore)->get(route('lotti.index'));
        $response->assertStatus(200);

        // Operatore sees only operaio sections
        $response->assertSee('Magazzino');
        $response->assertSee('Lotti Produzione');
        $response->assertSee('Distinte Base');

        // Operatore should NOT see admin-only items
        $response->assertDontSee('Dashboard');
        $response->assertDontSee('Registro FITOK');
        $response->assertDontSee('Preventivi');
        $response->assertDontSee('Ordini');
        $response->assertDontSee('Fornitori');
        $response->assertDontSee('Clienti');
        $response->assertDontSee('Materiali');
        $response->assertDontSee('Costruzioni');
        $response->assertDontSee('Settings Produzione');
        $response->assertDontSee('Istruzioni');
    }

    public function test_admin_sees_all_sidebar_items(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // Admin sees everything
        $response->assertSee('Dashboard');
        $response->assertSee('Magazzino');
        $response->assertSee('Lotti Produzione');
        $response->assertSee('Preventivi');
        $response->assertSee('Ordini');
        $response->assertSee('Fornitori');
        $response->assertSee('Clienti');
        $response->assertSee('Materiali');
        $response->assertSee('Costruzioni');
    }
}
