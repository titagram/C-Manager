<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Forms\BomForm;
use App\Models\Bom;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BomFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(BomForm::class)
            ->assertStatus(200);
    }

    public function test_can_create_bom(): void
    {
        $prodotto = Prodotto::factory()->create();

        Livewire::actingAs($this->user)
            ->test(BomForm::class)
            ->set('nome', 'Cassa Standard 80x80')
            ->set('versione', '1.0')
            ->set('righe.0.prodotto_id', $prodotto->id)
            ->set('righe.0.quantita', 0.5)
            ->set('righe.0.coefficiente_scarto', 0.10)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bom', [
            'nome' => 'Cassa Standard 80x80',
        ]);

        $this->assertDatabaseHas('bom_righe', [
            'prodotto_id' => $prodotto->id,
            'quantita' => 0.5,
        ]);
    }

    public function test_nome_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(BomForm::class)
            ->set('nome', '')
            ->call('save')
            ->assertHasErrors(['nome' => 'required']);
    }

    public function test_can_add_riga(): void
    {
        Livewire::actingAs($this->user)
            ->test(BomForm::class)
            ->assertCount('righe', 1)
            ->call('aggiungiRiga')
            ->assertCount('righe', 2);
    }

    public function test_can_remove_riga(): void
    {
        Livewire::actingAs($this->user)
            ->test(BomForm::class)
            ->call('aggiungiRiga')
            ->assertCount('righe', 2)
            ->call('rimuoviRiga', 0)
            ->assertCount('righe', 1);
    }

    public function test_cannot_remove_last_riga(): void
    {
        Livewire::actingAs($this->user)
            ->test(BomForm::class)
            ->assertCount('righe', 1)
            ->call('rimuoviRiga', 0)
            ->assertCount('righe', 1);
    }

    public function test_can_update_bom(): void
    {
        $bom = Bom::factory()->create(['nome' => 'Old Name']);

        Livewire::actingAs($this->user)
            ->test(BomForm::class, ['bom' => $bom])
            ->set('nome', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('New Name', $bom->fresh()->nome);
    }
}
