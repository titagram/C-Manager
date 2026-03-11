<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Per un fresh start, decommentare questa sezione:
        // $this->freshStart();
        
        $this->call([
            UserSeeder::class,
            ProdottiSeeder::class,
            FornitoriSeeder::class,
            CostruzioniSeeder::class,
            DemoDataSeeder::class,
        ]);
    }

    /**
     * Pulisce tutte le tabelle prima del seeding
     */
    private function freshStart(): void
    {
        Schema::disableForeignKeyConstraints();
        
        // Ordine inverso rispetto alle dipendenze
        DB::table('consumi_materiale')->truncate();
        DB::table('movimenti_magazzino')->truncate();
        DB::table('preventivo_righe')->truncate();
        DB::table('preventivi')->truncate();
        DB::table('lotti_produzione')->truncate();
        DB::table('lotti_materiale')->truncate();
        DB::table('prodotti')->truncate();
        DB::table('clienti')->truncate();
        DB::table('documenti')->truncate();
        // Non svuotiamo users per non perdere l'admin
        
        Schema::enableForeignKeyConstraints();
    }
}
