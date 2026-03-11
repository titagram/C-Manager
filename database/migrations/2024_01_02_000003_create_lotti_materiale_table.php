<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotti_materiale', function (Blueprint $table) {
            $table->id();
            $table->string('codice_lotto')->unique();
            $table->foreignId('prodotto_id')->constrained('prodotti');
            $table->date('data_arrivo');
            $table->string('fornitore')->nullable();
            $table->string('numero_ddt')->nullable();
            $table->decimal('quantita_iniziale', 12, 4);

            // Campi specifici FITOK
            $table->string('fitok_certificato')->nullable();
            $table->date('fitok_data_trattamento')->nullable();
            $table->string('fitok_tipo_trattamento')->nullable();
            $table->string('fitok_paese_origine', 2)->nullable();

            // Dimensioni specifiche (per legname)
            $table->decimal('lunghezza_mm', 10, 2)->nullable();
            $table->decimal('larghezza_mm', 10, 2)->nullable();
            $table->decimal('spessore_mm', 10, 2)->nullable();

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('codice_lotto');
            $table->index('prodotto_id');
            $table->index('data_arrivo');
            $table->index(['fitok_certificato', 'fitok_data_trattamento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotti_materiale');
    }
};
