<?php

namespace Tests\Feature\Livewire;

use App\Enums\UserRole;
use App\Livewire\Tables\CostruzioniTable;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CostruzioniTableDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplica_costruzione_con_componenti(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $costruzione = Costruzione::factory()->create([
            'nome' => 'Cassa Export',
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Componente interno',
            'is_internal' => true,
            'allow_rotation' => false,
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Componente ruotabile',
            'is_internal' => false,
            'allow_rotation' => true,
        ]);

        Livewire::actingAs($user)
            ->test(CostruzioniTable::class)
            ->call('duplica', $costruzione->id);

        $duplicata = Costruzione::query()
            ->where('nome', 'like', 'Cassa Export (copia)%')
            ->latest('id')
            ->first();

        $this->assertNotNull($duplicata);
        $this->assertSame($costruzione->categoria, $duplicata->categoria);
        $this->assertSame(2, $duplicata->componenti()->count());
        $this->assertDatabaseHas('componenti_costruzione', [
            'costruzione_id' => $duplicata->id,
            'nome' => 'Componente interno',
            'is_internal' => true,
            'allow_rotation' => false,
        ]);
        $this->assertDatabaseHas('componenti_costruzione', [
            'costruzione_id' => $duplicata->id,
            'nome' => 'Componente ruotabile',
            'is_internal' => false,
            'allow_rotation' => true,
        ]);
    }
}
