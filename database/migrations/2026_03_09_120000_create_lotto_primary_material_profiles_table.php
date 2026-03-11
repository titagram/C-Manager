<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotto_primary_material_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lotto_produzione_id')->constrained('lotti_produzione')->cascadeOnDelete();
            $table->string('profile_key', 50);
            $table->foreignId('prodotto_id')->constrained('prodotti')->cascadeOnDelete();
            $table->unsignedInteger('ordine')->default(0);
            $table->timestamps();

            $table->unique(['lotto_produzione_id', 'profile_key'], 'lotto_primary_profiles_unique');
            $table->index(['lotto_produzione_id', 'ordine'], 'lotto_primary_profiles_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_primary_material_profiles');
    }
};
