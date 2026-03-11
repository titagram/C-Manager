<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->foreignId('ordine_id')->nullable()->after('preventivo_id')->constrained('ordini')->nullOnDelete();
            $table->foreignId('ordine_riga_id')->nullable()->after('ordine_id')->constrained('ordine_righe')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dropForeign(['ordine_id']);
            $table->dropForeign(['ordine_riga_id']);
            $table->dropColumn(['ordine_id', 'ordine_riga_id']);
        });
    }
};
