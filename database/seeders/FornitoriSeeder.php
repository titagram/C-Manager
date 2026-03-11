<?php

namespace Database\Seeders;

use App\Models\Fornitore;
use Illuminate\Database\Seeder;

class FornitoriSeeder extends Seeder
{
    public function run(): void
    {
        // Fornitori estratti dall'analisi Excel (registro FITOK)
        $fornitori = [
            [
                'codice' => 'SPARBER',
                'ragione_sociale' => 'Sparber Holz GmbH',
                'nazione' => 'AT',
                'citta' => 'Innsbruck',
                'note' => 'Fornitore principale tavole abete - Austria',
            ],
            [
                'codice' => 'FORNONI',
                'ragione_sociale' => 'Fornoni Legnami Srl',
                'nazione' => 'IT',
                'citta' => 'Brescia',
                'provincia' => 'BS',
                'note' => 'Fornitore tavole abete 55x75',
            ],
            [
                'codice' => 'JUNG',
                'ragione_sociale' => 'Jung Holzhandel GmbH',
                'nazione' => 'DE',
                'citta' => 'München',
                'note' => 'Fornitore tavole 23x80 - Germania',
            ],
            [
                'codice' => 'HIRT',
                'ragione_sociale' => 'Hirt Holzindustrie AG',
                'nazione' => 'AT',
                'citta' => 'Salzburg',
                'note' => 'Fornitore tavole 23x80 - Austria',
            ],
            [
                'codice' => 'SAGE-HIRT',
                'ragione_sociale' => 'Sage Hirt Holz GmbH',
                'nazione' => 'AT',
                'citta' => 'Graz',
                'note' => 'Fornitore tavole 28x35',
            ],
            [
                'codice' => 'BAUR',
                'ragione_sociale' => 'Baur Holzwerke GmbH',
                'nazione' => 'DE',
                'citta' => 'Stuttgart',
                'note' => 'Fornitore tavole 17x75',
            ],
            [
                'codice' => 'LESOTEKA',
                'ragione_sociale' => 'Lesoteka d.o.o.',
                'nazione' => 'SI',
                'citta' => 'Ljubljana',
                'note' => 'Fornitore tavole 35x95 - Slovenia',
            ],
            [
                'codice' => 'KAML-HUBER',
                'ragione_sociale' => 'Kaml & Huber Holz OG',
                'nazione' => 'AT',
                'citta' => 'Klagenfurt',
                'note' => 'Fornitore tavole 22x75',
            ],
        ];

        foreach ($fornitori as $fornitore) {
            Fornitore::updateOrCreate(
                ['codice' => $fornitore['codice']],
                $fornitore
            );
        }
    }
}
