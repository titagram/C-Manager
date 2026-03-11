<?php

namespace App\Services;

use App\Enums\StatoConsumoMateriale;
use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\TipoMovimento;
use App\Exceptions\InsufficientStockException;
use App\Models\Bom;
use App\Models\Cliente;
use App\Models\ConsumoMateriale;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Preventivo;
use App\Models\Scarto;
use App\Models\User;
use App\Services\Production\DTO\OptimizerResultPayload;
use App\Services\Production\ProductionSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionLotService
{
    private const SCRAP_REUSE_RESIDUAL_NOTE_PREFIX = '[RESIDUO-RIUSO-SCARTO]';

    public function __construct(
        private InventoryService $inventoryService,
        private LottoProductionReadinessService $readinessService,
        private ProductionSettingsService $productionSettings
    ) {}

    /**
     * Crea un nuovo lotto di produzione
     */
    public function creaLotto(
        Cliente $cliente,
        string $prodottoFinale,
        User $user,
        ?Preventivo $preventivo = null,
        ?string $descrizione = null
    ): LottoProduzione {
        return DB::transaction(function () use ($cliente, $prodottoFinale, $user, $preventivo, $descrizione) {
            $codiceLotto = $this->generaCodiceLotto();

            return LottoProduzione::create([
                'codice_lotto' => $codiceLotto,
                'cliente_id' => $cliente->id,
                'preventivo_id' => $preventivo?->id,
                'prodotto_finale' => $prodottoFinale,
                'descrizione' => $descrizione,
                'stato' => StatoLottoProduzione::BOZZA,
                'created_by' => $user->id,
            ]);
        });
    }

    /**
     * Aggiunge un consumo di materiale al lotto di produzione
     *
     * @throws \Exception Se il lotto non è in stato bozza o in_lavorazione
     */
    public function aggiungiConsumo(
        LottoProduzione $lotto,
        LottoMateriale $materiale,
        float $quantita,
        User $user,
        ?string $note = null
    ): ConsumoMateriale {
        return DB::transaction(function () use ($lotto, $materiale, $quantita, $note) {
            if (! in_array($lotto->stato, [StatoLottoProduzione::BOZZA, StatoLottoProduzione::IN_LAVORAZIONE])) {
                throw new \Exception(
                    "Impossibile aggiungere consumi a un lotto in stato {$lotto->stato->label()}"
                );
            }

            if (! $this->inventoryService->verificaDisponibilita($materiale, $quantita)) {
                throw new \Exception(
                    "Giacenza insufficiente per il materiale {$materiale->codice_lotto}"
                );
            }

            // Verifica se esiste già un consumo per questo materiale
            $consumoEsistente = ConsumoMateriale::where('lotto_produzione_id', $lotto->id)
                ->where('lotto_materiale_id', $materiale->id)
                ->first();

            if ($consumoEsistente) {
                throw new \Exception(
                    "Esiste già un consumo per il materiale {$materiale->codice_lotto} in questo lotto"
                );
            }

            return ConsumoMateriale::create([
                'lotto_produzione_id' => $lotto->id,
                'lotto_materiale_id' => $materiale->id,
                'quantita' => $quantita,
                'note' => $note,
            ]);
        });
    }

    /**
     * Rimuove un consumo dal lotto di produzione
     */
    public function rimuoviConsumo(ConsumoMateriale $consumo): bool
    {
        return DB::transaction(function () use ($consumo) {
            $lotto = $consumo->lottoProduzione;

            if (! in_array($lotto->stato, [StatoLottoProduzione::BOZZA, StatoLottoProduzione::IN_LAVORAZIONE])) {
                throw new \Exception(
                    "Impossibile rimuovere consumi da un lotto in stato {$lotto->stato->label()}"
                );
            }

            // Se il movimento è già stato generato, non si può rimuovere
            if ($consumo->movimento_id) {
                throw new \Exception(
                    'Impossibile rimuovere un consumo già scaricato dal magazzino'
                );
            }

            return $consumo->delete();
        });
    }

    /**
     * Avvia la lavorazione del lotto
     */
    public function avviaLavorazione(LottoProduzione $lotto, ?User $user = null): LottoProduzione
    {
        return DB::transaction(function () use ($lotto, $user) {
            if (! in_array($lotto->stato, [
                StatoLottoProduzione::BOZZA,
                StatoLottoProduzione::CONFERMATO,
            ], true)) {
                throw new \Exception(
                    'Solo i lotti in bozza o confermati possono essere avviati'
                );
            }

            if ($lotto->preventivo_id && ! $lotto->ordine_id) {
                throw new \RuntimeException(
                    "Associare il lotto {$lotto->codice_lotto} a un ordine prima di avviare la produzione."
                );
            }

            $this->readinessService->assertReady($lotto);

            $lotto->update([
                'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
                'data_inizio' => now(),
                'avviato_at' => now(),
            ]);

            // Per lotti standalone (senza ordine) generiamo/aggiorniamo
            // automaticamente una BOM operativa all'avvio manuale.
            if (! $lotto->ordine_id) {
                $authenticated = auth()->user();
                $resolvedUser = $user instanceof User
                    ? $user
                    : ($authenticated instanceof User ? $authenticated : null);
                $bom = $this->creaAggiornaBomPerLotto($lotto->fresh(), $resolvedUser);
                $this->pianificaConsumiLottoStandalone(
                    $lotto->fresh(['materialiUsati.prodotto', 'componentiManuali.prodotto']),
                    "Opzionato da avvio lotto {$lotto->codice_lotto} (BOM {$bom->codice})"
                );
            }

            return $lotto->fresh();
        });
    }

    /**
     * Conferma il lotto e genera gli scarichi dal magazzino
     */
    public function confermaLotto(LottoProduzione $lotto, User $user): LottoProduzione
    {
        return DB::transaction(function () use ($lotto, $user) {
            if ($lotto->stato !== StatoLottoProduzione::IN_LAVORAZIONE) {
                throw new \Exception(
                    'Solo i lotti in lavorazione possono essere confermati'
                );
            }

            if ($lotto->preventivo_id && ! $lotto->ordine_id) {
                throw new \RuntimeException(
                    "Associare il lotto {$lotto->codice_lotto} a un ordine prima di completare la produzione."
                );
            }

            $haFabbisognoMateriali = $this->lottoHaFabbisognoMateriali($lotto);

            // Verifica disponibilità per tutti i consumi.
            // Se il lotto non ha ancora consumi (caso storico / dati incompleti),
            // prova a opzionarli automaticamente dai materiali richiesti.
            $consumiPendingQuery = $lotto->consumiMateriale()
                ->with('lottoMateriale')
                ->whereIn('stato', [
                    StatoConsumoMateriale::PIANIFICATO->value,
                    StatoConsumoMateriale::OPZIONATO->value,
                ]);

            $consumi = $consumiPendingQuery->get();
            $haConsumi = $lotto->consumiMateriale()->exists();

            if ($consumi->isEmpty() && ! $haConsumi && $haFabbisognoMateriali) {
                $consumiCreati = $this->pianificaConsumiLottoStandalone(
                    $lotto->fresh(['materialiUsati.prodotto', 'componentiManuali.prodotto']),
                    "Opzionato automaticamente in completamento lotto {$lotto->codice_lotto}"
                );

                if ($consumiCreati > 0) {
                    $consumi = $consumiPendingQuery->get();
                    $haConsumi = $lotto->consumiMateriale()->exists();
                }
            }

            if ($consumi->isEmpty() && ! $haConsumi && $haFabbisognoMateriali) {
                // Legacy fallback:
                // if the lot was historically completed via direct scarichi linked to lotto_produzione_id,
                // rebuild consumed rows so completion can proceed and traceability remains visible.
                $consumiStorici = $this->sincronizzaConsumiDaScarichiStorici($lotto);
                if ($consumiStorici > 0) {
                    $haConsumi = true;
                }
            }

            if ($consumi->isEmpty() && ! $haConsumi && $haFabbisognoMateriali) {
                throw new \Exception(
                    "Nessun materiale opzionabile trovato in magazzino per completare il lotto {$lotto->codice_lotto}"
                );
            }

            $mancanti = [];

            foreach ($consumi as $consumo) {
                $giacenza = $consumo->lottoMateriale->giacenza;
                if ($giacenza < $consumo->quantita) {
                    $mancanti[] = [
                        'lotto_materiale_id' => $consumo->lotto_materiale_id,
                        'codice_lotto' => $consumo->lottoMateriale->codice_lotto,
                        'necessario' => $consumo->quantita,
                        'disponibile' => $giacenza,
                    ];
                }
            }

            if (! empty($mancanti)) {
                throw new InsufficientStockException($mancanti);
            }

            // Genera gli scarichi per tutti i consumi
            $this->generaScarichi($lotto, $user);

            // Calcola FITOK
            $lotto->calcolaFitok();

            // Registra gli scarti di produzione
            $this->registraScarti($lotto);

            $lotto->update([
                'stato' => StatoLottoProduzione::COMPLETATO,
                'data_fine' => now(),
                'completato_at' => now(),
            ]);

            $this->syncOrdineStateAfterLottoCompletion($lotto);

            return $lotto->fresh(['ordine']);
        });
    }

    private function syncOrdineStateAfterLottoCompletion(LottoProduzione $lotto): void
    {
        if (! $lotto->ordine_id) {
            return;
        }

        $ordine = $lotto->ordine()->with('lottiProduzione')->first();
        if (! $ordine) {
            return;
        }

        if (! in_array($ordine->stato, [
            StatoOrdine::CONFERMATO,
            StatoOrdine::IN_PRODUZIONE,
        ], true)) {
            return;
        }

        $hasPendingLots = $ordine->lottiProduzione
            ->contains(fn (LottoProduzione $item) => ! in_array($item->stato, [
                StatoLottoProduzione::COMPLETATO,
                StatoLottoProduzione::ANNULLATO,
            ], true));

        if (! $hasPendingLots) {
            $ordine->update(['stato' => StatoOrdine::PRONTO]);
        }
    }

    private function lottoHaFabbisognoMateriali(LottoProduzione $lotto): bool
    {
        return $lotto->materialiUsati()
            ->whereNotNull('prodotto_id')
            ->exists()
            || $lotto->componentiManuali()
                ->whereNotNull('prodotto_id')
                ->exists();
    }

    /**
     * Annulla il lotto
     */
    public function annullaLotto(LottoProduzione $lotto): LottoProduzione
    {
        return DB::transaction(function () use ($lotto) {
            if ($lotto->stato === StatoLottoProduzione::COMPLETATO) {
                throw new \Exception(
                    'Impossibile annullare un lotto già completato'
                );
            }

            $lotto->update([
                'stato' => StatoLottoProduzione::ANNULLATO,
            ]);

            $lotto->consumiMateriale()
                ->whereIn('stato', [
                    StatoConsumoMateriale::OPZIONATO->value,
                    StatoConsumoMateriale::PIANIFICATO->value,
                ])
                ->update([
                    'stato' => StatoConsumoMateriale::RILASCIATO->value,
                    'released_at' => now(),
                ]);

            return $lotto->fresh();
        });
    }

    /**
     * Registra gli scarti prodotti durante la lavorazione del lotto
     */
    public function registraScarti(LottoProduzione $lotto): Collection
    {
        $scarti = collect();
        $this->clearExistingScrapReuseResiduals($lotto);
        $consumiPerProdotto = $lotto->consumiMateriale()
            ->with('lottoMateriale')
            ->get()
            ->filter(fn (ConsumoMateriale $consumo) => $consumo->lotto_materiale_id !== null && $consumo->lottoMateriale?->prodotto_id !== null)
            ->groupBy(fn (ConsumoMateriale $consumo) => (string) $consumo->lottoMateriale->prodotto_id);

        foreach ($lotto->materialiUsati as $materiale) {
            // Skip if no scrap was generated
            if (! $materiale->scarto_totale_mm || $materiale->scarto_totale_mm <= 0) {
                continue;
            }

            $lottoMaterialeId = $this->resolveLottoMaterialeIdPerScarto($materiale, $consumiPerProdotto);
            if ($lottoMaterialeId === null) {
                continue;
            }

            // Calculate volume in cubic meters
            // Volume = length * width * thickness (all in mm) / 1,000,000,000
            $volumeMc = ($materiale->scarto_totale_mm * $materiale->larghezza_mm * $materiale->spessore_mm) / 1000000000;

            // Determine if scrap is reusable using configurable threshold.
            $riutilizzabile = $materiale->scarto_totale_mm >= $this->productionSettings->scrapReusableMinLengthMm();

            $scarto = Scarto::create([
                'lotto_produzione_id' => $lotto->id,
                'lotto_materiale_id' => $lottoMaterialeId,
                'lunghezza_mm' => $materiale->scarto_totale_mm,
                'larghezza_mm' => $materiale->larghezza_mm,
                'spessore_mm' => $materiale->spessore_mm,
                'volume_mc' => round($volumeMc, 6),
                'riutilizzabile' => $riutilizzabile,
                'riutilizzato' => false,
                'note' => $materiale->lotto_materiale_id
                    ? "Scarto da lotto {$lotto->codice_lotto}"
                    : "Scarto da lotto {$lotto->codice_lotto} (lotto materiale dedotto dai consumi)",
            ]);

            $scarti->push($scarto);
        }

        $scarti = $scarti->merge($this->registraResiduiDaRiutilizzoScarti($lotto));

        return $scarti;
    }

    private function clearExistingScrapReuseResiduals(LottoProduzione $lotto): void
    {
        Scarto::query()
            ->where('lotto_produzione_id', $lotto->id)
            ->where('note', 'like', self::SCRAP_REUSE_RESIDUAL_NOTE_PREFIX.'%')
            ->delete();
    }

    private function registraResiduiDaRiutilizzoScarti(LottoProduzione $lotto): Collection
    {
        $optimizerResult = OptimizerResultPayload::normalizeForRuntime($lotto->optimizer_result);
        $sourceSummaries = data_get($optimizerResult, 'trace.scrap_reuse.source_summaries');

        if (! is_array($sourceSummaries) || $sourceSummaries === []) {
            return collect();
        }

        return collect($sourceSummaries)
            ->filter(fn ($summary) => is_array($summary) && (float) ($summary['remaining_length_mm'] ?? 0) > 0)
            ->map(function (array $summary) use ($lotto): ?Scarto {
                $lottoMaterialeId = (int) ($summary['lotto_materiale_id'] ?? 0);
                if ($lottoMaterialeId <= 0) {
                    return null;
                }

                $remainingLength = round((float) ($summary['remaining_length_mm'] ?? 0), 2);
                $remainingWidth = round((float) ($summary['remaining_width_mm'] ?? 0), 2);
                $remainingThickness = round((float) ($summary['remaining_thickness_mm'] ?? 0), 2);
                $volumeMc = round(max(0, ($remainingLength * $remainingWidth * $remainingThickness) / 1000000000), 6);

                if ($volumeMc <= 0) {
                    return null;
                }

                $sourceScrapId = (int) ($summary['source_scrap_id'] ?? 0);
                $sourceLottoCode = (string) ($summary['source_lotto_produzione_code'] ?? '');

                return Scarto::create([
                    'lotto_produzione_id' => $lotto->id,
                    'lotto_materiale_id' => $lottoMaterialeId,
                    'lunghezza_mm' => $remainingLength,
                    'larghezza_mm' => $remainingWidth,
                    'spessore_mm' => $remainingThickness,
                    'volume_mc' => $volumeMc,
                    'riutilizzabile' => $remainingLength >= $this->productionSettings->scrapReusableMinLengthMm(),
                    'riutilizzato' => false,
                    'note' => sprintf(
                        '%s Residuo da riuso scarto #%d durante il lotto %s%s',
                        self::SCRAP_REUSE_RESIDUAL_NOTE_PREFIX,
                        $sourceScrapId,
                        $lotto->codice_lotto,
                        $sourceLottoCode !== '' ? " (scarto origine lotto {$sourceLottoCode})" : ''
                    ),
                ]);
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<string, Collection<int, ConsumoMateriale>>  $consumiPerProdotto
     */
    private function resolveLottoMaterialeIdPerScarto(object $materiale, Collection $consumiPerProdotto): ?int
    {
        if (! empty($materiale->lotto_materiale_id)) {
            return (int) $materiale->lotto_materiale_id;
        }

        if (empty($materiale->prodotto_id)) {
            return null;
        }

        /** @var Collection<int, ConsumoMateriale> $consumiCompatibili */
        $consumiCompatibili = $consumiPerProdotto->get((string) $materiale->prodotto_id, collect());
        if ($consumiCompatibili->isEmpty()) {
            return null;
        }

        return (int) $consumiCompatibili->first()->lotto_materiale_id;
    }

    /**
     * Genera i movimenti di scarico per i consumi del lotto
     */
    public function generaScarichi(LottoProduzione $lotto, User $user): Collection
    {
        $movimenti = collect();

        foreach ($lotto->consumiMateriale()->whereNull('movimento_id')->get() as $consumo) {
            $movimento = $this->inventoryService->scarico(
                lotto: $consumo->lottoMateriale,
                quantita: $consumo->quantita,
                lottoProduzione: $lotto,
                documento: null,
                user: $user,
                causale: "Scarico per lotto produzione {$lotto->codice_lotto}"
            );

            $consumo->update([
                'movimento_id' => $movimento->id,
                'stato' => StatoConsumoMateriale::CONSUMATO,
                'consumato_at' => now(),
            ]);
            $movimenti->push($movimento);
        }

        return $movimenti;
    }

    /**
     * Genera un codice lotto univoco
     */
    private function generaCodiceLotto(): string
    {
        $anno = now()->format('Y');
        $mese = now()->format('m');

        // Conta i lotti del mese corrente
        $count = LottoProduzione::whereYear('created_at', $anno)
            ->whereMonth('created_at', $mese)
            ->count();

        return sprintf('LP-%s%s-%04d', $anno, $mese, $count + 1);
    }

    private function creaAggiornaBomPerLotto(LottoProduzione $lotto, ?User $user): Bom
    {
        $lines = $this->buildBomLinesFromLotto($lotto);

        $payload = [
            'nome' => "Distinta materiali lotto {$lotto->codice_lotto}",
            'prodotto_id' => null,
            'lotto_produzione_id' => $lotto->id,
            'ordine_id' => null,
            'categoria_output' => null,
            'versione' => '1.0',
            'is_active' => true,
            'generated_at' => now(),
            'source' => 'lotto',
            'note' => "Distinta materiali generata automaticamente da avvio lotto {$lotto->codice_lotto}",
        ];

        $bom = Bom::query()
            ->where('lotto_produzione_id', $lotto->id)
            ->where('source', 'lotto')
            ->latest('id')
            ->first();

        if ($bom) {
            $bom->update($payload);
            $bom->righe()->delete();
        } else {
            $bom = Bom::create(array_merge($payload, [
                'created_by' => $user?->id,
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
     * @return array<string, array{
     *   prodotto_id:int|null,
     *   descrizione:string,
     *   quantita:float,
     *   unita_misura:string,
     *   is_fitok_required:bool,
     *   note:string
     * }>
     */
    private function buildBomLinesFromLotto(LottoProduzione $lotto): array
    {
        $lotto->loadMissing(['materialiUsati.prodotto', 'componentiManuali.prodotto']);

        $lines = [];

        foreach ($lotto->materialiUsati as $materiale) {
            $prodotto = $materiale->prodotto;
            $unita = $prodotto?->unita_misura?->value ?? 'mc';
            $quantita = $this->resolveQuantitaMaterialeForBom($materiale, $unita);

            $this->addBomLine(
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
            $unita = strtolower($componenteManuale->unita_misura ?: ($prodotto?->unita_misura?->value ?? 'pz'));

            $this->addBomLine(
                lines: $lines,
                prodottoId: $prodotto?->id,
                descrizione: $prodotto?->nome ?? 'Componente manuale',
                quantita: (float) $componenteManuale->quantita,
                unita: $unita,
                isFitokRequired: (bool) ($prodotto?->soggetto_fitok ?? false),
                note: "Componente manuale lotto {$lotto->codice_lotto}"
            );
        }

        return $lines;
    }

    /**
     * @param array<string, array{
     *   prodotto_id:int|null,
     *   descrizione:string,
     *   quantita:float,
     *   unita_misura:string,
     *   is_fitok_required:bool,
     *   note:string
     * }> $lines
     */
    private function addBomLine(
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

        $unitaNorm = strtolower($unita);
        $key = $prodottoId
            ? implode('|', [(string) $prodottoId, $unitaNorm])
            : implode('|', ['0', $unitaNorm, strtolower($descrizione)]);

        if (! isset($lines[$key])) {
            $lines[$key] = [
                'prodotto_id' => $prodottoId,
                'descrizione' => $descrizione,
                'quantita' => 0,
                'unita_misura' => $unitaNorm,
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

    private function resolveQuantitaMaterialeForBom(object $materiale, string $unita): float
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

    private function pianificaConsumiLottoStandalone(LottoProduzione $lotto, string $note): int
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
                + $this->resolveQuantitaMaterialeForBom($materiale, $unita);
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
                    'note' => $note,
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

    private function sincronizzaConsumiDaScarichiStorici(LottoProduzione $lotto): int
    {
        $scarichiStorici = MovimentoMagazzino::query()
            ->where('lotto_produzione_id', $lotto->id)
            ->where('tipo', TipoMovimento::SCARICO->value)
            ->whereNotNull('lotto_materiale_id')
            ->orderBy('data_movimento')
            ->orderBy('id')
            ->get()
            ->groupBy('lotto_materiale_id');

        if ($scarichiStorici->isEmpty()) {
            return 0;
        }

        $consumiSincronizzati = 0;

        foreach ($scarichiStorici as $lottoMaterialeId => $movimenti) {
            $quantita = round((float) $movimenti->sum('quantita'), 4);
            if ($quantita <= 0) {
                continue;
            }

            $movimentoRiferimento = $movimenti->last();

            ConsumoMateriale::updateOrCreate(
                [
                    'lotto_produzione_id' => $lotto->id,
                    'lotto_materiale_id' => (int) $lottoMaterialeId,
                ],
                [
                    'movimento_id' => $movimentoRiferimento?->id,
                    'quantita' => $quantita,
                    'stato' => StatoConsumoMateriale::CONSUMATO->value,
                    'consumato_at' => $movimentoRiferimento?->data_movimento ?? now(),
                    'note' => "Consumo ricostruito da scarichi storici lotto {$lotto->codice_lotto}",
                ]
            );

            $consumiSincronizzati++;
        }

        return $consumiSincronizzati;
    }

    /**
     * Ottiene le statistiche di produzione per un periodo
     */
    public function getStatistichePeriodo(\DateTime $dataInizio, \DateTime $dataFine): array
    {
        $lotti = LottoProduzione::whereBetween('created_at', [$dataInizio, $dataFine])->get();

        return [
            'totale_lotti' => $lotti->count(),
            'completati' => $lotti->where('stato', StatoLottoProduzione::COMPLETATO)->count(),
            'in_lavorazione' => $lotti->where('stato', StatoLottoProduzione::IN_LAVORAZIONE)->count(),
            'bozza' => $lotti->where('stato', StatoLottoProduzione::BOZZA)->count(),
            'annullati' => $lotti->where('stato', StatoLottoProduzione::ANNULLATO)->count(),
        ];
    }
}
