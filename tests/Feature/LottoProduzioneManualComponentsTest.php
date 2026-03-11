<?php

namespace Tests\Feature;

use App\Livewire\Forms\LottoProduzioneForm;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LottoProduzioneManualComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_salva_componenti_non_calcolati_sul_lotto(): void
    {
        $user = User::factory()->admin()->create();

        $costruzione = Costruzione::factory()->create();

        $componenteManuale = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
            'formula_lunghezza' => null,
            'formula_larghezza' => null,
            'formula_quantita' => '1',
        ]);

        $materiale = Prodotto::factory()->create();
        $preventivo = Preventivo::factory()->create([
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class)
            ->set('prodotto_finale', 'Lotto con componenti manuali')
            ->set('preventivoId', $preventivo->id)
            ->set('costruzione_id', $costruzione->id)
            ->set('componentiManuali.0.componente_costruzione_id', $componenteManuale->id)
            ->set('componentiManuali.0.prodotto_id', $materiale->id)
            ->set('componentiManuali.0.quantita', 4)
            ->set('componentiManuali.0.prezzo_unitario', 12.5)
            ->set('componentiManuali.0.unita_misura', 'pz')
            ->call('save')
            ->assertRedirect(route('lotti.index'));

        $this->assertDatabaseHas('lotto_componenti_manuali', [
            'componente_costruzione_id' => $componenteManuale->id,
            'prodotto_id' => $materiale->id,
            'quantita' => 4,
            'prezzo_unitario' => 12.5,
            'unita_misura' => 'pz',
        ]);
    }
}
