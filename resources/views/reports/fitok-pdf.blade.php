<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registro FITOK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #333;
        }
        .container {
            padding: 15px;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #16a34a;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #16a34a;
        }
        .document-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 10px;
        }
        .period {
            font-size: 10pt;
            color: #666;
            margin-top: 5px;
        }
        .riepilogo {
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }
        .riepilogo-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
            border: 1px solid #e5e7eb;
        }
        .riepilogo-label {
            font-size: 7pt;
            color: #666;
            text-transform: uppercase;
        }
        .riepilogo-value {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 3px;
        }
        .riepilogo-value.green { color: #16a34a; }
        .riepilogo-value.red { color: #dc2626; }
        .riepilogo-value.blue { color: #2563eb; }
        .riepilogo-value.orange { color: #ea580c; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 5px 4px;
            text-align: left;
            font-size: 7pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #d1d5db;
            padding: 4px;
            font-size: 7pt;
            vertical-align: top;
        }
        .table .text-right {
            text-align: right;
        }
        .table .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 6pt;
            font-weight: bold;
        }
        .badge-green {
            background-color: #dcfce7;
            color: #16a34a;
        }
        .badge-red {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .badge-blue {
            background-color: #dbeafe;
            color: #2563eb;
        }
        .badge-orange {
            background-color: #ffedd5;
            color: #ea580c;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 7pt;
            color: #666;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">C-Manager Demo</div>
            <div class="document-title">REGISTRO FITOK</div>
            <div class="period">Periodo: {{ $data['periodo']['da'] }} - {{ $data['periodo']['a'] }}</div>
        </div>

        <!-- Riepilogo -->
        <div class="riepilogo">
            <div class="riepilogo-item">
                <div class="riepilogo-label">Carichi</div>
                <div class="riepilogo-value green">{{ number_format($data['riepilogo']['carichi'], 2, ',', '.') }}</div>
            </div>
            <div class="riepilogo-item">
                <div class="riepilogo-label">Scarichi</div>
                <div class="riepilogo-value red">{{ number_format($data['riepilogo']['scarichi'], 2, ',', '.') }}</div>
            </div>
            <div class="riepilogo-item">
                <div class="riepilogo-label">Rettifiche +</div>
                <div class="riepilogo-value blue">{{ number_format($data['riepilogo']['rettifiche_positive'], 2, ',', '.') }}</div>
            </div>
            <div class="riepilogo-item">
                <div class="riepilogo-label">Rettifiche -</div>
                <div class="riepilogo-value orange">{{ number_format($data['riepilogo']['rettifiche_negative'], 2, ',', '.') }}</div>
            </div>
            <div class="riepilogo-item">
                <div class="riepilogo-label">Saldo Periodo</div>
                <div class="riepilogo-value {{ $data['riepilogo']['saldo'] >= 0 ? 'green' : 'red' }}">
                    {{ number_format($data['riepilogo']['saldo'], 2, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Tabella Movimenti -->
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 8%">Data</th>
                    <th style="width: 7%">Tipo</th>
                    <th style="width: 9%">Lotto Carico</th>
                    <th style="width: 9%">Lotto Dest.</th>
                    <th style="width: 11%">Stato Cert.</th>
                    <th style="width: 13%">Prodotto</th>
                    <th style="width: 6%" class="text-right">Quantità</th>
                    <th style="width: 4%">UM</th>
                    <th style="width: 8%">Certificato</th>
                    <th style="width: 7%">Data Tratt.</th>
                    <th style="width: 6%">Tipo Tratt.</th>
                    <th style="width: 4%">Paese</th>
                    <th style="width: 5%">Documento</th>
                    <th style="width: 3%">Causale</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['movimenti'] as $movimento)
                    <tr>
                        <td>{{ $movimento['data'] }}</td>
                        <td>
                            @php
                                $badgeClass = match($movimento['tipo']) {
                                    'Carico' => 'badge-green',
                                    'Scarico' => 'badge-red',
                                    'Rettifica +' => 'badge-blue',
                                    'Rettifica -' => 'badge-orange',
                                    default => '',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $movimento['tipo'] }}</span>
                        </td>
                        <td>{{ $movimento['lotto_carico'] ?? $movimento['lotto'] }}</td>
                        <td>{{ $movimento['lotto_produzione_destinazione'] ?? '-' }}</td>
                        <td>{{ $movimento['stato_certificazione_uscita'] ?? '-' }}</td>
                        <td>{{ $movimento['prodotto'] }}</td>
                        <td class="text-right">{{ $movimento['quantita'] }}</td>
                        <td>{{ $movimento['unita'] }}</td>
                        <td>{{ $movimento['certificato_fitok'] }}</td>
                        <td>{{ $movimento['data_trattamento'] }}</td>
                        <td>{{ $movimento['tipo_trattamento'] }}</td>
                        <td>{{ $movimento['paese_origine'] }}</td>
                        <td>{{ $movimento['documento'] }}</td>
                        <td>{{ $movimento['causale'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center" style="padding: 20px;">
                            Nessun movimento FITOK nel periodo selezionato
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>Documento generato il {{ $data['generato_il'] }}</p>
            <p>Registro conforme alla normativa FITOK (ISPM 15)</p>
        </div>
    </div>
</body>
</html>
