<?php

namespace Tests\Feature;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Livewire\Forms\LottoProduzioneForm;
use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LottoProduzioneMaterialCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_ferramenta_cannot_be_used_as_materiale_asse_for_optimizer(): void
    {
        $user = User::factory()->admin()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
        ]);

        $ferramenta = Prodotto::factory()->create([
            'categoria' => Categoria::FERRAMENTA,
            'unita_misura' => UnitaMisura::PZ,
            'is_active' => true,
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->set('materiale_id', $ferramenta->id)
            ->set('larghezza_cm', '100')
            ->set('profondita_cm', '50')
            ->set('altezza_cm', '100')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', false)
            ->assertSee('non e compatibile come materiale asse');
    }
}
