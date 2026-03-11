<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable(true)->change();
            $table->string('prodotto_finale')->nullable(true)->change();
            $table->string('codice_lotto')->nullable(true)->change();
            $table->foreignId('preventivo_id')->nullable(true)->change();
            $table->string('stato')->default('bozza')->nullable(true)->change();
            $table->date('data_inizio')->nullable(true)->change();
            $table->date('data_fine')->nullable(true)->change();
            $table->foreignId('created_by')->nullable(true)->change();
        });
    }

    public function down(): void
    {   
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable(false)->change();
            $table->string('prodotto_finale')->nullable(false)->change();
            $table->string('codice_lotto')->nullable(false)->change();
            $table->foreignId('preventivo_id')->nullable(false)->change();
            $table->string('stato')->default('bozza')->nullable(false)->change();
            $table->date('data_inizio')->nullable(false)->change();
            $table->date('data_fine')->nullable(false)->change();
            $table->foreignId('created_by')->nullable(false)->change();
        });
    }
};
