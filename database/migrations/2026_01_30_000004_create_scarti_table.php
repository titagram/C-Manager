<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scarti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lotto_produzione_id')->constrained('lotti_produzione')->cascadeOnDelete();
            $table->foreignId('lotto_materiale_id')->constrained('lotti_materiale')->cascadeOnDelete();
            $table->decimal('lunghezza_mm', 10, 2);
            $table->decimal('larghezza_mm', 10, 2);
            $table->decimal('spessore_mm', 10, 2);
            $table->decimal('volume_mc', 12, 6);
            $table->boolean('riutilizzabile')->default(false);
            $table->boolean('riutilizzato')->default(false);
            $table->foreignId('riutilizzato_in_lotto_id')->nullable()->constrained('lotti_produzione')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('riutilizzabile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scarti');
    }
};
