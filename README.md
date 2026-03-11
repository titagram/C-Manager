# C-Manager Demo

Gestionale demo sviluppato in Laravel 12 per la gestione di imballaggi industriali in legno: preventivi, ordini, lotti di produzione, magazzino materiali, tracciabilita FITOK e distinte base.

Licenza: MIT. Vedi il file `LICENSE`.


## Stack

- PHP 8.2+
- Laravel 12
- Livewire 3
- Blade
- PostgreSQL
- Vite + Tailwind CSS
- DomPDF per export PDF
- PhpSpreadsheet per export Excel
- PHPUnit per test automatici

## Cosa fa l'applicazione

L'applicazione copre un flusso operativo end-to-end:

1. gestione anagrafiche clienti, fornitori, materiali e costruzioni
2. creazione di preventivi con righe collegate ai lotti
3. conversione preventivo -> ordine
4. apertura e avanzamento dei lotti di produzione
5. carico e scarico dei lotti materiale in magazzino
6. tracciabilita FITOK dei movimenti
7. generazione BOM e supporto al calcolo materiali
8. reportistica PDF/Excel e strumenti di validazione dati

## Aree principali dell'interfaccia

- `/` Dashboard admin
- `/magazzino` magazzino lotti materiale e movimenti
- `/fitok` registro FITOK con export PDF/Excel
- `/lotti` lotti di produzione
- `/preventivi` preventivi commerciali
- `/ordini` ordini confermati e pronti per la produzione
- `/bom` distinte base
- `/clienti`, `/fornitori`, `/prodotti`, `/costruzioni` anagrafiche
- `/settings/production` impostazioni avanzate produzione
- `/istruzioni` guida operativa interna

## Ruoli demo

I seed creano tre utenti:

- `admin@demo.test` / `password`
- `operatore@demo.test` / `password`
- `tecnico@demo.test` / `password`

L'admin puo gestire tutte le sezioni. Gli operatori hanno accesso limitato ai flussi operativi.

## Setup rapido

### Modalita locale classica

```bash
composer run setup
composer run dev
```

`composer run setup` esegue:

```bash
composer install
cp .env.example .env   # se .env non esiste
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

`composer run dev` avvia in parallelo:

- server Laravel
- queue listener
- log tail (`pail`)
- Vite in watch mode

### Modalita Docker/Sail

Se preferisci Docker:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

## Comandi principali

### Sviluppo

```bash
composer run dev
npm run dev
npm run build
```

### Database

```bash
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
```

Per eseguire solo i seed demo principali:

```bash
php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder
```

### Test

```bash
composer run test
php artisan test
php artisan test --filter NomeTest
```

### Qualita codice

```bash
./vendor/bin/pint
```

## Seeder disponibili

I seed sono organizzati per essere idempotenti: ri-eseguirli non dovrebbe duplicare i dati principali.

### `DatabaseSeeder`

Seeder principale. Esegue in ordine:

1. `UserSeeder`
2. `ProdottiSeeder`
3. `FornitoriSeeder`
4. `CostruzioniSeeder`
5. `DemoDataSeeder`

Contiene anche un helper `freshStart()` commentato, utile come riferimento per reset mirati delle tabelle.

### `UserSeeder`

Crea gli utenti demo con ruoli:

- admin
- operatore
- tecnico

### `ProdottiSeeder`

Popola i materiali e i semilavorati di esempio:

- tavole abete FITOK in varie sezioni
- assi e listelli
- minuteria/ferramenta

Per i prodotti a volume (`mc`) imposta anche il peso specifico standard.

### `FornitoriSeeder`

Inserisce fornitori demo utili per il registro FITOK e per i lotti materiale.

### `CostruzioniSeeder`

Inserisce tipologie costruttive e relative formule/componenti. Include diverse varianti, tra cui:

- casse standard
- casse con profili Excel/legacy
- gabbie
- altre costruzioni specialistiche gestite dagli optimizer

Serve da base per il motore di calcolo materiali e per la costruzione delle BOM.

### `CostruzioneSeeder`

Alias deprecato mantenuto per backward compatibility. Richiama `CostruzioniSeeder`.

### `DemoDataSeeder`

E il seed piu importante per la demo portfolio. Crea un dataset coerente e collegato:

- clienti demo
- lotti materiale FITOK con DDT e movimenti di carico
- un preventivo in bozza con lotto placeholder
- un ordine operativo con lotto completato
- consumi materiale gia registrati
- movimenti di scarico associati al lotto
- scarti riutilizzabili
- una BOM demo collegata all'ordine

Questo seed rende subito navigabile l'applicazione senza dover inserire dati manualmente.

## Flusso demo gia pronto dopo il seeding

Dopo `migrate --seed` trovi un dataset utile per mostrare il progetto:

- clienti demo in anagrafica
- materiali e fornitori pronti
- lotti FITOK gia caricati
- un preventivo `PRV-2026-0001`
- un ordine `ORD-2026-0001`
- due lotti produzione:
  - `LP-2026-0001` come placeholder di preventivo
  - `LP-2026-0002` come lotto operativo completato

Questo permette di mostrare sia la parte commerciale sia quella di produzione e tracciabilita.

## Comandi Artisan custom

Il progetto include diversi comandi utili oltre a quelli standard Laravel.

### `preventivi:expire`

Segna come scaduti i preventivi inviati con `validita_fino` passata.

```bash
php artisan preventivi:expire
```

### `inventory:anomaly-report`

Genera un report sulle anomalie inventariali:

- rettifiche negative
- mismatch tra scarti teorici e registrati
- consumi senza movimento

```bash
php artisan inventory:anomaly-report
php artisan inventory:anomaly-report --days=30 --json=storage/app/reports/inventory_anomalies.json
```

### `production:backfill-lotto-material-volumes`

Ricostruisce `volume_netto_mc` e `volume_scarto_mc` nelle righe materiali dei lotti partendo dall'`optimizer_result`.

```bash
php artisan production:backfill-lotto-material-volumes --dry-run
php artisan production:backfill-lotto-material-volumes --limit=200 --json=storage/app/reports/backfill.json
```

### `production:cassa-rollout-validate`

Confronta optimizer cassa e comportamento legacy su dataset lotti esistenti, producendo delta report.

```bash
php artisan production:cassa-rollout-validate --limit=200 --only-significant
php artisan production:cassa-rollout-validate --json=storage/app/reports/cassa_rollout_validation.json
```

### `production:generate-cassa-dataset`

Genera un dataset sintetico di lotti cassa per validazioni massive o rollout tecnici.

```bash
php artisan production:generate-cassa-dataset --count=30 --only-missing
php artisan production:generate-cassa-dataset --fresh --json=storage/app/reports/cassa_dataset_generation_report.json
```

### `app:debug-reset-db`

Resetta il database e rilancia `migrate:fresh --seed`, ma solo in ambiente debug e solo con conferma esplicita e utente admin.

```bash
php artisan app:debug-reset-db --confirmed --requested-by=1
```

## Impostazioni produzione

La sezione `/settings/production` permette di governare il comportamento di alcuni servizi di calcolo e rollout.

Variabili `.env` rilevanti:

- `PRODUCTION_SETTINGS_DB_ENABLED`
- `PRODUCTION_SETTINGS_LOCK_ENABLED`
- `PRODUCTION_SETTINGS_LOCK_ONLY_PRODUCTION`
- `PRODUCTION_SETTINGS_LOCKED_KEYS`
- `PRODUCTION_MATERIAL_CALCULATION_COOLDOWN_SECONDS`
- `PRODUCTION_CASSA_OPTIMIZER_MODE`
- `PRODUCTION_CASSA_SHADOW_COMPARE_ENABLED`
- `PRODUCTION_CASSA_SHADOW_COMPARE_VOLUME_DELTA_MC`
- `PRODUCTION_CASSA_SHADOW_COMPARE_WASTE_DELTA_PERCENT`
- `PRODUCTION_COMPONENT_AUTHORING_GUARD_ENABLED`

Ordine di priorita dei valori:

1. database (`production_settings`)
2. `config/production.php`
3. default hardcoded nei service

## Test suite gia presenti

La suite e ampia e copre logica dominio, Livewire, regressioni e coerenza dei seed.

### Test Feature

Coprono comportamento applicativo end-to-end:

- autenticazione e permessi
- visibilita sidebar e access control operator/admin
- pagine Blade e controller PDF/Excel
- componenti Livewire per clienti, prodotti, ordini, preventivi, BOM, magazzino, lotti
- flussi di regressione preventivo -> ordine -> produzione
- pagine guida e UX di supporto
- comandi Artisan custom
- seed demo e coerenza FITOK

Esempi utili:

- `tests/Feature/AuthTest.php`: login/logout e protezione accessi
- `tests/Feature/Regression/PreventivoOrdineLifecycleTest.php`: ciclo completo preventivo -> ordine -> lotto
- `tests/Feature/Seeders/DemoDataSeederCoherenceTest.php`: verifica coerenza del dataset demo
- `tests/Feature/Seeders/DemoDataSeederFitokCoverageTest.php`: verifica copertura FITOK e idempotenza del seed demo
- `tests/Feature/Commands/ValidateCassaRolloutCommandTest.php`: controllo del comando di rollout cassa

### Test Unit

Coprono le parti di dominio e infrastruttura piu sensibili:

- enum e policy
- model e relazioni
- migrazioni
- servizi di pricing, inventory, conversione preventivo/ordine
- motore formule
- optimizer di produzione
- servizi di rollout e pianificazione scarti

Esempi utili:

- `tests/Unit/Services/CuttingOptimizerServiceTest.php`: logica di ottimizzazione taglio
- `tests/Unit/Services/CassaOptimizerServiceTest.php`: ottimizzazione casse
- `tests/Unit/Services/ProductionLotServiceTest.php`: regole dominio sui lotti
- `tests/Unit/Services/FormulaEvaluatorServiceTest.php`: parsing e valutazione formule
- `tests/Unit/Services/Production/ExcelLegacyGoldenDatasetTest.php`: confronto con golden dataset legacy

### Come eseguire gruppi utili di test

```bash
php artisan test --filter DemoDataSeeder
php artisan test --filter PreventivoOrdineLifecycleTest
php artisan test --filter ProductionLotServiceTest
php artisan test tests/Feature/Commands
php artisan test tests/Unit/Services/Production
```

## Struttura del progetto

```text
app/                Logica applicativa, model, service, Livewire, comandi
config/             Configurazioni Laravel e moduli custom
database/           Migrazioni, factory, seeders
docs/               Piani, note tecniche e documentazione interna
public/             Web root
resources/          Blade, asset frontend, css/js
routes/             Definizione route web
tests/              Test feature e unit
```

## Note operative

- Il progetto usa dati demo ma con flussi realistici.
- I seed sono pensati per essere ri-eseguibili.
- Alcuni comandi di analisi producono report JSON in `storage/app/reports`.
- La parte piu interessante per portfolio e il legame tra calcolo materiali, lotti, magazzino e tracciabilita FITOK.

## File utili da leggere dopo il README

- `routes/web.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/DemoDataSeeder.php`
- `app/Console/Commands`
- `tests`
