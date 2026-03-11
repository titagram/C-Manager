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
            $table->foreignId('preventivo_id')->nullable(true)->change();
            $table->string('descrizione')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preventivo_righe', function (Blueprint $table) {
            $table->foreignId('preventivo_id')->nullable(false)->change();
            $table->string('descrizione')->nullable(false)->change();
        });
    }
};
