<?php

namespace Tests\Feature\Seeders;

use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\StatoPreventivo;
use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use Database\Seeders\CostruzioniSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\FornitoriSeeder;
use Database\Seeders\ProdottiSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederCoherenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeders_create_a_placeholder_pair_and_an_operational_order_flow(): void
    {
        $this->seed([
            UserSeeder::class,
            ProdottiSeeder::class,
            FornitoriSeeder::class,
            CostruzioniSeeder::class,
            DemoDataSeeder::class,
        ]);

        $this->assertSame(1, Preventivo::query()->count());
        $this->assertSame(1, Ordine::query()->count());
        $this->assertSame(2, LottoProduzione::query()->count());
        $this->assertSame(1, PreventivoRiga::query()->whereNotNull('lotto_produzione_id')->count());

        $preventivo = Preventivo::query()->where('numero', 'PRV-2026-0001')->firstOrFail();
        $lottoPlaceholder = LottoProduzione::query()->where('codice_lotto', 'LP-2026-0001')->firstOrFail();
        $lottoOperativo = LottoProduzione::query()->where('codice_lotto', 'LP-2026-0002')->firstOrFail();
        $ordine = Ordine::query()->where('numero', 'ORD-2026-0001')->firstOrFail();
        $riga = PreventivoRiga::query()->whereNotNull('lotto_produzione_id')->firstOrFail();

        $this->assertSame($preventivo->id, $lottoPlaceholder->preventivo_id);
        $this->assertSame($preventivo->id, $riga->preventivo_id);
        $this->assertSame($lottoPlaceholder->id, $riga->lotto_produzione_id);
        $this->assertSame(StatoPreventivo::BOZZA, $preventivo->stato);
        $this->assertSame(StatoLottoProduzione::BOZZA, $lottoPlaceholder->stato);
        $this->assertTrue($lottoPlaceholder->isPlaceholderBozza());
        $this->assertSame($ordine->id, $lottoOperativo->ordine_id);
        $this->assertNull($lottoOperativo->preventivo_id);
        $this->assertSame(StatoOrdine::PRONTO, $ordine->stato);
        $this->assertSame(StatoLottoProduzione::COMPLETATO, $lottoOperativo->stato);
    }
}
