<?php

namespace Tests\Feature\Commands;

use App\Services\InventoryAnomalyService;
use Mockery;
use Tests\TestCase;

class InventoryAnomalyReportCommandTest extends TestCase
{
    public function test_command_outputs_inventory_anomaly_summary(): void
    {
        $service = Mockery::mock(InventoryAnomalyService::class);
        $service->shouldReceive('analyzeLastDays')
            ->once()
            ->with(7)
            ->andReturn([
                'period' => [
                    'from' => '2026-03-01',
                    'to' => '2026-03-07',
                ],
                'kpis' => [
                    'rettifiche_negative_count' => 3,
                    'rettifiche_negative_qty' => 9.5,
                    'rettifiche_negative_without_reason_code_count' => 0,
                    'rettifiche_negative_reason_coverage_percent' => 100.0,
                    'rettifiche_sospetto_ammanco_qty' => 4.0,
                    'scarti_mismatch_lotti_count' => 2,
                    'scarti_mismatch_delta_mc' => 0.45,
                    'consumi_senza_movimento_count' => 1,
                ],
                'top_lotti_rischio' => [
                    [
                        'lotto_produzione_id' => 11,
                        'codice_lotto' => 'LP-ANOM-011',
                        'volume_scarto_teorico_mc' => 0.60,
                        'volume_scarto_registrato_mc' => 0.20,
                        'delta_scarto_mc' => 0.40,
                    ],
                ],
                'top_materiali_rettifiche' => [
                    [
                        'lotto_materiale_id' => 21,
                        'codice_lotto' => 'LM-ANOM-021',
                        'quantita_rettifiche_negative' => 5.5,
                        'quantita_sospetto_ammanco' => 3.0,
                        'movimenti_count' => 2,
                    ],
                ],
            ]);

        $this->app->instance(InventoryAnomalyService::class, $service);

        $this->artisan('inventory:anomaly-report', ['--days' => 7])
            ->expectsOutputToContain('Report anomalie inventario - ultimi 7 giorni')
            ->expectsOutputToContain('Top lotti a rischio')
            ->expectsOutputToContain('Top lotti materiale per rettifiche negative')
            ->assertExitCode(0);
    }
}

