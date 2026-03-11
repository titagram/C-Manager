<?php

namespace Tests\Feature\Commands;

use App\Enums\StatoPreventivo;
use App\Models\Preventivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirePreventiviTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_marks_inviato_preventivo_as_scaduto(): void
    {
        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::INVIATO,
            'validita_fino' => now()->subDays(5),
        ]);

        $this->artisan('preventivi:expire')
            ->expectsOutputToContain('Preventivi scaduti: 1')
            ->assertExitCode(0);

        $this->assertEquals(StatoPreventivo::SCADUTO, $preventivo->fresh()->stato);
    }

    public function test_expire_does_not_touch_accettato_preventivo(): void
    {
        $preventivo = Preventivo::factory()->create([
            'stato' => StatoPreventivo::ACCETTATO,
            'validita_fino' => now()->subDays(5),
        ]);

        $this->artisan('preventivi:expire')
            ->expectsOutputToContain('Preventivi scaduti: 0')
            ->assertExitCode(0);

        $this->assertEquals(StatoPreventivo::ACCETTATO, $preventivo->fresh()->stato);
    }
}