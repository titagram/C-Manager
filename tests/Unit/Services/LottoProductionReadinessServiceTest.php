<?php

namespace Tests\Unit\Services;

use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\LottoProductionReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LottoProductionReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lotto_is_ready_when_calculated_and_manual_requirements_are_satisfied(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create();

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
        ]);

        $componenteManuale = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
            'nome' => 'Tasselli',
        ]);

        $lotto = LottoProduzione::factory()->create([
            'created_by' => $user->id,
            'costruzione_id' => $costruzione->id,
        ]);

        $prodotto = Prodotto::factory()->create();

        $lotto->materialiUsati()->create([
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.08,
            'costo_materiale' => 10,
            'prezzo_vendita' => 15,
            'ordine' => 0,
        ]);

        $lotto->componentiManuali()->create([
            'componente_costruzione_id' => $componenteManuale->id,
            'prodotto_id' => $prodotto->id,
            'quantita' => 4,
            'unita_misura' => 'pz',
        ]);

        $result = app(LottoProductionReadinessService::class)->evaluate($lotto);

        $this->assertTrue($result['ready']);
        $this->assertSame([], $result['reasons']);
    }

    public function test_lotto_is_not_ready_when_calculated_components_exist_without_materials(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create();

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
        ]);

        $lotto = LottoProduzione::factory()->create([
            'created_by' => $user->id,
            'costruzione_id' => $costruzione->id,
        ]);

        $result = app(LottoProductionReadinessService::class)->evaluate($lotto);

        $this->assertFalse($result['ready']);
        $this->assertTrue($result['requires_calculated_materials']);
        $this->assertStringContainsString('materiali calcolati', $result['message']);
    }

    public function test_placeholder_bozza_is_not_ready_without_technical_definition(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'costruzione_id' => null,
            'optimizer_result' => null,
        ]);

        $result = app(LottoProductionReadinessService::class)->evaluate($lotto);

        $this->assertFalse($result['ready']);
        $this->assertContains('manca la definizione tecnica del lotto', $result['reasons']);
    }

    public function test_lotto_is_not_ready_when_manual_components_are_missing(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create();

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
            'nome' => 'Listelli',
        ]);

        $lotto = LottoProduzione::factory()->create([
            'created_by' => $user->id,
            'costruzione_id' => $costruzione->id,
        ]);

        $result = app(LottoProductionReadinessService::class)->evaluate($lotto);

        $this->assertFalse($result['ready']);
        $this->assertContains('Listelli', $result['missing_manual_components']);
    }

    public function test_evaluate_order_reports_non_ready_lotti(): void
    {
        $user = User::factory()->create();
        $ordine = Ordine::factory()->create(['created_by' => $user->id]);

        $costruzione = Costruzione::factory()->create();
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
        ]);

        LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'created_by' => $user->id,
            'costruzione_id' => $costruzione->id,
        ]);

        $result = app(LottoProductionReadinessService::class)->evaluateOrder($ordine);

        $this->assertFalse($result['ready']);
        $this->assertSame(1, $result['total_lotti']);
        $this->assertSame(0, $result['lotti_pronti']);
        $this->assertSame(1, $result['lotti_non_pronti']);
        $this->assertCount(1, $result['issues']);
    }

    public function test_lotto_with_multi_material_manual_components_is_ready(): void
    {
        $user = User::factory()->create();
        $costruzione = Costruzione::factory()->create();

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
        ]);

        $manualeA = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
            'nome' => 'Traverso interno',
        ]);

        $manualeB = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
            'nome' => 'Rinforzo laterale',
        ]);

        $lotto = LottoProduzione::factory()->create([
            'created_by' => $user->id,
            'costruzione_id' => $costruzione->id,
        ]);

        $materialePrincipale = Prodotto::factory()->create();
        $materialeManualeA = Prodotto::factory()->create();
        $materialeManualeB = Prodotto::factory()->create();

        $lotto->materialiUsati()->create([
            'prodotto_id' => $materialePrincipale->id,
            'descrizione' => 'Asse principale',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 120,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.096,
            'costo_materiale' => 12,
            'prezzo_vendita' => 20,
            'ordine' => 0,
        ]);

        $lotto->componentiManuali()->create([
            'componente_costruzione_id' => $manualeA->id,
            'prodotto_id' => $materialeManualeA->id,
            'quantita' => 2,
            'unita_misura' => 'pz',
        ]);

        $lotto->componentiManuali()->create([
            'componente_costruzione_id' => $manualeB->id,
            'prodotto_id' => $materialeManualeB->id,
            'quantita' => 3,
            'unita_misura' => 'pz',
        ]);

        $result = app(LottoProductionReadinessService::class)->evaluate($lotto);

        $this->assertTrue($result['ready']);
        $this->assertSame([], $result['missing_manual_components']);
        $this->assertSame([], $result['reasons']);
    }
}
