<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Tables\ProdottiTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProdottiTableNoMaterialiTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_mostra_tab_materiali(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProdottiTable::class)
            ->assertDontSee('Materiali');
    }
}
