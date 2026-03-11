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
        Schema::create('progressivi_annuali', function (Blueprint $table) {
            $table->id();
            $table->string('entita', 64);
            $table->smallInteger('anno');
            $table->unsignedInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['entita', 'anno'], 'progressivi_annuali_entita_anno_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progressivi_annuali');
    }
};
