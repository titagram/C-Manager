<?php

namespace Tests\Unit\Services;

use App\Enums\TipoMovimento;
use App\Models\ConsumoMateriale;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\LottoProduzioneMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\Scarto;
use App\Models\User;
use App\Services\InventoryAnomalyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAnomalyServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryAnomalyService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryAnomalyService::class);
        $this->user = User::factory()->create();
    }

    public function test_analyze_period_calculates_inventory_anomaly_kpis(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-ANOM-001',
        ]);

        $lottoProduzione = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-ANOM-001',
            'created_by' => $this->user->id,
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::RETTIFICA_NEGATIVA,
            'quantita' => 5.0,
            'causale' => 'Differenza inventario',
            'causale_codice' => MovimentoMagazzino::REASON_CODE_SUSPECTED_SHORTAGE,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);
        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::RETTIFICA_NEGATIVA,
            'quantita' => 2.0,
            'causale' => 'Errore conteggio',
            'causale_codice' => MovimentoMagazzino::REASON_CODE_COUNT_MISMATCH,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        LottoProduzioneMateriale::factory()->create([
            'lotto_produzione_id' => $lottoProduzione->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'prodotto_id' => $prodotto->id,
            'volume_scarto_mc' => 1.500000,
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lottoProduzione->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'volume_mc' => 0.400000,
        ]);

        ConsumoMateriale::query()->create([
            'lotto_produzione_id' => $lottoProduzione->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'quantita' => 1.0,
            'note' => 'Consumo senza movimento collegato',
        ]);

        $report = $this->service->analyzePeriod(now()->subDay(), now()->addDay());

        $this->assertSame(2, $report['kpis']['rettifiche_negative_count']);
        $this->assertEqualsWithDelta(7.0, (float) $report['kpis']['rettifiche_negative_qty'], 0.0001);
        $this->assertEqualsWithDelta(5.0, (float) $report['kpis']['rettifiche_sospetto_ammanco_qty'], 0.0001);
        $this->assertSame(0, $report['kpis']['rettifiche_negative_without_reason_code_count']);
        $this->assertSame(1, $report['kpis']['scarti_mismatch_lotti_count']);
        $this->assertEqualsWithDelta(1.1, (float) $report['kpis']['scarti_mismatch_delta_mc'], 0.0001);
        $this->assertSame(1, $report['kpis']['consumi_senza_movimento_count']);

        $this->assertNotEmpty($report['top_lotti_rischio']);
        $this->assertSame('LP-ANOM-001', $report['top_lotti_rischio'][0]['codice_lotto']);

        $this->assertNotEmpty($report['top_materiali_rettifiche']);
        $this->assertSame('LM-ANOM-001', $report['top_materiali_rettifiche'][0]['codice_lotto']);
    }
}

