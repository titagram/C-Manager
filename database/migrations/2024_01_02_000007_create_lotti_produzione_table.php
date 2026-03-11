<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotti_produzione', function (Blueprint $table) {
            $table->id();
            $table->string('codice_lotto')->unique();
            $table->foreignId('cliente_id')->constrained('clienti');
            $table->foreignId('preventivo_id')->nullable()->constrained('preventivi');
            $table->string('prodotto_finale');
            $table->text('descrizione')->nullable();
            $table->string('stato')->default('bozza'); // bozza, in_lavorazione, completato, annullato
            $table->date('data_inizio')->nullable();
            $table->date('data_fine')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('codice_lotto');
            $table->index('stato');
            $table->index('cliente_id');
            $table->index('data_inizio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotti_produzione');
    }
};
