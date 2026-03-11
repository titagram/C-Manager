<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preventivi', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('cliente_id')->constrained('clienti');
            $table->date('data');
            $table->date('validita_fino')->nullable();
            $table->string('stato')->default('bozza'); // bozza, inviato, accettato, rifiutato, scaduto
            $table->text('descrizione')->nullable();

            // Snapshot della versione del motore di calcolo
            $table->string('engine_version', 20)->default('1.0.0');

            // Totali calcolati al momento del salvataggio (per storicizzazione)
            $table->decimal('totale_materiali', 12, 2)->default(0);
            $table->decimal('totale_lavorazioni', 12, 2)->default(0);
            $table->decimal('totale', 12, 2)->default(0);

            // Input JSON (per ricalcolo/audit)
            $table->json('input_snapshot')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('stato');
            $table->index('cliente_id');
            $table->index('data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preventivi');
    }
};
