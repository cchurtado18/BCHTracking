<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->folio }} · Nota de cobro SkyLink One</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111;
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.25;
        }
        .vch-ticket { width: 72mm; max-width: 100%; margin: 0 auto; padding: 2mm 0 6mm; }
        .vch-center { text-align: center; }
        .vch-right { text-align: right; }
        .vch-sep { border: 0; border-top: 1px dashed #222; margin: 6px 0; }
        .vch-company { font-weight: 700; font-size: 13px; text-transform: uppercase; }
        .vch-muted { font-size: 11px; }
        .vch-title { font-weight: 700; font-size: 14px; letter-spacing: 0.04em; margin: 2px 0; }
        .vch-row { display: flex; justify-content: space-between; gap: 6px; }
        .vch-block { margin: 2px 0; }
        .vch-cols {
            display: grid;
            grid-template-columns: 1.4fr 0.7fr 0.9fr 1fr;
            gap: 2px;
            font-size: 11px;
            font-weight: 700;
        }
        .vch-line-name { margin-top: 4px; }
        .vch-line-nums {
            display: grid;
            grid-template-columns: 1.4fr 0.7fr 0.9fr 1fr;
            gap: 2px;
            font-size: 11px;
        }
        .vch-line-nums span:nth-child(n+2) { text-align: right; }
        .vch-totals .vch-row, .vch-pay .vch-row { font-size: 12px; }
        .vch-totals .vch-row strong, .vch-pay .vch-row strong { font-weight: 700; }
        .vch-sig { margin-top: 14px; min-height: 28px; border-bottom: 1px solid #222; }
        .vch-sig-note { margin-top: 6px; font-size: 11px; }
        .vch-footer { margin-top: 8px; font-weight: 700; }
        .no-print {
            position: sticky;
            top: 0;
            background: #F4F8FD;
            border-bottom: 1px solid #E8EBEF;
            padding: 10px 12px;
            display: flex;
            gap: 8px;
            justify-content: center;
            font-family: Inter, system-ui, sans-serif;
        }
        .no-print a, .no-print button {
            appearance: none;
            border: 1px solid #0A2D6F;
            background: #0A2D6F;
            color: #fff;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .no-print a.secondary { background: #fff; color: #0A2D6F; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button type="button" onclick="window.print()">Imprimir voucher</button>
    @auth
        <a href="{{ route('accounting.invoices.pdf', $invoice) }}">Descargar PDF</a>
        <a class="secondary" href="{{ route('accounting.invoices.show', $invoice) }}">Volver</a>
    @endauth
</div>

@include('accounting.invoices.partials.voucher-ticket')
</body>
</html>
