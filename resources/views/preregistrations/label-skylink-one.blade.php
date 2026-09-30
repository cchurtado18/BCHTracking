@php
    $labelAutoprint = request()->boolean('autoprint');
    $labelFormat = $labelFormat ?? '4x6';
    $isNarrow = $labelFormat === 'narrow';
    $pageSizeCss = $isNarrow ? '2.25in 4in' : '4in 6in';
    $sheetWidthCss = $isNarrow ? '2.25in' : '4in';
    $sheetMinHeightCss = $isNarrow ? '4in' : '6in';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta - {{ $preregistration->warehouse_code }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f4f6;
            padding: 16px;
        }

        /* Contenedor 4x6 */
        .label-sheet {
            width: 4in;
            min-height: 6in;
            margin: 0 auto;
            background: #fff;
            border: none;
            border-radius: 0;
            padding: 12px 14px;
        }

        .sl-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 1px minmax(0, 1fr);
            align-items: center;
            gap: 10px;
            min-height: 84px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #111;
        }
        .sl-header-provider {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        .sl-header-provider img {
            max-width: 198px;
            max-height: 52px;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: left center;
        }
        .sl-header-address {
            font-size: 12px;
            line-height: 1.25;
            font-weight: 700;
            color: #111;
            letter-spacing: 0.01em;
        }
        .sl-header-rule {
            width: 1px;
            align-self: stretch;
            background: #111;
        }
        .sl-header-agency {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 84px;
        }
        .sl-header-agency-frame {
            width: 100%;
            height: 84px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sl-header-agency-frame img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
        }
        .sl-header-agency-name {
            font-size: 18px;
            font-weight: 900;
            color: #111;
            text-align: center;
            line-height: 1.1;
            padding: 0 4px;
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
            margin-bottom: 0;
            /* Permite que el tracking largo no se salga de la etiqueta */
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .sl-tracking-cubic {
            font-size: 12px;
            font-weight: 900;
            color: #111;
            margin-top: 4px;
        }
        .sl-meta-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 6px 0 2px;
            padding: 8px 0;
            border-top: 1px solid #111;
            border-bottom: 1px solid #e5e7eb;
        }
        .sl-meta-col + .sl-meta-col {
            border-left: 1px solid #e5e7eb;
            padding-left: 10px;
        }
        .sl-bulto-badge {
            display: inline-block;
            margin-left: 8px;
            font-size: 11px;
            font-weight: 900;
            color: #111;
            border: 1px solid #111;
            border-radius: 999px;
            padding: 2px 8px;
            background: #fff;
            letter-spacing: 0.02em;
            line-height: 1;
            vertical-align: middle;
        }

        .sl-destination-title {
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
        .sl-grid-cell {
            padding: 12px 8px;
        }
        .sl-grid-cell + .sl-grid-cell {
            border-left: 1px solid #e5e7eb;
        }
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
        .sl-grid-value.service-air { color: #0A2D6F; }
        .sl-grid-value.service-sea { color: #1e40af; }
        .sl-grid-value.service-cft { color: #16794C; }

        /* Badge AIR/SEA para distinguir en blanco y negro (por forma) */
        .sl-service-badge {
            display: inline-block;
            border: 2px solid #111;
            border-radius: 10px;
            padding: 4px 10px;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.06em;
            line-height: 1.05;
            color: #111 !important; /* Evita depender de color */
        }
        .sl-service-badge.service-air { border-style: dashed; }
        .sl-service-badge.service-sea { border-style: solid; }

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

        /* Código grande + barcode */
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
        .sl-barcode-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 6px;
        }
        .sl-barcode-row {
            display: inline-flex;
            align-items: flex-end;
            justify-content: center;
            gap: 14px;
        }
        .sl-service-mark-large {
            display: inline-block;
            font-size: 60px;
            font-weight: 900;
            line-height: 0.9;
            color: #111;
            letter-spacing: 0.02em;
            margin-bottom: 16px;
            margin-left: 6px;
        }

        /* RECIBIDO EN ALMACÉN */
        .sl-reception {
            margin-top: 2px;
            background: #f3f4f6;
            border-radius: 8px;
            padding: 9px 10px 7px;
            text-align: center;
        }
        .sl-reception-title {
            font-size: 14px;
            font-weight: 900;
            color: #1e40af;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .sl-reception-divider {
            height: 1px;
            background: #d1d5db;
            margin: 0 auto 6px;
            width: 80%;
        }
        .sl-reception-datetime {
            font-size: 14px;
            font-weight: 800;
            color: #1f2937;
            white-space: nowrap;
        }
        .sl-review-text {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            margin-top: 8px;
            font-weight: 600;
        }
        .sl-care-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 6px;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
        }
        .sl-care {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            color: #111;
            text-align: center;
        }
        .sl-care svg { width: 22px; height: 22px; }
        .sl-care span {
            font-size: 7px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            line-height: 1.15;
        }

        /* Fecha mini dentro de la celda de CÓDIGO */
        .sl-code-mini-date {
            margin-top: 6px;
            font-size: 13px;
            font-weight: 900;
            color: #111827;
            letter-spacing: 0.01em;
            text-align: center;
        }

        .no-print { text-align: center; margin-bottom: 16px; }

        /*
         * 2.25"×4" — mismo ancho que ofrecen muchos drivers «LABEL»; rejilla en una columna para no recortar.
         * Si su rollo es 4×6, abra la versión normal (sin ?format=narrow) y elija 4×6 en el diálogo.
         */
        .label-paper-narrow .label-sheet {
            width: 2.25in;
            min-height: 4in;
            padding: 6px 8px;
        }
        .label-paper-narrow .sl-header { min-height: 0; gap: 6px; margin-bottom: 6px; padding-bottom: 6px; }
        .label-paper-narrow .sl-header-provider img { max-width: 118px; max-height: 30px; }
        .label-paper-narrow .sl-header-address { font-size: 8px; }
        .label-paper-narrow .sl-header-agency { min-height: 44px; }
        .label-paper-narrow .sl-header-agency-frame { height: 44px; }
        .label-paper-narrow .sl-header-agency-name { font-size: 11px; }
        .label-paper-narrow .sl-bulto-badge { font-size: 9px; padding: 1px 6px; margin-left: 4px; }
        .label-paper-narrow .sl-warehouse-title { font-size: 10px; margin-bottom: 4px; }
        .label-paper-narrow .sl-warehouse-code { font-size: 22px; letter-spacing: 0.05em; }
        .label-paper-narrow .sl-barcode-row { flex-wrap: wrap; gap: 4px; justify-content: center; }
        .label-paper-narrow .sl-service-mark-large { font-size: 28px; margin-bottom: 4px; margin-left: 0; }
        .label-paper-narrow .sl-tracking-global-label { font-size: 9px; }
        .label-paper-narrow .sl-tracking-global-value { font-size: 11px; line-height: 1.15; }
        .label-paper-narrow .sl-meta-2 {
            grid-template-columns: 1fr;
            gap: 4px;
            margin-bottom: 6px;
        }
        .label-paper-narrow .sl-meta-col + .sl-meta-col { border-left: none; padding-left: 0; border-top: 1px solid #e5e7eb; padding-top: 4px; }
        .label-paper-narrow .sl-tracking-cubic { font-size: 10px; }
        .label-paper-narrow .sl-destination-title { font-size: 10px; margin-top: 0; }
        .label-paper-narrow .sl-care-row { gap: 3px; margin-top: 6px; padding-top: 6px; }
        .label-paper-narrow .sl-care svg { width: 14px; height: 14px; }
        .label-paper-narrow .sl-care span { font-size: 6px; }
        .label-paper-narrow .sl-destination-name { font-size: 14px; }
        .label-paper-narrow .sl-grid-3 {
            grid-template-columns: 1fr;
            margin-top: 8px;
        }
        .label-paper-narrow .sl-grid-cell + .sl-grid-cell { border-left: none; border-top: 1px solid #e5e7eb; }
        .label-paper-narrow .sl-grid-cell { padding: 8px 6px; }
        .label-paper-narrow .sl-grid-title { font-size: 10px; margin-bottom: 4px; }
        .label-paper-narrow .sl-grid-value { font-size: 12px; }
        .label-paper-narrow .sl-service-badge { font-size: 12px; padding: 2px 8px; }
        .label-paper-narrow .sl-code-mini-date { font-size: 11px; margin-top: 4px; }
        .label-paper-narrow .sl-description-title { font-size: 9px; margin-top: 6px; }
        .label-paper-narrow .sl-description-value { font-size: 11px; margin-bottom: 4px; }
        .label-paper-narrow .sl-review-text { font-size: 9px; margin-top: 4px; }

        /* Tamaño de hoja explícito (debe coincidir con «Tamaño del papel» en el diálogo) */
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
                max-width: none;
                margin: 0;
                padding: 8px 10px;
                border: none;
                border-radius: 0;
                box-sizing: border-box;
                page-break-inside: avoid;
                box-shadow: none;
            }

            /* Compactar sin usar transform (el scale alteraba medidas reales en térmicas). */
            .sl-header { min-height: 80px; margin-bottom: 6px; padding-bottom: 8px; }
            .sl-header-provider img { max-width: 198px; max-height: 50px; }
            .sl-header-address { font-size: 12px; }
            .sl-header-agency { min-height: 80px; }
            .sl-header-agency-frame { height: 80px; }
            .sl-warehouse-code { font-size: 34px; }
            .sl-service-mark-large { font-size: 50px; margin-bottom: 10px; }
            .sl-destination-name { font-size: 18px; }
            .sl-grid-3 { margin-top: 10px; }
            .sl-grid-cell { padding: 8px 6px; }
            .sl-grid-value { font-size: 15px; }
            .sl-description-title { margin-top: 10px; }
            .sl-description-value { font-size: 15px; margin-bottom: 6px; }
            .sl-tracking-global-value { font-size: 17px; margin-bottom: 6px; }
            .sl-review-text { font-size: 11px; margin-top: 6px; }
        }
    </style>
</head>
<body class="{{ $isNarrow ? 'label-paper-narrow' : 'label-paper-4x6' }}">
    <div class="no-print">
        @if(session('success'))
        <p style="margin-bottom: 12px; padding: 10px; background: #E8EEF8; color: #0A2D6F; border-radius: 6px; font-size: 14px;">{{ session('success') }}</p>
        @endif
        @if(session('warning'))
        <p style="margin-bottom: 12px; padding: 10px; background: #fef3c7; color: #92400e; border-radius: 6px; font-size: 14px;">{{ session('warning') }}</p>
        @endif

        <button type="button" onclick="printLabel();" style="background:#2563eb;color:#fff;border:none;padding:12px 24px;font-size:16px;border-radius:8px;cursor:pointer;font-weight:600;">🖨️ Imprimir etiqueta</button>

        @if(!empty($dropoffNextStep) && !empty($dropoffTotal))
            <p style="margin-top: 14px;">
                <a href="{{ route('preregistrations.create') }}" style="display: inline-block; padding: 8px 14px; background: #0A2D6F; color: #fff; border-radius: 6px; font-weight: 600; text-decoration: none;">
                    Continuar con el siguiente bulto ({{ $dropoffNextStep }}/{{ $dropoffTotal }})
                </a>
            </p>
            <p style="margin-top: 6px; font-size: 13px; color: #6b7280;">Después de imprimir esta etiqueta, completa los datos del bulto {{ $dropoffNextStep }}.</p>
        @endif

        <p style="margin-top: 12px; font-size: 14px;">
            <a href="{{ route('preregistrations.show', $preregistration->id) }}" style="color:#2563eb;text-decoration:none;font-weight:600;">← Volver al preregistro</a>
        </p>
        @if($isNarrow)
        <p style="margin-top: 10px; font-size: 12px; color: #6b7280; max-width: 52ch; margin-left: auto; margin-right: auto;">Esta vista es para papel <strong>2.25×4&nbsp;pulgadas</strong> (lo que muestra su driver). En impresión: mismo tamaño en «Tamaño del papel», escala <strong>100&nbsp;%</strong> o «Predeterminado», márgenes <strong>ninguno</strong>. Si en realidad usa rollo <strong>4×6</strong>, cierre esta pestaña y abra <a href="{{ route('preregistrations.label', $preregistration->id) }}">etiqueta 4×6</a>.</p>
        @else
        <p style="margin-top: 10px; font-size: 12px; color: #6b7280; max-width: 52ch; margin-left: auto; margin-right: auto;">En el diálogo elija <strong>tamaño 4×6&nbsp;pulgadas</strong> (no 2.25×4). Escala <strong>100&nbsp;%</strong>, márgenes <strong>cero</strong>, sin ajustar a página. Si su impresora <strong>solo ofrece 2.25×4</strong>, use <a href="{{ route('preregistrations.label', ['id' => $preregistration->id, 'format' => 'narrow']) }}">etiqueta 2.25×4</a>.</p>
        @endif
    </div>
    

    @include('preregistrations.partials.label-skylink-one-sheet', ['preregistration' => $preregistration])

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        var labelNarrow = {{ $isNarrow ? 'true' : 'false' }};
        document.querySelectorAll('.barcode-canvas').forEach(function(el) {
            if (!el.dataset.barcode) return;
            JsBarcode(el, el.dataset.barcode, labelNarrow ? {
                format: 'CODE128',
                width: 1,
                height: 28,
                displayValue: true,
                fontSize: 8,
                margin: 0
            } : {
                format: 'CODE128',
                width: 2.2,
                height: 44,
                displayValue: true,
                fontSize: 13,
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

