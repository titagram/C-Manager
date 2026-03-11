<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CostruzioneSeeder extends Seeder
{
    /**
     * @deprecated Use CostruzioniSeeder. Kept as BC alias.
     */
    public function run(): void
    {
        $this->call(CostruzioniSeeder::class);
    }
}
