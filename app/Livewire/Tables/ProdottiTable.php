<?php

namespace App\Livewire\Tables;

use App\Enums\Categoria;
use App\Models\Prodotto;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ProdottiTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoria = '';
    public string $stato = '';
    public string $sortField = 'nome';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'categoria' => ['except' => ''],
        'stato' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoria(): void
    {
        $this->resetPage();
    }

    public function updatingStato(): void
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
        $this->reset(['search', 'categoria', 'stato']);
        $this->resetPage();
    }

    public function toggleActive(Prodotto $prodotto): void
    {
        $this->authorize('update', $prodotto);
        $prodotto->update(['is_active' => !$prodotto->is_active]);
    }

    public function delete(Prodotto $prodotto): void
    {
        $this->authorize('delete', $prodotto);
        $prodotto->delete();
        session()->flash('success', "Prodotto \"{$prodotto->nome}\" eliminato.");
    }

    public function duplica(Prodotto $prodotto)
    {
        $this->authorize('create', Prodotto::class);
        $this->authorize('view', $prodotto);

        $nuovo = $prodotto->replicate();
        $nuovo->codice = $this->generateDuplicateCode((string) $prodotto->codice);
        $nuovo->nome = Str::limit("{$prodotto->nome} (Copia)", 255, '');
        $nuovo->save();

        session()->flash('success', "Prodotto duplicato: {$nuovo->codice}");

        return $this->redirect(route('prodotti.show', $nuovo->id));
    }

    private function generateDuplicateCode(string $sourceCode): string
    {
        $base = Str::upper(Str::limit($sourceCode, 40, '')) . '-COPY';
        $candidate = $base;
        $suffix = 2;

        while (Prodotto::withTrashed()->where('codice', $candidate)->exists()) {
            $candidate = Str::limit($base, 44, '') . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    public function render()
    {
        $query = Prodotto::query();

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->categoria) {
            $query->where('categoria', $this->categoria);
        }

        if ($this->stato === 'attivi') {
            $query->where('is_active', true);
        } elseif ($this->stato === 'inattivi') {
            $query->where('is_active', false);
        }

        $prodotti = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.tables.prodotti-table', [
            'prodotti' => $prodotti,
            'categorie' => Categoria::cases(),
        ]);
    }
}
