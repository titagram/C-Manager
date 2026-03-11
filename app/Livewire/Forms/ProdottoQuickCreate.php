<?php

namespace App\Livewire\Forms;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use App\Models\Prodotto;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ProdottoQuickCreate extends Component
{
    #[Validate('required|string|max:50|unique:prodotti,codice')]
    public string $codice = '';

    #[Validate('required|string|max:255')]
    public string $nome = '';

    #[Validate('required|string')]
    public string $unita_misura = 'mc';

    #[Validate('required|string')]
    public string $categoria = 'materia_prima';

    #[Validate('nullable|numeric|min:0')]
    public ?string $prezzo_unitario = null;

    #[Validate('boolean')]
    public bool $soggetto_fitok = false;

    public function mount()
    {
        // Default values
    }

    public function save()
    {
        $validated = $this->validate();

        $prodotto = Prodotto::create([
            'codice' => $validated['codice'],
            'nome' => $validated['nome'],
            'unita_misura' => $validated['unita_misura'],
            'categoria' => $validated['categoria'],
            'prezzo_unitario' => $validated['prezzo_unitario'] ?: null,
            'soggetto_fitok' => $validated['soggetto_fitok'],
            'is_active' => true,
        ]);

        $this->reset();

        $this->dispatch('product-created', id: $prodotto->id);
        $this->dispatch('close-modal', name: 'quick-product');

        // Also dispatch Alpine event to close the modal
        $this->js('$dispatch("close-product-modal")');
    }

    public function render()
    {
        return view('livewire.forms.prodotto-quick-create', [
            'unitaMisura' => UnitaMisura::cases(),
            'categorie' => Categoria::cases(),
        ]);
    }
}
