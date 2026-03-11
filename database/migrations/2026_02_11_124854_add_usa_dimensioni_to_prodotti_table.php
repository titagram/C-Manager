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
        if (! Schema::hasColumn('prodotti', 'usa_dimensioni')) {
            Schema::table('prodotti', function (Blueprint $table) {
                $table->boolean('usa_dimensioni')->default(true);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('prodotti', 'usa_dimensioni')) {
            Schema::table('prodotti', function (Blueprint $table) {
                $table->dropColumn('usa_dimensioni');
            });
        }
    }
};
