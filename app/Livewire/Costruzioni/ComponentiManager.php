<?php

namespace App\Livewire\Costruzioni;

use App\Models\Costruzione;
use App\Models\ComponenteCostruzione;
use Livewire\Component;

class ComponentiManager extends Component
{
    private const OPTIMIZER_MANAGED_CATEGORIES = ['cassa', 'gabbia', 'bancale', 'legaccio'];

    public Costruzione $costruzione;

    public $nome = '';
    public $tipo_dimensionamento = 'CALCOLATO';
    public $formula_lunghezza = '';
    public $formula_larghezza = '';
    public $formula_quantita = '1';
    public bool $is_internal = false;
    public bool $allow_rotation = false;

    public ?ComponenteCostruzione $editingComponent = null;
    public bool $isEditing = false;
    public bool $showModal = false;

    protected function rules()
    {
        $rules = [
            'nome' => 'required|string|max:255',
            'tipo_dimensionamento' => 'required|in:CALCOLATO,MANUALE',
            'formula_lunghezza' => 'nullable|string|max:255',
            'formula_larghezza' => 'nullable|string|max:255',
            'formula_quantita' => 'required|string|max:255',
            'is_internal' => 'boolean',
            'allow_rotation' => 'boolean',
        ];

        // Category-specific authoring guard:
        // Current migrated construction optimizers require rectangular components
        // for CALCOLATO rows (length + width formulas).
        if ($this->isOptimizerManagedCategory() && $this->tipo_dimensionamento === 'CALCOLATO') {
            $rules['formula_lunghezza'] = 'required|string|max:255';
            $rules['formula_larghezza'] = 'required|string|max:255';
        }

        return $rules;
    }

    public function mount(Costruzione $costruzione)
    {
        $this->costruzione = $costruzione;
    }

    public function openModal()
    {
        $this->reset([
            'nome',
            'tipo_dimensionamento',
            'formula_lunghezza',
            'formula_larghezza',
            'formula_quantita',
            'is_internal',
            'allow_rotation',
            'editingComponent',
            'isEditing',
        ]);
        $this->formula_quantita = '1';
        $this->tipo_dimensionamento = 'CALCOLATO';
        $this->is_internal = false;
        $this->allow_rotation = false;
        $this->showModal = true;
    }

    public function edit(ComponenteCostruzione $componente)
    {
        $this->editingComponent = $componente;
        $this->nome = $componente->nome;
        $this->tipo_dimensionamento = $componente->tipo_dimensionamento
            ?: ($componente->calcolato ? 'CALCOLATO' : 'MANUALE');
        $this->formula_lunghezza = $componente->formula_lunghezza;
        $this->formula_larghezza = $componente->formula_larghezza;
        $this->formula_quantita = $componente->formula_quantita;
        $this->is_internal = (bool) ($componente->is_internal ?? false);
        $this->allow_rotation = (bool) ($componente->allow_rotation ?? false);
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $calculatedNameError = $this->validateCalculatedComponentNameByCategory();
        if ($calculatedNameError !== null) {
            $this->addError('nome', $calculatedNameError);

            return;
        }

        if ($this->requiresManualByCategorySemantics()) {
            $this->addError(
                'tipo_dimensionamento',
                'Per costruzioni gabbia i componenti di rinforzo devono essere MANUALE.'
            );

            return;
        }

        if ($this->isEditing
            && $this->requiresAtLeastOneCalculatedComponent()
            && $this->editingComponent !== null
            && $this->isCalculatedComponent($this->editingComponent)
            && $this->tipo_dimensionamento === 'MANUALE'
            && $this->countCalculatedComponentsExcluding($this->editingComponent->id) === 0
        ) {
            $this->addError(
                'tipo_dimensionamento',
                'Per questa categoria deve rimanere almeno un componente CALCOLATO.'
            );

            return;
        }

        if ($this->isEditing) {
            $this->editingComponent->update([
                'nome' => $this->nome,
                'calcolato' => $this->tipo_dimensionamento === 'CALCOLATO',
                'tipo_dimensionamento' => $this->tipo_dimensionamento,
                'formula_lunghezza' => $this->formula_lunghezza,
                'formula_larghezza' => $this->formula_larghezza,
                'formula_quantita' => $this->formula_quantita,
                'is_internal' => $this->is_internal,
                'allow_rotation' => $this->allow_rotation,
            ]);
            session()->flash('success', 'Componente aggiornato con successo.');
        } else {
            $this->costruzione->componenti()->create([
                'nome' => $this->nome,
                'calcolato' => $this->tipo_dimensionamento === 'CALCOLATO',
                'tipo_dimensionamento' => $this->tipo_dimensionamento,
                'formula_lunghezza' => $this->formula_lunghezza,
                'formula_larghezza' => $this->formula_larghezza,
                'formula_quantita' => $this->formula_quantita,
                'is_internal' => $this->is_internal,
                'allow_rotation' => $this->allow_rotation,
            ]);
            session()->flash('success', 'Componente aggiunto con successo.');
        }

        $this->showModal = false;
        $this->reset([
            'nome',
            'tipo_dimensionamento',
            'formula_lunghezza',
            'formula_larghezza',
            'formula_quantita',
            'is_internal',
            'allow_rotation',
            'editingComponent',
            'isEditing',
        ]);
        $this->is_internal = false;
        $this->allow_rotation = false;
    }

    public function delete(ComponenteCostruzione $componente)
    {
        if ($this->requiresAtLeastOneCalculatedComponent()
            && $this->isCalculatedComponent($componente)
            && $this->countCalculatedComponentsExcluding($componente->id) === 0
        ) {
            session()->flash('error', 'Per questa categoria deve rimanere almeno un componente CALCOLATO.');

            return;
        }

        $componente->delete();
        session()->flash('success', 'Componente eliminato con successo.');
    }

    public function render()
    {
        return view('livewire.costruzioni.componenti-manager', [
            'componenti' => $this->costruzione->componenti()->get(),
            'isOptimizerManagedCategory' => $this->isOptimizerManagedCategory(),
            'calculatedNameExamples' => $this->calculatedNameExamplesForCurrentCategory(),
            'componentAuthoringGuardEnabled' => $this->isComponentAuthoringGuardEnabled(),
            'currentCategory' => $this->normalizedCategoria(),
        ]);
    }

    private function requiresAtLeastOneCalculatedComponent(): bool
    {
        return $this->isOptimizerManagedCategory();
    }

    private function isCalculatedComponent(ComponenteCostruzione $componente): bool
    {
        if (!empty($componente->tipo_dimensionamento)) {
            return strtoupper((string) $componente->tipo_dimensionamento) === 'CALCOLATO';
        }

        return (bool) $componente->calcolato;
    }

    private function countCalculatedComponentsExcluding(?int $excludeId = null): int
    {
        $query = $this->costruzione
            ->componenti()
            ->when($excludeId !== null, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) {
                $q->where('tipo_dimensionamento', 'CALCOLATO')
                    ->orWhere('calcolato', true);
            });

        return (int) $query->count();
    }

    private function requiresManualByCategorySemantics(): bool
    {
        if ($this->normalizedCategoria() !== 'gabbia') {
            return false;
        }

        if ($this->tipo_dimensionamento === 'MANUALE') {
            return false;
        }

        return $this->isRinforzoComponentName($this->nome);
    }

    private function isRinforzoComponentName(string $name): bool
    {
        $normalized = strtolower(trim($name));

        return $normalized !== '' && str_contains($normalized, 'rinforz');
    }

    private function validateCalculatedComponentNameByCategory(): ?string
    {
        if (!$this->isComponentAuthoringGuardEnabled()) {
            return null;
        }

        if ($this->tipo_dimensionamento !== 'CALCOLATO') {
            return null;
        }

        if (!$this->isOptimizerManagedCategory()) {
            return null;
        }

        $normalizedName = strtolower(trim((string) $this->nome));
        if ($normalizedName === '') {
            return null;
        }

        // Preserve gabbia semantic rule messaging on `tipo_dimensionamento`:
        // rinforzi must be MANUALE (handled in requiresManualByCategorySemantics()).
        if ($this->normalizedCategoria() === 'gabbia' && $this->isRinforzoComponentName($this->nome)) {
            return null;
        }

        if ($this->containsAnyKeyword($normalizedName, $this->manualOnlyKeywords())) {
            return 'Per questa categoria i componenti di ferramenta/non ottimizzabili devono essere impostati come MANUALE (es. chiodi, viti, regge).';
        }

        $allowedKeywords = $this->calculatedNameKeywordsForCurrentCategory();
        if ($allowedKeywords === []) {
            return null;
        }

        if ($this->containsAnyKeyword($normalizedName, $allowedKeywords)) {
            return null;
        }

        $examples = implode(', ', $this->calculatedNameExamplesForCurrentCategory());
        $categoria = $this->normalizedCategoria();

        return sprintf(
            'Nome non compatibile con lo schema CALCOLATO per categoria %s. Usa componenti strutturali previsti (es. %s).',
            $categoria !== '' ? $categoria : 'selezionata',
            $examples !== '' ? $examples : 'fondo, parete, traversa'
        );
    }

    private function containsAnyKeyword(string $normalizedName, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($normalizedName, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function calculatedNameKeywordsForCurrentCategory(): array
    {
        return match ($this->normalizedCategoria()) {
            'cassa' => ['parete', 'fondo', 'coperchio', 'fianco', 'testata', 'legacc', 'basament', 'pannell'],
            'gabbia' => ['montant', 'travers', 'fondo', 'coperchio', 'pianton', 'listell', 'pannell', 'corrente'],
            'bancale' => ['moral', 'doga', 'pattin', 'perimetr', 'travers', 'listell', 'piano', 'fondo'],
            'legaccio' => ['legacc', 'travers', 'listell', 'moral', 'pattin', 'doga'],
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    private function calculatedNameExamplesForCurrentCategory(): array
    {
        return match ($this->normalizedCategoria()) {
            'cassa' => ['Parete lunga', 'Parete corta', 'Fondo', 'Coperchio'],
            'gabbia' => ['Montanti verticali', 'Traverse lunghe', 'Fondo'],
            'bancale' => ['Morali', 'Doghe piano superiore', 'Doghe fondo'],
            'legaccio' => ['Legacci', 'Traverse legaccio'],
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    private function manualOnlyKeywords(): array
    {
        return [
            'chiod',
            'vite',
            'bullon',
            'dado',
            'rondell',
            'ferrament',
            'graff',
            'fascett',
            'reggia',
            'nastro',
            'colla',
            'sigillant',
            'vernic',
        ];
    }

    private function isOptimizerManagedCategory(): bool
    {
        return in_array($this->normalizedCategoria(), self::OPTIMIZER_MANAGED_CATEGORIES, true);
    }

    private function normalizedCategoria(): string
    {
        return strtolower(trim((string) ($this->costruzione->categoria ?? '')));
    }

    private function isComponentAuthoringGuardEnabled(): bool
    {
        return (bool) config('production.component_authoring_guard_enabled', true);
    }
}
