<?php

namespace Tests\Unit\Support;

use App\Enums\StatoConsumoMateriale;
use App\Models\Bom;
use App\Models\Cliente;
use App\Models\ConsumoMateriale;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Models\User;
use App\Support\ProductionFlowStepper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductionFlowStepperTest extends TestCase
{
    use RefreshDatabase;

    public function test_preventivo_bozza_shows_current_on_first_step(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create();

        $payload = app(ProductionFlowStepper::class)->forPreventivo($preventivo);
        $steps = $this->stepsByKey($payload['steps']);

        $this->assertSame('current', $steps->get('preventivo')['status']);
        $this->assertSame('pending', $steps->get('ordine')['status']);
        $this->assertSame('pending', $steps->get('lotto')['status']);
        $this->assertSame('pending', $steps->get('bom')['status']);
        $this->assertSame('pending', $steps->get('magazzino')['status']);
    }

    public function test_full_pipeline_is_marked_completed(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $preventivo = Preventivo::factory()->accettato()->create([
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        $ordine = Ordine::factory()->confermato()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        $lotto = LottoProduzione::factory()->completato()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        Bom::factory()->generatedFromOrder()->create([
            'ordine_id' => $ordine->id,
            'source' => 'ordine',
            'created_by' => $user->id,
        ]);

        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
        ]);

        ConsumoMateriale::create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'quantita' => 1.5,
            'stato' => StatoConsumoMateriale::OPZIONATO,
            'opzionato_at' => now(),
        ]);

        $payload = app(ProductionFlowStepper::class)->forOrdine($ordine);
        $steps = $this->stepsByKey($payload['steps']);

        $this->assertSame('completed', $steps->get('preventivo')['status']);
        $this->assertSame('completed', $steps->get('ordine')['status']);
        $this->assertSame('completed', $steps->get('lotto')['status']);
        $this->assertSame('completed', $steps->get('bom')['status']);
        $this->assertSame('completed', $steps->get('magazzino')['status']);
    }

    public function test_manual_order_skips_preventivo_step(): void
    {
        $ordine = Ordine::factory()->confermato()->create([
            'preventivo_id' => null,
        ]);

        $payload = app(ProductionFlowStepper::class)->forOrdine($ordine);
        $steps = $this->stepsByKey($payload['steps']);

        $this->assertSame('skipped', $steps->get('preventivo')['status']);
        $this->assertSame('completed', $steps->get('ordine')['status']);
        $this->assertSame('current', $steps->get('lotto')['status']);
    }

    public function test_flow_marks_completed_steps_as_fuori_sequenza_when_previous_step_is_pending(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $preventivo = Preventivo::factory()->bozza()->create([
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        $ordine = Ordine::factory()->confermato()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        LottoProduzione::factory()->bozza()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        $payload = app(ProductionFlowStepper::class)->forOrdine($ordine);
        $steps = $this->stepsByKey($payload['steps']);

        $this->assertSame('current', $steps->get('preventivo')['status']);
        $this->assertSame('inconsistent', $steps->get('ordine')['status']);
        $this->assertSame('Fuori sequenza', $steps->get('ordine')['status_label']);
        $this->assertSame('pending', $steps->get('lotto')['status']);
    }

    public function test_placeholder_bozza_does_not_count_as_operational_lotto_step(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create();

        LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'optimizer_result' => null,
            'costruzione_id' => null,
        ]);

        $payload = app(ProductionFlowStepper::class)->forPreventivo($preventivo);
        $steps = $this->stepsByKey($payload['steps']);

        $this->assertSame('pending', $steps->get('lotto')['status']);
        $this->assertStringContainsString('bozza da completare tecnicamente', $steps->get('lotto')['meta']);
    }

    public function test_lotto_context_does_not_inherit_sibling_order_from_same_preventivo(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $preventivo = Preventivo::factory()->accettato()->create([
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        $ordine = Ordine::factory()->pronto()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        $lottoOperativo = LottoProduzione::factory()->completato()->create([
            'ordine_id' => $ordine->id,
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        Bom::factory()->generatedFromOrder()->create([
            'ordine_id' => $ordine->id,
            'lotto_produzione_id' => $lottoOperativo->id,
            'source' => 'ordine',
            'created_by' => $user->id,
        ]);

        $lottoInBozza = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'ordine_id' => null,
            'cliente_id' => $cliente->id,
            'created_by' => $user->id,
        ]);

        $payload = app(ProductionFlowStepper::class)->forLotto($lottoInBozza);
        $steps = $this->stepsByKey($payload['steps']);

        $this->assertSame('completed', $steps->get('preventivo')['status']);
        $this->assertSame('current', $steps->get('ordine')['status']);
        $this->assertStringContainsString('Da generare da preventivo accettato', $steps->get('ordine')['meta']);
        $this->assertSame('pending', $steps->get('lotto')['status']);
        $this->assertStringContainsString('bozza da completare tecnicamente', $steps->get('lotto')['meta']);
        $this->assertSame('pending', $steps->get('bom')['status']);
        $this->assertSame('pending', $steps->get('magazzino')['status']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @return Collection<string, array<string, mixed>>
     */
    private function stepsByKey(array $steps): Collection
    {
        return collect($steps)->keyBy('key');
    }
}
