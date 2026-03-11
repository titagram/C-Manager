<?php

namespace App\Livewire\Tables;

use App\Models\Cliente;
use Livewire\Component;
use Livewire\WithPagination;

class ClientiTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $stato = '';
    public string $sortField = 'ragione_sociale';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'stato' => ['except' => ''],
    ];

    public function updatingSearch(): void
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
        $this->reset(['search', 'stato']);
        $this->resetPage();
    }

    public function toggleActive(Cliente $cliente): void
    {
        $this->authorize('update', $cliente);
        $cliente->update(['is_active' => !$cliente->is_active]);
    }

    public function delete(Cliente $cliente): void
    {
        $this->authorize('delete', $cliente);
        $cliente->delete();
        session()->flash('success', "Cliente \"{$cliente->ragione_sociale}\" eliminato.");
    }

    public function render()
    {
        $query = Cliente::query()
            ->withCount(['preventivi', 'lottiProduzione']);

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->stato === 'attivi') {
            $query->where('is_active', true);
        } elseif ($this->stato === 'inattivi') {
            $query->where('is_active', false);
        }

        $clienti = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.tables.clienti-table', [
            'clienti' => $clienti,
        ]);
    }
}
