<?php

namespace App\Livewire\Forms;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Prodotto;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ProdottoForm extends Component
{
    public ?Prodotto $prodotto = null;

    public string $codice = '';

    #[Validate('required|string|max:255')]
    public string $nome = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $descrizione = null;

    #[Validate('required|string')]
    public string $unita_misura = 'pz';

    #[Validate('required|string')]
    public string $categoria = 'altro';

    #[Validate('boolean')]
    public bool $soggetto_fitok = false;

    #[Validate('nullable|numeric|min:0|max:9999999.9999')]
    public ?string $prezzo_unitario = null;

    #[Validate('nullable|numeric|min:0|max:9999999.9999')]
    public ?string $costo_unitario = null;

    #[Validate('nullable|numeric|min:0|max:9999999.99')]
    public ?string $prezzo_mc = null;

    #[Validate('nullable|numeric|min:0|max:1')]
    public ?string $coefficiente_scarto = null;

    #[Validate('nullable|numeric|min:0')]
    public ?string $lunghezza_mm = null;

    #[Validate('nullable|numeric|min:0')]
    public ?string $larghezza_mm = null;

    #[Validate('nullable|numeric|min:0')]
    public ?string $spessore_mm = null;

    #[Validate('nullable|numeric|min:0|max:99999.999')]
    public ?string $peso_specifico_kg_mc = null;

    #[Validate('boolean')]
    public bool $is_active = true;

    #[Validate('boolean')]
    public bool $usa_dimensioni = true;

    public function mount(?Prodotto $prodotto = null): void
    {
        if ($prodotto?->exists) {
            $this->prodotto = $prodotto;
            $this->codice = $prodotto->codice;
            $this->nome = $prodotto->nome;
            $this->descrizione = $prodotto->descrizione;
            $this->unita_misura = $prodotto->unita_misura->value;
            $this->categoria = $prodotto->categoria->value;
            $this->soggetto_fitok = $prodotto->soggetto_fitok;
            $this->prezzo_unitario = $prodotto->prezzo_unitario ? (string) $prodotto->prezzo_unitario : null;
            $this->costo_unitario = $prodotto->costo_unitario ? (string) $prodotto->costo_unitario : null;
            $this->prezzo_mc = $prodotto->prezzo_mc ? (string) $prodotto->prezzo_mc : null;
            $this->coefficiente_scarto = $prodotto->coefficiente_scarto ? (string) $prodotto->coefficiente_scarto : null;
            $this->lunghezza_mm = $prodotto->lunghezza_mm ? (string) $prodotto->lunghezza_mm : null;
            $this->larghezza_mm = $prodotto->larghezza_mm ? (string) $prodotto->larghezza_mm : null;
            $this->spessore_mm = $prodotto->spessore_mm ? (string) $prodotto->spessore_mm : null;
            $this->peso_specifico_kg_mc = $prodotto->peso_specifico_kg_mc
                ? (string) $prodotto->peso_specifico_kg_mc
                : null;
            $this->is_active = $prodotto->is_active;
            $this->usa_dimensioni = $prodotto->usa_dimensioni;
        }
    }

    public function rules(): array
    {
        $rules = [
            'codice' => 'required|string|max:50|unique:prodotti,codice',
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string|max:1000',
            'unita_misura' => 'required|string|in:'.implode(',', array_column(UnitaMisura::cases(), 'value')),
            'categoria' => 'required|string|in:'.implode(',', array_column(Categoria::cases(), 'value')),
            'soggetto_fitok' => 'boolean',
            'prezzo_unitario' => 'nullable|numeric|min:0|max:9999999.9999',
            'costo_unitario' => 'nullable|numeric|min:0|max:9999999.9999',
            'prezzo_mc' => 'nullable|numeric|min:0|max:9999999.99',
            'coefficiente_scarto' => 'nullable|numeric|min:0|max:1',
            'lunghezza_mm' => 'nullable|numeric|min:0',
            'larghezza_mm' => 'nullable|numeric|min:0',
            'spessore_mm' => 'nullable|numeric|min:0',
            'peso_specifico_kg_mc' => 'nullable|numeric|min:0|max:99999.999',
            'is_active' => 'boolean',
            'usa_dimensioni' => 'boolean',
        ];

        if ($this->prodotto?->exists) {
            $rules['codice'] = 'required|string|max:50|unique:prodotti,codice,'.$this->prodotto->id;
        }

        return $rules;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'codice' => $validated['codice'],
            'nome' => $validated['nome'],
            'descrizione' => $validated['descrizione'],
            'unita_misura' => $validated['unita_misura'],
            'categoria' => $validated['categoria'],
            'soggetto_fitok' => $validated['soggetto_fitok'],
            'prezzo_unitario' => $validated['prezzo_unitario'] ?: null,
            'costo_unitario' => $validated['costo_unitario'] ?: null,
            'prezzo_mc' => $validated['prezzo_mc'] ?: null,
            'coefficiente_scarto' => $validated['coefficiente_scarto'] ?: 0.10,
            'peso_specifico_kg_mc' => $validated['peso_specifico_kg_mc'] ?: null,
            'is_active' => $validated['is_active'],
            'usa_dimensioni' => $validated['usa_dimensioni'],
        ];

        if ($validated['usa_dimensioni']) {
            $data['lunghezza_mm'] = $validated['lunghezza_mm'] ?: null;
            $data['larghezza_mm'] = $validated['larghezza_mm'] ?: null;
            $data['spessore_mm'] = $validated['spessore_mm'] ?: null;
        } else {
            $data['lunghezza_mm'] = null;
            $data['larghezza_mm'] = null;
            $data['spessore_mm'] = null;
        }

        if ($this->prodotto?->exists) {
            $this->prodotto->update($data);
            session()->flash('success', "Prodotto \"{$this->nome}\" aggiornato con successo.");
        } else {
            Prodotto::create($data);
            session()->flash('success', "Prodotto \"{$this->nome}\" creato con successo.");
        }

        $this->redirect(route('prodotti.index'));
    }

    public function render()
    {
        $uom = UnitaMisura::tryFrom($this->unita_misura);
        $isMc = $uom === UnitaMisura::MC;

        $prezzoUnitario = $this->prezzo_unitario !== null && $this->prezzo_unitario !== ''
            ? max(0, (float) $this->prezzo_unitario)
            : null;
        $prezzoMc = $this->prezzo_mc !== null && $this->prezzo_mc !== ''
            ? max(0, (float) $this->prezzo_mc)
            : null;

        $prezzoEffettivo = 0.0;
        $prezzoEffettivoFonte = 'Default (0)';

        if ($isMc) {
            if ($prezzoMc !== null) {
                $prezzoEffettivo = $prezzoMc;
                $prezzoEffettivoFonte = 'Prezzo dedicato m³';
            } elseif ($prezzoUnitario !== null) {
                $prezzoEffettivo = $prezzoUnitario;
                $prezzoEffettivoFonte = 'Prezzo listino (fallback)';
            }
        } elseif ($prezzoUnitario !== null) {
            $prezzoEffettivo = $prezzoUnitario;
            $prezzoEffettivoFonte = 'Prezzo listino';
        }

        return view('livewire.forms.prodotto-form', [
            'unitaMisura' => UnitaMisura::cases(),
            'categorie' => Categoria::cases(),
            'isEditing' => $this->prodotto?->exists,
            'showPrezzoMcInput' => (bool) config('production.show_prezzo_mc_input', true),
            'prezzoEffettivo' => $prezzoEffettivo,
            'prezzoEffettivoFonte' => $prezzoEffettivoFonte,
        ]);
    }
}
