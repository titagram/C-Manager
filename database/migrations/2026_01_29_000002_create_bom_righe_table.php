<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bom')->cascadeOnDelete();
            $table->foreignId('prodotto_id')->nullable()->constrained('prodotti')->nullOnDelete();
            $table->string('descrizione')->nullable();
            $table->decimal('quantita', 12, 4);
            $table->string('unita_misura')->default('MC');
            $table->decimal('coefficiente_scarto', 5, 4)->default(0.10);
            $table->boolean('is_fitok_required')->default(false);
            $table->unsignedInteger('ordine')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            // bom_id and prodotto_id already have indexes from constrained()
            $table->index('ordine');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_righe');
    }
};
