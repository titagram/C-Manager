<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;

class HealthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Health::checks([
            // Monitor database connection
            DatabaseCheck::new(),

            // Warn if disk usage is high (e.g. backups filling up)
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(80)
                ->failWhenUsedSpaceIsAbovePercentage(90),

            // Ensure debug mode is off in production
            DebugModeCheck::new(),

            // Ensure we are in the correct environment
            EnvironmentCheck::new(),

            // Check if app is optimized (cached routes/config) in production
            OptimizedAppCheck::new(),
        ]);
    }
}
