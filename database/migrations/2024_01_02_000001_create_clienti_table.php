<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clienti', function (Blueprint $table) {
            $table->id();
            $table->string('ragione_sociale');
            $table->string('partita_iva', 11)->nullable()->unique();
            $table->string('codice_fiscale', 16)->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('citta')->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('ragione_sociale');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clienti');
    }
};
