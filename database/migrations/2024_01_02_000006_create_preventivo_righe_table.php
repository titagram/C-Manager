<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preventivo_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventivo_id')->constrained('preventivi')->cascadeOnDelete();
            $table->foreignId('prodotto_id')->nullable()->constrained('prodotti');
            $table->string('descrizione');

            // Dimensioni input
            $table->decimal('lunghezza_mm', 10, 2)->nullable();
            $table->decimal('larghezza_mm', 10, 2)->nullable();
            $table->decimal('spessore_mm', 10, 2)->nullable();
            $table->integer('quantita')->default(1);

            // Risultati calcolo
            $table->decimal('superficie_mq', 12, 6)->nullable();
            $table->decimal('volume_mc', 12, 6)->nullable();
            $table->decimal('materiale_netto', 12, 4)->nullable();
            $table->decimal('coefficiente_scarto', 5, 4)->default(0.1000);
            $table->decimal('materiale_lordo', 12, 4)->nullable();

            // Prezzo
            $table->decimal('prezzo_unitario', 10, 4)->nullable();
            $table->decimal('totale_riga', 12, 2)->nullable();

            $table->integer('ordine')->default(0);
            $table->timestamps();

            $table->index('preventivo_id');
            $table->index('ordine');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preventivo_righe');
    }
};
