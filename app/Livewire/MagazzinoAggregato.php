<?php

namespace App\Livewire;

use App\Enums\Categoria;
use App\Models\Bom;
use App\Models\Prodotto;
use App\Models\Scarto;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;

class MagazzinoAggregato extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoria = '';
    public string $filtroFitok = ''; // 'solo_fitok', 'solo_non_fitok', ''
    public string $filtroScarti = ''; // 'con_scarti', 'senza_scarti', ''
    public string $filtroGiacenza = 'positiva'; // 'positiva', 'zero', ''
    public string $activeTab = 'giacenze';
    public string $sortField = 'nome';
    public string $sortDirection = 'asc';
    public ?int $expanded = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoria' => ['except' => ''],
        'filtroFitok' => ['except' => ''],
        'filtroScarti' => ['except' => ''],
        'filtroGiacenza' => ['except' => 'positiva'],
        'activeTab' => ['except' => 'giacenze'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoria(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroFitok(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroScarti(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroGiacenza(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['giacenze', 'opzionato', 'scarti'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function toggle(int $prodottoId): void
    {
        $this->expanded = $this->expanded === $prodottoId ? null : $prodottoId;
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'categoria',
            'filtroFitok',
            'filtroScarti',
            'filtroGiacenza',
        ]);
        $this->resetPage();
    }

    private function scartoVolumeExpression(string $table = 'scarti'): string
    {
        return "((COALESCE({$table}.lunghezza_mm, 0) * COALESCE({$table}.larghezza_mm, 0) * COALESCE({$table}.spessore_mm, 0)) / 1000000000)";
    }

    public function render()
    {
        if (! in_array($this->activeTab, ['giacenze', 'opzionato', 'scarti'], true)) {
            $this->activeTab = 'giacenze';
        }

        $inventoryService = app(InventoryService::class);

        $query = Prodotto::query()
            ->with([
                'lottiMateriale' => function ($q) {
                    $q->with('movimenti');
                }
            ]);

        // Search filter
        if ($this->search) {
            $query->search($this->search);
        }

        // Categoria filter
        if ($this->categoria) {
            $query->where('categoria', $this->categoria);
        }

        // FITOK filter
        if ($this->filtroFitok === 'solo_fitok') {
            $query->where('soggetto_fitok', true);
        } elseif ($this->filtroFitok === 'solo_non_fitok') {
            $query->where('soggetto_fitok', false);
        }

        // Sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Get all matching products
        $allProducts = $query->get();
        $productIds = $allProducts->pluck('id')->all();

        $scartiByProduct = collect();
        $consumiByProduct = collect();
        if (!empty($productIds)) {
            $scartiByProduct = \DB::table('scarti')
                ->join('lotti_materiale', 'scarti.lotto_materiale_id', '=', 'lotti_materiale.id')
                ->whereIn('lotti_materiale.prodotto_id', $productIds)
                ->where('scarti.riutilizzabile', true)
                ->where('scarti.riutilizzato', false)
                ->selectRaw('lotti_materiale.prodotto_id, COALESCE(SUM('.$this->scartoVolumeExpression('scarti').'), 0) as scarti_volume')
                ->groupBy('lotti_materiale.prodotto_id')
                ->get()
                ->keyBy('prodotto_id');

            $consumiByProduct = \DB::table('consumi_materiale')
                ->join('lotti_materiale', 'consumi_materiale.lotto_materiale_id', '=', 'lotti_materiale.id')
                ->whereIn('lotti_materiale.prodotto_id', $productIds)
                ->selectRaw('lotti_materiale.prodotto_id')
                ->selectRaw('COALESCE(SUM(consumi_materiale.quantita), 0) as totale_consumato')
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN lotti_materiale.fitok_certificato IS NOT NULL THEN consumi_materiale.quantita ELSE 0 END), 0) as totale_consumato_fitok'
                )
                ->groupBy('lotti_materiale.prodotto_id')
                ->get()
                ->keyBy('prodotto_id');
        }

        // Calculate aggregated data for each product
        $prodottiConDati = $allProducts->map(function ($prodotto) use ($inventoryService, $scartiByProduct, $consumiByProduct) {
            // Calculate total stock from all lotti
            $lottiConGiacenza = $prodotto->lottiMateriale->map(function ($lotto) use ($inventoryService) {
                $lotto->giacenza_calcolata = $inventoryService->calcolaGiacenza($lotto);
                return $lotto;
            })->filter(fn($l) => $l->giacenza_calcolata > 0);

            $prodotto->giacenza_totale = $lottiConGiacenza->sum('giacenza_calcolata');
            $prodotto->lotti_attivi_count = $lottiConGiacenza->count();

            // Calculate FITOK percentage for this product
            if ($prodotto->soggetto_fitok) {
                $totalVolume = 0;
                $fitokVolume = 0;

                foreach ($lottiConGiacenza as $lotto) {
                    $volume = $lotto->giacenza_calcolata;
                    $totalVolume += $volume;

                    // Consider FITOK if has certificate
                    if ($lotto->fitok_certificato) {
                        $fitokVolume += $volume;
                    }
                }

                $prodotto->fitok_percentuale_giacenza = $totalVolume > 0
                    ? round(($fitokVolume / $totalVolume) * 100, 2)
                    : 0;

                $consumi = $consumiByProduct->get($prodotto->id);
                $totaleConsumato = (float) ($consumi?->totale_consumato ?? 0);
                $totaleConsumatoFitok = (float) ($consumi?->totale_consumato_fitok ?? 0);
                $prodotto->fitok_percentuale_produzione = $totaleConsumato > 0
                    ? round(($totaleConsumatoFitok / $totaleConsumato) * 100, 2)
                    : null;

                // Backward compatibility: existing sort/filter logic expects fitok_percentuale.
                $prodotto->fitok_percentuale = $prodotto->fitok_percentuale_giacenza;
            } else {
                $prodotto->fitok_percentuale_giacenza = null;
                $prodotto->fitok_percentuale_produzione = null;
                $prodotto->fitok_percentuale = null;
            }

            // Calculate reusable scraps volume
            $scarti = $scartiByProduct->get($prodotto->id);
            $prodotto->scarti_volume = (float) ($scarti?->scarti_volume ?? 0);

            $prodotto->lottiConGiacenza = $lottiConGiacenza;

            return $prodotto;
        });

        $prodottiPerTracciabilita = $prodottiConDati;

        // Apply post-query filters
        if ($this->filtroGiacenza === 'positiva') {
            $prodottiConDati = $prodottiConDati->filter(fn($p) => $p->giacenza_totale > 0);
        } elseif ($this->filtroGiacenza === 'zero') {
            $prodottiConDati = $prodottiConDati->filter(fn($p) => $p->giacenza_totale <= 0);
        }

        if ($this->filtroScarti === 'con_scarti') {
            $prodottiConDati = $prodottiConDati->filter(fn($p) => $p->scarti_volume > 0);
        } elseif ($this->filtroScarti === 'senza_scarti') {
            $prodottiConDati = $prodottiConDati->filter(fn($p) => $p->scarti_volume <= 0);
        }

        // Sort by calculated fields if needed
        if ($this->sortField === 'giacenza_totale') {
            $prodottiConDati = $this->sortDirection === 'asc'
                ? $prodottiConDati->sortBy('giacenza_totale')
                : $prodottiConDati->sortByDesc('giacenza_totale');
        } elseif ($this->sortField === 'fitok_percentuale') {
            $prodottiConDati = $this->sortDirection === 'asc'
                ? $prodottiConDati->sortBy('fitok_percentuale')
                : $prodottiConDati->sortByDesc('fitok_percentuale');
        } elseif ($this->sortField === 'scarti_volume') {
            $prodottiConDati = $this->sortDirection === 'asc'
                ? $prodottiConDati->sortBy('scarti_volume')
                : $prodottiConDati->sortByDesc('scarti_volume');
        }

        // Manual pagination
        $perPage = 15;
        $currentPage = $this->getPage();
        $prodottiPaginati = new \Illuminate\Pagination\LengthAwarePaginator(
            $prodottiConDati->forPage($currentPage, $perPage),
            $prodottiConDati->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $totaleScarti = (float) $prodottiConDati->sum('scarti_volume');
        $totaleScartiFitok = (float) $prodottiConDati
            ->filter(fn($p) => (bool) $p->soggetto_fitok)
            ->sum('scarti_volume');
        $totaleScartiNonFitok = (float) $prodottiConDati
            ->filter(fn($p) => !(bool) $p->soggetto_fitok)
            ->sum('scarti_volume');

        $scartiTracciabilita = collect();
        $scartiRiepilogoCompatibili = collect();
        $consumiTracciabilita = collect();
        $totaliConsumiTracciabilita = [
            'totale' => 0.0,
            'opzionato' => 0.0,
            'consumato' => 0.0,
        ];
        $totaliScartiTracciabilita = [
            'totale' => 0.0,
            'pezzi_totali' => 0,
            'peso_totale_kg' => 0.0,
            'riutilizzabile_disponibile' => 0.0,
            'riutilizzato' => 0.0,
            'non_riutilizzabile' => 0.0,
        ];

        $filteredProductIds = $prodottiPerTracciabilita->pluck('id')->all();
        if (!empty($filteredProductIds)) {
            $scartiTracciabilita = \DB::table('scarti')
                ->join('lotti_materiale', 'scarti.lotto_materiale_id', '=', 'lotti_materiale.id')
                ->leftJoin('prodotti', 'lotti_materiale.prodotto_id', '=', 'prodotti.id')
                ->leftJoin('lotti_produzione as lotti_origine', 'scarti.lotto_produzione_id', '=', 'lotti_origine.id')
                ->leftJoin('lotti_produzione as lotti_riuso', 'scarti.riutilizzato_in_lotto_id', '=', 'lotti_riuso.id')
                ->whereIn('lotti_materiale.prodotto_id', $filteredProductIds)
                ->select([
                    'scarti.id',
                    'scarti.created_at',
                    'scarti.volume_mc',
                    'scarti.lunghezza_mm',
                    'scarti.larghezza_mm',
                    'scarti.spessore_mm',
                    'scarti.riutilizzabile',
                    'scarti.riutilizzato',
                    'scarti.note',
                    'lotti_materiale.codice_lotto as lotto_materiale_codice',
                    'prodotti.codice as prodotto_codice',
                    'prodotti.nome as prodotto_nome',
                    'prodotti.peso_specifico_kg_mc',
                    'lotti_origine.codice_lotto as lotto_produzione_codice',
                    'lotti_riuso.codice_lotto as lotto_riuso_codice',
                ])
                ->orderByDesc('scarti.created_at')
                ->get()
                ->map(function ($scarto) {
                    $volume = Scarto::calculateVolumeMcFromDimensions(
                        $scarto->lunghezza_mm,
                        $scarto->larghezza_mm,
                        $scarto->spessore_mm
                    );
                    $tipo = 'Non riutilizzabile';

                    if ((bool) $scarto->riutilizzato) {
                        $tipo = 'Riutilizzato';
                    } elseif ((bool) $scarto->riutilizzabile) {
                        $tipo = 'Riutilizzabile disponibile';
                    }

                    $scarto->volume_mc = $volume;
                    $scarto->tipologia_label = $tipo;
                    $pesoSpecifico = (float) ($scarto->peso_specifico_kg_mc ?? 0);
                    $scarto->peso_stimato_kg = $pesoSpecifico > 0
                        ? round(Scarto::calculateWeightKgFromVolume($volume, $pesoSpecifico), 3)
                        : null;

                    return $scarto;
                });

            $totaliScartiTracciabilita['totale'] = (float) $scartiTracciabilita->sum('volume_mc');
            $totaliScartiTracciabilita['pezzi_totali'] = (int) $scartiTracciabilita->count();
            $totaliScartiTracciabilita['peso_totale_kg'] = (float) $scartiTracciabilita
                ->sum(fn($scarto) => (float) ($scarto->peso_stimato_kg ?? 0));
            $totaliScartiTracciabilita['riutilizzabile_disponibile'] = (float) $scartiTracciabilita
                ->filter(fn($s) => (bool) $s->riutilizzabile && !(bool) $s->riutilizzato)
                ->sum('volume_mc');
            $totaliScartiTracciabilita['riutilizzato'] = (float) $scartiTracciabilita
                ->filter(fn($s) => (bool) $s->riutilizzato)
                ->sum('volume_mc');
            $totaliScartiTracciabilita['non_riutilizzabile'] = (float) $scartiTracciabilita
                ->filter(fn($s) => !(bool) $s->riutilizzabile && !(bool) $s->riutilizzato)
                ->sum('volume_mc');

            $scartiRiepilogoCompatibili = \DB::table('scarti')
                ->join('lotti_materiale', 'scarti.lotto_materiale_id', '=', 'lotti_materiale.id')
                ->leftJoin('prodotti', 'lotti_materiale.prodotto_id', '=', 'prodotti.id')
                ->whereIn('lotti_materiale.prodotto_id', $filteredProductIds)
                ->where('scarti.riutilizzabile', true)
                ->where('scarti.riutilizzato', false)
                ->select([
                    'prodotti.codice as prodotto_codice',
                    'prodotti.nome as prodotto_nome',
                    'scarti.lunghezza_mm',
                    'scarti.larghezza_mm',
                    'scarti.spessore_mm',
                ])
                ->selectRaw('COUNT(*) as pezzi')
                ->selectRaw('COALESCE(SUM('.$this->scartoVolumeExpression('scarti').'), 0) as volume_mc')
                ->selectRaw('COALESCE(SUM('.$this->scartoVolumeExpression('scarti').' * COALESCE(prodotti.peso_specifico_kg_mc, 0)), 0) as peso_totale_kg')
                ->groupBy(
                    'prodotti.codice',
                    'prodotti.nome',
                    'scarti.lunghezza_mm',
                    'scarti.larghezza_mm',
                    'scarti.spessore_mm'
                )
                ->orderByDesc('pezzi')
                ->get();

            $consumiTracciabilitaRaw = \DB::table('consumi_materiale')
                ->join('lotti_materiale', 'consumi_materiale.lotto_materiale_id', '=', 'lotti_materiale.id')
                ->leftJoin('prodotti', 'lotti_materiale.prodotto_id', '=', 'prodotti.id')
                ->leftJoin('lotti_produzione', 'consumi_materiale.lotto_produzione_id', '=', 'lotti_produzione.id')
                ->leftJoin('ordini', 'lotti_produzione.ordine_id', '=', 'ordini.id')
                ->whereIn('lotti_materiale.prodotto_id', $filteredProductIds)
                ->whereIn('consumi_materiale.stato', ['opzionato', 'consumato'])
                ->select([
                    'consumi_materiale.id',
                    'consumi_materiale.lotto_produzione_id',
                    'consumi_materiale.quantita',
                    'consumi_materiale.stato',
                    'consumi_materiale.note',
                    'consumi_materiale.created_at',
                    'consumi_materiale.opzionato_at',
                    'consumi_materiale.consumato_at',
                    'lotti_materiale.codice_lotto as lotto_materiale_codice',
                    'prodotti.codice as prodotto_codice',
                    'prodotti.nome as prodotto_nome',
                    'prodotti.unita_misura',
                    'lotti_produzione.codice_lotto as lotto_produzione_codice',
                    'lotti_produzione.ordine_id as ordine_id',
                    'ordini.numero as ordine_numero',
                ])
                ->orderByDesc('consumi_materiale.updated_at')
                ->get();

            $lottoIds = $consumiTracciabilitaRaw
                ->pluck('lotto_produzione_id')
                ->filter()
                ->unique()
                ->values();
            $ordineIds = $consumiTracciabilitaRaw
                ->pluck('ordine_id')
                ->filter()
                ->unique()
                ->values();

            $bomByLotto = $lottoIds->isEmpty()
                ? collect()
                : Bom::query()
                    ->whereIn('lotto_produzione_id', $lottoIds->all())
                    ->where('source', 'lotto')
                    ->orderByDesc('id')
                    ->get()
                    ->unique('lotto_produzione_id')
                    ->keyBy('lotto_produzione_id');

            $bomByOrdine = $ordineIds->isEmpty()
                ? collect()
                : Bom::query()
                    ->whereIn('ordine_id', $ordineIds->all())
                    ->where('source', 'ordine')
                    ->orderByDesc('id')
                    ->get()
                    ->unique('ordine_id')
                    ->keyBy('ordine_id');

            $consumiTracciabilita = $consumiTracciabilitaRaw->map(function ($consumo) use ($bomByLotto, $bomByOrdine) {
                $eventoAt = $consumo->consumato_at ?? $consumo->opzionato_at ?? $consumo->created_at;
                $bom = $bomByLotto->get($consumo->lotto_produzione_id);
                if (!$bom && $consumo->ordine_id) {
                    $bom = $bomByOrdine->get($consumo->ordine_id);
                }

                $consumo->data_evento = $eventoAt;
                $consumo->unita_misura = strtolower((string) ($consumo->unita_misura ?? ''));
                $consumo->bom_codice = $bom?->codice;
                $consumo->display_note = $this->buildConsumoDisplayNote($consumo);

                return $consumo;
            });

            $totaliConsumiTracciabilita['totale'] = (float) $consumiTracciabilita->sum('quantita');
            $totaliConsumiTracciabilita['opzionato'] = (float) $consumiTracciabilita
                ->where('stato', 'opzionato')
                ->sum('quantita');
            $totaliConsumiTracciabilita['consumato'] = (float) $consumiTracciabilita
                ->where('stato', 'consumato')
                ->sum('quantita');
        }

        return view('livewire.magazzino-aggregato', [
            'prodotti' => $prodottiPaginati,
            'categorie' => Categoria::cases(),
            'totaliScarti' => [
                'totale' => round($totaleScarti, 3),
                'fitok' => round($totaleScartiFitok, 3),
                'non_fitok' => round($totaleScartiNonFitok, 3),
            ],
            'scartiTracciabilita' => $scartiTracciabilita,
            'scartiRiepilogoCompatibili' => $scartiRiepilogoCompatibili,
            'consumiTracciabilita' => $consumiTracciabilita,
            'totaliConsumiTracciabilita' => [
                'totale' => round($totaliConsumiTracciabilita['totale'], 3),
                'opzionato' => round($totaliConsumiTracciabilita['opzionato'], 3),
                'consumato' => round($totaliConsumiTracciabilita['consumato'], 3),
            ],
            'totaliScartiTracciabilita' => [
                'totale' => round($totaliScartiTracciabilita['totale'], 3),
                'pezzi_totali' => $totaliScartiTracciabilita['pezzi_totali'],
                'peso_totale_kg' => round($totaliScartiTracciabilita['peso_totale_kg'], 3),
                'riutilizzabile_disponibile' => round($totaliScartiTracciabilita['riutilizzabile_disponibile'], 3),
                'riutilizzato' => round($totaliScartiTracciabilita['riutilizzato'], 3),
                'non_riutilizzabile' => round($totaliScartiTracciabilita['non_riutilizzabile'], 3),
            ],
        ]);
    }

    private function buildConsumoDisplayNote(object $consumo): string
    {
        $stato = strtolower((string) ($consumo->stato ?? ''));
        $ordineNumero = trim((string) ($consumo->ordine_numero ?? ''));
        $lottoCodice = trim((string) ($consumo->lotto_produzione_codice ?? ''));
        $fallback = trim((string) ($consumo->note ?? ''));

        $destinazione = $ordineNumero !== ''
            ? "ordine {$ordineNumero}"
            : ($lottoCodice !== '' ? "lotto {$lottoCodice}" : '');

        if ($stato === 'consumato' && $destinazione !== '') {
            return "Utilizzato in {$destinazione}";
        }

        if ($stato === 'opzionato' && $destinazione !== '') {
            return "Opzionato per {$destinazione}";
        }

        if ($fallback !== '') {
            return $fallback;
        }

        return '-';
    }
}
