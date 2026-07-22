<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $consolidation->code }} · Reporte de saco · BCH Tracking</title>
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
        .h-code { font-size: 14pt; font-weight: 800; color: #0d9488; font-family: ui-monospace, monospace; letter-spacing: 0.05em; }
        .h-code-label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.06em; }

        .lbl { font-size: 7.5pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; display: block; }
        .val { font-size: 9.5pt; color: #0f172a; font-weight: 600; margin-top: 1px; }

        /* Banda de metadatos */
        .strip {
            display: grid; grid-template-columns: repeat(5, 1fr) auto;
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
        tr.unmatched td { background: #fffbeb; color: #92400e; }

        .notes-row {
            margin-top: 6px; padding: 4px 0;
            border-top: 1px solid #e5e7eb;
            font-size: 7.5pt; color: #64748b;
        }
        .notes-row strong { color: #475569; text-transform: uppercase; letter-spacing: 0.04em; }

        /* Espaciador flexible para empujar el pie hacia el final */
        .spacer { flex-grow: 1; }

        /* Disclaimer + firmas */
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
        <a href="{{ route('consolidations.show', $consolidation->id) }}" class="btn-back">← Volver al saco</a>
        <p class="print-hint">Al imprimir, desmarque «Encabezados y pies de página» para no incluir la URL.</p>
    </div>

    @php
        $statusLabels = [
            'OPEN' => 'Abierto',
            'SENT' => 'Enviado',
            'RECEIVED' => 'Recibido',
            'CANCELLED' => 'Cancelado',
        ];
        $displayTimezone = config('app.display_timezone') ?: 'America/New_York';
        $serviceWord = $consolidation->service_type === 'AIR' ? 'AÉREO' : 'MARÍTIMO';
        $createdAt = $consolidation->created_at?->timezone($displayTimezone);
        $sentAt = $consolidation->sent_at?->timezone($displayTimezone);
    @endphp

    <div class="doc">
        <header class="h">
            <div class="h-left">
                <img src="{{ asset('images/bch-tracking-logo.png') }}" alt="BCH Tracking" class="h-logo">
                <div>
                    <div class="h-company">BCH TRACKING</div>
                    <div class="h-address">
                        8307 NW 68TH ST · Miami, FL 33166
                    </div>
                </div>
            </div>
            <div class="h-right">
                <div class="h-title">REPORTE DE SACO</div>
                <div>
                    <div class="h-code-label">Número</div>
                    <div class="h-code">{{ $consolidation->code }}</div>
                </div>
            </div>
        </header>

        <div class="strip">
            <div class="strip-cell">
                <span class="lbl">Fecha creación</span>
                <div class="val">{{ $createdAt?->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
            <div class="strip-cell">
                <span class="lbl">Estado</span>
                <div class="val">{{ $statusLabels[$consolidation->status] ?? $consolidation->status }}</div>
            </div>
            <div class="strip-cell">
                <span class="lbl">Enviado</span>
                <div class="val">{{ $sentAt?->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
            <div class="strip-cell">
                <span class="lbl">Origen / Destino</span>
                <div class="val">MIA / NIC</div>
            </div>
            <div class="strip-cell">
                <span class="lbl">Cantidad de bultos</span>
                <div class="val">{{ $report['expected_packages'] }}</div>
            </div>
            <div class="strip-service">{{ $serviceWord }}</div>
        </div>

        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="center" style="width: 4%;">#</th>
                        <th style="width: 12%;">Warehouse</th>
                        <th style="width: 20%;">Tracking</th>
                        <th style="width: 25%;">Cliente</th>
                        <th style="width: 22%;">Agencia</th>
                        <th class="num" style="width: 9%;">Peso lbs</th>
                        <th class="num" style="width: 8%;">Pie³</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consolidation->items as $i => $item)
                        @php
                            $package = $item->preregistration;
                            $weight = $package?->verified_weight_lbs ?? $package?->intake_weight_lbs;
                            $trackingValue = $package?->tracking_external ?? $item->unmatched_code;
                        @endphp
                        <tr class="{{ $package ? '' : 'unmatched' }}">
                            <td class="center">{{ $i + 1 }}</td>
                            <td class="mono">{{ $package?->warehouse_code ?? '—' }}</td>
                            <td class="mono">{{ $trackingValue ? \Illuminate\Support\Str::limit($trackingValue, 24) : '—' }}</td>
                            <td>{{ $package ? (\Illuminate\Support\Str::limit($package->label_name ?? '', 28) ?: '—') : 'Sin preregistro asociado' }}</td>
                            <td>{{ $package?->agency?->name ? \Illuminate\Support\Str::limit($package->agency->name, 24) : '—' }}</td>
                            <td class="num">{{ $weight !== null ? number_format((float) $weight, 2) : '—' }}</td>
                            <td class="num">{{ $package?->cubic_feet !== null ? number_format((float) $package->cubic_feet, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="center" style="padding: 14px 0; color: #94a3b8;">Este saco todavía no contiene ítems.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="spacer"></div>

        @if($consolidation->notes)
        <div class="notes-row">
            <strong>Nota:</strong> {{ $consolidation->notes }}
        </div>
        @endif

        <div class="foot">
            <div class="disclaimer">
                Reporte del contenido del saco al momento de su generación. Los pesos corresponden al peso verificado o, en su defecto, al peso de ingreso registrado en Miami. Las líneas resaltadas corresponden a códigos escaneados sin preregistro asociado.
            </div>
            <div class="sigs">
                <div class="sig">
                    <div class="sig-line">&nbsp;</div>
                    <div class="sig-caption">Preparado por — BCH Tracking</div>
                </div>
                <div class="sig">
                    <div class="sig-line">&nbsp;</div>
                    <div class="sig-caption">Recibido — Destino</div>
                </div>
            </div>
        </div>

        <div class="meta-foot">
            Generado el {{ now()->timezone($displayTimezone)->format('d/m/Y H:i') }} · {{ $consolidation->code }}
        </div>
    </div>
</body>
</html>
