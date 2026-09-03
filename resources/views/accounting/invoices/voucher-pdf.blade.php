<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->folio }}</title>
    <style>
        @page { margin: 8px 10px; }
        body {
            margin: 0;
            padding: 0;
            color: #111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.35;
        }
        .center { text-align: center; }
        .muted { font-size: 8px; color: #333; }
        .title { font-weight: 700; font-size: 11px; letter-spacing: 0.04em; margin: 4px 0; }
        .company { font-weight: 700; font-size: 10px; text-transform: uppercase; }
        .sep { border-top: 1px dashed #222; margin: 6px 0; height: 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .right { text-align: right; }
        .label { white-space: nowrap; padding-right: 6px; }
        .head td { font-weight: 700; font-size: 8px; border-bottom: 1px solid #222; padding-bottom: 2px; }
        .line-name { padding-top: 4px; }
        .totals td { font-size: 9px; }
        .totals strong { font-weight: 700; }
        .sig {
            margin-top: 12px;
            border-bottom: 1px solid #222;
            height: 18px;
        }
        .footer { margin-top: 8px; font-weight: 700; }
    </style>
</head>
<body>
@php
    $company = $company ?? config('accounting.company');
    $companyName = $company['name'] ?? 'SkyLink One';
    if (str_contains(mb_strtolower($companyName), 'primetrack')) {
        $companyName = 'SkyLink One';
    }
    $agency = $invoice->agency;
    $note = $invoice->deliveryNote;
    $printedAt = now()->timezone(config('app.display_timezone', config('app.timezone')));
@endphp

<div class="center">
    <div class="company">{{ $companyName }}</div>
    @if(!empty($company['tax_id']))
        <div class="muted">{{ $company['tax_id'] }}</div>
    @endif
    @if(!empty($company['address']))
        <div class="muted">{{ $company['address'] }}</div>
    @endif
    @if(!empty($company['phones']))
        <div class="muted">Tel: {{ $company['phones'] }}</div>
    @endif
</div>

<div class="sep"></div>
<div class="center title">NOTA DE COBRO SKYLINK ONE</div>
<div class="sep"></div>

<table>
    <tr><td class="label">OC#</td><td>{{ $invoice->folio }}</td></tr>
    <tr><td class="label">FECHA</td><td>{{ optional($invoice->issued_at)->format('d/m/Y') ?? '—' }}</td></tr>
    <tr><td class="label">REG.POR</td><td>{{ $invoice->createdBy?->name ?? '—' }}</td></tr>
    @if($invoice->noteCodesLabel() !== '—')
    <tr><td class="label">HOJA</td><td>{{ $invoice->noteCodesLabel() }}</td></tr>
    @endif
</table>

<div class="sep"></div>

<table>
    <tr><td class="label">COD CLI</td><td>{{ $agency?->code ?? '—' }}</td></tr>
    <tr><td class="label">Cliente</td><td>{{ $agency?->name ?? '—' }}</td></tr>
    <tr><td class="label">Telefono</td><td>{{ $agency?->phone ?: '—' }}</td></tr>
    <tr><td class="label">Ciudad</td><td>{{ $agency?->department ?: '—' }}</td></tr>
    @if($agency?->address)
    <tr><td class="label">Dir</td><td>{{ $agency->address }}</td></tr>
    @endif
</table>

<div class="sep"></div>

<table>
    <tr class="head">
        <td>SERVICIO</td>
        <td class="right">CANT.</td>
        <td class="right">TARIFA</td>
        <td class="right">IMPORTE</td>
    </tr>
    @foreach($invoice->lines as $line)
    <tr>
        <td colspan="4" class="line-name">{{ $line->description }}</td>
    </tr>
    <tr>
        <td></td>
        <td class="right">{{ number_format((float) $line->quantity_lbs, 2, '.', ',') }} {{ \App\Support\ServiceType::unit($line->service_type) }}</td>
        <td class="right">{{ number_format((float) $line->rate_per_lb, 2, '.', ',') }}</td>
        <td class="right">{{ number_format((float) $line->amount_usd, 2, '.', ',') }}</td>
    </tr>
    @endforeach
</table>

<div class="sep"></div>

<table class="totals">
    @if((float) $invoice->total_lbs > 0)
    <tr><td>TOTAL LBS</td><td class="right"><strong>{{ number_format((float) $invoice->total_lbs, 2, '.', ',') }}</strong></td></tr>
    @endif
    @php $pdfCft = $invoice->lines->where('service_type', 'CFT')->sum('quantity_lbs'); @endphp
    @if($pdfCft > 0)
    <tr><td>TOTAL PIE³</td><td class="right"><strong>{{ number_format((float) $pdfCft, 2, '.', ',') }}</strong></td></tr>
    @endif
    <tr><td>TOTAL COR</td><td class="right"><strong>{{ number_format((float) $invoice->total_cor, 2, '.', ',') }}</strong></td></tr>
    <tr><td>TOTAL USD</td><td class="right"><strong>{{ number_format((float) $invoice->total_usd, 2, '.', ',') }}</strong></td></tr>
</table>

<div class="sep"></div>

<table class="totals">
    <tr><td>Estado</td><td class="right"><strong>{{ $invoice->statusLabel() }}</strong></td></tr>
    <tr><td>Pagado</td><td class="right"><strong>{{ number_format((float) $invoice->amount_paid, 2, '.', ',') }}</strong></td></tr>
    <tr><td>Saldo</td><td class="right"><strong>{{ number_format($invoice->balanceUsd(), 2, '.', ',') }}</strong></td></tr>
</table>

<div class="sep"></div>
<div class="sig"></div>
<div class="center muted" style="margin-top:6px;">Firma cliente igual a Ced.</div>
<div class="center muted">Reconozco el monto adeudado</div>
<div class="sep"></div>
<div class="footer center">{{ $company['footer'] ?? 'Es un gusto atenderle!' }}</div>
<div class="center muted" style="margin-top:6px;">{{ $printedAt->format('d/m/Y H:i') }}</div>
</body>
</html>
