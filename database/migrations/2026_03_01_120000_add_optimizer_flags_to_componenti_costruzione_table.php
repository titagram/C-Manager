<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('componenti_costruzione', function (Blueprint $table) {
            $table->boolean('is_internal')
                ->default(false)
                ->after('formula_quantita')
                ->comment('Flag futuro optimizer: true se componente interno');

            $table->boolean('allow_rotation')
                ->default(false)
                ->after('is_internal')
                ->comment('Flag futuro optimizer: true se rotazione consentita');
        });
    }

    public function down(): void
    {
        Schema::table('componenti_costruzione', function (Blueprint $table) {
            $table->dropColumn(['is_internal', 'allow_rotation']);
        });
    }
};

