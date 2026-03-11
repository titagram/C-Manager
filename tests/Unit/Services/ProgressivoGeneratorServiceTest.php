<?php

namespace Tests\Unit\Services;

use App\Models\Ordine;
use App\Models\Preventivo;
use App\Services\ProgressivoGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProgressivoGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressivoGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProgressivoGeneratorService::class);
    }

    public function test_generates_incremental_progressivi_by_entity_and_year(): void
    {
        $this->assertEquals(1, $this->service->next('preventivi', 2026));
        $this->assertEquals(2, $this->service->next('preventivi', 2026));
        $this->assertEquals(1, $this->service->next('ordini', 2026));
        $this->assertEquals(1, $this->service->next('preventivi', 2027));
    }

    public function test_bootstraps_from_existing_max_progressivo_including_soft_deleted(): void
    {
        $active = Preventivo::factory()->create([
            'anno' => 2026,
            'progressivo' => 5,
            'numero' => 'PRV-2026-0005',
        ]);

        $deleted = Preventivo::factory()->create([
            'anno' => 2026,
            'progressivo' => 8,
            'numero' => 'PRV-2026-0008',
        ]);
        $deleted->delete();

        $next = $this->service->next('preventivi', 2026);

        $this->assertEquals(9, $next);
        $this->assertNotNull($active->fresh());
    }

    public function test_models_use_centralized_sequence_generation(): void
    {
        $preventivo1 = Preventivo::factory()->create(['anno' => 2026]);
        $preventivo2 = Preventivo::factory()->create(['anno' => 2026]);
        $ordine1 = Ordine::factory()->create(['anno' => 2026]);

        $this->assertEquals(1, $preventivo1->progressivo);
        $this->assertEquals(2, $preventivo2->progressivo);
        $this->assertEquals(1, $ordine1->progressivo);

        $rows = DB::table('progressivi_annuali')
            ->orderBy('entita')
            ->get(['entita', 'anno', 'last_value'])
            ->map(fn ($row) => (array) $row)
            ->all();

        $this->assertEquals([
            ['entita' => 'ordini', 'anno' => 2026, 'last_value' => 1],
            ['entita' => 'preventivi', 'anno' => 2026, 'last_value' => 2],
        ], $rows);
    }
}
