<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom', function (Blueprint $table) {
            $table->smallInteger('anno')->nullable()->after('codice');
            $table->integer('progressivo')->nullable()->after('anno');
        });

        // Migrate existing data: extract anno and progressivo from 'codice' (format: BOM-YYYY-NNNN)
        DB::table('bom')->whereNotNull('codice')->orderBy('id')->chunk(100, function ($boms) {
            foreach ($boms as $bom) {
                if (preg_match('/^BOM-(\d{4})-(\d+)$/', $bom->codice, $matches)) {
                    DB::table('bom')
                        ->where('id', $bom->id)
                        ->update([
                            'anno' => (int) $matches[1],
                            'progressivo' => (int) $matches[2],
                        ]);
                }
            }
        });

        // Make columns not nullable and add unique index
        Schema::table('bom', function (Blueprint $table) {
            $table->smallInteger('anno')->nullable(false)->change();
            $table->integer('progressivo')->nullable(false)->change();
            $table->unique(['anno', 'progressivo'], 'bom_anno_progressivo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bom', function (Blueprint $table) {
            $table->dropUnique('bom_anno_progressivo_unique');
            $table->dropColumn(['anno', 'progressivo']);
        });
    }
};
