<?php

namespace App\Livewire\Tables;

use App\Enums\StatoPreventivo;
use App\Models\Preventivo;
use App\Services\LottoDuplicatorService;
use App\Services\PreventivoToOrdineService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PreventiviTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $stato = '';
    public string $sortField = 'data';
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
        }
        else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'stato']);
        $this->resetPage();
    }

    public function cambiaStato(Preventivo $preventivo, string $nuovoStato): void
    {
        $this->authorize('changeStatus', $preventivo);

        $newState = StatoPreventivo::from($nuovoStato);

        if (!$preventivo->stato->canTransitionTo($newState)) {
            $this->addError('stato', "Transizione non valida da {$preventivo->stato->label()} a {$newState->label()}");
            return;
        }

        if ($newState === StatoPreventivo::ACCETTATO) {
            try {
                DB::transaction(function () use ($preventivo, $nuovoStato): void {
                    $preventivo->update(['stato' => $nuovoStato]);

                    $ordineEsistente = $preventivo->ordine()->exists();
                    if ($ordineEsistente) {
                        session()->flash('success', 'Preventivo accettato. Ordine già esistente.');
                        return;
                    }

                    $ordine = app(PreventivoToOrdineService::class)->convert($preventivo->fresh());
                    session()->flash('success', "Preventivo accettato e convertito automaticamente in ordine {$ordine->numero}.");
                });
            } catch (\Throwable $e) {
                $this->addError('stato', $e->getMessage());
            }

            return;
        }

        $preventivo->update(['stato' => $nuovoStato]);
        session()->flash('success', "Stato preventivo aggiornato a \"{$newState->label()}\".");
    }

    public function duplica(Preventivo $preventivo): void
    {
        $this->authorize('create', Preventivo::class); // Authorize creation of new preventivo
        $this->authorize('view', $preventivo); // Authorize viewing of source preventivo

        $preventivo->loadMissing([
            'righe.lottoProduzione.materialiUsati',
            'righe.lottoProduzione.componentiManuali',
            'lottoProduzione.materialiUsati',
            'lottoProduzione.componentiManuali',
        ]);

        $nuovo = $preventivo->replicate();
        $nuovo->numero = null;
        $nuovo->anno = null;
        $nuovo->progressivo = null;
        $nuovo->stato = StatoPreventivo::BOZZA;
        $nuovo->data = now();
        $nuovo->validita_fino = now()->addDays(30);
        $nuovo->created_by = auth()->id();
        $nuovo->save();

        $lottoMap = [];
        $duplicator = app(LottoDuplicatorService::class);

        // Duplica le righe
        foreach ($preventivo->righe as $riga) {
            $nuovaRiga = $riga->replicate();
            $nuovaRiga->preventivo_id = $nuovo->id;

            if ($riga->lottoProduzione) {
                $sourceLotto = $riga->lottoProduzione;
                $newLotto = $lottoMap[$sourceLotto->id] ?? null;

                if (! $newLotto) {
                    $newLotto = $duplicator->duplicate($sourceLotto, [
                        'preventivo_id' => $nuovo->id,
                        'ordine_id' => null,
                        'ordine_riga_id' => null,
                        'cliente_id' => $nuovo->cliente_id,
                        'created_by' => auth()->id(),
                    ]);

                    $lottoMap[$sourceLotto->id] = $newLotto;
                }

                $nuovaRiga->lotto_produzione_id = $newLotto->id;
            } else {
                $nuovaRiga->lotto_produzione_id = null;
            }

            $nuovaRiga->save();
        }

        if ($preventivo->lottoProduzione && ! isset($lottoMap[$preventivo->lottoProduzione->id])) {
            $duplicator->duplicate($preventivo->lottoProduzione, [
                'preventivo_id' => $nuovo->id,
                'ordine_id' => null,
                'ordine_riga_id' => null,
                'cliente_id' => $nuovo->cliente_id,
                'created_by' => auth()->id(),
            ]);
        }

        session()->flash('success', "Preventivo duplicato con numero {$nuovo->numero}.");
    }

    public function delete(Preventivo $preventivo): void
    {
        $this->authorize('delete', $preventivo);

        $statiTerminali = [
            \App\Enums\StatoLottoProduzione::COMPLETATO->value,
            \App\Enums\StatoLottoProduzione::ANNULLATO->value,
        ];

        // Check via righe (lotto_produzione_id on rows)
        $lottiAttivi = $preventivo->righe()
            ->whereNotNull('lotto_produzione_id')
            ->whereHas('lottoProduzione', fn($q) => $q->whereNotIn('stato', $statiTerminali))
            ->count();

        // Check via direct relationship
        if ($lottiAttivi === 0 && $preventivo->lottoProduzione
        && !in_array($preventivo->lottoProduzione->stato->value, $statiTerminali)) {
            $lottiAttivi = 1;
        }

        if ($lottiAttivi > 0) {
            session()->flash('error', 'Impossibile eliminare: lotti di produzione ancora attivi collegati a questo preventivo.');
            return;
        }

        $numero = $preventivo->numero;
        $preventivo->delete();
        session()->flash('success', "Preventivo \"{$numero}\" eliminato.");
    }

    public function convertiInOrdine(int $preventivoId): void
    {
        $preventivo = Preventivo::findOrFail($preventivoId);
        $this->authorize('changeStatus', $preventivo);

        try {
            $service = app(PreventivoToOrdineService::class);
            $ordine = $service->convert($preventivo);

            session()->flash('success', "Ordine {$ordine->numero} creato dal preventivo {$preventivo->numero}");
            $this->redirect(route('ordini.show', $ordine));
        }
        catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $query = Preventivo::query()
            ->with(['cliente', 'createdBy'])
            ->withExists('ordine');

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->stato) {
            $query->where('stato', $this->stato);
        }

        $preventivi = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.tables.preventivi-table', [
            'preventivi' => $preventivi,
            'stati' => StatoPreventivo::cases(),
        ]);
    }
}
