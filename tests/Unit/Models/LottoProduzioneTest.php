<?php

namespace Tests\Unit\Models;

use App\Models\Cliente;
use App\Models\ConsumoMateriale;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\LottoProduzioneMateriale;
use App\Models\Ordine;
use App\Models\OrdineRiga;
use App\Models\Prodotto;
use App\Models\Scarto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LottoProduzioneTest extends TestCase
{
    use RefreshDatabase;

    public function test_lotto_can_belong_to_ordine(): void
    {
        $cliente = Cliente::factory()->create();
        $ordine = Ordine::factory()->create(['cliente_id' => $cliente->id]);
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'ordine_id' => $ordine->id,
        ]);

        $this->assertInstanceOf(Ordine::class, $lotto->ordine);
        $this->assertEquals($ordine->id, $lotto->ordine->id);
    }

    public function test_lotto_can_belong_to_ordine_riga(): void
    {
        $cliente = Cliente::factory()->create();
        $ordine = Ordine::factory()->create(['cliente_id' => $cliente->id]);
        $ordineRiga = OrdineRiga::factory()->create(['ordine_id' => $ordine->id]);
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'ordine_id' => $ordine->id,
            'ordine_riga_id' => $ordineRiga->id,
        ]);

        $this->assertInstanceOf(OrdineRiga::class, $lotto->ordineRiga);
        $this->assertEquals($ordineRiga->id, $lotto->ordineRiga->id);
    }

    public function test_lotto_ordine_is_nullable(): void
    {
        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'ordine_id' => null,
            'ordine_riga_id' => null,
        ]);

        $this->assertNull($lotto->ordine);
        $this->assertNull($lotto->ordineRiga);
    }

    public function test_lotto_appartiene_a_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create(['cliente_id' => $cliente->id]);

        $this->assertInstanceOf(Cliente::class, $lotto->cliente);
        $this->assertEquals($cliente->id, $lotto->cliente->id);
    }

    public function test_auto_generates_codice_lotto(): void
    {
        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create(['cliente_id' => $cliente->id]);

        $this->assertNotNull($lotto->codice_lotto);
        $this->assertStringStartsWith('LP-', $lotto->codice_lotto);
    }

    public function test_lotto_calcola_fitok_percentuale(): void
    {
        $cliente = Cliente::factory()->create();
        $prodottoFitok = Prodotto::factory()->create(['soggetto_fitok' => true]);
        $prodottoNonFitok = Prodotto::factory()->create(['soggetto_fitok' => false]);

        $lottoMatFitok = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoFitok->id,
            'quantita_iniziale' => 10,
        ]);
        $lottoMatNonFitok = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoNonFitok->id,
            'quantita_iniziale' => 10,
        ]);

        $lotto = LottoProduzione::factory()->create(['cliente_id' => $cliente->id]);

        // 3 MC FITOK + 2 MC non-FITOK = 60% FITOK
        ConsumoMateriale::factory()->create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMatFitok->id,
            'quantita' => 3.0,
        ]);
        ConsumoMateriale::factory()->create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMatNonFitok->id,
            'quantita' => 2.0,
        ]);

        $lotto->calcolaFitok();

        $this->assertEquals(60.00, $lotto->fitok_percentuale);
        $this->assertEquals(3.0, $lotto->fitok_volume_mc);
        $this->assertEquals(2.0, $lotto->non_fitok_volume_mc);
        $this->assertNotNull($lotto->fitok_calcolato_at);
    }

    public function test_lotto_fitok_100_percent(): void
    {
        $cliente = Cliente::factory()->create();
        $prodottoFitok = Prodotto::factory()->create(['soggetto_fitok' => true]);
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoFitok->id,
        ]);

        $lotto = LottoProduzione::factory()->create(['cliente_id' => $cliente->id]);
        ConsumoMateriale::factory()->create([
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMat->id,
            'quantita' => 5.0,
        ]);

        $lotto->calcolaFitok();

        $this->assertEquals(100.00, $lotto->fitok_percentuale);
    }

    public function test_lotto_is_fitok_compliant(): void
    {
        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'fitok_percentuale' => 100.00,
        ]);

        $this->assertTrue($lotto->isFitokCompliant());
    }

    public function test_lotto_is_not_fitok_compliant(): void
    {
        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'fitok_percentuale' => 80.00,
        ]);

        $this->assertFalse($lotto->isFitokCompliant());
    }

    public function test_mixed_fitok_lotto_is_not_certifiable(): void
    {
        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'fitok_percentuale' => 40.00,
        ]);

        $this->assertTrue($lotto->isFitokMixed());
        $this->assertTrue($lotto->isFitokNonCertificabile());
        $this->assertFalse($lotto->isFitokCompliant());
        $this->assertSame(
            'Misto (non certificabile FITOK)',
            $lotto->getFitokCertificationStatusLabel()
        );
    }

    public function test_has_many_materiali_usati(): void
    {
        $lotto = LottoProduzione::factory()->create();

        LottoProduzioneMateriale::factory()->count(3)->create([
            'lotto_produzione_id' => $lotto->id,
        ]);

        $this->assertCount(3, $lotto->materialiUsati);
        $this->assertInstanceOf(LottoProduzioneMateriale::class, $lotto->materialiUsati->first());
    }

    public function test_has_many_scarti(): void
    {
        $lotto = LottoProduzione::factory()->create();

        Scarto::factory()->count(2)->create([
            'lotto_produzione_id' => $lotto->id,
        ]);

        $this->assertCount(2, $lotto->scarti);
        $this->assertInstanceOf(Scarto::class, $lotto->scarti->first());
    }

    public function test_materiali_usati_ordered_by_ordine(): void
    {
        $lotto = LottoProduzione::factory()->create();

        LottoProduzioneMateriale::factory()->create([
            'lotto_produzione_id' => $lotto->id,
            'ordine' => 2,
            'descrizione' => 'Second',
        ]);

        LottoProduzioneMateriale::factory()->create([
            'lotto_produzione_id' => $lotto->id,
            'ordine' => 0,
            'descrizione' => 'First',
        ]);

        $this->assertEquals('First', $lotto->materialiUsati->first()->descrizione);
        $this->assertEquals('Second', $lotto->materialiUsati->last()->descrizione);
    }

    public function test_numero_univoco_is_auto_generated_when_empty(): void
    {
        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'prodotto_finale' => 'Test Product',
        ]);

        $this->assertNotNull($lotto->numero_univoco);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{3}$/', $lotto->numero_univoco);
    }

    public function test_numero_univoco_increments_sequentially(): void
    {
        $cliente = Cliente::factory()->create();

        $lotto1 = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'prodotto_finale' => 'Test 1',
        ]);

        $lotto2 = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'prodotto_finale' => 'Test 2',
        ]);

        // Extract numbers from formato YYYY-NNN
        preg_match('/^\d{4}-(\d{3})$/', $lotto1->numero_univoco, $matches1);
        preg_match('/^\d{4}-(\d{3})$/', $lotto2->numero_univoco, $matches2);

        $num1 = (int) $matches1[1];
        $num2 = (int) $matches2[1];

        // Second should be one more than first
        $this->assertEquals($num1 + 1, $num2);
    }

    public function test_numero_univoco_can_be_manually_set(): void
    {
        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
            'prodotto_finale' => 'Test',
            'numero_univoco' => 'CUSTOM-01',
        ]);

        $this->assertEquals('CUSTOM-01', $lotto->numero_univoco);
    }

    public function test_calcola_totali_includes_numero_pezzi(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'larghezza_cm' => 100,
            'profondita_cm' => 80,
            'altezza_cm' => 120,
            'numero_pezzi' => 5,
            'peso_kg_mc' => 360,
        ]);

        $lotto->calcolaTotali();

        // Volume for 1 piece = 100 * 80 * 120 / 1000000 = 0.96 MC
        // Volume for 5 pieces = 0.96 * 5 = 4.8 MC
        $this->assertEquals(4.8, $lotto->volume_totale_mc);

        // Peso = 4.8 * 360 = 1728 kg
        $this->assertEquals(1728, $lotto->peso_totale_kg);
    }

    public function test_calcola_totali_with_single_piece(): void
    {
        $lotto = LottoProduzione::factory()->create([
            'larghezza_cm' => 50,
            'profondita_cm' => 50,
            'altezza_cm' => 50,
            'numero_pezzi' => 1,
            'peso_kg_mc' => 360,
        ]);

        $lotto->calcolaTotali();

        // Volume = 50 * 50 * 50 / 1000000 = 0.125 MC
        $this->assertEquals(0.125, $lotto->volume_totale_mc);

        // Peso = 0.125 * 360 = 45 kg
        $this->assertEquals(45, $lotto->peso_totale_kg);
    }
}
