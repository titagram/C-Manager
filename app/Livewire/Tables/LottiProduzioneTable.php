<?php

namespace App\Livewire\Tables;

use App\Enums\StatoLottoProduzione;
use App\Models\Cliente;
use App\Models\LottoProduzione;
use App\Models\User;
use App\Services\LottoProductionReadinessService;
use App\Services\ProductionLotService;
use Livewire\Component;
use Livewire\WithPagination;

class LottiProduzioneTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $stato = '';
    public string $cliente = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public bool $trashed = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'stato' => ['except' => ''],
        'cliente' => ['except' => ''],
        'trashed' => ['except' => false],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStato(): void
    {
        $this->resetPage();
    }

    public function updatingCliente(): void
    {
        $this->resetPage();
    }

    public function updatingTrashed(): void
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
        $this->reset(['search', 'stato', 'cliente', 'trashed']);
        $this->resetPage();
    }

    public function avvia(LottoProduzione $lotto): void
    {
        $this->authorize('start', $lotto);
        $this->resetErrorBag('action');

        try {
            app(ProductionLotService::class)->avviaLavorazione($lotto);
            $this->notify("Lotto \"{$lotto->codice_lotto}\" avviato.");
        }
        catch (\Throwable $e) {
            $this->addError('action', $e->getMessage());
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function completa(LottoProduzione $lotto): void
    {
        $this->authorize('complete', $lotto);
        $this->resetErrorBag('action');

        $user = auth()->user();
        if (! $user instanceof User) {
            $this->addError('action', 'Utente non autenticato.');
            $this->notify('Utente non autenticato.', 'error');
            return;
        }

        try {
            $lottoCompletato = app(ProductionLotService::class)->confermaLotto($lotto, $user);
            $message = "Lotto \"{$lotto->codice_lotto}\" completato.";

            if ($lottoCompletato->ordine && $lottoCompletato->ordine->stato->value === 'pronto') {
                $message .= " Ordine \"{$lottoCompletato->ordine->numero}\" aggiornato a Pronto.";
            }

            $this->notify($message);
        }
        catch (\Throwable $e) {
            $this->addError('action', $e->getMessage());
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function annulla(LottoProduzione $lotto): void
    {
        $this->authorize('cancel', $lotto);
        $this->resetErrorBag('action');

        try {
            app(ProductionLotService::class)->annullaLotto($lotto);
            $this->notify("Lotto \"{$lotto->codice_lotto}\" annullato.");
        }
        catch (\Throwable $e) {
            $this->addError('action', $e->getMessage());
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function delete(LottoProduzione $lotto): void
    {
        $this->authorize('delete', $lotto);
        $codice = $lotto->codice_lotto;
        $lotto->delete();
        $this->notify("Lotto \"{$codice}\" eliminato.");
    }

    public function restore(int $lottoId): void
    {
        $lotto = LottoProduzione::withTrashed()->findOrFail($lottoId);
        $this->authorize('restore', $lotto);
        $lotto->restore();
        $this->notify("Lotto \"{$lotto->codice_lotto}\" ripristinato.");
    }

    public function forceDelete(int $lottoId): void
    {
        $lotto = LottoProduzione::withTrashed()->findOrFail($lottoId);
        $this->authorize('forceDelete', $lotto);
        $codice = $lotto->codice_lotto;
        $lotto->forceDelete();
        $this->notify("Lotto \"{$codice}\" eliminato definitivamente.");
    }

    private function notify(string $message, string $type = 'success'): void
    {
        session()->flash($type === 'error' ? 'error' : 'success', $message);

        $payload = json_encode([
            'message' => $message,
            'type' => $type,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload !== false) {
            $this->js("window.dispatchEvent(new CustomEvent('toast', { detail: {$payload} }));");
        }
    }

    public function render()
    {
        $query = LottoProduzione::query()
            ->with([
            'cliente',
            'preventivo.cliente',
            'createdBy',
            'costruzione.componenti',
            'componentiManuali',
            'materialiUsati',
        ])
            ->withCount('movimenti');

        if ($this->trashed) {
            $query->onlyTrashed();
        }

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->stato) {
            $query->where('stato', $this->stato);
        }

        if ($this->cliente) {
            $query->where('cliente_id', $this->cliente);
        }

        $lotti = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $readinessService = app(LottoProductionReadinessService::class);
        $readinessByLotto = [];
        foreach ($lotti as $lotto) {
            $readinessByLotto[$lotto->id] = $readinessService->evaluate($lotto);
        }

        return view('livewire.tables.lotti-produzione-table', [
            'lotti' => $lotti,
            'stati' => StatoLottoProduzione::cases(),
            'clienti' => Cliente::active()->orderBy('ragione_sociale')->get(),
            'readinessByLotto' => $readinessByLotto,
        ]);
    }
}
