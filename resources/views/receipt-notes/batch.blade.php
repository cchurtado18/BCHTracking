@extends('layouts.app')

@section('title', $receiptNote ? 'Editar nota '.$receiptNote->code : 'Nueva nota de recepción')

@section('content')
<div class="rnb-page">
    <header class="rnb-hero">
        <div class="rnb-hero-inner">
            <div>
                <p class="rnb-kicker">Recepción</p>
                <h1 class="rnb-title">{{ $receiptNote ? 'Nota ' . $receiptNote->code : 'Nueva nota de recepción' }}</h1>
                <p class="rnb-sub">
                    @if($receiptNote)
                        Agregue los bultos que entregó el cliente. Cuando termine pulse «Imprimir comprobante».
                    @else
                        Paso 1: capture los datos del cliente que entrega los paquetes.
                    @endif
                </p>
            </div>
            <div class="rnb-hero-actions">
                <a href="{{ route('receipt-notes.index') }}" class="rnb-btn rnb-btn-glass">← Listado</a>
            </div>
        </div>
    </header>

    @if ($errors->any())
    <div class="rnb-alert rnb-alert-err">
        <ul class="rnb-alert-ul">
            @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(!$receiptNote)
        {{-- Paso 1 — Datos del entregante --}}
        <div class="rnb-card">
            <div class="rnb-card-h">1. Datos del cliente que entrega</div>
            <div class="rnb-card-b">
                <form action="{{ route('receipt-notes.store') }}" method="POST" class="rnb-form">
                    @csrf
                    <div class="rnb-grid-2">
                        <div class="rnb-field">
                            <label for="delivered_by" class="rnb-label">Nombre completo *</label>
                            <input type="text" name="delivered_by" id="delivered_by" value="{{ old('delivered_by') }}" required class="rnb-input" autofocus placeholder="Nombre y apellidos">
                        </div>
                        <div class="rnb-field">
                            <label for="delivered_by_id_number" class="rnb-label">Cédula / Identificación</label>
                            <input type="text" name="delivered_by_id_number" id="delivered_by_id_number" value="{{ old('delivered_by_id_number') }}" class="rnb-input" placeholder="Ej. 001-XXXXXX-XXXXY">
                        </div>
                        <div class="rnb-field">
                            <label for="delivered_by_phone" class="rnb-label">Teléfono</label>
                            <input type="text" name="delivered_by_phone" id="delivered_by_phone" value="{{ old('delivered_by_phone') }}" class="rnb-input" placeholder="Opcional">
                        </div>
                        <div class="rnb-field">
                            <label for="agency_id" class="rnb-label">Agencia de recepción *</label>
                            <select name="agency_id" id="agency_id" required class="rnb-select">
                                <option value="">Seleccione…</option>
                                @foreach($agencies as $a)
                                <option value="{{ $a->id }}" {{ (string) old('agency_id') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }} @if($a->code)({{ $a->code }})@endif</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="rnb-field">
                        <label for="notes" class="rnb-label">Notas (opcional)</label>
                        <textarea name="notes" id="notes" rows="2" class="rnb-textarea" placeholder="Observaciones de la recepción…">{{ old('notes') }}</textarea>
                    </div>
                    <div class="rnb-form-actions">
                        <a href="{{ route('receipt-notes.index') }}" class="rnb-btn rnb-btn-secondary">Cancelar</a>
                        <button type="submit" class="rnb-btn rnb-btn-primary">Iniciar recepción →</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        {{-- Paso 2 — Agregar bultos --}}
        <div class="rnb-card">
            <div class="rnb-card-h">Datos del entregante</div>
            <div class="rnb-card-b rnb-info-grid">
                <div>
                    <div class="rnb-info-label">Nombre</div>
                    <div class="rnb-info-value">{{ $receiptNote->delivered_by }}</div>
                </div>
                <div>
                    <div class="rnb-info-label">ID / Cédula</div>
                    <div class="rnb-info-value">{{ $receiptNote->delivered_by_id_number ?: '—' }}</div>
                </div>
                <div>
                    <div class="rnb-info-label">Teléfono</div>
                    <div class="rnb-info-value">{{ $receiptNote->delivered_by_phone ?: '—' }}</div>
                </div>
                <div>
                    <div class="rnb-info-label">Agencia de recepción</div>
                    <div class="rnb-info-value">{{ $receiptNote->agency?->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="rnb-info-label">Operador</div>
                    <div class="rnb-info-value">{{ $receiptNote->receivedBy?->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="rnb-info-label">Fecha</div>
                    <div class="rnb-info-value">{{ $receiptNote->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <div class="rnb-grid-main">
            <div class="rnb-card">
                <div class="rnb-card-h">2. Agregar bulto por escaneo</div>
                <div class="rnb-card-b">
                    <form action="{{ route('receipt-notes.add-item', $receiptNote->id) }}" method="POST" class="rnb-scan-form" id="rnb-scan-form">
                        @csrf
                        <label for="rnb-scan-input" class="rnb-label">Warehouse code (escanear)</label>
                        <input type="text" name="warehouse_code" id="rnb-scan-input" class="rnb-scan-input" placeholder="Enfocar aquí — listo para pistola" autocomplete="off" autocapitalize="characters" spellcheck="false" autofocus maxlength="6" pattern="[0-9]{6}" inputmode="numeric">
                        <p class="rnb-hint">Si el código pertenece a un drop-off de varios bultos, se agregarán todos los disponibles. Use «Quitar» en la tabla derecha si se equivocó.</p>
                    </form>
                </div>
            </div>

            <div class="rnb-card">
                <div class="rnb-card-h rnb-card-h-row">
                    <span>Bultos en esta nota ({{ $receiptNote->preregistrations->count() }})</span>
                    @if($receiptNote->preregistrations->count() > 0)
                    <a href="{{ route('receipt-notes.print', $receiptNote->id) }}" target="_blank" class="rnb-btn rnb-btn-primary rnb-btn-sm">Imprimir comprobante</a>
                    @endif
                </div>
                <div class="rnb-card-b">
                    @if($receiptNote->preregistrations->count() > 0)
                    <div class="rnb-table-wrap">
                        <table class="rnb-table">
                            <thead>
                                <tr>
                                    <th>Warehouse</th>
                                    <th>Bulto</th>
                                    <th>Destinatario</th>
                                    <th>Peso lbs</th>
                                    <th>Servicio</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receiptNote->preregistrations as $pre)
                                <tr>
                                    <td><span class="rnb-code">{{ $pre->warehouse_code ?? '—' }}</span></td>
                                    <td class="rnb-muted">{{ ($pre->bultos_total && $pre->bultos_total > 1 && $pre->bulto_index) ? $pre->bulto_index.'/'.$pre->bultos_total : '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($pre->label_name, 24) }}</td>
                                    <td class="rnb-num">{{ $pre->intake_weight_lbs ?? '—' }}</td>
                                    <td><span class="rnb-badge rnb-badge-{{ strtolower($pre->service_type ?? '') }}">{{ $pre->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span></td>
                                    <td class="rnb-row-actions">
                                        <form action="{{ route('receipt-notes.remove-item', [$receiptNote->id, $pre->id]) }}" method="POST" onsubmit="return confirm('¿Quitar este bulto de la nota?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rnb-btn rnb-btn-sm rnb-btn-danger">Quitar</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="rnb-empty">
                        <p class="rnb-empty-title">Aún no hay bultos en esta nota</p>
                        <p class="rnb-empty-sub">Escanee un warehouse code o selecciónelo de la tabla de disponibles más abajo.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="rnb-card">
            <div class="rnb-card-h">Preregistros DROP_OFF disponibles para agregar ({{ $availablePreregistrations->count() }})</div>
            <div class="rnb-card-b">
                @if($availablePreregistrations->count() > 0)
                <div class="rnb-table-wrap">
                    <table class="rnb-table">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th>Tracking</th>
                                <th>Destinatario</th>
                                <th>Peso lbs</th>
                                <th>Servicio</th>
                                <th>Agencia</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($availablePreregistrations as $pre)
                            <tr>
                                <td><span class="rnb-code">{{ $pre->warehouse_code ?? '—' }}</span></td>
                                <td class="rnb-code rnb-muted">{{ \Illuminate\Support\Str::limit($pre->tracking_external, 18) ?: '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($pre->label_name, 22) }}</td>
                                <td class="rnb-num">{{ $pre->intake_weight_lbs ?? '—' }}</td>
                                <td><span class="rnb-badge rnb-badge-{{ strtolower($pre->service_type ?? '') }}">{{ $pre->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span></td>
                                <td class="rnb-muted">{{ \Illuminate\Support\Str::limit($pre->agency?->name ?? '—', 18) }}</td>
                                <td class="rnb-muted">{{ $pre->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y') }}</td>
                                <td class="rnb-row-actions">
                                    <form action="{{ route('receipt-notes.add-item', $receiptNote->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="preregistration_id" value="{{ $pre->id }}">
                                        <button type="submit" class="rnb-btn rnb-btn-sm rnb-btn-outline-primary">+ Añadir</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="rnb-empty">
                    <p class="rnb-empty-title">No hay preregistros DROP_OFF disponibles para esta agencia en los últimos 30 días.</p>
                    <p class="rnb-empty-sub">Solo aparecen aquí los preregistros DROP_OFF que aún no estén en otra nota de recepción.</p>
                </div>
                @endif
            </div>
        </div>

        @if($receiptNote->preregistrations->count() === 0)
        <div class="rnb-card">
            <div class="rnb-card-b" style="display:flex;justify-content:flex-end;">
                <form action="{{ route('receipt-notes.destroy', $receiptNote->id) }}" method="POST" onsubmit="return confirm('Eliminar esta nota vacía?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rnb-btn rnb-btn-danger">Descartar nota vacía</button>
                </form>
            </div>
        </div>
        @endif
    @endif
</div>

<style>
.rnb-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

.rnb-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.5rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.rnb-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.rnb-kicker { margin: 0 0 0.25rem; font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.85); }
.rnb-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.rnb-sub { margin: 0.4rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.92); max-width: 56ch; line-height: 1.45; }
.rnb-hero-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

.rnb-btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0.55rem 1rem; font-size: 0.875rem; font-weight: 500;
    border-radius: 0.5rem; border: 1px solid transparent;
    cursor: pointer; text-decoration: none; transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.rnb-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; font-weight: 600; }
.rnb-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.rnb-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.rnb-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.rnb-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.rnb-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.rnb-btn-glass { background: rgba(255,255,255,0.18); color: #fff; border-color: rgba(255,255,255,0.4); }
.rnb-btn-glass:hover { background: rgba(255,255,255,0.28); color: #fff; }
.rnb-btn-danger { background: #fff; color: #b91c1c; border-color: #fecaca; }
.rnb-btn-danger:hover { background: #fef2f2; color: #991b1b; border-color: #f87171; }
.rnb-btn-sm { padding: 0.35rem 0.7rem; font-size: 0.8125rem; }

.rnb-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.25rem; overflow: hidden; }
.rnb-card-h {
    padding: 0.85rem 1.25rem; font-size: 0.8125rem; font-weight: 700;
    letter-spacing: 0.04em; text-transform: uppercase; color: #fff;
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 55%, #0f766e 100%);
}
.rnb-card-h-row { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap; text-transform: none; letter-spacing: 0; font-size: 0.875rem; }
.rnb-card-b { padding: 1.25rem 1.5rem; }

.rnb-grid-main { display: grid; grid-template-columns: 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
@media (min-width: 1024px) { .rnb-grid-main { grid-template-columns: minmax(280px, 380px) 1fr; gap: 1.5rem; } }

.rnb-grid-2 { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1rem; }
@media (min-width: 700px) { .rnb-grid-2 { grid-template-columns: 1fr 1fr; } }
.rnb-form { display: flex; flex-direction: column; gap: 1rem; }
.rnb-form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem; }
.rnb-field { display: flex; flex-direction: column; gap: 0.35rem; }
.rnb-label { font-size: 0.8125rem; font-weight: 600; color: #374151; }
.rnb-input, .rnb-select, .rnb-textarea {
    width: 100%; padding: 0.6rem 0.85rem; font-size: 0.875rem;
    border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827;
}
.rnb-textarea { resize: vertical; min-height: 64px; }
.rnb-input:focus, .rnb-select:focus, .rnb-textarea:focus {
    outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
}

.rnb-scan-input {
    width: 100%; box-sizing: border-box; margin-top: 0.25rem;
    padding: 1rem 1.15rem; min-height: 3.5rem;
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
    font-size: 1.25rem; font-weight: 600; letter-spacing: 0.04em;
    color: #0f172a; background: #fff;
    border: 2px solid #dce3ea; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}
.rnb-scan-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 4px rgba(13,148,136,0.14); }
.rnb-hint { margin: 0.75rem 0 0; font-size: 0.75rem; color: #6b7280; line-height: 1.45; }

.rnb-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem 1.25rem; }
.rnb-info-label { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
.rnb-info-value { font-size: 0.9375rem; font-weight: 600; color: #0f172a; margin-top: 0.2rem; }

.rnb-table-wrap { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
.rnb-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.rnb-table thead { background: #f0fdfa; }
.rnb-table th { padding: 0.55rem 0.75rem; text-align: left; font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #0f766e; border-bottom: 1px solid #ccfbf1; }
.rnb-table td { padding: 0.65rem 0.75rem; border-top: 1px solid #f1f5f9; vertical-align: middle; }
.rnb-table tbody tr:hover { background: #f8fafc; }
.rnb-row-actions { text-align: right; }
.rnb-code { font-family: ui-monospace, monospace; font-weight: 600; color: #0f172a; }
.rnb-num { font-variant-numeric: tabular-nums; }
.rnb-muted { color: #6b7280; }
.rnb-badge { display: inline-block; padding: 0.18rem 0.55rem; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; }
.rnb-badge-air { background: #dbeafe; color: #1d4ed8; }
.rnb-badge-sea { background: #d1fae5; color: #047857; }

.rnb-empty { text-align: center; padding: 1.5rem 1rem; border: 2px dashed #e5e7eb; border-radius: 0.5rem; background: #fafafa; }
.rnb-empty-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.rnb-empty-sub { margin: 0.4rem 0 0; font-size: 0.8125rem; color: #6b7280; }

.rnb-alert { margin-bottom: 1rem; padding: 0.85rem 1.1rem; border-radius: 0.5rem; font-size: 0.875rem; line-height: 1.45; }
.rnb-alert-err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.rnb-alert-ul { margin: 0; padding-left: 1.2rem; }
</style>

@push('scripts')
<script>
(function () {
    var input = document.getElementById('rnb-scan-input');
    var form = document.getElementById('rnb-scan-form');
    if (!input || !form) return;
    var submitted = false;
    function trySubmit() {
        if (submitted) return;
        var v = (input.value || '').trim();
        if (v.length === 6 && /^\d{6}$/.test(v)) {
            submitted = true;
            form.submit();
        }
    }
    input.addEventListener('input', function() {
        if (input.value.length >= 6) trySubmit();
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            trySubmit();
        }
    });
})();
</script>
@endpush
@endsection
