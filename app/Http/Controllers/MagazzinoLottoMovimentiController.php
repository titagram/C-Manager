<?php

namespace App\Http\Controllers;

use App\Enums\TipoMovimento;
use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use Illuminate\View\View;

class MagazzinoLottoMovimentiController extends Controller
{
    public function __invoke(LottoMateriale $lotto): View
    {
        $lotto->load('prodotto');

        $movimenti = MovimentoMagazzino::query()
            ->with([
                'createdBy:id,name',
                'documento:id,tipo,numero,data',
                'lottoProduzione:id,codice_lotto,ordine_id',
                'lottoProduzione.ordine:id,numero',
            ])
            ->where('lotto_materiale_id', $lotto->id)
            ->whereIn('tipo', [
                TipoMovimento::SCARICO->value,
                TipoMovimento::RETTIFICA_NEGATIVA->value,
            ])
            ->orderByDesc('data_movimento')
            ->orderByDesc('id')
            ->get();

        $summary = [
            'totale_quantita' => round((float) $movimenti->sum(fn (MovimentoMagazzino $movimento) => (float) $movimento->quantita), 4),
            'manuali' => $movimenti->filter(fn (MovimentoMagazzino $movimento) => $movimento->tipo === TipoMovimento::SCARICO && $movimento->lotto_produzione_id === null)->count(),
            'consumi' => $movimenti->filter(fn (MovimentoMagazzino $movimento) => $movimento->tipo === TipoMovimento::SCARICO && $movimento->lotto_produzione_id !== null)->count(),
            'rettifiche_negative' => $movimenti->filter(fn (MovimentoMagazzino $movimento) => $movimento->tipo === TipoMovimento::RETTIFICA_NEGATIVA)->count(),
        ];

        return view('magazzino.movimenti', [
            'lotto' => $lotto,
            'movimenti' => $movimenti,
            'summary' => $summary,
        ]);
    }
}
