<?php

namespace Tests\Unit\Services\Calcolo;

use App\Services\Calcolo\DTO\RigaInput;
use App\Services\Calcolo\PreventivoCalculator;
use PHPUnit\Framework\TestCase;

class PreventivoCalculatorTest extends TestCase
{
    public function test_calcola_materiale_con_scarto_non_sovra_arrotonda_per_errori_floating_point(): void
    {
        $calculator = new PreventivoCalculator();

        $result = $calculator->calcolaMaterialeConScarto(3.0, 0.10);

        $this->assertEqualsWithDelta(3.3, $result, 0.000001);
    }

    public function test_calcola_riga_mq_usa_materiale_lordo_atteso_senza_overshoot(): void
    {
        $calculator = new PreventivoCalculator();

        $output = $calculator->calcolaRiga(new RigaInput(
            prodotto_id: null,
            descrizione: 'Pannello',
            lunghezza_mm: 2000,
            larghezza_mm: 500,
            spessore_mm: 0,
            quantita: 3,
            coefficienteScarto: 0.10,
            prezzoUnitario: 10,
            unitaMisura: 'mq',
        ));

        $this->assertEqualsWithDelta(3.3, $output->materiale_lordo, 0.000001);
        $this->assertEqualsWithDelta(33.0, $output->totale, 0.000001);
    }
}
