<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Costruzioni\ComponentiManager;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ComponentiManagerDimensionamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_salvataggio_manuale_imposta_calcolato_false(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'custom',
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->set('nome', 'Pannello frontale')
            ->set('tipo_dimensionamento', 'MANUALE')
            ->set('formula_quantita', '1')
            ->call('save');

        $this->assertDatabaseHas('componenti_costruzione', [
            'costruzione_id' => $costruzione->id,
            'nome' => 'Pannello frontale',
            'tipo_dimensionamento' => 'MANUALE',
            'calcolato' => false,
        ]);
    }

    public function test_modifica_calcolato_imposta_calcolato_true(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'custom',
        ]);

        $componente = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->call('edit', $componente->id)
            ->set('tipo_dimensionamento', 'CALCOLATO')
            ->set('formula_quantita', '2')
            ->call('save');

        $this->assertDatabaseHas('componenti_costruzione', [
            'id' => $componente->id,
            'tipo_dimensionamento' => 'CALCOLATO',
            'calcolato' => true,
        ]);
    }

    public function test_cassa_calcolato_requires_formula_lunghezza_and_larghezza(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->set('nome', 'Componente non valido per cassa')
            ->set('tipo_dimensionamento', 'CALCOLATO')
            ->set('formula_lunghezza', 'L')
            ->set('formula_larghezza', '')
            ->set('formula_quantita', '1')
            ->call('save')
            ->assertHasErrors(['formula_larghezza']);

        $this->assertDatabaseMissing('componenti_costruzione', [
            'costruzione_id' => $costruzione->id,
            'nome' => 'Componente non valido per cassa',
        ]);
    }

    public function test_cassa_manual_component_remains_allowed_without_dimension_formulas(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->set('nome', 'Chiodi')
            ->set('tipo_dimensionamento', 'MANUALE')
            ->set('formula_lunghezza', '')
            ->set('formula_larghezza', '')
            ->set('formula_quantita', '200')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('componenti_costruzione', [
            'costruzione_id' => $costruzione->id,
            'nome' => 'Chiodi',
            'tipo_dimensionamento' => 'MANUALE',
            'calcolato' => false,
        ]);
    }

    public function test_cassa_calculated_component_rejects_hardware_name(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->set('nome', 'Chiodi zincati')
            ->set('tipo_dimensionamento', 'CALCOLATO')
            ->set('formula_lunghezza', 'L')
            ->set('formula_larghezza', 'W')
            ->set('formula_quantita', '200')
            ->call('save')
            ->assertHasErrors(['nome']);

        $this->assertDatabaseMissing('componenti_costruzione', [
            'costruzione_id' => $costruzione->id,
            'nome' => 'Chiodi zincati',
        ]);
    }

    public function test_gabbia_cannot_convert_last_calcolato_component_to_manual(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'gabbia',
        ]);

        $componente = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Montanti',
            'tipo_dimensionamento' => 'CALCOLATO',
            'calcolato' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->call('edit', $componente->id)
            ->set('tipo_dimensionamento', 'MANUALE')
            ->call('save')
            ->assertHasErrors(['tipo_dimensionamento']);

        $this->assertDatabaseHas('componenti_costruzione', [
            'id' => $componente->id,
            'tipo_dimensionamento' => 'CALCOLATO',
            'calcolato' => true,
        ]);
    }

    public function test_gabbia_cannot_delete_last_calcolato_component(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'gabbia',
        ]);

        $componente = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Traverse',
            'tipo_dimensionamento' => 'CALCOLATO',
            'calcolato' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->call('delete', $componente->id)
            ->assertSee('deve rimanere almeno un componente CALCOLATO');

        $this->assertDatabaseHas('componenti_costruzione', [
            'id' => $componente->id,
        ]);
    }

    public function test_gabbia_allows_deleting_a_calculated_component_when_another_exists(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'gabbia',
        ]);

        $first = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Montanti',
            'tipo_dimensionamento' => 'CALCOLATO',
            'calcolato' => true,
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Traverse',
            'tipo_dimensionamento' => 'CALCOLATO',
            'calcolato' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->call('delete', $first->id);

        $this->assertDatabaseMissing('componenti_costruzione', [
            'id' => $first->id,
        ]);
    }

    public function test_gabbia_rinforzi_component_must_be_manual(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'gabbia',
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->set('nome', 'Rinforzi Diagonali')
            ->set('tipo_dimensionamento', 'CALCOLATO')
            ->set('formula_lunghezza', 'L')
            ->set('formula_larghezza', 'W')
            ->set('formula_quantita', '2')
            ->call('save')
            ->assertHasErrors(['tipo_dimensionamento']);

        $this->assertDatabaseMissing('componenti_costruzione', [
            'costruzione_id' => $costruzione->id,
            'nome' => 'Rinforzi Diagonali',
        ]);
    }

    public function test_gabbia_rinforzi_component_can_be_manual(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'gabbia',
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->set('nome', 'Rinforzi Diagonali')
            ->set('tipo_dimensionamento', 'MANUALE')
            ->set('formula_lunghezza', '')
            ->set('formula_larghezza', '')
            ->set('formula_quantita', '2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('componenti_costruzione', [
            'costruzione_id' => $costruzione->id,
            'nome' => 'Rinforzi Diagonali',
            'tipo_dimensionamento' => 'MANUALE',
            'calcolato' => false,
        ]);
    }

    public function test_calculated_component_can_persist_internal_and_rotation_flags(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->set('nome', 'Parete corta interna')
            ->set('tipo_dimensionamento', 'CALCOLATO')
            ->set('formula_lunghezza', 'W - (2 * T)')
            ->set('formula_larghezza', 'H')
            ->set('formula_quantita', '2')
            ->set('is_internal', true)
            ->set('allow_rotation', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('componenti_costruzione', [
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete corta interna',
            'is_internal' => true,
            'allow_rotation' => true,
        ]);
    }

    public function test_edit_component_updates_internal_and_rotation_flags(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
        ]);

        $componente = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Fondo',
            'tipo_dimensionamento' => 'CALCOLATO',
            'calcolato' => true,
            'is_internal' => false,
            'allow_rotation' => false,
        ]);

        Livewire::actingAs($user)
            ->test(ComponentiManager::class, ['costruzione' => $costruzione])
            ->call('edit', $componente->id)
            ->set('is_internal', true)
            ->set('allow_rotation', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('componenti_costruzione', [
            'id' => $componente->id,
            'is_internal' => true,
            'allow_rotation' => true,
        ]);
    }
}
