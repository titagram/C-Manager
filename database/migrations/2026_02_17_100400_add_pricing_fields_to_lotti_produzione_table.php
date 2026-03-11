<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->decimal('prezzo_calcolato', 12, 2)->nullable()->after('peso_totale_kg');
            $table->decimal('ricarico_percentuale', 5, 2)->default(0)->after('prezzo_calcolato');
            $table->decimal('prezzo_finale_override', 12, 2)->nullable()->after('ricarico_percentuale');
            $table->decimal('prezzo_finale', 12, 2)->nullable()->after('prezzo_finale_override');
            $table->timestamp('prezzo_calcolato_at')->nullable()->after('prezzo_finale');
        });
    }

    public function down(): void
    {
        Schema::table('lotti_produzione', function (Blueprint $table) {
            $table->dropColumn([
                'prezzo_calcolato',
                'ricarico_percentuale',
                'prezzo_finale_override',
                'prezzo_finale',
                'prezzo_calcolato_at',
            ]);
        });
    }
};

