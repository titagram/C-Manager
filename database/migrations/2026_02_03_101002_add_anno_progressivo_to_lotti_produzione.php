<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->smallInteger('anno')->nullable()->after('codice_lotto');
            $table->integer('progressivo')->nullable()->after('anno');
        });

        // Migrate existing data: extract anno and progressivo from 'codice_lotto' (format: LP-YYYY-NNNN)
        DB::table('lotti_produzione')->whereNotNull('codice_lotto')->orderBy('id')->chunk(100, function ($lotti) {
            foreach ($lotti as $lotto) {
                if (preg_match('/^LP-(\d{4})-(\d+)$/', $lotto->codice_lotto, $matches)) {
                    DB::table('lotti_produzione')
                        ->where('id', $lotto->id)
                        ->update([
                            'anno' => (int) $matches[1],
                            'progressivo' => (int) $matches[2],
                        ]);
                }
            }
        });

        // Make columns not nullable and add unique index
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->smallInteger('anno')->nullable(false)->change();
            $table->integer('progressivo')->nullable(false)->change();
            $table->unique(['anno', 'progressivo'], 'lotti_produzione_anno_progressivo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dropUnique('lotti_produzione_anno_progressivo_unique');
            $table->dropColumn(['anno', 'progressivo']);
        });
    }
};
