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
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->foreignId('costruzione_id')->nullable()->after('prodotto_finale')->constrained('costruzioni')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dropForeign(['costruzione_id']);
            $table->dropColumn('costruzione_id');
        });
    }
};
