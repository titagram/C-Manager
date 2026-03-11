<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preventivo_righe', function (Blueprint $table) {
            $table->dropForeign(['lotto_produzione_id']);

            $table->foreign('lotto_produzione_id')
                ->references('id')
                ->on('lotti_produzione')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('preventivo_righe', function (Blueprint $table) {
            $table->dropForeign(['lotto_produzione_id']);

            $table->foreign('lotto_produzione_id')
                ->references('id')
                ->on('lotti_produzione')
                ->nullOnDelete();
        });
    }
};
