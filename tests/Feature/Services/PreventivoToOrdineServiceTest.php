<?php

namespace Tests\Feature\Services;

use App\Enums\StatoOrdine;
use App\Enums\StatoPreventivo;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Services\PreventivoToOrdineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventivoToOrdineServiceTest extends TestCase
{
    use RefreshDatabase;

    private PreventivoToOrdineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PreventivoToOrdineService::class);
    }

    public function test_converts_preventivo_to_ordine(): void
    {
        $preventivo = Preventivo::factory()->accettato()->create(['totale' => 1500.00]);
        PreventivoRiga::factory()->count(2)->create(['preventivo_id' => $preventivo->id]);

        $ordine = $this->service->convert($preventivo);

        $this->assertNotNull($ordine);
        $this->assertEquals($preventivo->cliente_id, $ordine->cliente_id);
        $this->assertEquals($preventivo->id, $ordine->preventivo_id);
        $this->assertEquals(StatoOrdine::CONFERMATO, $ordine->stato);
        $this->assertCount(2, $ordine->righe);
    }

    public function test_throws_exception_for_non_accettato_preventivo(): void
    {
        $preventivo = Preventivo::factory()->bozza()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->convert($preventivo);
    }

    public function test_throws_exception_if_ordine_already_exists(): void
    {
        $preventivo = Preventivo::factory()->accettato()->create();
        PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
        ]);
        $this->service->convert($preventivo);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->convert($preventivo);
    }
}
