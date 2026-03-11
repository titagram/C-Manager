<?php

namespace Tests\Feature\Commands;

use App\Models\LottoProduzione;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillLottoMaterialVolumesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_missing_net_and_scrap_volumes_from_bin_payload(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'optimizer_result' => [
                'bin_length' => 2300,
                'bins' => [
                    [
                        'capacity' => 2300,
                        'waste' => 300,
                        'items' => [
                            ['description' => 'Pezzo 1', 'length' => 1000, 'width' => 250],
                            ['description' => 'Pezzo 2', 'length' => 1000, 'width' => 250],
                        ],
                        'volume_lordo_mc' => 0.011500,
                        'volume_netto_mc' => 0.010000,
                        'volume_scarto_mc' => 0.001500,
                    ],
                ],
            ],
        ]);

        $row = $lotto->materialiUsati()->create([
            'descrizione' => 'Asse 1',
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.011500,
            'volume_netto_mc' => null,
            'volume_scarto_mc' => null,
            'pezzi_per_asse' => 2,
            'assi_necessarie' => 1,
            'scarto_per_asse_mm' => 300,
            'scarto_totale_mm' => 300,
            'scarto_percentuale' => 13.04,
            'ordine' => 0,
        ]);

        $this->artisan("production:backfill-lotto-material-volumes --lotto-id={$lotto->id}")
            ->expectsOutputToContain('Analizzati: 1')
            ->expectsOutputToContain('Righe aggiornate: 1')
            ->assertExitCode(0);

        $row->refresh();
        $this->assertEqualsWithDelta(0.010000, (float) $row->volume_netto_mc, 0.000001);
        $this->assertEqualsWithDelta(0.001500, (float) $row->volume_scarto_mc, 0.000001);
    }

    public function test_it_does_not_persist_changes_in_dry_run_mode(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'optimizer_result' => [
                'bin_length' => 2300,
                'bins' => [
                    [
                        'capacity' => 2300,
                        'waste' => 300,
                        'items' => [],
                        'volume_lordo_mc' => 0.011500,
                        'volume_netto_mc' => 0.010000,
                        'volume_scarto_mc' => 0.001500,
                    ],
                ],
            ],
        ]);

        $row = $lotto->materialiUsati()->create([
            'descrizione' => 'Asse 1',
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.011500,
            'volume_netto_mc' => null,
            'volume_scarto_mc' => null,
            'pezzi_per_asse' => 0,
            'assi_necessarie' => 1,
            'ordine' => 0,
        ]);

        $this->artisan("production:backfill-lotto-material-volumes --lotto-id={$lotto->id} --dry-run")
            ->expectsOutputToContain('Modalita: dry-run')
            ->expectsOutputToContain('Righe aggiornate: 1')
            ->assertExitCode(0);

        $row->refresh();
        $this->assertNull($row->volume_netto_mc);
        $this->assertNull($row->volume_scarto_mc);
    }

    public function test_it_can_derive_volumes_from_waste_when_bin_totals_are_missing(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'optimizer_result' => [
                'bin_length' => 2300,
                'bins' => [
                    [
                        'capacity' => 2300,
                        'waste' => 300,
                        'items' => [
                            ['description' => 'Legacy piece', 'length' => 2000],
                        ],
                    ],
                ],
            ],
        ]);

        $row = $lotto->materialiUsati()->create([
            'descrizione' => 'Asse legacy',
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.011500,
            'volume_netto_mc' => null,
            'volume_scarto_mc' => null,
            'pezzi_per_asse' => 1,
            'assi_necessarie' => 1,
            'scarto_per_asse_mm' => 300,
            'scarto_totale_mm' => 300,
            'ordine' => 0,
        ]);

        $this->artisan("production:backfill-lotto-material-volumes --lotto-id={$lotto->id}")
            ->expectsOutputToContain('Righe aggiornate: 1')
            ->assertExitCode(0);

        $row->refresh();
        $this->assertEqualsWithDelta(0.010000, (float) $row->volume_netto_mc, 0.000001);
        $this->assertEqualsWithDelta(0.001500, (float) $row->volume_scarto_mc, 0.000001);
    }

    public function test_it_backfills_rows_without_optimizer_result_using_row_fields_fallback(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'optimizer_result' => null,
        ]);

        $row = $lotto->materialiUsati()->create([
            'descrizione' => 'Asse storico senza optimizer',
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.011500,
            'volume_netto_mc' => null,
            'volume_scarto_mc' => null,
            'pezzi_per_asse' => 1,
            'assi_necessarie' => 1,
            'scarto_per_asse_mm' => 300,
            'scarto_totale_mm' => 300,
            'scarto_percentuale' => 13.04,
            'ordine' => 0,
        ]);

        $this->artisan("production:backfill-lotto-material-volumes --lotto-id={$lotto->id}")
            ->expectsOutputToContain('Analizzati: 1')
            ->expectsOutputToContain('Righe aggiornate: 1')
            ->expectsOutputToContain('fallback_row_fields=1')
            ->assertExitCode(0);

        $row->refresh();
        $this->assertEqualsWithDelta(0.010000, (float) $row->volume_netto_mc, 0.000001);
        $this->assertEqualsWithDelta(0.001500, (float) $row->volume_scarto_mc, 0.000001);
    }

    public function test_it_backfills_extra_rows_when_rows_bins_are_mismatched(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'optimizer_result' => [
                'bin_length' => 2300,
                'bins' => [
                    [
                        'capacity' => 2300,
                        'waste' => 300,
                        'items' => [],
                        'volume_lordo_mc' => 0.011500,
                        'volume_netto_mc' => 0.010000,
                        'volume_scarto_mc' => 0.001500,
                    ],
                ],
            ],
        ]);

        $lotto->materialiUsati()->create([
            'descrizione' => 'Asse da bin',
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.011500,
            'volume_netto_mc' => null,
            'volume_scarto_mc' => null,
            'pezzi_per_asse' => 1,
            'assi_necessarie' => 1,
            'scarto_per_asse_mm' => 300,
            'scarto_totale_mm' => 300,
            'ordine' => 0,
        ]);

        $extraRow = $lotto->materialiUsati()->create([
            'descrizione' => 'Asse extra legacy',
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.011500,
            'volume_netto_mc' => null,
            'volume_scarto_mc' => null,
            'pezzi_per_asse' => 1,
            'assi_necessarie' => 1,
            'scarto_per_asse_mm' => 300,
            'scarto_totale_mm' => 300,
            'ordine' => 1,
        ]);

        $this->artisan("production:backfill-lotto-material-volumes --lotto-id={$lotto->id}")
            ->expectsOutputToContain('Righe aggiornate: 2')
            ->expectsOutputToContain('mismatch rows/bins (2/1)')
            ->assertExitCode(0);

        $extraRow->refresh();
        $this->assertEqualsWithDelta(0.010000, (float) $extraRow->volume_netto_mc, 0.000001);
        $this->assertEqualsWithDelta(0.001500, (float) $extraRow->volume_scarto_mc, 0.000001);
    }

    public function test_it_includes_soft_deleted_lotti_in_backfill_scope(): void
    {
        $lotto = LottoProduzione::factory()->bozza()->create([
            'optimizer_result' => null,
        ]);

        $row = $lotto->materialiUsati()->create([
            'descrizione' => 'Asse su lotto soft-deleted',
            'lunghezza_mm' => 2300,
            'larghezza_mm' => 250,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.011500,
            'volume_netto_mc' => null,
            'volume_scarto_mc' => null,
            'pezzi_per_asse' => 1,
            'assi_necessarie' => 1,
            'scarto_totale_mm' => 300,
            'scarto_per_asse_mm' => 300,
            'scarto_percentuale' => 13.04,
            'ordine' => 0,
        ]);

        $lotto->delete();

        $this->artisan('production:backfill-lotto-material-volumes --limit=500')
            ->expectsOutputToContain('Analizzati: 1')
            ->expectsOutputToContain('Righe aggiornate: 1')
            ->assertExitCode(0);

        $row->refresh();
        $this->assertEqualsWithDelta(0.010000, (float) $row->volume_netto_mc, 0.000001);
        $this->assertEqualsWithDelta(0.001500, (float) $row->volume_scarto_mc, 0.000001);
    }
}
