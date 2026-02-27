<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $pageTitle = (isset($deliveryNote) && $deliveryNote) ? 'Nota ' . e($deliveryNote->code) . ' - BCH Tracking' : 'Comprobante de entrega - BCH Tracking';
    @endphp
    <title>{{ $pageTitle }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, body * { font-family: Arial, Helvetica, sans-serif; }
        body {
            font-size: 11pt;
            color: #222;
            line-height: 1.5;
            max-width: 210mm;
            margin: 0 auto;
            padding: 24px 20px 32px;
            background: #f8fafc;
        }

        /* Marco del documento: elegante y diferenciado */
        .doc-frame {
            max-width: 100%;
            margin: 0 auto 32px;
            padding: 36px 40px;
            background: #fff;
            border: 2px solid #0d9488;
            border-radius: 8px;
            position: relative;
            box-shadow: 0 4px 20px rgba(13, 148, 136, 0.1);
        }
        .doc-frame::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 1px solid rgba(13, 148, 136, 0.25);
            border-radius: 4px;
            pointer-events: none;
        }

        .no-print { margin-bottom: 20px; }
        .no-print .btn-print { background: #0d9488; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .no-print .btn-back { margin-left: 12px; color: #0d9488; font-weight: 500; text-decoration: none; font-size: 14px; }
        .print-hint { font-size: 12px; color: #666; margin-top: 8px; }

        /* Header: limpio, una línea de acento */
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            padding-bottom: 12px;
            margin-bottom: 24px;
            border-bottom: 3px solid #0d9488;
        }
        .doc-header-brand { display: flex; align-items: center; gap: 12px; }
        .doc-header-logo { height: 44px; width: auto; max-width: 140px; object-fit: contain; display: block; }
        .doc-header-titles { }
        .doc-header-company { font-size: 18px; font-weight: 700; color: #222; }
        .doc-header-type { font-size: 11px; color: #666; margin-top: 2px; letter-spacing: 0.02em; }
        .doc-header-meta { text-align: right; }
        .doc-header-ref { font-size: 16px; font-weight: 700; color: #222; }
        .doc-header-date { font-size: 11px; color: #666; margin-top: 4px; }

        /* Bloques de datos: sin cajas grises, solo líneas y espaciado */
        .doc-info { display: grid; grid-template-columns: 1fr 1fr; gap: 32px 40px; margin-bottom: 28px; }
        @media (max-width: 560px) { .doc-info { grid-template-columns: 1fr; } }
        .doc-info-block { }
        .doc-info-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #0d9488;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }
        .doc-info-row { margin-bottom: 8px; }
        .doc-info-row:last-child { margin-bottom: 0; }
        .doc-info-label { font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 2px; }
        .doc-info-value { font-size: 12px; color: #222; font-weight: 500; }

        /* Tabla: líneas finas, mucho aire */
        .doc-section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #0d9488;
            margin-bottom: 12px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }
        .doc-table-wrap { overflow-x: auto; margin: 16px 0; }
        .doc-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        .doc-table th,
        .doc-table td {
            padding: 10px 12px;
            text-align: left;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
        }
        .doc-table thead th {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #444;
            background: #f8fafc;
            border-bottom: 2px solid #0d9488;
        }
        .doc-table tbody tr:hover { background: #fafafa; }
        .doc-table .col-num, .doc-table .col-peso { text-align: right; }
        .doc-table .col-code { font-size: 10pt; }
        .doc-table .total-row td {
            font-weight: 700;
            background: #f8fafc;
            border-top: 2px solid #0d9488;
            border-bottom: none;
            padding: 12px;
        }
        .doc-summary {
            font-size: 12pt;
            font-weight: 600;
            color: #222;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }

        /* Firmas: minimalistas */
        .doc-signatures { margin-top: 40px; padding-top: 24px; border-top: 2px solid #e5e7eb; }
        .doc-signatures-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #666; margin-bottom: 16px; }
        .doc-signature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px 48px; }
        @media (max-width: 560px) { .doc-signature-grid { grid-template-columns: 1fr; } }
        .doc-signature-box { }
        .doc-signature-line { border-bottom: 1px solid #222; height: 28px; margin-top: 4px; }
        .doc-signature-caption { font-size: 10px; color: #666; margin-top: 6px; }
        .doc-signature-bch { margin-top: 28px; }
        .doc-signature-bch .doc-signature-grid { max-width: 280px; }

        .doc-footer { margin-top: 32px; padding-top: 12px; font-size: 10px; color: #999; text-align: center; }
        .doc-empty { color: #666; font-style: italic; padding: 16px 0; }

        @page { size: A4; margin: 18mm; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 12px 0 24px; background: #fff; max-width: none; }
            .doc-frame {
                margin: 0 auto;
                padding: 28px 32px;
                border-radius: 0;
                box-shadow: none;
                border: 2px solid #0d9488;
            }
            .doc-frame::before {
                top: 8px; left: 8px; right: 8px; bottom: 8px;
                border-radius: 0;
                border: 1px solid rgba(13, 148, 136, 0.35);
            }
            .doc-header { border-bottom-color: #0d9488; }
            .doc-table thead th { background: #f0fdfa; border-bottom-color: #0d9488; }
            .doc-table .total-row td { background: #f0fdfa; border-top-color: #0d9488; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()" class="btn-print">Imprimir / Guardar PDF</button>
        <a href="{{ route('deliveries.index', session('deliveries_index_agency_id') ? ['agency_id' => session('deliveries_index_agency_id')] : []) }}" class="btn-back">← Volver a Entregas</a>
        <p class="print-hint">Al imprimir, desmarque «Encabezados y pies de página» para no incluir la URL.</p>
    </div>

    <div class="doc-frame">
    <header class="doc-header">
        <div class="doc-header-brand">
            <img src="{{ asset('images/bch-tracking-logo.png') }}" alt="BCH Tracking" class="doc-header-logo">
            <div class="doc-header-titles">
                <div class="doc-header-company">BCH Tracking</div>
                <div class="doc-header-type">Comprobante de entrega</div>
            </div>
        </div>
        <div class="doc-header-meta">
            @if(isset($deliveryNote) && $deliveryNote)
                <div class="doc-header-ref">{{ $deliveryNote->code }}</div>
            @elseif(isset($deliveryNotesInReport) && $deliveryNotesInReport->isNotEmpty())
                <div class="doc-header-ref">{{ $deliveryNotesInReport->pluck('code')->join(', ') }}</div>
            @else
                <div class="doc-header-ref">—</div>
            @endif
            <div class="doc-header-date">{{ \Carbon\Carbon::parse($date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</div>
        </div>
    </header>

    <div class="doc-info">
        <div class="doc-info-block">
            <div class="doc-info-title">Agencia que retira</div>
            <div class="doc-info-row">
                <span class="doc-info-label">Agencia</span>
                <span class="doc-info-value">{{ $agencyName }}</span>
            </div>
            @if(isset($agency) && $agency)
                @if($agency->code)
                <div class="doc-info-row">
                    <span class="doc-info-label">Código</span>
                    <span class="doc-info-value">{{ $agency->code }}</span>
                </div>
                @endif
                @if($agency->address)
                <div class="doc-info-row">
                    <span class="doc-info-label">Dirección</span>
                    <span class="doc-info-value">{{ $agency->address }}</span>
                </div>
                @endif
                @if($agency->department)
                <div class="doc-info-row">
                    <span class="doc-info-label">Departamento</span>
                    <span class="doc-info-value">{{ $agency->department }}</span>
                </div>
                @endif
                @if($agency->phone)
                <div class="doc-info-row">
                    <span class="doc-info-label">Teléfono</span>
                    <span class="doc-info-value">{{ $agency->phone }}</span>
                </div>
                @endif
            @endif
        </div>
        <div class="doc-info-block">
            <div class="doc-info-title">Datos de la entrega</div>
            <div class="doc-info-row">
                <span class="doc-info-label">Fecha</span>
                <span class="doc-info-value">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
            </div>
            @if(!empty($retiradoPor))
            <div class="doc-info-row">
                <span class="doc-info-label">Retirado por</span>
                <span class="doc-info-value">{{ $retiradoPor }}</span>
            </div>
            @endif
            @if(!empty($retiradoCedula))
            <div class="doc-info-row">
                <span class="doc-info-label">Identificación</span>
                <span class="doc-info-value">{{ $retiradoCedula }}</span>
            </div>
            @endif
            @if(!empty($retiradoTelefono))
            <div class="doc-info-row">
                <span class="doc-info-label">Teléfono</span>
                <span class="doc-info-value">{{ $retiradoTelefono }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="doc-section-title">Paquetes entregados</div>
    @if($deliveries->isEmpty())
        <p class="doc-empty">No hay entregas registradas para esta nota o agencia en esta fecha.</p>
    @else
        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th>Cliente</th>
                        <th>Warehouse</th>
                        <th>Bulto</th>
                        <th>Tracking</th>
                        <th>Servicio</th>
                        <th class="col-peso">Peso (lbs)</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalLbs = 0; @endphp
                    @foreach($deliveries as $i => $d)
                    @php
                        $p = $d->preregistration;
                        $peso = $p->verified_weight_lbs ?? $p->intake_weight_lbs ?? 0;
                        $totalLbs += (float) $peso;
                    @endphp
                    <tr>
                        <td class="col-num">{{ $i + 1 }}</td>
                        <td>{{ $p->label_name ?? '—' }}</td>
                        <td class="col-code">{{ $p->warehouse_code ?? '—' }}</td>
                        <td>{{ ($p->bultos_total && $p->bultos_total > 1 && $p->bulto_index) ? $p->bulto_index . '/' . $p->bultos_total : '—' }}</td>
                        <td class="col-code">{{ $p->tracking_external ?? '—' }}</td>
                        <td>{{ $p->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</td>
                        <td class="col-peso">{{ $peso ? number_format($peso, 2) : '—' }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6"><strong>Total: {{ $deliveries->count() }} paquete{{ $deliveries->count() !== 1 ? 's' : '' }}</strong></td>
                        <td class="col-peso"><strong>{{ $totalLbs > 0 ? number_format($totalLbs, 2) : '—' }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="doc-summary">{{ $deliveries->count() }} paquete{{ $deliveries->count() !== 1 ? 's' : '' }} entregado{{ $deliveries->count() !== 1 ? 's' : '' }}@if($totalLbs > 0) · {{ number_format($totalLbs, 2) }} lbs total @endif</p>
    @endif

    <div class="doc-signatures">
        <p class="doc-signatures-title">Recibido conforme</p>
        <div class="doc-signature-grid">
            <div class="doc-signature-box">
                <div class="doc-signature-line">&nbsp;</div>
                <div class="doc-signature-caption">Firma</div>
            </div>
            <div class="doc-signature-box">
                <div class="doc-signature-line">&nbsp;</div>
                <div class="doc-signature-caption">Nombre completo</div>
            </div>
        </div>
        <div class="doc-signature-bch">
            <p class="doc-signatures-title">Entregado por BCH Tracking</p>
            <div class="doc-signature-grid">
                <div class="doc-signature-box">
                    <div class="doc-signature-line">&nbsp;</div>
                    <div class="doc-signature-caption">Firma autorizada</div>
                </div>
            </div>
        </div>
    </div>

    <footer class="doc-footer">
        Generado {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }} — BCH Tracking
    </footer>
    </div>
</body>
</html>
