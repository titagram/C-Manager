<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotto_produzione_materiali', function (Blueprint $table) {
            $table->string('source_profile', 50)
                ->nullable()
                ->after('prodotto_id');
        });
    }

    public function down(): void
    {
        Schema::table('lotto_produzione_materiali', function (Blueprint $table) {
            $table->dropColumn('source_profile');
        });
    }
};
