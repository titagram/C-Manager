<?php

namespace Tests\Unit\Services;

use App\Enums\StatoLottoProduzione;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimento;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\FitokReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FitokReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private FitokReportService $service;
    private Cliente $cliente;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FitokReportService();
        $this->cliente = Cliente::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_get_registro_includes_destination_production_lot_fields(): void
    {
        $prodottoFitok = Prodotto::factory()->create([
            'soggetto_fitok' => true,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoFitok->id,
            'codice_lotto' => 'LM-FITOK-TRACE-001',
        ]);

        $lottoProduzione = LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'codice_lotto' => 'LP-FITOK-TRACE-001',
            'created_by' => $this->user->id,
        ]);

        $documento = Documento::query()->create([
            'tipo' => TipoDocumento::DDT_USCITA,
            'numero' => 'DDT-TRACE-001',
            'data' => now()->toDateString(),
            'cliente_id' => $this->cliente->id,
            'created_by' => $this->user->id,
        ]);

        $movimento = MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 2.5,
            'documento_id' => $documento->id,
            'lotto_produzione_id' => $lottoProduzione->id,
            'causale' => 'Scarico test tracciabilita',
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        $registro = $this->service->getRegistro(now()->subDay(), now()->addDay());
        $riga = $registro->firstWhere('id', $movimento->id);

        $this->assertNotNull($riga);
        $this->assertSame($lottoProduzione->id, (int) $riga->lotto_produzione_id);
        $this->assertSame('LP-FITOK-TRACE-001', (string) $riga->lotto_produzione_codice);
        $this->assertSame('DDT-TRACE-001', (string) $riga->documento_numero);
        $this->assertSame('LM-FITOK-TRACE-001', (string) $riga->codice_lotto);
    }

    public function test_get_lotti_produzione_fitok(): void
    {
        $lottoCompliant = LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'stato' => StatoLottoProduzione::COMPLETATO,
            'fitok_percentuale' => 100.00,
            'data_fine' => now()->subDay(),
            'fitok_calcolato_at' => now()->subDay(),
        ]);
        $lottoPartial = LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'stato' => StatoLottoProduzione::COMPLETATO,
            'fitok_percentuale' => 60.00,
            'data_fine' => now()->subDay(),
            'fitok_calcolato_at' => now()->subDay(),
        ]);
        $lottoNone = LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'stato' => StatoLottoProduzione::COMPLETATO,
            'fitok_percentuale' => 0,
            'data_fine' => now()->subDay(),
            'fitok_calcolato_at' => now()->subDay(),
        ]);

        $report = $this->service->getLottiProduzioneFitok(now()->subMonth(), now());

        $this->assertCount(3, $report);
        $this->assertEquals(100.00, $report->firstWhere('id', $lottoCompliant->id)->fitok_percentuale);
        $this->assertEquals(60.00, $report->firstWhere('id', $lottoPartial->id)->fitok_percentuale);
    }

    public function test_get_riepilogo_fitok_produzione(): void
    {
        LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'stato' => StatoLottoProduzione::COMPLETATO,
            'fitok_percentuale' => 100.00,
            'fitok_volume_mc' => 5.0,
            'non_fitok_volume_mc' => 0,
            'data_fine' => now()->subDay(),
            'fitok_calcolato_at' => now()->subDay(),
        ]);
        LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'stato' => StatoLottoProduzione::COMPLETATO,
            'fitok_percentuale' => 50.00,
            'fitok_volume_mc' => 2.5,
            'non_fitok_volume_mc' => 2.5,
            'data_fine' => now()->subDay(),
            'fitok_calcolato_at' => now()->subDay(),
        ]);

        $riepilogo = $this->service->getRiepilogoFitokProduzione(now()->subMonth(), now());

        $this->assertEquals(2, $riepilogo['totale_lotti']);
        $this->assertEquals(1, $riepilogo['lotti_100_fitok']);
        $this->assertEquals(1, $riepilogo['lotti_parziali']);
        $this->assertEquals(7.5, $riepilogo['volume_fitok_totale']);
        $this->assertEquals(2.5, $riepilogo['volume_non_fitok_totale']);
    }

    public function test_riepilogo_marks_mixed_lotto_as_non_certifiable(): void
    {
        LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'stato' => StatoLottoProduzione::COMPLETATO,
            'fitok_percentuale' => 100.00,
            'fitok_volume_mc' => 3.0,
            'non_fitok_volume_mc' => 0,
            'data_fine' => now()->subDay(),
            'fitok_calcolato_at' => now()->subDay(),
        ]);

        LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'stato' => StatoLottoProduzione::COMPLETATO,
            'fitok_percentuale' => 50.00,
            'fitok_volume_mc' => 1.5,
            'non_fitok_volume_mc' => 1.5,
            'data_fine' => now()->subDay(),
            'fitok_calcolato_at' => now()->subDay(),
        ]);

        LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'stato' => StatoLottoProduzione::COMPLETATO,
            'fitok_percentuale' => 0.00,
            'fitok_volume_mc' => 0,
            'non_fitok_volume_mc' => 2.0,
            'data_fine' => now()->subDay(),
            'fitok_calcolato_at' => now()->subDay(),
        ]);

        $riepilogo = $this->service->getRiepilogoFitokProduzione(now()->subMonth(), now());

        $this->assertEquals(1, $riepilogo['lotti_certificabili_fitok']);
        $this->assertEquals(2, $riepilogo['lotti_non_certificabili_fitok']);
        $this->assertEquals(1, $riepilogo['lotti_parziali']);
        $this->assertEquals(1, $riepilogo['lotti_non_fitok']);
    }

    public function test_get_fitok_destination_map_links_material_load_to_production_lot(): void
    {
        $prodottoFitok = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-MAP-001',
            'nome' => 'Abete Map',
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoFitok->id,
            'codice_lotto' => 'LM-MAP-001',
        ]);

        $lottoProduzione = LottoProduzione::factory()->create([
            'cliente_id' => $this->cliente->id,
            'codice_lotto' => 'LP-MAP-001',
            'fitok_percentuale' => 40.00,
            'created_by' => $this->user->id,
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lottoProduzione->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1.25,
            'causale' => 'Allocazione produzione',
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lottoProduzione->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 0.75,
            'causale' => 'Allocazione produzione',
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        $mappa = $this->service->getFitokDestinationMap(now()->subDay(), now()->addDay());

        $this->assertCount(1, $mappa);
        $row = $mappa->first();
        $this->assertSame('LM-MAP-001', $row['lotto_carico_codice']);
        $this->assertSame('LP-MAP-001', $row['lotto_produzione_codice']);
        $this->assertSame('PF-MAP-001', $row['prodotto_codice']);
        $this->assertSame('Misto (non certificabile FITOK)', $row['stato_certificazione_uscita']);
        $this->assertEqualsWithDelta(2.0, (float) $row['quantita_destinata'], 0.0001);
        $this->assertSame(2, $row['movimenti_count']);
    }

    public function test_get_lotti_in_scadenza_uses_configured_validita_per_trattamento(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-16'));
        try {
            config()->set('fitok.validita_default_giorni', 365);
            config()->set('fitok.validita_trattamenti', [
                'HT' => 365,
                'MB' => 180,
            ]);

            $prodottoFitok = Prodotto::factory()->create(['soggetto_fitok' => true]);
            $prodottoNonFitok = Prodotto::factory()->create(['soggetto_fitok' => false]);

            LottoMateriale::factory()->create([
                'codice_lotto' => 'LOT-HT-SOON',
                'prodotto_id' => $prodottoFitok->id,
                'fitok_certificato' => 'FITOK-HT-001',
                'fitok_tipo_trattamento' => 'HT',
                'fitok_data_trattamento' => now()->subDays(340),
            ]);

            LottoMateriale::factory()->create([
                'codice_lotto' => 'LOT-MB-SOON',
                'prodotto_id' => $prodottoFitok->id,
                'fitok_certificato' => 'FITOK-MB-001',
                'fitok_tipo_trattamento' => 'MB',
                'fitok_data_trattamento' => now()->subDays(160),
            ]);

            LottoMateriale::factory()->create([
                'codice_lotto' => 'LOT-HT-FAR',
                'prodotto_id' => $prodottoFitok->id,
                'fitok_certificato' => 'FITOK-HT-002',
                'fitok_tipo_trattamento' => 'HT',
                'fitok_data_trattamento' => now()->subDays(300),
            ]);

            LottoMateriale::factory()->create([
                'codice_lotto' => 'LOT-HT-EXPIRED',
                'prodotto_id' => $prodottoFitok->id,
                'fitok_certificato' => 'FITOK-HT-003',
                'fitok_tipo_trattamento' => 'HT',
                'fitok_data_trattamento' => now()->subDays(390),
            ]);

            LottoMateriale::factory()->create([
                'codice_lotto' => 'LOT-NON-FITOK',
                'prodotto_id' => $prodottoNonFitok->id,
                'fitok_certificato' => 'FITOK-NF-001',
                'fitok_tipo_trattamento' => 'HT',
                'fitok_data_trattamento' => now()->subDays(340),
            ]);

            LottoMateriale::factory()->create([
                'codice_lotto' => 'LOT-NO-CERT',
                'prodotto_id' => $prodottoFitok->id,
                'fitok_certificato' => null,
                'fitok_tipo_trattamento' => 'HT',
                'fitok_data_trattamento' => now()->subDays(340),
            ]);

            $lotti = $this->service->getLottiInScadenza(30);
            $codici = $lotti->pluck('codice_lotto')->values()->all();

            $this->assertSame(
                ['LOT-HT-EXPIRED', 'LOT-MB-SOON', 'LOT-HT-SOON'],
                $codici
            );

            $mbSoon = $lotti->firstWhere('codice_lotto', 'LOT-MB-SOON');
            $this->assertNotNull($mbSoon);
            $this->assertSame(180, (int) $mbSoon->fitok_validita_giorni);
            $this->assertSame(20, (int) $mbSoon->fitok_giorni_alla_scadenza);
            $this->assertSame('2026-03-08', (string) $mbSoon->fitok_data_scadenza);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_get_lotti_in_scadenza_uses_default_validita_for_unknown_treatment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-16'));
        try {
            config()->set('fitok.validita_default_giorni', 200);
            config()->set('fitok.validita_trattamenti', [
                'HT' => 365,
            ]);

            $prodottoFitok = Prodotto::factory()->create(['soggetto_fitok' => true]);

            LottoMateriale::factory()->create([
                'codice_lotto' => 'LOT-UNKNOWN-TREATMENT',
                'prodotto_id' => $prodottoFitok->id,
                'fitok_certificato' => 'FITOK-UNK-001',
                'fitok_tipo_trattamento' => 'XYZ',
                'fitok_data_trattamento' => now()->subDays(185),
            ]);

            $lotti = $this->service->getLottiInScadenza(30);
            $lotto = $lotti->firstWhere('codice_lotto', 'LOT-UNKNOWN-TREATMENT');

            $this->assertNotNull($lotto);
            $this->assertSame(200, (int) $lotto->fitok_validita_giorni);
            $this->assertSame(15, (int) $lotto->fitok_giorni_alla_scadenza);
        } finally {
            Carbon::setTestNow();
        }
    }
}
