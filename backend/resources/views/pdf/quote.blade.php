<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $quote->quote_number }} v{{ $quote->version }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            color: #1e293b;
            margin: 0;
            padding: 20px 24px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 12px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .company-sub {
            font-size: 9.5px;
            color: #475569;
            margin-top: 3px;
        }
        .quote-box {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px 14px;
            text-align: right;
        }
        .quote-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .quote-number {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .quote-meta {
            font-size: 9.5px;
            color: #475569;
            margin-top: 4px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .info-card {
            width: 50%;
            vertical-align: top;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }
        .info-card h4 {
            margin: 0 0 6px 0;
            font-size: 9.5px;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 0.4px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .info-row {
            margin-bottom: 3px;
            font-size: 9.8px;
        }
        .info-label {
            font-weight: bold;
            color: #475569;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 9.5px;
            text-transform: uppercase;
            padding: 7px 6px;
            text-align: left;
            border: 1px solid #1e293b;
        }
        .items-table th.num,
        .items-table td.num {
            text-align: right;
        }
        .items-table th.center,
        .items-table td.center {
            text-align: center;
        }
        .section-row td {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: bold;
            font-size: 10px;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
        }
        .items-table td {
            padding: 6px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .bottom-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .terms-cell {
            width: 58%;
            vertical-align: top;
            padding-right: 16px;
        }
        .totals-cell {
            width: 42%;
            vertical-align: top;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .totals-table tr.total-row td {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 12px;
            border-bottom: none;
        }
        .terms-box {
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            padding: 10px 12px;
            border-radius: 4px;
        }
        .terms-box h4 {
            margin: 0 0 6px 0;
            font-size: 9.5px;
            text-transform: uppercase;
            color: #1e3a8a;
        }
        .terms-box p {
            margin: 3px 0;
            font-size: 9.5px;
        }
        .signature-section {
            margin-top: 32px;
            width: 100%;
            border-collapse: collapse;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            padding-top: 30px;
        }
        .signature-line {
            border-top: 1px solid #64748b;
            padding-top: 5px;
            font-size: 9.5px;
            color: #475569;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8.5px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top; padding-bottom: 10px;">
                <div class="company-name">{{ $company?->name ?? 'TECHQUOTE INTEGRACIONES S.A.C.' }}</div>
                <div class="company-sub">
                    RUC: {{ $company?->ruc ?? '20601234567' }} | Soluciones en CCTV, Cableado Estructurado, Redes Wi-Fi y Mantenimiento<br>
                    {{ $company?->address ?? 'Av. República de Panamá 3420, San Isidro, Lima' }} | Tel: {{ $company?->phone ?? '(01) 442-8900' }} | {{ $company?->email ?? 'ventas@techquote.pe' }}
                </div>
            </td>
            <td style="width: 40%; vertical-align: top; padding-bottom: 10px;">
                <div class="quote-box">
                    <div class="quote-title">Cotización Técnica</div>
                    <div class="quote-number">{{ $quote->quote_number }} <span style="font-size: 11px; color: #475569;">(v{{ $quote->version }})</span></div>
                    <div class="quote-meta">
                        Emisión: {{ $quote->issue_date?->format('d/m/Y') }} | Válida hasta: {{ $quote->valid_until?->format('d/m/Y') }}<br>
                        Estado: <strong>{{ strtoupper($quote->status->label()) }}</strong> | Moneda: <strong>{{ $quote->currency }}</strong>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="info-grid">
        <tr>
            <td class="info-card">
                <h4>Datos del Cliente</h4>
                <div class="info-row"><span class="info-label">Razón Social:</span> {{ $quote->client->business_name }}</div>
                <div class="info-row"><span class="info-label">{{ $quote->client->document_type }}:</span> {{ $quote->client->document_number }}</div>
                @if($quote->client->fiscal_address)
                    <div class="info-row"><span class="info-label">Dirección:</span> {{ $quote->client->fiscal_address }}</div>
                @endif
                @if($quote->contact)
                    <div class="info-row"><span class="info-label">Atención:</span> {{ $quote->contact->full_name }} @if($quote->contact->position)({{ $quote->contact->position }})@endif</div>
                @endif
            </td>
            <td class="info-card">
                <h4>Sede de Instalación y Proyecto</h4>
                @if($quote->site)
                    <div class="info-row"><span class="info-label">Sede:</span> {{ $quote->site->site_name }}</div>
                    <div class="info-row"><span class="info-label">Ubicación:</span> {{ $quote->site->address }} @if($quote->site->district_city)- {{ $quote->site->district_city }}@endif</div>
                @else
                    <div class="info-row"><span class="info-label">Sede:</span> Dirección fiscal principal</div>
                @endif
                @if($quote->lead)
                    <div class="info-row"><span class="info-label">Oportunidad:</span> {{ $quote->lead->code }} - {{ $quote->lead->title }}</div>
                @endif
                <div class="info-row"><span class="info-label">Elaborado por:</span> {{ $quote->creator?->name ?? 'Equipo Comercial' }}</div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="center">#</th>
                <th style="width: 44%;">Descripción Técnica</th>
                <th style="width: 8%;" class="center">Und.</th>
                <th style="width: 9%;" class="num">Cant.</th>
                <th style="width: 12%;" class="num">P. Unit.</th>
                <th style="width: 8%;" class="num">Desc.%</th>
                <th style="width: 13%;" class="num">Importe</th>
            </tr>
        </thead>
        <tbody>
            @php $counter = 1; @endphp
            @foreach($groupedItems as $sectionName => $items)
                <tr class="section-row">
                    <td colspan="7">{{ $sectionName }}</td>
                </tr>
                @foreach($items as $item)
                    <tr>
                        <td class="center">{{ $counter++ }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="center">{{ $item->unit->value ?? $item->unit }}</td>
                        <td class="num">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="num">{{ $currencySymbol }} {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="num">{{ number_format((float) $item->discount_percent, 1) }}%</td>
                        <td class="num"><strong>{{ $currencySymbol }} {{ number_format((float) $item->line_total, 2) }}</strong></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <table class="bottom-grid">
        <tr>
            <td class="terms-cell">
                <div class="terms-box">
                    <h4>Condiciones Comerciales y Garantía</h4>
                    <p><strong>Forma de Pago:</strong> {{ $quote->payment_terms ?: 'A convenir' }}</p>
                    <p><strong>Tiempo de Entrega / Ejecución:</strong> {{ $quote->delivery_time ?: 'Por confirmar' }}</p>
                    <p><strong>Garantía:</strong> {{ $quote->warranty_terms ?: '12 meses' }}</p>
                    @if($quote->technical_notes)
                        <p style="margin-top: 6px;"><strong>Notas Técnicas:</strong> {{ $quote->technical_notes }}</p>
                    @endif
                </div>
            </td>
            <td class="totals-cell">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal</td>
                        <td style="text-align: right;">{{ $currencySymbol }} {{ number_format((float) $quote->subtotal, 2) }}</td>
                    </tr>
                    @if((float) $quote->discount_amount > 0)
                    <tr>
                        <td>Descuento Global</td>
                        <td style="text-align: right; color: #b91c1c;">- {{ $currencySymbol }} {{ number_format((float) $quote->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>IGV (18%)</td>
                        <td style="text-align: right;">{{ $currencySymbol }} {{ number_format((float) $quote->tax_amount, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>TOTAL ({{ $quote->currency }})</td>
                        <td style="text-align: right;">{{ $currencySymbol }} {{ number_format((float) $quote->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="signature-section">
        <tr>
            <td class="signature-box">
                <div class="signature-line">
                    <strong>{{ $quote->creator?->name ?? 'Ejecutivo Comercial' }}</strong><br>
                    TechQuote Integraciones S.A.C.
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td class="signature-box">
                <div class="signature-line">
                    <strong>Aprobación del Cliente</strong><br>
                    {{ $quote->client->business_name }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Documento generado electrónicamente por TechQuote CRM. Precios expresados en {{ $quote->currency }} sujetos a validez de la oferta.
    </div>
</body>
</html>
