<?php

namespace App\Http\Controllers;

use App\Models\Preventivo;
use Barryvdh\DomPDF\Facade\Pdf;

class PreventivoPdfController extends Controller
{
    public function __invoke(Preventivo $preventivo)
    {
        $preventivo->load(['cliente', 'righe.prodotto', 'righe.lottoProduzione.costruzione', 'createdBy']);

        $pdf = Pdf::loadView('preventivi.pdf', [
            'preventivo' => $preventivo,
        ]);

        $filename = "Preventivo_{$preventivo->numero}.pdf";

        return $pdf->download($filename);
    }
}
