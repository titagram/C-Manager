<?php

namespace Tests\Unit\Services;

use App\Enums\TipoMovimento;
use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;
    private User $user;
    private Prodotto $prodotto;
    private LottoMateriale $lotto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InventoryService();
        $this->user = User::factory()->create();
        $this->prodotto = Prodotto::factory()->create();
        $this->lotto = LottoMateriale::factory()->create([
            'prodotto_id' => $this->prodotto->id,
            'quantita_iniziale' => 100,
        ]);
    }

    public function test_carico_crea_movimento_corretto(): void
    {
        $movimento = $this->service->carico(
            lotto: $this->lotto,
            quantita: 50,
            documento: null,
            user: $this->user,
            causale: 'Test carico'
        );

        $this->assertInstanceOf(MovimentoMagazzino::class, $movimento);
        $this->assertEquals(TipoMovimento::CARICO, $movimento->tipo);
        $this->assertEquals(50, $movimento->quantita);
        $this->assertEquals('Test carico', $movimento->causale);
        $this->assertEquals($this->user->id, $movimento->created_by);
    }

    public function test_calcola_giacenza_con_solo_carico(): void
    {
        $this->service->carico($this->lotto, 100, null, $this->user);

        $giacenza = $this->service->calcolaGiacenza($this->lotto);

        $this->assertEquals(100, $giacenza);
    }

    public function test_calcola_giacenza_con_carico_e_scarico(): void
    {
        $this->service->carico($this->lotto, 100, null, $this->user);
        $this->service->scarico($this->lotto, 30, null, null, $this->user, 'Scarico test');

        $giacenza = $this->service->calcolaGiacenza($this->lotto);

        $this->assertEquals(70, $giacenza);
    }

    public function test_scarico_fallisce_con_giacenza_insufficiente(): void
    {
        $this->service->carico($this->lotto, 50, null, $this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Giacenza insufficiente');

        $this->service->scarico($this->lotto, 100, null, null, $this->user, 'Scarico eccessivo');
    }

    public function test_rettifica_positiva_aumenta_giacenza(): void
    {
        $this->service->carico($this->lotto, 100, null, $this->user);
        $this->service->rettifica($this->lotto, 20, true, $this->user, 'Rettifica inventario');

        $giacenza = $this->service->calcolaGiacenza($this->lotto);

        $this->assertEquals(120, $giacenza);
    }

    public function test_rettifica_negativa_diminuisce_giacenza(): void
    {
        $this->service->carico($this->lotto, 100, null, $this->user);
        $this->service->rettifica(
            $this->lotto,
            30,
            false,
            $this->user,
            'Rettifica inventario',
            \App\Models\MovimentoMagazzino::REASON_CODE_COUNT_MISMATCH
        );

        $giacenza = $this->service->calcolaGiacenza($this->lotto);

        $this->assertEquals(70, $giacenza);
    }

    public function test_rettifica_negativa_fallisce_se_supera_giacenza(): void
    {
        $this->service->carico($this->lotto, 50, null, $this->user);

        $this->expectException(\Exception::class);

        $this->service->rettifica(
            $this->lotto,
            100,
            false,
            $this->user,
            'Rettifica eccessiva',
            \App\Models\MovimentoMagazzino::REASON_CODE_COUNT_MISMATCH
        );
    }

    public function test_verifica_disponibilita_true(): void
    {
        $this->service->carico($this->lotto, 100, null, $this->user);

        $this->assertTrue($this->service->verificaDisponibilita($this->lotto, 50));
        $this->assertTrue($this->service->verificaDisponibilita($this->lotto, 100));
    }

    public function test_verifica_disponibilita_false(): void
    {
        $this->service->carico($this->lotto, 100, null, $this->user);

        $this->assertFalse($this->service->verificaDisponibilita($this->lotto, 150));
    }

    public function test_giacenza_lotto_senza_movimenti_usa_quantita_iniziale_come_baseline(): void
    {
        $giacenza = $this->service->calcolaGiacenza($this->lotto);

        $this->assertEquals(100, $giacenza);
    }

    public function test_scarico_su_lotto_legacy_senza_carico_iniziale_scala_la_baseline(): void
    {
        $movimento = $this->service->scarico(
            lotto: $this->lotto,
            quantita: 40,
            lottoProduzione: null,
            documento: null,
            user: $this->user,
            causale: 'Scarico legacy'
        );

        $this->assertSame(TipoMovimento::SCARICO, $movimento->tipo);
        $this->assertEqualsWithDelta(60, $this->service->calcolaGiacenza($this->lotto), 0.0001);
    }
}
