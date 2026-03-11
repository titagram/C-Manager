<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_production_settings_page(): void
    {
        $this->get(route('settings.production'))->assertRedirect(route('login'));
    }

    public function test_operatore_cannot_access_production_settings_page(): void
    {
        $operatore = User::factory()->create([
            'role' => UserRole::OPERATORE,
            'is_active' => true,
        ]);

        $this->actingAs($operatore)
            ->get(route('settings.production'))
            ->assertForbidden();
    }

    public function test_admin_can_access_production_settings_page(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->get(route('settings.production'))
            ->assertOk()
            ->assertSee('Settings Produzione');
    }
}
