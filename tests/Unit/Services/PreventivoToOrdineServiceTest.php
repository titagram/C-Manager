<?php

namespace Tests\Unit\Services;

use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\StatoPreventivo;
use App\Enums\TipoMovimento;
use App\Models\Cliente;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\PreventivoToOrdineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventivoToOrdineServiceTest extends TestCase
{
    use RefreshDatabase;

    private PreventivoToOrdineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PreventivoToOrdineService::class);
    }

    public function test_converts_accepted_preventivo_to_ordine(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cliente = Cliente::factory()->create();
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoPreventivo::ACCETTATO,
            'descrizione' => 'Test description',
            'totale' => 1500.00,
        ]);
        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'quantita' => 1,
            'volume_mc' => 0.1,
            'materiale_lordo' => 0.1,
            'totale_riga' => 10,
        ]);

        $ordine = $this->service->convert($preventivo);

        $this->assertInstanceOf(Ordine::class, $ordine);
        $this->assertEquals($preventivo->id, $ordine->preventivo_id);
        $this->assertEquals($cliente->id, $ordine->cliente_id);
        $this->assertEquals(StatoOrdine::CONFERMATO, $ordine->stato);
        $this->assertEquals('Test description', $ordine->descrizione);
        $this->assertEquals(10.00, (float) $ordine->totale);
        $this->assertEquals($user->id, $ordine->created_by);
    }

    public function test_throws_exception_for_non_accepted_preventivo(): void
    {
        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::BOZZA,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Solo i preventivi accettati possono essere convertiti in ordini.');

        $this->service->convert($preventivo);
    }

    public function test_throws_exception_if_already_converted(): void
    {
        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        // Create an ordine linked to this preventivo
        Ordine::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Questo preventivo è già stato convertito in ordine.');

        $this->service->convert($preventivo);
    }

    public function test_throws_exception_if_preventivo_has_no_righe(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Il preventivo non contiene righe');

        $this->service->convert($preventivo);
    }

    public function test_copies_righe_from_preventivo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cliente = Cliente::factory()->create();
        $prodotto = Prodotto::factory()->create([
            'prezzo_unitario' => 100.00,
        ]);
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Riga 1',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 500,
            'spessore_mm' => 50,
            'quantita' => 10,
            'volume_mc' => 0.25,
            'materiale_lordo' => 0.30,
            'totale_riga' => 30.00,
        ]);

        $ordine = $this->service->convert($preventivo);

        $this->assertCount(1, $ordine->righe);
        $riga = $ordine->righe->first();
        $this->assertEquals($prodotto->id, $riga->prodotto_id);
        $this->assertEquals('Riga 1', $riga->descrizione);
        $this->assertEquals(1000, $riga->larghezza_mm); // lunghezza -> larghezza
        $this->assertEquals(500, $riga->profondita_mm); // larghezza -> profondita
        $this->assertEquals(50, $riga->altezza_mm); // spessore -> altezza
        $this->assertEquals(10, $riga->quantita);
        $this->assertEquals(0.30, $riga->volume_mc_finale);
        $this->assertEquals(30.00, $riga->totale_riga);
    }

    public function test_falls_back_to_volume_mc_when_materiale_lordo_is_zero(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'descrizione' => 'Riga lotto con lordo legacy a zero',
            'volume_mc' => 0.0115,
            'materiale_lordo' => 0,
            'prezzo_unitario' => 500,
            'totale_riga' => 5.75,
        ]);

        $ordine = $this->service->convert($preventivo);
        $riga = $ordine->righe->first();

        $this->assertEqualsWithDelta(0.0115, (float) $riga->volume_mc_finale, 0.000001);
        $this->assertEqualsWithDelta(5.75, (float) $riga->totale_riga, 0.01);
    }

    public function test_convert_recalculates_order_total_from_generated_rows(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
            'totale' => 999.99,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'volume_mc' => 0.10,
            'materiale_lordo' => 0.10,
            'prezzo_unitario' => 100,
            'totale_riga' => 10,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'volume_mc' => 0.20,
            'materiale_lordo' => 0.20,
            'prezzo_unitario' => 200,
            'totale_riga' => 40,
        ]);

        $ordine = $this->service->convert($preventivo);

        $this->assertEqualsWithDelta(50, (float) $ordine->fresh()->totale, 0.01);
    }

    public function test_creates_one_ordine_riga_for_each_preventivo_riga(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        PreventivoRiga::factory()->count(2)->create([
            'preventivo_id' => $preventivo->id,
            'quantita' => 1,
            'volume_mc' => 0.1,
            'materiale_lordo' => 0.1,
            'totale_riga' => 10,
        ]);

        $ordine = $this->service->convert($preventivo);

        $this->assertSame(2, $ordine->righe()->count());
    }

    public function test_generates_ordine_numero_automatically(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);
        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'quantita' => 1,
            'volume_mc' => 0.1,
            'materiale_lordo' => 0.1,
            'totale_riga' => 10,
        ]);

        $ordine = $this->service->convert($preventivo);

        $this->assertNotEmpty($ordine->numero);
        $this->assertStringStartsWith('ORD-', $ordine->numero);
    }

    public function test_sets_data_ordine_to_now(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);
        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'quantita' => 1,
            'volume_mc' => 0.1,
            'materiale_lordo' => 0.1,
            'totale_riga' => 10,
        ]);

        $ordine = $this->service->convert($preventivo);

        $this->assertEquals(now()->toDateString(), $ordine->data_ordine->toDateString());
    }

    public function test_uses_preventivo_riga_prezzo_unitario_as_prezzo_mc(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $prodotto = Prodotto::factory()->create([
            'prezzo_unitario' => 999.99,
        ]);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'prodotto_id' => $prodotto->id,
            'prezzo_unitario' => 123.45,
            'volume_mc' => 0.500000,
            'materiale_lordo' => 0.6000,
            'totale_riga' => null,
        ]);

        $ordine = $this->service->convert($preventivo);
        $riga = $ordine->righe->first();

        $this->assertEquals(123.45, (float) $riga->prezzo_mc);
        $this->assertEquals(74.07, (float) $riga->totale_riga);
    }

    public function test_falls_back_to_prodotto_prezzo_unitario_when_riga_price_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $prodotto = Prodotto::factory()->create([
            'prezzo_unitario' => 700.00,
        ]);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'prodotto_id' => $prodotto->id,
            'prezzo_unitario' => null,
            'volume_mc' => 1.000000,
            'materiale_lordo' => null,
            'totale_riga' => null,
        ]);

        $ordine = $this->service->convert($preventivo);
        $riga = $ordine->righe->first();

        $this->assertEquals(700.00, (float) $riga->prezzo_mc);
        $this->assertEquals(700.00, (float) $riga->totale_riga);
    }

    public function test_falls_back_to_prodotto_prezzo_mc_for_mc_rows_when_riga_price_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $prodotto = Prodotto::factory()->create([
            'unita_misura' => 'mc',
            'prezzo_unitario' => 1,
            'prezzo_mc' => 545.55,
        ]);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'prodotto_id' => $prodotto->id,
            'unita_misura' => 'mc',
            'prezzo_unitario' => null,
            'volume_mc' => 1.000000,
            'materiale_lordo' => null,
            'totale_riga' => null,
        ]);

        $ordine = $this->service->convert($preventivo);
        $riga = $ordine->righe->first();

        $this->assertEquals(545.55, (float) $riga->prezzo_mc);
        $this->assertEquals(545.55, (float) $riga->totale_riga);
    }

    public function test_casts_decimal_dimensions_to_integer_for_ordine_riga(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'prodotto_id' => null,
            'descrizione' => 'Riga dimensioni decimali',
            'lunghezza_mm' => '0.00',
            'larghezza_mm' => '1200.60',
            'spessore_mm' => '18.20',
            'quantita' => 1,
            'volume_mc' => 0.0644,
            'materiale_lordo' => 0,
            'prezzo_unitario' => 0,
            'totale_riga' => 100,
        ]);

        $ordine = $this->service->convert($preventivo);
        $riga = $ordine->righe->first();

        $this->assertSame(0, $riga->larghezza_mm);
        $this->assertSame(1200, $riga->profondita_mm);
        $this->assertSame(18, $riga->altezza_mm);
    }

    public function test_convert_generates_order_bom_and_opziona_materiali_for_linked_lotti(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cliente = Cliente::factory()->create();
        $preventivo = Preventivo::factory()->create([
            'cliente_id' => $cliente->id,
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        $prodotto = Prodotto::factory()->create(['unita_misura' => 'mc']);
        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 10,
        ]);
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 10,
            'data_movimento' => now(),
            'created_by' => $user->id,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'stato' => StatoLottoProduzione::BOZZA,
            'created_by' => $user->id,
        ]);
        $lotto->materialiUsati()->create([
            'prodotto_id' => $prodotto->id,
            'descrizione' => 'Asse da opzionare',
            'lunghezza_mm' => 4000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita_pezzi' => 1,
            'volume_mc' => 0.5,
            'ordine' => 0,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'lotto_produzione_id' => $lotto->id,
            'descrizione' => 'Riga lotto',
            'quantita' => 1,
            'volume_mc' => 0.5,
            'materiale_lordo' => 0.5,
            'totale_riga' => 100,
        ]);

        $ordine = $this->service->convert($preventivo);

        $this->assertDatabaseHas('bom', [
            'ordine_id' => $ordine->id,
            'source' => 'ordine',
        ]);

        $this->assertDatabaseHas('consumi_materiale', [
            'lotto_produzione_id' => $lotto->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'stato' => 'opzionato',
        ]);
    }

    public function test_convert_links_lotto_to_order_and_keeps_origin_preventivo_link_for_traceability(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        $lotto = LottoProduzione::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'stato' => StatoLottoProduzione::BOZZA,
            'created_by' => $user->id,
        ]);

        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'lotto_produzione_id' => $lotto->id,
            'quantita' => 1,
            'volume_mc' => 0.1,
            'materiale_lordo' => 0.1,
            'totale_riga' => 10,
        ]);

        $ordine = $this->service->convert($preventivo);
        $lotto->refresh();

        $this->assertSame($ordine->id, $lotto->ordine_id);
        $this->assertSame($preventivo->id, $lotto->preventivo_id);
    }
}
