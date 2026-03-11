<?php

namespace Tests\Unit\Models;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_ha_attributi_corretti(): void
    {
        $cliente = Cliente::factory()->create([
            'ragione_sociale' => 'Azienda Test SRL',
            'partita_iva' => '12345678901',
            'citta' => 'Milano',
            'provincia' => 'MI',
            'is_active' => true,
        ]);

        $this->assertEquals('Azienda Test SRL', $cliente->ragione_sociale);
        $this->assertEquals('12345678901', $cliente->partita_iva);
        $this->assertEquals('Milano', $cliente->citta);
        $this->assertEquals('MI', $cliente->provincia);
        $this->assertTrue($cliente->is_active);
    }

    public function test_indirizzo_completo_attribute(): void
    {
        $cliente = Cliente::factory()->create([
            'indirizzo' => 'Via Roma 1',
            'cap' => '20100',
            'citta' => 'Milano',
            'provincia' => 'MI',
        ]);

        $this->assertEquals('Via Roma 1 20100 Milano (MI)', $cliente->indirizzo_completo);
    }

    public function test_scope_active(): void
    {
        Cliente::factory()->count(3)->create(['is_active' => true]);
        Cliente::factory()->count(2)->create(['is_active' => false]);

        $this->assertCount(3, Cliente::active()->get());
    }

    public function test_scope_search_ragione_sociale(): void
    {
        Cliente::factory()->create(['ragione_sociale' => 'ABC Company']);
        Cliente::factory()->create(['ragione_sociale' => 'XYZ Industries']);

        $this->assertCount(1, Cliente::search('ABC')->get());
    }

    public function test_scope_search_partita_iva(): void
    {
        Cliente::factory()->create(['partita_iva' => '12345678901']);
        Cliente::factory()->create(['partita_iva' => '99999999999']);

        $this->assertCount(1, Cliente::search('12345')->get());
    }

    public function test_scope_search_email(): void
    {
        Cliente::factory()->create(['email' => 'test@example.com']);
        Cliente::factory()->create(['email' => 'altro@domain.it']);

        $this->assertCount(1, Cliente::search('example')->get());
    }

    public function test_cliente_soft_delete(): void
    {
        $cliente = Cliente::factory()->create();
        $cliente->delete();

        $this->assertSoftDeleted($cliente);
    }
}
