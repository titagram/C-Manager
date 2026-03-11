<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->decimal('fitok_percentuale', 5, 2)->nullable()->after('peso_totale_kg');
            $table->decimal('fitok_volume_mc', 12, 6)->nullable()->after('fitok_percentuale');
            $table->decimal('non_fitok_volume_mc', 12, 6)->nullable()->after('fitok_volume_mc');
            $table->timestamp('fitok_calcolato_at')->nullable()->after('non_fitok_volume_mc');
        });
    }

    public function down(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dropColumn([
                'fitok_percentuale',
                'fitok_volume_mc',
                'non_fitok_volume_mc',
                'fitok_calcolato_at',
            ]);
        });
    }
};
