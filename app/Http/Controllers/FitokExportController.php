<?php

namespace App\Http\Controllers;

use App\Services\FitokReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FitokExportController extends Controller
{
    public function __construct(
        private FitokReportService $fitokService
    ) {}

    public function pdf(Request $request)
    {
        $dataInizio = Carbon::parse($request->get('data_inizio', now()->startOfMonth()));
        $dataFine = Carbon::parse($request->get('data_fine', now()->endOfMonth()));

        $data = $this->fitokService->getDataForExport($dataInizio, $dataFine);

        $pdf = Pdf::loadView('reports.fitok-pdf', [
            'data' => $data,
            'dataInizio' => $dataInizio,
            'dataFine' => $dataFine,
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = "Registro_FITOK_{$dataInizio->format('Y-m-d')}_{$dataFine->format('Y-m-d')}.pdf";

        return $pdf->download($filename);
    }

    public function excel(Request $request): StreamedResponse
    {
        $dataInizio = Carbon::parse($request->get('data_inizio', now()->startOfMonth()));
        $dataFine = Carbon::parse($request->get('data_fine', now()->endOfMonth()));

        $data = $this->fitokService->getDataForExport($dataInizio, $dataFine);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registro FITOK');

        // Header
        $sheet->setCellValue('A1', 'REGISTRO FITOK');
        $sheet->setCellValue('A2', "Periodo: {$data['periodo']['da']} - {$data['periodo']['a']}");
        $sheet->setCellValue('A3', "Generato il: {$data['generato_il']}");

        // Riepilogo
        $sheet->setCellValue('A5', 'RIEPILOGO');
        $sheet->setCellValue('A6', 'Carichi:');
        $sheet->setCellValue('B6', $data['riepilogo']['carichi']);
        $sheet->setCellValue('A7', 'Scarichi:');
        $sheet->setCellValue('B7', $data['riepilogo']['scarichi']);
        $sheet->setCellValue('A8', 'Rettifiche +:');
        $sheet->setCellValue('B8', $data['riepilogo']['rettifiche_positive']);
        $sheet->setCellValue('A9', 'Rettifiche -:');
        $sheet->setCellValue('B9', $data['riepilogo']['rettifiche_negative']);
        $sheet->setCellValue('A10', 'Saldo:');
        $sheet->setCellValue('B10', $data['riepilogo']['saldo']);

        // Table headers
        $headers = [
            'Data',
            'Tipo',
            'Lotto Carico',
            'Lotto Produzione Destinazione',
            'Stato Certificazione Uscita',
            'Prodotto',
            'Quantità',
            'Unità',
            'Certificato FITOK',
            'Data Trattamento',
            'Tipo Trattamento',
            'Paese Origine',
            'Documento',
            'Causale',
        ];
        $row = 12;

        foreach ($headers as $index => $header) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E5E7EB');
        }

        // Data rows
        $row = 13;
        foreach ($data['movimenti'] as $movimento) {
            $sheet->setCellValue('A' . $row, $movimento['data']);
            $sheet->setCellValue('B' . $row, $movimento['tipo']);
            $sheet->setCellValue('C' . $row, $movimento['lotto_carico'] ?? $movimento['lotto']);
            $sheet->setCellValue('D' . $row, $movimento['lotto_produzione_destinazione'] ?? '-');
            $sheet->setCellValue('E' . $row, $movimento['stato_certificazione_uscita'] ?? '-');
            $sheet->setCellValue('F' . $row, $movimento['prodotto']);
            $sheet->setCellValue('G' . $row, $movimento['quantita']);
            $sheet->setCellValue('H' . $row, $movimento['unita']);
            $sheet->setCellValue('I' . $row, $movimento['certificato_fitok']);
            $sheet->setCellValue('J' . $row, $movimento['data_trattamento']);
            $sheet->setCellValue('K' . $row, $movimento['tipo_trattamento']);
            $sheet->setCellValue('L' . $row, $movimento['paese_origine']);
            $sheet->setCellValue('M' . $row, $movimento['documento']);
            $sheet->setCellValue('N' . $row, $movimento['causale']);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Style title
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A5')->getFont()->setBold(true);

        $filename = "Registro_FITOK_{$dataInizio->format('Y-m-d')}_{$dataFine->format('Y-m-d')}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
