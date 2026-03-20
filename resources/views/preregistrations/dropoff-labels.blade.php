<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas Drop Off - {{ $preregistrations->first()->warehouse_code ?? 'BCH' }} - BCH Tracking</title>
    <style>
        @php
            $first = $preregistrations->first();
            $isSkyLinkOne = $first && $first->agency && $first->agency->isSkyLinkOne();
        @endphp

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f3f4f6; padding: 16px; }
        .label-sheet {
            width: 4in; min-height: 6in; max-width: 100%;
            margin: 16px auto; background: white; border: 2px solid #111;
            border-radius: 8px; padding: 14px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .label-sheet .label-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #1e40af; }
        .label-sheet .label-header .company-block { margin: 0; padding: 0; border: none; }
        .label-sheet .label-header .company { font-size: 14px; font-weight: 700; color: #1e40af; letter-spacing: 0.02em; margin: 0; padding: 0; border: none; }
        .label-sheet .label-header .company-address { font-size: 10px; color: #374151; margin-top: 2px; letter-spacing: 0.02em; }
        .label-sheet .label-header .company-city { font-size: 10px; color: #374151; margin-top: 1px; letter-spacing: 0.02em; }
        .label-sheet .label-header .agency-logo-wrap { min-height: 58px; max-width: 200px; display: flex; align-items: center; justify-content: flex-end; background: transparent; padding: 4px 0; }
        .label-sheet .label-header .agency-logo { max-height: 58px; max-width: 180px; width: auto; height: auto; object-fit: contain; object-position: right center; display: block; background: transparent; mix-blend-mode: multiply; }
        .label-sheet .label-header .agency-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
        .label-sheet .label-header .agency-name-fallback { font-size: 12px; font-weight: 600; color: #1e40af; text-align: right; max-width: 180px; }
        .label-sheet .label-header .agency-name-below { font-size: 10px; font-weight: 600; color: #1e40af; text-align: right; max-width: 180px; line-height: 1.2; }
        .label-sheet .barcode-section { margin-top: 20px; }
        .label-sheet .warehouse-code { font-size: 42px; font-weight: 800; letter-spacing: 0.15em; text-align: center; margin: 16px 0; font-family: 'Helvetica Neue', Arial, sans-serif; font-variant-numeric: tabular-nums; color: #111; }
        .label-sheet .barcode-wrap { text-align: center; margin: 8px 0 16px; max-width: 100%; overflow: hidden; }
        .label-sheet .barcode-row { display: inline-flex; align-items: flex-end; justify-content: center; gap: 14px; }
        .label-sheet .barcode-wrap canvas { max-width: 100%; height: auto !important; }
        .label-sheet .service-mark-large { display: inline-block; font-size: 62px; font-weight: 900; line-height: 0.9; color: #111; letter-spacing: 0.02em; margin-bottom: 18px; margin-left: 6px; }
        /* Títulos (labels) en negro: mayor contraste */
        .label-sheet .field { font-size: 10px; color: #111827; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 800; margin-top: 0; }
        .label-sheet .value { font-size: 15px; font-weight: 600; color: #111; margin-top: 2px; }
        .label-sheet .value.value-sm { font-size: 12px; font-weight: 600; }
        .label-sheet .value.value-code { font-family: 'Helvetica Neue', Arial, sans-serif; font-variant-numeric: tabular-nums; font-size: 13px; letter-spacing: 0.08em; }
        .label-sheet .value.value-destination { font-size: 16px; font-weight: 800; }
        .label-sheet .value.label-tracking { font-family: 'Helvetica Neue', Arial, sans-serif; font-variant-numeric: tabular-nums; font-size: 14px; }
        .label-sheet .value.label-service {
            display: inline-block;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.08em;
            padding: 2px 12px;
            border: 2px solid #111;
            border-radius: 10px;
            line-height: 1.1;
            color: #111;
            text-transform: uppercase;
        }
        .label-sheet .value.label-service-air { border-style: dashed; }
        .label-sheet .value.label-service-sea { border-style: solid; }
        .label-sheet .reception-note { margin-top: 20px; padding: 12px 14px; background: #eff6ff; border: 1px solid #93c5fd; border-radius: 6px; }
        .label-sheet .reception-note .title { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #111827; font-weight: 700; margin-bottom: 4px; }
        .label-sheet .reception-note .datetime { font-size: 18px; font-weight: 700; color: #1e3a8a; }

        /* Layout tipo etiqueta logística (informativo y limpio) */
        .label-sheet .kv { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .label-sheet .kv:first-of-type { padding-top: 4px; }
        .label-sheet .kv:last-of-type { border-bottom: none; }
        .label-sheet .kv.kv-destination { background: #f8fafc; border: 1px solid #e5e7eb; border-left: 5px solid #111827; border-radius: 8px; padding: 10px 12px; margin-top: 8px; }
        .label-sheet .kv.kv-3col { display: grid; grid-template-columns: 1.1fr 0.9fr 1fr; gap: 10px; align-items: start; padding: 10px 0; }
        .label-sheet .kv.kv-3col .kv-col { min-width: 0; }
        .label-sheet .kv.kv-3col .value { margin-top: 4px; }
        .label-sheet .kv .value { margin-top: 4px; }
        .label-sheet .kv.kv-3col { border-bottom: 1px solid #e5e7eb; }
        .label-sheet .kv + .barcode-section { margin-top: 10px; }
        .label-sheet .warehouse-code { text-shadow: 0 1px 0 rgba(0,0,0,0.08); }
        .no-print { text-align: center; margin-bottom: 16px; }
        .no-print button { background: #2563eb; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .no-print button:hover { background: #1d4ed8; }
        .no-print a { display: inline-block; margin-left: 12px; color: #4b5563; font-size: 14px; }
        .no-print-hint { font-size: 13px; color: #6b7280; margin-top: 8px; }
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .label-sheet { width: 4in; min-height: 6in; max-width: none; margin: 0; border: 1px solid #000; box-shadow: none; page-break-after: always; }
            .label-sheet:last-child { page-break-after: auto; }
        }

        /* SkyLink One (para que Drop Off se vea igual que Courier) */
        .label-sheet {
            width: 4in;
            min-height: 6in;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #111;
            border-radius: 8px;
            padding: 12px 14px;
        }
        .sl-top-logo {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin-bottom: 10px;
            min-height: 56px;
        }
        .sl-top-logo img {
            max-width: 100%;
            max-height: 56px;
            object-fit: contain;
        }
        .sl-divider {
            height: 3px;
            background: #1e40af;
            margin: 10px 0 16px;
        }
        .sl-tracking-global-label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 700;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }
        .sl-tracking-global-value {
            font-size: 20px;
            font-weight: 800;
            color: #111;
            line-height: 1;
            margin-bottom: 8px;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .sl-agency-box {
            margin-top: 6px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 14px;
            position: relative;
        }
        .sl-bulto-badge {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 11px;
            font-weight: 900;
            color: #111;
            border: 1px solid #111;
            border-radius: 999px;
            padding: 2px 8px;
            background: #fff;
            letter-spacing: 0.02em;
            line-height: 1;
        }
        .sl-agency-box::before {
            content: '';
            position: absolute;
            left: 14px;
            top: 10px;
            bottom: 10px;
            width: 8px;
            background: #1e40af;
            border-radius: 8px;
        }
        .sl-agency-title {
            font-size: 12px;
            font-weight: 800;
            color: #111827;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-left: 14px;
            margin-bottom: 4px;
        }
        .sl-agency-value {
            font-size: 22px;
            font-weight: 800;
            color: #111;
            margin-left: 14px;
            line-height: 1.05;
        }
        .sl-destination-title {
            margin-top: 18px;
            font-size: 12px;
            font-weight: 800;
            color: #6b7280;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .sl-destination-name {
            font-size: 22px;
            font-weight: 800;
            color: #111;
            line-height: 1.05;
        }
        .sl-grid-3 {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1fr 0.9fr 0.85fr;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }
        .sl-grid-cell { padding: 12px 8px; }
        .sl-grid-cell + .sl-grid-cell { border-left: 1px solid #e5e7eb; }
        .sl-grid-title {
            font-size: 12px;
            color: #111827;
            font-weight: 900;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .sl-grid-value {
            font-size: 18px;
            font-weight: 800;
            color: #111;
        }
        .sl-service-badge {
            display: inline-block;
            border: 2px solid #111;
            border-radius: 10px;
            padding: 4px 10px;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.06em;
            line-height: 1.05;
            color: #111 !important; /* B/N: forzar negro */
        }
        .sl-service-badge.service-air { border-style: dashed; }
        .sl-service-badge.service-sea { border-style: solid; }
        .sl-code-mini-date {
            margin-top: 6px;
            font-size: 13px;
            font-weight: 900;
            color: #111827;
            letter-spacing: 0.01em;
            text-align: center;
        }
        .sl-grid-value.service-air { color: #0f766e; }
        .sl-grid-value.service-sea { color: #1e40af; }
        .sl-description-title {
            margin-top: 16px;
            font-size: 12px;
            font-weight: 800;
            color: #6b7280;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 4px;
            text-align: center;
        }
        .sl-description-value {
            font-size: 18px;
            font-weight: 800;
            color: #111;
            margin-bottom: 10px;
            text-align: center;
            word-break: break-word;
        }
        .sl-warehouse-title {
            margin-top: 6px;
            font-size: 12px;
            font-weight: 800;
            color: #6b7280;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .sl-warehouse-code {
            font-size: 40px;
            font-weight: 900;
            color: #111;
            text-align: center;
            letter-spacing: 0.08em;
            line-height: 1;
            margin-bottom: 6px;
        }
        .sl-barcode-wrap { display: flex; justify-content: center; margin-bottom: 6px; }
        .sl-barcode-row { display: inline-flex; align-items: flex-end; justify-content: center; gap: 14px; }
        .sl-service-mark-large { display: inline-block; font-size: 60px; font-weight: 900; line-height: 0.9; color: #111; letter-spacing: 0.02em; margin-bottom: 16px; margin-left: 6px; }
        .sl-review-text {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            margin-top: 8px;
            font-weight: 600;
        }
        @media print {
            .label-sheet {
                border: 2px solid #111;
                transform: scale(0.94);
                transform-origin: top left;
                page-break-inside: avoid;
            }
            .sl-tracking-global-value { font-size: 18px; margin-bottom: 6px; }
            .sl-description-value { margin-bottom: 6px; }
            .sl-review-text { font-size: 12px; margin-top: 6px; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        @if(session('success'))
        <p style="margin-bottom: 12px; padding: 10px; background: #d1fae5; color: #065f46; border-radius: 6px; font-size: 14px;">{{ session('success') }}</p>
        @endif
        <button type="button" onclick="window.print();">🖨️ Imprimir todas las etiquetas</button>
        <p class="no-print-hint">Se imprimirá una etiqueta por cada bulto (mismo código de almacén).</p>
        <a href="{{ route('preregistrations.index', session('preregistrations_index_filters', [])) }}">← Volver a preregistros</a>
    </div>

    @foreach($preregistrations as $preregistration)
        @include('preregistrations.partials.label-skylink-one-sheet', ['preregistration' => $preregistration])
    @endforeach

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        document.querySelectorAll('.barcode-canvas').forEach(function(el) {
            if (!el.dataset.barcode) return;

            var isSkylink = el.id && el.id.indexOf('-skylink') !== -1;
            var opts = isSkylink
                ? { format: 'CODE128', width: 2.2, height: 44, displayValue: true, fontSize: 13, margin: 0 }
                : { format: 'CODE128', width: 2.5, height: 50, displayValue: true, fontSize: 16, margin: 8 };

            JsBarcode(el, el.dataset.barcode, opts);
        });
    </script>
</body>
</html>
