<?php

namespace App\Services;

use App\Models\Prodotto;

class CassaOptimizerService
{
    private const KERF_MM = 5; // Larghezza lama sega

    /**
     * Calcola materiali necessari per produrre casse
     *
     * @param float $larghezza_mm Larghezza esterna cassa
     * @param float $profondita_mm Profondità esterna cassa
     * @param float $altezza_mm Altezza cassa
     * @param int $quantita Numero di casse da produrre
     * @param Prodotto $materiale Asse di legno (deve avere lunghezza_mm, larghezza_mm, spessore_mm)
     * @return array Piano di taglio dettagliato
     */
    public function calcola(
        float $larghezza_mm,
        float $profondita_mm,
        float $altezza_mm,
        int $quantita,
        Prodotto $materiale
    ): array {
        // Validazione materiale
        if (!$materiale->lunghezza_mm || !$materiale->larghezza_mm || !$materiale->spessore_mm) {
            throw new \InvalidArgumentException(
                'Il materiale deve avere lunghezza_mm, larghezza_mm e spessore_mm definiti.'
            );
        }

        $spessore = $materiale->spessore_mm;
        $lunghezza_asse = $materiale->lunghezza_mm;
        $larghezza_asse = $materiale->larghezza_mm;

        // Step 1: Calcola dimensioni delle 5 facce considerando spessori sovrapposti
        $facce = $this->calcolaFacce($larghezza_mm, $profondita_mm, $altezza_mm, $spessore);

        // Step 2: Per ogni tipo di faccia, calcola piano di taglio ottimale
        $pianoTaglio = [];
        $totaleAssiNecessarie = 0;
        $scartoTotaleMm = 0;

        foreach ($facce as $faccia) {
            $numPezziTotali = $faccia['quantita_per_cassa'] * $quantita;

            $risultatoTaglio = $this->ottimizzaTaglio2D(
                $faccia['larghezza'],
                $faccia['altezza'],
                $numPezziTotali,
                $lunghezza_asse,
                $larghezza_asse
            );

            $pianoTaglio[] = [
                'tipo_faccia' => $faccia['tipo'],
                'descrizione' => $faccia['descrizione'],
                'dimensione_faccia' => [
                    'larghezza_mm' => $faccia['larghezza'],
                    'altezza_mm' => $faccia['altezza'],
                ],
                'pezzi_totali' => $numPezziTotali,
                'assi_necessarie' => $risultatoTaglio['assi_necessarie'],
                'pezzi_per_asse_lunghezza' => $risultatoTaglio['pezzi_per_asse_lunghezza'],
                'pezzi_per_asse_larghezza' => $risultatoTaglio['pezzi_per_asse_larghezza'],
                'pezzi_per_asse_totale' => $risultatoTaglio['pezzi_per_asse_totale'],
                'scarto_per_asse_mm' => $risultatoTaglio['scarto_per_asse_mm'],
                'scarto_totale_mm' => $risultatoTaglio['scarto_totale_mm'],
                'scarto_percentuale' => $risultatoTaglio['scarto_percentuale'],
                'note_taglio' => $risultatoTaglio['note_taglio'],
            ];

            $totaleAssiNecessarie += $risultatoTaglio['assi_necessarie'];
            $scartoTotaleMm += $risultatoTaglio['scarto_totale_mm'];
        }

        // Step 3: Calcola statistiche globali
        $lunghezzaTotaleUsata = $totaleAssiNecessarie * $lunghezza_asse;
        $scartoPercentualeGlobale = $lunghezzaTotaleUsata > 0
            ? round(($scartoTotaleMm / $lunghezzaTotaleUsata) * 100, 2)
            : 0;

        return [
            'materiale' => [
                'nome' => $materiale->nome,
                'codice' => $materiale->codice,
                'lunghezza_mm' => $lunghezza_asse,
                'larghezza_mm' => $larghezza_asse,
                'spessore_mm' => $spessore,
            ],
            'dimensioni_cassa' => [
                'larghezza_mm' => $larghezza_mm,
                'profondita_mm' => $profondita_mm,
                'altezza_mm' => $altezza_mm,
            ],
            'quantita_casse' => $quantita,
            'piano_taglio' => $pianoTaglio,
            'riepilogo' => [
                'totale_assi_necessarie' => $totaleAssiNecessarie,
                'lunghezza_totale_usata_mm' => $lunghezzaTotaleUsata,
                'scarto_totale_mm' => $scartoTotaleMm,
                'scarto_percentuale' => $scartoPercentualeGlobale,
            ],
        ];
    }

    /**
     * Calcola le 5 facce della cassa considerando spessori sovrapposti
     */
    private function calcolaFacce(float $larghezza, float $profondita, float $altezza, float $spessore): array
    {
        return [
            [
                'tipo' => 'lato_esterno',
                'descrizione' => 'Lato esterno (frontale/retro)',
                'larghezza' => $larghezza,
                'altezza' => $altezza,
                'quantita_per_cassa' => 2,
            ],
            [
                'tipo' => 'lato_interno',
                'descrizione' => 'Lato interno (sinistro/destro)',
                'larghezza' => $profondita - (2 * $spessore),
                'altezza' => $altezza,
                'quantita_per_cassa' => 2,
            ],
            [
                'tipo' => 'fondo',
                'descrizione' => 'Fondo',
                'larghezza' => $larghezza - (2 * $spessore),
                'altezza' => $profondita - (2 * $spessore),
                'quantita_per_cassa' => 1,
            ],
        ];
    }

    /**
     * Ottimizza il taglio 2D di una faccia dalle assi disponibili
     */
    private function ottimizzaTaglio2D(
        float $larghezza_faccia,
        float $altezza_faccia,
        int $pezzi_totali,
        float $lunghezza_asse,
        float $larghezza_asse
    ): array {
        // Calcola quanti "strati" di asse servono per coprire la larghezza della faccia
        $stratiInLarghezza = max(1, (int) ceil($larghezza_faccia / $larghezza_asse));

        // Kerf applicato sui tagli reali: n*L + (n-1)*kerf <= lunghezza asse.
        $pezziPerAsseInLunghezza = $this->calcolaPezziPerFila($altezza_faccia, $lunghezza_asse);
        if ($pezziPerAsseInLunghezza <= 0) {
            throw new \InvalidArgumentException(sprintf(
                'Il pezzo da %dmm non entra nella lunghezza asse disponibile (%dmm).',
                (int) $altezza_faccia,
                (int) $lunghezza_asse
            ));
        }

        // Pezzi totali per asse (lunghezza × strati larghezza)
        $pezziPerAsseTotale = $pezziPerAsseInLunghezza * $stratiInLarghezza;

        // Assi necessarie
        $assiNecessarie = (int) ceil($pezzi_totali / $pezziPerAsseTotale);

        // Calcola scarto coerente tra assi pieni e ultimo asse parziale.
        $scartoTotaleMm = $this->calcolaScartoTotale(
            $pezzi_totali,
            $stratiInLarghezza,
            $pezziPerAsseInLunghezza,
            $altezza_faccia,
            $lunghezza_asse
        );
        $scartoPerAsseMm = $assiNecessarie > 0 ? ($scartoTotaleMm / $assiNecessarie) : 0.0;

        // Scarto percentuale
        $scartoPercentuale = $lunghezza_asse > 0
            ? round(($scartoPerAsseMm / $lunghezza_asse) * 100, 2)
            : 0;

        // Note di taglio
        $noteTaglio = [];
        if ($stratiInLarghezza > 1) {
            $noteTaglio[] = sprintf(
                'Taglio in larghezza: asse da %dmm → %d strati da ~%dmm',
                (int) $larghezza_asse,
                $stratiInLarghezza,
                (int) ceil($larghezza_faccia / $stratiInLarghezza)
            );
        }
        $noteTaglio[] = sprintf(
            'Taglio in lunghezza: %d pezzi da %dmm per asse',
            $pezziPerAsseInLunghezza,
            (int) $altezza_faccia
        );

        return [
            'pezzi_per_asse_larghezza' => $stratiInLarghezza,
            'pezzi_per_asse_lunghezza' => $pezziPerAsseInLunghezza,
            'pezzi_per_asse_totale' => $pezziPerAsseTotale,
            'assi_necessarie' => $assiNecessarie,
            'scarto_per_asse_mm' => $scartoPerAsseMm,
            'scarto_totale_mm' => $scartoTotaleMm,
            'scarto_percentuale' => $scartoPercentuale,
            'note_taglio' => $noteTaglio,
        ];
    }

    private function calcolaPezziPerFila(float $lunghezzaPezzo, float $lunghezzaAsse): int
    {
        if ($lunghezzaPezzo <= 0 || $lunghezzaAsse <= 0) {
            return 0;
        }

        return (int) floor(
            ($lunghezzaAsse + self::KERF_MM) / ($lunghezzaPezzo + self::KERF_MM)
        );
    }

    private function calcolaScartoTotale(
        int $pezziTotali,
        int $stratiInLarghezza,
        int $pezziPerFilaMassimi,
        float $lunghezzaPezzo,
        float $lunghezzaAsse
    ): float {
        if ($pezziTotali <= 0) {
            return 0.0;
        }

        $capacitaPerAsse = $stratiInLarghezza * $pezziPerFilaMassimi;
        if ($capacitaPerAsse <= 0) {
            return 0.0;
        }

        $assiPieni = intdiv($pezziTotali, $capacitaPerAsse);
        $pezziUltimoAsse = $pezziTotali % $capacitaPerAsse;

        $scartoAssePieno = $this->calcolaScartoAsse(
            $capacitaPerAsse,
            $stratiInLarghezza,
            $pezziPerFilaMassimi,
            $lunghezzaPezzo,
            $lunghezzaAsse
        );

        $totale = $assiPieni * $scartoAssePieno;

        if ($pezziUltimoAsse > 0) {
            $totale += $this->calcolaScartoAsse(
                $pezziUltimoAsse,
                $stratiInLarghezza,
                $pezziPerFilaMassimi,
                $lunghezzaPezzo,
                $lunghezzaAsse
            );
        }

        return $totale;
    }

    private function calcolaScartoAsse(
        int $pezziSullAsse,
        int $stratiInLarghezza,
        int $pezziPerFilaMassimi,
        float $lunghezzaPezzo,
        float $lunghezzaAsse
    ): float {
        if ($stratiInLarghezza <= 0) {
            return $lunghezzaAsse;
        }

        $pezziRimanenti = $pezziSullAsse;
        $sommaLunghezzeUsate = 0.0;

        for ($fila = 0; $fila < $stratiInLarghezza; $fila++) {
            if ($pezziRimanenti <= 0) {
                break;
            }

            $pezziInFila = min($pezziPerFilaMassimi, $pezziRimanenti);
            $sommaLunghezzeUsate += $this->lunghezzaUsataFila($pezziInFila, $lunghezzaPezzo);
            $pezziRimanenti -= $pezziInFila;
        }

        // Scarto espresso in mm "equivalenti asse": media tra gli strati in larghezza.
        $lunghezzaMediaUsata = $sommaLunghezzeUsate / $stratiInLarghezza;

        return max(0.0, $lunghezzaAsse - $lunghezzaMediaUsata);
    }

    private function lunghezzaUsataFila(int $pezziInFila, float $lunghezzaPezzo): float
    {
        if ($pezziInFila <= 0) {
            return 0.0;
        }

        return ($pezziInFila * $lunghezzaPezzo) + (($pezziInFila - 1) * self::KERF_MM);
    }
}
