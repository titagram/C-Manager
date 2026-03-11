<?php

namespace Tests\Unit\Enums;

use App\Enums\StatoOrdine;
use PHPUnit\Framework\TestCase;

class StatoOrdineTest extends TestCase
{
    public function test_all_stati_have_labels(): void
    {
        foreach (StatoOrdine::cases() as $stato) {
            $this->assertNotEmpty($stato->label());
        }
    }

    public function test_all_stati_have_colors(): void
    {
        foreach (StatoOrdine::cases() as $stato) {
            $this->assertNotEmpty($stato->color());
        }
    }

    public function test_all_stati_have_icons(): void
    {
        foreach (StatoOrdine::cases() as $stato) {
            $this->assertNotEmpty($stato->icon());
        }
    }

    public function test_confermato_is_default_initial_state(): void
    {
        $this->assertEquals('confermato', StatoOrdine::CONFERMATO->value);
    }

    public function test_can_transition_checks(): void
    {
        $this->assertTrue(StatoOrdine::CONFERMATO->canTransitionTo(StatoOrdine::IN_PRODUZIONE));
        $this->assertTrue(StatoOrdine::IN_PRODUZIONE->canTransitionTo(StatoOrdine::PRONTO));
        $this->assertTrue(StatoOrdine::PRONTO->canTransitionTo(StatoOrdine::CONSEGNATO));
        $this->assertTrue(StatoOrdine::CONSEGNATO->canTransitionTo(StatoOrdine::FATTURATO));

        $this->assertFalse(StatoOrdine::FATTURATO->canTransitionTo(StatoOrdine::CONFERMATO));
        $this->assertFalse(StatoOrdine::CONSEGNATO->canTransitionTo(StatoOrdine::IN_PRODUZIONE));
    }
}
