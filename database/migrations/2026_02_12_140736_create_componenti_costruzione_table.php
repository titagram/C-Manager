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
        Schema::create('componenti_costruzione', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costruzione_id')->constrained('costruzioni')->cascadeOnDelete();
            
            $table->string('nome'); // Es: "Parete Esterna (A)", "Parete Interna (B)"
            
            // 'CALCOLATO' (usa formule) o 'MANUALE' (utente inserisce misure nel lotto)
            $table->string('tipo_dimensionamento')->default('CALCOLATO'); 

            // LE FORMULE (Variabili: L, W, H, T)
            // Es: "L" oppure "W - (2 * T)"
            $table->string('formula_lunghezza')->nullable(); 
            $table->string('formula_larghezza')->nullable(); 
            $table->string('formula_quantita')->default('1'); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('componenti_costruzione');
    }
};
