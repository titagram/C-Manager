<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->smallInteger('anno')->nullable()->after('numero');
            $table->integer('progressivo')->nullable()->after('anno');
        });

        // Migrate existing data: extract anno and progressivo from 'numero' (format: ORD-YYYY-NNNN)
        DB::table('ordini')->whereNotNull('numero')->orderBy('id')->chunk(100, function ($ordini) {
            foreach ($ordini as $ordine) {
                if (preg_match('/^ORD-(\d{4})-(\d+)$/', $ordine->numero, $matches)) {
                    DB::table('ordini')
                        ->where('id', $ordine->id)
                        ->update([
                            'anno' => (int) $matches[1],
                            'progressivo' => (int) $matches[2],
                        ]);
                }
            }
        });

        // Make columns not nullable and add unique index
        Schema::table('ordini', function (Blueprint $table) {
            $table->smallInteger('anno')->nullable(false)->change();
            $table->integer('progressivo')->nullable(false)->change();
            $table->unique(['anno', 'progressivo'], 'ordini_anno_progressivo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->dropUnique('ordini_anno_progressivo_unique');
            $table->dropColumn(['anno', 'progressivo']);
        });
    }
};
