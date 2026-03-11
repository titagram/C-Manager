<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotto_produzione_materiali', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lotto_produzione_id')->constrained('lotti_produzione')->cascadeOnDelete();
            $table->foreignId('lotto_materiale_id')->nullable()->constrained('lotti_materiale')->nullOnDelete();
            $table->foreignId('prodotto_id')->nullable()->constrained('prodotti')->nullOnDelete();
            $table->string('descrizione');
            $table->decimal('lunghezza_mm', 10, 2);
            $table->decimal('larghezza_mm', 10, 2);
            $table->decimal('spessore_mm', 10, 2);
            $table->integer('quantita_pezzi');
            $table->decimal('volume_mc', 12, 6);
            $table->integer('pezzi_per_asse')->nullable();
            $table->integer('assi_necessarie')->nullable();
            $table->decimal('scarto_per_asse_mm', 10, 2)->nullable();
            $table->decimal('scarto_totale_mm', 10, 2)->nullable();
            $table->decimal('scarto_percentuale', 5, 2)->nullable();
            $table->decimal('costo_materiale', 10, 2)->nullable();
            $table->decimal('prezzo_vendita', 10, 2)->nullable();
            $table->boolean('is_fitok')->default(false);
            $table->integer('ordine')->default(0);
            $table->timestamps();

            $table->index(['lotto_produzione_id', 'ordine']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_produzione_materiali');
    }
};
