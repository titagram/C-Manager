<?php

namespace App\Livewire\Forms;

use App\Enums\Categoria;
use App\Enums\TipoCostruzione;
use App\Enums\UnitaMisura;
use App\Models\Bom;
use App\Models\BomRiga;
use App\Models\Prodotto;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BomForm extends Component
{
    public ?Bom $bom = null;

    // Header fields
    public string $nome = '';
    public ?int $prodotto_id = null;
    public ?string $categoria_output = null;
    public string $versione = '1.0';
    public bool $is_active = true;
    public string $note = '';

    // Dynamic rows
    public array $righe = [];

    public function mount(?Bom $bom = null): void
    {
        if ($bom?->exists) {
            $this->bom = $bom;
            $this->nome = $bom->nome;
            $this->prodotto_id = $bom->prodotto_id;
            $this->categoria_output = $bom->categoria_output;
            $this->versione = $bom->versione ?? '1.0';
            $this->is_active = $bom->is_active;
            $this->note = $bom->note ?? '';

            // Load existing rows
            foreach ($bom->righe as $riga) {
                $this->righe[] = [
                    'id' => $riga->id,
                    'prodotto_id' => $riga->prodotto_id,
                    'descrizione' => $riga->descrizione ?? '',
                    'quantita' => (float) $riga->quantita,
                    'unita_misura' => $riga->unita_misura?->value ?? 'mc',
                    'coefficiente_scarto' => (float) $riga->coefficiente_scarto,
                    'is_fitok_required' => $riga->is_fitok_required,
                    'note' => $riga->note ?? '',
                ];
            }
        }

        // Ensure at least one row
        if (empty($this->righe)) {
            $this->aggiungiRiga();
        }
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'prodotto_id' => 'nullable|exists:prodotti,id',
            'categoria_output' => 'nullable|string|max:50',
            'versione' => 'required|string|max:10',
            'is_active' => 'boolean',
            'note' => 'nullable|string|max:5000',
            'righe' => 'required|array|min:1',
            'righe.*.prodotto_id' => 'nullable|exists:prodotti,id',
            'righe.*.descrizione' => 'nullable|string|max:255',
            'righe.*.quantita' => 'required|numeric|min:0.0001',
            'righe.*.unita_misura' => 'nullable|string|max:10',
            'righe.*.coefficiente_scarto' => 'required|numeric|min:0|max:1',
            'righe.*.is_fitok_required' => 'boolean',
            'righe.*.note' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Il nome della distinta base e obbligatorio.',
            'versione.required' => 'La versione e obbligatoria.',
            'righe.required' => 'Aggiungi almeno una riga.',
            'righe.min' => 'Aggiungi almeno una riga.',
            'righe.*.quantita.required' => 'La quantita di riferimento e obbligatoria.',
            'righe.*.quantita.min' => 'La quantita di riferimento deve essere maggiore di 0.',
            'righe.*.coefficiente_scarto.required' => 'Il coefficiente di scarto e obbligatorio.',
            'righe.*.coefficiente_scarto.min' => 'Il coefficiente di scarto non puo essere negativo.',
            'righe.*.coefficiente_scarto.max' => 'Il coefficiente di scarto non puo superare 1 (100%).',
        ];
    }

    public function aggiungiRiga(): void
    {
        $this->righe[] = [
            'id' => null,
            'prodotto_id' => null,
            'descrizione' => null,
            'quantita' => 1.0,
            'unita_misura' => 'mc',
            'coefficiente_scarto' => 0.10,
            'is_fitok_required' => false,
            'note' => null,
        ];
    }

    public function rimuoviRiga(int $index): void
    {
        if (count($this->righe) > 1) {
            unset($this->righe[$index]);
            $this->righe = array_values($this->righe);
        }
    }

    public function selezionaProdotto(int $index, ?int $prodottoId): void
    {
        if ($prodottoId) {
            $prodotto = Prodotto::find($prodottoId);
            if ($prodotto) {
                $this->righe[$index]['prodotto_id'] = $prodotto->id;
                $this->righe[$index]['descrizione'] = $prodotto->nome;
                $this->righe[$index]['unita_misura'] = $prodotto->unita_misura?->value ?? 'mc';
                $this->righe[$index]['coefficiente_scarto'] = (float) ($prodotto->coefficiente_scarto ?? 0.10);
                $this->righe[$index]['is_fitok_required'] = $prodotto->soggetto_fitok ?? false;
            }
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        DB::transaction(function () {
            $data = [
                'nome' => $this->nome,
                'prodotto_id' => $this->prodotto_id ?: null,
                'categoria_output' => $this->categoria_output ?: null,
                'versione' => $this->versione,
                'is_active' => $this->is_active,
                'note' => $this->note ?: null,
            ];

            if ($this->bom?->exists) {
                $this->bom->update($data);
                $bom = $this->bom;

                // Delete removed rows
                $existingIds = collect($this->righe)->pluck('id')->filter()->toArray();
                BomRiga::where('bom_id', $bom->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();

                session()->flash('success', "Distinta base \"{$bom->codice}\" aggiornata con successo.");
            } else {
                $data['created_by'] = auth()->id();

                $bom = Bom::create($data);
                session()->flash('success', "Distinta base \"{$bom->codice}\" creata con successo.");
            }

            // Save/update rows
            foreach ($this->righe as $ordine => $rigaData) {
                $rigaId = $rigaData['id'] ?? null;

                $rigaPayload = [
                    'bom_id' => $bom->id,
                    'prodotto_id' => ($rigaData['prodotto_id'] ?? null) ?: null,
                    'descrizione' => $rigaData['descrizione'] ?? null,
                    'quantita' => $rigaData['quantita'] ?? 1.0,
                    'unita_misura' => $rigaData['unita_misura'] ?? 'mc',
                    'coefficiente_scarto' => $rigaData['coefficiente_scarto'] ?? 0.10,
                    'is_fitok_required' => $rigaData['is_fitok_required'] ?? false,
                    'ordine' => $ordine,
                    'note' => $rigaData['note'] ?? null,
                ];

                if ($rigaId) {
                    BomRiga::where('id', $rigaId)->update($rigaPayload);
                } else {
                    BomRiga::create($rigaPayload);
                }
            }
        });

        $this->redirect(route('bom.index'));
    }

    public function render()
    {
        // Get material categories for filtering products
        $materialiCategories = Categoria::materiali();

        return view('livewire.forms.bom-form', [
            'prodotti' => Prodotto::where('is_active', true)->orderBy('nome')->get(),
            'materiePrime' => Prodotto::where('is_active', true)
                ->whereIn('categoria', $materialiCategories)
                ->orderBy('nome')
                ->get(),
            'unitaMisura' => UnitaMisura::cases(),
            'categorieOutput' => TipoCostruzione::cases(),
            'isEditing' => $this->bom?->exists ?? false,
        ]);
    }
}
