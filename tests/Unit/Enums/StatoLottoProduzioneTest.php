<?php

namespace Tests\Unit\Enums;

use App\Enums\StatoLottoProduzione;
use Tests\TestCase;

class StatoLottoProduzioneTest extends TestCase
{
    public function test_has_bozza_state(): void
    {
        $this->assertTrue(StatoLottoProduzione::tryFrom('bozza') instanceof StatoLottoProduzione);
    }

    public function test_has_confermato_state(): void
    {
        $this->assertTrue(StatoLottoProduzione::tryFrom('confermato') instanceof StatoLottoProduzione);
    }

    public function test_bozza_label_is_correct(): void
    {
        $stato = StatoLottoProduzione::BOZZA;
        $this->assertEquals('Bozza', $stato->label());
    }

    public function test_confermato_label_is_correct(): void
    {
        $stato = StatoLottoProduzione::CONFERMATO;
        $this->assertEquals('Confermato', $stato->label());
    }

    public function test_bozza_color_is_gray(): void
    {
        $stato = StatoLottoProduzione::BOZZA;
        $this->assertEquals('gray', $stato->color());
    }

    public function test_confermato_color_is_cyan(): void
    {
        $stato = StatoLottoProduzione::CONFERMATO;
        $this->assertEquals('cyan', $stato->color());
    }
}
