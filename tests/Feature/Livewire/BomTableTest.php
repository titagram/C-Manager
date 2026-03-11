<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Tables\BomTable;
use App\Models\Bom;
use App\Models\Ordine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BomTableTest extends TestCase
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
            ->test(BomTable::class)
            ->assertStatus(200);
    }

    public function test_component_shows_only_generated_bom(): void
    {
        $ordine = Ordine::factory()->create();

        Bom::factory()->create([
            'nome' => 'Template storico',
            'source' => 'template',
            'generated_at' => null,
        ]);

        Bom::factory()->generatedFromOrder()->create([
            'nome' => 'Lista materiali ORD',
            'ordine_id' => $ordine->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(BomTable::class)
            ->assertSee('Lista materiali ORD')
            ->assertDontSee('Template storico');
    }

    public function test_search_filter_works(): void
    {
        $ordine = Ordine::factory()->create();

        Bom::factory()->generatedFromOrder()->create([
            'nome' => 'Lista Cassa Grande',
            'ordine_id' => $ordine->id,
        ]);

        Bom::factory()->generatedFromOrder()->create([
            'nome' => 'Lista Gabbia Piccola',
            'ordine_id' => $ordine->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(BomTable::class)
            ->set('search', 'Cassa')
            ->assertSee('Lista Cassa Grande')
            ->assertDontSee('Lista Gabbia Piccola');
    }

    public function test_can_delete_bom(): void
    {
        $bom = Bom::factory()->generatedFromOrder()->create([
            'ordine_id' => Ordine::factory()->create()->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(BomTable::class)
            ->call('delete', $bom->id);

        $this->assertSoftDeleted($bom);
    }
}
