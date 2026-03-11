<?php

namespace Database\Seeders;

use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\StatoPreventivo;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimento;
use App\Models\Bom;
use App\Models\Cliente;
use App\Models\ConsumoMateriale;
use App\Models\Documento;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\Prodotto;
use App\Models\Scarto;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@demo.test')->first();
        $seedUser = $admin ?? User::query()->first();

        if (! $seedUser) {
            return;
        }

        $oggi = now()->startOfDay();
        $giorniTrascorsiNelMese = max(0, $oggi->day - 1);
        $giorniAgoCaricoRecente = min(5, $giorniTrascorsiNelMese);
        $giorniAgoScaricoPrincipale = min(2, $giorniTrascorsiNelMese);
        $giorniAgoScaricoSecondario = min(1, $giorniTrascorsiNelMese);

        // =================================================================
        // CLIENTI - Anagrafiche demo per portfolio
        // =================================================================
        $clienti = [
            [
                'ragione_sociale' => 'Alfa Packaging Srl',
                'partita_iva' => '10123456789',
                'codice_fiscale' => '10123456789',
                'indirizzo' => 'Via dell\'Industria 12',
                'cap' => '37135',
                'citta' => 'Verona',
                'provincia' => 'VR',
                'telefono' => '045 1112233',
                'email' => 'acquisti@alfapackaging.test',
            ],
            [
                'ragione_sociale' => 'Beta Export Srl',
                'partita_iva' => '10234567890',
                'codice_fiscale' => '10234567890',
                'indirizzo' => 'Via Logistica 25',
                'cap' => '37060',
                'citta' => 'Mozzecane',
                'provincia' => 'VR',
                'telefono' => '045 2223344',
                'email' => 'commerciale@betaexport.test',
            ],
            [
                'ragione_sociale' => 'Gamma Shipping Spa',
                'partita_iva' => '10345678901',
                'codice_fiscale' => '10345678901',
                'indirizzo' => 'Via del Porto 7',
                'cap' => '30175',
                'citta' => 'Marghera',
                'provincia' => 'VR',
                'telefono' => '045 3334455',
                'email' => 'ordini@gammashipping.test',
            ],
            [
                'ragione_sociale' => 'Delta Logistics Sas',
                'partita_iva' => '10456789012',
                'codice_fiscale' => '10456789012',
                'indirizzo' => 'Via Artigianato 8',
                'cap' => '37100',
                'citta' => 'Verona',
                'provincia' => 'VR',
                'telefono' => '045 4445566',
                'email' => 'info@deltalogistics.test',
            ],
            [
                'ragione_sociale' => 'Omega Industrial Crates Srl',
                'partita_iva' => '10567890123',
                'codice_fiscale' => '10567890123',
                'indirizzo' => 'Via delle Spedizioni 20',
                'cap' => '37045',
                'citta' => 'Legnago',
                'provincia' => 'VR',
                'telefono' => '045 5556677',
                'email' => 'vendite@omega-crates.test',
            ],
        ];

        foreach ($clienti as $cliente) {
            Cliente::firstOrCreate(
                ['partita_iva' => $cliente['partita_iva']],
                $cliente
            );
        }

        // =================================================================
        // LOTTI MATERIALE - Pacchi legname FITOK (da registro Excel)
        // =================================================================
        $abete3575 = Prodotto::where('codice', 'MP-ABE-3575')->first();
        $abete3595 = Prodotto::where('codice', 'MP-ABE-3595')->first();
        $abete25100 = Prodotto::where('codice', 'MP-ABE-25100')->first();
        $abete40100 = Prodotto::where('codice', 'MP-ABE-40100')->first();
        $abete5575 = Prodotto::where('codice', 'MP-ABE-5575')->first();

        if ($abete3575 && $abete3595 && $abete25100 && $abete40100 && $abete5575) {
            $lottiMateriale = [
                [
                    'codice_lotto' => '115/26',
                    'prodotto_id' => $abete25100->id,
                    'data_arrivo' => $oggi->copy()->subDays(24)->setTime(8, 10),
                    'fornitore' => 'SPARBER',
                    'numero_ddt' => 'F.31015',
                    'quantita_iniziale' => 2.850,
                    'fitok_certificato' => 'FITOK-AT-2026-0115',
                    'fitok_data_trattamento' => $oggi->copy()->subDays(27),
                    'fitok_tipo_trattamento' => 'HT',
                    'fitok_paese_origine' => 'AT',
                    'lunghezza_mm' => 4000,
                    'larghezza_mm' => 100,
                    'spessore_mm' => 25,
                ],
                [
                    'codice_lotto' => '116/26',
                    'prodotto_id' => $abete40100->id,
                    'data_arrivo' => $oggi->copy()->subDays(22)->setTime(8, 25),
                    'fornitore' => 'SPARBER',
                    'numero_ddt' => 'F.31016',
                    'quantita_iniziale' => 2.100,
                    'fitok_certificato' => 'FITOK-AT-2026-0116',
                    'fitok_data_trattamento' => $oggi->copy()->subDays(25),
                    'fitok_tipo_trattamento' => 'HT',
                    'fitok_paese_origine' => 'AT',
                    'lunghezza_mm' => 4000,
                    'larghezza_mm' => 100,
                    'spessore_mm' => 40,
                ],
                [
                    'codice_lotto' => '303/25',
                    'prodotto_id' => $abete3575->id,
                    'data_arrivo' => $oggi->copy()->subDays(35)->setTime(8, 20),
                    'fornitore' => 'SPARBER',
                    'numero_ddt' => 'F.30641',
                    'quantita_iniziale' => 3.560,
                    'fitok_certificato' => 'FITOK-AT-2025-0303',
                    'fitok_data_trattamento' => $oggi->copy()->subDays(38),
                    'fitok_tipo_trattamento' => 'HT',
                    'fitok_paese_origine' => 'AT',
                    'lunghezza_mm' => 4000,
                    'larghezza_mm' => 75,
                    'spessore_mm' => 35,
                ],
                [
                    'codice_lotto' => '389/25',
                    'prodotto_id' => $abete3595->id,
                    'data_arrivo' => $oggi->copy()->subDays(27)->setTime(8, 35),
                    'fornitore' => 'SPARBER',
                    'numero_ddt' => 'F.30542',
                    'quantita_iniziale' => 3.671,
                    'fitok_certificato' => 'FITOK-AT-2025-0389',
                    'fitok_data_trattamento' => $oggi->copy()->subDays(30),
                    'fitok_tipo_trattamento' => 'HT',
                    'fitok_paese_origine' => 'AT',
                    'lunghezza_mm' => 4000,
                    'larghezza_mm' => 95,
                    'spessore_mm' => 35,
                ],
                [
                    'codice_lotto' => '196/25',
                    'prodotto_id' => $abete5575->id,
                    'data_arrivo' => $oggi->copy()->subDays(18)->setTime(9, 5),
                    'fornitore' => 'FORNONI',
                    'numero_ddt' => '1207',
                    'quantita_iniziale' => 3.898,
                    'fitok_certificato' => 'FITOK-IT-2025-0196',
                    'fitok_data_trattamento' => $oggi->copy()->subDays(21),
                    'fitok_tipo_trattamento' => 'HT',
                    'fitok_paese_origine' => 'IT',
                    'lunghezza_mm' => 4500,
                    'larghezza_mm' => 75,
                    'spessore_mm' => 55,
                ],
                [
                    'codice_lotto' => '508/25',
                    'prodotto_id' => $abete5575->id,
                    'data_arrivo' => $oggi->copy()->subDays($giorniAgoCaricoRecente)->setTime(10, 15),
                    'fornitore' => 'FORNONI',
                    'numero_ddt' => '1250',
                    'quantita_iniziale' => 3.500,
                    'fitok_certificato' => 'FITOK-IT-2025-0508',
                    'fitok_data_trattamento' => $oggi->copy()->subDays(max($giorniAgoCaricoRecente + 2, 2)),
                    'fitok_tipo_trattamento' => 'HT',
                    'fitok_paese_origine' => 'IT',
                    'lunghezza_mm' => 4000,
                    'larghezza_mm' => 75,
                    'spessore_mm' => 55,
                ],
            ];

            foreach ($lottiMateriale as $lotto) {
                $prodotto = Prodotto::query()->find($lotto['prodotto_id']);
                $pesoSpecifico = (float) ($prodotto?->peso_specifico_kg_mc ?? 360);
                $lotto['peso_totale_kg'] = round(((float) $lotto['quantita_iniziale']) * $pesoSpecifico, 3);

                $lottoCreato = LottoMateriale::updateOrCreate(
                    ['codice_lotto' => $lotto['codice_lotto']],
                    $lotto
                );

                $documentoIngresso = $this->upsertDocumento(
                    tipo: TipoDocumento::DDT_INGRESSO->value,
                    numero: $lotto['numero_ddt'],
                    data: $lotto['data_arrivo']->toDateString(),
                    attributes: [
                        'fornitore' => $lotto['fornitore'],
                        'descrizione' => 'Carico materiale lotto '.$lotto['codice_lotto'],
                        'created_by' => $seedUser->id,
                    ]
                );

                MovimentoMagazzino::updateOrCreate(
                    [
                        'lotto_materiale_id' => $lottoCreato->id,
                        'tipo' => TipoMovimento::CARICO->value,
                        'documento_id' => $documentoIngresso->id,
                    ],
                    [
                        'quantita' => $lotto['quantita_iniziale'],
                        'causale' => 'Carico da DDT '.$lotto['numero_ddt'].' - '.$lotto['fornitore'],
                        'created_by' => $seedUser->id,
                        'data_movimento' => $lotto['data_arrivo'],
                    ]
                );
            }
        }

        // =================================================================
        // PREVENTIVO BOZZA + LOTTO PLACEHOLDER (coerenti e collegati 1:1)
        // =================================================================
        $clientePreventivo = Cliente::where('ragione_sociale', 'Alfa Packaging Srl')->first();

        if ($clientePreventivo) {
            $preventivo = Preventivo::updateOrCreate(
                ['numero' => 'PRV-2026-0001'],
                [
                    'cliente_id' => $clientePreventivo->id,
                    'data' => now()->subDays(10),
                    'validita_fino' => now()->addDays(20),
                    'stato' => StatoPreventivo::BOZZA,
                    'descrizione' => 'Preventivo demo con lotto placeholder in bozza',
                    'engine_version' => '1.0.0',
                    'totale_materiali' => 285.00,
                    'totale_lavorazioni' => 0,
                    'totale' => 285.00,
                    'created_by' => $seedUser->id,
                ]
            );

            $lottoPlaceholder = LottoProduzione::updateOrCreate(
                ['codice_lotto' => 'LP-2026-0001'],
                [
                    'cliente_id' => $clientePreventivo->id,
                    'preventivo_id' => $preventivo->id,
                    'prodotto_finale' => 'Cassa 80x80x120',
                    'larghezza_cm' => null,
                    'profondita_cm' => null,
                    'altezza_cm' => null,
                    'tipo_prodotto' => null,
                    'spessore_base_mm' => null,
                    'spessore_fondo_mm' => null,
                    'numero_pezzi' => 1,
                    'numero_univoco' => null,
                    'descrizione' => 'Placeholder tecnico da completare prima della produzione',
                    'stato' => StatoLottoProduzione::BOZZA,
                    'created_by' => $seedUser->id,
                ]
            );

            if ($lottoPlaceholder->preventivo_id !== $preventivo->id) {
                $lottoPlaceholder->update([
                    'preventivo_id' => $preventivo->id,
                    'ordine_id' => null,
                    'cliente_id' => $lottoPlaceholder->cliente_id ?: $preventivo->cliente_id,
                ]);
            }

            $riga = PreventivoRiga::updateOrCreate(
                [
                    'preventivo_id' => $preventivo->id,
                    'lotto_produzione_id' => $lottoPlaceholder->id,
                ],
                [
                    'tipo_riga' => 'lotto',
                    'include_in_bom' => true,
                    'prodotto_id' => $abete3575?->id,
                    'unita_misura' => 'mc',
                    'descrizione' => 'Riga lotto demo 80x80x120',
                    'lunghezza_mm' => 800,
                    'larghezza_mm' => 800,
                    'spessore_mm' => 1200,
                    'quantita' => 2,
                    'superficie_mq' => 0,
                    'volume_mc' => 1.536,
                    'materiale_netto' => 1.536,
                    'coefficiente_scarto' => 0.10,
                    'materiale_lordo' => 1.690,
                    'prezzo_unitario' => 168.64,
                    'totale_riga' => 285.00,
                    'ordine' => 0,
                ]
            );

            if ($preventivo->totale != (float) $riga->totale_riga) {
                $preventivo->update([
                    'totale_materiali' => (float) $riga->totale_riga,
                    'totale_lavorazioni' => 0,
                    'totale' => (float) $riga->totale_riga,
                ]);
            }
        }

        // =================================================================
        // ORDINE OPERATIVO + LOTTO COMPLETATO (tracciabilità FITOK reale)
        // =================================================================
        $clienteOrdine = Cliente::where('ragione_sociale', 'Beta Export Srl')->first();

        if ($clienteOrdine && $abete3575 && $abete5575) {
            $ordine = Ordine::updateOrCreate(
                ['numero' => 'ORD-2026-0001'],
                [
                    'anno' => 2026,
                    'progressivo' => 1,
                    'cliente_id' => $clienteOrdine->id,
                    'preventivo_id' => null,
                    'data_ordine' => now()->subDays(6),
                    'data_consegna_prevista' => now()->addDays(4),
                    'stato' => StatoOrdine::PRONTO,
                    'descrizione' => 'Ordine demo operativo con materiali consumati e scarti',
                    'totale' => 410.00,
                    'created_by' => $seedUser->id,
                ]
            );

            $lottoOperativo = LottoProduzione::updateOrCreate(
                ['codice_lotto' => 'LP-2026-0002'],
                [
                    'cliente_id' => $clienteOrdine->id,
                    'preventivo_id' => null,
                    'ordine_id' => $ordine->id,
                    'prodotto_finale' => 'Gabbia 100x80x60',
                    'larghezza_cm' => 100,
                    'profondita_cm' => 80,
                    'altezza_cm' => 60,
                    'tipo_prodotto' => 'GABBIA DEMO',
                    'spessore_base_mm' => 25,
                    'spessore_fondo_mm' => 20,
                    'numero_pezzi' => 1,
                    'numero_univoco' => 'G',
                    'descrizione' => 'Lotto demo operativo con consumi già registrati',
                    'stato' => StatoLottoProduzione::COMPLETATO,
                    'data_inizio' => now()->subDays(3),
                    'data_fine' => now()->subDays(1),
                    'volume_totale_mc' => 0.580,
                    'peso_kg_mc' => 360,
                    'peso_totale_kg' => 208.80,
                    'fitok_percentuale' => 100,
                    'fitok_volume_mc' => 0.580,
                    'non_fitok_volume_mc' => 0,
                    'fitok_calcolato_at' => $oggi->copy()->subDay(),
                    'created_by' => $seedUser->id,
                ]
            );

            $lottoOperativo->materialiUsati()->updateOrCreate(
                ['ordine' => 0],
                [
                    'prodotto_id' => $abete3575->id,
                    'descrizione' => 'Assi principali abete FITOK',
                    'lunghezza_mm' => 4000,
                    'larghezza_mm' => 75,
                    'spessore_mm' => 35,
                    'quantita_pezzi' => 6,
                    'volume_mc' => 0.400,
                    'scarto_totale_mm' => 320,
                    'scarto_percentuale' => 8,
                ]
            );

            $lottoOperativo->materialiUsati()->updateOrCreate(
                ['ordine' => 1],
                [
                    'prodotto_id' => $abete5575->id,
                    'descrizione' => 'Rinforzi abete FITOK',
                    'lunghezza_mm' => 4000,
                    'larghezza_mm' => 75,
                    'spessore_mm' => 55,
                    'quantita_pezzi' => 2,
                    'volume_mc' => 0.180,
                    'scarto_totale_mm' => 120,
                    'scarto_percentuale' => 4,
                ]
            );

            $bom = Bom::updateOrCreate(
                [
                    'ordine_id' => $ordine->id,
                    'source' => 'ordine',
                ],
                [
                    'anno' => 2026,
                    'progressivo' => 1,
                    'codice' => 'BOM-2026-0001',
                    'nome' => 'BOM demo ordine Beta Export',
                    'lotto_produzione_id' => $lottoOperativo->id,
                    'categoria_output' => 'gabbia',
                    'versione' => '1.0',
                    'is_active' => true,
                    'generated_at' => now()->subDays(3),
                    'note' => 'BOM demo coerente con lotto operativo seed',
                    'created_by' => $seedUser->id,
                ]
            );

            $scarichi = [
                [
                    'codice_lotto_materiale' => '303/25',
                    'numero_documento' => 'BI-LP-2026-0002-01',
                    'data' => $oggi->copy()->subDays($giorniAgoScaricoPrincipale)->setTime(11, 10),
                    'quantita' => 0.400,
                    'causale' => 'Scarico per lotto LP-2026-0002 - Gabbia Beta Export',
                ],
                [
                    'codice_lotto_materiale' => '508/25',
                    'numero_documento' => 'BI-LP-2026-0002-02',
                    'data' => $oggi->copy()->subDays($giorniAgoScaricoSecondario)->setTime(15, 40),
                    'quantita' => 0.180,
                    'causale' => 'Integrazione materiale per lotto LP-2026-0002',
                ],
            ];

            foreach ($scarichi as $index => $scarico) {
                $lottoMateriale = LottoMateriale::where('codice_lotto', $scarico['codice_lotto_materiale'])->first();
                if (! $lottoMateriale) {
                    continue;
                }

                $documentoScarico = $this->upsertDocumento(
                    tipo: TipoDocumento::BOLLA_INTERNA->value,
                    numero: $scarico['numero_documento'],
                    data: $scarico['data']->toDateString(),
                    attributes: [
                        'cliente_id' => $clienteOrdine->id,
                        'descrizione' => 'Scarico materiale su lotto produzione '.$lottoOperativo->codice_lotto,
                        'created_by' => $seedUser->id,
                    ]
                );

                $movimento = MovimentoMagazzino::updateOrCreate(
                    [
                        'lotto_materiale_id' => $lottoMateriale->id,
                        'tipo' => TipoMovimento::SCARICO->value,
                        'documento_id' => $documentoScarico->id,
                        'lotto_produzione_id' => $lottoOperativo->id,
                    ],
                    [
                        'quantita' => $scarico['quantita'],
                        'causale' => $scarico['causale'],
                        'created_by' => $seedUser->id,
                        'data_movimento' => $scarico['data'],
                    ]
                );

                ConsumoMateriale::updateOrCreate(
                    [
                        'lotto_produzione_id' => $lottoOperativo->id,
                        'lotto_materiale_id' => $lottoMateriale->id,
                    ],
                    [
                        'movimento_id' => $movimento->id,
                        'stato' => 'consumato',
                        'quantita' => $scarico['quantita'],
                        'opzionato_at' => $scarico['data']->copy()->subHours(2),
                        'consumato_at' => $scarico['data'],
                        'note' => $index === 0
                            ? "Utilizzato in ordine {$ordine->numero}"
                            : "Utilizzato in ordine {$ordine->numero} (integrazione)",
                    ]
                );
            }

            Scarto::updateOrCreate(
                [
                    'lotto_produzione_id' => $lottoOperativo->id,
                    'lotto_materiale_id' => LottoMateriale::query()->where('codice_lotto', '303/25')->value('id'),
                    'lunghezza_mm' => 1000,
                    'larghezza_mm' => 75,
                    'spessore_mm' => 35,
                ],
                [
                    'volume_mc' => round(Scarto::calculateVolumeMcFromDimensions(1000, 75, 35), 6),
                    'riutilizzabile' => true,
                    'riutilizzato' => false,
                    'note' => 'Scarto riutilizzabile disponibile da lotto operativo demo',
                ]
            );

            $lottoOperativo->update([
                'fitok_percentuale' => 100,
                'fitok_volume_mc' => 0.580,
                'non_fitok_volume_mc' => 0,
                'fitok_calcolato_at' => $oggi->copy()->subDay(),
            ]);
        }
    }

    private function upsertDocumento(string $tipo, string $numero, string $data, array $attributes): Documento
    {
        $documento = Documento::query()
            ->where('tipo', $tipo)
            ->where('numero', $numero)
            ->whereDate('data', $data)
            ->first();

        if ($documento) {
            $documento->update(array_merge($attributes, [
                'tipo' => $tipo,
                'numero' => $numero,
                'data' => $data,
            ]));

            return $documento;
        }

        return Documento::create(array_merge($attributes, [
            'tipo' => $tipo,
            'numero' => $numero,
            'data' => $data,
        ]));
    }
}
