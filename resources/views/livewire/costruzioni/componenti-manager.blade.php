<div>
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium">Componenti Costruzione</h3>
        <button wire:click="openModal" class="btn-primary">
            <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Aggiungi Componente
        </button>
    </div>

    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-x-auto border rounded-lg">
        <table class="table w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">Nome</th>
                    <th class="px-4 py-2 text-left">Tipo Dimensionamento</th>
                    <th class="px-4 py-2 text-left">Formula Lunghezza</th>
                    <th class="px-4 py-2 text-left">Formula Larghezza</th>
                    <th class="px-4 py-2 text-left">Formula Quantità</th>
                    <th class="px-4 py-2 text-left">Interno</th>
                    <th class="px-4 py-2 text-left">Rotazione</th>
                    <th class="px-4 py-2 text-right">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($componenti as $componente)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $componente->nome }}</td>
                        <td class="px-4 py-2">
                            <span class="badge {{ $componente->tipo_dimensionamento === 'CALCOLATO' ? 'badge-info' : 'badge-secondary' }}">
                                {{ $componente->tipo_dimensionamento }}
                            </span>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $componente->formula_lunghezza ?? '-' }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $componente->formula_larghezza ?? '-' }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $componente->formula_quantita }}</td>
                        <td class="px-4 py-2 text-xs">
                            @if($componente->is_internal)
                                <span class="badge badge-info">Sì</span>
                            @else
                                <span class="badge badge-secondary">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs">
                            @if($componente->allow_rotation)
                                <span class="badge badge-info">Sì</span>
                            @else
                                <span class="badge badge-secondary">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click="edit({{ $componente->id }})" class="btn-ghost btn-sm text-blue-600">
                                Modifica
                            </button>
                            <button wire:click="delete({{ $componente->id }})" 
                                    wire:confirm="Sei sicuro di voler eliminare questo componente?"
                                    class="btn-ghost btn-sm text-red-600">
                                Elimina
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            Nessun componente definito per questa costruzione.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Form -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">
                        {{ $isEditing ? 'Modifica Componente' : 'Nuovo Componente' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <form wire:submit="save" class="p-6 space-y-4">
                    <!-- Nome -->
                    <div>
                        <label class="form-label">Nome Componente *</label>
                        <input wire:model="nome" type="text" class="form-input @error('nome') form-input-error @enderror" placeholder="Es. Parete Laterale">
                        @error('nome') <p class="form-error">{{ $message }}</p> @enderror
                        @if($componentAuthoringGuardEnabled && $isOptimizerManagedCategory && $tipo_dimensionamento === 'CALCOLATO')
                            <p class="text-xs text-muted-foreground mt-1">
                                Categoria <strong>{{ $currentCategory }}</strong>: il nome di un componente CALCOLATO deve essere strutturale
                                (es. {{ implode(', ', $calculatedNameExamples) }}).
                            </p>
                        @endif
                        @if($componentAuthoringGuardEnabled && $isOptimizerManagedCategory && $tipo_dimensionamento === 'MANUALE')
                            <p class="text-xs text-muted-foreground mt-1">
                                Ferramenta/non ottimizzabili (es. chiodi, viti, regge) vanno definiti come MANUALE.
                            </p>
                        @endif
                    </div>

                    <!-- Tipo Dimensionamento -->
                    <div>
                        <label class="form-label">Tipo Dimensionamento *</label>
                        <select wire:model.live="tipo_dimensionamento" class="form-select @error('tipo_dimensionamento') form-input-error @enderror">
                            <option value="CALCOLATO">CALCOLATO (Usa formule)</option>
                            <option value="MANUALE">MANUALE (Inserimento nel lotto)</option>
                        </select>
                        @error('tipo_dimensionamento') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="text-xs text-muted-foreground mt-1">
                            Se "Calcolato", le dimensioni verranno derivate dalle dimensioni del lotto (L, W, H).
                            Se "Manuale", l'utente dovrà inserire le misure specifiche nel lotto.
                        </p>
                    </div>

                    @if($tipo_dimensionamento === 'CALCOLATO')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-muted/30 p-4 rounded-lg">
                            <div class="md:col-span-2 flex items-start gap-2">
                                <p class="text-xs font-medium mb-2 pt-1">Variabili disponibili: <code class="bg-gray-200 px-1 rounded">L</code>, <code class="bg-gray-200 px-1 rounded">W</code>, <code class="bg-gray-200 px-1 rounded">H</code>, <code class="bg-gray-200 px-1 rounded">T</code></p>
                                
                                <div x-data="{ open: false }" class="relative inline-block">
                                    <button 
                                        @mouseenter="open = true" 
                                        @mouseleave="open = false" 
                                        type="button" 
                                        class="text-blue-500 hover:text-blue-700 focus:outline-none"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                    </button>

                                    <div 
                                        x-show="open" 
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 translate-y-1"
                                        class="absolute z-10 w-72 px-4 py-3 mb-2 -ml-32 text-sm text-gray-700 bg-white rounded-lg shadow-xl border border-gray-100 bottom-full"
                                        style="display: none;"
                                    >
                                        <h4 class="font-semibold mb-2 text-gray-900">Come funzionano le formule?</h4>
                                        <p class="mb-3 text-xs leading-relaxed">
                                            Qui non inserisci misure fisse (es. 1200mm), ma la regola geometrica per ricavarle. Il software calcolerà automaticamente i millimetri esatti quando creerai un Lotto di Produzione.
                                        </p>
                                        <ul class="space-y-1 text-xs">
                                            <li><span class="font-mono font-bold text-primary">L</span> = Lunghezza finale cassa</li>
                                            <li><span class="font-mono font-bold text-primary">W</span> = Larghezza finale cassa</li>
                                            <li><span class="font-mono font-bold text-primary">H</span> = Altezza finale cassa</li>
                                            <li><span class="font-mono font-bold text-primary">T</span> = Spessore materiale scelto</li>
                                        </ul>
                                        <!-- Arrow -->
                                        <div class="absolute bottom-0 left-1/2 w-3 h-3 -mb-1 transform -translate-x-1/2 rotate-45 bg-white border-b border-r border-gray-100"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Formula Lunghezza -->
                            <div>
                                <label class="form-label">Formula Lunghezza</label>
                                <input wire:model="formula_lunghezza" type="text" class="form-input font-mono @error('formula_lunghezza') form-input-error @enderror" placeholder="Es. L - (2 * T)">
                                @error('formula_lunghezza') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <!-- Formula Larghezza -->
                            <div>
                                <label class="form-label">Formula Larghezza</label>
                                <input wire:model="formula_larghezza" type="text" class="form-input font-mono @error('formula_larghezza') form-input-error @enderror" placeholder="Es. H">
                                @error('formula_larghezza') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="inline-flex items-center gap-2 text-sm font-medium">
                                    <input wire:model="is_internal" type="checkbox" class="form-checkbox">
                                    Componente interno
                                </label>
                                @error('is_internal') <p class="form-error">{{ $message }}</p> @enderror
                                <p class="text-xs text-muted-foreground mt-1">
                                    Flag geometrico per futuri optimizer (es. offset interno).
                                </p>
                            </div>

                            <div>
                                <label class="inline-flex items-center gap-2 text-sm font-medium">
                                    <input wire:model="allow_rotation" type="checkbox" class="form-checkbox">
                                    Consenti rotazione
                                </label>
                                @error('allow_rotation') <p class="form-error">{{ $message }}</p> @enderror
                                <p class="text-xs text-muted-foreground mt-1">
                                    Flag per consentire rotazione pezzo nelle strategie future.
                                </p>
                            </div>
                        </div>
                    @endif

                    <!-- Formula Quantità -->
                    <div>
                        <label class="form-label">Quantità (Formula o Numero) *</label>
                        <input wire:model="formula_quantita" type="text" class="form-input font-mono @error('formula_quantita') form-input-error @enderror" placeholder="Es. 2 oppure (L / 500) + 1">
                        @error('formula_quantita') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="text-xs text-muted-foreground mt-1">Puoi usare un numero fisso o una formula.</p>
                    </div>

                    <div class="flex justify-end pt-4 gap-3">
                        <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Annulla</button>
                        <button type="submit" class="btn-primary">Salva Componente</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
