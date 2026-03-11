<?php

namespace Database\Seeders;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Prodotto;
use Illuminate\Database\Seeder;

class ProdottiSeeder extends Seeder
{
    public function run(): void
    {
        $prodotti = [
            // =====================================================
            // MATERIE PRIME - Tavole Abete (da registro FITOK)
            // =====================================================
            [
                'codice' => 'MP-ABE-1795',
                'nome' => 'Tavola Abete 17x95',
                'descrizione' => 'Tavola abete MM 17X95 - FITOK',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::MATERIA_PRIMA,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 550.00,
                'coefficiente_scarto' => 0.10,
                'lunghezza_mm' => 4000,
                'larghezza_mm' => 95,
                'spessore_mm' => 17,
            ],
            [
                'codice' => 'MP-ABE-2275',
                'nome' => 'Tavola Abete 22x75',
                'descrizione' => 'Tavola abete MM 22X75 - FITOK',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::MATERIA_PRIMA,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 545.00,
                'coefficiente_scarto' => 0.10,
                'lunghezza_mm' => 4000,
                'larghezza_mm' => 75,
                'spessore_mm' => 22,
            ],
            [
                'codice' => 'MP-ABE-2380',
                'nome' => 'Tavola Abete 23x80+',
                'descrizione' => 'Tavola abete MM 23X80+ - FITOK',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::MATERIA_PRIMA,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 540.00,
                'coefficiente_scarto' => 0.10,
                'lunghezza_mm' => 4000,
                'larghezza_mm' => 80,
                'spessore_mm' => 23,
            ],
            [
                'codice' => 'MP-ABE-25100',
                'nome' => 'Tavola Abete 25x100',
                'descrizione' => 'Tavola abete MM 25X100 - FITOK',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::MATERIA_PRIMA,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 545.00,
                'coefficiente_scarto' => 0.10,
                'lunghezza_mm' => 4000,
                'larghezza_mm' => 100,
                'spessore_mm' => 25,
            ],
            [
                'codice' => 'MP-ABE-40100',
                'nome' => 'Tavola Abete 40x100',
                'descrizione' => 'Tavola abete MM 40X100 - FITOK',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::MATERIA_PRIMA,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 580.00,
                'coefficiente_scarto' => 0.08,
                'lunghezza_mm' => 4000,
                'larghezza_mm' => 100,
                'spessore_mm' => 40,
            ],
            [
                'codice' => 'MP-ABE-3575',
                'nome' => 'Tavola Abete 35x75',
                'descrizione' => 'Tavola abete MM 35X75 - FITOK',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::MATERIA_PRIMA,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 545.00,
                'coefficiente_scarto' => 0.10,
                'lunghezza_mm' => 4000,
                'larghezza_mm' => 75,
                'spessore_mm' => 35,
            ],
            [
                'codice' => 'MP-ABE-3595',
                'nome' => 'Tavola Abete 35x95',
                'descrizione' => 'Tavola abete MM 35X95 - FITOK',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::MATERIA_PRIMA,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 560.00,
                'coefficiente_scarto' => 0.10,
                'lunghezza_mm' => 4000,
                'larghezza_mm' => 95,
                'spessore_mm' => 35,
            ],
            [
                'codice' => 'MP-ABE-35118',
                'nome' => 'Tavola Abete 35x118',
                'descrizione' => 'Tavola abete MM 35X118 - FITOK',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::MATERIA_PRIMA,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 570.00,
                'coefficiente_scarto' => 0.10,
                'lunghezza_mm' => 4000,
                'larghezza_mm' => 118,
                'spessore_mm' => 35,
            ],
            [
                'codice' => 'MP-ABE-5575',
                'nome' => 'Tavola Abete 55x75',
                'descrizione' => 'Tavola abete MM 55X75 - FITOK',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::MATERIA_PRIMA,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 580.00,
                'coefficiente_scarto' => 0.08,
                'lunghezza_mm' => 4000,
                'larghezza_mm' => 75,
                'spessore_mm' => 55,
            ],

            // =====================================================
            // SEMILAVORATI - Assi lavorate
            // =====================================================
            [
                'codice' => 'ASS-230x10',
                'nome' => 'Asse 230x10',
                'descrizione' => 'Asse lavorata 230x10x4 cm',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::ASSE,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 600.00,
                'coefficiente_scarto' => 0.05,
            ],
            [
                'codice' => 'ASS-330x24',
                'nome' => 'Asse 330x24',
                'descrizione' => 'Asse lavorata 330x24x2.5 cm',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::ASSE,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 590.00,
                'coefficiente_scarto' => 0.05,
            ],

            // =====================================================
            // SEMILAVORATI - Listelli
            // =====================================================
            [
                'codice' => 'LIS-200x35',
                'nome' => 'Listello 200x3.5',
                'descrizione' => 'Listello 200x3.5x2.8 cm',
                'unita_misura' => UnitaMisura::MC,
                'categoria' => Categoria::LISTELLO,
                'soggetto_fitok' => true,
                'prezzo_unitario' => 580.00,
                'coefficiente_scarto' => 0.05,
            ],

            // =====================================================
            // FERRAMENTA
            // =====================================================
            [
                'codice' => 'FER-CHI-001',
                'nome' => 'Chiodi Imballaggio',
                'descrizione' => 'Chiodi per imballaggio (conf. 1000pz)',
                'unita_misura' => UnitaMisura::PZ,
                'categoria' => Categoria::FERRAMENTA,
                'soggetto_fitok' => false,
                'prezzo_unitario' => 25.00,
                'coefficiente_scarto' => 0.02,
            ],
            [
                'codice' => 'FER-REG-001',
                'nome' => 'Reggetta Plastica',
                'descrizione' => 'Reggetta in plastica per fissaggio',
                'unita_misura' => UnitaMisura::ML,
                'categoria' => Categoria::FERRAMENTA,
                'soggetto_fitok' => false,
                'prezzo_unitario' => 0.15,
                'coefficiente_scarto' => 0.05,
            ],
        ];

        foreach ($prodotti as $prodotto) {
            if (($prodotto['unita_misura'] ?? null) === UnitaMisura::MC) {
                $prodotto['peso_specifico_kg_mc'] = 360.000;
            }

            Prodotto::updateOrCreate(
                ['codice' => $prodotto['codice']],
                $prodotto
            );
        }
    }
}
