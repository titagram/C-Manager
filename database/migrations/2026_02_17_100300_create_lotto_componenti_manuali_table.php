<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotto_componenti_manuali', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lotto_produzione_id')->constrained('lotti_produzione')->cascadeOnDelete();
            $table->foreignId('componente_costruzione_id')->constrained('componenti_costruzione')->cascadeOnDelete();
            $table->foreignId('prodotto_id')->nullable()->constrained('prodotti')->nullOnDelete();
            $table->decimal('quantita', 12, 4);
            $table->string('unita_misura', 10)->default('pz');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['lotto_produzione_id', 'componente_costruzione_id'], 'lotto_componenti_manuali_unique');
            $table->index('prodotto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_componenti_manuali');
    }
};

