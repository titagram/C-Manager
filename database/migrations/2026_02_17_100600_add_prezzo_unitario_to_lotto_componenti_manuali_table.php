<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotto_componenti_manuali', function (Blueprint $table) {
            $table->decimal('prezzo_unitario', 12, 4)
                ->nullable()
                ->after('quantita');
        });
    }

    public function down(): void
    {
        Schema::table('lotto_componenti_manuali', function (Blueprint $table) {
            $table->dropColumn('prezzo_unitario');
        });
    }
};
