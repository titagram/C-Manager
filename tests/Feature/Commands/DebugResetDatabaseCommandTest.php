<?php

namespace Tests\Feature\Commands;

use App\Models\User;
use App\Services\DebugDatabaseResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DebugResetDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_blocked_when_app_debug_false(): void
    {
        config()->set('app.debug', false);

        $admin = User::factory()->admin()->create();

        $resetService = Mockery::mock(DebugDatabaseResetService::class);
        $resetService->shouldReceive('resetWithSeed')->never();
        $this->app->instance(DebugDatabaseResetService::class, $resetService);

        $this->artisan('app:debug-reset-db', [
            '--confirmed' => true,
            '--requested-by' => $admin->id,
        ])
            ->expectsOutputToContain('APP_DEBUG deve essere TRUE')
            ->assertExitCode(1);
    }

    public function test_command_runs_migrate_fresh_seed_when_app_debug_true(): void
    {
        config()->set('app.debug', true);

        $admin = User::factory()->admin()->create();

        $resetService = Mockery::mock(DebugDatabaseResetService::class);
        $resetService->shouldReceive('resetWithSeed')
            ->once()
            ->andReturn(0);
        $this->app->instance(DebugDatabaseResetService::class, $resetService);

        $this->artisan('app:debug-reset-db', [
            '--confirmed' => true,
            '--requested-by' => $admin->id,
        ])
            ->expectsOutputToContain('Reset completato con successo.')
            ->assertExitCode(0);
    }
}
