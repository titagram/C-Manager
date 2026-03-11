<?php

namespace Tests\Unit\Services;

use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\StatoPreventivo;
use App\Enums\TipoMovimento;
use App\Enums\TipoRigaPreventivo;
use App\Enums\UnitaMisura;
use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\OrderProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProductionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_avvia_produzione_avvia_tutti_i_lotti_genera_bom_e_consumi_opzionati(): void
    {
        $user = User::factory()->create();

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        $ordine = Ordine::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'stato' => StatoOrdine::CONFERMATO,
            'created_by' => $user->id,
        ]);

        $prodottoLotto = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC,
        ]);

        $prodottoSfuso = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoLotto->id,
            'quantita_iniziale' => 10,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 10,
            'data_movimento' => now(),
            'created_by' => $user->id,
        ]);

        $lottoA = LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::CONFERMATO,
            'created_by' => $user->id,
        ]);

        $lottoB = LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::CONFERMATO,
            'created_by' => $user->id,
        ]);

        $lottoA->materialiUsati()->create([
            'prodotto_id' => $prodottoLotto->id,
            'descrizione' => 'Asse A',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 2.0,
            'costo_materiale' => 100,
            'prezzo_vendita' => 150,
            'ordine' => 0,
        ]);

        $lottoB->materialiUsati()->create([
            'prodotto_id' => $prodottoLotto->id,
            'descrizione' => 'Asse B',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 1.0,
            'costo_materiale' => 50,
            'prezzo_vendita' => 80,
            'ordine' => 0,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'tipo_riga' => TipoRigaPreventivo::SFUSO,
            'include_in_bom' => true,
            'prodotto_id' => $prodottoSfuso->id,
            'descrizione' => 'Materiale sfuso',
            'materiale_lordo' => 1.2,
            'volume_mc' => 1.2,
            'quantita' => 1,
        ]);

        $service = app(OrderProductionService::class);
        $result = $service->avviaProduzione($ordine, $user);

        $ordine->refresh();
        $lottoA->refresh();
        $lottoB->refresh();

        $this->assertSame(StatoOrdine::IN_PRODUZIONE, $ordine->stato);
        $this->assertSame(StatoLottoProduzione::IN_LAVORAZIONE, $lottoA->stato);
        $this->assertSame(StatoLottoProduzione::IN_LAVORAZIONE, $lottoB->stato);

        $this->assertEquals(2, $result['lotti_avviati']);
        $this->assertDatabaseHas('bom', [
            'id' => $result['bom']->id,
            'ordine_id' => $ordine->id,
            'source' => 'ordine',
        ]);
        $this->assertStringStartsWith('Distinta materiali ordine ', $result['bom']->nome);
        $this->assertStringNotContainsString('Lista della spesa', (string) $result['bom']->note);

        $quantitaProdottoLotto = (float) $result['bom']->righe()
            ->where('prodotto_id', $prodottoLotto->id)
            ->sum('quantita');

        $quantitaProdottoSfuso = (float) $result['bom']->righe()
            ->where('prodotto_id', $prodottoSfuso->id)
            ->sum('quantita');

        $this->assertEqualsWithDelta(3.0, $quantitaProdottoLotto, 0.0001);
        $this->assertEqualsWithDelta(1.2, $quantitaProdottoSfuso, 0.0001);

        $rigaAggregata = $result['bom']->righe()
            ->where('prodotto_id', $prodottoLotto->id)
            ->first();
        $this->assertNotNull($rigaAggregata);
        $this->assertStringContainsString($lottoA->codice_lotto, (string) $rigaAggregata->note);
        $this->assertStringContainsString($lottoB->codice_lotto, (string) $rigaAggregata->note);

        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lottoA->id,
            'stato' => 'opzionato',
        ]);

        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lottoB->id,
            'stato' => 'opzionato',
        ]);
    }

    public function test_componenti_manuali_vengono_inclusi_nella_bom_generata(): void
    {
        $user = User::factory()->create();

        $ordine = Ordine::factory()->create([
            'stato' => StatoOrdine::CONFERMATO,
            'created_by' => $user->id,
        ]);

        $prodotto = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::PZ,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 20,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 20,
            'data_movimento' => now(),
            'created_by' => $user->id,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::CONFERMATO,
            'created_by' => $user->id,
        ]);

        $costruzione = Costruzione::factory()->create();
        $componente = ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => false,
            'tipo_dimensionamento' => 'MANUALE',
        ]);

        $lotto->componentiManuali()->create([
            'componente_costruzione_id' => $componente->id,
            'prodotto_id' => $prodotto->id,
            'quantita' => 5,
            'unita_misura' => 'pz',
        ]);

        $result = app(OrderProductionService::class)->avviaProduzione($ordine, $user);

        $quantitaBom = (float) $result['bom']->righe()
            ->where('prodotto_id', $prodotto->id)
            ->sum('quantita');

        $this->assertEqualsWithDelta(5, $quantitaBom, 0.0001);
    }

    public function test_righe_sfuse_con_include_in_bom_false_non_vengono_generate(): void
    {
        $user = User::factory()->create();

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        $ordine = Ordine::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'stato' => StatoOrdine::CONFERMATO,
            'created_by' => $user->id,
        ]);

        LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::CONFERMATO,
            'optimizer_result' => ['version' => 'v2'],
            'created_by' => $user->id,
        ]);

        $prodotto = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'tipo_riga' => TipoRigaPreventivo::SFUSO,
            'include_in_bom' => true,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Sfuso incluso',
            'materiale_lordo' => 2.5,
            'volume_mc' => 2.5,
            'quantita' => 1,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'tipo_riga' => TipoRigaPreventivo::SFUSO,
            'include_in_bom' => false,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Sfuso escluso',
            'materiale_lordo' => 3.0,
            'volume_mc' => 3.0,
            'quantita' => 1,
        ]);

        $result = app(OrderProductionService::class)->avviaProduzione($ordine, $user);

        $this->assertDatabaseHas('bom_righe', [
            'bom_id' => $result['bom']->id,
            'descrizione' => 'Sfuso incluso',
        ]);

        $this->assertDatabaseMissing('bom_righe', [
            'bom_id' => $result['bom']->id,
            'descrizione' => 'Sfuso escluso',
        ]);
    }

    public function test_righe_sfuse_in_bom_rispettano_unita_mq_e_ml(): void
    {
        $user = User::factory()->create();

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        $ordine = Ordine::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'stato' => StatoOrdine::CONFERMATO,
            'created_by' => $user->id,
        ]);

        $prodottoMq = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC,
        ]);

        $prodottoMl = Prodotto::factory()->create([
            'unita_misura' => UnitaMisura::MC,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'tipo_riga' => TipoRigaPreventivo::SFUSO,
            'include_in_bom' => true,
            'prodotto_id' => $prodottoMq->id,
            'unita_misura' => 'mq',
            'descrizione' => 'Pannello mq',
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 500,
            'spessore_mm' => 20,
            'quantita' => 3,
            'superficie_mq' => 3.0,
            'materiale_lordo' => 0.066,
            'volume_mc' => 0.06,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'tipo_riga' => TipoRigaPreventivo::SFUSO,
            'include_in_bom' => true,
            'prodotto_id' => $prodottoMl->id,
            'unita_misura' => 'ml',
            'descrizione' => 'Listello ml',
            'lunghezza_mm' => 1500,
            'larghezza_mm' => 30,
            'spessore_mm' => 10,
            'quantita' => 4,
            'superficie_mq' => 0.18,
            'materiale_lordo' => 0.002,
            'volume_mc' => 0.0018,
        ]);

        $result = app(OrderProductionService::class)->avviaProduzione($ordine, $user);

        $quantitaMq = (float) $result['bom']->righe()
            ->where('prodotto_id', $prodottoMq->id)
            ->sum('quantita');

        $quantitaMl = (float) $result['bom']->righe()
            ->where('prodotto_id', $prodottoMl->id)
            ->sum('quantita');

        $this->assertEqualsWithDelta(3.0, $quantitaMq, 0.0001);
        $this->assertEqualsWithDelta(6.0, $quantitaMl, 0.0001);
    }

    public function test_blocca_avvio_ordine_se_un_lotto_non_e_pronto(): void
    {
        $user = User::factory()->create();

        $ordine = Ordine::factory()->create([
            'stato' => StatoOrdine::CONFERMATO,
            'created_by' => $user->id,
        ]);

        $costruzione = Costruzione::factory()->create();
        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $costruzione->id,
            'calcolato' => true,
            'tipo_dimensionamento' => 'CALCOLATO',
        ]);

        LottoProduzione::factory()->create([
            'ordine_id' => $ordine->id,
            'cliente_id' => $ordine->cliente_id,
            'stato' => StatoLottoProduzione::CONFERMATO,
            'costruzione_id' => $costruzione->id,
            'created_by' => $user->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('non e\' pronto');

        app(OrderProductionService::class)->avviaProduzione($ordine, $user);
    }
}
