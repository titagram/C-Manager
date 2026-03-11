<?php

namespace App\Livewire\Forms;

use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Services\InventoryService;
use Livewire\Component;

class ScaricoForm extends Component
{
    public string $tipo_movimento = 'scarico';
    public string $lotto_id = '';
    public string $quantita = '';
    public string $lotto_produzione_id = '';
    public string $causale = '';
    public string $causale_codice = '';

    // Dati del lotto selezionato
    public ?LottoMateriale $lottoSelezionato = null;
    public float $giacenzaDisponibile = 0;

    public function mount(): void
    {
        // Precompila lotto se passato via query string
        if (request()->has('lotto')) {
            $this->lotto_id = request()->get('lotto');
            $this->updatedLottoId($this->lotto_id);
        }
    }

    public function updatedLottoId($value): void
    {
        if ($value) {
            $this->lottoSelezionato = LottoMateriale::with('prodotto')->find($value);
            if ($this->lottoSelezionato) {
                $inventoryService = app(InventoryService::class);
                $this->giacenzaDisponibile = $inventoryService->calcolaGiacenza($this->lottoSelezionato);
            }
        } else {
            $this->lottoSelezionato = null;
            $this->giacenzaDisponibile = 0;
        }
    }

    public function rules(): array
    {
        $rules = [
            'tipo_movimento' => 'required|in:scarico,rettifica_negativa',
            'lotto_id' => 'required|exists:lotti_materiale,id',
            'quantita' => [
                'required',
                'numeric',
                'min:0.0001',
                function ($attribute, $value, $fail) {
                    if ((float) $value > $this->giacenzaDisponibile) {
                        $fail("La quantita richiesta ({$value}) supera la giacenza disponibile ({$this->giacenzaDisponibile}).");
                    }
                },
            ],
            'lotto_produzione_id' => 'nullable|exists:lotti_produzione,id',
            'causale' => 'required|string|max:500',
            'causale_codice' => 'nullable|string|max:50',
        ];

        if ($this->tipo_movimento === 'rettifica_negativa') {
            $rules['causale_codice'] = 'required|in:' . implode(',', array_keys(MovimentoMagazzino::negativeAdjustmentReasonCodeOptions()));
        }

        return $rules;
    }

    public function updatedTipoMovimento(): void
    {
        if ($this->tipo_movimento !== 'rettifica_negativa') {
            $this->causale_codice = '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $inventoryService = app(InventoryService::class);
        $lotto = LottoMateriale::findOrFail($validated['lotto_id']);
        $lottoProduzione = $validated['lotto_produzione_id']
            ? LottoProduzione::find($validated['lotto_produzione_id'])
            : null;

        try {
            if ($validated['tipo_movimento'] === 'rettifica_negativa') {
                $inventoryService->rettifica(
                    lotto: $lotto,
                    quantita: (float) $validated['quantita'],
                    positiva: false,
                    user: auth()->user(),
                    causale: $validated['causale'],
                    causaleCodice: $validated['causale_codice'] ?? null
                );
            } else {
                $inventoryService->scarico(
                    lotto: $lotto,
                    quantita: (float) $validated['quantita'],
                    lottoProduzione: $lottoProduzione,
                    documento: null,
                    user: auth()->user(),
                    causale: $validated['causale']
                );
            }

            $um = $lotto->prodotto->unita_misura->abbreviation();
            if ($validated['tipo_movimento'] === 'rettifica_negativa') {
                session()->flash(
                    'success',
                    "Rettifica negativa -{$validated['quantita']} {$um} registrata sul lotto \"{$lotto->codice_lotto}\"."
                );
            } else {
                session()->flash('success', "Scaricato {$validated['quantita']} {$um} dal lotto \"{$lotto->codice_lotto}\".");
            }

            $this->redirect(route('magazzino.index'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function setFullQuantity(): void
    {
        $this->quantita = (string) $this->giacenzaDisponibile;
    }

    public function render()
    {
        $inventoryService = app(InventoryService::class);

        // Lotti con giacenza positiva
        $lotti = LottoMateriale::with('prodotto')
            ->get()
            ->map(function ($lotto) use ($inventoryService) {
                $lotto->giacenza_calcolata = $inventoryService->calcolaGiacenza($lotto);
                return $lotto;
            })
            ->filter(fn($l) => $l->giacenza_calcolata > 0)
            ->sortBy('prodotto.nome');

        $lottiProduzione = LottoProduzione::where('stato', '!=', 'completato')
            ->where('stato', '!=', 'annullato')
            ->orderBy('created_at', 'desc')
            ->get();

        $quantitaLabelBase = $this->tipo_movimento === 'rettifica_negativa'
            ? 'Quantita da rettificare (-)'
            : 'Quantita da scaricare';
        $quantitaLabel = $quantitaLabelBase . ' *';

        if ($this->lottoSelezionato?->prodotto?->unita_misura) {
            $quantitaLabel = sprintf(
                '%s (%s) *',
                $quantitaLabelBase,
                $this->lottoSelezionato->prodotto->unita_misura->abbreviation()
            );
        }

        return view('livewire.forms.scarico-form', [
            'lotti' => $lotti,
            'lottiProduzione' => $lottiProduzione,
            'quantitaLabel' => $quantitaLabel,
            'causaliCodiceRettificaNegativa' => MovimentoMagazzino::negativeAdjustmentReasonCodeOptions(),
        ]);
    }
}
