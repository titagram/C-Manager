<?php

namespace Tests\Unit\Models;

use App\Models\LottoProduzione;
use App\Models\PreventivoRiga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventivoRigaTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_lotto_produzione(): void
    {
        $lotto = LottoProduzione::factory()->create();
        $riga = PreventivoRiga::factory()->create([
            'lotto_produzione_id' => $lotto->id,
        ]);

        $this->assertInstanceOf(LottoProduzione::class, $riga->lottoProduzione);
        $this->assertEquals($lotto->id, $riga->lottoProduzione->id);
    }

    public function test_lotto_produzione_is_nullable(): void
    {
        $riga = PreventivoRiga::factory()->create([
            'lotto_produzione_id' => null,
        ]);

        $this->assertNull($riga->lottoProduzione);
    }
}
