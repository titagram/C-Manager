<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validita certificazione FITOK (giorni)
    |--------------------------------------------------------------------------
    |
    | La validita e' configurabile per tipo trattamento. Se il tipo non e'
    | presente in mappa, viene usato il valore di default.
    |
    */
    'validita_default_giorni' => (int) env('FITOK_VALIDITA_DEFAULT_GIORNI', 365),

    'validita_trattamenti' => [
        'HT' => (int) env('FITOK_VALIDITA_HT_GIORNI', 365),
        'MB' => (int) env('FITOK_VALIDITA_MB_GIORNI', 365),
        'KD' => (int) env('FITOK_VALIDITA_KD_GIORNI', 365),
        'DH' => (int) env('FITOK_VALIDITA_DH_GIORNI', 365),
    ],
];

