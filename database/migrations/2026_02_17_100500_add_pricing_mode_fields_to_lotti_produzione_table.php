<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->string('pricing_mode', 32)
                ->default('tariffa_mc')
                ->after('prezzo_calcolato');
            $table->decimal('tariffa_mc', 12, 2)
                ->nullable()
                ->after('pricing_mode');
            $table->json('pricing_snapshot')
                ->nullable()
                ->after('prezzo_calcolato_at');
        });
    }

    public function down(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_mode',
                'tariffa_mc',
                'pricing_snapshot',
            ]);
        });
    }
};
