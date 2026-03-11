<?php

namespace App\Livewire\Tables;

use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Models\Ordine;
use App\Models\User;
use App\Services\LottoProductionReadinessService;
use App\Services\OrderProductionService;
use Livewire\Component;
use Livewire\WithPagination;

class OrdiniTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $stato = '';

    public string $sortField = 'data_ordine';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'stato' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStato(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'stato']);
        $this->resetPage();
    }

    public function cambiaStato(int $ordineId, string $nuovoStato): void
    {
        $ordine = Ordine::findOrFail($ordineId);
        $this->authorize('changeStatus', $ordine);

        $newState = StatoOrdine::from($nuovoStato);

        if (! $ordine->stato->canTransitionTo($newState)) {
            $message = "Transizione non valida da {$ordine->stato->label()} a {$newState->label()}";
            $this->addError('stato', $message);
            session()->flash('error', $message);

            return;
        }

        if ($ordine->stato === StatoOrdine::CONFERMATO && $newState === StatoOrdine::IN_PRODUZIONE) {
            $user = auth()->user();
            if (! $user instanceof User) {
                $this->addError('stato', 'Utente non autenticato.');
                session()->flash('error', 'Utente non autenticato.');

                return;
            }

            try {
                $result = app(OrderProductionService::class)->avviaProduzione($ordine, $user);
                $bom = $result['bom'];
                session()->flash(
                    'success',
                    "Produzione avviata: {$result['lotti_avviati']} lotti, {$result['consumi_opzionati']} consumi opzionati, BOM {$bom->codice} generata."
                );
            } catch (\Throwable $e) {
                $this->addError('stato', $e->getMessage());
                session()->flash('error', $e->getMessage());
            }

            return;
        }

        if ($ordine->stato === StatoOrdine::IN_PRODUZIONE && $newState === StatoOrdine::PRONTO) {
            $user = auth()->user();
            if (! $user instanceof User) {
                $this->addError('stato', 'Utente non autenticato.');
                session()->flash('error', 'Utente non autenticato.');

                return;
            }

            try {
                $result = app(OrderProductionService::class)->completaProduzione($ordine, $user);
                session()->flash(
                    'success',
                    "Produzione completata: {$result['lotti_completati']} lotti chiusi. Ordine segnato come Pronto."
                );
            } catch (\Throwable $e) {
                $this->addError('stato', $e->getMessage());
                session()->flash('error', $e->getMessage());
            }

            return;
        }

        $ordine->update(['stato' => $nuovoStato]);
        session()->flash('success', "Stato ordine aggiornato a {$newState->label()}");
    }

    public function delete(int $ordineId): void
    {
        $ordine = Ordine::findOrFail($ordineId);
        $this->authorize('delete', $ordine);

        $lottiAttivi = $ordine->lottiProduzione()
            ->whereNotIn('stato', [
                StatoLottoProduzione::COMPLETATO->value,
                StatoLottoProduzione::ANNULLATO->value,
            ])->count();

        if ($lottiAttivi > 0) {
            session()->flash('error', "Impossibile eliminare: {$lottiAttivi} lott".($lottiAttivi === 1 ? 'o' : 'i').' di produzione ancora attiv'.($lottiAttivi === 1 ? 'o' : 'i').'.');

            return;
        }

        $numero = $ordine->numero;
        $ordine->delete();
        session()->flash('success', "Ordine \"{$numero}\" eliminato.");
    }

    public function render()
    {
        $query = Ordine::with([
            'cliente',
            'createdBy',
            'lottiProduzione.costruzione.componenti',
            'lottiProduzione.componentiManuali',
            'lottiProduzione.materialiUsati',
        ]);

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->stato) {
            $query->where('stato', $this->stato);
        }

        $ordini = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $readinessService = app(LottoProductionReadinessService::class);
        $readinessByOrdine = [];
        foreach ($ordini as $ordine) {
            $readinessByOrdine[$ordine->id] = $readinessService->evaluateOrder($ordine);
        }

        return view('livewire.tables.ordini-table', [
            'ordini' => $ordini,
            'stati' => StatoOrdine::cases(),
            'readinessByOrdine' => $readinessByOrdine,
        ]);
    }
}
