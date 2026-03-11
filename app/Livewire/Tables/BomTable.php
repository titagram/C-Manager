<?php

namespace App\Livewire\Tables;

use App\Models\Bom;
use Livewire\Component;
use Livewire\WithPagination;

class BomTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
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
        $this->reset(['search']);
        $this->resetPage();
    }

    public function delete(int $bomId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $bom = Bom::findOrFail($bomId);
        $bom->delete();
        session()->flash('success', "BOM \"{$bom->codice}\" eliminata.");
    }

    public function render()
    {
        $query = Bom::query()
            ->generated()
            ->with(['ordine', 'createdBy'])
            ->withCount('righe');

        if ($this->search) {
            $query->search($this->search);
        }

        $boms = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.tables.bom-table', [
            'boms' => $boms,
        ]);
    }
}
