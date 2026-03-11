<?php

namespace Database\Seeders;

use App\Models\Costruzione;
use Illuminate\Database\Seeder;

class CostruzioniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // 1. CASSA STANDARD (Chiusa)
            [
                'nome' => 'Cassa Standard',
                'slug' => 'cassa-standard',
                'richiede_lunghezza' => true,
                'richiede_larghezza' => true,
                'richiede_altezza' => true,
                'categoria' => 'cassa',
                'descrizione' => 'Cassa chiusa con pareti laterali esterne e testate interne.',
                'is_active' => true,
                'config' => [
                    'optimizer_key' => 'geometrica',
                ],
                'componenti' => [
                    [
                        'nome' => 'Fondo',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Coperchio',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Parete Lunga (Esterna)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L', // Copre tutta la lunghezza
                        'formula_larghezza' => 'H', // Alta quanto la cassa
                        'formula_quantita' => '2',
                    ],
                    [
                        'nome' => 'Parete Corta (Interna)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'W - (2 * T)', // Si accorcia per stare dentro
                        'formula_larghezza' => 'H',
                        'formula_quantita' => '2',
                        'is_internal' => true,
                    ],
                ],
            ],

            [
                'nome' => 'Cassa Standard Geometrica',
                'slug' => 'cassa-standard-geometrica',
                'richiede_lunghezza' => true,
                'richiede_larghezza' => true,
                'richiede_altezza' => true,
                'categoria' => 'cassa',
                'descrizione' => 'Alias geometrico esplicito della cassa standard per compatibilità rollout.',
                'is_active' => true,
                'config' => [
                    'optimizer_key' => 'geometrica',
                ],
                'componenti' => [
                    [
                        'nome' => 'Fondo',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Coperchio',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Parete Lunga (Esterna)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'H',
                        'formula_quantita' => '2',
                    ],
                    [
                        'nome' => 'Parete Corta (Interna)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'W - (2 * T)',
                        'formula_larghezza' => 'H',
                        'formula_quantita' => '2',
                        'is_internal' => true,
                    ],
                ],
            ],

            [
                'nome' => 'Cassa SP25',
                'slug' => 'cassa-sp25',
                'richiede_lunghezza' => true,
                'richiede_larghezza' => true,
                'richiede_altezza' => true,
                'categoria' => 'cassa',
                'descrizione' => 'Cassa con routine Excel SP25 e profili materiali base/fondo.',
                'is_active' => true,
                'config' => [
                    'optimizer_key' => 'excel_sp25',
                ],
                'componenti' => [
                    [
                        'nome' => 'Fondo',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Coperchio',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Parete Lunga (Esterna)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'H',
                        'formula_quantita' => '2',
                    ],
                    [
                        'nome' => 'Parete Corta (Interna)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'W - (2 * T)',
                        'formula_larghezza' => 'H',
                        'formula_quantita' => '2',
                        'is_internal' => true,
                    ],
                ],
            ],

            [
                'nome' => 'Cassa SP25 Fondo 40',
                'slug' => 'cassa-sp25-fondo40',
                'richiede_lunghezza' => true,
                'richiede_larghezza' => true,
                'richiede_altezza' => true,
                'categoria' => 'cassa',
                'descrizione' => 'Cassa con routine Excel SP25 Fondo 40 e profili materiali distinti.',
                'is_active' => true,
                'config' => [
                    'optimizer_key' => 'excel_sp25_fondo40',
                ],
                'componenti' => [
                    [
                        'nome' => 'Fondo',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Coperchio',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Parete Lunga (Esterna)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'H',
                        'formula_quantita' => '2',
                    ],
                    [
                        'nome' => 'Parete Corta (Interna)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'W - (2 * T)',
                        'formula_larghezza' => 'H',
                        'formula_quantita' => '2',
                        'is_internal' => true,
                    ],
                ],
            ],

            // 2. GABBIA STANDARD (Aperta)
            [
                'nome' => 'Gabbia Standard',
                'slug' => 'gabbia-standard',
                'richiede_lunghezza' => true,
                'richiede_larghezza' => true,
                'richiede_altezza' => true,
                'categoria' => 'gabbia',
                'descrizione' => 'Imballo a gabbia con montanti e traverse.',
                'is_active' => true,
                'config' => [], // Added default empty config
                'componenti' => [
                    [
                        'nome' => 'Fondo',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Coperchio',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Montanti Verticali',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'H', // Alti quanto la gabbia
                        'formula_larghezza' => '100', // Larghezza fissa tavola (es. 10cm)
                        'formula_quantita' => '4', // 4 angoli
                    ],
                    [
                        'nome' => 'Traverse Lunghe',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => '100', // Larghezza fissa tavola
                        'formula_quantita' => '4', // 2 sopra, 2 sotto
                    ],
                    [
                        'nome' => 'Traverse Corte (Interne)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'W - (2 * T)',
                        'formula_larghezza' => '100', // Larghezza fissa tavola
                        'formula_quantita' => '4', // 2 sopra, 2 sotto
                        'is_internal' => true,
                    ],
                    [
                        'nome' => 'Rinforzi Diagonali',
                        'tipo_dimensionamento' => 'MANUALE', // Spesso variano, meglio manuali o formula complessa
                        'formula_lunghezza' => null,
                        'formula_larghezza' => '100',
                        'formula_quantita' => '2',
                    ],
                ],
            ],

            // 3. BANCALE (Pallet)
            [
                'nome' => 'Bancale Standard 2 Vie',
                'slug' => 'bancale-standard',
                'richiede_lunghezza' => true,
                'richiede_larghezza' => true,
                'richiede_altezza' => false, // Di solito l'altezza è standard data dai morali
                'categoria' => 'bancale',
                'descrizione' => 'Pallet a due vie con morali interi.',
                'is_active' => true,
                'config' => [], // Added default empty config
                'componenti' => [
                    [
                        'nome' => 'Morali (Traverse di base)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L', // Lungo tutto il bancale
                        'formula_larghezza' => '80', // Larghezza standard morale (es. 8cm)
                        'formula_quantita' => '3', // SX, Centro, DX
                    ],
                    [
                        'nome' => 'Doghe Piano Superiore',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'W', // Larghe come il bancale
                        'formula_larghezza' => '100', // Larghezza doga standard
                        // FORMULA AVANZATA: Calcola quante doghe servono in base alla lunghezza
                        // Assumiamo Doga 100mm + Spazio 30mm = 130mm passo
                        'formula_quantita' => 'ceil(L / 130)',
                    ],
                    [
                        'nome' => 'Doghe Fondo (Pattini)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'W',
                        'formula_larghezza' => '100',
                        'formula_quantita' => '3', // Sotto i morali
                    ],
                ],
            ],

            // 4. CASSA 2 VIE (Con Legacci/Skids sotto)
            [
                'nome' => 'Cassa 2 Vie (Con Legacci)',
                'slug' => 'cassa-2-vie-standard',
                'richiede_lunghezza' => true,
                'richiede_larghezza' => true,
                'richiede_altezza' => true,
                'categoria' => 'cassa',
                'descrizione' => 'Cassa standard rialzata da legacci per inforcamento.',
                'is_active' => true,
                'config' => [
                    'optimizer_key' => 'geometrica',
                ],
                'componenti' => [
                    [
                        'nome' => 'Fondo',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                    [
                        'nome' => 'Legacci (Basamento)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'W', // Solitamente i legacci vanno nel senso della profondità
                        'formula_larghezza' => '80', // Larghezza morale
                        'formula_quantita' => '3', // Numero legacci standard
                    ],
                    [
                        'nome' => 'Parete Lunga',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'H',
                        'formula_quantita' => '2',
                    ],
                    [
                        'nome' => 'Parete Corta (Interna)',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'W - (2 * T)',
                        'formula_larghezza' => 'H',
                        'formula_quantita' => '2',
                        'is_internal' => true,
                    ],
                    [
                        'nome' => 'Coperchio',
                        'tipo_dimensionamento' => 'CALCOLATO',
                        'formula_lunghezza' => 'L',
                        'formula_larghezza' => 'W',
                        'formula_quantita' => '1',
                    ],
                ],
            ],
        ];

        // LOGICA DI INSERIMENTO
        foreach ($data as $costruzioneData) {
            // Estraiamo i componenti dall'array per gestirli separatamente
            $componenti = $costruzioneData['componenti'] ?? [];
            unset($costruzioneData['componenti']);

            // Creiamo o aggiorniamo la Costruzione
            $costruzione = Costruzione::updateOrCreate(
                ['slug' => $costruzioneData['slug']], // Chiave univoca di ricerca
                $costruzioneData // Dati da aggiornare
            );

            $costruzione->componenti()
                ->whereNotIn('nome', array_map(
                    fn (array $componente): string => (string) $componente['nome'],
                    $componenti
                ))
                ->delete();

            // Creiamo o aggiorniamo i Componenti figli
            foreach ($componenti as $componenteData) {
                $costruzione->componenti()->updateOrCreate(
                    // Cerchiamo per nome ALL'INTERNO di questa costruzione
                    // (così "Fondo" della Cassa non sovrascrive "Fondo" della Gabbia)
                    [
                        'costruzione_id' => $costruzione->id,
                        'nome' => $componenteData['nome'],
                    ],
                    $componenteData
                );
            }
        }
    }
}
