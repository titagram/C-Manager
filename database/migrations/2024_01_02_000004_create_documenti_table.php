<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documenti', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // ddt_ingresso, ddt_uscita, fattura, bolla_interna, rettifica
            $table->string('numero');
            $table->date('data');
            $table->foreignId('cliente_id')->nullable()->constrained('clienti');
            $table->string('fornitore')->nullable();
            $table->text('descrizione')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tipo', 'numero', 'data']);
            $table->index('tipo');
            $table->index('data');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documenti');
    }
};
