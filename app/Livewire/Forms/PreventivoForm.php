<?php

namespace App\Livewire\Forms;

use App\Enums\StatoLottoProduzione;
use App\Enums\StatoPreventivo;
use App\Enums\TipoRigaPreventivo;
use App\Enums\UnitaMisura;
use App\Models\Cliente;
use App\Models\LottoProduzione;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\Prodotto;
use App\Services\Calcolo\DTO\RigaInput;
use App\Services\LottoDuplicatorService;
use App\Services\Calcolo\PreventivoCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PreventivoForm extends Component
{
    // Use ID instead of model instance to avoid Livewire serialization issues
    public ?int $preventivoId = null;

    // Header
    public ?int $cliente_id = null;

    public string $descrizione = '';

    public ?string $validita_fino = null;

    // Righe dinamiche
    public array $righe = [];

    // Totali calcolati
    public float $totale_materiali = 0;

    public float $totale_lotti = 0;

    public float $totale_lavorazioni = 0;

    public float $totale = 0;

    public bool $showLavorazioniExtra = false;

    // Lock editing when preventivo is not editable (e.g. accepted)
    public bool $isReadOnly = false;

    public ?string $preventivoStatoLabel = null;

    // Modal properties
    public bool $showRigaModal = false;

    public ?int $lottoToDuplicateId = null;

    protected PreventivoCalculator $calculator;

    public function boot(PreventivoCalculator $calculator): void
    {
        $this->calculator = $calculator;
    }

    public function mount($preventivo = null): void
    {
        // Handle both ID (string/int) and model instance
        if ($preventivo instanceof Preventivo) {
            $preventivoModel = $preventivo;
            $this->preventivoId = $preventivo->id;
        } elseif (is_numeric($preventivo)) {
            $preventivoModel = Preventivo::find($preventivo);
            $this->preventivoId = (int) $preventivo;
        } else {
            $preventivoModel = null;
        }

        if ($preventivoModel?->exists) {
            $this->isReadOnly = ! $preventivoModel->canBeEdited();
            $this->preventivoStatoLabel = $preventivoModel->stato?->label();
            $this->cliente_id = $preventivoModel->cliente_id;
            $this->descrizione = $preventivoModel->descrizione ?? '';
            $this->validita_fino = $preventivoModel->validita_fino?->format('Y-m-d');

            // Carica le righe esistenti
            foreach ($preventivoModel->righe as $riga) {
                $this->righe[] = [
                    'id' => $riga->id,
                    'lotto_produzione_id' => $riga->lotto_produzione_id,
                    'tipo_riga' => $riga->tipo_riga?->value ?? ($riga->lotto_produzione_id ? TipoRigaPreventivo::LOTTO->value : TipoRigaPreventivo::SFUSO->value),
                    'include_in_bom' => $riga->include_in_bom ?? true,
                    'prodotto_id' => $riga->prodotto_id,
                    'unita_misura' => $riga->unita_misura
                        ?? $riga->prodotto?->unita_misura?->value
                        ?? UnitaMisura::MC->value,
                    'descrizione' => $riga->descrizione ?? '',
                    'lunghezza_mm' => $riga->lunghezza_mm,
                    'larghezza_mm' => $riga->larghezza_mm,
                    'spessore_mm' => $riga->spessore_mm,
                    'quantita' => $riga->quantita,
                    'coefficiente_scarto' => $riga->coefficiente_scarto,
                    'prezzo_unitario' => $riga->prezzo_unitario,
                    'superficie_mq' => $riga->superficie_mq,
                    'volume_mc' => $riga->volume_mc,
                    'materiale_netto' => $riga->materiale_netto,
                    'materiale_lordo' => $riga->materiale_lordo,
                    'peso_totale_kg' => $riga->lottoProduzione?->peso_totale_kg,
                    'show_weight_in_quote' => $riga->lottoProduzione?->costruzione?->showWeightInQuote() ?? false,
                    'totale_riga' => $riga->totale_riga,
                ];
            }

            $this->totale_lavorazioni = (float) $preventivoModel->totale_lavorazioni;
            $this->showLavorazioniExtra = $this->totale_lavorazioni > 0;
            // Recompute totals from current rows to avoid stale header totals when lotto rows were updated externally.
            $this->ricalcola();
        } else {
            $this->validita_fino = now()->addDays(30)->format('Y-m-d');
        }
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'nullable|exists:clienti,id',
            'descrizione' => 'nullable|string|max:1000',
            'validita_fino' => 'nullable|date',
            'totale_lavorazioni' => 'nullable|numeric|min:0|max:9999999.99',
            'righe' => 'required|array|min:1',
            'righe.*.tipo_riga' => 'nullable|in:lotto,sfuso',
            'righe.*.include_in_bom' => 'boolean',
            'righe.*.prodotto_id' => 'nullable|exists:prodotti,id',
            'righe.*.unita_misura' => 'nullable|string|in:'.implode(',', array_map(
                fn (UnitaMisura $unita) => $unita->value,
                UnitaMisura::cases()
            )),
            'righe.*.descrizione' => 'required|string|max:255',
            'righe.*.lunghezza_mm' => 'nullable|numeric|min:0',
            'righe.*.larghezza_mm' => 'nullable|numeric|min:0',
            'righe.*.spessore_mm' => 'nullable|numeric|min:0',
            'righe.*.quantita' => 'required|integer|min:1',
            'righe.*.coefficiente_scarto' => 'required|numeric|min:0|max:1',
            'righe.*.prezzo_unitario' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'righe.required' => 'Aggiungi almeno una riga.',
            'righe.min' => 'Aggiungi almeno una riga.',
            'righe.*.descrizione.required' => 'La descrizione è obbligatoria.',
            'righe.*.lunghezza_mm.required' => 'Lunghezza obbligatoria.',
            'righe.*.larghezza_mm.required' => 'Larghezza obbligatoria.',
            'righe.*.spessore_mm.required' => 'Spessore obbligatorio.',
            'righe.*.quantita.required' => 'Quantità obbligatoria.',
            'righe.*.prezzo_unitario.required' => 'Prezzo obbligatorio.',
        ];
    }

    public function aggiungiRiga(): void
    {
        $this->ensureEditable();

        // Show the modal
        $this->showRigaModal = true;
        $this->lottoToDuplicateId = null;
    }

    public function creaRigaDaLotto(?int $lottoToDuplicateId = null): void
    {
        $this->ensureEditable();

        $lottoId = null;
        $preventivoId = null;

        DB::transaction(function () use ($lottoToDuplicateId, &$lottoId, &$preventivoId) {
            // If preventivo doesn't exist yet, save it in BOZZA first
            $preventivoId = $this->preventivoId;

            if (! $preventivoId) {
                $preventivo = Preventivo::create([
                    'cliente_id' => $this->cliente_id,
                    'descrizione' => $this->descrizione ?: null,
                    'validita_fino' => $this->validita_fino ?: null,
                    'data' => now(),
                    'stato' => StatoPreventivo::BOZZA,
                    'totale_materiali' => 0,
                    'totale_lavorazioni' => 0,
                    'totale' => 0,
                    'created_by' => auth()->id(),
                    'engine_version' => $this->calculator->getVersion(),
                    'input_snapshot' => [],
                ]);
                $preventivoId = $preventivo->id;
            }

            if ($lottoToDuplicateId) {
                $lotto = app(LottoDuplicatorService::class)->duplicate(
                    LottoProduzione::findOrFail($lottoToDuplicateId),
                    [
                        'preventivo_id' => $preventivoId,
                        'ordine_id' => null,
                        'ordine_riga_id' => null,
                        'cliente_id' => $this->cliente_id ?: null,
                        'created_by' => auth()->id(),
                    ]
                );
            } else {
                $lotto = LottoProduzione::create([
                    'stato' => StatoLottoProduzione::BOZZA,
                    'created_by' => auth()->id(),
                    'preventivo_id' => $preventivoId,
                    'cliente_id' => $this->cliente_id ?: null,
                ]);
            }

            $lottoId = $lotto->id;

            // Create a new PreventivoRiga linked to this lotto
            $riga = PreventivoRiga::create([
                'preventivo_id' => $preventivoId,
                'lotto_produzione_id' => $lotto->id,
                'tipo_riga' => TipoRigaPreventivo::LOTTO,
                'include_in_bom' => true,
                'prodotto_id' => null,
                'unita_misura' => UnitaMisura::MC->value,
                'descrizione' => $lotto->prodotto_finale ?? 'Nuovo lotto',
                'lunghezza_mm' => 0,
                'larghezza_mm' => 0,
                'spessore_mm' => 0,
                'quantita' => 1,
                'superficie_mq' => 0,
                'volume_mc' => 0,
                'materiale_netto' => 0,
                'coefficiente_scarto' => 0.10,
                'materiale_lordo' => 0,
                'prezzo_unitario' => 0,
                'totale_riga' => 0,
                'ordine' => count($this->righe),
            ]);

            // Add the riga to the form state
            $this->righe[] = [
                'id' => $riga->id,
                'lotto_produzione_id' => $lotto->id,
                'tipo_riga' => TipoRigaPreventivo::LOTTO->value,
                'include_in_bom' => true,
                'prodotto_id' => null,
                'unita_misura' => UnitaMisura::MC->value,
                'descrizione' => $riga->descrizione,
                'lunghezza_mm' => 0,
                'larghezza_mm' => 0,
                'spessore_mm' => 0,
                'quantita' => 1,
                'coefficiente_scarto' => 0.10,
                'prezzo_unitario' => 0,
                'superficie_mq' => 0,
                'volume_mc' => 0,
                'materiale_netto' => 0,
                'materiale_lordo' => 0,
                'peso_totale_kg' => $lotto->peso_totale_kg,
                'show_weight_in_quote' => $lotto->costruzione?->showWeightInQuote() ?? false,
                'totale_riga' => 0,
            ];
        });

        // Redirect to lotto edit page with query params
        $queryParams = [
            'from' => 'preventivo',
            'preventivo_id' => $preventivoId,
        ];

        $this->redirect(route('lotti.edit', $lottoId).'?'.http_build_query($queryParams));
    }

    public function creaRigaSfusa(): void
    {
        $this->ensureEditable();

        $this->righe[] = [
            'id' => null,
            'lotto_produzione_id' => null,
            'tipo_riga' => TipoRigaPreventivo::SFUSO->value,
            'include_in_bom' => true,
            'prodotto_id' => null,
            'unita_misura' => UnitaMisura::MC->value,
            'descrizione' => '',
            'lunghezza_mm' => null,
            'larghezza_mm' => null,
            'spessore_mm' => null,
            'quantita' => 1,
            'coefficiente_scarto' => 0.10,
            'prezzo_unitario' => 0,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'materiale_lordo' => 0,
            'peso_totale_kg' => null,
            'show_weight_in_quote' => false,
            'totale_riga' => 0,
        ];

        $this->showRigaModal = false;
    }

    public function rimuoviRiga(int $index): void
    {
        $this->ensureEditable();

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

    public function updatedTotaleLavorazioni(): void
    {
        $this->totale_lavorazioni = round(max(0, (float) $this->totale_lavorazioni), 2);
        $this->ricalcola();
    }

    public function abilitaLavorazioniExtra(): void
    {
        $this->ensureEditable();
        $this->showLavorazioniExtra = true;
    }

    public function rimuoviLavorazioniExtra(): void
    {
        $this->ensureEditable();
        $this->totale_lavorazioni = 0;
        $this->showLavorazioniExtra = false;
        $this->ricalcola();
    }

    public function selezionaProdotto(int $index, ?int $prodottoId): void
    {
        $this->ensureEditable();

        if ($prodottoId) {
            $prodotto = Prodotto::find($prodottoId);
            if ($prodotto) {
                $this->righe[$index]['prodotto_id'] = $prodotto->id;
                $this->righe[$index]['descrizione'] = $prodotto->nome;
                $this->righe[$index]['coefficiente_scarto'] = $prodotto->coefficiente_scarto ?? 0.10;
                $this->righe[$index]['unita_misura'] = $prodotto->unita_misura?->value ?? UnitaMisura::MC->value;
                $this->righe[$index]['prezzo_unitario'] = $prodotto->prezzoListinoPerUnita($this->righe[$index]['unita_misura']);

                // Pre-fill dimensions for sfuso rows when the selected product stores defaults.
                $tipoRiga = $this->righe[$index]['tipo_riga'] ?? TipoRigaPreventivo::SFUSO->value;
                if ($tipoRiga === TipoRigaPreventivo::SFUSO->value) {
                    foreach (['lunghezza_mm', 'larghezza_mm', 'spessore_mm'] as $campoDimensione) {
                        if ($prodotto->{$campoDimensione} !== null) {
                            $this->righe[$index][$campoDimensione] = (float) $prodotto->{$campoDimensione};
                        }
                    }
                }
            }
        }
        $this->ricalcola();
    }

    public function ricalcola(): void
    {
        $totaleLotti = 0;
        $totaleManuali = 0;

        foreach ($this->righe as $index => $riga) {
            // Check if this is a lotto-based riga
            $isLottoRiga = ($riga['tipo_riga'] ?? null) === TipoRigaPreventivo::LOTTO->value
                || ! empty($riga['lotto_produzione_id']);

            if ($isLottoRiga) {
                // For lotto righe, use totale_riga directly (synced from lotto)
                $totaleLotti += (float) ($riga['totale_riga'] ?? 0);

                continue;
            }

            $unitaRiga = $this->resolveUnitaMisuraRiga($riga);
            $this->righe[$index]['unita_misura'] = $unitaRiga;

            if ($this->rigaSfusaHaInputSufficienti($riga, $unitaRiga)) {
                $rigaInput = RigaInput::fromArray([
                    'prodotto_id' => $riga['prodotto_id'] ?? null,
                    'unita_misura' => $unitaRiga,
                    'descrizione' => $riga['descrizione'] ?? '',
                    'lunghezza_mm' => (float) $riga['lunghezza_mm'],
                    'larghezza_mm' => (float) $riga['larghezza_mm'],
                    'spessore_mm' => (float) $riga['spessore_mm'],
                    'quantita' => (int) $riga['quantita'],
                    'coefficiente_scarto' => (float) ($riga['coefficiente_scarto'] ?? 0.10),
                    'prezzo_unitario' => (float) ($riga['prezzo_unitario'] ?? 0),
                ]);

                $rigaOutput = $this->calculator->calcolaRiga($rigaInput);

                $this->righe[$index]['superficie_mq'] = $rigaOutput->superficie_mq;
                $this->righe[$index]['volume_mc'] = $rigaOutput->volume_mc;
                $this->righe[$index]['materiale_netto'] = $rigaOutput->materiale_netto;
                $this->righe[$index]['materiale_lordo'] = $rigaOutput->materiale_lordo;
                $this->righe[$index]['totale_riga'] = $rigaOutput->totale;
                $totaleManuali += (float) $rigaOutput->totale;
            } else {
                $this->righe[$index]['superficie_mq'] = 0;
                $this->righe[$index]['volume_mc'] = 0;
                $this->righe[$index]['materiale_netto'] = 0;
                $this->righe[$index]['materiale_lordo'] = 0;
                $this->righe[$index]['totale_riga'] = 0;
            }
        }

        $this->totale_materiali = round($totaleManuali, 2);
        $this->totale_lotti = round($totaleLotti, 2);
        $this->totale = round($totaleManuali + $totaleLotti + (float) $this->totale_lavorazioni, 2);
    }

    public function save(): void
    {
        $this->ensureEditable();

        $validated = $this->validate();
        $this->validateRigheSfuse();

        $this->ricalcola();

        $data = [
            'cliente_id' => $this->cliente_id,
            'descrizione' => $this->descrizione ?: null,
            'validita_fino' => $this->validita_fino ?: null,
            'totale_materiali' => $this->totale_materiali,
            'totale_lavorazioni' => $this->totale_lavorazioni,
            'totale' => $this->totale,
            'engine_version' => $this->calculator->getVersion(),
            'input_snapshot' => $this->righe,
        ];

        if ($this->preventivoId) {
            $preventivo = Preventivo::findOrFail($this->preventivoId);
            $preventivo->update($data);

            // Elimina righe rimosse
            $existingIds = collect($this->righe)->pluck('id')->filter()->toArray();
            PreventivoRiga::where('preventivo_id', $preventivo->id)
                ->whereNotIn('id', $existingIds)
                ->delete();

            session()->flash('success', "Preventivo \"{$preventivo->numero}\" aggiornato con successo.");
        } else {
            $data['data'] = now();
            $data['stato'] = StatoPreventivo::BOZZA;
            $data['created_by'] = auth()->id();

            $preventivo = Preventivo::create($data);
            session()->flash('success', "Preventivo \"{$preventivo->numero}\" creato con successo.");
        }

        // Salva/aggiorna le righe
        foreach ($this->righe as $ordine => $rigaData) {
            $rigaId = $rigaData['id'] ?? null;

            $rigaPayload = [
                'preventivo_id' => $preventivo->id,
                'lotto_produzione_id' => ($rigaData['lotto_produzione_id'] ?? null) ?: null,
                'tipo_riga' => ($rigaData['tipo_riga'] ?? null) ?: (
                    ! empty($rigaData['lotto_produzione_id'])
                        ? TipoRigaPreventivo::LOTTO->value
                        : TipoRigaPreventivo::SFUSO->value
                ),
                'include_in_bom' => (bool) ($rigaData['include_in_bom'] ?? true),
                'prodotto_id' => ($rigaData['prodotto_id'] ?? null) ?: null,
                'unita_misura' => $this->normalizeUnitaMisura($rigaData['unita_misura'] ?? null),
                'descrizione' => $rigaData['descrizione'] ?? '',
                'lunghezza_mm' => $rigaData['lunghezza_mm'] ?? 0,
                'larghezza_mm' => $rigaData['larghezza_mm'] ?? 0,
                'spessore_mm' => $rigaData['spessore_mm'] ?? 0,
                'quantita' => $rigaData['quantita'] ?? 1,
                'coefficiente_scarto' => $rigaData['coefficiente_scarto'] ?? 0.10,
                'prezzo_unitario' => $rigaData['prezzo_unitario'] ?? 0,
                'superficie_mq' => $rigaData['superficie_mq'] ?? 0,
                'volume_mc' => $rigaData['volume_mc'] ?? 0,
                'materiale_netto' => $rigaData['materiale_netto'] ?? 0,
                'materiale_lordo' => $rigaData['materiale_lordo'] ?? 0,
                'totale_riga' => $rigaData['totale_riga'] ?? 0,
                'ordine' => $ordine,
            ];

            if ($rigaId) {
                PreventivoRiga::find($rigaId)->update($rigaPayload);
            } else {
                PreventivoRiga::create($rigaPayload);
            }
        }

        $this->redirect(route('preventivi.index'));
    }

    private function validateRigheSfuse(): void
    {
        $errors = [];

        foreach ($this->righe as $index => $riga) {
            $tipo = ($riga['tipo_riga'] ?? null) ?: (
                ! empty($riga['lotto_produzione_id'])
                    ? TipoRigaPreventivo::LOTTO->value
                    : TipoRigaPreventivo::SFUSO->value
            );

            if ($tipo !== TipoRigaPreventivo::SFUSO->value) {
                continue;
            }

            $unita = $this->resolveUnitaMisuraRiga($riga);
            $this->righe[$index]['unita_misura'] = $unita;

            if ((int) ($riga['quantita'] ?? 0) <= 0) {
                $errors["righe.{$index}.quantita"] = 'Quantità obbligatoria per materiale sfuso.';
            }

            if (in_array($unita, [UnitaMisura::MC->value, UnitaMisura::ML->value, UnitaMisura::MQ->value], true)
                && (float) ($riga['lunghezza_mm'] ?? 0) <= 0
            ) {
                $errors["righe.{$index}.lunghezza_mm"] = 'Lunghezza obbligatoria per materiale sfuso.';
            }

            if (in_array($unita, [UnitaMisura::MC->value, UnitaMisura::MQ->value], true)
                && (float) ($riga['larghezza_mm'] ?? 0) <= 0
            ) {
                $errors["righe.{$index}.larghezza_mm"] = 'Larghezza obbligatoria per materiale sfuso.';
            }

            if ($unita === UnitaMisura::MC->value && (float) ($riga['spessore_mm'] ?? 0) <= 0) {
                $errors["righe.{$index}.spessore_mm"] = 'Spessore obbligatorio per materiale sfuso.';
            }

            if ((float) ($riga['prezzo_unitario'] ?? 0) < 0) {
                $errors["righe.{$index}.prezzo_unitario"] = 'Prezzo non valido per materiale sfuso.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function resolveUnitaMisuraRiga(array $riga): string
    {
        if (! empty($riga['unita_misura'])) {
            return $this->normalizeUnitaMisura($riga['unita_misura']);
        }

        if (! empty($riga['prodotto_id'])) {
            $prodotto = Prodotto::find((int) $riga['prodotto_id']);
            if ($prodotto?->unita_misura) {
                return $prodotto->unita_misura->value;
            }
        }

        return UnitaMisura::MC->value;
    }

    private function normalizeUnitaMisura(?string $unita): string
    {
        $value = strtolower((string) ($unita ?: UnitaMisura::MC->value));

        return in_array($value, array_map(fn (UnitaMisura $u) => $u->value, UnitaMisura::cases()), true)
            ? $value
            : UnitaMisura::MC->value;
    }

    private function rigaSfusaHaInputSufficienti(array $riga, string $unita): bool
    {
        if ((int) ($riga['quantita'] ?? 0) <= 0) {
            return false;
        }

        $lunghezza = (float) ($riga['lunghezza_mm'] ?? 0);
        $larghezza = (float) ($riga['larghezza_mm'] ?? 0);
        $spessore = (float) ($riga['spessore_mm'] ?? 0);

        return match ($unita) {
            UnitaMisura::PZ->value, UnitaMisura::KG->value => true,
            UnitaMisura::ML->value => $lunghezza > 0,
            UnitaMisura::MQ->value => $lunghezza > 0 && $larghezza > 0,
            default => $lunghezza > 0 && $larghezza > 0 && $spessore > 0,
        };
    }

    #[Livewire\Attributes\On('product-created')]
    public function onProductCreated($id): void
    {
        // Questo metodo serve solo a triggerare il re-render del componente
        // in modo che la lista prodotti nel render() venga aggiornata.
    }

    public function render()
    {
        return view('livewire.forms.preventivo-form', [
            'clienti' => Cliente::where('is_active', true)->orderBy('ragione_sociale')->get(),
            'prodotti' => Prodotto::where('is_active', true)->orderBy('nome')->get(),
            'unitaMisura' => UnitaMisura::cases(),
            'lottiDisponibili' => LottoProduzione::query()
                ->whereNull('deleted_at')
                ->whereNot('stato', StatoLottoProduzione::ANNULLATO->value)
                ->orderBy('created_at', 'desc')
                ->get(),
            'isEditing' => (bool) $this->preventivoId,
        ]);
    }

    private function ensureEditable(): void
    {
        if (! $this->preventivoId) {
            return;
        }

        $preventivo = Preventivo::findOrFail($this->preventivoId);
        if ($preventivo->canBeEdited()) {
            $this->isReadOnly = false;
            $this->preventivoStatoLabel = $preventivo->stato?->label();

            return;
        }

        $this->isReadOnly = true;
        $this->preventivoStatoLabel = $preventivo->stato?->label();

        throw ValidationException::withMessages([
            'preventivo' => sprintf(
                'Questo preventivo è in stato "%s" e non può essere modificato.',
                $this->preventivoStatoLabel
            ),
        ]);
    }
}
