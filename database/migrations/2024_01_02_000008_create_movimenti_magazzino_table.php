<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimenti_magazzino', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lotto_materiale_id')->constrained('lotti_materiale');
            $table->string('tipo'); // carico, scarico, rettifica_positiva, rettifica_negativa
            $table->decimal('quantita', 12, 4);
            $table->foreignId('documento_id')->nullable()->constrained('documenti');
            $table->foreignId('lotto_produzione_id')->nullable()->constrained('lotti_produzione');
            $table->text('causale')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('data_movimento');
            $table->timestamps();

            $table->index('lotto_materiale_id');
            $table->index('tipo');
            $table->index('data_movimento');
            $table->index('documento_id');
            $table->index('lotto_produzione_id');

            // Per query FITOK
            $table->index(['tipo', 'data_movimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimenti_magazzino');
    }
};
