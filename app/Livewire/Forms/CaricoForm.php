<?php

namespace App\Livewire\Forms;

use App\Enums\UnitaMisura;
use App\Models\LottoMateriale;
use App\Models\Prodotto;
use App\Services\InventoryService;
use Livewire\Component;

class CaricoForm extends Component
{
    // Dati Lotto
    public string $codice_lotto = '';
    public string $prodotto_id = '';
    public ?string $data_arrivo = null;
    public string $fornitore_id = ''; // ID Fornitore selezionato
    public string $numero_ddt = '';
    public string $quantita = '';
    public ?string $causale = null;

    // Dati FITOK (opzionali)
    public ?string $fitok_certificato = null;
    public ?string $fitok_data_trattamento = null;
    public ?string $fitok_tipo_trattamento = null;
    public ?string $fitok_paese_origine = null;

    public string $note = '';

    // Stato UI
    public bool $showFitok = false;
    public ?int $generatedCodeSequence = null;

    protected $listeners = ['prodottoSelected' => 'onProdottoSelected'];

    public function mount(): void
    {
        $this->data_arrivo = now()->format('Y-m-d');
        
        if (request()->has('fornitore_id')) {
            $this->fornitore_id = request()->get('fornitore_id');
        }

        $this->generateCodiceLotto();
    }

    public function generateCodiceLotto(): void
    {
        $year = now()->format('y');
        $month = now()->format('m');
        $prefix = "L{$year}{$month}-";

        if ($this->generatedCodeSequence === null) {
            $maxSequence = LottoMateriale::query()
                ->where('codice_lotto', 'like', $prefix . '%')
                ->pluck('codice_lotto')
                ->map(function (string $codice) use ($prefix): int {
                    $suffix = (string) str($codice)->after($prefix);
                    return ctype_digit($suffix) ? (int) $suffix : 0;
                })
                ->max() ?? 0;

            $this->generatedCodeSequence = $maxSequence;
        }

        do {
            $this->generatedCodeSequence++;
            $candidate = sprintf('%s%04d', $prefix, $this->generatedCodeSequence);
        } while (LottoMateriale::query()->where('codice_lotto', $candidate)->exists());

        $this->codice_lotto = $candidate;
    }

    public function updatedProdottoId($value): void
    {
        if ($value) {
            $prodotto = Prodotto::find($value);
            $this->showFitok = $prodotto?->soggetto_fitok ?? false;
            
            // Se diventato visibile e abbiamo un fornitore, prova a precompilare la nazione
            if ($this->showFitok && $this->fornitore_id && empty($this->fitok_paese_origine)) {
                $this->prefillPaeseOrigine();
            }
        } else {
            $this->showFitok = false;
        }
    }

    public function updatedFornitoreId($value): void
    {
        if ($this->showFitok && $value) {
            $this->prefillPaeseOrigine();
        }
    }

    public function updatedShowFitok($value): void
    {
        if ($value) {
            $this->prefillPaeseOrigine();
        }
    }

    protected function prefillPaeseOrigine(): void
    {
        if (empty($this->fornitore_id)) {
            return;
        }

        $fornitore = \App\Models\Fornitore::find($this->fornitore_id);
        if (!$fornitore || empty($fornitore->nazione)) {
            return;
        }

        $this->fitok_paese_origine = self::nazioneToLabel($fornitore->nazione);
    }

    /**
     * Convert an ISO 3166-1 alpha-2 country code to its Italian name.
     */
    public static function nazioneToLabel(?string $codice): ?string
    {
        if (!$codice) {
            return null;
        }

        if (class_exists(\Locale::class)) {
            $nome = \Locale::getDisplayRegion('-' . strtoupper($codice), 'it');
            if ($nome && $nome !== '-' . strtoupper($codice) && $nome !== strtoupper($codice)) {
                return $nome;
            }
        }

        return strtoupper($codice);
    }

    public function rules(): array
    {
        $rules = [
            'codice_lotto' => 'required|string|max:50|unique:lotti_materiale,codice_lotto',
            'prodotto_id' => 'required|exists:prodotti,id',
            'data_arrivo' => 'required|date',
            'fornitore_id' => 'required|exists:fornitori,id',
            'numero_ddt' => 'nullable|string|max:100',
            'quantita' => 'required|numeric|min:0.0001',
            'causale' => 'nullable|string|max:500',
            'note' => 'nullable|string|max:1000',
        ];

        if ($this->showFitok) {
            $rules['fitok_certificato'] = 'nullable|string|max:100';
            $rules['fitok_data_trattamento'] = 'nullable|date';
            $rules['fitok_tipo_trattamento'] = 'nullable|string|max:100';
            $rules['fitok_paese_origine'] = 'nullable|string|max:100';
        }

        return $rules;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $prodotto = Prodotto::findOrFail($validated['prodotto_id']);
        
        // Recupera nome fornitore per campo legacy
        $fornitore = \App\Models\Fornitore::find($validated['fornitore_id']);
        $nomeFornitore = $fornitore ? $fornitore->ragione_sociale : null;

        $inventoryService = app(InventoryService::class);

        // Crea il lotto
        $quantita = (float) $validated['quantita'];
        $pesoTotaleKg = null;
        $unitaMisura = $prodotto->unita_misura?->value;

        if ($unitaMisura === UnitaMisura::KG->value) {
            $pesoTotaleKg = $quantita;
        } elseif ($unitaMisura === UnitaMisura::MC->value) {
            $pesoSpecifico = (float) ($prodotto->peso_specifico_kg_mc ?? 360);
            $pesoTotaleKg = round($quantita * $pesoSpecifico, 3);
        }

        $lotto = LottoMateriale::create([
            'codice_lotto' => $validated['codice_lotto'],
            'prodotto_id' => $validated['prodotto_id'],
            'fornitore_id' => $validated['fornitore_id'],
            'fornitore' => $nomeFornitore, // Retrocompatibilità
            'data_arrivo' => $validated['data_arrivo'],
            'numero_ddt' => $validated['numero_ddt'] ?: null,
            'quantita_iniziale' => $quantita,
            'peso_totale_kg' => $pesoTotaleKg,
            'lunghezza_mm' => $prodotto->lunghezza_mm,
            'larghezza_mm' => $prodotto->larghezza_mm,
            'spessore_mm' => $prodotto->spessore_mm,
            'fitok_certificato' => $this->fitok_certificato ?: null,
            'fitok_data_trattamento' => $this->fitok_data_trattamento ?: null,
            'fitok_tipo_trattamento' => $this->fitok_tipo_trattamento ?: null,
            'fitok_paese_origine' => $this->fitok_paese_origine ?: null,
            'note' => $validated['note'] ?: null,
        ]);

        // Registra il movimento di carico
        $inventoryService->carico(
            lotto: $lotto,
            quantita: $quantita,
            documento: null,
            user: auth()->user(),
            causale: $validated['causale'] ?: 'Carico iniziale'
        );

        $um = $prodotto->unita_misura?->abbreviation() ?? 'unita';
        session()->flash('success', "Lotto \"{$lotto->codice_lotto}\" creato con carico di {$validated['quantita']} {$um}.");

        $this->redirect(route('magazzino.index'));
    }

    public function render()
    {
        $selectedProdotto = null;
        if ($this->prodotto_id !== '') {
            $selectedProdotto = Prodotto::query()->find((int) $this->prodotto_id);
        }

        $quantitaLabel = 'Quantita *';
        if ($selectedProdotto?->unita_misura) {
            $quantitaLabel = "Quantita ({$selectedProdotto->unita_misura->abbreviation()}) *";
        }

        return view('livewire.forms.carico-form', [
            'prodotti' => Prodotto::active()->orderBy('nome')->get(),
            'fornitori' => \App\Models\Fornitore::active()->orderBy('ragione_sociale')->get(),
            'quantitaLabel' => $quantitaLabel,
        ]);
    }
}
