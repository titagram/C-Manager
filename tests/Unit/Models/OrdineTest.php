<?php

namespace Tests\Unit\Models;

use App\Enums\StatoOrdine;
use App\Models\Cliente;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdineTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordine_appartiene_a_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $ordine = Ordine::factory()->create(['cliente_id' => $cliente->id]);

        $this->assertInstanceOf(Cliente::class, $ordine->cliente);
        $this->assertEquals($cliente->id, $ordine->cliente->id);
    }

    public function test_ordine_appartiene_a_preventivo(): void
    {
        $preventivo = Preventivo::factory()->create();
        $ordine = Ordine::factory()->create(['preventivo_id' => $preventivo->id]);

        $this->assertInstanceOf(Preventivo::class, $ordine->preventivo);
        $this->assertEquals($preventivo->id, $ordine->preventivo->id);
    }

    public function test_ordine_puo_non_avere_preventivo(): void
    {
        $ordine = Ordine::factory()->create(['preventivo_id' => null]);

        $this->assertNull($ordine->preventivo);
    }

    public function test_ordine_appartiene_a_user(): void
    {
        $user = User::factory()->create();
        $ordine = Ordine::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $ordine->createdBy);
        $this->assertEquals($user->id, $ordine->createdBy->id);
    }

    public function test_auto_generates_numero(): void
    {
        $ordine = Ordine::factory()->create();

        $this->assertNotNull($ordine->numero);
        $this->assertStringStartsWith('ORD-', $ordine->numero);
    }

    public function test_numero_format_is_correct(): void
    {
        $ordine = Ordine::factory()->create();
        $year = now()->year;

        $this->assertMatchesRegularExpression("/^ORD-{$year}-\d{4}$/", $ordine->numero);
    }

    public function test_can_be_edited_true_for_confermato(): void
    {
        $ordine = Ordine::factory()->confermato()->create();

        $this->assertTrue($ordine->canBeEdited());
    }

    public function test_can_be_edited_false_for_consegnato(): void
    {
        $ordine = Ordine::factory()->consegnato()->create();

        $this->assertFalse($ordine->canBeEdited());
    }

    public function test_can_be_edited_false_for_fatturato(): void
    {
        $ordine = Ordine::factory()->fatturato()->create();

        $this->assertFalse($ordine->canBeEdited());
    }

    public function test_scope_by_stato(): void
    {
        Ordine::factory()->confermato()->create();
        Ordine::factory()->inProduzione()->create();
        Ordine::factory()->consegnato()->create();

        $this->assertCount(1, Ordine::byStato(StatoOrdine::CONFERMATO)->get());
        $this->assertCount(1, Ordine::byStato(StatoOrdine::IN_PRODUZIONE)->get());
        $this->assertCount(1, Ordine::byStato(StatoOrdine::CONSEGNATO)->get());
    }

    public function test_scope_search(): void
    {
        $cliente = Cliente::factory()->create(['ragione_sociale' => 'Rossi Costruzioni']);
        Ordine::factory()->create([
            'cliente_id' => $cliente->id,
            'descrizione' => 'Ordine speciale',
        ]);
        Ordine::factory()->create([
            'descrizione' => 'Altro ordine',
        ]);

        $this->assertCount(1, Ordine::search('rossi')->get());
        $this->assertCount(1, Ordine::search('speciale')->get());
    }

    public function test_soft_delete(): void
    {
        $ordine = Ordine::factory()->create();
        $ordine->delete();

        $this->assertSoftDeleted($ordine);
        $this->assertNull(Ordine::find($ordine->id));
        $this->assertNotNull(Ordine::withTrashed()->find($ordine->id));
    }

    public function test_can_be_edited_true_for_in_produzione(): void
    {
        $ordine = Ordine::factory()->inProduzione()->create();

        $this->assertTrue($ordine->canBeEdited());
    }

    public function test_ricalcola_totale_without_righe(): void
    {
        $ordine = Ordine::factory()->create(['totale' => 500]);
        $ordine->ricalcolaTotale();

        $this->assertEquals(0, $ordine->totale);
    }

    public function test_scope_in_corso(): void
    {
        Ordine::factory()->confermato()->create();
        Ordine::factory()->inProduzione()->create();
        Ordine::factory()->pronto()->create();
        Ordine::factory()->consegnato()->create();
        Ordine::factory()->fatturato()->create();

        $inCorso = Ordine::inCorso()->get();

        $this->assertCount(3, $inCorso);
    }

    public function test_ordine_has_many_righe(): void
    {
        $ordine = Ordine::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $ordine->righe());
    }

    public function test_ordine_has_many_lotti_produzione(): void
    {
        $ordine = Ordine::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $ordine->lottiProduzione());
    }
}
