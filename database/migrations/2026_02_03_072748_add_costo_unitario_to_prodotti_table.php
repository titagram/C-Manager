<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prodotti', function (Blueprint $table) {
            // Add costo_unitario after prezzo_unitario
            $table->decimal('costo_unitario', 10, 4)->nullable()->after('prezzo_unitario');
        });
    }

    public function down(): void
    {
        Schema::table('prodotti', function (Blueprint $table) {
            $table->dropColumn('costo_unitario');
        });
    }
};
