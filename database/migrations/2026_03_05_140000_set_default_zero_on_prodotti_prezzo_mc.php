<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('prodotti') || !Schema::hasColumn('prodotti', 'prezzo_mc')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE prodotti MODIFY prezzo_mc DECIMAL(10,2) NULL DEFAULT 0");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE prodotti ALTER COLUMN prezzo_mc SET DEFAULT 0");
            return;
        }

        // SQLite: alter default requires table rebuild; functional default is
        // guaranteed at model level for test/dev portability.
    }

    public function down(): void
    {
        if (!Schema::hasTable('prodotti') || !Schema::hasColumn('prodotti', 'prezzo_mc')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE prodotti MODIFY prezzo_mc DECIMAL(10,2) NULL DEFAULT NULL");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE prodotti ALTER COLUMN prezzo_mc DROP DEFAULT");
        }
    }
};
