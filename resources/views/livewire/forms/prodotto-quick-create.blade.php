<div>
    <div class="px-6 py-5 border-b border-border flex justify-between items-center bg-muted/20">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary/10 rounded-lg text-primary">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3.75h3M12 15.75h3M12 11.25a2.25 2.25 0 002.25-2.25V6m0 0l-2.25 2.25M14.25 6l2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-foreground">Nuovo Prodotto</h3>
                <p class="text-sm text-muted-foreground">Aggiungi rapidamente un articolo al catalogo</p>
            </div>
        </div>
        <button type="button" @click="showQuickProduct = false" class="text-muted-foreground hover:text-foreground p-1 hover:bg-muted rounded-full transition-colors">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    
    <form wire:submit="save" class="p-6 space-y-6">
        <div class="grid grid-cols-2 gap-6">
            <!-- Codice -->
            <div class="space-y-1.5">
                <label for="new_codice" class="text-sm font-medium text-foreground required">Codice Articolo</label>
                <input type="text" id="new_codice" wire:model="codice" class="form-input" placeholder="Es. TAV-ABETE">
                @error('codice') <span class="text-xs text-destructive">{{ $message }}</span> @enderror
            </div>

            <!-- Categoria -->
            <div class="space-y-1.5">
                <label for="new_categoria" class="text-sm font-medium text-foreground required">Categoria</label>
                <select id="new_categoria" wire:model="categoria" class="form-select">
                    @foreach($categorie as $cat)
                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                    @endforeach
                </select>
                @error('categoria') <span class="text-xs text-destructive">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Nome -->
        <div class="space-y-1.5">
            <label for="new_nome" class="text-sm font-medium text-foreground required">Nome Prodotto</label>
            <input type="text" id="new_nome" wire:model="nome" class="form-input" placeholder="Es. Tavola Abete 400x10x2 cm">
            @error('nome') <span class="text-xs text-destructive">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- Prezzo -->
            <div class="space-y-1.5">
                <label for="new_prezzo" class="text-sm font-medium text-foreground">Prezzo Unitario (€)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">€</span>
                    <input type="number" step="0.0001" id="new_prezzo" wire:model="prezzo_unitario" class="form-input pl-8" placeholder="0.00">
                </div>
                @error('prezzo_unitario') <span class="text-xs text-destructive">{{ $message }}</span> @enderror
            </div>

            <!-- Unità Misura -->
            <div class="space-y-1.5">
                <label for="new_um" class="text-sm font-medium text-foreground required">Unità Misura</label>
                <select id="new_um" wire:model="unita_misura" class="form-select">
                    @foreach($unitaMisura as $um)
                        <option value="{{ $um->value }}">{{ $um->label() }}</option>
                    @endforeach
                </select>
                @error('unita_misura') <span class="text-xs text-destructive">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- FITOK Toggle -->
        <div class="flex items-center justify-between p-4 rounded-lg bg-muted/30 border border-border">
            <div class="space-y-0.5">
                <label class="text-sm font-medium text-foreground">Tracciabilità FITOK</label>
                <p class="text-xs text-muted-foreground">Abilita se il prodotto richiede certificazione fitosanitaria</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" wire:model="soggetto_fitok" class="sr-only peer">
                <div class="w-11 h-6 bg-input peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-ring rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
            </label>
        </div>

        <div class="flex justify-end gap-3 pt-6 border-t border-border">
            <button type="button" @click="showQuickProduct = false" class="btn-ghost">Annulla</button>
            <button type="submit" class="btn-primary min-w-[140px]">
                <span wire:loading.remove>Crea Prodotto</span>
                <span wire:loading class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Salvataggio...
                </span>
            </button>
        </div>
    </form>
</div>
