<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IstruzioniPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_istruzioni_page(): void
    {
        $this->get(route('istruzioni'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_production_updates_section(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('istruzioni'))
            ->assertOk()
            ->assertSee('Aggiornamenti Produzione (stato corrente)')
            ->assertSee('Flusso operativo ufficiale (2026-03)')
            ->assertSee('Ordine da preventivo')
            ->assertSee('Preventivo oppure Ordine')
            ->assertSee('Opzionato/Consumato')
            ->assertSee('Legenda modifiche:')
            ->assertSee('[NUOVO]')
            ->assertSee('Settings Produzione')
            ->assertSee('Debug Optimizer (admin)')
            ->assertSee('production:generate-cassa-dataset')
            ->assertSee('Settings Produzione - guida operativa')
            ->assertSee('physical')
            ->assertSee('excel_strict')
            ->assertSee('legacy')
            ->assertSee('preview')
            ->assertSee('compatibility')
            ->assertSee('strict')
            ->assertSee('Materiali cassa')
            ->assertSee('Tipi Costruzione')
            ->assertSee('Bancale Standard 2 Vie')
            ->assertSee('Cassa 2 Vie (Con Legacci)')
            ->assertSee('Cassa SP25')
            ->assertSee('Cassa SP25 Fondo 40')
            ->assertSee('Cassa Standard Geometrica')
            ->assertSee('Gabbia Standard')
            ->assertSee('Regola veloce per scegliere il template giusto');
    }
}
