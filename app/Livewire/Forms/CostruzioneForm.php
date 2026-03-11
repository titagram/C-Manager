<?php

namespace App\Livewire\Forms;

use App\Enums\TipoCostruzione;
use App\Models\Costruzione;
use App\Services\Production\CassaRoutineCatalog;
use Illuminate\Support\Str;
use Livewire\Component;

class CostruzioneForm extends Component
{
    public ?Costruzione $costruzione = null;

    public string $categoria = 'cassa';

    public string $nome = '';

    public string $descrizione = '';

    public bool $show_weight_in_quote = false;

    public bool $is_active = true;

    public string $cassa_optimizer_key = 'geometrica';

    public function mount(?Costruzione $costruzione = null): void
    {
        if ($costruzione?->exists) {
            $this->costruzione = $costruzione;
            $this->categoria = $costruzione->categoria;
            $this->nome = $costruzione->nome;
            $this->descrizione = $costruzione->descrizione ?? '';
            $this->show_weight_in_quote = (bool) data_get($costruzione->config, 'show_weight_in_quote', false);
            $this->is_active = $costruzione->is_active;
            $this->cassa_optimizer_key = (string) data_get($costruzione->config, 'optimizer_key', 'geometrica');
        }
    }

    public function rules(): array
    {
        return [
            'categoria' => 'required|string|in:'.implode(',', array_column(TipoCostruzione::cases(), 'value')),
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string|max:1000',
            'show_weight_in_quote' => 'boolean',
            'is_active' => 'boolean',
            'cassa_optimizer_key' => 'required|string|in:geometrica,excel_sp25,excel_sp25_fondo40',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $config = $this->costruzione?->config ?? [];
        $config['show_weight_in_quote'] = (bool) ($validated['show_weight_in_quote'] ?? false);
        if (($validated['categoria'] ?? $this->categoria) === 'cassa') {
            $config['optimizer_key'] = (string) ($validated['cassa_optimizer_key'] ?? 'geometrica');
        } else {
            unset($config['optimizer_key']);
        }

        $data = [
            'categoria' => $validated['categoria'],
            'nome' => $validated['nome'],
            'slug' => $this->resolveSlug($validated['nome']),
            'descrizione' => $validated['descrizione'] ?: null,
            'config' => $config,
            'is_active' => $validated['is_active'],
        ];

        if ($this->costruzione?->exists) {
            $this->costruzione->update($data);
            session()->flash('success', "Costruzione \"{$this->nome}\" aggiornata con successo.");
        } else {
            Costruzione::create($data);
            session()->flash('success', "Costruzione \"{$this->nome}\" creata con successo.");
        }

        $this->redirect(route('costruzioni.index'));
    }

    public function render()
    {
        return view('livewire.forms.costruzione-form', [
            'tipiCostruzione' => TipoCostruzione::cases(),
            'cassaModeOptions' => [
                'geometrica' => app(CassaRoutineCatalog::class)->label('geometrica'),
                'excel_sp25' => app(CassaRoutineCatalog::class)->label('cassasp25'),
                'excel_sp25_fondo40' => app(CassaRoutineCatalog::class)->label('cassasp25fondo40'),
            ],
        ]);
    }

    private function resolveSlug(string $nome): string
    {
        $base = Str::slug($nome);
        if ($base === '') {
            $base = 'costruzione';
        }

        $slug = $base;
        $suffix = 1;

        while (
            Costruzione::query()
                ->where('slug', $slug)
                ->when($this->costruzione?->exists, fn ($q) => $q->whereKeyNot($this->costruzione->id))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
