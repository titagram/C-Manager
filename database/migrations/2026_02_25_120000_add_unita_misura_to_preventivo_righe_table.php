<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preventivo_righe', function (Blueprint $table) {
            $table->string('unita_misura', 10)
                ->nullable()
                ->after('prodotto_id');
            $table->index('unita_misura');
        });

        // Conservative backfill for historical rows created before unit-aware preventivo righe.
        DB::table('preventivo_righe')
            ->whereNull('unita_misura')
            ->update(['unita_misura' => 'mc']);
    }

    public function down(): void
    {
        Schema::table('preventivo_righe', function (Blueprint $table) {
            $table->dropIndex(['unita_misura']);
            $table->dropColumn('unita_misura');
        });
    }
};
