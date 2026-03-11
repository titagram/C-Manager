<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Soglia Riutilizzabilità Scarti (mm)
    |--------------------------------------------------------------------------
    |
    | Lunghezza minima (in millimetri) oltre la quale uno scarto viene marcato
    | come riutilizzabile nel magazzino scarti.
    |
    */
    'scrap_reusable_min_length_mm' => (int) env('SCRAP_REUSABLE_MIN_LENGTH_MM', 500),

    /*
    |--------------------------------------------------------------------------
    | Kerf di taglio (mm)
    |--------------------------------------------------------------------------
    |
    | Spessore lama utilizzato dagli optimizer di taglio.
    | Valore di fallback config/env quando non presente override nel pannello
    | admin (tabella production_settings).
    |
    */
    'cutting_kerf_mm' => (float) env('PRODUCTION_CUTTING_KERF_MM', 0),

    /*
    |--------------------------------------------------------------------------
    | Cooldown calcolo materiali lotto (secondi)
    |--------------------------------------------------------------------------
    |
    | Intervallo minimo tra due invocazioni consecutive di `calcolaMateriali`
    | per lo stesso utente/sessione. Riduce click ripetuti e carico inutile.
    |
    */
    'material_calculation_cooldown_seconds' => (int) env(
        'PRODUCTION_MATERIAL_CALCULATION_COOLDOWN_SECONDS',
        2
    ),

    /*
    |--------------------------------------------------------------------------
    | Cassa Optimizer Mode
    |--------------------------------------------------------------------------
    |
    | - physical: usa l'optimizer cassa fisico con profili materiali reali
    | - excel_strict: riproduce le righe legacy Excel supportate
    | - legacy: forza fallback al bin packing legacy 1D
    |
    | Compatibilita retroattiva:
    | - `category` viene letto come alias di `physical`.
    |
    */
    'cassa_optimizer_mode' => env('PRODUCTION_CASSA_OPTIMIZER_MODE', 'physical'),

    /*
    |--------------------------------------------------------------------------
    | Cassa Shadow Compare (Category vs Legacy)
    |--------------------------------------------------------------------------
    |
    | Confronto controllato tra optimizer categoria cassa e fallback legacy
    | 1D. Quando attivo, il confronto gira in shadow e logga solo differenze
    | significative secondo le soglie configurate.
    |
    */
    'cassa_shadow_compare_enabled' => filter_var(
        env('PRODUCTION_CASSA_SHADOW_COMPARE_ENABLED', false),
        FILTER_VALIDATE_BOOL
    ),
    'cassa_shadow_compare_volume_delta_mc' => (float) env(
        'PRODUCTION_CASSA_SHADOW_COMPARE_VOLUME_DELTA_MC',
        0.0005
    ),
    'cassa_shadow_compare_waste_delta_percent' => (float) env(
        'PRODUCTION_CASSA_SHADOW_COMPARE_WASTE_DELTA_PERCENT',
        0.5
    ),

    /*
    |--------------------------------------------------------------------------
    | Gabbia Excel Mode
    |--------------------------------------------------------------------------
    |
    | - preview: calcola solo anteprima regole Excel nel trace, ma usa il
    |   flusso rettangolare v1 per il taglio effettivo
    | - compatibility: usa i pezzi normalizzati dal builder Excel per il taglio
    |   della categoria gabbia (fase intermedia verso optimizer v2 completo)
    | - strict: per routine gabbia supportate forza i pezzi Excel; se i dati
    |   necessari mancano, genera errore esplicito (niente fallback silenzioso)
    |
    */
    'gabbia_excel_mode' => env('PRODUCTION_GABBIA_EXCEL_MODE', 'preview'),

    /*
    |--------------------------------------------------------------------------
    | Bancale Excel Mode
    |--------------------------------------------------------------------------
    |
    | - preview: calcola solo anteprima regole Excel nel trace, ma usa il
    |   flusso rettangolare v1 per il taglio effettivo
    | - compatibility: usa i pezzi normalizzati dal builder Excel per il taglio
    |   della categoria bancale (fase intermedia verso optimizer v2 completo)
    | - strict: per routine bancale supportate forza i pezzi Excel; se i dati
    |   necessari mancano, genera errore esplicito (niente fallback silenzioso)
    |
    */
    'bancale_excel_mode' => env('PRODUCTION_BANCALE_EXCEL_MODE', 'preview'),

    /*
    |--------------------------------------------------------------------------
    | Legaccio Excel Mode
    |--------------------------------------------------------------------------
    |
    | - preview: calcola solo anteprima regole Excel nel trace, ma usa il
    |   flusso rettangolare v1 per il taglio effettivo
    | - compatibility: usa i pezzi normalizzati dal builder Excel per il taglio
    |   della categoria legaccio (fase intermedia verso optimizer v2 completo)
    | - strict: per routine legaccio supportate forza i pezzi Excel; se i dati
    |   necessari mancano, genera errore esplicito (niente fallback silenzioso)
    |
    */
    'legaccio_excel_mode' => env('PRODUCTION_LEGACCIO_EXCEL_MODE', 'preview'),

    /*
    |--------------------------------------------------------------------------
    | Component Authoring Guard
    |--------------------------------------------------------------------------
    |
    | Guardrail authoring nel manager componenti costruzione:
    | - limita i nomi CALCOLATO ai componenti strutturali previsti per categoria
    | - impedisce di marcare CALCOLATO componenti chiaramente manuali (es ferramenta)
    |
    */
    'component_authoring_guard_enabled' => filter_var(
        env('PRODUCTION_COMPONENT_AUTHORING_GUARD_ENABLED', true),
        FILTER_VALIDATE_BOOL
    ),

    /*
    |--------------------------------------------------------------------------
    | Production Settings (DB + Admin Panel)
    |--------------------------------------------------------------------------
    |
    | - settings_db_enabled: abilita la lettura/scrittura delle impostazioni
    |   persistite in tabella `production_settings`
    | - settings_lock_enabled: abilita lock delle chiavi configurabili da UI
    | - settings_lock_only_production: applica il lock solo in APP_ENV=production
    | - settings_locked_keys: elenco CSV chiavi bloccate dal pannello
    |
    */
    'settings_db_enabled' => filter_var(
        env('PRODUCTION_SETTINGS_DB_ENABLED', true),
        FILTER_VALIDATE_BOOL
    ),
    'settings_lock_enabled' => filter_var(
        env('PRODUCTION_SETTINGS_LOCK_ENABLED', false),
        FILTER_VALIDATE_BOOL
    ),
    'settings_lock_only_production' => filter_var(
        env('PRODUCTION_SETTINGS_LOCK_ONLY_PRODUCTION', true),
        FILTER_VALIDATE_BOOL
    ),
    'settings_locked_keys' => array_values(array_filter(array_map(
        static fn ($key) => trim((string) $key),
        explode(',', (string) env('PRODUCTION_SETTINGS_LOCKED_KEYS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | UI dedicata prezzo m³ prodotto
    |--------------------------------------------------------------------------
    |
    | Campo temporaneamente disattivabile dalla UI mantenendo compatibilita'
    | applicativa lato backend.
    |
    */
    'show_prezzo_mc_input' => filter_var(
        env('PRODUCTION_SHOW_PREZZO_MC_INPUT', true),
        FILTER_VALIDATE_BOOL
    ),
];
