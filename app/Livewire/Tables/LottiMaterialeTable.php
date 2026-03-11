<?php

namespace App\Livewire\Tables;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\LottoMateriale;
use App\Models\Prodotto;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;

class LottiMaterialeTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $prodotto = '';
    public string $categoria = '';
    public string $giacenza = '';
    public bool $soloFitok = false;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'prodotto' => ['except' => ''],
        'categoria' => ['except' => ''],
        'giacenza' => ['except' => ''],
        'soloFitok' => ['except' => false],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingProdotto(): void
    {
        $this->resetPage();
    }

    public function updatingCategoria(): void
    {
        $this->resetPage();
    }

    public function updatingGiacenza(): void
    {
        $this->resetPage();
    }

    public function updatingSoloFitok(): void
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

    public function resetFilters(): void
    {
        $this->reset(['search', 'prodotto', 'categoria', 'giacenza', 'soloFitok']);
        $this->resetPage();
    }

    public function render()
    {
        $inventoryService = app(InventoryService::class);

        $query = LottoMateriale::query()
            ->with(['prodotto', 'movimenti']);

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->prodotto) {
            $query->where('prodotto_id', $this->prodotto);
        }

        if ($this->categoria) {
            $query->whereHas('prodotto', function ($q) {
                $q->where('categoria', $this->categoria);
            });
        }

        if ($this->soloFitok) {
            $query->whereHas('prodotto', function ($q) {
                $q->where('soggetto_fitok', true);
            });
        }

        // Sorting
        if ($this->sortField === 'prodotto') {
            $query->join('prodotti', 'lotti_materiale.prodotto_id', '=', 'prodotti.id')
                ->select('lotti_materiale.*')
                ->orderBy('prodotti.nome', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $lotti = $query->paginate(15);

        // Calcola giacenze per ogni lotto
        $lottiConGiacenza = $lotti->getCollection()->map(function ($lotto) use ($inventoryService) {
            $lotto->giacenza_calcolata = $inventoryService->calcolaGiacenza($lotto);

            $pesoVisualizzato = $lotto->peso_totale_kg !== null
                ? (float) $lotto->peso_totale_kg
                : null;

            if ($pesoVisualizzato === null) {
                $unita = $lotto->prodotto?->unita_misura?->value;
                $giacenza = (float) $lotto->giacenza_calcolata;

                if ($unita === UnitaMisura::KG->value) {
                    $pesoVisualizzato = $giacenza;
                } elseif ($unita === UnitaMisura::MC->value) {
                    $pesoSpecifico = (float) ($lotto->prodotto?->peso_specifico_kg_mc ?? 360);
                    $pesoVisualizzato = $giacenza > 0 ? round($giacenza * $pesoSpecifico, 3) : 0.0;
                }
            }

            $lotto->peso_visualizzato_kg = $pesoVisualizzato;

            return $lotto;
        });

        // Filtra per giacenza se richiesto
        if ($this->giacenza === 'positiva') {
            $lottiConGiacenza = $lottiConGiacenza->filter(fn($l) => $l->giacenza_calcolata > 0);
        } elseif ($this->giacenza === 'esaurita') {
            $lottiConGiacenza = $lottiConGiacenza->filter(fn($l) => $l->giacenza_calcolata <= 0);
        } elseif ($this->giacenza === 'bassa') {
            $lottiConGiacenza = $lottiConGiacenza->filter(fn($l) => $l->giacenza_calcolata > 0 && $l->giacenza_calcolata <= 10);
        }

        $lotti->setCollection($lottiConGiacenza);

        return view('livewire.tables.lotti-materiale-table', [
            'lotti' => $lotti,
            'prodotti' => Prodotto::active()->orderBy('nome')->get(),
            'categorie' => Categoria::cases(),
        ]);
    }
}
