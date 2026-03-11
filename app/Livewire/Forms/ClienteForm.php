<?php

namespace App\Livewire\Forms;

use App\Models\Cliente;
use Livewire\Component;

class ClienteForm extends Component
{
    public ?Cliente $cliente = null;

    public string $ragione_sociale = '';
    public string $partita_iva = '';
    public string $codice_fiscale = '';
    public string $indirizzo = '';
    public string $cap = '';
    public string $citta = '';
    public string $provincia = '';
    public string $telefono = '';
    public string $email = '';
    public string $note = '';
    public bool $is_active = true;

    public function mount(?Cliente $cliente = null): void
    {
        if ($cliente?->exists) {
            $this->cliente = $cliente;
            $this->ragione_sociale = $cliente->ragione_sociale;
            $this->partita_iva = $cliente->partita_iva ?? '';
            $this->codice_fiscale = $cliente->codice_fiscale ?? '';
            $this->indirizzo = $cliente->indirizzo ?? '';
            $this->cap = $cliente->cap ?? '';
            $this->citta = $cliente->citta ?? '';
            $this->provincia = $cliente->provincia ?? '';
            $this->telefono = $cliente->telefono ?? '';
            $this->email = $cliente->email ?? '';
            $this->note = $cliente->note ?? '';
            $this->is_active = $cliente->is_active;
        }
    }

    public function rules(): array
    {
        $pivaRule = 'nullable|string|size:11|unique:clienti,partita_iva';
        if ($this->cliente?->exists) {
            $pivaRule .= ',' . $this->cliente->id;
        }

        return [
            'ragione_sociale' => 'required|string|max:255',
            'partita_iva' => $pivaRule,
            'codice_fiscale' => 'nullable|string|max:16',
            'indirizzo' => 'nullable|string|max:255',
            'cap' => 'nullable|string|max:10',
            'citta' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:2',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'note' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'ragione_sociale' => $validated['ragione_sociale'],
            'partita_iva' => $validated['partita_iva'] ?: null,
            'codice_fiscale' => $validated['codice_fiscale'] ?: null,
            'indirizzo' => $validated['indirizzo'] ?: null,
            'cap' => $validated['cap'] ?: null,
            'citta' => $validated['citta'] ?: null,
            'provincia' => $validated['provincia'] ? strtoupper($validated['provincia']) : null,
            'telefono' => $validated['telefono'] ?: null,
            'email' => $validated['email'] ?: null,
            'note' => $validated['note'] ?: null,
            'is_active' => $validated['is_active'],
        ];

        if ($this->cliente?->exists) {
            $this->cliente->update($data);
            session()->flash('success', "Cliente \"{$this->ragione_sociale}\" aggiornato con successo.");
        } else {
            Cliente::create($data);
            session()->flash('success', "Cliente \"{$this->ragione_sociale}\" creato con successo.");
        }

        $this->redirect(route('clienti.index'));
    }

    public function render()
    {
        return view('livewire.forms.cliente-form', [
            'isEditing' => $this->cliente?->exists,
        ]);
    }
}
