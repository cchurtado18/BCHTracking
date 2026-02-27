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
            border: 2px solid #111;
            border-radius: 8px;
            padding: 14px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .label-sheet {
                width: 4in;
                min-height: 6in;
                max-width: none;
                margin: 0;
                border: 1px solid #000;
                box-shadow: none;
                page-break-after: always;
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
        <p class="no-print-hint">Seleccione la impresora de etiquetas en el cuadro de impresión.</p>
        <a href="{{ route('consolidations.show', $consolidation->id) }}">← Volver al saco</a>
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
