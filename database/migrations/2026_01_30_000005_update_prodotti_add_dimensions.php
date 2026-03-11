<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prodotti', function (Blueprint $table) {
            $table->decimal('lunghezza_mm', 10, 2)
                ->nullable()
                ->after('coefficiente_scarto')
                ->comment('Lunghezza materiale in mm (per assi/materiali)');

            $table->decimal('larghezza_mm', 10, 2)
                ->nullable()
                ->after('lunghezza_mm')
                ->comment('Larghezza materiale in mm (per assi/materiali)');

            $table->decimal('spessore_mm', 10, 2)
                ->nullable()
                ->after('larghezza_mm')
                ->comment('Spessore materiale in mm (per assi/materiali)');
        });
    }

    public function down(): void
    {
        Schema::table('prodotti', function (Blueprint $table) {
            $table->dropColumn(['lunghezza_mm', 'larghezza_mm', 'spessore_mm']);
        });
    }
};
