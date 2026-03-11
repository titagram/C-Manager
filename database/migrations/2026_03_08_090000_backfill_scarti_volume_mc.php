<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('scarti')->update([
            'volume_mc' => DB::raw('ROUND((COALESCE(lunghezza_mm, 0) * COALESCE(larghezza_mm, 0) * COALESCE(spessore_mm, 0)) / 1000000000, 6)'),
        ]);
    }

    public function down(): void
    {
        // No-op: the previous persisted values were inconsistent and cannot be reconstructed safely.
    }
};
