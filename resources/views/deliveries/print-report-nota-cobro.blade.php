<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $notaCode = (isset($deliveryNote) && $deliveryNote) ? 'NO-' . str_pad(ltrim($deliveryNote->code, 'BCH-'), 5, '0', STR_PAD_LEFT) : 'NO-00000';
        $pageTitle = 'Nota de cobro ' . $notaCode . ' - CH Logistics';
    @endphp
    <title>{{ $pageTitle }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, body * { font-family: Arial, Helvetica, sans-serif; }
        body {
            font-size: 11pt;
            color: #222;
            line-height: 1.4;
            max-width: 210mm;
            margin: 0 auto;
            padding: 24px 20px 32px;
            background: #fff;
        }

        .no-print { margin-bottom: 20px; }
        .no-print .btn-print { background: #1e40af; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .no-print .btn-back { margin-left: 12px; color: #1e40af; font-weight: 500; text-decoration: none; font-size: 14px; }
        .print-hint { font-size: 12px; color: #666; margin-top: 8px; }

        /* Header: dos columnas separadas por línea vertical (estilo CH Logistics, como en la foto) */
        .nc-header {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin-bottom: 24px;
        }
        .nc-header-left {
            flex: 0 0 auto;
            padding-right: 20px;
            border-right: 1px solid #444;
        }
        .nc-logo-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .nc-logo-shield {
            width: 48px;
            height: 48px;
            border: 2px solid #1e40af;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
        }
        .nc-logo-shield .nc-c { color: #ea580c; }
        .nc-logo-shield .nc-h { color: #1e40af; }
        .nc-logo-logistics {
            font-size: 20px;
            font-weight: 700;
            color: #1e40af;
        }
        .nc-company-address { font-size: 11px; color: #374151; margin-bottom: 2px; }
        .nc-company-phone { font-size: 11px; color: #374151; }

        .nc-header-right {
            flex: 1;
            padding-left: 20px;
        }
        .nc-title-row { display: flex; align-items: baseline; gap: 12px; margin-bottom: 8px; }
        .nc-title { font-size: 20px; font-weight: 700; color: #222; }
        .nc-nota-code { font-size: 18px; font-weight: 700; color: #222; }
        .nc-delivery-address { font-size: 11px; color: #374151; margin-bottom: 4px; }
        .nc-delivery-phone { font-size: 11px; color: #374151; }

        /* Datos del cliente y fecha / forma de pago */
        .nc-mid-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px 40px;
            margin-bottom: 24px;
        }
        .nc-client-title { font-size: 12px; font-weight: 700; color: #222; margin-bottom: 8px; }
        .nc-client-name { font-size: 13px; font-weight: 600; color: #222; margin-bottom: 4px; }
        .nc-client-phone { font-size: 12px; color: #374151; margin-bottom: 4px; }
        .nc-client-address { font-size: 12px; color: #374151; }

        .nc-date-block { text-align: right; }
        .nc-date { font-size: 18px; font-weight: 700; color: #222; margin-bottom: 8px; }
        .nc-payment-label { font-size: 11px; color: #374151; margin-bottom: 2px; }
        .nc-payment-value { font-size: 12px; color: #222; }

        /* Tabla WRH, Detalle, Servicio, Precio, Total — solo líneas horizontales y fondo gris en header (como en la foto) */
        .nc-table-wrap { margin: 16px 0; overflow-x: auto; }
        .nc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }
        .nc-table th, .nc-table td {
            padding: 10px 12px;
            text-align: left;
            border: none;
            border-bottom: 1px solid #d1d5db;
        }
        .nc-table thead th {
            font-weight: 700;
            background: #e5e7eb;
            color: #374151;
            border-top: 1px solid #9ca3af;
            border-bottom: 1px solid #9ca3af;
        }
        .nc-table .col-wrh { width: 10%; }
        .nc-table .col-detalle { width: 35%; }
        .nc-table .col-servicio { width: 20%; }
        .nc-table .col-precio, .nc-table .col-total { width: 15%; text-align: right; }

        /* Subtotal / Total y firma */
        .nc-totals-row {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 24px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }
        .nc-subtotal, .nc-total { font-size: 12pt; }
        .nc-total { font-weight: 700; }

        .nc-signature-block { margin-top: 28px; }
        .nc-signature-line { border-bottom: 1px solid #222; height: 28px; margin-top: 4px; min-width: 200px; }
        .nc-signature-caption { font-size: 10px; color: #374151; margin-top: 6px; }

        /* DATOS DE ENTREGA — título centrado, misma tabla minimalista */
        .nc-delivery-section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #222;
            margin: 28px 0 12px;
            text-align: center;
        }
        .nc-delivery-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }
        .nc-delivery-table th, .nc-delivery-table td {
            padding: 10px 12px;
            text-align: left;
            border: none;
            border-bottom: 1px solid #d1d5db;
        }
        .nc-delivery-table thead th {
            font-weight: 700;
            background: #e5e7eb;
            color: #374151;
            border-top: 1px solid #9ca3af;
            border-bottom: 1px solid #9ca3af;
        }

        /* Footer: QR y disclaimer */
        .nc-footer {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: flex-start;
            gap: 24px;
        }
        .nc-qr-wrap {
            flex: 0 0 80px;
            width: 80px;
            height: 80px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #6b7280;
        }
        .nc-disclaimer-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #222; margin-bottom: 6px; }
        .nc-disclaimer-text { font-size: 10px; color: #374151; line-height: 1.4; text-transform: uppercase; }

        @page { size: A4; margin: 18mm; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 12px 0 24px; background: #fff; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()" class="btn-print">Imprimir / Guardar PDF</button>
        <a href="{{ route('deliveries.index', session('deliveries_index_agency_id') ? ['agency_id' => session('deliveries_index_agency_id')] : []) }}" class="btn-back">← Volver a Entregas</a>
        <p class="print-hint">Nota de cobro CH Logistics (encomienda familiar). Al imprimir, desmarque «Encabezados y pies de página».</p>
    </div>

    {{-- Header: empresa (izq) | Nota de cobro + dirección entrega (der) — como en la foto --}}
    <header class="nc-header">
        <div class="nc-header-left">
            <div class="nc-logo-box">
                <div class="nc-logo-shield"><span class="nc-c">C</span><span class="nc-h">H</span></div>
                <span class="nc-logo-logistics">Logistics</span>
            </div>
            <div class="nc-company-address">8307 NW 68TH ST Miami FL 33166</div>
            <div class="nc-company-phone">+505 8928 8565</div>
        </div>
        <div class="nc-header-right">
            <div class="nc-title-row">
                <span class="nc-title">Nota de cobro</span>
                <span class="nc-nota-code">{{ $notaCode }}</span>
            </div>
            <div class="nc-delivery-address">{{ $deliveryAddress ?? ($agency->address ?? 'Km 11 carretera Masaya, de la entrada al Colegio Pureza, 100 mts al Este.') }}</div>
            <div class="nc-delivery-phone">{{ $deliveryPhone ?? ($agency->phone ?? '+505 8928 8565') }}</div>
        </div>
    </header>

    {{-- Datos del cliente (izq) | Fecha y forma de pago (der) --}}
    <div class="nc-mid-section">
        <div>
            <div class="nc-client-title">Datos del cliente</div>
            <div class="nc-client-name">{{ $clientName ?? '—' }}</div>
            <div class="nc-client-phone">{{ $clientPhone ?? '—' }}</div>
            <div class="nc-client-address">{{ $clientAddress ?? '—' }}</div>
        </div>
        <div class="nc-date-block">
            <div class="nc-date">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</div>
            <div class="nc-payment-label">Forma de pago:</div>
            <div class="nc-payment-value">Transferencia / Zelle / Efectivo</div>
        </div>
    </div>

    {{-- Tabla: WRH, Detalle, Servicio, Precio, Total --}}
    <div class="nc-table-wrap">
        <table class="nc-table">
            <thead>
                <tr>
                    <th class="col-wrh">WRH</th>
                    <th class="col-detalle">Detalle</th>
                    <th class="col-servicio">Servicio</th>
                    <th class="col-precio">Precio</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                @if($deliveries->isEmpty())
                    <tr><td colspan="5" style="text-align: center; color: #6b7280;">No hay ítems.</td></tr>
                @else
                    @php $subtotal = 0; @endphp
                    @foreach($deliveries as $d)
                    @php
                        $p = $d->preregistration;
                        $detalle = $p->description ? Str::limit($p->description, 40) : ($p->label_name ?? '—');
                        $servicio = $p->service_type == 'AIR' ? 'Aéreo' : 'Maritimo';
                    @endphp
                    <tr>
                        <td>{{ $p->warehouse_code ?? '—' }}</td>
                        <td>{{ $detalle }}</td>
                        <td>{{ $servicio }}</td>
                        <td class="col-precio">—</td>
                        <td class="col-total">—</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div class="nc-totals-row">
        <span class="nc-subtotal">Subtotal: —</span>
        <span class="nc-total">Total: —</span>
    </div>

    <div class="nc-signature-block">
        <div class="nc-signature-line">&nbsp;</div>
        <div class="nc-signature-caption">Firma de recibido del cliente</div>
    </div>

    <div class="nc-delivery-section-title">DATOS DE ENTREGA</div>
    <table class="nc-delivery-table">
        <thead>
            <tr>
                <th>Nombre receptor</th>
                <th>Dirección de entrega</th>
                <th>Telefono</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $agencyName ?? ($agency->name ?? '—') }}</td>
                <td>{{ $agency->address ?? '—' }}</td>
                <td>{{ $agency->phone ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <footer class="nc-footer">
        <div class="nc-qr-wrap">QR</div>
        <div>
            <div class="nc-disclaimer-title">DISCLAIMER:</div>
            <div class="nc-disclaimer-text">ESTÁ PROHIBIDO ENVIAR ARMAS DE FUEGO, DROGAS, ARMAS Y CUALQUIER SUSTANCIA ILEGAL A TRAVÉS DE ENVIOS CH LOGISTICS, Y CUALQUIER VIOLACIÓN DE ESTA POLÍTICA RESULTARÁ EN POSIBLES ACCIONES LEGALES CONTRA EL CLIENTE.</div>
        </div>
    </footer>
</body>
</html>
