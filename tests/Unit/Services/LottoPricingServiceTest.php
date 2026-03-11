<?php

namespace Tests\Unit\Services;

use App\Enums\LottoPricingMode;
use App\Services\LottoPricingService;
use Tests\TestCase;

class LottoPricingServiceTest extends TestCase
{
    public function test_calcola_prezzo_con_tariffa_mc(): void
    {
        $result = app(LottoPricingService::class)->calcola(
            volumeTotaleMc: 1.25,
            costoTotale: 100,
            pricingMode: LottoPricingMode::TARIFFA_MC->value,
            tariffaMc: 280,
            ricaricoPercentuale: 0,
            prezzoFinaleOverride: null
        );

        $this->assertSame(LottoPricingMode::TARIFFA_MC->value, $result['pricing_mode']);
        $this->assertEqualsWithDelta(350, $result['prezzo_calcolato'], 0.01);
        $this->assertEqualsWithDelta(350, $result['prezzo_finale'], 0.01);
    }

    public function test_calcola_prezzo_con_costo_piu_ricarico(): void
    {
        $result = app(LottoPricingService::class)->calcola(
            volumeTotaleMc: 1.25,
            costoTotale: 200,
            pricingMode: LottoPricingMode::COSTO_RICARICO->value,
            tariffaMc: null,
            ricaricoPercentuale: 30,
            prezzoFinaleOverride: null
        );

        $this->assertSame(LottoPricingMode::COSTO_RICARICO->value, $result['pricing_mode']);
        $this->assertEqualsWithDelta(260, $result['prezzo_calcolato'], 0.01);
        $this->assertEqualsWithDelta(260, $result['prezzo_finale'], 0.01);
    }

    public function test_override_manuale_sovrascrive_prezzo_finale(): void
    {
        $result = app(LottoPricingService::class)->calcola(
            volumeTotaleMc: 2,
            costoTotale: 100,
            pricingMode: LottoPricingMode::TARIFFA_MC->value,
            tariffaMc: 150,
            ricaricoPercentuale: 0,
            prezzoFinaleOverride: 999.99
        );

        $this->assertEqualsWithDelta(300, $result['prezzo_calcolato'], 0.01);
        $this->assertEqualsWithDelta(999.99, $result['prezzo_finale'], 0.01);
    }
}
