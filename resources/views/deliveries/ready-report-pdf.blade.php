<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listos para retiro · {{ $accountName }}</title>
    <style>
        @page { margin: 18mm 14mm 16mm; }
        body {
            margin: 0;
            padding: 0;
            color: #111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            line-height: 1.35;
        }
        .brand { font-weight: 700; font-size: 13px; color: #0A2D6F; letter-spacing: 0.03em; }
        .muted { color: #4b5563; font-size: 8.5px; }
        .title { font-weight: 700; font-size: 14px; margin: 8px 0 2px; }
        .meta { margin-bottom: 10px; }
        .meta td { padding: 1px 8px 1px 0; }
        table { width: 100%; border-collapse: collapse; }
        .rows { margin-top: 4px; }
        .rows th {
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1.5px solid #0A2D6F;
            padding: 4px 4px 5px;
            color: #0A2D6F;
        }
        .rows td {
            border-bottom: 1px solid #e5e7eb;
            padding: 4px;
            vertical-align: top;
        }
        .num { text-align: right; white-space: nowrap; }
        .code { font-family: DejaVu Sans Mono, monospace; }
        .totals {
            margin-top: 10px;
            border-top: 1.5px solid #111;
            padding-top: 6px;
        }
        .totals td { padding: 2px 8px 2px 0; }
        .footer { margin-top: 14px; color: #6b7280; font-size: 8px; }
    </style>
</head>
<body>
@php
    $printedAt = $printedAt ?? now()->timezone(config('app.display_timezone') ?: 'America/New_York');
    $serviceFilter = ! empty($serviceType) ? \App\Support\ServiceType::label($serviceType) : 'Todos';
@endphp

<div class="brand">PrimeTrack Group</div>
<div class="muted">8307 NW 68th St · Miami, FL 33166</div>
<div class="title">Paquetes listos para retiro</div>
<div class="muted">Listado para enviar al cliente. No incluye paquetes que ya salieron.</div>

<table class="meta">
    <tr>
        <td><strong>Cliente</strong></td>
        <td>{{ $accountName }}</td>
        <td><strong>Código</strong></td>
        <td class="code">{{ $agency->code ?: '—' }}</td>
    </tr>
    <tr>
        <td><strong>Servicio</strong></td>
        <td>{{ $serviceFilter }}</td>
        <td><strong>Emitido</strong></td>
        <td>{{ $printedAt->format('d/m/Y H:i') }}</td>
    </tr>
</table>

<table class="rows">
    <thead>
        <tr>
            <th>#</th>
            <th>Warehouse</th>
            <th>Tracking</th>
            <th>Destinatario</th>
            <th>Servicio</th>
            <th class="num">Peso (lbs)</th>
            <th>Listo desde</th>
        </tr>
    </thead>
    <tbody>
        @foreach($packages as $index => $p)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td class="code">{{ $p->warehouse_code ?: '—' }}</td>
            <td>{{ $p->tracking_external ?: '—' }}</td>
            <td>{{ $p->label_name ?: '—' }}</td>
            <td>{{ \App\Support\ServiceType::label($p->service_type) }}</td>
            <td class="num">{{ number_format((float) ($p->verified_weight_lbs ?? $p->intake_weight_lbs ?? 0), 2) }}</td>
            <td>{{ $p->ready_at ? $p->ready_at->timezone(config('app.display_timezone') ?: 'America/New_York')->format('d/m/Y H:i') : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td><strong>Total paquetes</strong></td>
        <td>{{ number_format($totals['count']) }}</td>
        <td><strong>Peso total</strong></td>
        <td>{{ number_format($totals['lbs'], 2) }} lbs</td>
    </tr>
    <tr>
        <td>Aéreo</td>
        <td>{{ $totals['air_count'] }} · {{ number_format($totals['air_lbs'], 2) }} lbs</td>
        <td>Marítimo</td>
        <td>{{ $totals['sea_count'] }} · {{ number_format($totals['sea_lbs'], 2) }} lbs</td>
    </tr>
    <tr>
        <td>Pie cúbico</td>
        <td>{{ $totals['cft_count'] }} · {{ number_format($totals['cft_lbs'], 2) }} lbs</td>
        <td></td>
        <td></td>
    </tr>
</table>

<div class="footer">PrimeTrack Group · reporte de listos para retiro · {{ $printedAt->format('d/m/Y H:i') }}</div>
</body>
</html>
