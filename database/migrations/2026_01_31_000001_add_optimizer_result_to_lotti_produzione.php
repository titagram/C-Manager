<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->json('optimizer_result')
                ->nullable()
                ->after('numero_univoco')
                ->comment('Risultati calcolo optimizer (piano di taglio JSON)');
        });
    }

    public function down(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dropColumn('optimizer_result');
        });
    }
};
