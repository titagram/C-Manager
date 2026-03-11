<?php

namespace App\Livewire\Tables;

use App\Enums\TipoCostruzione;
use App\Models\Costruzione;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class CostruzioniTable extends Component
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

    public function toggleActive(Costruzione $costruzione): void
    {
        $this->authorize('update', $costruzione);
        $costruzione->update(['is_active' => !$costruzione->is_active]);
    }

    public function delete(Costruzione $costruzione): void
    {
        $this->authorize('delete', $costruzione);
        $costruzione->delete();
        session()->flash('success', "Costruzione \"{$costruzione->nome}\" eliminata.");
    }

    public function duplica(int $costruzioneId): void
    {
        $costruzione = Costruzione::with('componenti')->findOrFail($costruzioneId);
        $this->authorize('create', Costruzione::class);
        $this->authorize('view', $costruzione);

        $nuovoNome = $this->nextNomeDisponibile($costruzione->nome . ' (copia)');

        DB::transaction(function () use ($costruzione, $nuovoNome) {
            $nuova = Costruzione::create([
                'categoria' => $costruzione->categoria,
                'nome' => $nuovoNome,
                'slug' => Str::slug($nuovoNome),
                'descrizione' => $costruzione->descrizione,
                'config' => $costruzione->config ?? [],
                'richiede_lunghezza' => $costruzione->richiede_lunghezza,
                'richiede_larghezza' => $costruzione->richiede_larghezza,
                'richiede_altezza' => $costruzione->richiede_altezza,
                'is_active' => $costruzione->is_active,
            ]);

            foreach ($costruzione->componenti as $componente) {
                $nuova->componenti()->create([
                    'nome' => $componente->nome,
                    'calcolato' => $componente->calcolato,
                    'tipo_dimensionamento' => $componente->tipo_dimensionamento,
                    'formula_lunghezza' => $componente->formula_lunghezza,
                    'formula_larghezza' => $componente->formula_larghezza,
                    'formula_quantita' => $componente->formula_quantita,
                    'is_internal' => (bool) ($componente->is_internal ?? false),
                    'allow_rotation' => (bool) ($componente->allow_rotation ?? false),
                ]);
            }
        });

        session()->flash('success', "Costruzione \"{$costruzione->nome}\" duplicata.");
    }

    private function nextNomeDisponibile(string $baseName): string
    {
        $candidate = $baseName;
        $suffix = 2;

        while (Costruzione::where('nome', $candidate)->exists()) {
            $candidate = sprintf('%s %d', $baseName, $suffix);
            $suffix++;
        }

        return $candidate;
    }

    public function render()
    {
        $query = Costruzione::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nome', 'like', '%' . $this->search . '%')
                  ->orWhere('descrizione', 'like', '%' . $this->search . '%')
                  ->orWhere('categoria', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoria) {
            $query->where('categoria', $this->categoria);
        }

        if ($this->stato === 'attivi') {
            $query->where('is_active', true);
        } elseif ($this->stato === 'inattivi') {
            $query->where('is_active', false);
        }

        $costruzioni = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.tables.costruzioni-table', [
            'costruzioni' => $costruzioni,
            'tipiCostruzione' => TipoCostruzione::cases(),
        ]);
    }
}
