<?php

namespace App\Livewire\Forms;

use App\Enums\StatoOrdine;
use App\Models\Cliente;
use App\Models\Ordine;
use App\Models\OrdineRiga;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OrdineForm extends Component
{
    // Use ID instead of model instance to avoid Livewire serialization/type issues.
    public ?int $ordineId = null;

    // Header fields
    public ?int $cliente_id = null;
    public ?int $preventivo_id = null;
    public string $descrizione = '';
    public ?string $data_consegna_prevista = null;
    public string $note = '';

    // Dynamic rows
    public array $righe = [];

    // Calculated totals
    public float $totale = 0;

    public function mount($ordine = null): void
    {
        if ($ordine instanceof Ordine) {
            $ordineModel = $ordine;
            $this->ordineId = $ordine->id;
        } elseif (is_numeric($ordine)) {
            $ordineModel = Ordine::find($ordine);
            $this->ordineId = $ordineModel?->id;
        } else {
            $ordineModel = null;
        }

        if ($ordineModel?->exists) {
            $this->cliente_id = $ordineModel->cliente_id;
            $this->preventivo_id = $ordineModel->preventivo_id;
            $this->descrizione = $ordineModel->descrizione ?? '';
            $this->data_consegna_prevista = $ordineModel->data_consegna_prevista?->format('Y-m-d');
            $this->note = $ordineModel->note ?? '';

            // Load existing rows
            foreach ($ordineModel->righe as $riga) {
                $this->righe[] = [
                    'id' => $riga->id,
                    'prodotto_id' => $riga->prodotto_id,
                    'descrizione' => $riga->descrizione ?? '',
                    'tipo_costruzione' => $riga->tipo_costruzione ?? '',
                    'larghezza_mm' => $riga->larghezza_mm,
                    'profondita_mm' => $riga->profondita_mm,
                    'altezza_mm' => $riga->altezza_mm,
                    'quantita' => $riga->quantita,
                    'volume_mc' => $riga->volume_mc_finale ?? 0,
                    'prezzo_mc' => $riga->prezzo_mc ?? 0,
                    'totale_riga' => $riga->totale_riga ?? 0,
                ];
            }

            $this->totale = (float) $ordineModel->totale;
        } else {
            $this->data_consegna_prevista = now()->addDays(14)->format('Y-m-d');
            $this->aggiungiRiga();
        }
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|exists:clienti,id',
            'descrizione' => 'nullable|string|max:1000',
            'data_consegna_prevista' => 'nullable|date',
            'note' => 'nullable|string|max:5000',
            'righe' => 'required|array|min:1',
            'righe.*.descrizione' => 'required|string|max:255',
            'righe.*.larghezza_mm' => 'required|numeric|min:1',
            'righe.*.profondita_mm' => 'required|numeric|min:1',
            'righe.*.altezza_mm' => 'required|numeric|min:1',
            'righe.*.quantita' => 'required|integer|min:1',
            'righe.*.prezzo_mc' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Seleziona un cliente.',
            'righe.required' => 'Aggiungi almeno una riga.',
            'righe.min' => 'Aggiungi almeno una riga.',
            'righe.*.descrizione.required' => 'La descrizione e obbligatoria.',
            'righe.*.larghezza_mm.required' => 'Larghezza obbligatoria.',
            'righe.*.profondita_mm.required' => 'Profondita obbligatoria.',
            'righe.*.altezza_mm.required' => 'Altezza obbligatoria.',
            'righe.*.quantita.required' => 'Quantita obbligatoria.',
            'righe.*.prezzo_mc.required' => 'Prezzo obbligatorio.',
        ];
    }

    public function aggiungiRiga(): void
    {
        $this->righe[] = [
            'id' => null,
            'prodotto_id' => null,
            'descrizione' => '',
            'tipo_costruzione' => '',
            'larghezza_mm' => '',
            'profondita_mm' => '',
            'altezza_mm' => '',
            'quantita' => 1,
            'volume_mc' => 0,
            'prezzo_mc' => '',
            'totale_riga' => 0,
        ];
    }

    public function rimuoviRiga(int $index): void
    {
        if (count($this->righe) > 1) {
            unset($this->righe[$index]);
            $this->righe = array_values($this->righe);
            $this->ricalcola();
        }
    }

    public function updatedRighe(): void
    {
        $this->ricalcola();
    }

    public function ricalcola(): void
    {
        $totaleOrdine = 0;

        foreach ($this->righe as $index => $riga) {
            $l = ((float) ($riga['larghezza_mm'] ?? 0)) / 1000;
            $p = ((float) ($riga['profondita_mm'] ?? 0)) / 1000;
            $h = ((float) ($riga['altezza_mm'] ?? 0)) / 1000;
            $q = (int) ($riga['quantita'] ?? 1);
            $prezzo = (float) ($riga['prezzo_mc'] ?? 0);

            $volumeUnit = round($l * $p * $h, 6);
            $volumeTot = round($volumeUnit * $q, 6);
            $rigaTot = round($volumeTot * $prezzo, 2);

            $this->righe[$index]['volume_mc'] = $volumeTot;
            $this->righe[$index]['totale_riga'] = $rigaTot;

            $totaleOrdine += $rigaTot;
        }

        $this->totale = round($totaleOrdine, 2);
    }

    public function save(): void
    {
        $this->validate();

        $this->ricalcola();

        DB::transaction(function () {
            $data = [
                'cliente_id' => $this->cliente_id,
                'preventivo_id' => $this->preventivo_id ?: null,
                'descrizione' => $this->descrizione ?: null,
                'data_consegna_prevista' => $this->data_consegna_prevista ?: null,
                'note' => $this->note ?: null,
                'totale' => $this->totale,
            ];

            if ($this->ordineId) {
                $ordine = Ordine::findOrFail($this->ordineId);
                $ordine->update($data);

                // Delete removed rows
                $existingIds = collect($this->righe)->pluck('id')->filter()->toArray();
                OrdineRiga::where('ordine_id', $ordine->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();

                session()->flash('success', "Ordine \"{$ordine->numero}\" aggiornato con successo.");
            } else {
                $data['data_ordine'] = now();
                $data['stato'] = StatoOrdine::CONFERMATO;
                $data['created_by'] = auth()->id();

                $ordine = Ordine::create($data);
                session()->flash('success', "Ordine \"{$ordine->numero}\" creato con successo.");
            }

            // Save/update rows
            foreach ($this->righe as $ordineNum => $rigaData) {
                $rigaId = $rigaData['id'] ?? null;

                // Calculate volume values
                $l = ((float) ($rigaData['larghezza_mm'] ?? 0)) / 1000;
                $p = ((float) ($rigaData['profondita_mm'] ?? 0)) / 1000;
                $h = ((float) ($rigaData['altezza_mm'] ?? 0)) / 1000;
                $q = (int) ($rigaData['quantita'] ?? 1);

                $volumeUnit = round($l * $p * $h, 6);
                $volumeTot = round($volumeUnit * $q, 6);

                $rigaPayload = [
                    'ordine_id' => $ordine->id,
                    'prodotto_id' => ($rigaData['prodotto_id'] ?? null) ?: null,
                    'descrizione' => $rigaData['descrizione'] ?? '',
                    'tipo_costruzione' => $rigaData['tipo_costruzione'] ?? null,
                    'larghezza_mm' => $rigaData['larghezza_mm'] ?? 0,
                    'profondita_mm' => $rigaData['profondita_mm'] ?? 0,
                    'altezza_mm' => $rigaData['altezza_mm'] ?? 0,
                    'quantita' => $rigaData['quantita'] ?? 1,
                    'volume_mc_calcolato' => $volumeUnit,
                    'volume_mc_finale' => $volumeTot,
                    'prezzo_mc' => $rigaData['prezzo_mc'] ?? 0,
                    'totale_riga' => $rigaData['totale_riga'] ?? 0,
                    'ordine' => $ordineNum,
                ];

                if ($rigaId) {
                    OrdineRiga::find($rigaId)->update($rigaPayload);
                } else {
                    OrdineRiga::create($rigaPayload);
                }
            }
        });

        $this->redirect(route('ordini.index'));
    }

    public function render()
    {
        return view('livewire.forms.ordine-form', [
            'clienti' => Cliente::where('is_active', true)->orderBy('ragione_sociale')->get(),
            'isEditing' => (bool) $this->ordineId,
        ]);
    }
}
