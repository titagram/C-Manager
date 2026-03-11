<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumi_materiale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lotto_produzione_id')->constrained('lotti_produzione')->cascadeOnDelete();
            $table->foreignId('lotto_materiale_id')->constrained('lotti_materiale');
            $table->foreignId('movimento_id')->nullable()->constrained('movimenti_magazzino');
            $table->decimal('quantita', 12, 4);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('lotto_produzione_id');
            $table->index('lotto_materiale_id');

            // Unique per evitare duplicati
            $table->unique(['lotto_produzione_id', 'lotto_materiale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumi_materiale');
    }
};
