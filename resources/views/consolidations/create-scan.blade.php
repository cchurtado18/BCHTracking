@extends('layouts.app')

@section('title', 'Crear saco por escaneo')

@section('content')
@php
    $lookupJson = $scanLookup->map(fn ($p) => [
        'id' => $p->id,
        'tracking' => (string) ($p->tracking_external ?? ''),
        'warehouse' => (string) ($p->warehouse_code ?? ''),
        'label' => (string) ($p->label_name ?? ''),
        'service_type' => $p->service_type,
        'weight_lbs' => round((float) ($p->verified_weight_lbs ?? $p->intake_weight_lbs ?? 0), 2),
    ])->values();
@endphp
<script type="application/json" id="scan-lookup-json">@json($lookupJson)</script>

<div class="csscan-page">
    <header class="csscan-topbar">
        <div class="csscan-topbar-main">
            <p class="csscan-kicker">Consolidación</p>
            <h1 class="csscan-title">Crear saco por escaneo</h1>
            <p class="csscan-sub">Pulse Enter tras cada código. El servidor validará de nuevo al guardar.</p>
        </div>
        <div class="csscan-topbar-actions">
            <a href="{{ route('consolidations.create') }}" class="csscan-link csscan-link-secondary">Modos de creación</a>
            <a href="{{ route('consolidations.index') }}" class="csscan-link csscan-link-primary">Lista de sacos</a>
        </div>
    </header>

    @if ($errors->any())
    <div class="csscan-errors" role="alert">
        <ul class="csscan-errors-ul">
            @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('error'))
    <p class="csscan-flash csscan-flash-err">{{ session('error') }}</p>
    @endif

    <form action="{{ route('consolidations.store-scan') }}" method="POST" id="csscan-form" class="csscan-layout">
        @csrf
        <div id="csscan-hidden-codes"></div>

        <div class="csscan-panel csscan-panel--form">
            <div class="csscan-panel-section">
                <h2 class="csscan-section-label">Detalle del saco</h2>
                <div class="csscan-field">
                    <label for="csscan_service_type" class="csscan-label">Tipo de servicio</label>
                    <select name="service_type" id="csscan_service_type" required class="csscan-select">
                        <option value="AIR" @selected(old('service_type', 'AIR') === 'AIR')>Aéreo</option>
                        <option value="SEA" @selected(old('service_type') === 'SEA')>Marítimo</option>
                    </select>
                </div>
                <div class="csscan-field">
                    <label for="csscan_notes" class="csscan-label">Notas <span class="csscan-label-hint">opcional</span></label>
                    <textarea name="notes" id="csscan_notes" rows="2" class="csscan-textarea" placeholder="Ej. vuelo, referencia interna…">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="csscan-panel-section csscan-panel-section--scan">
                <div class="csscan-scan-header">
                    <h2 class="csscan-section-label csscan-section-label--lg">Escanear código</h2>
                    <span class="csscan-scan-pill">Se agrega solo al escanear</span>
                </div>
                <div class="csscan-scan-box">
                    <label for="csscan_input" class="csscan-label csscan-label-muted">Tracking o código warehouse</label>
                    <input type="text" id="csscan_input" class="csscan-input" placeholder="Enfocar aquí — listo para pistola" autocomplete="off" autocapitalize="characters" spellcheck="false" autofocus>
                    <p id="csscan_line_feedback" class="csscan-feedback" role="status"></p>
                </div>
                <p class="csscan-hint">Los duplicados se rechazan. El pistola suele enviar el código de corrido y se agrega al detenerse; también puede pulsar Enter o Tab. Use <strong>Eliminar</strong> en cada fila si se equivocó.</p>
            </div>

            <div class="csscan-actions">
                <button type="submit" class="csscan-btn csscan-btn-primary" id="csscan_submit" disabled>Crear saco con esta lista</button>
            </div>
        </div>

        <div class="csscan-list-panel">
            <div class="csscan-list-head">
                <div>
                    <h2 class="csscan-list-title">Códigos en el saco</h2>
                    <p class="csscan-list-sub">Vista previa antes de guardar · peso solo de ítems con preregistro en este servicio</p>
                </div>
                <div class="csscan-list-head-stats" aria-live="polite">
                    <span id="csscan_count" class="csscan-count">0</span>
                    <div id="csscan_weight_wrap" class="csscan-weight-wrap">
                        <span class="csscan-weight-label">Total lbs</span>
                        <span id="csscan_weight_total" class="csscan-weight-total">0.0</span>
                    </div>
                </div>
            </div>
            <ul id="csscan_list" class="csscan-list" aria-live="polite"></ul>
            <p id="csscan_empty" class="csscan-empty">Aún no hay códigos. Escanee el primero en el panel izquierdo.</p>
        </div>
    </form>
</div>

<style>
/* —— Page shell —— */
.csscan-page {
    --csscan-accent: #0d9488;
    --csscan-accent-dark: #0f766e;
    --csscan-surface: #ffffff;
    --csscan-canvas: #f4f6f9;
    --csscan-border: #e8ecf1;
    --csscan-text: #0f172a;
    --csscan-text-muted: #64748b;
    --csscan-radius: 14px;
    --csscan-radius-sm: 10px;
    --csscan-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 16px rgba(15, 23, 42, 0.06);
    --csscan-shadow-lg: 0 4px 6px rgba(15, 23, 42, 0.03), 0 12px 32px rgba(15, 23, 42, 0.08);

    padding: 1.75rem 1.25rem 2.5rem;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    min-height: calc(100vh - 4rem);
    background: var(--csscan-canvas);
    box-sizing: border-box;
}

/* —— Top bar (compact SaaS header) —— */
.csscan-topbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem 1.5rem;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
    background: var(--csscan-surface);
    border: 1px solid var(--csscan-border);
    border-radius: var(--csscan-radius);
    box-shadow: var(--csscan-shadow);
}
.csscan-topbar-main { min-width: 0; flex: 1; max-width: 42rem; }
.csscan-kicker {
    margin: 0 0 0.25rem;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--csscan-accent);
}
.csscan-title {
    margin: 0;
    font-size: 1.375rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: var(--csscan-text);
    line-height: 1.2;
}
.csscan-sub {
    margin: 0.5rem 0 0;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--csscan-text-muted);
}
.csscan-topbar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.csscan-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    border-radius: var(--csscan-radius-sm);
    text-decoration: none;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
    border: 1px solid transparent;
}
.csscan-link-secondary {
    color: var(--csscan-text-muted);
    background: transparent;
    border-color: var(--csscan-border);
}
.csscan-link-secondary:hover {
    background: #f8fafc;
    color: var(--csscan-text);
    border-color: #cbd5e1;
}
.csscan-link-primary {
    color: #fff;
    background: var(--csscan-accent);
    border-color: var(--csscan-accent);
}
.csscan-link-primary:hover {
    background: var(--csscan-accent-dark);
    border-color: var(--csscan-accent-dark);
}

/* —— Dos columnas: formulario izquierda, lista siempre a la derecha —— */
.csscan-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    align-items: start;
}
@media (min-width: 600px) {
    .csscan-layout {
        /* Lista anclada a la derecha con ancho cómodo para tarjetas */
        grid-template-columns: minmax(0, 1fr) minmax(300px, min(420px, 42%));
        gap: 1.25rem 1.5rem;
        align-items: stretch;
    }
    .csscan-panel--form {
        grid-column: 1;
        min-width: 0;
    }
    .csscan-list-panel {
        grid-column: 2;
        min-width: 0;
    }
}
@media (min-width: 1100px) {
    .csscan-layout {
        grid-template-columns: minmax(360px, 460px) minmax(360px, 1fr);
        gap: 1.75rem;
    }
}

/* —— Left: form column —— */
.csscan-panel {
    background: var(--csscan-surface);
    border: 1px solid var(--csscan-border);
    border-radius: var(--csscan-radius);
    box-shadow: var(--csscan-shadow);
    padding: 1.5rem 1.5rem 1.75rem;
}
.csscan-panel--form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.csscan-panel-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.csscan-panel-section--scan {
    padding-top: 0.25rem;
}
.csscan-section-label {
    margin: 0;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--csscan-text-muted);
}
.csscan-section-label--lg {
    font-size: 0.75rem;
    color: var(--csscan-text);
}
.csscan-scan-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.csscan-scan-pill {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--csscan-accent-dark);
    background: rgba(13, 148, 136, 0.1);
    border: 1px solid rgba(13, 148, 136, 0.2);
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
}

.csscan-field { display: flex; flex-direction: column; gap: 0.4rem; }
.csscan-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--csscan-text);
}
.csscan-label-muted { color: var(--csscan-text-muted); font-weight: 500; }
.csscan-label-hint { font-weight: 500; color: var(--csscan-text-muted); }
.csscan-select, .csscan-textarea {
    width: 100%;
    padding: 0.65rem 0.85rem;
    font-size: 0.875rem;
    border: 1px solid var(--csscan-border);
    border-radius: var(--csscan-radius-sm);
    color: var(--csscan-text);
    background: #fafbfc;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}
.csscan-select:focus, .csscan-textarea:focus {
    outline: none;
    border-color: var(--csscan-accent);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
}
.csscan-textarea { resize: vertical; min-height: 72px; line-height: 1.45; }

/* —— Hero scan input —— */
.csscan-scan-box {
    margin-top: 0.35rem;
    padding: 1.25rem 1.25rem 1.1rem;
    background: linear-gradient(180deg, #fafcfc 0%, #f4f9f8 100%);
    border: 1px solid rgba(13, 148, 136, 0.18);
    border-radius: var(--csscan-radius);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
}
.csscan-input {
    width: 100%;
    box-sizing: border-box;
    margin-top: 0.35rem;
    padding: 1.1rem 1.25rem;
    min-height: 3.75rem;
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
    font-size: 1.125rem;
    font-weight: 500;
    letter-spacing: 0.02em;
    color: var(--csscan-text);
    background: #fff;
    border: 2px solid #dce3ea;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}
.csscan-input::placeholder { color: #94a3b8; font-weight: 400; }
.csscan-input:hover {
    border-color: #c5ced8;
}
.csscan-input:focus {
    outline: none;
    border-color: var(--csscan-accent);
    box-shadow:
        0 0 0 4px rgba(13, 148, 136, 0.14),
        0 8px 28px rgba(13, 148, 136, 0.12);
}
.csscan-input:focus-visible {
    outline: none;
}

.csscan-feedback {
    min-height: 1.35rem;
    margin: 0.75rem 0 0;
    padding: 0.35rem 0;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.4;
    border-radius: 6px;
    transition: color 0.15s, background 0.15s;
}
.csscan-feedback.ok { color: #047857; background: rgba(16, 185, 129, 0.08); padding: 0.35rem 0.5rem; }
.csscan-feedback.warn { color: #b45309; background: rgba(245, 158, 11, 0.1); padding: 0.35rem 0.5rem; }
.csscan-feedback.err { color: #b91c1c; background: rgba(239, 68, 68, 0.08); padding: 0.35rem 0.5rem; }

.csscan-hint {
    margin: 0;
    font-size: 0.75rem;
    line-height: 1.5;
    color: var(--csscan-text-muted);
}

/* —— Primary CTA —— */
.csscan-actions {
    margin-top: 0.25rem;
    padding-top: 0.25rem;
}
.csscan-btn {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: center;
    padding: 0.9rem 1.25rem;
    font-size: 0.9375rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    border-radius: 12px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: background 0.15s, transform 0.12s, box-shadow 0.15s, opacity 0.15s;
}
.csscan-btn-primary {
    color: #fff;
    background: linear-gradient(180deg, #14b8a6 0%, var(--csscan-accent) 55%, var(--csscan-accent-dark) 100%);
    border-color: rgba(15, 118, 110, 0.35);
    box-shadow: 0 2px 4px rgba(13, 148, 136, 0.25), 0 8px 20px rgba(13, 148, 136, 0.2);
}
.csscan-btn-primary:hover:not(:disabled) {
    filter: brightness(1.03);
    box-shadow: 0 4px 8px rgba(13, 148, 136, 0.28), 0 12px 28px rgba(13, 148, 136, 0.22);
    transform: translateY(-1px);
}
.csscan-btn-primary:active:not(:disabled) {
    transform: translateY(0);
}
.csscan-btn-primary:disabled {
    opacity: 0.42;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
    filter: grayscale(0.15);
}

/* —— Right: list column —— */
.csscan-list-panel {
    position: relative;
    background: var(--csscan-surface);
    border: 1px solid var(--csscan-border);
    border-radius: var(--csscan-radius);
    box-shadow: var(--csscan-shadow-lg);
    padding: 1.35rem 1.5rem 1.5rem;
    min-height: 22rem;
    display: flex;
    flex-direction: column;
}
@media (min-width: 600px) {
    .csscan-list-panel {
        position: sticky;
        top: 1rem;
        align-self: start;
        max-height: calc(100vh - 7rem);
    }
}
.csscan-list-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--csscan-border);
}
.csscan-list-head-stats {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.45rem;
}
.csscan-weight-wrap {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    padding: 0.35rem 0.65rem;
    min-width: 5.5rem;
    border-radius: 10px;
    border: 1px solid rgba(59, 130, 246, 0.28);
    background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}
.csscan-weight-label {
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #64748b;
}
.csscan-weight-total {
    font-size: 1.125rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: #1e40af;
    letter-spacing: -0.02em;
    line-height: 1.15;
}
.csscan-list-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--csscan-text);
}
.csscan-list-sub {
    margin: 0.2rem 0 0;
    font-size: 0.75rem;
    color: var(--csscan-text-muted);
}
.csscan-count {
    flex-shrink: 0;
    min-width: 2.25rem;
    height: 2.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.65rem;
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--csscan-accent-dark);
    background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid rgba(13, 148, 136, 0.25);
    border-radius: 10px;
    box-shadow: 0 1px 2px rgba(13, 148, 136, 0.08);
}

.csscan-list {
    list-style: none;
    margin: 0;
    padding: 0;
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    scrollbar-gutter: stable;
}
.csscan-list::-webkit-scrollbar { width: 8px; }
.csscan-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 8px;
}
.csscan-list::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Row cards (JS builds: li.csscan-row > div + button.csscan-remove) */
.csscan-row {
    display: flex;
    align-items: stretch;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    padding: 1rem 1rem 1rem 1.125rem;
    border-radius: 12px;
    border: 1px solid var(--csscan-border);
    background: #fafbfc;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition: box-shadow 0.15s, border-color 0.15s;
}
.csscan-row:hover {
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}
.csscan-row.match {
    border-color: rgba(16, 185, 129, 0.45);
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    box-shadow: 0 1px 3px rgba(16, 185, 129, 0.12);
}
.csscan-row.unmatch {
    border-color: rgba(245, 158, 11, 0.45);
    background: linear-gradient(135deg, #fffbeb 0%, #fef9c3 100%);
    box-shadow: 0 1px 3px rgba(245, 158, 11, 0.12);
}
.csscan-row-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.csscan-row-actions {
    flex-shrink: 0;
    display: flex;
    align-items: flex-start;
    padding-top: 0.125rem;
}
.csscan-row-code {
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
    font-weight: 600;
    font-size: 0.9375rem;
    color: var(--csscan-text);
    word-break: break-all;
    line-height: 1.35;
    letter-spacing: 0.02em;
}
.csscan-row-meta {
    font-size: 0.8125rem;
    line-height: 1.45;
    color: var(--csscan-text-muted);
}
.csscan-row-weight {
    font-size: 0.8125rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #1d4ed8;
    margin-top: 0.15rem;
}
.csscan-row-weight--muted {
    font-weight: 600;
    color: var(--csscan-text-muted);
}
.csscan-row-badge {
    align-self: flex-start;
    margin-top: 0.15rem;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.3rem 0.55rem;
    border-radius: 6px;
}
.csscan-row-badge.ok {
    color: #047857;
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.28);
}
.csscan-row-badge.warn {
    color: #b45309;
    background: rgba(245, 158, 11, 0.18);
    border: 1px solid rgba(245, 158, 11, 0.35);
}

/* Eliminar por fila */
.csscan-remove {
    padding: 0.45rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    background: #fff;
    border: 1px solid var(--csscan-border);
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition: color 0.15s, background 0.15s, border-color 0.15s, box-shadow 0.15s;
}
.csscan-remove:hover {
    color: #b91c1c;
    background: #fef2f2;
    border-color: #fecaca;
    box-shadow: 0 1px 3px rgba(220, 38, 38, 0.08);
}
.csscan-remove:focus-visible {
    outline: 2px solid var(--csscan-accent);
    outline-offset: 2px;
}

.csscan-empty {
    margin: 2rem 0 0;
    padding: 2rem 1.25rem;
    font-size: 0.875rem;
    line-height: 1.55;
    color: var(--csscan-text-muted);
    text-align: center;
    border: 1px dashed var(--csscan-border);
    border-radius: 12px;
    background: #fafbfc;
}

/* Alerts */
.csscan-flash {
    margin: 0 0 1rem;
    padding: 0.85rem 1.1rem;
    border-radius: var(--csscan-radius-sm);
    font-size: 0.875rem;
    line-height: 1.45;
}
.csscan-flash-err {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.csscan-errors {
    margin-bottom: 1rem;
    padding: 0.85rem 1.1rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: var(--csscan-radius-sm);
    color: #991b1b;
    font-size: 0.875rem;
}
.csscan-errors-ul { margin: 0; padding-left: 1.2rem; }
</style>

@push('scripts')
<script>
(function() {
    const lookupEl = document.getElementById('scan-lookup-json');
    const lookup = lookupEl ? JSON.parse(lookupEl.textContent || '[]') : [];
    const serviceSelect = document.getElementById('csscan_service_type');
    const input = document.getElementById('csscan_input');
    const list = document.getElementById('csscan_list');
    const hiddenWrap = document.getElementById('csscan-hidden-codes');
    const countEl = document.getElementById('csscan_count');
    const weightTotalEl = document.getElementById('csscan_weight_total');
    const emptyEl = document.getElementById('csscan_empty');
    const submitBtn = document.getElementById('csscan_submit');
    const feedback = document.getElementById('csscan_line_feedback');

    const lines = [];
    let scanDebounceTimer = null;
    /** Pausa tras el último carácter: el pistola manda el código en ráfaga; al escribir a mano use Enter si hace pausas largas. */
    var SCAN_DEBOUNCE_MS = 250;

    function norm(s) {
        return String(s || '').trim().toUpperCase();
    }

    function findInLookup(code) {
        const st = serviceSelect.value;
        const n = norm(code);
        if (!n) return null;
        for (let i = 0; i < lookup.length; i++) {
            const row = lookup[i];
            if (row.service_type !== st) continue;
            const t = norm(row.tracking);
            const w = norm(row.warehouse);
            if (n === t || n === w) return row;
        }
        return null;
    }

    /** Paquete existe en preregistro pero con otro tipo de servicio (p. ej. marítimo vs saco aéreo). */
    function weightFromHit(hit) {
        if (!hit || hit.weight_lbs === undefined || hit.weight_lbs === null) return 0;
        const w = Number(hit.weight_lbs);
        return Number.isFinite(w) ? w : 0;
    }

    function totalLbsInList() {
        var sum = 0;
        for (var i = 0; i < lines.length; i++) {
            if (lines[i].matched) sum += lines[i].weightLbs || 0;
        }
        return sum;
    }

    function rematchAllFromLookup() {
        lines.forEach(function(entry) {
            var hit = findInLookup(entry.display);
            entry.matched = !!hit;
            entry.label = hit ? hit.label : '';
            entry.weightLbs = hit ? weightFromHit(hit) : 0;
        });
    }

    function findOtherServiceMatch(code) {
        const st = serviceSelect.value;
        const n = norm(code);
        if (!n) return null;
        for (let i = 0; i < lookup.length; i++) {
            const row = lookup[i];
            if (row.service_type === st) continue;
            const t = norm(row.tracking);
            const w = norm(row.warehouse);
            if (n === t || n === w) return row;
        }
        return null;
    }

    function syncHiddens() {
        hiddenWrap.innerHTML = '';
        lines.forEach(function(entry) {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'entry_codes[]';
            inp.value = entry.raw;
            hiddenWrap.appendChild(inp);
        });
    }

    function render() {
        list.innerHTML = '';
        lines.forEach(function(entry) {
            const li = document.createElement('li');
            li.className = 'csscan-row ' + (entry.matched ? 'match' : 'unmatch');
            const main = document.createElement('div');
            main.className = 'csscan-row-main';
            const code = document.createElement('div');
            code.className = 'csscan-row-code';
            code.textContent = entry.display;
            main.appendChild(code);
            const meta = document.createElement('div');
            meta.className = 'csscan-row-meta';
            if (entry.matched) {
                meta.textContent = entry.label || 'Preregistro';
            } else {
                meta.textContent = 'No aparece en preregistro para este tipo de servicio; se guardará solo el código.';
            }
            main.appendChild(meta);
            var wEl = document.createElement('div');
            wEl.className = 'csscan-row-weight' + (entry.matched ? '' : ' csscan-row-weight--muted');
            if (entry.matched) {
                wEl.textContent = (Number(entry.weightLbs) || 0).toFixed(2) + ' lbs';
            } else {
                wEl.textContent = '— (no suma al total lbs)';
            }
            main.appendChild(wEl);
            const badge = document.createElement('div');
            badge.className = 'csscan-row-badge ' + (entry.matched ? 'ok' : 'warn');
            badge.textContent = entry.matched ? '✓ En preregistro' : '⚠ Sin preregistro';
            main.appendChild(badge);
            const actions = document.createElement('div');
            actions.className = 'csscan-row-actions';
            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'csscan-remove';
            rm.textContent = 'Eliminar';
            rm.addEventListener('click', function() {
                const i = lines.indexOf(entry);
                if (i !== -1) lines.splice(i, 1);
                render();
            });
            actions.appendChild(rm);
            li.appendChild(main);
            li.appendChild(actions);
            list.appendChild(li);
        });
        countEl.textContent = String(lines.length);
        if (weightTotalEl) {
            weightTotalEl.textContent = totalLbsInList().toFixed(1);
        }
        emptyEl.style.display = lines.length ? 'none' : '';
        submitBtn.disabled = lines.length === 0;
        syncHiddens();
    }

    function setFeedback(text, cls) {
        feedback.textContent = text || '';
        feedback.className = 'csscan-feedback' + (cls ? ' ' + cls : '');
    }

    function clearScanDebounce() {
        if (scanDebounceTimer) {
            clearTimeout(scanDebounceTimer);
            scanDebounceTimer = null;
        }
    }

    function scheduleScanCommit() {
        clearScanDebounce();
        scanDebounceTimer = setTimeout(function() {
            scanDebounceTimer = null;
            tryCommitScan();
        }, SCAN_DEBOUNCE_MS);
    }

    /** @returns {boolean} true si se agregó un código */
    function tryCommitScan() {
        const raw = input.value;
        const display = norm(raw);
        if (!display) {
            return false;
        }
        const dup = lines.some(function(l) { return norm(l.display) === display; });
        if (dup) {
            setFeedback('Ese código ya está en la lista.', 'err');
            input.select();
            return false;
        }
        const otherSvc = findOtherServiceMatch(display);
        if (otherSvc) {
            const sackIsAir = serviceSelect.value === 'AIR';
            const pkgIsAir = otherSvc.service_type === 'AIR';
            const sackWord = sackIsAir ? 'aéreo' : 'marítimo';
            const pkgWord = pkgIsAir ? 'aéreo' : 'marítimo';
            setFeedback('Alerta: este paquete está en preregistro como ' + pkgWord + ', no como ' + sackWord + '. Cambie el tipo de servicio del saco o use otro código.', 'err');
            input.select();
            return false;
        }
        const hit = findInLookup(display);
        lines.push({
            raw: raw.trim(),
            display: display,
            matched: !!hit,
            label: hit ? hit.label : '',
            weightLbs: hit ? weightFromHit(hit) : 0,
        });
        input.value = '';
        setFeedback(hit ? 'Agregado (preregistro).' : 'Agregado: sin preregistro — se guardará el código en el saco.', hit ? 'ok' : 'warn');
        render();
        input.focus();
        return true;
    }

    input.addEventListener('input', function() {
        scheduleScanCommit();
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if ((e.key === 'Tab') && norm(input.value) === '') {
                return;
            }
            e.preventDefault();
            clearScanDebounce();
            tryCommitScan();
        }
    });

    serviceSelect.addEventListener('change', function() {
        clearScanDebounce();
        rematchAllFromLookup();
        render();
        setFeedback('Tipo de servicio cambiado. Se recalculó coincidencia y peso por fila.', '');
    });

    render();
})();
</script>
@endpush
@endsection
