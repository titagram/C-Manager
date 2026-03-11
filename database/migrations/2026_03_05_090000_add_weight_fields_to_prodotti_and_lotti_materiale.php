<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('prodotti', 'peso_specifico_kg_mc')) {
            Schema::table('prodotti', function (Blueprint $table) {
                $table->decimal('peso_specifico_kg_mc', 10, 3)
                    ->nullable()
                    ->after('spessore_mm')
                    ->comment('Peso specifico in kg/m3 per prodotti volumetrici');
            });
        }

        if (!Schema::hasColumn('lotti_materiale', 'peso_totale_kg')) {
            Schema::table('lotti_materiale', function (Blueprint $table) {
                $table->decimal('peso_totale_kg', 12, 3)
                    ->nullable()
                    ->after('quantita_iniziale')
                    ->comment('Peso totale lotto in kg');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lotti_materiale', 'peso_totale_kg')) {
            Schema::table('lotti_materiale', function (Blueprint $table) {
                $table->dropColumn('peso_totale_kg');
            });
        }

        if (Schema::hasColumn('prodotti', 'peso_specifico_kg_mc')) {
            Schema::table('prodotti', function (Blueprint $table) {
                $table->dropColumn('peso_specifico_kg_mc');
            });
        }
    }
};

