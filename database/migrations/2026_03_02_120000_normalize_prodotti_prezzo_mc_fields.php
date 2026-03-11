<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Canonical rule: for U.M. `mc`, keep both price columns aligned for legacy compatibility.
        DB::table('prodotti')
            ->where('unita_misura', 'mc')
            ->whereNull('prezzo_mc')
            ->whereNotNull('prezzo_unitario')
            ->update([
                'prezzo_mc' => DB::raw('prezzo_unitario'),
            ]);

        DB::table('prodotti')
            ->where('unita_misura', 'mc')
            ->whereNotNull('prezzo_mc')
            ->update([
                'prezzo_unitario' => DB::raw('prezzo_mc'),
            ]);

        // For non-volumetric products keep `prezzo_mc` empty to avoid ambiguity.
        DB::table('prodotti')
            ->where('unita_misura', '!=', 'mc')
            ->update([
                'prezzo_mc' => null,
            ]);
    }

    public function down(): void
    {
        // No-op: this migration normalizes existing price data and is intentionally irreversible.
    }
};
