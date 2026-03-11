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
        Schema::table('lotti_produzione', function (Blueprint $table) {
            // Dimensioni cassa/gabbia in centimetri
            $table->decimal('larghezza_cm', 8, 2)->nullable()->after('prodotto_finale');
            $table->decimal('profondita_cm', 8, 2)->nullable()->after('larghezza_cm');
            $table->decimal('altezza_cm', 8, 2)->nullable()->after('profondita_cm');
            
            // Tipo prodotto (es. CASSA SP 25, GABBIA SP 20)
            $table->string('tipo_prodotto', 50)->nullable()->after('altezza_cm');
            
            // Spessori specifici (per casse con fondo diverso)
            $table->decimal('spessore_base_mm', 6, 2)->nullable()->after('tipo_prodotto');
            $table->decimal('spessore_fondo_mm', 6, 2)->nullable()->after('spessore_base_mm');
            
            // Quantità e parametri produzione
            $table->integer('numero_pezzi')->default(1)->after('spessore_fondo_mm');
            $table->string('numero_univoco', 10)->nullable()->after('numero_pezzi');
            
            // Volume e peso calcolati
            $table->decimal('volume_totale_mc', 10, 6)->nullable()->after('numero_univoco');
            $table->decimal('peso_kg_mc', 6, 2)->default(360)->after('volume_totale_mc');
            $table->decimal('peso_totale_kg', 10, 2)->nullable()->after('peso_kg_mc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dropColumn([
                'larghezza_cm',
                'profondita_cm',
                'altezza_cm',
                'tipo_prodotto',
                'spessore_base_mm',
                'spessore_fondo_mm',
                'numero_pezzi',
                'numero_univoco',
                'volume_totale_mc',
                'peso_kg_mc',
                'peso_totale_kg',
            ]);
        });
    }
};

