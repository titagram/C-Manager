<?php

namespace Tests\Unit\Services;

use App\Models\Bom;
use App\Models\BomRiga;
use App\Models\Cliente;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\BomCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private BomCalculatorService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BomCalculatorService();
        $this->user = User::factory()->create();
    }

    public function test_calcola_fabbisogno_from_bom(): void
    {
        $prodotto = Prodotto::factory()->create();
        $bom = Bom::factory()->create();
        BomRiga::factory()->create([
            'bom_id' => $bom->id,
            'prodotto_id' => $prodotto->id,
            'quantita' => 0.5, // 0.5 MC per pezzo
            'coefficiente_scarto' => 0.10,
        ]);

        $fabbisogno = $this->service->calcolaFabbisogno($bom, 10); // 10 pezzi

        // 0.5 * 1.10 * 10 = 5.5 MC
        $this->assertCount(1, $fabbisogno);
        $this->assertEquals($prodotto->id, $fabbisogno[0]['prodotto_id']);
        $this->assertEquals(5.5, $fabbisogno[0]['quantita_necessaria']);
    }

    public function test_verifica_disponibilita_ok(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);
        // Create carico movement to set initial stock
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 100,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $bom = Bom::factory()->create();
        BomRiga::factory()->create([
            'bom_id' => $bom->id,
            'prodotto_id' => $prodotto->id,
            'quantita' => 1.0,
            'coefficiente_scarto' => 0,
        ]);

        $result = $this->service->verificaDisponibilita($bom, 10);

        $this->assertTrue($result['disponibile']);
        $this->assertEmpty($result['mancanti']);
    }

    public function test_verifica_disponibilita_insufficiente(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 5,
        ]);
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 5,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $bom = Bom::factory()->create();
        BomRiga::factory()->create([
            'bom_id' => $bom->id,
            'prodotto_id' => $prodotto->id,
            'quantita' => 1.0,
            'coefficiente_scarto' => 0,
        ]);

        $result = $this->service->verificaDisponibilita($bom, 10);

        $this->assertFalse($result['disponibile']);
        $this->assertCount(1, $result['mancanti']);
        $this->assertEquals(5, $result['mancanti'][0]['mancante']); // need 10, have 5
    }

    public function test_genera_consumi_from_bom(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 100,
        ]);
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 100,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $bom = Bom::factory()->create();
        BomRiga::factory()->create([
            'bom_id' => $bom->id,
            'prodotto_id' => $prodotto->id,
            'quantita' => 1.0,
            'coefficiente_scarto' => 0.10,
        ]);

        $cliente = Cliente::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'cliente_id' => $cliente->id,
        ]);

        $consumi = $this->service->generaConsumi($bom, $lotto, 5);

        $this->assertCount(1, $consumi);
        // 1.0 * 1.10 * 5 = 5.5
        $this->assertEquals(5.5, $consumi[0]->quantita);
        $this->assertEquals($lotto->id, $consumi[0]->lotto_produzione_id);
        $this->assertStringContainsString('template BOM', (string) $consumi[0]->note);
    }

    public function test_genera_consumi_da_template_keeps_backward_compatibility(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lottoMat = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'quantita_iniziale' => 50,
        ]);
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoMat->id,
            'tipo' => 'carico',
            'quantita' => 50,
            'data_movimento' => now(),
            'created_by' => $this->user->id,
        ]);

        $bom = Bom::factory()->create();
        BomRiga::factory()->create([
            'bom_id' => $bom->id,
            'prodotto_id' => $prodotto->id,
            'quantita' => 2.0,
            'coefficiente_scarto' => 0,
        ]);

        $lottoLegacy = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
        ]);
        $lottoDirect = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
        ]);

        $legacy = $this->service->generaConsumi($bom, $lottoLegacy, 3);
        $direct = $this->service->generaConsumiDaTemplate($bom, $lottoDirect, 3);

        $this->assertCount(1, $legacy);
        $this->assertCount(1, $direct);
        $this->assertEquals(6.0, $legacy[0]->quantita);
        $this->assertEquals(6.0, $direct[0]->quantita);
    }
}
