<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Enums\StatoLottoProduzione;
use App\Enums\StatoPreventivo;
use App\Services\FitokReportService;

class DashboardController extends Controller
{
    public function __construct(
        private FitokReportService $fitokService
    ) {}

    public function index()
    {
        $stats = [
            'lotti_attivi' => LottoMateriale::whereNull('deleted_at')->count(),
            'lotti_produzione_attivi' => LottoProduzione::whereIn('stato', [
                StatoLottoProduzione::BOZZA,
                StatoLottoProduzione::IN_LAVORAZIONE,
            ])->count(),
            'preventivi_aperti' => Preventivo::whereIn('stato', [
                StatoPreventivo::BOZZA,
                StatoPreventivo::INVIATO,
            ])->count(),
            'clienti_attivi' => Cliente::where('is_active', true)->count(),
            'prodotti_attivi' => Prodotto::where('is_active', true)->count(),
            'valore_preventivi_aperti' => Preventivo::whereIn('stato', [
                StatoPreventivo::BOZZA,
                StatoPreventivo::INVIATO,
            ])->sum('totale'),
        ];

        $movimenti = MovimentoMagazzino::with('lottoMateriale')
            ->orderBy('data_movimento', 'desc')
            ->limit(5)
            ->get();

        $lottiProduzione = LottoProduzione::with('cliente')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Preventivi recenti
        $preventiviRecenti = Preventivo::with('cliente')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Lotti FITOK in scadenza
        $lottiInScadenza = $this->fitokService->getLottiInScadenza(30);

        return view('dashboard', compact(
            'stats',
            'movimenti',
            'lottiProduzione',
            'preventiviRecenti',
            'lottiInScadenza'
        ));
    }
}
