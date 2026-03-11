<?php

namespace Tests\Unit\Models;

use App\Enums\StatoPreventivo;
use App\Models\Cliente;
use App\Models\Preventivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventivoTest extends TestCase
{
    use RefreshDatabase;

    public function test_preventivo_appartiene_a_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $preventivo = Preventivo::factory()->create(['cliente_id' => $cliente->id]);

        $this->assertInstanceOf(Cliente::class, $preventivo->cliente);
        $this->assertEquals($cliente->id, $preventivo->cliente->id);
    }

    public function test_preventivo_appartiene_a_user(): void
    {
        $user = User::factory()->create();
        $preventivo = Preventivo::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $preventivo->createdBy);
        $this->assertEquals($user->id, $preventivo->createdBy->id);
    }

    public function test_is_scaduto_true_when_past_and_not_accepted(): void
    {
        $preventivo = Preventivo::factory()->create([
            'validita_fino' => now()->subDays(10),
            'stato' => StatoPreventivo::INVIATO,
        ]);

        $this->assertTrue($preventivo->isScaduto());
    }

    public function test_is_scaduto_false_when_accepted(): void
    {
        $preventivo = Preventivo::factory()->create([
            'validita_fino' => now()->subDays(10),
            'stato' => StatoPreventivo::ACCETTATO,
        ]);

        $this->assertFalse($preventivo->isScaduto());
    }

    public function test_is_scaduto_false_when_no_validita(): void
    {
        $preventivo = Preventivo::factory()->create([
            'validita_fino' => null,
        ]);

        $this->assertFalse($preventivo->isScaduto());
    }

    public function test_can_be_edited_true_for_bozza(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create();

        $this->assertTrue($preventivo->canBeEdited());
    }

    public function test_can_be_edited_false_for_inviato(): void
    {
        $preventivo = Preventivo::factory()->inviato()->create();

        $this->assertFalse($preventivo->canBeEdited());
    }

    public function test_can_be_edited_false_for_accettato(): void
    {
        $preventivo = Preventivo::factory()->accettato()->create();

        $this->assertFalse($preventivo->canBeEdited());
    }

    public function test_scope_by_stato(): void
    {
        Preventivo::factory()->bozza()->create();
        Preventivo::factory()->inviato()->create();
        Preventivo::factory()->accettato()->create();

        $this->assertCount(1, Preventivo::byStato(StatoPreventivo::BOZZA)->get());
        $this->assertCount(1, Preventivo::byStato(StatoPreventivo::INVIATO)->get());
        $this->assertCount(1, Preventivo::byStato(StatoPreventivo::ACCETTATO)->get());
    }

    public function test_scope_search(): void
    {
        $cliente = Cliente::factory()->create(['ragione_sociale' => 'Rossi Costruzioni']);
        Preventivo::factory()->create([
            'cliente_id' => $cliente->id,
            'descrizione' => 'Lavoro speciale',
        ]);
        Preventivo::factory()->create([
            'descrizione' => 'Altro lavoro',
        ]);

        $this->assertCount(1, Preventivo::search('rossi')->get());
        $this->assertCount(1, Preventivo::search('speciale')->get());
    }

    public function test_auto_generates_numero(): void
    {
        $preventivo = Preventivo::factory()->create();

        $this->assertNotNull($preventivo->numero);
        $this->assertStringStartsWith('PRV-', $preventivo->numero);
    }

    public function test_soft_delete(): void
    {
        $preventivo = Preventivo::factory()->create();
        $preventivo->delete();

        $this->assertSoftDeleted($preventivo);
        $this->assertNull(Preventivo::find($preventivo->id));
        $this->assertNotNull(Preventivo::withTrashed()->find($preventivo->id));
    }
}
