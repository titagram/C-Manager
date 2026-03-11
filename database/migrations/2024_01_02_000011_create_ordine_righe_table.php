<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordine_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordine_id')->constrained('ordini')->cascadeOnDelete();
            $table->foreignId('prodotto_id')->nullable()->constrained('prodotti')->nullOnDelete();
            $table->string('descrizione');
            $table->string('tipo_costruzione')->nullable();
            $table->integer('larghezza_mm')->nullable();
            $table->integer('profondita_mm')->nullable();
            $table->integer('altezza_mm')->nullable();
            $table->string('riferimento_volume')->default('esterno');
            $table->integer('spessore_base_mm')->nullable();
            $table->integer('spessore_fondo_mm')->nullable();
            $table->integer('quantita')->default(1);
            $table->decimal('volume_mc_calcolato', 10, 6)->default(0);
            $table->decimal('volume_mc_finale', 10, 6)->default(0);
            $table->decimal('prezzo_mc', 10, 4)->default(0);
            $table->decimal('totale_riga', 12, 2)->default(0);
            $table->integer('ordine')->default(0);
            $table->timestamps();

            $table->index('ordine_id');
            $table->index('ordine');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordine_righe');
    }
};
