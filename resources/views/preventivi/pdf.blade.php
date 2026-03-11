<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Preventivo {{ $preventivo->numero }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #2563eb;
        }
        .company-subtitle {
            font-size: 10pt;
            color: #666;
        }
        .document-title {
            font-size: 18pt;
            font-weight: bold;
            margin-top: 20px;
        }
        .document-number {
            font-size: 14pt;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 8pt;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .info-value {
            margin-bottom: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px 6px;
            text-align: left;
            font-size: 8pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #d1d5db;
            padding: 6px;
            font-size: 9pt;
        }
        .table .text-right {
            text-align: right;
        }
        .table .text-center {
            text-align: center;
        }
        .totals {
            float: right;
            width: 250px;
        }
        .totals-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 0;
        }
        .totals-label {
            display: table-cell;
            width: 60%;
            color: #666;
        }
        .totals-value {
            display: table-cell;
            width: 40%;
            text-align: right;
            font-weight: bold;
        }
        .totals-row.grand-total {
            border-bottom: 2px solid #2563eb;
            border-top: 2px solid #2563eb;
            font-size: 12pt;
        }
        .totals-row.grand-total .totals-label,
        .totals-row.grand-total .totals-value {
            color: #2563eb;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 8pt;
            color: #666;
        }
        .validity {
            background-color: #fef3c7;
            padding: 10px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .validity-label {
            font-weight: bold;
            color: #92400e;
        }
        .notes {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9fafb;
            border-radius: 4px;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">C-Manager Demo</div>
            <div class="company-subtitle">Lavorazione legno e materiali</div>
            <div class="document-title">PREVENTIVO</div>
            <div class="document-number">{{ $preventivo->numero }}</div>
        </div>

        <!-- Info Section -->
        <div class="info-grid">
            <div class="info-col">
                <div class="info-label">Cliente</div>
                <div class="info-value">
                    <strong>{{ $preventivo->cliente?->ragione_sociale ?? 'N/D' }}</strong><br>
                    @if($preventivo->cliente?->indirizzo)
                        {{ $preventivo->cliente->indirizzo }}<br>
                    @endif
                    @if($preventivo->cliente?->cap || $preventivo->cliente?->citta || $preventivo->cliente?->provincia)
                        {{ $preventivo->cliente->cap }} {{ $preventivo->cliente->citta }} ({{ $preventivo->cliente->provincia }})<br>
                    @endif
                    @if($preventivo->cliente?->partita_iva)
                        P.IVA: {{ $preventivo->cliente->partita_iva }}<br>
                    @endif
                    @if($preventivo->cliente?->codice_fiscale)
                        C.F.: {{ $preventivo->cliente->codice_fiscale }}
                    @endif
                </div>
            </div>
            <div class="info-col">
                <div class="info-label">Data Preventivo</div>
                <div class="info-value">{{ $preventivo->data->format('d/m/Y') }}</div>

                @if($preventivo->validita_fino)
                    <div class="info-label">Valido fino al</div>
                    <div class="info-value">{{ $preventivo->validita_fino->format('d/m/Y') }}</div>
                @endif

                <div class="info-label">Stato</div>
                <div class="info-value">{{ $preventivo->stato->label() }}</div>
            </div>
        </div>

        @if($preventivo->descrizione)
            <div class="info-section">
                <div class="info-label">Descrizione Lavoro</div>
                <div class="info-value">{{ $preventivo->descrizione }}</div>
            </div>
        @endif

        <!-- Tabella Righe -->
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 25%">Descrizione</th>
                    <th style="width: 12%">Dimensioni (mm)</th>
                    <th style="width: 8%" class="text-center">Qtà</th>
                    <th style="width: 10%" class="text-right">Mc Netto</th>
                    <th style="width: 8%" class="text-center">Scarto</th>
                    <th style="width: 10%" class="text-right">Mc Lordo</th>
                    <th style="width: 10%" class="text-right">€/mc</th>
                    <th style="width: 12%" class="text-right">Totale</th>
                </tr>
            </thead>
            <tbody>
                @foreach($preventivo->righe as $index => $riga)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            {{ $riga->descrizione }}
                            @if($riga->prodotto)
                                <br><small style="color: #666">{{ $riga->prodotto->codice }}</small>
                            @endif
                            @php
                                $showPeso = $riga->lottoProduzione?->costruzione?->showWeightInQuote() ?? false;
                                $pesoLotto = (float) ($riga->lottoProduzione?->peso_totale_kg ?? 0);
                            @endphp
                            @if($showPeso && $pesoLotto > 0)
                                <br><small style="color: #666">Peso lotto: {{ number_format($pesoLotto, 2, ',', '.') }} kg</small>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $dimL = (float) ($riga->lunghezza_mm ?? 0);
                                $dimW = (float) ($riga->larghezza_mm ?? 0);
                                $dimH = (float) ($riga->spessore_mm ?? 0);

                                if (($dimL <= 0 || $dimW <= 0 || $dimH <= 0) && $riga->lottoProduzione) {
                                    $dimL = (float) ($riga->lottoProduzione->larghezza_cm ?? 0) * 10;
                                    $dimW = (float) ($riga->lottoProduzione->profondita_cm ?? 0) * 10;
                                    $dimH = (float) ($riga->lottoProduzione->altezza_cm ?? 0) * 10;
                                }
                            @endphp
                            @if($dimL > 0 || $dimW > 0 || $dimH > 0)
                                {{ number_format($dimL, 0) }} x
                                {{ number_format($dimW, 0) }} x
                                {{ number_format($dimH, 0) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">{{ $riga->quantita }}</td>
                        <td class="text-right">{{ number_format($riga->materiale_netto, 4, ',', '.') }}</td>
                        <td class="text-center">{{ number_format($riga->coefficiente_scarto * 100, 0) }}%</td>
                        <td class="text-right">{{ number_format($riga->materiale_lordo, 3, ',', '.') }}</td>
                        <td class="text-right">€ {{ number_format($riga->prezzo_unitario, 2, ',', '.') }}</td>
                        <td class="text-right"><strong>€ {{ number_format($riga->totale_riga, 2, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totali -->
        <div class="clearfix">
            <div class="totals">
                <div class="totals-row">
                    <span class="totals-label">Totale Materiali:</span>
                    <span class="totals-value">€ {{ number_format($preventivo->totale_materiali, 2, ',', '.') }}</span>
                </div>
                @if((float) $preventivo->totale_lavorazioni > 0)
                <div class="totals-row">
                    <span class="totals-label">Lavorazioni extra:</span>
                    <span class="totals-value">€ {{ number_format($preventivo->totale_lavorazioni, 2, ',', '.') }}</span>
                </div>
                @endif
                <div class="totals-row grand-total">
                    <span class="totals-label">TOTALE:</span>
                    <span class="totals-value">€ {{ number_format($preventivo->totale, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        @if($preventivo->validita_fino)
            <div class="validity" style="clear: both; margin-top: 60px;">
                <span class="validity-label">Validità:</span>
                Il presente preventivo è valido fino al {{ $preventivo->validita_fino->format('d/m/Y') }}.
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Preventivo generato il {{ now()->format('d/m/Y H:i') }} - Motore calcolo v{{ $preventivo->engine_version ?? '1.0.0' }}</p>
            @if($preventivo->createdBy)
                <p>Redatto da: {{ $preventivo->createdBy->name }}</p>
            @endif
        </div>
    </div>
</body>
</html>
