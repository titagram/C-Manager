<?php

namespace Tests\Feature;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Livewire\Forms\LottoProduzioneForm;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LottoProduzioneCassaExcelProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_cassa_requires_profile_materials_and_uses_them_in_optimizer_bins(): void
    {
        $user = User::factory()->admin()->create();
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'slug' => 'cassa-sp25-test',
            'config' => [
                'optimizer_key' => 'excel_sp25',
            ],
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Fondo',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'W',
            'formula_quantita' => '1',
        ]);

        $base = Prodotto::factory()->create([
            'categoria' => Categoria::MATERIA_PRIMA,
            'unita_misura' => UnitaMisura::MC,
            'is_active' => true,
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 25,
            'prezzo_mc' => 545,
            'costo_unitario' => 400,
        ]);
        $fondo = Prodotto::factory()->create([
            'categoria' => Categoria::MATERIA_PRIMA,
            'unita_misura' => UnitaMisura::MC,
            'is_active' => true,
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 40,
            'prezzo_mc' => 580,
            'costo_unitario' => 420,
        ]);

        $component = Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class)
            ->set('costruzione_id', $costruzione->id)
            ->assertSee('Materiali cassa')
            ->set('primaryMaterialProfiles.base', $base->id)
            ->set('primaryMaterialProfiles.fondo', $fondo->id)
            ->set('larghezza_cm', '80')
            ->set('profondita_cm', '80')
            ->set('altezza_cm', '120')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true);

        $result = $component->get('optimizerResult');

        $this->assertSame('cassa', data_get($result, 'optimizer.name'));
        $this->assertSame('cassasp25', data_get($result, 'trace.variant_routine'));
        $this->assertSame('physical_v2', data_get($result, 'trace.optimizer_mode'));
        $this->assertSame($base->id, data_get($result, 'trace.resolved_material_profiles.base.id'));
        $this->assertSame($fondo->id, data_get($result, 'trace.resolved_material_profiles.fondo.id'));
        $this->assertTrue(
            collect($result['bins'] ?? [])->contains(fn (array $bin): bool => ($bin['source_profile'] ?? null) === 'fondo')
        );
    }

    public function test_saving_lotto_persists_primary_material_profiles_and_source_profile_rows(): void
    {
        $user = User::factory()->admin()->create();
        $lotto = LottoProduzione::factory()->bozza()->create([
            'created_by' => $user->id,
        ]);
        $costruzione = Costruzione::factory()->create([
            'categoria' => 'cassa',
            'slug' => 'cassa-sp25-save-test',
            'config' => [
                'optimizer_key' => 'excel_sp25',
            ],
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Fondo',
            'formula_lunghezza' => 'L',
            'formula_larghezza' => 'W',
            'formula_quantita' => '1',
        ]);

        $base = Prodotto::factory()->create([
            'categoria' => Categoria::MATERIA_PRIMA,
            'unita_misura' => UnitaMisura::MC,
            'is_active' => true,
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 25,
            'prezzo_mc' => 545,
            'costo_unitario' => 400,
        ]);
        $fondo = Prodotto::factory()->create([
            'categoria' => Categoria::MATERIA_PRIMA,
            'unita_misura' => UnitaMisura::MC,
            'is_active' => true,
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 40,
            'prezzo_mc' => 580,
            'costo_unitario' => 420,
        ]);

        Livewire::actingAs($user)
            ->test(LottoProduzioneForm::class, ['lotto' => $lotto])
            ->set('costruzione_id', $costruzione->id)
            ->set('primaryMaterialProfiles.base', $base->id)
            ->set('primaryMaterialProfiles.fondo', $fondo->id)
            ->set('larghezza_cm', '80')
            ->set('profondita_cm', '80')
            ->set('altezza_cm', '120')
            ->set('numero_pezzi', '1')
            ->call('calcolaMateriali')
            ->assertSet('showOptimizerResults', true)
            ->call('save');

        $fresh = $lotto->fresh(['primaryMaterialProfiles', 'materialiUsati']);

        $this->assertNotNull($fresh);
        $this->assertSame(
            [$base->id, $fondo->id],
            $fresh->primaryMaterialProfiles->sortBy('ordine')->pluck('prodotto_id')->values()->all()
        );
        $this->assertTrue(
            $fresh->materialiUsati->contains(fn ($row): bool => $row->source_profile === 'fondo')
        );
    }
}
