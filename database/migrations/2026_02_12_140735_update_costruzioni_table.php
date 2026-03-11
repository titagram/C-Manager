<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('costruzioni', function (Blueprint $table) {
            $table->renameColumn('tipo', 'categoria');
            $table->string('slug')->nullable()->after('nome')->index();
            $table->boolean('richiede_lunghezza')->default(true)->after('config');
            $table->boolean('richiede_larghezza')->default(true)->after('richiede_lunghezza');
            $table->boolean('richiede_altezza')->default(true)->after('richiede_larghezza');
            $table->unique('nome');
        });

        // Initialize slug for existing records
        DB::table('costruzioni')->get()->each(function ($costruzione) {
            DB::table('costruzioni')
                ->where('id', $costruzione->id)
                ->update(['slug' => Str::slug($costruzione->nome)]);
        });

        // Make slug non-nullable
        Schema::table('costruzioni', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('costruzioni', function (Blueprint $table) {
            $table->dropUnique(['nome']);
            $table->dropColumn(['slug', 'richiede_lunghezza', 'richiede_larghezza', 'richiede_altezza']);
            $table->renameColumn('categoria', 'tipo');
        });
    }
};
