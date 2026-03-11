<?php

namespace Tests\Unit\Models;

use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponenteCostruzioneTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_manual_component_normalizes_calcolato_false(): void
    {
        $costruzione = Costruzione::factory()->create();

        $componente = ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => '   Chiodi   ',
            'tipo_dimensionamento' => 'MANUALE',
            'calcolato' => true,
            'formula_lunghezza' => '',
            'formula_larghezza' => '   ',
            'formula_quantita' => '',
        ]);

        $componente->refresh();

        $this->assertSame('MANUALE', $componente->tipo_dimensionamento);
        $this->assertFalse((bool) $componente->calcolato);
        $this->assertSame('Chiodi', $componente->nome);
        $this->assertNull($componente->formula_lunghezza);
        $this->assertNull($componente->formula_larghezza);
        $this->assertSame('1', $componente->formula_quantita);
    }

    public function test_saving_calculated_component_normalizes_calcolato_true(): void
    {
        $costruzione = Costruzione::factory()->create();

        $componente = ComponenteCostruzione::create([
            'costruzione_id' => $costruzione->id,
            'nome' => 'Parete',
            'tipo_dimensionamento' => 'calcolato',
            'calcolato' => false,
            'formula_lunghezza' => ' L ',
            'formula_larghezza' => ' H ',
            'formula_quantita' => ' 2 ',
        ]);

        $componente->refresh();

        $this->assertSame('CALCOLATO', $componente->tipo_dimensionamento);
        $this->assertTrue((bool) $componente->calcolato);
        $this->assertSame('L', $componente->formula_lunghezza);
        $this->assertSame('H', $componente->formula_larghezza);
        $this->assertSame('2', $componente->formula_quantita);
    }
}

