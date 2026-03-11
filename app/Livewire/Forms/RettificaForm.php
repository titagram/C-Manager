<?php

namespace App\Livewire\Forms;

use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use App\Services\InventoryService;
use Livewire\Attributes\On;
use Livewire\Component;

class RettificaForm extends Component
{
    public bool $showModal = false;
    public ?LottoMateriale $lotto = null;
    public float $giacenzaAttuale = 0;

    public string $tipo = 'positiva';
    public string $quantita = '';
    public string $causale = '';
    public string $causale_codice = '';

    protected $listeners = ['open-rettifica' => 'openModal'];

    #[On('open-rettifica')]
    public function openModal(int $lottoId): void
    {
        $this->lotto = LottoMateriale::with('prodotto')->find($lottoId);

        if ($this->lotto) {
            $inventoryService = app(InventoryService::class);
            $this->giacenzaAttuale = $inventoryService->calcolaGiacenza($this->lotto);
            $this->showModal = true;
            $this->reset(['tipo', 'quantita', 'causale', 'causale_codice']);
            $this->tipo = 'positiva';
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->lotto = null;
        $this->reset(['tipo', 'quantita', 'causale', 'causale_codice']);
    }

    public function rules(): array
    {
        $rules = [
            'tipo' => 'required|in:positiva,negativa',
            'quantita' => 'required|numeric|min:0.0001',
            'causale' => 'required|string|min:5|max:500',
            'causale_codice' => 'nullable|string|max:50',
        ];

        if ($this->tipo === 'negativa') {
            $rules['quantita'] = [
                'required',
                'numeric',
                'min:0.0001',
                function ($attribute, $value, $fail) {
                    if ((float) $value > $this->giacenzaAttuale) {
                        $fail("La rettifica negativa ({$value}) supera la giacenza disponibile ({$this->giacenzaAttuale}).");
                    }
                },
            ];
            $rules['causale_codice'] = 'required|in:' . implode(',', array_keys(MovimentoMagazzino::negativeAdjustmentReasonCodeOptions()));
        }

        return $rules;
    }

    public function updatedTipo(): void
    {
        if ($this->tipo !== 'negativa') {
            $this->causale_codice = '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $inventoryService = app(InventoryService::class);

        try {
            $inventoryService->rettifica(
                lotto: $this->lotto,
                quantita: (float) $validated['quantita'],
                positiva: $validated['tipo'] === 'positiva',
                user: auth()->user(),
                causale: $validated['causale'],
                causaleCodice: $validated['causale_codice'] ?? null,
            );

            $tipoLabel = $validated['tipo'] === 'positiva' ? '+' : '-';
            $um = $this->lotto->prodotto->unita_misura->abbreviation();

            session()->flash('success', "Rettifica {$tipoLabel}{$validated['quantita']} {$um} applicata al lotto \"{$this->lotto->codice_lotto}\".");

            $this->closeModal();
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function getGiacenzaDopoRettificaProperty(): float
    {
        if (!$this->lotto || !$this->quantita) {
            return $this->giacenzaAttuale;
        }

        $delta = (float) $this->quantita;
        return $this->tipo === 'positiva'
            ? $this->giacenzaAttuale + $delta
            : $this->giacenzaAttuale - $delta;
    }

    public function render()
    {
        return view('livewire.forms.rettifica-form', [
            'causaliCodiceRettificaNegativa' => MovimentoMagazzino::negativeAdjustmentReasonCodeOptions(),
        ]);
    }
}
