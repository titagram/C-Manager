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
        Schema::create('costruzioni', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50); // 'cassa', 'pallet', 'bancale'
            $table->string('nome');
            $table->text('descrizione')->nullable();
            $table->json('config'); // {lati: 4, ha_fondo: true, ha_coperchio: false, assemblaggio: 'chiodato'}
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tipo');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costruzioni');
    }
};
