<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumi_materiale', function (Blueprint $table) {
            $table->string('stato', 20)->default('pianificato')->after('movimento_id');
            $table->timestamp('opzionato_at')->nullable()->after('stato');
            $table->timestamp('consumato_at')->nullable()->after('opzionato_at');
            $table->timestamp('released_at')->nullable()->after('consumato_at');

            $table->index('stato');
            $table->index(['lotto_materiale_id', 'stato']);
        });

        // Backfill deterministic status for existing rows.
        DB::table('consumi_materiale')
            ->whereNotNull('movimento_id')
            ->update([
                'stato' => 'consumato',
                'consumato_at' => now(),
            ]);

        DB::table('consumi_materiale')
            ->whereNull('movimento_id')
            ->update(['stato' => 'pianificato']);
    }

    public function down(): void
    {
        Schema::table('consumi_materiale', function (Blueprint $table) {
            $table->dropIndex(['lotto_materiale_id', 'stato']);
            $table->dropIndex(['stato']);
            $table->dropColumn([
                'stato',
                'opzionato_at',
                'consumato_at',
                'released_at',
            ]);
        });
    }
};

