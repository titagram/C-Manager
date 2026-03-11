<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimenti_magazzino', function (Blueprint $table) {
            $table->string('causale_codice', 50)
                ->nullable()
                ->after('causale');

            $table->index('causale_codice');
        });
    }

    public function down(): void
    {
        Schema::table('movimenti_magazzino', function (Blueprint $table) {
            $table->dropIndex(['causale_codice']);
            $table->dropColumn('causale_codice');
        });
    }
};

