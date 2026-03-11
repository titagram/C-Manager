<?php

namespace Tests\Feature\Livewire;

use App\Enums\Categoria;
use App\Enums\UserRole;
use App\Livewire\Tables\ProdottiTable;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProdottiTableTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_prodotti_page_contains_livewire_component(): void
    {
        $response = $this->actingAs($this->user)->get('/prodotti');

        $response->assertStatus(200);
        $response->assertSeeLivewire(ProdottiTable::class);
    }

    public function test_component_renders_prodotti(): void
    {
        $prodotto = Prodotto::factory()->create(['nome' => 'Test Prodotto']);

        Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->assertSee('Test Prodotto');
    }

    public function test_search_filter_works(): void
    {
        Prodotto::factory()->create(['nome' => 'Tavola Abete']);
        Prodotto::factory()->create(['nome' => 'Pannello MDF']);

        Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->set('search', 'Abete')
            ->assertSee('Tavola Abete')
            ->assertDontSee('Pannello MDF');
    }

    public function test_categoria_filter_works(): void
    {
        Prodotto::factory()->create(['nome' => 'Tavola Abete', 'categoria' => Categoria::ASSE]);
        Prodotto::factory()->create(['nome' => 'Viti Acciaio', 'categoria' => Categoria::FERRAMENTA]);

        Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->set('categoria', 'asse')
            ->assertSee('Tavola Abete')
            ->assertDontSee('Viti Acciaio');
    }

    public function test_stato_filter_attivi(): void
    {
        Prodotto::factory()->create(['nome' => 'Attivo', 'is_active' => true]);
        Prodotto::factory()->create(['nome' => 'Inattivo', 'is_active' => false]);

        Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->set('stato', 'attivi')
            ->assertSee('Attivo')
            ->assertDontSee('Inattivo');
    }

    public function test_toggle_active(): void
    {
        $prodotto = Prodotto::factory()->create(['is_active' => true]);

        Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->call('toggleActive', $prodotto->id);

        $this->assertFalse($prodotto->fresh()->is_active);
    }

    public function test_delete_prodotto(): void
    {
        $prodotto = Prodotto::factory()->create();

        Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->call('delete', $prodotto->id);

        $this->assertSoftDeleted($prodotto);
    }

    public function test_can_duplicate_prodotto(): void
    {
        $prodotto = Prodotto::factory()->create([
            'codice' => 'PRD-ORIG-01',
            'nome' => 'Prodotto Originale',
            'descrizione' => 'Descrizione base',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->call('duplica', $prodotto->id);

        $this->assertDatabaseCount('prodotti', 2);

        $duplicato = Prodotto::query()->whereKeyNot($prodotto->id)->firstOrFail();
        $this->assertStringStartsWith('PRD-ORIG-01-COPY', $duplicato->codice);
        $this->assertSame('Descrizione base', $duplicato->descrizione);
        $this->assertSame($prodotto->unita_misura->value, $duplicato->unita_misura->value);
    }

    public function test_duplicate_redirects_to_edit_page(): void
    {
        $prodotto = Prodotto::factory()->create([
            'codice' => 'PRD-REDIR-01',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->call('duplica', $prodotto->id);

        $duplicato = Prodotto::query()->where('codice', 'like', 'PRD-REDIR-01-COPY%')->firstOrFail();
        $component->assertRedirect(route('prodotti.show', $duplicato->id));
    }

    public function test_duplicate_skips_codes_used_by_soft_deleted_products(): void
    {
        $prodotto = Prodotto::factory()->create([
            'codice' => 'PRD-SOFT-01',
            'nome' => 'Prodotto sorgente',
        ]);

        $trashedDuplicate = Prodotto::factory()->create([
            'codice' => 'PRD-SOFT-01-COPY',
            'nome' => 'Prodotto sorgente (Copia)',
        ]);
        $trashedDuplicate->delete();

        Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->call('duplica', $prodotto->id);

        $this->assertDatabaseHas('prodotti', [
            'codice' => 'PRD-SOFT-01-COPY-2',
            'deleted_at' => null,
        ]);
    }

    public function test_reset_filters(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProdottiTable::class)
            ->set('search', 'test')
            ->set('categoria', 'asse')
            ->set('stato', 'attivi')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('categoria', '')
            ->assertSet('stato', '');
    }
}
