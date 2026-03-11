<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

class DebugDatabaseResetService
{
    public function resetWithSeed(): int
    {
        return Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);
    }
}
