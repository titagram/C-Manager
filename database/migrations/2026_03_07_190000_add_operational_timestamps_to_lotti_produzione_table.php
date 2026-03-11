<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dateTime('avviato_at')->nullable()->after('data_inizio');
            $table->dateTime('completato_at')->nullable()->after('data_fine');
        });
    }

    public function down(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dropColumn(['avviato_at', 'completato_at']);
        });
    }
};
