<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preventivi', function (Blueprint $table) {
            $table->smallInteger('anno')->nullable()->after('numero');
            $table->integer('progressivo')->nullable()->after('anno');
        });

        // Migrate existing data: extract anno and progressivo from 'numero' (format: PRV-YYYY-NNNN)
        DB::table('preventivi')->whereNotNull('numero')->orderBy('id')->chunk(100, function ($preventivi) {
            foreach ($preventivi as $preventivo) {
                if (preg_match('/^PRV-(\d{4})-(\d+)$/', $preventivo->numero, $matches)) {
                    DB::table('preventivi')
                        ->where('id', $preventivo->id)
                        ->update([
                            'anno' => (int) $matches[1],
                            'progressivo' => (int) $matches[2],
                        ]);
                }
            }
        });

        // Make columns not nullable and add unique index
        Schema::table('preventivi', function (Blueprint $table) {
            $table->smallInteger('anno')->nullable(false)->change();
            $table->integer('progressivo')->nullable(false)->change();
            $table->unique(['anno', 'progressivo'], 'preventivi_anno_progressivo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('preventivi', function (Blueprint $table) {
            $table->dropUnique('preventivi_anno_progressivo_unique');
            $table->dropColumn(['anno', 'progressivo']);
        });
    }
};
