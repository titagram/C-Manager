<?php

namespace Tests\Unit\Models;

use App\Enums\TipoMovimento;
use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LottoMaterialeTest extends TestCase
{
    use RefreshDatabase;

    public function test_lotto_appartiene_a_prodotto(): void
    {
        $prodotto = Prodotto::factory()->create();
        $lotto = LottoMateriale::factory()->create(['prodotto_id' => $prodotto->id]);

        $this->assertInstanceOf(Prodotto::class, $lotto->prodotto);
        $this->assertEquals($prodotto->id, $lotto->prodotto->id);
    }

    public function test_giacenza_attribute_con_movimenti(): void
    {
        $user = User::factory()->create();
        $lotto = LottoMateriale::factory()->create();

        // Carico 100
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lotto->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 100,
            'created_by' => $user->id,
            'data_movimento' => now(),
        ]);

        // Scarico 30
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lotto->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 30,
            'created_by' => $user->id,
            'data_movimento' => now(),
        ]);

        $this->assertEquals(70, $lotto->giacenza);
    }

    public function test_giacenza_attribute_senza_movimenti(): void
    {
        $lotto = LottoMateriale::factory()->create([
            'quantita_iniziale' => 42.5,
        ]);

        $this->assertEquals(42.5, $lotto->giacenza);
    }

    public function test_dimensioni_attribute(): void
    {
        $lotto = LottoMateriale::factory()->create([
            'lunghezza_mm' => 2000,
            'larghezza_mm' => 300,
            'spessore_mm' => 25,
        ]);

        $this->assertEquals('2000.00 x 300.00 x 25.00 mm', $lotto->dimensioni);
    }

    public function test_dimensioni_attribute_null(): void
    {
        $lotto = LottoMateriale::factory()->create([
            'lunghezza_mm' => null,
            'larghezza_mm' => null,
            'spessore_mm' => null,
        ]);

        $this->assertNull($lotto->dimensioni);
    }

    public function test_is_fitok(): void
    {
        $prodottoFitok = Prodotto::factory()->create(['soggetto_fitok' => true]);
        $prodottoNonFitok = Prodotto::factory()->create(['soggetto_fitok' => false]);

        $lottoFitok = LottoMateriale::factory()->create(['prodotto_id' => $prodottoFitok->id]);
        $lottoNonFitok = LottoMateriale::factory()->create(['prodotto_id' => $prodottoNonFitok->id]);

        $this->assertTrue($lottoFitok->isFitok());
        $this->assertFalse($lottoNonFitok->isFitok());
    }

    public function test_has_fitok_data(): void
    {
        $lottoConFitok = LottoMateriale::factory()->create(['fitok_certificato' => 'CERT-123']);
        $lottoSenzaFitok = LottoMateriale::factory()->create(['fitok_certificato' => null]);

        $this->assertTrue($lottoConFitok->hasFitokData());
        $this->assertFalse($lottoSenzaFitok->hasFitokData());
    }

    public function test_fitok_paese_origine_can_be_null(): void
    {
        $lotto = LottoMateriale::factory()->create([
            'fitok_certificato' => null,
            'fitok_data_trattamento' => null,
            'fitok_tipo_trattamento' => null,
            'fitok_paese_origine' => null,
        ]);

        $this->assertNull($lotto->fresh()->fitok_paese_origine);
    }

    public function test_scope_search(): void
    {
        $prodotto = Prodotto::factory()->create(['nome' => 'Tavola Abete']);
        LottoMateriale::factory()->create([
            'codice_lotto' => 'LOT-001',
            'fornitore' => 'Fornitore ABC',
            'prodotto_id' => $prodotto->id,
        ]);
        LottoMateriale::factory()->create([
            'codice_lotto' => 'LOT-002',
            'fornitore' => 'Fornitore XYZ',
        ]);

        $this->assertCount(1, LottoMateriale::search('LOT-001')->get());
        $this->assertCount(1, LottoMateriale::search('ABC')->get());
        $this->assertCount(1, LottoMateriale::search('Abete')->get());
    }
}
