<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('preventivo_righe', function (Blueprint $table) {
            $table->foreignId('lotto_produzione_id')
                ->nullable()
                ->after('preventivo_id')
                ->constrained('lotti_produzione')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preventivo_righe', function (Blueprint $table) {
            $table->dropForeign(['lotto_produzione_id']);
            $table->dropColumn('lotto_produzione_id');
        });
    }
};
