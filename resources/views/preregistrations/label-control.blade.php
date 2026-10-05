@php
    $labelAutoprint = request()->boolean('autoprint');
    $labelFormat = $labelFormat ?? '4x6';
    $isNarrow = $labelFormat === 'narrow';
    $pageSizeCss = $isNarrow ? '2.25in 4in' : '4in 6in';
    $sheetWidthCss = $isNarrow ? '2.25in' : '4in';
    $sheetMinHeightCss = $isNarrow ? '4in' : '6in';
    $providerLogo = asset('images/primetrack-group-logo.png').'?v=2';
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $dt = $preregistration->created_at ? $preregistration->created_at->timezone($displayTz) : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta de control - {{ $preregistration->warehouse_code }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f4f6;
            padding: 16px;
        }
        .label-sheet {
            width: 4in;
            min-height: 6in;
            margin: 0 auto;
            background: #fff;
            padding: 14px 12px 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }
        .ctrl-brand {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .ctrl-logo {
            width: 100%;
            max-width: 360px;
            height: 210px;
            object-fit: contain;
            object-position: center;
        }
        .ctrl-address {
            font-size: 18px;
            font-weight: 800;
            color: #111;
            text-align: center;
            line-height: 1.25;
            letter-spacing: 0.01em;
        }
        .ctrl-date {
            font-size: 20px;
            font-weight: 800;
            color: #111;
            text-align: center;
            letter-spacing: 0.02em;
        }
        .ctrl-warehouse {
            font-size: 56px;
            font-weight: 900;
            color: #111;
            letter-spacing: 0.08em;
            line-height: 1;
            text-align: center;
        }
        .ctrl-barcode {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        .ctrl-barcode canvas {
            max-width: 100%;
        }
        .no-print { text-align: center; margin-bottom: 16px; }
        .no-print button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .no-print button:hover { background: #1d4ed8; }
        .no-print > a {
            display: inline-block;
            margin-left: 12px;
            color: #4b5563;
            font-size: 14px;
        }
        .no-print-hint {
            font-size: 13px;
            color: #6b7280;
            margin-top: 8px;
            max-width: 52ch;
            margin-left: auto;
            margin-right: auto;
        }
        .label-paper-narrow .label-sheet {
            width: 2.25in;
            min-height: 4in;
            padding: 8px 6px;
            gap: 8px;
        }
        .label-paper-narrow .ctrl-brand { gap: 6px; }
        .label-paper-narrow .ctrl-logo {
            max-width: 200px;
            height: 92px;
        }
        .label-paper-narrow .ctrl-address {
            font-size: 11px;
        }
        .label-paper-narrow .ctrl-date {
            font-size: 12px;
        }
        .label-paper-narrow .ctrl-warehouse {
            font-size: 28px;
            letter-spacing: 0.05em;
        }
        @page {
            size: {{ $pageSizeCss }};
            margin: 0;
        }
        @media print {
            html, body {
                width: {{ $sheetWidthCss }};
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .label-sheet {
                width: {{ $sheetWidthCss }} !important;
                min-height: {{ $sheetMinHeightCss }};
                margin: 0;
                padding: {{ $isNarrow ? '10px 8px' : '16px 14px' }};
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body class="{{ $isNarrow ? 'label-paper-narrow' : 'label-paper-4x6' }}">
    <div class="no-print">
        @if(session('success'))
        <p style="margin-bottom: 12px; padding: 10px; background: #E8EEF8; color: #0A2D6F; border-radius: 6px; font-size: 14px;">{{ session('success') }}</p>
        @endif

        <button type="button" onclick="printLabel();">🖨️ Imprimir etiqueta</button>
        <a href="{{ route('preregistrations.show', $preregistration->id) }}">← Volver al preregistro</a>
        @if($isNarrow)
        <p class="no-print-hint">Esta vista es para papel <strong>2.25×4&nbsp;pulgadas</strong>. Si usa rollo <strong>4×6</strong>, abra <a href="{{ route('preregistrations.control-label', $preregistration->id) }}">etiqueta de control 4×6</a>.</p>
        @else
        <p class="no-print-hint">En el diálogo elija <strong>tamaño 4×6&nbsp;pulgadas</strong>. Si su impresora solo ofrece 2.25×4, use <a href="{{ route('preregistrations.control-label', ['id' => $preregistration->id, 'format' => 'narrow']) }}">etiqueta de control 2.25×4</a>.</p>
        @endif
    </div>

    <div class="label-sheet">
        <div class="ctrl-brand">
            <img src="{{ $providerLogo }}" alt="PrimeTrack Group" class="ctrl-logo">
            <div class="ctrl-address">8307 NW 68th St<br>Miami, FL 33166</div>
        </div>
        <div class="ctrl-warehouse">{{ $preregistration->warehouse_code }}</div>
        <div class="ctrl-barcode">
            <canvas id="barcode-{{ $preregistration->id }}-control" class="barcode-canvas" data-barcode="{{ $preregistration->warehouse_code }}"></canvas>
        </div>
        <div class="ctrl-date">{{ $dt ? $dt->format('d/m/Y') : '—' }}</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        var labelNarrow = {{ $isNarrow ? 'true' : 'false' }};
        document.querySelectorAll('.barcode-canvas').forEach(function(el) {
            if (!el.dataset.barcode) return;
            JsBarcode(el, el.dataset.barcode, labelNarrow ? {
                format: 'CODE128',
                width: 1.4,
                height: 48,
                displayValue: false,
                margin: 0
            } : {
                format: 'CODE128',
                width: 2.6,
                height: 88,
                displayValue: false,
                margin: 0
            });
        });

        window.__barcodesReady = true;
        function printLabel() {
            if (window.__barcodesReady) {
                window.print();
                return;
            }
            setTimeout(printLabel, 200);
        }
        @if(!empty($labelAutoprint))
        (function () {
            function run() {
                printLabel();
            }
            if (document.readyState === 'complete') {
                setTimeout(run, 400);
            } else {
                window.addEventListener('load', function () {
                    setTimeout(run, 400);
                });
            }
        })();
        @endif
    </script>
</body>
</html>
