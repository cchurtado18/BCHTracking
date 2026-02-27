<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas Drop Off - {{ $preregistrations->first()->warehouse_code ?? 'BCH' }} - BCH Tracking</title>
    <style>
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
        .label-sheet .barcode-wrap canvas { max-width: 100%; height: auto !important; }
        /* Títulos (labels) en negro: mayor contraste */
        .label-sheet .field { font-size: 10px; color: #111827; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 800; margin-top: 0; }
        .label-sheet .value { font-size: 15px; font-weight: 600; color: #111; margin-top: 2px; }
        .label-sheet .value.value-sm { font-size: 12px; font-weight: 600; }
        .label-sheet .value.value-code { font-family: 'Helvetica Neue', Arial, sans-serif; font-variant-numeric: tabular-nums; font-size: 13px; letter-spacing: 0.08em; }
        .label-sheet .value.value-destination { font-size: 16px; font-weight: 800; }
        .label-sheet .value.label-tracking { font-family: 'Helvetica Neue', Arial, sans-serif; font-variant-numeric: tabular-nums; font-size: 14px; }
        .label-sheet .value.label-service { font-size: 20px; font-weight: 800; letter-spacing: 0.03em; }
        .label-sheet .value.label-service-air { color: #0f766e; }
        .label-sheet .value.label-service-sea { color: #1e40af; }
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
        @include('preregistrations.partials.label-sheet', compact('preregistration'))
    @endforeach

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        document.querySelectorAll('.barcode-canvas').forEach(function(el) {
            if (el.dataset.barcode) JsBarcode(el, el.dataset.barcode, {
                format: 'CODE128',
                width: 2.5,
                height: 50,
                displayValue: true,
                fontSize: 16,
                margin: 8
            });
        });
    </script>
</body>
</html>
