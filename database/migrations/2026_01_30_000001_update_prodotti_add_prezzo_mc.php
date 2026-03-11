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
        Schema::table('prodotti', function (Blueprint $table) {
            $table->decimal('prezzo_mc', 10, 2)
                ->nullable()
                ->after('prezzo_unitario')
                ->comment('Prezzo al metro cubo (per costruzioni)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prodotti', function (Blueprint $table) {
            $table->dropColumn('prezzo_mc');
        });
    }
};
