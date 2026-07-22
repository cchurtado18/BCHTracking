<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta Saco - {{ $consolidation->code }} - BCH Tracking</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #f3f4f6;
            padding: 16px;
        }
        .label-sheet {
            width: 4in;
            min-height: 6in;
            max-width: 100%;
            margin: 0 auto;
            background: white;
            border: none;
            border-radius: 0;
            padding: 14px 16px;
            box-shadow: none;
        }
        .label-sheet .label-header {
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #0d9488;
        }
        .label-sheet .label-header .company {
            font-size: 14px;
            font-weight: 700;
            color: #0d9488;
            letter-spacing: 0.02em;
        }
        .label-sheet .saco-code {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-align: center;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            color: #111;
        }
        .label-sheet .field {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 10px;
        }
        .label-sheet .value {
            font-size: 14px;
            font-weight: 600;
            color: #111;
            margin-top: 2px;
        }
        .no-print {
            text-align: center;
            margin-bottom: 16px;
        }
        .no-print-hint {
            font-size: 13px;
            color: #6b7280;
            margin-top: 8px;
            margin-bottom: 0;
        }
        .no-print button {
            background: #0d9488;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .no-print button:hover { background: #0f766e; }
        .no-print a {
            display: inline-block;
            margin-left: 12px;
            color: #4b5563;
            font-size: 14px;
        }
        .edit-prompt {
            margin: 16px auto 0;
            max-width: 4in;
            padding: 14px 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
            text-align: left;
        }
        .edit-prompt-title { margin: 0 0 4px; font-size: 14px; font-weight: 700; color: #0f172a; }
        .edit-prompt-sub { margin: 0 0 12px; font-size: 12px; line-height: 1.45; color: #64748b; }
        .edit-prompt-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .edit-prompt-btn {
            flex: 1 1 140px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #0f766e;
            background: #f0fdfa;
            border: 1px solid rgba(13, 148, 136, 0.4);
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .edit-prompt-btn:hover {
            background: #ccfbf1;
            border-color: #0d9488;
            color: #0f766e;
        }
        .edit-prompt-btn--primary {
            color: #fff;
            background: #0d9488;
            border-color: #0d9488;
        }
        .edit-prompt-btn--primary:hover {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
        }

        @page {
            size: 4in 6in;
            margin: 0;
        }

        @media print {
            html, body {
                width: 4in;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .label-sheet {
                width: 4in !important;
                min-height: 6in;
                max-width: none;
                margin: 0;
                border: none;
                box-shadow: none;
                page-break-after: always;
                box-sizing: border-box;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        @if(session('success'))
        <p style="margin-bottom: 12px; padding: 10px; background: #d1fae5; color: #065f46; border-radius: 6px; font-size: 14px;">{{ session('success') }}</p>
        @endif
        <button type="button" onclick="window.print();" class="no-print-btn">🖨️ Imprimir etiqueta del saco</button>
        <p class="no-print-hint">Papel <strong>4×6&nbsp;pulgadas</strong>, escala <strong>100&nbsp;%</strong>, sin márgenes. En la impresora térmica, el driver debe coincidir con ese tamaño.</p>

        @if($consolidation->status === 'OPEN')
        <div class="edit-prompt">
            <p class="edit-prompt-title">¿Cómo quieres seguir editando este saco?</p>
            <p class="edit-prompt-sub">El saco está <strong>abierto</strong>. Puedes seguir agregando paquetes por escaneo o seleccionándolos manualmente. También puedes eliminar cualquier paquete si te equivocaste.</p>
            <div class="edit-prompt-actions">
                <a href="{{ route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan']) }}" class="edit-prompt-btn edit-prompt-btn--primary">▦ Seguir escaneando</a>
                <a href="{{ route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'select']) }}" class="edit-prompt-btn">☰ Seleccionar manualmente</a>
            </div>
        </div>
        @endif

        <a href="{{ route('consolidations.show', $consolidation->id) }}">← Volver al saco</a>
        <a href="{{ route('consolidations.report', $consolidation->id) }}" target="_blank">Reporte detallado</a>
    </div>

    <div class="label-sheet">
        <div class="label-header">
            <div class="company">BCH Tracking - Saco</div>
        </div>
        <div class="field">Código del saco</div>
        <div class="saco-code">{{ $consolidation->code }}</div>

        <div class="field">Tipo de servicio</div>
        <div class="value">{{ $consolidation->service_type === 'AIR' ? 'Aéreo' : 'Marítimo' }}</div>

        <div class="field">Items en el saco</div>
        <div class="value">{{ $consolidation->items->count() }}</div>

        <div class="field">Peso total (lbs)</div>
        <div class="value">{{ number_format($report['total_lbs'] ?? 0, 2) }}</div>

        <div class="field">Fecha de creación</div>
        <div class="value">{{ $consolidation->created_at->format('d/m/Y H:i') }}</div>

        @if($consolidation->notes)
        <div class="field">Notas</div>
        <div class="value" style="font-size: 12px;">{{ Str::limit($consolidation->notes, 80) }}</div>
        @endif
    </div>
</body>
</html>
