<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prodotti', function (Blueprint $table) {
            $table->id();
            $table->string('codice')->unique();
            $table->string('nome');
            $table->text('descrizione')->nullable();
            $table->string('unita_misura'); // pz, mq, mc, ml, kg
            $table->string('categoria'); // legname, pannello, ferramenta, altro
            $table->boolean('soggetto_fitok')->default(false);
            $table->decimal('prezzo_unitario', 10, 4)->nullable();
            $table->decimal('coefficiente_scarto', 5, 4)->default(0.1000); // 10%
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('codice');
            $table->index('categoria');
            $table->index('soggetto_fitok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prodotti');
    }
};
