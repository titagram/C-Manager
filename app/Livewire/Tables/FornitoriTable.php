<?php

namespace App\Livewire\Tables;

use App\Models\Fornitore;
use Livewire\Component;
use Livewire\WithPagination;

class FornitoriTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $stato = '';
    public string $nazione = '';
    public string $sortField = 'codice';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'stato' => ['except' => ''],
        'nazione' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStato(): void
    {
        $this->resetPage();
    }

    public function updatingNazione(): void
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
        $this->reset(['search', 'stato', 'nazione']);
        $this->resetPage();
    }

    public function toggleActive(Fornitore $fornitore): void
    {
        $fornitore->update(['is_active' => !$fornitore->is_active]);
    }

    public function delete(Fornitore $fornitore): void
    {
        $fornitore->delete();
        session()->flash('success', "Fornitore \"{$fornitore->ragione_sociale}\" eliminato.");
    }

    public function render()
    {
        $query = Fornitore::query()
            ->withCount('lottiMateriale');

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->stato === 'attivi') {
            $query->where('is_active', true);
        } elseif ($this->stato === 'inattivi') {
            $query->where('is_active', false);
        }

        if ($this->nazione) {
            $query->where('nazione', $this->nazione);
        }

        $fornitori = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        // Nazioni disponibili per filtro
        $nazioniDisponibili = Fornitore::select('nazione')
            ->distinct()
            ->orderBy('nazione')
            ->pluck('nazione');

        return view('livewire.tables.fornitori-table', [
            'fornitori' => $fornitori,
            'nazioniDisponibili' => $nazioniDisponibili,
        ]);
    }
}
