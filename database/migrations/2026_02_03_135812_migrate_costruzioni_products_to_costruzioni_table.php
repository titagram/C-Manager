<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrate products with construction categories (cassa, gabbia, legaccio, bancale)
     * to the costruzioni table, then delete them from prodotti.
     */
    public function up(): void
    {
        // Get products with construction categories using raw query to avoid enum casting
        $costruzioniProdotti = DB::table('prodotti')
            ->whereIn('categoria', ['cassa', 'gabbia', 'legaccio', 'bancale'])
            ->get();

        foreach ($costruzioniProdotti as $prodotto) {
            // Check if a costruzione with same name already exists
            $exists = DB::table('costruzioni')
                ->where('nome', $prodotto->nome)
                ->where('tipo', $prodotto->categoria)
                ->exists();

            if (!$exists) {
                // Create corresponding costruzione with empty config
                DB::table('costruzioni')->insert([
                    'tipo' => $prodotto->categoria,
                    'nome' => $prodotto->nome,
                    'descrizione' => $prodotto->descrizione,
                    'config' => '{}', // Empty JSON object
                    'is_active' => $prodotto->is_active ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Delete products with construction categories from prodotti table
        DB::table('prodotti')
            ->whereIn('categoria', ['cassa', 'gabbia', 'legaccio', 'bancale'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration as we don't have enough data
        // to restore the original prodotti records
    }
};
