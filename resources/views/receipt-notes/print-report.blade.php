<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $receiptNote->code }} · Recibo de almacén · BCH Tracking</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, body * { font-family: Arial, Helvetica, sans-serif; }
        body {
            font-size: 9.5pt;
            color: #1f2937;
            line-height: 1.35;
            background: #f1f5f9;
            padding: 16px 0;
        }
        .doc {
            background: #fff;
            width: 8.5in;          /* Letter */
            min-height: 11in;
            margin: 0 auto;
            padding: 9mm 10mm 8mm;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
            display: flex; flex-direction: column;
        }

        .no-print { width: 8.5in; margin: 0 auto 12px; padding: 0 10mm; }
        .no-print .btn-print { background: #0d9488; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .no-print .btn-back { margin-left: 12px; color: #0d9488; font-weight: 500; text-decoration: none; font-size: 14px; }
        .print-hint { font-size: 11px; color: #6b7280; margin-top: 6px; }

        /* Header */
        .h {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 14px; padding-bottom: 7px; margin-bottom: 7px;
            border-bottom: 2px solid #0d9488;
        }
        .h-left { display: flex; gap: 9px; align-items: flex-start; }
        .h-logo { height: 38px; width: auto; max-width: 140px; object-fit: contain; }
        .h-company { font-size: 12px; font-weight: 800; color: #0d9488; letter-spacing: 0.02em; }
        .h-address { font-size: 8pt; color: #4b5563; line-height: 1.3; margin-top: 1px; }
        .h-right { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 3px; }
        .h-title { font-size: 14pt; font-weight: 800; color: #0f172a; letter-spacing: 0.04em; }
        .h-meta { display: flex; gap: 10px; align-items: center; }
        .h-qr { width: 56px; height: 56px; flex-shrink: 0; border: 1px solid #e5e7eb; padding: 2px; background: #fff; }
        .h-qr img { display: block; width: 100%; height: 100%; }
        .h-code { font-size: 14pt; font-weight: 800; color: #0d9488; font-family: ui-monospace, monospace; letter-spacing: 0.05em; }
        .h-code-label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.06em; }

        /* Cuadro de cuenta + receptor */
        .row1 {
            display: grid; grid-template-columns: 1.2fr 1fr; gap: 0;
            border: 1px solid #cbd5e1; margin-bottom: 6px;
        }
        .row1-cell-l { padding: 6px 9px; border-right: 1px solid #cbd5e1; }
        .row1-cell-r { padding: 0; }
        .lbl { font-size: 7.5pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; display: block; }
        .val { font-size: 9.5pt; color: #0f172a; font-weight: 600; margin-top: 1px; }
        .val-strong { font-size: 10.5pt; font-weight: 800; color: #0f172a; }

        .mini-grid { display: grid; grid-template-columns: 1fr 1fr; }
        .mini-cell {
            padding: 4px 9px;
            border-bottom: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
        }
        .mini-cell:nth-child(2n) { border-right: none; }
        .mini-cell:nth-last-child(-n+2) { border-bottom: none; }
        .mini-cell-wide { grid-column: 1 / -1; }

        /* Banda de metadatos */
        .strip {
            display: grid; grid-template-columns: repeat(4, 1fr) auto;
            border: 1px solid #cbd5e1; margin-bottom: 6px;
        }
        .strip-cell {
            padding: 4px 9px;
            border-right: 1px solid #cbd5e1;
        }
        .strip-cell:last-child { border-right: none; }
        .strip-service {
            padding: 0 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13pt; font-weight: 800; color: #fff;
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            letter-spacing: 0.05em; min-width: 90px;
        }

        /* Descripción */
        .desc {
            border: 1px solid #cbd5e1; padding: 4px 9px; margin-bottom: 6px;
            display: flex; gap: 9px; align-items: baseline;
        }
        .desc-label { font-size: 7.5pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; flex-shrink: 0; }
        .desc-value { font-size: 9pt; color: #0f172a; }

        /* Tabla principal */
        .tbl-wrap { border: 1px solid #cbd5e1; }
        table.tbl { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        table.tbl thead { display: table-header-group; }
        table.tbl thead th {
            padding: 4px 5px; text-align: left; font-size: 7.5pt; font-weight: 700;
            color: #1f2937; text-transform: uppercase; letter-spacing: 0.04em;
            background: #f1f5f9; border-bottom: 1.5px solid #0d9488;
            border-right: 1px solid #cbd5e1;
        }
        table.tbl thead th:last-child { border-right: none; }
        table.tbl tbody tr { page-break-inside: avoid; }
        table.tbl tbody td {
            padding: 3.5px 5px; border-top: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        table.tbl tbody td:last-child { border-right: none; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .center { text-align: center; }
        .mono { font-family: ui-monospace, monospace; font-size: 8pt; }
        .svc-badge {
            display: inline-block; padding: 1px 7px; border-radius: 10px;
            font-size: 7.5pt; font-weight: 700; letter-spacing: 0.04em;
        }
        .svc-air { background: #dbeafe; color: #1e40af; }
        .svc-sea { background: #fef3c7; color: #92400e; }

        /* Pie: notas + totales */
        .foot-row {
            display: grid; grid-template-columns: 1fr 195px; gap: 7px; margin-top: 6px;
        }
        .notes-box {
            border: 1px solid #cbd5e1; padding: 5px 9px;
            font-size: 8.5pt; color: #1f2937;
        }
        .notes-box .lbl { margin-bottom: 2px; }
        .totals { border: 1px solid #cbd5e1; }
        .totals-row {
            display: grid; grid-template-columns: 1fr auto;
            padding: 4px 9px; border-bottom: 1px solid #e5e7eb;
            align-items: baseline;
        }
        .totals-row:last-child { border-bottom: none; }
        .totals-row-strong { background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%); }
        .totals-label { font-size: 8pt; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.04em; }
        .totals-value { font-size: 10pt; font-weight: 800; color: #0f172a; text-align: right; }

        /* Espaciador flexible para empujar el pie hacia el final */
        .spacer { flex-grow: 1; }

        /* Disclaimer + firmas mini */
        .foot {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 6px;
            padding-top: 5px; border-top: 1px solid #e5e7eb;
            page-break-inside: avoid;
        }
        .disclaimer { font-size: 7pt; color: #6b7280; line-height: 1.4; }
        .sigs { display: flex; gap: 12px; justify-content: flex-end; align-items: flex-end; }
        .sig { min-width: 115px; text-align: center; }
        .sig-line { border-bottom: 1px solid #1f2937; height: 20px; }
        .sig-caption { font-size: 7.5pt; color: #6b7280; margin-top: 3px; }
        .meta-foot { margin-top: 4px; font-size: 7pt; color: #94a3b8; text-align: right; }

        @page { size: letter portrait; margin: 0; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
            .doc { width: 8.5in; min-height: 11in; border: none; box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()" class="btn-print">Imprimir / Guardar PDF</button>
        <a href="{{ route('receipt-notes.batch', ['receipt_note_id' => $receiptNote->id]) }}" class="btn-back">← Volver a la nota</a>
        <p class="print-hint">Al imprimir, desmarque «Encabezados y pies de página» para no incluir la URL.</p>
    </div>

    @php
        $items = $receiptNote->preregistrations;
        $uniqueLabelNames = $items->pluck('label_name')->filter()->unique()->values();
        $singleLabelName = $uniqueLabelNames->count() === 1 ? $uniqueLabelNames->first() : null;
        $serviceTypes = $items->pluck('service_type')->filter()->unique();
        $serviceWord = $serviceTypes->count() === 1
            ? ($serviceTypes->first() === 'AIR' ? 'AÉREO' : ($serviceTypes->first() === 'SEA' ? 'MARÍTIMO' : '—'))
            : 'MIXTO';
        $agency = $receiptNote->agency;
        $createdAt = $receiptNote->created_at?->timezone(config('app.display_timezone'));
        $descriptions = $items->pluck('description')->filter()->map(fn($d) => trim($d))->unique()->take(10)->implode(' · ');
        $totalKg = $totalLbs * 0.453592;
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=0&data=' . urlencode($receiptNote->code);
    @endphp

    <div class="doc">
        <header class="h">
            <div class="h-left">
                <img src="{{ asset('images/bch-tracking-logo.png') }}" alt="BCH Tracking" class="h-logo">
                <div>
                    <div class="h-company">BCH TRACKING</div>
                    <div class="h-address">
                        Miami Warehouse · FL, USA<br>
                        Tel: (305) 000-0000
                    </div>
                </div>
            </div>
            <div class="h-right">
                <div class="h-title">RECIBO DE ALMACÉN</div>
                <div class="h-meta">
                    <div>
                        <div class="h-code-label">Número</div>
                        <div class="h-code">{{ $receiptNote->code }}</div>
                    </div>
                    <div class="h-qr" title="{{ $receiptNote->code }}">
                        <img src="{{ $qrUrl }}" alt="QR">
                    </div>
                </div>
            </div>
        </header>

        <div class="row1">
            <div class="row1-cell-l">
                <span class="lbl">Recibido para</span>
                <div class="val-strong" style="margin-top: 4px;">{{ $agency?->name ?? '—' }}</div>
                @if($agency?->address)
                <div class="val" style="font-weight: 500; font-size: 10pt; margin-top: 4px;">{{ $agency->address }}</div>
                @endif
                @if($agency?->department)
                <div class="val" style="font-weight: 500; font-size: 10pt;">{{ $agency->department }} · Nicaragua</div>
                @endif
                @if($agency?->phone)
                <div class="val" style="font-weight: 500; font-size: 10pt;">Tel: {{ $agency->phone }}</div>
                @endif
            </div>
            <div class="row1-cell-r">
                <div class="mini-grid">
                    <div class="mini-cell">
                        <span class="lbl">Cuenta</span>
                        <span class="val">{{ \Illuminate\Support\Str::upper($agency?->name ?? '—') }}</span>
                    </div>
                    <div class="mini-cell">
                        <span class="lbl">Fecha</span>
                        <span class="val">{{ $createdAt?->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>
                    <div class="mini-cell">
                        <span class="lbl">Bultos</span>
                        <span class="val">{{ $items->count() }}</span>
                    </div>
                    <div class="mini-cell">
                        <span class="lbl">Peso bruto</span>
                        <span class="val">{{ number_format($totalLbs, 2) }} LBS</span>
                    </div>
                    <div class="mini-cell">
                        <span class="lbl">Volumen</span>
                        <span class="val">{{ number_format($totalFt3, 2) }} CF</span>
                    </div>
                    <div class="mini-cell">
                        <span class="lbl">Servicio</span>
                        <span class="val">{{ $serviceWord }}</span>
                    </div>
                    <div class="mini-cell mini-cell-wide">
                        <span class="lbl">Referencia (destinatario final)</span>
                        <span class="val">{{ $singleLabelName ?? 'Varios — ver detalle' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="strip">
            <div class="strip-cell">
                <span class="lbl">Recibido de</span>
                <div class="val">{{ $receiptNote->delivered_by }}</div>
            </div>
            <div class="strip-cell">
                <span class="lbl">ID / Cédula</span>
                <div class="val">{{ $receiptNote->delivered_by_id_number ?: '—' }}</div>
            </div>
            <div class="strip-cell">
                <span class="lbl">Teléfono</span>
                <div class="val">{{ $receiptNote->delivered_by_phone ?: '—' }}</div>
            </div>
            <div class="strip-cell">
                <span class="lbl">Origen / Destino</span>
                <div class="val">MIA / NIC</div>
            </div>
            <div class="strip-service">{{ $serviceWord }}</div>
        </div>

        @if($descriptions)
        <div class="desc">
            <span class="desc-label">Descripción</span>
            <span class="desc-value">{{ $descriptions }}</span>
        </div>
        @endif

        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="center" style="width: 3%;">#</th>
                        <th style="width: 12%;">Warehouse</th>
                        <th style="width: 16%;">Tracking</th>
                        <th class="center" style="width: 6%;">Bulto</th>
                        <th style="width: 11%;">Dimensión</th>
                        <th class="num" style="width: 8%;">Peso lbs</th>
                        <th class="num" style="width: 6%;">Ft³</th>
                        <th class="center" style="width: 10%;">Servicio</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $i => $p)
                        @php
                            $svc = $p->service_type;
                            $svcLabel = $svc === 'AIR' ? 'AÉREO' : ($svc === 'SEA' ? 'MARÍTIMO' : '—');
                            $svcClass = $svc === 'AIR' ? 'svc-air' : ($svc === 'SEA' ? 'svc-sea' : '');
                        @endphp
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td class="mono">{{ $p->warehouse_code ?? '—' }}</td>
                            <td class="mono">{{ \Illuminate\Support\Str::limit($p->tracking_external, 22) ?: '—' }}</td>
                            <td class="center">{{ ($p->bultos_total && $p->bultos_total > 1 && $p->bulto_index) ? $p->bulto_index . '/' . $p->bultos_total : '1' }}</td>
                            <td>{{ $p->dimension ?: '—' }}</td>
                            <td class="num">{{ $p->intake_weight_lbs ? number_format((float) $p->intake_weight_lbs, 2) : '—' }}</td>
                            <td class="num">{{ $p->cubic_feet ? number_format((float) $p->cubic_feet, 2) : '—' }}</td>
                            <td class="center"><span class="svc-badge {{ $svcClass }}">{{ $svcLabel }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($p->label_name, 24) ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="center" style="padding: 14px 0; color: #94a3b8;">Esta nota aún no tiene bultos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="spacer"></div>

        <div class="foot-row">
            <div class="notes-box">
                <span class="lbl">Notas</span>
                {{ $receiptNote->notes ?: '—' }}
            </div>
            <div class="totals">
                <div class="totals-row">
                    <span class="totals-label">Bultos</span>
                    <span class="totals-value">{{ $items->count() }}</span>
                </div>
                <div class="totals-row">
                    <span class="totals-label">Libras</span>
                    <span class="totals-value">{{ number_format($totalLbs, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span class="totals-label">Kilos</span>
                    <span class="totals-value">{{ number_format($totalKg, 2) }}</span>
                </div>
                <div class="totals-row totals-row-strong">
                    <span class="totals-label">Pies cúbicos</span>
                    <span class="totals-value">{{ number_format($totalFt3, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="foot">
            <div class="disclaimer">
                Es responsabilidad del cliente declarar el contenido y embalar adecuadamente. BCH Tracking no se responsabiliza por daños en empaque, contenido no declarado, frágiles sin protección o artículos prohibidos. Pesos y dimensiones son preliminares.
            </div>
            <div class="sigs">
                <div class="sig">
                    <div class="sig-line">&nbsp;</div>
                    <div class="sig-caption">Entregado por (cliente)</div>
                </div>
                <div class="sig">
                    <div class="sig-line">&nbsp;</div>
                    <div class="sig-caption">Recibido — BCH Tracking</div>
                </div>
            </div>
        </div>

        <div class="meta-foot">
            Generado el {{ $createdAt?->format('d/m/Y H:i') }} por {{ $receiptNote->receivedBy?->name ?? 'BCH Tracking' }} · {{ $receiptNote->code }}
        </div>
    </div>
</body>
</html>
