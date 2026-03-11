<?php

namespace App\Services;

use App\Enums\StatoConsumoMateriale;
use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\TipoRigaPreventivo;
use App\Models\Bom;
use App\Models\ConsumoMateriale;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\PreventivoRiga;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderProductionService
{
    public function __construct(
        private readonly ProductionLotService $productionLotService,
        private readonly LottoProductionReadinessService $readinessService
    ) {}

    /**
     * Avvia la produzione di un ordine in modo atomico.
     *
     * @return array{ordine: Ordine, bom: Bom, lotti_avviati: int, consumi_opzionati: int}
     */
    public function avviaProduzione(Ordine $ordine, User $user): array
    {
        return DB::transaction(function () use ($ordine, $user) {
            $ordine->load('lottiProduzione');

            if ($ordine->stato !== StatoOrdine::CONFERMATO) {
                throw new \RuntimeException('Solo gli ordini confermati possono essere avviati in produzione.');
            }

            foreach ($ordine->lottiProduzione as $lotto) {
                if (! in_array($lotto->stato, [
                    StatoLottoProduzione::BOZZA,
                    StatoLottoProduzione::CONFERMATO,
                    StatoLottoProduzione::IN_LAVORAZIONE,
                ], true)) {
                    throw new \RuntimeException(
                        "Il lotto {$lotto->codice_lotto} non e' avviabile dallo stato {$lotto->stato->label()}."
                    );
                }

                $readiness = $this->readinessService->evaluate($lotto);
                if (! $readiness['ready']) {
                    throw new \RuntimeException(
                        "Il lotto {$lotto->codice_lotto} non e' pronto: {$readiness['message']}"
                    );
                }
            }

            $lottiAvviati = 0;
            foreach ($ordine->lottiProduzione as $lotto) {
                if (in_array($lotto->stato, [StatoLottoProduzione::BOZZA, StatoLottoProduzione::CONFERMATO], true)) {
                    $this->productionLotService->avviaLavorazione($lotto);
                    $lottiAvviati++;
                }
            }

            $ordine->refresh();
            $ordine->load('lottiProduzione.materialiUsati.prodotto', 'lottiProduzione.componentiManuali.prodotto');

            $consumiOpzionati = 0;
            foreach ($ordine->lottiProduzione as $lotto) {
                $consumiOpzionati += $this->pianificaConsumiLotto($lotto, $ordine->numero);
            }

            $bom = $this->creaAggiornaBomOrdine($ordine, $user);

            $ordine->update(['stato' => StatoOrdine::IN_PRODUZIONE]);

            return [
                'ordine' => $ordine->fresh(),
                'bom' => $bom,
                'lotti_avviati' => $lottiAvviati,
                'consumi_opzionati' => $consumiOpzionati,
            ];
        });
    }

    /**
     * Genera/aggiorna BOM ordine e opzioni di materiale senza cambiare lo stato ordine.
     * Usato alla creazione ordine da preventivo (stato CONFERMATO).
     *
     * @return array{ordine: Ordine, bom: Bom, consumi_opzionati: int}
     */
    public function sincronizzaOrdineConfermato(Ordine $ordine, User $user): array
    {
        return DB::transaction(function () use ($ordine, $user) {
            $ordine->refresh();

            if ($ordine->stato !== StatoOrdine::CONFERMATO) {
                throw new \RuntimeException('La sincronizzazione BOM e opzioni richiede un ordine in stato Confermato.');
            }

            $ordine->load('lottiProduzione.materialiUsati.prodotto', 'lottiProduzione.componentiManuali.prodotto');

            $consumiOpzionati = 0;
            foreach ($ordine->lottiProduzione as $lotto) {
                $consumiOpzionati += $this->pianificaConsumiLotto($lotto, $ordine->numero);
            }

            $bom = $this->creaAggiornaBomOrdine($ordine, $user);

            return [
                'ordine' => $ordine->fresh(),
                'bom' => $bom,
                'consumi_opzionati' => $consumiOpzionati,
            ];
        });
    }

    /**
     * Completa la produzione ordine:
     * - completa i lotti in lavorazione collegati
     * - valida assenza di lotti non terminali
     * - marca l'ordine come PRONTO
     *
     * @return array{ordine: Ordine, lotti_completati: int}
     */
    public function completaProduzione(Ordine $ordine, User $user): array
    {
        return DB::transaction(function () use ($ordine, $user) {
            $ordine->refresh();
            $ordine->load('lottiProduzione');

            if ($ordine->stato !== StatoOrdine::IN_PRODUZIONE) {
                throw new \RuntimeException('Solo gli ordini in produzione possono essere completati.');
            }

            $lottiCompletati = 0;
            foreach ($ordine->lottiProduzione as $lotto) {
                if ($lotto->stato === StatoLottoProduzione::IN_LAVORAZIONE) {
                    $this->productionLotService->confermaLotto($lotto, $user);
                    $lottiCompletati++;
                }
            }

            $ordine->load('lottiProduzione');
            $lottiNonTerminali = $ordine->lottiProduzione
                ->filter(fn (LottoProduzione $lotto) => ! in_array($lotto->stato, [
                    StatoLottoProduzione::COMPLETATO,
                    StatoLottoProduzione::ANNULLATO,
                ], true))
                ->values();

            if ($lottiNonTerminali->isNotEmpty()) {
                $codici = $lottiNonTerminali
                    ->pluck('codice_lotto')
                    ->implode(', ');

                throw new \RuntimeException(
                    "Impossibile segnare l'ordine come pronto: restano lotti non terminali ({$codici})."
                );
            }

            $ordine->update(['stato' => StatoOrdine::PRONTO]);

            return [
                'ordine' => $ordine->fresh(),
                'lotti_completati' => $lottiCompletati,
            ];
        });
    }

    private function creaAggiornaBomOrdine(Ordine $ordine, User $user): Bom
    {
        $lines = $this->buildShoppingList($ordine);

        $payload = [
            'nome' => "Distinta materiali ordine {$ordine->numero}",
            'prodotto_id' => null,
            'lotto_produzione_id' => null,
            'ordine_id' => $ordine->id,
            'categoria_output' => null,
            'versione' => '1.0',
            'is_active' => true,
            'generated_at' => now(),
            'source' => 'ordine',
            'note' => "Distinta materiali generata automaticamente da avvio produzione ordine {$ordine->numero}",
        ];

        $bom = Bom::query()
            ->where('ordine_id', $ordine->id)
            ->where('source', 'ordine')
            ->latest('id')
            ->first();

        if ($bom) {
            $bom->update($payload);
            $bom->righe()->delete();
        } else {
            $bom = Bom::create(array_merge($payload, [
                'created_by' => $user->id,
            ]));
        }

        foreach (array_values($lines) as $ordineRiga => $line) {
            $bom->righe()->create([
                'prodotto_id' => $line['prodotto_id'],
                'source_type' => 'aggregato',
                'source_id' => null,
                'descrizione' => $line['descrizione'],
                'quantita' => $line['quantita'],
                'unita_misura' => $line['unita_misura'],
                'coefficiente_scarto' => 0,
                'is_fitok_required' => $line['is_fitok_required'],
                'is_optional' => false,
                'ordine' => $ordineRiga,
                'note' => $line['note'],
            ]);
        }

        return $bom->loadCount('righe');
    }

    /**
     * @return array<string, array{prodotto_id:int|null, descrizione:string, quantita:float, unita_misura:string, is_fitok_required:bool, note:string}>
     */
    private function buildShoppingList(Ordine $ordine): array
    {
        $lines = [];

        foreach ($ordine->lottiProduzione as $lotto) {
            foreach ($lotto->materialiUsati as $materiale) {
                $prodotto = $materiale->prodotto;
                $unita = $prodotto?->unita_misura?->value ?? 'mc';
                $quantita = $this->resolveQuantitaMateriale($materiale, $unita);

                $this->addLine(
                    lines: $lines,
                    prodottoId: $prodotto?->id,
                    descrizione: $prodotto?->nome ?? ($materiale->descrizione ?: 'Materiale lotto'),
                    quantita: $quantita,
                    unita: $unita,
                    isFitokRequired: (bool) ($prodotto?->soggetto_fitok ?? false),
                    note: "Lotto {$lotto->codice_lotto}"
                );
            }

            foreach ($lotto->componentiManuali as $componenteManuale) {
                $prodotto = $componenteManuale->prodotto;
                $unita = $componenteManuale->unita_misura ?: ($prodotto?->unita_misura?->value ?? 'pz');

                $this->addLine(
                    lines: $lines,
                    prodottoId: $prodotto?->id,
                    descrizione: $prodotto?->nome ?? 'Componente manuale',
                    quantita: (float) $componenteManuale->quantita,
                    unita: strtolower($unita),
                    isFitokRequired: (bool) ($prodotto?->soggetto_fitok ?? false),
                    note: "Componente manuale lotto {$lotto->codice_lotto}"
                );
            }
        }

        if ($ordine->preventivo_id) {
            PreventivoRiga::query()
                ->with('prodotto')
                ->where('preventivo_id', $ordine->preventivo_id)
                ->where('tipo_riga', TipoRigaPreventivo::SFUSO->value)
                ->where('include_in_bom', true)
                ->get()
                ->each(function (PreventivoRiga $riga) use (&$lines) {
                    $unita = strtolower($riga->unita_misura ?: ($riga->prodotto?->unita_misura?->value ?? 'mc'));
                    $quantita = $this->resolveQuantitaRigaSfusa($riga, $unita);

                    $this->addLine(
                        lines: $lines,
                        prodottoId: $riga->prodotto_id,
                        descrizione: $riga->descrizione ?: ($riga->prodotto?->nome ?? 'Materiale sfuso'),
                        quantita: $quantita,
                        unita: $unita,
                        isFitokRequired: (bool) ($riga->prodotto?->soggetto_fitok ?? false),
                        note: 'Riga sfusa da preventivo'
                    );
                });
        }

        return $lines;
    }

    /**
     * @param  array<string, array{prodotto_id:int|null, descrizione:string, quantita:float, unita_misura:string, is_fitok_required:bool, note:string}>  $lines
     */
    private function addLine(
        array &$lines,
        ?int $prodottoId,
        string $descrizione,
        float $quantita,
        string $unita,
        bool $isFitokRequired,
        string $note
    ): void {
        $quantita = round($quantita, 4);
        if ($quantita <= 0) {
            return;
        }

        $key = $prodottoId
            ? implode('|', [(string) $prodottoId, strtolower($unita)])
            : implode('|', ['0', strtolower($unita), strtolower($descrizione)]);

        if (! isset($lines[$key])) {
            $lines[$key] = [
                'prodotto_id' => $prodottoId,
                'descrizione' => $descrizione,
                'quantita' => 0,
                'unita_misura' => strtolower($unita),
                'is_fitok_required' => $isFitokRequired,
                'note' => $note,
            ];
        }

        $lines[$key]['quantita'] = round($lines[$key]['quantita'] + $quantita, 4);

        if ($note !== '') {
            $noteParts = array_filter(array_map('trim', explode(';', $lines[$key]['note'])));
            if (! in_array($note, $noteParts, true)) {
                $noteParts[] = $note;
            }
            $lines[$key]['note'] = implode('; ', $noteParts);
        }
    }

    private function resolveQuantitaMateriale(object $materiale, string $unita): float
    {
        return match (strtolower($unita)) {
            'pz' => (float) max(1, (int) ($materiale->assi_necessarie ?? $materiale->quantita_pezzi ?? 1)),
            'ml' => (float) (((float) ($materiale->lunghezza_mm ?? 0) / 1000) * max(1, (int) ($materiale->assi_necessarie ?? 1))),
            'mq' => (float) (
                (((float) ($materiale->lunghezza_mm ?? 0) / 1000) * ((float) ($materiale->larghezza_mm ?? 0) / 1000))
                * max(1, (int) ($materiale->quantita_pezzi ?? 1))
            ),
            default => (float) ($materiale->volume_mc ?? 0),
        };
    }

    private function resolveQuantitaRigaSfusa(PreventivoRiga $riga, string $unita): float
    {
        return match (strtolower($unita)) {
            'pz' => (float) ($riga->quantita ?? 0),
            'kg' => (float) ($riga->quantita ?? 0),
            'ml' => (float) (
                (((float) ($riga->lunghezza_mm ?? 0)) / 1000)
                * max(1, (int) ($riga->quantita ?? 1))
            ),
            'mq' => (float) (
                $riga->superficie_mq
                ?: (
                    (((float) ($riga->lunghezza_mm ?? 0)) / 1000)
                    * (((float) ($riga->larghezza_mm ?? 0)) / 1000)
                    * max(1, (int) ($riga->quantita ?? 1))
                )
            ),
            default => (float) ($riga->materiale_lordo ?: ($riga->volume_mc ?: 0)),
        };
    }

    private function pianificaConsumiLotto(LottoProduzione $lotto, string $numeroOrdine): int
    {
        $lotto->consumiMateriale()
            ->where('stato', '!=', StatoConsumoMateriale::CONSUMATO->value)
            ->delete();

        $fabbisognoPerProdotto = [];

        foreach ($lotto->materialiUsati as $materiale) {
            if (! $materiale->prodotto_id) {
                continue;
            }

            $unita = $materiale->prodotto?->unita_misura?->value ?? 'mc';
            $fabbisognoPerProdotto[$materiale->prodotto_id] = ($fabbisognoPerProdotto[$materiale->prodotto_id] ?? 0)
                + $this->resolveQuantitaMateriale($materiale, $unita);
        }

        foreach ($lotto->componentiManuali as $componenteManuale) {
            if (! $componenteManuale->prodotto_id) {
                continue;
            }

            $fabbisognoPerProdotto[$componenteManuale->prodotto_id] = ($fabbisognoPerProdotto[$componenteManuale->prodotto_id] ?? 0)
                + (float) $componenteManuale->quantita;
        }

        $consumiCreati = 0;
        foreach ($fabbisognoPerProdotto as $prodottoId => $quantitaRichiesta) {
            $remaining = round((float) $quantitaRichiesta, 4);

            if ($remaining <= 0) {
                continue;
            }

            $lottiMateriale = LottoMateriale::query()
                ->where('prodotto_id', $prodottoId)
                ->orderBy('data_arrivo')
                ->orderBy('id')
                ->get();

            foreach ($lottiMateriale as $lottoMateriale) {
                if ($remaining <= 0) {
                    break;
                }

                $disponibile = $this->quantitaDisponibilePerOpzione($lottoMateriale);
                if ($disponibile <= 0) {
                    continue;
                }

                $quantitaOpzionata = min($remaining, $disponibile);

                ConsumoMateriale::create([
                    'lotto_produzione_id' => $lotto->id,
                    'lotto_materiale_id' => $lottoMateriale->id,
                    'quantita' => $quantitaOpzionata,
                    'stato' => StatoConsumoMateriale::OPZIONATO,
                    'opzionato_at' => now(),
                    'note' => "Opzionato da avvio produzione ordine {$numeroOrdine}",
                ]);

                $consumiCreati++;
                $remaining = round($remaining - $quantitaOpzionata, 4);
            }
        }

        return $consumiCreati;
    }

    private function quantitaDisponibilePerOpzione(LottoMateriale $lottoMateriale): float
    {
        $giacenza = (float) $lottoMateriale->giacenza;

        $giaOpzionata = (float) ConsumoMateriale::query()
            ->where('lotto_materiale_id', $lottoMateriale->id)
            ->where('stato', StatoConsumoMateriale::OPZIONATO->value)
            ->sum('quantita');

        return round(max(0, $giacenza - $giaOpzionata), 4);
    }
}
