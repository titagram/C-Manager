<?php

namespace App\Livewire\Forms;

use App\Models\Fornitore;
use Livewire\Component;

class FornitoreForm extends Component
{
    public ?Fornitore $fornitore = null;

    public string $codice = '';
    public string $ragione_sociale = '';
    public string $partita_iva = '';
    public string $codice_fiscale = '';
    public string $indirizzo = '';
    public string $cap = '';
    public string $citta = '';
    public string $provincia = '';
    public string $nazione = 'IT';
    public string $telefono = '';
    public string $email = '';
    public string $note = '';
    public bool $is_active = true;

    public function mount(?Fornitore $fornitore = null): void
    {
        if ($fornitore?->exists) {
            $this->fornitore = $fornitore;
            $this->codice = $fornitore->codice;
            $this->ragione_sociale = $fornitore->ragione_sociale;
            $this->partita_iva = $fornitore->partita_iva ?? '';
            $this->codice_fiscale = $fornitore->codice_fiscale ?? '';
            $this->indirizzo = $fornitore->indirizzo ?? '';
            $this->cap = $fornitore->cap ?? '';
            $this->citta = $fornitore->citta ?? '';
            $this->provincia = $fornitore->provincia ?? '';
            $this->nazione = $fornitore->nazione ?? 'IT';
            $this->telefono = $fornitore->telefono ?? '';
            $this->email = $fornitore->email ?? '';
            $this->note = $fornitore->note ?? '';
            $this->is_active = $fornitore->is_active;
        }
    }

    public function rules(): array
    {
        $codiceRule = 'required|string|max:20|unique:fornitori,codice';
        $pivaRule = 'nullable|string|max:20|unique:fornitori,partita_iva';
        
        if ($this->fornitore?->exists) {
            $codiceRule .= ',' . $this->fornitore->id;
            $pivaRule .= ',' . $this->fornitore->id;
        }

        return [
            'codice' => $codiceRule,
            'ragione_sociale' => 'required|string|max:255',
            'partita_iva' => $pivaRule,
            'codice_fiscale' => 'nullable|string|max:20',
            'indirizzo' => 'nullable|string|max:255',
            'cap' => 'nullable|string|max:10',
            'citta' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:5',
            'nazione' => 'required|string|size:2',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'note' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'codice' => strtoupper($validated['codice']),
            'ragione_sociale' => $validated['ragione_sociale'],
            'partita_iva' => $validated['partita_iva'] ?: null,
            'codice_fiscale' => $validated['codice_fiscale'] ?: null,
            'indirizzo' => $validated['indirizzo'] ?: null,
            'cap' => $validated['cap'] ?: null,
            'citta' => $validated['citta'] ?: null,
            'provincia' => $validated['provincia'] ? strtoupper($validated['provincia']) : null,
            'nazione' => strtoupper($validated['nazione']),
            'telefono' => $validated['telefono'] ?: null,
            'email' => $validated['email'] ?: null,
            'note' => $validated['note'] ?: null,
            'is_active' => $validated['is_active'],
        ];

        if ($this->fornitore?->exists) {
            $this->fornitore->update($data);
            session()->flash('success', "Fornitore \"{$this->ragione_sociale}\" aggiornato con successo.");
        } else {
            Fornitore::create($data);
            session()->flash('success', "Fornitore \"{$this->ragione_sociale}\" creato con successo.");
        }

        $this->redirect(route('fornitori.index'));
    }

    public function render()
    {
        return view('livewire.forms.fornitore-form', [
            'isEditing' => $this->fornitore?->exists,
        ]);
    }
}
