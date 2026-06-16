{{--
    Partial: comprobante de entrega "Salida Producto" – BCH TRACKING.
    Una sola página tamaño carta. Si los items no caben, la tabla continúa
    en una página adicional manteniendo juntos los bloques de retirante/firmas.

    Variables esperadas:
      $deliveries, $agency (opcional), $agencyName, $date, $deliveryNote,
      $retiradoPor, $retiradoCedula, $retiradoTelefono
--}}
@php
    $deliveryNote = $deliveryNote ?? null;

    $deliveriesAir = $deliveries->filter(fn($d) => optional($d->preregistration)->service_type === 'AIR');
    $deliveriesSea = $deliveries->filter(fn($d) => optional($d->preregistration)->service_type === 'SEA');
    $groupedAir = $deliveriesAir->groupBy(fn($d) => optional($d->preregistration)->warehouse_code ?? '—');
    $groupedSea = $deliveriesSea->groupBy(fn($d) => optional($d->preregistration)->warehouse_code ?? '—');

    $calcLine = function ($group) {
        $first = optional($group->first())->preregistration;
        $peso = $group->sum(function ($d) {
            $p = $d->preregistration;
            if (! $p) return 0;
            return (float) ($p->verified_weight_lbs ?? $p->intake_weight_lbs ?? 0);
        });
        // Pies cúbicos: sumamos sólo los paquetes que tienen dimensión registrada.
        // Cuando ninguno la tiene (típico courier aéreo), $hasCft queda en false y mostramos "—".
        $cft = 0.0;
        $hasCft = false;
        foreach ($group as $d) {
            $cf = optional($d->preregistration)->cubic_feet;
            if ($cf !== null) {
                $hasCft = true;
                $cft += (float) $cf;
            }
        }
        return [
            'code'    => optional($first)->warehouse_code ?? '—',
            'first'   => $first,
            'peso'    => $peso,
            'piezas'  => $group->count(),
            'cant'    => 1,
            'cft'     => $cft,
            'has_cft' => $hasCft,
        ];
    };

    $linesAir = $groupedAir->map($calcLine)->values();
    $linesSea = $groupedSea->map($calcLine)->values();

    $totalAirCant   = $linesAir->sum('cant');
    $totalAirPeso   = $linesAir->sum('peso');
    $totalAirPiezas = $linesAir->sum('piezas');
    $totalAirCft    = $linesAir->sum('cft');
    $totalAirHasCft = $linesAir->contains(fn($l) => $l['has_cft'] === true);
    $totalSeaCant   = $linesSea->sum('cant');
    $totalSeaPeso   = $linesSea->sum('peso');
    $totalSeaPiezas = $linesSea->sum('piezas');
    $totalSeaCft    = $linesSea->sum('cft');
    $totalSeaHasCft = $linesSea->contains(fn($l) => $l['has_cft'] === true);
    $grandCant   = $totalAirCant + $totalSeaCant;
    $grandPeso   = $totalAirPeso + $totalSeaPeso;
    $grandPiezas = $totalAirPiezas + $totalSeaPiezas;
    $grandCft    = $totalAirCft + $totalSeaCft;
    $grandHasCft = $totalAirHasCft || $totalSeaHasCft;

    $docNumber = $deliveryNote
        ? $deliveryNote->code
        : 'S' . str_pad((string) (\Carbon\Carbon::parse($date)->format('His')), 5, '0', STR_PAD_LEFT);

    // OC # = número de factura ingresado en la entrega.
    // Todos los deliveries de una misma nota comparten la misma factura.
    $firstDelivery = $deliveries->first();
    $invoiceNumber = $firstDelivery?->invoice_number;
    $ocNumber = filled($invoiceNumber) ? $invoiceNumber : '';
    $printDateLong = \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    $printDateFooter = \Carbon\Carbon::now()->format('d/m/Y H:i');
    $documentDate = \Carbon\Carbon::parse($date)->format('d/m/Y');

    // En el encabezado mostramos la AGENCIA destino (a quién se entrega).
    // El nombre de la persona que retira aparece más abajo, en el bloque de datos del retirante.
    $clientName = strtoupper($agencyName ?? '—');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($deliveryNote ? 'Nota '.$deliveryNote->code : 'Comprobante de entrega') . ' - BCH TRACKING' }}</title>
    <style>
        /* === Tamaño carta (Letter): 8.5in x 11in = 21.59cm x 27.94cm === */
        @page { size: letter portrait; margin: 0.5in 0.5in 0.5in 0.5in; }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, body * { font-family: Arial, Helvetica, sans-serif; }
        body { font-size: 9pt; color: #111; line-height: 1.3; background: #fff; }

        /* Sheet siempre como flex column para empujar el bloque de firmas al fondo */
        .doc-sheet {
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        .doc-main { flex: 0 0 auto; }
        .doc-spacer { flex: 1 1 auto; min-height: 0.25in; }

        @media screen {
            body { background: #e2e8f0; padding: 16px 0; }
            .doc-sheet {
                width: 8.5in;
                min-height: 11in;
                margin: 0 auto;
                padding: 0.5in;
                box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            }
        }
        @media print {
            body { padding: 0; background: #fff; }
            .no-print { display: none !important; }
            .doc-sheet {
                width: auto;
                min-height: 10in; /* 11in - 0.5in*2 de @page margin */
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }

        .no-print { width: 8.5in; max-width: 100%; margin: 0 auto; padding: 12px 0 14px; }
        .btn-print { background: #1e40af; color: #fff; border: none; padding: 7px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; }
        .btn-back { margin-left: 12px; color: #1e40af; font-weight: 500; text-decoration: none; font-size: 13px; }
        .print-hint { font-size: 11px; color: #475569; margin-top: 6px; }

        /* Header */
        .doc-top-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
        .doc-branch { font-size: 13pt; font-weight: 700; letter-spacing: 0.06em; }
        .doc-print-date { font-size: 9pt; }

        .doc-info { margin-bottom: 2px; }
        .doc-doc-type { font-size: 10pt; margin-bottom: 4px; }
        .doc-meta { font-size: 9pt; display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 4px; }
        .doc-meta-cell { display: flex; gap: 6px; align-items: baseline; }
        .doc-meta-label { font-weight: 400; }
        .doc-meta-value { font-weight: 600; }
        .doc-client { font-size: 11pt; font-weight: 700; letter-spacing: 0.03em; margin-top: 2px; }

        /* Tabla limpia: sin bordes en filas, sólo en encabezado y totales */
        table.products { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9pt; }
        table.products thead { display: table-header-group; }
        table.products th, table.products td {
            padding: 4px 6px;
            border: none;
            text-align: left;
            vertical-align: top;
        }
        table.products thead th {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            font-weight: 700;
        }
        table.products td.num, table.products th.num { text-align: right; }
        table.products td.col-code { font-family: ui-monospace, monospace; }

        .col-w-code { width: 10%; }
        .col-w-desc { width: 50%; }
        .col-w-cant { width: 7%; }
        .col-w-peso { width: 12%; }
        .col-w-piezas { width: 10%; }
        .col-w-total { width: 11%; }

        .group-header td {
            font-weight: 700;
            border: none;
            padding-top: 8px;
            padding-bottom: 2px;
        }
        table.products tr.group-total td {
            font-weight: 700;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
        }
        table.products tr.grand-total td {
            font-weight: 700;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 5px 6px;
        }

        /* Bloque inferior: REF + retirante + firmas + disclaimer (no se corta) */
        .footer-block { page-break-inside: avoid; }
        .ref-note { font-weight: 600; font-size: 9.5pt; margin-bottom: 0.25in; }

        .retirer-block { padding-left: 38%; font-size: 10pt; margin-bottom: 0.5in; }
        .retirer-row { margin-bottom: 0.13in; display: flex; align-items: baseline; gap: 8px; }
        .retirer-label { min-width: 70px; font-weight: 600; }
        .retirer-value { flex: 1; min-height: 16px; padding-bottom: 2px; }

        .signatures-block { display: flex; gap: 0.6in; justify-content: space-between; align-items: flex-end; }
        .sig-col { flex: 1; min-width: 0; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin: 0 12px; }
        .sig-caption { font-size: 8.5pt; margin-top: 4px; line-height: 1.2; }
        .sig-caption-top { font-size: 8.5pt; margin: 0 12px 4px; line-height: 1.2; }

        .disclaimer { margin-top: 0.25in; font-size: 7pt; line-height: 1.45; text-align: justify; }

        .page-footer { margin-top: 0.15in; text-align: right; font-size: 8pt; }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()" class="btn-print">Imprimir / Guardar PDF</button>
        <a href="{{ route('deliveries.index', session('deliveries_index_agency_id') ? ['agency_id' => session('deliveries_index_agency_id')] : []) }}" class="btn-back">← Volver a Entregas</a>
        <p class="print-hint">Imprimir en tamaño Carta (Letter, 8.5" × 11"), una hoja por cara. En el diálogo de impresión: márgenes «Predeterminados» y desmarque «Encabezados y pies de página».</p>
    </div>

    <section class="doc-sheet">
        <div class="doc-main">
        <div class="doc-top-row">
            <div class="doc-branch">BCH TRACKING</div>
            <div class="doc-print-date">{{ $printDateLong }}</div>
        </div>

        <div class="doc-info">
            <div class="doc-doc-type">Salida Producto</div>
            <div class="doc-meta">
                <div class="doc-meta-cell"><span class="doc-meta-label"># Documento:</span><span class="doc-meta-value">{{ $docNumber }}</span></div>
                <div class="doc-meta-cell"><span class="doc-meta-label">Bodega:</span><span class="doc-meta-value">BODEGA PRINCIPAL</span></div>
                <div class="doc-meta-cell"><span class="doc-meta-label">Fecha:</span><span class="doc-meta-value">{{ $documentDate }}</span></div>
            </div>
            <div class="doc-client">{{ $clientName }}</div>
        </div>

        <table class="products">
            <thead>
                <tr>
                    <th class="col-w-code">CÓDIGO</th>
                    <th class="col-w-desc">DESCRIPCIÓN</th>
                    <th class="col-w-cant num">CANT</th>
                    <th class="col-w-peso num">PESO(LBS)</th>
                    <th class="col-w-piezas num">PIEZAS</th>
                    <th class="col-w-total num">PIES³</th>
                </tr>
            </thead>
            <tbody>
                @if($linesAir->isNotEmpty())
                <tr class="group-header"><td colspan="6">AEREO</td></tr>
                @foreach($linesAir as $line)
                @php
                    $p = $line['first'];
                    $descRaw = $p && $p->description ? \Illuminate\Support\Str::limit($p->description, 30, '') : 'A/V';
                    $desc = 'AEREO - ' . strtoupper($descRaw) . ' - ' . ($p->label_name ?? '—');
                @endphp
                <tr>
                    <td class="col-code">{{ $line['code'] }}</td>
                    <td>{{ $desc }}</td>
                    <td class="num">{{ $line['cant'] }}</td>
                    <td class="num">{{ number_format($line['peso'], 2) }}</td>
                    <td class="num">{{ number_format($line['piezas'], 2) }}</td>
                    <td class="num">{{ $line['has_cft'] ? number_format($line['cft'], 2) : '—' }}</td>
                </tr>
                @endforeach
                <tr class="group-total">
                    <td colspan="2">TOTAL&nbsp;&nbsp;AEREO</td>
                    <td class="num">{{ $totalAirCant }}</td>
                    <td class="num">{{ number_format($totalAirPeso, 2) }}</td>
                    <td class="num">{{ number_format($totalAirPiezas, 2) }}</td>
                    <td class="num">{{ $totalAirHasCft ? number_format($totalAirCft, 2) : '—' }}</td>
                </tr>
                @endif

                @if($linesSea->isNotEmpty())
                <tr class="group-header"><td colspan="6">MARITIMO</td></tr>
                @foreach($linesSea as $line)
                @php
                    $p = $line['first'];
                    $descRaw = $p && $p->description ? \Illuminate\Support\Str::limit($p->description, 30, '') : 'A/V';
                    $desc = 'MARITIMO - ' . strtoupper($descRaw) . ' - ' . ($p->label_name ?? '—');
                @endphp
                <tr>
                    <td class="col-code">{{ $line['code'] }}</td>
                    <td>{{ $desc }}</td>
                    <td class="num">{{ $line['cant'] }}</td>
                    <td class="num">{{ number_format($line['peso'], 2) }}</td>
                    <td class="num">{{ number_format($line['piezas'], 2) }}</td>
                    <td class="num">{{ $line['has_cft'] ? number_format($line['cft'], 2) : '—' }}</td>
                </tr>
                @endforeach
                <tr class="group-total">
                    <td colspan="2">TOTAL&nbsp;&nbsp;MARITIMO</td>
                    <td class="num">{{ $totalSeaCant }}</td>
                    <td class="num">{{ number_format($totalSeaPeso, 2) }}</td>
                    <td class="num">{{ number_format($totalSeaPiezas, 2) }}</td>
                    <td class="num">{{ $totalSeaHasCft ? number_format($totalSeaCft, 2) : '—' }}</td>
                </tr>
                @endif

                @if($linesAir->isEmpty() && $linesSea->isEmpty())
                <tr><td colspan="6" style="text-align:center; color:#6b7280;">No hay ítems.</td></tr>
                @endif

                <tr class="grand-total">
                    <td colspan="2">TOTAL DEL REPORTE</td>
                    <td class="num">{{ $grandCant }}</td>
                    <td class="num">{{ number_format($grandPeso, 2) }}</td>
                    <td class="num">{{ number_format($grandPiezas, 2) }}</td>
                    <td class="num">{{ $grandHasCft ? number_format($grandCft, 2) : '—' }}</td>
                </tr>
            </tbody>
        </table>
        </div>{{-- /.doc-main --}}

        <div class="doc-spacer"></div>

        <div class="footer-block">
            <div class="ref-note">REF: Entrega de paquetes@if(filled($ocNumber)) OC #{{ $ocNumber }}@endif</div>

            <div class="retirer-block">
                <div class="retirer-row"><span class="retirer-label">Nombre:</span><span class="retirer-value">{{ $retiradoPor ?? '' }}</span></div>
                <div class="retirer-row"><span class="retirer-label">Cédula:</span><span class="retirer-value">{{ $retiradoCedula ?? '-' }}</span></div>
                <div class="retirer-row"><span class="retirer-label">Teléfono:</span><span class="retirer-value">{{ $retiradoTelefono ?? '-' }}</span></div>
            </div>

            <div class="signatures-block">
                <div class="sig-col">
                    <div class="sig-line"></div>
                    <div class="sig-caption">Entregado por</div>
                </div>
                <div class="sig-col">
                    <div class="sig-line"></div>
                    <div class="sig-caption">Recibido por</div>
                </div>
            </div>

            <div class="disclaimer">
                EL LLENADO DE ESTOS CAMPOS ES — "OBLIGATORIO" —. POR FAVOR COMPRUEBE QUE LOS PAQUETES, SALIDA DE PRODUCTOS Y FACTURA (S) (COPIA — DOCUMENTO VÁLIDO AL COBRO), SON RECIBIDOS A ENTERA SATISFACCIÓN DEL CLIENTE, PROPIETARIO, RESPONSABLE O PERSONA DELEGADA POR ESTE, PARA SU RETIRO (PREVIA COMUNICACIÓN POR CHAT WHATSAPP, QUE INCLUYA FOTO O IMAGEN DE CÉDULA).
            </div>

            <div class="page-footer">{{ $printDateFooter }}</div>
        </div>
    </section>
</body>
</html>
