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
        // Crea tabella fornitori
        Schema::create('fornitori', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 20)->unique();
            $table->string('ragione_sociale');
            $table->string('partita_iva', 20)->nullable()->unique();
            $table->string('codice_fiscale', 20)->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('cap', 10)->nullable();
            $table->string('citta', 100)->nullable();
            $table->string('provincia', 5)->nullable();
            $table->string('nazione', 2)->default('IT'); // Codice ISO
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Aggiunge foreign key a lotti_materiale
        Schema::table('lotti_materiale', function (Blueprint $table) {
            $table->foreignId('fornitore_id')->nullable()->after('fornitore')
                ->constrained('fornitori')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lotti_materiale', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fornitore_id');
        });

        Schema::dropIfExists('fornitori');
    }
};
