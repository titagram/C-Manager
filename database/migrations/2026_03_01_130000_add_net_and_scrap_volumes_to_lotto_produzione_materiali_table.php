<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotto_produzione_materiali', function (Blueprint $table) {
            $table->decimal('volume_netto_mc', 12, 6)
                ->nullable()
                ->after('volume_mc');

            $table->decimal('volume_scarto_mc', 12, 6)
                ->nullable()
                ->after('volume_netto_mc');
        });
    }

    public function down(): void
    {
        Schema::table('lotto_produzione_materiali', function (Blueprint $table) {
            $table->dropColumn(['volume_netto_mc', 'volume_scarto_mc']);
        });
    }
};

