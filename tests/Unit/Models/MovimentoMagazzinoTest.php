<?php

namespace Tests\Unit\Models;

use App\Enums\TipoMovimento;
use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimentoMagazzinoTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_adjustment_requires_structured_reason_code(): void
    {
        $lotto = LottoMateriale::factory()->create();
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('codice causale strutturato');

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lotto->id,
            'tipo' => TipoMovimento::RETTIFICA_NEGATIVA,
            'quantita' => 1,
            'causale' => 'Rettifica negativa senza codice strutturato',
            'created_by' => $user->id,
            'data_movimento' => now(),
        ]);
    }

    public function test_negative_adjustment_accepts_valid_structured_reason_code(): void
    {
        $lotto = LottoMateriale::factory()->create();
        $user = User::factory()->create();

        $movimento = MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lotto->id,
            'tipo' => TipoMovimento::RETTIFICA_NEGATIVA,
            'quantita' => 1,
            'causale' => 'Rettifica negativa per conteggio',
            'causale_codice' => MovimentoMagazzino::REASON_CODE_COUNT_MISMATCH,
            'created_by' => $user->id,
            'data_movimento' => now(),
        ]);

        $this->assertSame(
            MovimentoMagazzino::REASON_CODE_COUNT_MISMATCH,
            $movimento->causale_codice
        );
    }
}

