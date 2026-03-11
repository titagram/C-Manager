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
            $table->string('tipo_riga', 20)->default('sfuso')->after('lotto_produzione_id');
            $table->boolean('include_in_bom')->default(true)->after('tipo_riga');

            $table->index('tipo_riga');
            $table->index('include_in_bom');
        });

        DB::table('preventivo_righe')
            ->whereNotNull('lotto_produzione_id')
            ->update([
                'tipo_riga' => 'lotto',
                'include_in_bom' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('preventivo_righe', function (Blueprint $table) {
            $table->dropIndex(['tipo_riga']);
            $table->dropIndex(['include_in_bom']);
            $table->dropColumn([
                'tipo_riga',
                'include_in_bom',
            ]);
        });
    }
};

