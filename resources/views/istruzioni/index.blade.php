<x-layouts.app title="Istruzioni">
    <x-page-header
        title="Istruzioni"
        description="Guida completa all'utilizzo del gestionale demo"
    />

    <div class="space-y-6" x-data="{ activeTab: 'guida-rapida' }">
        <!-- Tabs Navigation -->
        <div class="bg-card rounded-lg shadow-sm border border-border">
            <div class="border-b border-border">
                <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                    <button
                        @click="activeTab = 'guida-rapida'"
                        :class="activeTab === 'guida-rapida' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors"
                    >
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            Guida Rapida
                        </div>
                    </button>
                    <button
                        @click="activeTab = 'guida-completa'"
                        :class="activeTab === 'guida-completa' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors"
                    >
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                            Guida Completa
                        </div>
                    </button>
                    <button
                        @click="activeTab = 'concetti-chiave'"
                        :class="activeTab === 'concetti-chiave' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors"
                    >
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                            </svg>
                            Concetti Chiave
                        </div>
                    </button>
                    <button
                        @click="activeTab = 'tipi-costruzione'"
                        :class="activeTab === 'tipi-costruzione' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors"
                    >
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h10.5A2.25 2.25 0 0119.5 6v12a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 18V6a2.25 2.25 0 012.25-2.25z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25h7.5M8.25 12h7.5M8.25 15.75h4.5" />
                            </svg>
                            Tipi Costruzione
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Guida Rapida -->
                <div x-show="activeTab === 'guida-rapida'" x-transition>
                    <div class="prose prose-slate max-w-none">
                        <h2 class="text-2xl font-bold text-foreground mb-6">Guida Rapida</h2>
                        <p class="text-muted-foreground mb-8">Una panoramica delle operazioni più comuni nel gestionale demo.</p>

                        <!-- Creare un Preventivo -->
                        <div class="bg-muted/30 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground text-sm font-bold">1</span>
                                Creare un Preventivo
                            </h3>
                            <ol class="space-y-3 ml-10">
                                <li class="text-foreground">
                                    <strong>Accedi alla sezione Preventivi</strong> dal menu laterale
                                </li>
                                <li class="text-foreground">
                                    <strong>Clicca su "Nuovo Preventivo"</strong> in alto a destra
                                </li>
                                <li class="text-foreground">
                                    <strong>Seleziona il cliente</strong> dall'elenco (o creane uno nuovo se necessario)
                                </li>
                                <li class="text-foreground">
                                    <strong>Aggiungi righe preventivo</strong> cliccando su "Aggiungi Riga":
                                    <ul class="mt-2 ml-6 space-y-1 text-sm text-muted-foreground">
                                        <li>Seleziona il prodotto o costruzione</li>
                                        <li>Inserisci la quantità</li>
                                        <li>Il prezzo viene calcolato automaticamente</li>
                                    </ul>
                                </li>
                                <li class="text-foreground">
                                    <strong>Salva come bozza</strong> per continuare in seguito, oppure <strong>conferma il preventivo</strong> per renderlo definitivo
                                </li>
                                <li class="text-foreground">
                                    <strong>Scarica il PDF</strong> per inviarlo al cliente
                                </li>
                            </ol>
                            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <p class="text-sm text-blue-800 dark:text-blue-200 flex items-start gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                    <span><strong>Nota:</strong> Una volta accettato, il preventivo non può più essere modificato. Assicurati che tutti i dati siano corretti prima di confermare.</span>
                                </p>
                            </div>
                        </div>

                        <!-- Convertire Preventivo in Ordine -->
                        <div class="bg-muted/30 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground text-sm font-bold">2</span>
                                Convertire un Preventivo in Ordine
                            </h3>
                            <ol class="space-y-3 ml-10">
                                <li class="text-foreground">
                                    <strong>Apri il preventivo</strong> dalla lista preventivi
                                </li>
                                <li class="text-foreground">
                                    <strong>Clicca su "Converti in Ordine"</strong> (visibile solo per preventivi accettati e non ancora convertiti)
                                </li>
                                <li class="text-foreground">
                                    <strong>Il sistema crea automaticamente un ordine</strong> con tutti i dati del preventivo
                                </li>
                                <li class="text-foreground">
                                    <strong>Modifica l'ordine</strong> se necessario (es. aggiungi data di consegna, note interne)
                                </li>
                                <li class="text-foreground">
                                    <strong>Conferma l'ordine</strong> per avviare la produzione
                                </li>
                            </ol>
                        </div>

                        <!-- Gestire Magazzino -->
                        <div class="bg-muted/30 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground text-sm font-bold">3</span>
                                Gestire il Magazzino (Carico/Scarico)
                            </h3>

                            <h4 class="font-semibold text-foreground mt-6 mb-3 ml-10">Carico Materiale:</h4>
                            <ol class="space-y-3 ml-10">
                                <li class="text-foreground">
                                    <strong>Vai su Magazzino</strong> dal menu laterale
                                </li>
                                <li class="text-foreground">
                                    <strong>Clicca su "Carico"</strong> nella barra superiore
                                </li>
                                <li class="text-foreground">
                                    <strong>Compila il modulo:</strong>
                                    <ul class="mt-2 ml-6 space-y-1 text-sm text-muted-foreground">
                                        <li>Seleziona il materiale</li>
                                        <li>Inserisci lunghezza, larghezza, quantità</li>
                                        <li>Scegli il fornitore</li>
                                        <li>Inserisci numero lotto e certificazione (se presente)</li>
                                    </ul>
                                </li>
                                <li class="text-foreground">
                                    <strong>Salva</strong> per registrare il carico
                                </li>
                            </ol>

                            <h4 class="font-semibold text-foreground mt-6 mb-3 ml-10">Scarico Materiale:</h4>
                            <ol class="space-y-3 ml-10">
                                <li class="text-foreground">
                                    <strong>Vai su Magazzino → Scarico</strong>
                                </li>
                                <li class="text-foreground">
                                    <strong>Seleziona il lotto materiale</strong> da cui scaricare
                                </li>
                                <li class="text-foreground">
                                    <strong>Inserisci la quantità</strong> da scaricare
                                </li>
                                <li class="text-foreground">
                                    <strong>Specifica la causale</strong> (produzione, scarto, ecc.)
                                </li>
                                <li class="text-foreground">
                                    <strong>Opzionale:</strong> collega lo scarico a un lotto produzione
                                </li>
                            </ol>

                            <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <p class="text-sm text-amber-800 dark:text-amber-200 flex items-start gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    <span><strong>Attenzione:</strong> Gli scarichi sono operazioni definitive. Verifica sempre le quantità prima di confermare.</span>
                                </p>
                            </div>
                        </div>

                        <!-- Creare Lotto Produzione -->
                        <div class="bg-muted/30 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground text-sm font-bold">4</span>
                                Creare un Lotto di Produzione
                            </h3>
                            <ol class="space-y-3 ml-10">
                                <li class="text-foreground">
                                    <strong>Vai su Lotti Produzione</strong> dal menu laterale
                                </li>
                                <li class="text-foreground">
                                    <strong>Clicca su "Nuovo Lotto"</strong>
                                </li>
                                <li class="text-foreground">
                                    <strong>Seleziona la costruzione</strong> da produrre
                                </li>
                                <li class="text-foreground">
                                    <strong>Inserisci dimensioni e quantità</strong> del lotto
                                </li>
                                <li class="text-foreground">
                                    <strong>Seleziona i materiali di taglio</strong> e clicca <strong>Calcola Ottimizzazione</strong>:
                                    <ul class="mt-2 ml-6 space-y-1 text-sm text-muted-foreground">
                                        <li>Costruzioni geometriche: compare un solo campo <strong>Materiale (Asse)</strong>.</li>
                                        <li>Routine cassa Excel supportate: compare la card <strong>Materiali cassa</strong> con profili distinti (es. <strong>base</strong> e <strong>fondo</strong>).</li>
                                    </ul>
                                </li>
                                <li class="text-foreground">
                                    <strong>Verifica il piano di taglio</strong> proposto:
                                    <ul class="mt-2 ml-6 space-y-1 text-sm text-muted-foreground">
                                        <li>Assi necessarie</li>
                                        <li>Scarto totale e percentuale</li>
                                        <li>Costo e prezzo stimati</li>
                                        <li>Routine rilevata e trace optimizer in caso di audit</li>
                                    </ul>
                                </li>
                                <li class="text-foreground">
                                    <strong>Salva il lotto</strong> e, se necessario, usa <strong>Salva questo piano di taglio</strong>
                                </li>
                                <li class="text-foreground">
                                    <strong>Avvia e completa</strong> il lotto dalla tabella per eseguire il ciclo produttivo
                                </li>
                            </ol>

                            <div class="mt-4 p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <p class="text-sm text-green-800 dark:text-green-200 flex items-start gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span><strong>Suggerimento:</strong> Per le casse Excel il sistema mantiene due livelli: <strong>excel_strict</strong> per confronto storico e <strong>physical</strong> per il piano di taglio reale, più rigoroso sulle dimensioni delle assi.</span>
                                </p>
                            </div>
                        </div>

                        <!-- Visualizzare Report FITOK -->
                        <div class="bg-muted/30 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground text-sm font-bold">5</span>
                                Visualizzare il Registro FITOK
                            </h3>
                            <ol class="space-y-3 ml-10">
                                <li class="text-foreground">
                                    <strong>Vai su Registro FITOK</strong> dal menu laterale
                                </li>
                                <li class="text-foreground">
                                    <strong>Filtra per periodo</strong> utilizzando i campi data
                                </li>
                                <li class="text-foreground">
                                    <strong>Visualizza i movimenti</strong> dei materiali soggetti FITOK nel periodo selezionato
                                </li>
                                <li class="text-foreground">
                                    <strong>Esporta in PDF o Excel</strong> per conservare il registro ufficiale
                                </li>
                            </ol>

                            <div class="mt-4 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-sm text-red-800 dark:text-red-200 flex items-start gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    <span><strong>Importante:</strong> Per ottenere un lotto produzione 100% FITOK, tutti i consumi registrati devono provenire da lotti materiale certificati.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guida Completa -->
                <div x-show="activeTab === 'guida-completa'" x-transition>
                    <div class="prose prose-slate max-w-none">
                        <h2 class="text-2xl font-bold text-foreground mb-6">Guida Completa</h2>
                        <p class="text-muted-foreground mb-8">Documentazione dettagliata di tutti i moduli del gestionale.</p>

                        <!-- Aggiornamenti Produzione -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Aggiornamenti Produzione (stato corrente)</h3>
                            <p class="text-foreground mb-3">
                                Sezione di riepilogo delle funzionalità introdotte nelle ultime iterazioni del motore di produzione.
                            </p>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Flusso operativo ufficiale (2026-03)</h4>
                            <ol class="space-y-2 text-foreground ml-6">
                                <li><strong>Preventivo:</strong> quando diventa <strong>ACCETTATO</strong> entra in sola lettura.</li>
                                <li><strong>Ordine da preventivo:</strong> viene creato in stato <strong>CONFERMATO</strong> (non PRONTO).</li>
                                <li><strong>Ordine PRONTO:</strong> stato impostabile solo manualmente quando la produzione è realmente terminata.</li>
                                <li><strong>Lotto produzione:</strong> deve avere origine univoca, associato a <strong>Preventivo oppure Ordine</strong> (mai entrambi).</li>
                                <li><strong>Avvio lotto:</strong> genera/aggiorna BOM operativa e pianifica materiale opzionato a magazzino.</li>
                                <li><strong>Completamento lotto:</strong> esegue scarichi reali da magazzino, registra scarti e aggiorna tracciabilità FITOK.</li>
                                <li><strong>Magazzino aggregato:</strong> vista a tab con <strong>Giacenze</strong>, <strong>Opzionato/Consumato</strong> e <strong>Scarti</strong>.</li>
                            </ol>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Legenda modifiche:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>[NUOVO]</strong> funzionalità non presente in precedenza</li>
                                <li><strong>[MIGLIORATO]</strong> funzionalità esistente con comportamento aggiornato</li>
                                <li><strong>[COMPATIBILITÀ]</strong> allineamento a logiche legacy/Excel senza interrompere il flusso attuale</li>
                                <li><strong>[SICUREZZA]</strong> restrizioni accesso, validazioni o audit</li>
                                <li><strong>[DATI]</strong> migrazione, backfill o allineamento dati storici</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Funzionalità introdotte:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>[MIGLIORATO]</strong> Ottimizzazione categoria <strong>cassa</strong> con piano di taglio coerente su pannellature reali e minimizzazione scarti.</li>
                                <li><strong>[NUOVO]</strong> Routing optimizer per categoria (<strong>cassa</strong>, <strong>gabbia</strong>, <strong>bancale</strong>, <strong>legaccio</strong>) con modalità per categoria <strong>preview</strong>/<strong>compatibility</strong>/<strong>strict</strong> dove disponibili.</li>
                                <li><strong>[NUOVO]</strong> Pagina <strong>Settings Produzione</strong> (solo admin) con controllo centralizzato di kerf, soglia scarto riutilizzabile e mode per categoria.</li>
                                <li><strong>[MIGLIORATO]</strong> Tooltip di supporto nei Settings Produzione per chiarire ogni parametro operativo.</li>
                                <li><strong>[MIGLIORATO]</strong> Settings Produzione con banner operativo per mode categoria in <strong>preview</strong> e alert critico su modifiche kerf/mode.</li>
                                <li><strong>[NUOVO]</strong> Pannello <strong>Debug Optimizer (admin)</strong> nel form lotto con trace, cutting plan e metadati runtime.</li>
                                <li><strong>[MIGLIORATO]</strong> Debug optimizer con dettaglio <strong>componente -&gt; assi</strong> (richiesto/prodotto/scarto allocato e assegnazione bin).</li>
                                <li><strong>[MIGLIORATO]</strong> Calcolo economico su volumetria materiale (lordo/netto/scarto) con valorizzazione prezzo al metro cubo per i materiali compatibili.</li>
                                <li><strong>[SICUREZZA]</strong> Cooldown server-side sul comando <strong>Calcola Ottimizzazione</strong> per evitare ricalcoli ravvicinati e carico inutile.</li>
                                <li><strong>[SICUREZZA]</strong> Hardened authoring dei <strong>ComponentiCostruzione</strong>: componenti non ottimizzabili (es. ferramenta) gestiti come manuali e fuori optimizer.</li>
                                <li><strong>[SICUREZZA]</strong> Audit append-only delle modifiche a <strong>production_settings</strong> e accesso scrittura riservato agli amministratori.</li>
                                <li><strong>[DATI]</strong> Comando di backfill volumi materiali lotto (inclusi soft-deleted) per valorizzare campi netti/scarto sui dati storici.</li>
                                <li><strong>[DATI]</strong> Comando <strong>production:generate-cassa-dataset</strong> per creare campioni sintetici `cassa` con validazione tecnica automatica (scarto record invalidi).</li>
                                <li><strong>[MIGLIORATO]</strong> Cancellazione lotto con coerenza su preventivo: riga collegata rimossa automaticamente (cascade applicativo/DB).</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-6 mb-2">Settings Produzione - guida operativa</h4>
                            <p class="text-foreground mb-3">
                                La pagina <strong>Settings Produzione</strong> controlla il comportamento runtime degli optimizer. Ogni modifica viene tracciata nello storico audit.
                            </p>

                            <h5 class="font-semibold text-foreground mt-4 mb-2">Parametri globali</h5>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Kerf di taglio (mm):</strong> spessore lama sottratto nel piano di taglio. Aumentarlo riduce materiale utile per asse e può aumentare scarto/numero assi.</li>
                                <li><strong>Soglia scarto riutilizzabile (mm):</strong> lunghezza minima oltre cui lo scarto è marcato come riutilizzabile in magazzino scarti.</li>
                            </ul>

                            <h5 class="font-semibold text-foreground mt-4 mb-2">Mode per categoria</h5>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Cassa optimizer mode:</strong></li>
                                <li><strong>physical</strong> = usa l optimizer cassa fisico con profili materiali reali, vincoli geometrici e controllo lordo/netto/scarto.</li>
                                <li><strong>excel_strict</strong> = riproduce le righe legacy Excel sulle routine cassa supportate per audit e confronto storico.</li>
                                <li><strong>legacy</strong> = forza fallback al bin packing legacy 1D (utile per confronto o rollback operativo).</li>
                            </ul>

                            <ul class="space-y-2 text-foreground ml-6 mt-3">
                                <li><strong>Gabbia/Bancale/Legaccio Excel mode:</strong></li>
                                <li><strong>preview</strong> = calcola anteprima Excel nel trace ma il taglio effettivo usa fallback rettangolare v1.</li>
                                <li><strong>compatibility</strong> = usa i pezzi normalizzati dal builder Excel per il taglio effettivo (fase di allineamento graduale).</li>
                                <li><strong>strict</strong> = forza il flusso Excel sulle routine supportate; se i dati richiesti mancano, genera errore esplicito senza fallback silenzioso.</li>
                            </ul>

                            <h5 class="font-semibold text-foreground mt-4 mb-2">Audit, lock e buone pratiche</h5>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Motivo modifica (audit):</strong> campo consigliato per spiegare il contesto operativo del cambiamento.</li>
                                <li><strong>Storico append-only:</strong> le modifiche sono registrate con utente, timestamp, valore precedente e nuovo valore.</li>
                                <li><strong>Lock policy ambiente:</strong> alcune chiavi possono risultare bloccate da policy ENV in produzione.</li>
                                <li><strong>Regola operativa:</strong> passare da <strong>preview</strong> a <strong>compatibility/strict</strong> solo dopo benchmark + test + validazione su lotti reali.</li>
                            </ul>
                        </div>

                        <!-- Dashboard -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Dashboard</h3>
                            <p class="text-foreground mb-3">
                                La dashboard è la pagina principale che fornisce una visione d'insieme dell'attività aziendale.
                            </p>
                            <h4 class="font-semibold text-foreground mt-4 mb-2">Elementi principali:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Statistiche chiave:</strong> Numero totale di ordini, preventivi, lotti in produzione</li>
                                <li><strong>Grafici:</strong> Andamento mensile delle vendite, stato degli ordini</li>
                                <li><strong>Attività recenti:</strong> Ultimi ordini, preventivi e movimenti di magazzino</li>
                                <li><strong>Alertre e notifiche:</strong> Materiali in esaurimento, ordini in scadenza</li>
                            </ul>
                        </div>

                        <!-- Clienti -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Clienti</h3>
                            <p class="text-foreground mb-3">
                                Gestione completa dell'anagrafica clienti con storico ordini e preventivi.
                            </p>
                            <h4 class="font-semibold text-foreground mt-4 mb-2">Funzionalità:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Anagrafica completa:</strong> Nome, ragione sociale, P.IVA, indirizzo, contatti</li>
                                <li><strong>Storico relazioni:</strong> Tutti i preventivi e ordini associati al cliente</li>
                                <li><strong>Note cliente:</strong> Annotazioni personali, preferenze, condizioni speciali</li>
                                <li><strong>Ricerca e filtri:</strong> Trova rapidamente i clienti per nome, città, o codice</li>
                            </ul>
                        </div>

                        <!-- Fornitori -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Fornitori</h3>
                            <p class="text-foreground mb-3">
                                Gestione fornitori di materiali con tracciabilità degli acquisti.
                            </p>
                            <h4 class="font-semibold text-foreground mt-4 mb-2">Informazioni gestite:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Dati anagrafici:</strong> Ragione sociale, P.IVA, sede legale</li>
                                <li><strong>Contatti:</strong> Telefono, email, referente commerciale</li>
                                <li><strong>Materiali forniti:</strong> Tipologie di materiali disponibili</li>
                                <li><strong>Storico carichi:</strong> Tutti i carichi effettuati dal fornitore</li>
                            </ul>
                        </div>

                        <!-- Prodotti -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Prodotti</h3>
                            <p class="text-foreground mb-3">
                                Catalogo prodotti con distinzione tra materiali e costruzioni.
                            </p>
                            <h4 class="font-semibold text-foreground mt-4 mb-2">Tipologie:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>MATERIALE:</strong> Materie prime acquistate (legno, metallo, pannelli, ecc.)</li>
                                <li><strong>COSTRUZIONE:</strong> Prodotti finiti o semilavorati creati in azienda</li>
                            </ul>
                            <h4 class="font-semibold text-foreground mt-4 mb-2">Informazioni prodotto:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Codice e nome:</strong> Identificativo univoco e descrizione</li>
                                <li><strong>Categoria:</strong> Classificazione per tipo (porte, finestre, mobili, ecc.)</li>
                                <li><strong>Prezzo:</strong> Prezzo di listino per preventivi</li>
                                <li><strong>Unità di misura:</strong> Pezzi, metri, metri quadri</li>
                                <li><strong>Note tecniche:</strong> Specifiche, dimensioni standard, lavorazioni</li>
                            </ul>
                        </div>

                        <!-- Magazzino -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Magazzino</h3>
                            <p class="text-foreground mb-3">
                                Sistema completo di gestione magazzino con tracciabilità per lotti.
                            </p>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Viste disponibili:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Vista Lotti:</strong> Elenco dettagliato di ogni singolo lotto con numero certificato, dimensioni, quantità residua</li>
                                <li><strong>Vista Aggregata:</strong> Giacenze totali per materiale, con filtri FITOK, giacenza e scarti disponibili</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Operazioni di carico:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li>Selezione materiale e fornitore</li>
                                <li>Inserimento dimensioni (lunghezza, larghezza, spessore se applicabile)</li>
                                <li>Quantità in ingresso</li>
                                <li>Numero lotto fornitore</li>
                                <li>Certificazione FITOK (se presente)</li>
                                <li>Data di carico</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Operazioni di scarico:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li>Selezione lotto da scaricare (con visualizzazione quantità disponibile)</li>
                                <li>Quantità da scaricare</li>
                                <li>Causale (produzione, scarto, rettifica, altro)</li>
                                <li>Collegamento opzionale a un lotto produzione</li>
                                <li>Note operazione</li>
                            </ul>

                            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    <strong>Tracciabilità:</strong> Ogni movimento è registrato con data, utente, e può essere collegato a un lotto produttivo per piena tracciabilità.
                                </p>
                            </div>
                        </div>

                        <!-- Preventivi -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Preventivi</h3>
                            <p class="text-foreground mb-3">
                                Sistema di creazione e gestione preventivi con generazione automatica PDF.
                            </p>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Stati preventivo:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>BOZZA:</strong> Preventivo in lavorazione, modificabile</li>
                                <li><strong>INVIATO:</strong> Preventivo inviato al cliente</li>
                                <li><strong>ACCETTATO:</strong> Preventivo approvato dal cliente</li>
                                <li><strong>RIFIUTATO:</strong> Preventivo non approvato</li>
                                <li><strong>SCADUTO:</strong> Preventivo oltre la validità</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Workflow:</h4>
                            <ol class="space-y-2 text-foreground ml-6">
                                <li>Creazione preventivo con selezione cliente</li>
                                <li>Aggiunta righe (prodotti/costruzioni) con quantità e prezzi</li>
                                <li>Calcolo automatico totali</li>
                                <li>Salvataggio come bozza per revisione</li>
                                <li>Invio al cliente (stato: INVIATO)</li>
                                <li>Esito cliente: ACCETTATO o RIFIUTATO</li>
                                <li>Generazione e download PDF con layout aziendale</li>
                                <li>Conversione in ordine disponibile per i preventivi ACCETTATI</li>
                            </ol>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Elementi del preventivo:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li>Numero progressivo automatico</li>
                                <li>Data emissione</li>
                                <li>Validità (giorni)</li>
                                <li>Condizioni di pagamento</li>
                                <li>Note e clausole contrattuali</li>
                            </ul>
                        </div>

                        <!-- Ordini -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Ordini</h3>
                            <p class="text-foreground mb-3">
                                Gestione ordini cliente con tracciamento dello stato di avanzamento.
                            </p>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Stati ordine:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>CONFERMATO:</strong> Ordine registrato e pronto alla pianificazione</li>
                                <li><strong>IN PRODUZIONE:</strong> Lavorazione in corso</li>
                                <li><strong>PRONTO:</strong> Completato, pronto per spedizione/ritiro</li>
                                <li><strong>CONSEGNATO:</strong> Consegnato al cliente</li>
                                <li><strong>FATTURATO:</strong> Chiuso lato amministrativo</li>
                                <li><strong>ANNULLATO:</strong> Ordine cancellato</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Funzionalità:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li>Creazione manuale o da preventivo</li>
                                <li>Gli ordini creati da preventivo partono in stato <strong>CONFERMATO</strong></li>
                                <li>Lo stato <strong>PRONTO</strong> viene impostato solo manualmente</li>
                                <li>Gestione date (ordine, consegna prevista, consegna effettiva)</li>
                                <li>Collegamento con lotti produttivi</li>
                                <li>Note interne e comunicazioni cliente</li>
                                <li>Cambio stato guidato nel flusso operativo</li>
                            </ul>
                        </div>

                        <!-- Lotti Produzione -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Lotti Produzione</h3>
                            <p class="text-foreground mb-3">
                                Cuore del sistema produttivo: pianificazione, tracciamento materiali e gestione certificazioni.
                            </p>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Stati lotto:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>BOZZA:</strong> Pianificazione, materiali non ancora prelevati</li>
                                <li><strong>CONFERMATO:</strong> Lotto pronto per l'avvio operativo</li>
                                <li><strong>IN LAVORAZIONE:</strong> Produzione attiva</li>
                                <li><strong>COMPLETATO:</strong> Lotto finito, prodotto pronto</li>
                                <li><strong>ANNULLATO:</strong> Lotto chiuso senza completamento</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Informazioni lotto:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Numero lotto:</strong> Identificativo univoco progressivo</li>
                                <li><strong>Prodotto/Costruzione:</strong> Cosa viene prodotto</li>
                                <li><strong>Quantità:</strong> Pezzi da produrre</li>
                                <li><strong>Origine commerciale:</strong> Preventivo <em>oppure</em> Ordine (associazione univoca)</li>
                                <li><strong>Date:</strong> Creazione lotto, inizio lavorazione, fine lavorazione</li>
                                <li><strong>Materiali utilizzati:</strong> Lista lotti materiale con quantità prelevate</li>
                                <li><strong>Certificazione FITOK:</strong> Calcolata automaticamente in base ai materiali</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Workflow produttivo:</h4>
                            <ol class="space-y-2 text-foreground ml-6">
                                <li><strong>Pianificazione (BOZZA):</strong> Definizione prodotto, parametri e piano di taglio</li>
                                <li><strong>Conferma (opzionale):</strong> Passaggio intermedio prima dell'avvio</li>
                                <li><strong>Avvio (IN LAVORAZIONE):</strong> Registrazione inizio lavorazione, BOM operativa e opzioni materiali</li>
                                <li><strong>Lavorazione:</strong> Aggiornamento note e avanzamento</li>
                                <li><strong>Completamento:</strong> Verifica disponibilità, scarichi reali, calcolo FITOK e registrazione scarti</li>
                                <li><strong>Chiusura:</strong> Cambio stato a COMPLETATO (oppure ANNULLATO se interrotto)</li>
                            </ol>

                            <div class="mt-4 p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <p class="text-sm text-green-800 dark:text-green-200">
                                    <strong>Ottimizzazione:</strong> Il sistema calcola un piano di taglio (assi necessarie e scarto) in base a costruzione, dimensioni e materiale selezionato.
                                </p>
                            </div>
                        </div>

                        <!-- Report FITOK -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">Report FITOK</h3>
                            <p class="text-foreground mb-3">
                                Registro dei movimenti di magazzino relativi a materiali soggetti FITOK.
                            </p>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Contenuti report:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li>Data movimento</li>
                                <li>Tipo movimento (carico, scarico, rettifiche)</li>
                                <li>Lotto materiale e prodotto</li>
                                <li>Quantità movimentata e unità di misura</li>
                                <li>Dati FITOK (certificato, data/tipo trattamento, paese)</li>
                                <li>Documento associato (se presente)</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Filtri disponibili:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li>Periodo predefinito (oggi, settimana, mese, trimestre, anno, personalizzato)</li>
                                <li>Intervallo date (Da/A)</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Esportazioni:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>PDF:</strong> Formato ufficiale per archiviazione e audit</li>
                                <li><strong>Excel:</strong> Per analisi e elaborazioni personalizzate</li>
                            </ul>

                            <div class="mt-4 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-sm text-red-800 dark:text-red-200">
                                    <strong>Normativa FITOK:</strong> Il registro FITOK è un documento legale. Assicurarsi che tutti i certificati materiali siano inseriti correttamente nel sistema.
                                </p>
                            </div>
                        </div>

                        <!-- BOM -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-foreground mb-3 pb-2 border-b border-border">BOM (Distinte Base)</h3>
                            <p class="text-foreground mb-3">
                                Sistema di gestione Distinte Base come template di riferimento materiali.
                            </p>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Cos'è una BOM:</h4>
                            <p class="text-foreground ml-6 mb-3">
                                Una Distinta Base (Bill of Materials) è una lista strutturata di tutti i materiali,
                                componenti e quantità necessarie per produrre un determinato prodotto finito.
                            </p>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Vantaggi:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Standardizzazione:</strong> Garantisce che ogni prodotto sia realizzato sempre con gli stessi materiali</li>
                                <li><strong>Velocità:</strong> Preparazione rapida di template riutilizzabili</li>
                                <li><strong>Precisione:</strong> Riduce errori di selezione materiali</li>
                                <li><strong>Calcolo costi:</strong> Consente di calcolare il costo standard di produzione</li>
                                <li><strong>Pianificazione:</strong> Facilita la previsione dei fabbisogni di materiale</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Struttura BOM:</h4>
                            <ul class="space-y-2 text-foreground ml-6">
                                <li><strong>Codice e nome BOM:</strong> Identificativo della distinta</li>
                                <li><strong>Prodotto finito:</strong> Cosa viene realizzato</li>
                                <li><strong>Lista materiali:</strong> Ogni materiale con quantità standard necessaria</li>
                                <li><strong>Note:</strong> Istruzioni di assemblaggio o particolarità</li>
                            </ul>

                            <h4 class="font-semibold text-foreground mt-4 mb-2">Utilizzo:</h4>
                            <ol class="space-y-2 text-foreground ml-6">
                                <li>Creare una BOM per ogni prodotto standard</li>
                                <li>Definire i materiali e le quantità tipo</li>
                                <li>Usare la BOM come riferimento tecnico/commerciale</li>
                                <li>Configurare il lotto operativo con Costruzioni + Ottimizzazione</li>
                                <li>Allineare eventuali differenze caso per caso</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Concetti Chiave -->
                <div x-show="activeTab === 'concetti-chiave'" x-transition>
                    <div class="prose prose-slate max-w-none">
                        <h2 class="text-2xl font-bold text-foreground mb-6">Concetti Chiave</h2>
                        <p class="text-muted-foreground mb-8">Comprensione approfondita dei concetti fondamentali del sistema.</p>

                        <!-- Materiali vs Costruzioni -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/20 dark:to-indigo-950/20 rounded-lg p-6 mb-6 border border-blue-200 dark:border-blue-800">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                </svg>
                                Materiali vs Costruzioni
                            </h3>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                    <h4 class="font-semibold text-foreground mb-2 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        MATERIALE
                                    </h4>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Materie prime acquistate da fornitori esterni e stoccate in magazzino.
                                    </p>
                                    <p class="text-sm font-medium text-foreground mb-2">Esempi:</p>
                                    <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                        <li>Pannelli in legno</li>
                                        <li>Lamiere metalliche</li>
                                        <li>Tubi in acciaio inox</li>
                                        <li>Vernici e finiture</li>
                                    </ul>
                                    <p class="text-sm font-medium text-foreground mt-3 mb-2">Caratteristiche:</p>
                                    <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                        <li>Gestiti per lotti con tracciabilità</li>
                                        <li>Hanno dimensioni fisiche (L x l x sp)</li>
                                        <li>Possono avere certificazione FITOK</li>
                                        <li>Si scaricano dal magazzino quando usati</li>
                                    </ul>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                                    <h4 class="font-semibold text-foreground mb-2 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                        COSTRUZIONE
                                    </h4>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Prodotti finiti o semilavorati realizzati internamente dall'azienda.
                                    </p>
                                    <p class="text-sm font-medium text-foreground mb-2">Esempi:</p>
                                    <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                        <li>Porte in legno</li>
                                        <li>Infissi su misura</li>
                                        <li>Mobili componibili</li>
                                        <li>Strutture metalliche</li>
                                    </ul>
                                    <p class="text-sm font-medium text-foreground mt-3 mb-2">Caratteristiche:</p>
                                    <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                        <li>Creati tramite lotti produttivi</li>
                                        <li>Richiedono materiali per essere prodotti</li>
                                        <li>Sono ciò che viene venduto ai clienti</li>
                                        <li>Possono avere distinte base (BOM) come template</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                <p class="text-sm text-blue-900 dark:text-blue-100">
                                    <strong>In sintesi:</strong> I MATERIALI sono ciò che compri e tieni in magazzino.
                                    Le COSTRUZIONI sono ciò che produci usando i materiali e vendi ai clienti.
                                </p>
                            </div>
                        </div>

                        <!-- Lotti Materiale vs Lotti Produzione -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20 rounded-lg p-6 mb-6 border border-green-200 dark:border-green-800">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3" />
                                </svg>
                                Lotti Materiale vs Lotti Produzione
                            </h3>

                            <div class="space-y-4">
                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-green-200 dark:border-green-800">
                                    <h4 class="font-semibold text-foreground mb-2 flex items-center gap-2">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold">1</span>
                                        Lotto Materiale
                                    </h4>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Rappresenta una partita specifica di materiale acquistato, con caratteristiche e provenienza univoche.
                                    </p>
                                    <div class="grid md:grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-sm font-medium text-foreground mb-1">Dati identificativi:</p>
                                            <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                                <li>Numero lotto interno</li>
                                                <li>Numero lotto fornitore</li>
                                                <li>Certificato FITOK (se presente)</li>
                                                <li>Data carico</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-foreground mb-1">Dati fisici:</p>
                                            <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                                <li>Dimensioni (L x l x sp)</li>
                                                <li>Quantità iniziale</li>
                                                <li>Quantità residua</li>
                                                <li>Fornitore</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/30 rounded">
                                        <p class="text-sm text-green-900 dark:text-green-100">
                                            <strong>Esempio:</strong> "100 pannelli di legno 2000x1000x18mm, lotto fornitore ABC123, certificato FITOK F-20250115-001, caricati il 15/01/2025"
                                        </p>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-emerald-200 dark:border-emerald-800">
                                    <h4 class="font-semibold text-foreground mb-2 flex items-center gap-2">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-500 text-white text-xs font-bold">2</span>
                                        Lotto Produzione
                                    </h4>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Rappresenta un'attività produttiva: la creazione di una quantità di prodotti finiti utilizzando materiali dal magazzino.
                                    </p>
                                    <div class="grid md:grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-sm font-medium text-foreground mb-1">Cosa definisce:</p>
                                            <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                                <li>Cosa produrre (costruzione)</li>
                                                <li>Quanti pezzi realizzare</li>
                                                <li>Quando (date inizio/fine)</li>
                                                <li>Eventuale riferimento commerciale (preventivo/ordine)</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-foreground mb-1">Cosa traccia:</p>
                                            <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                                <li>Materiali consumati</li>
                                                <li>Quantità prelevate per lotto</li>
                                                <li>Stato avanzamento</li>
                                                <li>Certificazione FITOK risultante</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="mt-3 p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded">
                                        <p class="text-sm text-emerald-900 dark:text-emerald-100">
                                            <strong>Esempio:</strong> "Produzione di 50 porte in legno modello 'Classica', usando 120mq di pannello dal lotto MAT-001 e 80mq dal lotto MAT-005"
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                <p class="text-sm text-green-900 dark:text-green-100">
                                    <strong>Relazione:</strong> I Lotti Produzione CONSUMANO i Lotti Materiale.
                                    Gli scarichi automatici vengono eseguiti al completamento del lotto, dopo i controlli di disponibilità.
                                </p>
                            </div>
                        </div>

                        <!-- Certificazione FITOK -->
                        <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-950/20 dark:to-orange-950/20 rounded-lg p-6 mb-6 border border-red-200 dark:border-red-800">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                Certificazione FITOK (Regola del Tutto-o-Niente)
                            </h3>

                            <div class="space-y-4">
                                <p class="text-foreground">
                                    FITOK è una certificazione di qualità che garantisce che i materiali utilizzati rispettino determinati standard.
                                    Nel sistema demo, la certificazione di un lotto produttivo segue una regola rigorosa.
                                </p>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-5 border-2 border-red-500 dark:border-red-600">
                                    <h4 class="font-bold text-red-600 dark:text-red-400 text-lg mb-3 flex items-center gap-2">
                                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        Regola Fondamentale
                                    </h4>
                                    <p class="text-foreground text-lg font-semibold mb-2">
                                        Un lotto produttivo è certificato FITOK SE E SOLO SE:
                                    </p>
                                    <div class="bg-red-50 dark:bg-red-950/40 rounded p-4 border-l-4 border-red-500">
                                        <p class="text-foreground font-bold">
                                            TUTTI i materiali utilizzati hanno certificazione FITOK
                                        </p>
                                    </div>
                                    <p class="text-muted-foreground mt-3 text-sm">
                                        È sufficiente che un solo materiale non sia certificato per invalidare l'intera certificazione del lotto.
                                    </p>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4">
                                    <div class="bg-green-50 dark:bg-green-950/30 rounded-lg p-4 border border-green-300 dark:border-green-700">
                                        <h5 class="font-semibold text-green-800 dark:text-green-300 mb-3 flex items-center gap-2">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Esempio CERTIFICATO
                                        </h5>
                                        <p class="text-sm text-foreground mb-2">Lotto Produzione "LP-2025-001":</p>
                                        <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                            <li>Materiale A - Certificato: F-001 ✓</li>
                                            <li>Materiale B - Certificato: F-002 ✓</li>
                                            <li>Materiale C - Certificato: F-003 ✓</li>
                                        </ul>
                                        <p class="mt-3 text-sm font-bold text-green-800 dark:text-green-300">
                                            → Lotto CERTIFICATO FITOK ✓
                                        </p>
                                    </div>

                                    <div class="bg-red-50 dark:bg-red-950/30 rounded-lg p-4 border border-red-300 dark:border-red-700">
                                        <h5 class="font-semibold text-red-800 dark:text-red-300 mb-3 flex items-center gap-2">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Esempio NON CERTIFICATO
                                        </h5>
                                        <p class="text-sm text-foreground mb-2">Lotto Produzione "LP-2025-002":</p>
                                        <ul class="text-sm text-muted-foreground space-y-1 ml-4">
                                            <li>Materiale A - Certificato: F-001 ✓</li>
                                            <li>Materiale B - Certificato: F-002 ✓</li>
                                            <li>Materiale C - NON certificato ✗</li>
                                        </ul>
                                        <p class="mt-3 text-sm font-bold text-red-800 dark:text-red-300">
                                            → Lotto NON CERTIFICATO ✗
                                        </p>
                                    </div>
                                </div>

                                <div class="bg-amber-50 dark:bg-amber-950/30 rounded-lg p-4 border border-amber-300 dark:border-amber-700">
                                    <h5 class="font-semibold text-amber-900 dark:text-amber-200 mb-2 flex items-center gap-2">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                        </svg>
                                        Implicazioni Pratiche
                                    </h5>
                                    <ul class="text-sm text-amber-900 dark:text-amber-200 space-y-2">
                                        <li><strong>1.</strong> Prima di avviare un lotto produttivo per un cliente che richiede certificazione, verifica che tutti i materiali siano certificati</li>
                                        <li><strong>2.</strong> Tieni sempre una scorta di materiali certificati per le produzioni critiche</li>
                                        <li><strong>3.</strong> Il Registro FITOK mostra i movimenti dei materiali soggetti FITOK nel periodo selezionato</li>
                                        <li><strong>4.</strong> Il sistema calcola automaticamente la certificazione: non puoi impostarla manualmente</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Stati Lotti Produzione -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950/20 dark:to-pink-950/20 rounded-lg p-6 mb-6 border border-purple-200 dark:border-purple-800">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Stati dei Lotti Produzione
                            </h3>

                            <p class="text-foreground mb-4">
                                I lotti produttivi usano cinque stati: BOZZA, CONFERMATO, IN LAVORAZIONE, COMPLETATO, ANNULLATO.
                                Qui sotto trovi i passaggi operativi principali.
                            </p>

                            <div class="space-y-4">
                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border-l-4 border-gray-400">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-full text-sm font-semibold">BOZZA</span>
                                        <span class="text-sm text-muted-foreground">Stato iniziale</span>
                                    </div>
                                    <p class="text-sm text-foreground mb-3">
                                        <strong>Significato:</strong> Lotto in pianificazione, non ancora avviato. I materiali sono identificati ma non ancora prelevati dal magazzino.
                                    </p>
                                    <div class="grid md:grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <p class="font-medium text-foreground mb-1">Cosa puoi fare:</p>
                                            <ul class="text-muted-foreground space-y-1 ml-4">
                                                <li>Modificare tutte le informazioni</li>
                                                <li>Aggiornare parametri e ricalcolare l'ottimizzazione</li>
                                                <li>Cambiare quantità</li>
                                                <li>Eliminare il lotto</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground mb-1">Effetti:</p>
                                            <ul class="text-muted-foreground space-y-1 ml-4">
                                                <li>Nessun impatto su magazzino</li>
                                                <li>Materiali ancora disponibili</li>
                                                <li>Nessuna movimentazione automatica registrata</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-cyan-50 dark:bg-cyan-950/30 rounded-lg p-4 border border-cyan-300 dark:border-cyan-700 text-sm">
                                    <p class="text-cyan-900 dark:text-cyan-100 mb-2">
                                        <strong>Stati aggiuntivi:</strong> oltre ai passaggi operativi esistono anche:
                                    </p>
                                    <ul class="text-cyan-900 dark:text-cyan-100 space-y-1 ml-4">
                                        <li><strong>CONFERMATO:</strong> stato intermedio prima dell'avvio (usabile dalla tabella lotti)</li>
                                        <li><strong>ANNULLATO:</strong> chiusura del lotto senza completamento</li>
                                    </ul>
                                </div>

                                <div class="flex items-center justify-center">
                                    <svg class="w-6 h-6 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                                    </svg>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border-l-4 border-blue-500">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-semibold">IN LAVORAZIONE</span>
                                        <span class="text-sm text-muted-foreground">Produzione attiva</span>
                                    </div>
                                    <p class="text-sm text-foreground mb-3">
                                        <strong>Significato:</strong> Produzione in corso. Il lotto è attivo e può essere portato a completamento.
                                    </p>
                                    <div class="grid md:grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <p class="font-medium text-foreground mb-1">Cosa puoi fare:</p>
                                            <ul class="text-muted-foreground space-y-1 ml-4">
                                                <li>Aggiornare date effettive</li>
                                                <li>Aggiungere note di lavorazione</li>
                                                <li>Registrare avanzamento</li>
                                                <li>Completare il lotto</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground mb-1">Nota operativa:</p>
                                            <ul class="text-muted-foreground space-y-1 ml-4">
                                                <li>Prima del completamento viene controllata la disponibilità di magazzino</li>
                                                <li>Se la disponibilità non basta, il completamento viene bloccato</li>
                                                <li>Gli scarichi vengono registrati in modo automatico solo in fase di completamento</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-950/30 rounded">
                                        <p class="text-sm text-blue-900 dark:text-blue-100">
                                            <strong>Effetto magazzino:</strong> Nessuno scarico immediato in questo stato; i movimenti vengono creati al completamento del lotto.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-center">
                                    <svg class="w-6 h-6 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                                    </svg>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border-l-4 border-green-500">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm font-semibold">COMPLETATO</span>
                                        <span class="text-sm text-muted-foreground">Produzione terminata</span>
                                    </div>
                                    <p class="text-sm text-foreground mb-3">
                                        <strong>Significato:</strong> Lotto concluso, prodotti finiti e pronti. Dati immutabili per tracciabilità storica.
                                    </p>
                                    <div class="grid md:grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <p class="font-medium text-foreground mb-1">Cosa puoi fare:</p>
                                            <ul class="text-muted-foreground space-y-1 ml-4">
                                                <li>Visualizzare tutti i dati</li>
                                                <li>Stampare report</li>
                                                <li>Consultare per tracciabilità</li>
                                                <li>Consultare i movimenti nel Registro FITOK</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground mb-1">Vincoli:</p>
                                            <ul class="text-red-600 dark:text-red-400 space-y-1 ml-4">
                                                <li>NESSUNA modifica possibile</li>
                                                <li>Dati congelati</li>
                                                <li>Solo consultazione</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="mt-3 p-3 bg-green-50 dark:bg-green-950/30 rounded">
                                        <p class="text-sm text-green-900 dark:text-green-100">
                                            <strong>Tracciabilità garantita:</strong> Tutti i dati (materiali, quantità, certificati) sono immutabili per garantire tracciabilità nel tempo.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 p-4 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                <p class="text-sm text-purple-900 dark:text-purple-100">
                                    <strong>Workflow ottimale:</strong> BOZZA → (opzionale CONFERMATO) → IN LAVORAZIONE → COMPLETATO.
                                    In alternativa puoi annullare un lotto non completato passando ad ANNULLATO.
                                </p>
                            </div>
                        </div>

                        <!-- Ottimizzatore di Taglio -->
                        <div class="bg-gradient-to-r from-cyan-50 to-sky-50 dark:from-cyan-950/20 dark:to-sky-950/20 rounded-lg p-6 mb-6 border border-cyan-200 dark:border-cyan-800">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Ottimizzatore di Taglio
                            </h3>

                            <p class="text-foreground mb-4">
                                Funzionalità di calcolo che ottimizza il piano di taglio del materiale selezionato nel lotto.
                            </p>

                            <div class="space-y-4">
                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-cyan-200 dark:border-cyan-800">
                                    <h4 class="font-semibold text-foreground mb-3">Come Funziona:</h4>
                                    <ol class="space-y-2 text-foreground">
                                        <li class="flex gap-2">
                                            <span class="font-bold text-cyan-600 dark:text-cyan-400">1.</span>
                                            <span>Selezioni costruzione, dimensioni del lotto, quantità pezzi e materiale (asse)</span>
                                        </li>
                                        <li class="flex gap-2">
                                            <span class="font-bold text-cyan-600 dark:text-cyan-400">2.</span>
                                            <span>Il sistema calcola le lunghezze dei componenti dalle formule della costruzione</span>
                                        </li>
                                        <li class="flex gap-2">
                                            <span class="font-bold text-cyan-600 dark:text-cyan-400">3.</span>
                                            <span>Esegue il bin packing sulla lunghezza asse e stima assi necessarie, scarto e costi</span>
                                        </li>
                                        <li class="flex gap-2">
                                            <span class="font-bold text-cyan-600 dark:text-cyan-400">4.</span>
                                            <span>Se il risultato ti va bene, salvi il piano di taglio sul lotto</span>
                                        </li>
                                    </ol>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-cyan-200 dark:border-cyan-800">
                                    <h4 class="font-semibold text-foreground mb-3">Criteri di Ottimizzazione:</h4>
                                    <div class="space-y-3">
                                        <div>
                                            <p class="font-medium text-foreground flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                                                Minimizzazione Assi
                                            </p>
                                            <p class="text-sm text-muted-foreground ml-5">
                                                Distribuisce i tagli per ridurre il numero totale di assi necessarie
                                            </p>
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                                                Gestione Kerf
                                            </p>
                                            <p class="text-sm text-muted-foreground ml-5">
                                                Considera lo spessore di taglio per una stima realistica dello sfrido
                                            </p>
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                                                Scarto Totale
                                            </p>
                                            <p class="text-sm text-muted-foreground ml-5">
                                                Calcola scarto in mm e percentuale globale del piano
                                            </p>
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                                                Stima Economica
                                            </p>
                                            <p class="text-sm text-muted-foreground ml-5">
                                                Stima costo e prezzo in base all'unità di misura del materiale
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-cyan-50 dark:bg-cyan-950/30 rounded-lg p-4 border border-cyan-300 dark:border-cyan-700">
                                    <h4 class="font-semibold text-cyan-900 dark:text-cyan-200 mb-2">Esempio Pratico:</h4>
                                    <div class="text-sm text-cyan-900 dark:text-cyan-100 space-y-2">
                                        <p><strong>Esigenza:</strong> Produrre componenti su asse da 4000mm</p>
                                        <p><strong>Dati input:</strong></p>
                                        <ul class="ml-6 space-y-1">
                                            <li>Costruzione: pannelli con formule lunghezza/quantità</li>
                                            <li>Dimensioni lotto: L/P/H impostate in form</li>
                                            <li>Materiale: asse legno da 4000mm</li>
                                        </ul>
                                        <p><strong>Output sistema:</strong></p>
                                        <ul class="ml-6 space-y-1">
                                            <li>Numero assi necessarie</li>
                                            <li>Scarto totale e scarto percentuale</li>
                                            <li>Costo e prezzo stimati</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scarti Riutilizzabili -->
                        <div class="bg-gradient-to-r from-lime-50 to-green-50 dark:from-lime-950/20 dark:to-green-950/20 rounded-lg p-6 mb-6 border border-lime-200 dark:border-lime-800">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-lime-600 dark:text-lime-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Scarti Riutilizzabili
                            </h3>

                            <p class="text-foreground mb-4">
                                Sistema di gestione intelligente degli scarti per ridurre sprechi e ottimizzare l'uso delle risorse.
                            </p>

                            <div class="space-y-4">
                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-lime-200 dark:border-lime-800">
                                    <h4 class="font-semibold text-foreground mb-3">Cosa Sono:</h4>
                                    <p class="text-foreground mb-3">
                                        Porzioni di materiale che avanzano da una lavorazione ma sono ancora in buone condizioni
                                        e sufficientemente grandi da essere utilizzate per altre produzioni.
                                    </p>
                                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                                        <div class="p-3 bg-green-50 dark:bg-green-950/30 rounded">
                                            <p class="font-medium text-green-800 dark:text-green-300 mb-2">Esempi di scarti RIUTILIZZABILI:</p>
                                            <ul class="text-green-700 dark:text-green-400 space-y-1 ml-4">
                                                <li>Pannello 1000x800mm da taglio più grande</li>
                                                <li>Tubo 500mm rimasto da barra 3m</li>
                                                <li>Lamiera 600x400mm da lavorazione</li>
                                            </ul>
                                        </div>
                                        <div class="p-3 bg-red-50 dark:bg-red-950/30 rounded">
                                            <p class="font-medium text-red-800 dark:text-red-300 mb-2">Esempi di scarti NON riutilizzabili:</p>
                                            <ul class="text-red-700 dark:text-red-400 space-y-1 ml-4">
                                                <li>Trucioli e segatura</li>
                                                <li>Pezzi danneggiati o difettosi</li>
                                                <li>Ritagli troppo piccoli</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-lime-200 dark:border-lime-800">
                                    <h4 class="font-semibold text-foreground mb-3">Come Gestirli:</h4>
                                    <ol class="space-y-3 text-foreground">
                                        <li class="flex gap-2">
                                            <span class="font-bold text-lime-600 dark:text-lime-400 flex-shrink-0">1.</span>
                                            <div>
                                                <strong>Durante il completamento lotto:</strong>
                                                <p class="text-sm text-muted-foreground mt-1">
                                                    Il sistema calcola lo scarto dai materiali del piano di taglio salvato
                                                </p>
                                            </div>
                                        </li>
                                        <li class="flex gap-2">
                                            <span class="font-bold text-lime-600 dark:text-lime-400 flex-shrink-0">2.</span>
                                            <div>
                                                <strong>Classificazione automatica:</strong>
                                                <p class="text-sm text-muted-foreground mt-1">
                                                    Gli scarti con lunghezza residua almeno 500mm vengono marcati come riutilizzabili
                                                </p>
                                            </div>
                                        </li>
                                        <li class="flex gap-2">
                                            <span class="font-bold text-lime-600 dark:text-lime-400 flex-shrink-0">3.</span>
                                            <div>
                                                <strong>Tracciabilità scarto:</strong>
                                                <p class="text-sm text-muted-foreground mt-1">
                                                    Il sistema registra lo scarto collegandolo al lotto produzione e al lotto materiale di origine
                                                </p>
                                                <ul class="text-sm text-muted-foreground mt-1 ml-4 space-y-1">
                                                    <li>Lunghezza/larghezza/spessore residui</li>
                                                    <li>Volume residuo</li>
                                                    <li>Indicatore riutilizzabile/riutilizzato</li>
                                                    <li>Nota con riferimento al lotto produzione</li>
                                                </ul>
                                            </div>
                                        </li>
                                        <li class="flex gap-2">
                                            <span class="font-bold text-lime-600 dark:text-lime-400 flex-shrink-0">4.</span>
                                            <div>
                                                <strong>Consultazione in magazzino:</strong>
                                                <p class="text-sm text-muted-foreground mt-1">
                                                    La vista aggregata espone il volume di scarti riutilizzabili disponibili per prodotto
                                                </p>
                                            </div>
                                        </li>
                                    </ol>
                                </div>

                                <div class="bg-lime-50 dark:bg-lime-950/30 rounded-lg p-4 border border-lime-300 dark:border-lime-700">
                                    <h4 class="font-semibold text-lime-900 dark:text-lime-200 mb-2 flex items-center gap-2">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                        </svg>
                                        Vantaggi
                                    </h4>
                                    <ul class="text-sm text-lime-900 dark:text-lime-100 space-y-2">
                                        <li><strong>Economico:</strong> Riduce costi evitando sprechi di materiale costoso</li>
                                        <li><strong>Ecologico:</strong> Minimizza rifiuti e impatto ambientale</li>
                                        <li><strong>Tracciabile:</strong> Mantiene piena tracciabilità e certificazioni FITOK</li>
                                        <li><strong>Operativo:</strong> Gli scarti disponibili sono visibili a colpo d'occhio nella vista aggregata</li>
                                    </ul>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-lime-200 dark:border-lime-800">
                                    <h4 class="font-semibold text-foreground mb-3">Vista Dedicata:</h4>
                                    <p class="text-foreground text-sm mb-2">
                                        Nel menu Magazzino (Vista Aggregata) puoi filtrare i prodotti con scarti disponibili:
                                    </p>
                                    <ul class="text-sm text-muted-foreground space-y-1 ml-6">
                                        <li>Filtro "Con scarti disponibili / Senza scarti"</li>
                                        <li>Volume scarti disponibile per prodotto</li>
                                        <li>Integrazione con dettaglio giacenze e lotti attivi</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- BOM -->
                        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-950/20 dark:to-yellow-950/20 rounded-lg p-6 mb-6 border border-amber-200 dark:border-amber-800">
                            <h3 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                BOM (Bill of Materials - Distinte Base)
                            </h3>

                            <p class="text-foreground mb-4">
                                Le Distinte Base sono template che descrivono materiali e quantità standard per un prodotto finito.
                                Nel flusso corrente fungono da riferimento, mentre il calcolo operativo del lotto avviene con Costruzioni + Ottimizzazione.
                            </p>

                            <div class="p-4 bg-white dark:bg-slate-900 rounded-lg border border-amber-200 dark:border-amber-800">
                                <h4 class="font-semibold text-foreground mb-2">Costruzioni + Ottimizzazione</h4>
                                <ul class="text-sm text-muted-foreground space-y-2 ml-6">
                                    <li>Le costruzioni geometriche usano un solo materiale asse e i componenti definiti nella costruzione.</li>
                                    <li>Le routine cassa Excel supportate possono richiedere profili materiali distinti, ad esempio <strong>base</strong> e <strong>fondo</strong>.</li>
                                    <li><strong>excel_strict</strong> replica la logica legacy per confronto; <strong>physical</strong> usa le stesse regole come riferimento ma applica vincoli fisici più rigorosi sulle assi reali.</li>
                                </ul>
                            </div>

                            <div class="space-y-4">
                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                                    <h4 class="font-semibold text-foreground mb-3">Concetto Fondamentale:</h4>
                                    <p class="text-foreground mb-3">
                                        Immagina una BOM come una ricetta di cucina: per fare una torta serve sempre la stessa
                                        quantità di farina, uova, zucchero. Allo stesso modo, per produrre una "Porta modello Classica"
                                        servono sempre gli stessi materiali nelle stesse quantità.
                                    </p>
                                    <div class="p-4 bg-amber-50 dark:bg-amber-950/30 rounded border-l-4 border-amber-500">
                                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-2">Esempio Pratico:</p>
                                        <p class="text-sm text-amber-800 dark:text-amber-300 mb-2">BOM: "Porta Classica 80x210cm"</p>
                                        <ul class="text-sm text-amber-900 dark:text-amber-200 space-y-1 ml-4">
                                            <li>Pannello compensato 18mm: 3.5 mq</li>
                                            <li>Listelli pino: 8 metri lineari</li>
                                            <li>Cerniere acciaio: 3 pezzi</li>
                                            <li>Vernice trasparente: 0.5 litri</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                                    <h4 class="font-semibold text-foreground mb-3">Quando Usare le BOM:</h4>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex gap-3">
                                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div>
                                                <p class="font-medium text-foreground">Prodotti Standard:</p>
                                                <p class="text-muted-foreground">Hai prodotti che produci regolarmente sempre allo stesso modo</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-3">
                                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div>
                                                <p class="font-medium text-foreground">Standardizzare Specifiche:</p>
                                                <p class="text-muted-foreground">Vuoi avere una base comune per ufficio tecnico, preventivi e produzione</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-3">
                                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div>
                                                <p class="font-medium text-foreground">Calcolare Costi:</p>
                                                <p class="text-muted-foreground">Vuoi sapere quanto costa produrre un'unità di prodotto</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-3">
                                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div>
                                                <p class="font-medium text-foreground">Pianificare Acquisti:</p>
                                                <p class="text-muted-foreground">Vuoi sapere quanti materiali ordinare per produrre N unità</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                                    <h4 class="font-semibold text-foreground mb-3">Workflow con BOM:</h4>
                                    <div class="space-y-3">
                                        <div class="flex gap-3">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex-shrink-0">1</div>
                                            <div class="flex-1">
                                                <p class="font-medium text-foreground">Setup Iniziale (una tantum)</p>
                                                <p class="text-sm text-muted-foreground mt-1">
                                                    Vai su Distinte Base → Crea una BOM per ogni prodotto standard → Definisci materiali e quantità standard
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex gap-3">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex-shrink-0">2</div>
                                            <div class="flex-1">
                                                <p class="font-medium text-foreground">Riferimento Tecnico</p>
                                                <p class="text-sm text-muted-foreground mt-1">
                                                    In fase di pianificazione confronta la BOM con il caso reale per verificare materiali e fabbisogni
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex gap-3">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex-shrink-0">3</div>
                                            <div class="flex-1">
                                                <p class="font-medium text-foreground">Calcolo Operativo Lotto</p>
                                                <p class="text-sm text-muted-foreground mt-1">
                                                    Crea il lotto con costruzione, dimensioni e materiale, poi esegui Calcola Ottimizzazione
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex gap-3">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex-shrink-0">4</div>
                                            <div class="flex-1">
                                                <p class="font-medium text-foreground">Verifica e Allineamento</p>
                                                <p class="text-sm text-muted-foreground mt-1">
                                                    Usa la BOM come benchmark e adatta il piano di taglio al lotto specifico
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-amber-50 dark:bg-amber-950/30 rounded-lg p-4 border border-amber-300 dark:border-amber-700">
                                    <h4 class="font-semibold text-amber-900 dark:text-amber-200 mb-2">Differenza Chiave: BOM vs Lotto Materiale</h4>
                                    <div class="grid md:grid-cols-2 gap-3 text-sm">
                                        <div class="p-3 bg-white dark:bg-amber-900/20 rounded">
                                            <p class="font-medium text-amber-900 dark:text-amber-200 mb-1">BOM (Distinta Base):</p>
                                            <ul class="text-amber-800 dark:text-amber-300 space-y-1 ml-4">
                                                <li>Ricetta astratta</li>
                                                <li>Dice "cosa" e "quanto"</li>
                                                <li>Non specifica lotti fisici</li>
                                                <li>Sempre uguale per quel prodotto</li>
                                            </ul>
                                        </div>
                                        <div class="p-3 bg-white dark:bg-amber-900/20 rounded">
                                            <p class="font-medium text-amber-900 dark:text-amber-200 mb-1">Lotto Materiale:</p>
                                            <ul class="text-amber-800 dark:text-amber-300 space-y-1 ml-4">
                                                <li>Materiale fisico in magazzino</li>
                                                <li>Ha dimensioni e quantità reali</li>
                                                <li>Specifico con numero lotto</li>
                                                <li>Cambia ogni acquisto</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-sm text-amber-900 dark:text-amber-100">
                                        <strong>In pratica:</strong> La BOM ti dice che per fare una porta servono 3.5 mq di pannello.
                                        Il lotto operativo calcola poi come tagliare il materiale reale in base a dimensioni e quantità effettive.
                                    </p>
                                </div>

                                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                                    <h4 class="font-semibold text-foreground mb-2">Vantaggi nell'usare BOM:</h4>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            <span><strong>Risparmio tempo:</strong> Inserimento dati 10 volte più veloce</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            <span><strong>Meno errori:</strong> Non rischi di dimenticare materiali</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            <span><strong>Consistenza:</strong> Definisci uno standard condiviso tra più reparti</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            <span><strong>Analisi costi:</strong> Calcolo immediato del costo standard di produzione</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            <span><strong>Pianificazione:</strong> Supporta stime preliminari di fabbisogno materiale</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tipi Costruzione -->
                <div x-show="activeTab === 'tipi-costruzione'" x-transition>
                    @php
                        $constructionGuide = [
                            [
                                'name' => 'Bancale Standard 2 Vie',
                                'badge' => 'Bancale',
                                'badgeColor' => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950/30 dark:text-amber-200 dark:border-amber-800',
                                'summary' => 'Pallet standard con accesso a due vie, adatto a basi semplici e ripetibili.',
                                'use' => 'Quando serve un basamento standard senza pareti e con struttura essenziale.',
                                'lotto' => 'Nel lotto richiede lunghezza e larghezza; il materiale di taglio resta singolo.',
                                'calc' => 'Usa l optimizer categoria bancale. La logica Excel dipende dal mode bancale in Settings Produzione.',
                                'note' => 'L altezza non e richiesta come dato lotto: la struttura deriva da morali e doghe previste dal template.',
                            ],
                            [
                                'name' => 'Cassa 2 Vie (Con Legacci)',
                                'badge' => 'Cassa geometrica',
                                'badgeColor' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950/30 dark:text-blue-200 dark:border-blue-800',
                                'summary' => 'Cassa rialzata con legacci basamento per consentire inforcamento e movimentazione.',
                                'use' => 'Quando serve una cassa chiusa ma con appoggio inferiore strutturato e passaggio forche.',
                                'lotto' => 'Nel lotto compare il classico campo singolo Materiale (Asse).',
                                'calc' => 'Segue il flusso geometrico cassa, quindi componenti e pannelli vengono derivati dal template.',
                                'note' => 'I legacci sono parte integrante della costruzione: non vanno aggiunti come componente manuale separato.',
                            ],
                            [
                                'name' => 'Cassa SP25',
                                'badge' => 'Cassa Excel',
                                'badgeColor' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-200 dark:border-emerald-800',
                                'summary' => 'Cassa con routine storica SP25, pensata per allineamento ai workbook legacy.',
                                'use' => 'Quando il riferimento commerciale o tecnico richiama esplicitamente la routine SP25.',
                                'lotto' => 'Nel lotto compare la card Materiali cassa con profili distinti, tipicamente base e fondo.',
                                'calc' => 'La routine cassa viene risolta da config.optimizer_key = excel_sp25; in runtime intervengono excel_strict o physical in base alle impostazioni cassa.',
                                'note' => 'E la scelta corretta quando vuoi confrontare il risultato con i casi Excel SP25 gia documentati.',
                            ],
                            [
                                'name' => 'Cassa SP25 Fondo 40',
                                'badge' => 'Cassa Excel',
                                'badgeColor' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-200 dark:border-emerald-800',
                                'summary' => 'Variante SP25 con fondo dedicato da 40 mm e profili materiali separati.',
                                'use' => 'Quando il fondo deve essere piu robusto o la commessa richiama lo schema SP25 Fondo 40.',
                                'lotto' => 'Nel lotto la card Materiali cassa distingue almeno base e fondo; il fondo va selezionato su materiale compatibile 40 mm.',
                                'calc' => 'La routine viene risolta da config.optimizer_key = excel_sp25_fondo40 e il motore mantiene audit tra logica storica e taglio fisico.',
                                'note' => 'Se scegli materiale fondo non coerente con il profilo richiesto, il piano di taglio puo fallire esplicitamente.',
                            ],
                            [
                                'name' => 'Cassa Standard',
                                'badge' => 'Cassa geometrica',
                                'badgeColor' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950/30 dark:text-blue-200 dark:border-blue-800',
                                'summary' => 'Cassa chiusa standard con fondo, coperchio, pareti lunghe esterne e testate corte interne.',
                                'use' => 'Quando serve una cassa semplice e non stai seguendo una routine Excel specifica.',
                                'lotto' => 'Nel lotto usa un solo materiale asse e calcola i pannelli dal set di componenti definito.',
                                'calc' => 'Oggi e un template geometrico standard, adatto a casi custom o generici.',
                                'note' => 'Per l utente e molto vicina a Cassa Standard Geometrica; la differenza principale e storica/di naming.',
                            ],
                            [
                                'name' => 'Cassa Standard Geometrica',
                                'badge' => 'Cassa geometrica',
                                'badgeColor' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950/30 dark:text-blue-200 dark:border-blue-800',
                                'summary' => 'Alias esplicito della cassa geometrica standard, introdotto per rendere chiaro che non usa routine Excel.',
                                'use' => 'Quando vuoi essere sicuro di lavorare su una cassa standard non legacy e leggibile anche lato rollout tecnico.',
                                'lotto' => 'Stesso flusso operativo della cassa standard: un materiale asse, stessi parametri dimensionali.',
                                'calc' => 'Config optimizer_key = geometrica; il lotto non richiede la card Materiali cassa multi profilo.',
                                'note' => 'Se devi spiegare a un operatore che non stai usando SP25, questo e il nome piu esplicito.',
                            ],
                            [
                                'name' => 'Gabbia Standard',
                                'badge' => 'Gabbia',
                                'badgeColor' => 'bg-violet-100 text-violet-800 border-violet-200 dark:bg-violet-950/30 dark:text-violet-200 dark:border-violet-800',
                                'summary' => 'Imballo aperto con montanti e traverse, meno pannellato di una cassa chiusa.',
                                'use' => 'Quando il contenuto richiede protezione strutturale ma non una chiusura completa a pannelli.',
                                'lotto' => 'Nel lotto usa il materiale asse singolo; i rinforzi restano gestiti come componenti manuali o separati.',
                                'calc' => 'Usa l optimizer categoria gabbia. Il comportamento Excel dipende dal mode gabbia preview, compatibility o strict.',
                                'note' => 'E indicata per imballi con struttura portante visibile e maggiore leggerezza rispetto a una cassa chiusa.',
                            ],
                        ];
                    @endphp

                    <div class="prose prose-slate max-w-none">
                        <h2 class="text-2xl font-bold text-foreground mb-6">Tipi Costruzione</h2>
                        <p class="text-muted-foreground mb-8">
                            Legenda operativa dei template costruzione standard presenti nel menu di selezione del lotto.
                            Questa guida descrive i template seedati: eventuali costruzioni custom create in azienda possono avere regole diverse.
                        </p>

                        <div class="grid gap-6 lg:grid-cols-2">
                            @foreach($constructionGuide as $construction)
                                <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
                                    <div class="p-5 border-b border-border bg-muted/30">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h3 class="text-xl font-semibold text-foreground mb-2">{{ $construction['name'] }}</h3>
                                                <p class="text-sm text-muted-foreground">{{ $construction['summary'] }}</p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium {{ $construction['badgeColor'] }}">
                                                {{ $construction['badge'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="p-5 space-y-4">
                                        <div>
                                            <h4 class="text-sm font-semibold uppercase tracking-wide text-foreground mb-1">Quando usarla</h4>
                                            <p class="text-sm text-muted-foreground">{{ $construction['use'] }}</p>
                                        </div>

                                        <div>
                                            <h4 class="text-sm font-semibold uppercase tracking-wide text-foreground mb-1">Nel lotto</h4>
                                            <p class="text-sm text-muted-foreground">{{ $construction['lotto'] }}</p>
                                        </div>

                                        <div>
                                            <h4 class="text-sm font-semibold uppercase tracking-wide text-foreground mb-1">Calcolo</h4>
                                            <p class="text-sm text-muted-foreground">{{ $construction['calc'] }}</p>
                                        </div>

                                        <div class="rounded-lg bg-muted/40 p-4 border border-border">
                                            <h4 class="text-sm font-semibold uppercase tracking-wide text-foreground mb-1">Nota pratica</h4>
                                            <p class="text-sm text-foreground">{{ $construction['note'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8 rounded-xl border border-sky-200 bg-sky-50/70 p-5 dark:border-sky-900 dark:bg-sky-950/20">
                            <h3 class="text-lg font-semibold text-foreground mb-2">Regola veloce per scegliere il template giusto</h3>
                            <ul class="space-y-2 text-sm text-muted-foreground ml-6">
                                <li><strong>Cassa standard / geometrica:</strong> per casse semplici senza riferimento Excel specifico.</li>
                                <li><strong>Cassa SP25 / SP25 Fondo 40:</strong> per casse da allineare a logiche storiche Excel e con materiali cassa distinti.</li>
                                <li><strong>Cassa 2 Vie:</strong> per casse con legacci inferiori e movimentazione a forche.</li>
                                <li><strong>Gabbia standard:</strong> per strutture aperte, portanti, meno pannellate.</li>
                                <li><strong>Bancale standard 2 vie:</strong> per basi/pallet senza pareti.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Styles -->
        <style>
            @media print {
                .no-print {
                    display: none !important;
                }

                .print-break-inside-avoid {
                    break-inside: avoid;
                }

                body {
                    background: white;
                }

                .prose {
                    max-width: none;
                }
            }
        </style>
    </div>
</x-layouts.app>
