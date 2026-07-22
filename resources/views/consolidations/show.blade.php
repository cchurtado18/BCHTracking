@extends('layouts.app')

@section('title', 'Detalle Consolidación')

@section('content')
<style>
    .cons-show-page {
        --cs-accent: #0d9488;
        --cs-accent-dark: #0f766e;
        --cs-surface: #ffffff;
        --cs-muted: #64748b;
        --cs-border: #e2e8f0;
        --cs-radius: 14px;
        --cs-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 4px 16px rgba(15, 23, 42, 0.06);
        max-width: 1200px;
        margin: 0 auto;
        padding: 1.25rem 1rem 2rem;
    }

    /* Hero */
    .cons-show-hero {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 45%, #14b8a6 100%);
        border-radius: var(--cs-radius);
        padding: 1.25rem 1.35rem 1.35rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--cs-shadow);
    }
    .cons-show-hero-top {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem 1.5rem;
        margin-bottom: 1rem;
    }
    .cons-show-kicker {
        margin: 0 0 0.2rem;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.75);
    }
    .cons-show-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        color: #fff;
        line-height: 1.2;
    }
    .cons-show-sub {
        margin: 0.35rem 0 0;
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.88);
    }

    /* Toolbar: botones unificados sobre el gradiente */
    .cons-show-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        padding-top: 0.25rem;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }
    .cons-show-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.5rem 0.95rem;
        font-size: 0.8125rem;
        font-weight: 600;
        border-radius: 10px;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: background 0.15s, color 0.15s, border-color 0.15s, box-shadow 0.15s, transform 0.1s;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
    }
    .cons-show-btn:active { transform: scale(0.98); }
    /* Principal: blanco + texto teal */
    .cons-show-btn--solid {
        background: #fff;
        color: var(--cs-accent-dark);
        border-color: rgba(255, 255, 255, 0.65);
    }
    .cons-show-btn--solid:hover {
        background: #f0fdfa;
        color: var(--cs-accent);
        border-color: #fff;
    }
    /* Secundario: cristal */
    .cons-show-btn--glass {
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.35);
        box-shadow: none;
    }
    .cons-show-btn--glass:hover {
        background: rgba(255, 255, 255, 0.24);
        border-color: rgba(255, 255, 255, 0.5);
    }
    /* Éxito / enviar: mismo lenguaje que solid pero con acento verde oscuro */
    .cons-show-btn--success {
        background: #fff;
        color: #047857;
        border-color: rgba(16, 185, 129, 0.45);
    }
    .cons-show-btn--success:hover {
        background: #ecfdf5;
        color: #065f46;
        border-color: #34d399;
    }
    /* Peligro */
    .cons-show-btn--danger {
        background: rgba(254, 242, 242, 0.98);
        color: #b91c1c;
        border-color: rgba(252, 165, 165, 0.9);
    }
    .cons-show-btn--danger:hover {
        background: #fee2e2;
        color: #991b1b;
        border-color: #f87171;
    }

    /* Tarjetas */
    .cons-show-card {
        background: var(--cs-surface);
        border: 1px solid var(--cs-border);
        border-radius: var(--cs-radius);
        box-shadow: var(--cs-shadow);
        overflow: hidden;
    }
    .cons-show-card-h {
        padding: 0.85rem 1.25rem;
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #fff;
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 55%, #0f766e 100%);
    }
    .cons-show-card-b {
        padding: 1.35rem 1.4rem 1.5rem;
    }

    /* Definición lista */
    .cons-show-dl { display: flex; flex-direction: column; gap: 1.1rem; }
    .cons-show-dt { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--cs-muted); }
    .cons-show-dd { margin: 0.25rem 0 0; font-size: 0.9375rem; font-weight: 600; color: #0f172a; }
    .cons-show-dd-notes { font-weight: 500; line-height: 1.5; }

    .cons-show-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 9999px;
        letter-spacing: 0.02em;
    }
    .cons-show-badge-air { background: #dbeafe; color: #1e40af; }
    .cons-show-badge-sea { background: #d1fae5; color: #047857; }
    .cons-show-badge-open { background: #d1fae5; color: #065f46; }
    .cons-show-badge-sent { background: #dbeafe; color: #1d4ed8; }
    .cons-show-badge-received { background: #ede9fe; color: #5b21b6; }
    .cons-show-badge-cancelled { background: #fee2e2; color: #991b1b; }

    /* Reporte métricas */
    .cons-show-report-title {
        margin: 0 0 1rem;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
    }
    .cons-show-metrics {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    @media (min-width: 640px) {
        .cons-show-metrics { grid-template-columns: repeat(4, 1fr); }
    }
    .cons-show-metric {
        padding: 0.85rem 1rem;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid var(--cs-border);
    }
    .cons-show-metric-label { font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--cs-muted); }
    .cons-show-metric-value { margin-top: 0.35rem; font-size: 1.375rem; font-weight: 800; letter-spacing: -0.02em; color: #0f172a; line-height: 1.1; }
    .cons-show-metric-value--ok { color: #047857; }
    .cons-show-metric-value--warn { color: #b45309; }
    .cons-show-metric-wide { grid-column: 1 / -1; }
    .cons-show-metric--amber {
        background: #fffbeb;
        border-color: #fde68a;
    }
    .cons-show-metric--amber .cons-show-metric-value { color: #b45309; font-size: 1.125rem; }

    /* Items en saco */
    .cons-show-item-list { display: flex; flex-direction: column; gap: 0.65rem; max-height: 24rem; overflow-y: auto; }
    .cons-show-item {
        padding: 1rem 1.1rem;
        border-radius: 12px;
        border: 1px solid var(--cs-border);
        background: #fafbfc;
    }
    .cons-show-item--ok { border-color: rgba(16, 185, 129, 0.35); background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); }
    .cons-show-item-name { font-size: 0.875rem; font-weight: 600; color: #0f172a; }
    .cons-show-item-code { font-size: 0.75rem; font-family: ui-monospace, monospace; color: var(--cs-muted); margin-top: 0.25rem; }
    .cons-show-item-meta { font-size: 0.75rem; color: var(--cs-muted); margin-top: 0.2rem; }
    .cons-show-item-tag { margin-top: 0.45rem; font-size: 0.6875rem; font-weight: 600; color: #047857; }

    .cons-show-item--orphan {
        border-color: #fcd34d;
        background: linear-gradient(135deg, #fffbeb 0%, #fef9c3 100%);
    }
    .cons-show-item-orphan-label { font-size: 0.625rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #b45309; }
    .cons-show-item-orphan-code { font-size: 0.875rem; font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a; margin-top: 0.35rem; }
    .cons-show-item-orphan-hint { font-size: 0.75rem; color: #b45309; margin-top: 0.25rem; }

    /* Item row con botón eliminar */
    .cons-show-item--row { display: flex; gap: 0.75rem; align-items: stretch; justify-content: space-between; }
    .cons-show-item-main { flex: 1; min-width: 0; }
    .cons-show-item-actions { flex-shrink: 0; display: flex; align-items: flex-start; margin: 0; }
    .cons-show-item-remove {
        padding: 0.4rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #b91c1c;
        background: #fff;
        border: 1px solid #fecaca;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }
    .cons-show-item-remove:hover {
        background: #fef2f2;
        border-color: #f87171;
        color: #991b1b;
        box-shadow: 0 1px 3px rgba(220, 38, 38, 0.12);
    }

    /* Pregunta inicial de modo de edición */
    .cons-show-mode-prompt {
        margin-top: 1.5rem;
        padding: 1.4rem 1.5rem;
        background: #fff;
        border: 1px solid var(--cs-border);
        border-radius: var(--cs-radius);
        box-shadow: var(--cs-shadow);
    }
    .cons-show-mode-prompt-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .cons-show-mode-prompt-sub {
        margin: 0.35rem 0 1rem;
        font-size: 0.875rem;
        color: var(--cs-muted);
        line-height: 1.45;
    }
    .cons-show-mode-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
    }
    @media (min-width: 640px) {
        .cons-show-mode-grid { grid-template-columns: 1fr 1fr; }
    }
    .cons-show-mode-card {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 1.1rem 1.15rem 1.2rem;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        background: #fff;
        text-decoration: none;
        color: inherit;
        transition: border-color 0.15s, box-shadow 0.15s, transform 0.12s, background 0.15s;
        cursor: pointer;
    }
    .cons-show-mode-card:hover {
        border-color: var(--cs-accent);
        box-shadow: 0 6px 18px rgba(13, 148, 136, 0.12);
        transform: translateY(-1px);
        background: #f0fdfa;
    }
    .cons-show-mode-card-icon { font-size: 1.5rem; line-height: 1; margin-bottom: 0.55rem; opacity: 0.85; }
    .cons-show-mode-card-title { margin: 0 0 0.35rem; font-size: 1rem; font-weight: 700; color: #0f172a; }
    .cons-show-mode-card-text { margin: 0; font-size: 0.8125rem; color: #475569; line-height: 1.45; }
    .cons-show-mode-card-cta { margin-top: 0.85rem; font-size: 0.8125rem; font-weight: 700; color: var(--cs-accent-dark); }

    /* Tabs cuando ya se eligió modo */
    .cons-show-edit-tabs {
        display: inline-flex;
        gap: 0.35rem;
        padding: 0.3rem;
        margin: 1.5rem 0 0.75rem;
        background: #f1f5f9;
        border: 1px solid var(--cs-border);
        border-radius: 10px;
    }
    .cons-show-edit-tab {
        padding: 0.45rem 0.95rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--cs-muted);
        background: transparent;
        border: 1px solid transparent;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.15s, color 0.15s, border-color 0.15s, box-shadow 0.15s;
    }
    .cons-show-edit-tab:hover {
        color: #0f172a;
        background: #e2e8f0;
    }
    .cons-show-edit-tab--active {
        color: var(--cs-accent-dark);
        background: #fff;
        border-color: rgba(13, 148, 136, 0.35);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }
    .cons-show-edit-tab--active:hover {
        background: #fff;
        color: var(--cs-accent-dark);
    }

    /* Scan box dentro del show */
    .cons-show-scan-box {
        padding: 1.25rem 1.25rem 1.1rem;
        background: linear-gradient(180deg, #fafcfc 0%, #f4f9f8 100%);
        border: 1px solid rgba(13, 148, 136, 0.18);
        border-radius: var(--cs-radius);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
    }
    .cons-show-scan-label { font-size: 0.8125rem; font-weight: 600; color: var(--cs-muted); }
    .cons-show-scan-input {
        width: 100%;
        box-sizing: border-box;
        margin-top: 0.35rem;
        padding: 1rem 1.15rem;
        min-height: 3.25rem;
        font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
        font-size: 1.0625rem;
        font-weight: 500;
        letter-spacing: 0.02em;
        color: #0f172a;
        background: #fff;
        border: 2px solid #dce3ea;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }
    .cons-show-scan-input:focus {
        outline: none;
        border-color: var(--cs-accent);
        box-shadow:
            0 0 0 4px rgba(13, 148, 136, 0.14),
            0 8px 28px rgba(13, 148, 136, 0.12);
    }
    .cons-show-scan-feedback {
        min-height: 1.35rem;
        margin: 0.65rem 0 0;
        padding: 0.3rem 0.4rem;
        font-size: 0.8125rem;
        font-weight: 600;
        border-radius: 6px;
    }
    .cons-show-scan-feedback.ok { color: #047857; background: rgba(16, 185, 129, 0.08); }
    .cons-show-scan-feedback.warn { color: #b45309; background: rgba(245, 158, 11, 0.1); }
    .cons-show-scan-feedback.err { color: #b91c1c; background: rgba(239, 68, 68, 0.08); }
    .cons-show-scan-hint { margin: 0.85rem 0 0; font-size: 0.75rem; color: var(--cs-muted); line-height: 1.5; }

    /* Tabla agregar */
    .cons-show-table-wrap {
        border: 1px solid var(--cs-border);
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    .cons-show-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .cons-show-table thead { background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); }
    .cons-show-table th {
        padding: 0.65rem 1rem;
        text-align: left;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.95);
    }
    .cons-show-table td { padding: 0.75rem 1rem; border-top: 1px solid var(--cs-border); vertical-align: middle; }
    .cons-show-table tbody tr:hover { background: #f8fafc; }
    .cons-show-table-add {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--cs-accent-dark);
        background: #f0fdfa;
        border: 1px solid rgba(13, 148, 136, 0.35);
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
    }
    .cons-show-table-add:hover {
        background: #ccfbf1;
        border-color: var(--cs-accent);
    }

    .cons-show-empty {
        text-align: center;
        padding: 2rem 1.25rem;
        border: 2px dashed var(--cs-border);
        border-radius: 12px;
        background: #f8fafc;
    }
    .cons-show-empty-note { margin-top: 0.5rem; font-size: 0.75rem; color: var(--cs-muted); }
    .cons-show-empty-p { margin: 0; font-size: 0.875rem; color: var(--cs-muted); }
    .cons-show-empty-title { margin: 0; font-size: 0.875rem; font-weight: 600; color: #334155; }

    .cons-show-grid-main {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    @media (min-width: 1024px) {
        .cons-show-grid-main { grid-template-columns: 2fr 1fr; gap: 1.5rem; }
    }

    .cons-show-divider { margin: 1.5rem 0 0; padding-top: 1.25rem; border-top: 1px solid var(--cs-border); }
    .cons-show-sent-note { margin-top: 0.75rem; font-size: 0.8125rem; color: var(--cs-muted); }
    .cons-show-sent-label { font-weight: 600; color: #334155; }
    .cons-show-sent-hint { margin-top: 0.35rem; font-size: 0.75rem; font-weight: 600; color: var(--cs-accent); }
</style>

<div class="cons-show-page">
    <header class="cons-show-hero">
        <div class="cons-show-hero-top">
            <div>
                <p class="cons-show-kicker">Consolidación</p>
                <h1 class="cons-show-title">{{ $consolidation->code }}</h1>
                <p class="cons-show-sub">Detalle del saco y paquetes incluidos</p>
            </div>
        </div>
        <div class="cons-show-toolbar">
            <a href="{{ route('consolidations.label', $consolidation->id) }}" target="_blank" class="cons-show-btn cons-show-btn--solid">Etiqueta del saco</a>
            <a href="{{ route('consolidations.report', $consolidation->id) }}" target="_blank" class="cons-show-btn cons-show-btn--solid">Reporte detallado</a>
            @if($consolidation->status === 'OPEN')
                <a href="{{ route('consolidations.edit', $consolidation->id) }}" class="cons-show-btn cons-show-btn--glass">Editar</a>
                @if($consolidation->items->count() > 0)
                    <form action="{{ route('consolidations.send', $consolidation->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de enviar este saco? Esto cambiará el estado a SENT y los paquetes con preregistro pasarán a IN_TRANSIT.');">
                        @csrf
                        <button type="submit" class="cons-show-btn cons-show-btn--success border-0">Enviar saco</button>
                    </form>
                @endif
                <form action="{{ route('consolidations.destroy', $consolidation->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este saco? Se quitarán los items y los preregistros quedarán disponibles de nuevo.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="cons-show-btn cons-show-btn--danger border-0">Eliminar saco</button>
                </form>
            @endif
            <a href="{{ route('consolidations.index', session('consolidations_index_filters', [])) }}" class="cons-show-btn cons-show-btn--glass">← Volver</a>
        </div>
    </header>

    <div class="cons-show-grid-main">
        <div class="cons-show-card">
            <div class="cons-show-card-h">Información</div>
            <div class="cons-show-card-b">
                <dl class="cons-show-dl">
                    <div>
                        <dt class="cons-show-dt">Código</dt>
                        <dd class="cons-show-dd">{{ $consolidation->code }}</dd>
                    </div>
                    <div>
                        <dt class="cons-show-dt">Tipo de servicio</dt>
                        <dd class="cons-show-dd">
                            <span class="cons-show-badge {{ $consolidation->service_type == 'AIR' ? 'cons-show-badge-air' : 'cons-show-badge-sea' }}">
                                {{ $consolidation->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="cons-show-dt">Estado</dt>
                        <dd class="cons-show-dd">
                            @php
                                $st = $consolidation->status;
                                $badgeClass = match ($st) {
                                    'OPEN' => 'cons-show-badge-open',
                                    'SENT' => 'cons-show-badge-sent',
                                    'RECEIVED' => 'cons-show-badge-received',
                                    'CANCELLED' => 'cons-show-badge-cancelled',
                                    default => 'cons-show-badge-sent',
                                };
                            @endphp
                            <span class="cons-show-badge {{ $badgeClass }}">{{ $st }}</span>
                        </dd>
                    </div>
                    @if($consolidation->notes)
                    <div>
                        <dt class="cons-show-dt">Notas</dt>
                        <dd class="cons-show-dd cons-show-dd-notes">{{ $consolidation->notes }}</dd>
                    </div>
                    @endif
                </dl>

                <div class="cons-show-divider">
                    <h3 class="cons-show-report-title">Reporte</h3>
                    <div class="cons-show-metrics">
                        <div class="cons-show-metric">
                            <div class="cons-show-metric-label">Total ítems</div>
                            <div class="cons-show-metric-value">{{ $report['total_items'] }}</div>
                        </div>
                        <div class="cons-show-metric">
                            <div class="cons-show-metric-label">Peso total</div>
                            <div class="cons-show-metric-value">{{ number_format($report['total_lbs'], 2) }} <span style="font-size:0.75rem;font-weight:600;color:#64748b;">lbs</span></div>
                        </div>
                        <div class="cons-show-metric">
                            <div class="cons-show-metric-label">Escaneados</div>
                            <div class="cons-show-metric-value cons-show-metric-value--ok">{{ $report['scanned_count'] }}</div>
                        </div>
                        <div class="cons-show-metric">
                            <div class="cons-show-metric-label">Faltantes</div>
                            <div class="cons-show-metric-value cons-show-metric-value--warn">{{ $report['missing_count'] }}</div>
                        </div>
                        @if(($report['unmatched_count'] ?? 0) > 0)
                        <div class="cons-show-metric cons-show-metric-wide cons-show-metric--amber">
                            <div class="cons-show-metric-label">Sin preregistro</div>
                            <div class="cons-show-metric-value">{{ $report['unmatched_count'] }} línea(s) solo con código</div>
                        </div>
                        @endif
                    </div>
                    @if($consolidation->status === 'SENT' && $consolidation->sent_at)
                        <div class="cons-show-sent-note">
                            <span class="cons-show-sent-label">Enviado el:</span> {{ $consolidation->sent_at->format('d/m/Y H:i') }}
                            <p class="cons-show-sent-hint">Este saco está disponible para escaneo en Nicaragua</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="cons-show-card">
            <div class="cons-show-card-h">Ítems en el saco ({{ $consolidation->items->count() }})</div>
            <div class="cons-show-card-b">
                @if($consolidation->items->count() > 0)
                    <div class="cons-show-item-list">
                        @foreach($consolidation->items as $item)
                            @if($item->preregistration)
                            <div class="cons-show-item cons-show-item--row {{ $item->scanned_at ? 'cons-show-item--ok' : '' }}">
                                <div class="cons-show-item-main">
                                    <div class="cons-show-item-name">{{ $item->preregistration->label_name }}</div>
                                    <div class="cons-show-item-code">{{ $item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? 'N/A' }}</div>
                                    <div class="cons-show-item-meta">{{ $item->preregistration->intake_weight_lbs }} lbs</div>
                                    @if($item->scanned_at)
                                        <div class="cons-show-item-tag">✓ Recibido en destino (escaneo)</div>
                                    @endif
                                </div>
                                @if($consolidation->status === 'OPEN')
                                <form action="{{ route('consolidations.items.destroy', [$consolidation->id, $item->id]) }}" method="POST" class="cons-show-item-actions" onsubmit="return confirm('¿Eliminar este paquete del saco? El preregistro quedará disponible para otro saco.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cons-show-item-remove" title="Eliminar del saco">Eliminar</button>
                                </form>
                                @endif
                            </div>
                            @else
                            <div class="cons-show-item cons-show-item--orphan cons-show-item--row">
                                <div class="cons-show-item-main">
                                    <div class="cons-show-item-orphan-label">Sin preregistro</div>
                                    <div class="cons-show-item-orphan-code">{{ $item->unmatched_code }}</div>
                                    <div class="cons-show-item-orphan-hint">Solo código guardado en el saco</div>
                                </div>
                                @if($consolidation->status === 'OPEN')
                                <form action="{{ route('consolidations.items.destroy', [$consolidation->id, $item->id]) }}" method="POST" class="cons-show-item-actions" onsubmit="return confirm('¿Eliminar este código del saco?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cons-show-item-remove" title="Eliminar del saco">Eliminar</button>
                                </form>
                                @endif
                            </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="cons-show-empty-p">No hay ítems en este saco</p>
                @endif
            </div>
        </div>
    </div>

    @if($consolidation->status === 'OPEN')
        @if(empty($mode))
            {{-- Pregunta inicial: cómo seguir editando el saco --}}
            <div class="cons-show-mode-prompt">
                <h3 class="cons-show-mode-prompt-title">¿Cómo quieres seguir editando este saco?</h3>
                <p class="cons-show-mode-prompt-sub">El saco está <strong>abierto</strong>. Puedes seguir agregando paquetes por escaneo (pistola) o seleccionándolos manualmente desde la lista. También puedes eliminar cualquier paquete del saco si te equivocaste.</p>
                <div class="cons-show-mode-grid">
                    <a href="{{ route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan']) }}" class="cons-show-mode-card">
                        <span class="cons-show-mode-card-icon" aria-hidden="true">▦</span>
                        <h4 class="cons-show-mode-card-title">Escanear código</h4>
                        <p class="cons-show-mode-card-text">Usa la pistola o teclea un tracking / warehouse. Se valida al instante contra los preregistros disponibles.</p>
                        <span class="cons-show-mode-card-cta">Continuar escaneando →</span>
                    </a>
                    <a href="{{ route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'select']) }}" class="cons-show-mode-card">
                        <span class="cons-show-mode-card-icon" aria-hidden="true">☰</span>
                        <h4 class="cons-show-mode-card-title">Seleccionar manualmente</h4>
                        <p class="cons-show-mode-card-text">Elige preregistros disponibles desde la tabla y agrégalos uno por uno.</p>
                        <span class="cons-show-mode-card-cta">Continuar seleccionando →</span>
                    </a>
                </div>
            </div>
        @else
            {{-- Tabs para alternar entre escaneo y selección en cualquier momento --}}
            <div class="cons-show-edit-tabs" role="tablist" aria-label="Modo de edición">
                <a href="{{ route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan']) }}"
                   class="cons-show-edit-tab {{ $mode === 'scan' ? 'cons-show-edit-tab--active' : '' }}"
                   role="tab" aria-selected="{{ $mode === 'scan' ? 'true' : 'false' }}">▦ Escanear código</a>
                <a href="{{ route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'select']) }}"
                   class="cons-show-edit-tab {{ $mode === 'select' ? 'cons-show-edit-tab--active' : '' }}"
                   role="tab" aria-selected="{{ $mode === 'select' ? 'true' : 'false' }}">☰ Seleccionar manualmente</a>
            </div>

            @if($mode === 'scan')
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
                <script type="application/json" id="cons-show-scan-lookup">@json($lookupJson)</script>
                <script type="application/json" id="cons-show-scan-meta">@json([
                    'service_type' => $consolidation->service_type,
                    'existing_codes' => $consolidation->items->map(function ($it) {
                        if ($it->preregistration) {
                            return strtoupper(trim((string) ($it->preregistration->warehouse_code ?? $it->preregistration->tracking_external ?? '')));
                        }
                        return strtoupper(trim((string) ($it->unmatched_code ?? '')));
                    })->filter()->values(),
                ])</script>

                <div class="cons-show-card">
                    <div class="cons-show-card-h">Agregar por escaneo ({{ $consolidation->service_type === 'AIR' ? 'Aéreo' : 'Marítimo' }})</div>
                    <div class="cons-show-card-b">
                        <form action="{{ route('consolidations.scan-item', $consolidation->id) }}" method="POST" id="cons-show-scan-form">
                            @csrf
                            <div class="cons-show-scan-box">
                                <label for="cons-show-scan-input" class="cons-show-scan-label">Tracking o código warehouse</label>
                                <input type="text" name="entry_code" id="cons-show-scan-input"
                                    class="cons-show-scan-input"
                                    placeholder="Enfoca aquí — listo para pistola"
                                    autocomplete="off" autocapitalize="characters" spellcheck="false" autofocus>
                                <p id="cons-show-scan-feedback" class="cons-show-scan-feedback" role="status"></p>
                            </div>
                            <p class="cons-show-scan-hint">Cada Enter o ráfaga de pistola agrega el código al saco. Si no coincide con un preregistro del mismo servicio, se guarda como código sin preregistro. Usa <strong>Eliminar</strong> en cada ítem si te equivocaste.</p>
                        </form>
                    </div>
                </div>
            @else
                <div class="cons-show-card" style="margin-top: 0.5rem;">
                    <div class="cons-show-card-h">Preregistros disponibles para agregar ({{ $availablePreregistrations->count() }})</div>
                    <div class="cons-show-card-b">
                        @if($availablePreregistrations->count() > 0)
                            <div class="cons-show-table-wrap">
                                <table class="cons-show-table">
                                    <thead>
                                        <tr>
                                            <th>Warehouse</th>
                                            <th>Nombre</th>
                                            <th>Peso (lbs)</th>
                                            <th>Fecha</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($availablePreregistrations as $preregistration)
                                        <tr>
                                            <td class="font-mono text-gray-600">{{ $preregistration->warehouse_code ?? $preregistration->tracking_external ?? 'N/A' }}</td>
                                            <td class="font-medium text-gray-900">{{ $preregistration->label_name }}</td>
                                            <td class="text-gray-500">{{ $preregistration->intake_weight_lbs }} lbs</td>
                                            <td class="text-gray-500">{{ $preregistration->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <form action="{{ route('consolidations.add-item', $consolidation->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="preregistration_id" value="{{ $preregistration->id }}">
                                                    <button type="submit" class="cons-show-table-add border-0">+ Agregar</button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="cons-show-empty">
                                <p class="cons-show-empty-title">No hay preregistros disponibles para agregar</p>
                                <p class="cons-show-empty-note">Todos los preregistros con estado RECEIVED_MIAMI y tipo {{ $consolidation->service_type }} ya están en otros sacos o no hay disponibles.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    @endif
</div>

@if($consolidation->status === 'OPEN' && $mode === 'scan')
@push('scripts')
<script>
(function() {
    var lookupEl = document.getElementById('cons-show-scan-lookup');
    var metaEl = document.getElementById('cons-show-scan-meta');
    var input = document.getElementById('cons-show-scan-input');
    var form = document.getElementById('cons-show-scan-form');
    var feedback = document.getElementById('cons-show-scan-feedback');
    if (!input || !form) return;

    var lookup = lookupEl ? JSON.parse(lookupEl.textContent || '[]') : [];
    var meta = metaEl ? JSON.parse(metaEl.textContent || '{}') : {};
    var serviceType = meta.service_type || 'AIR';
    var existingCodes = Array.isArray(meta.existing_codes) ? meta.existing_codes : [];

    var scanDebounceTimer = null;
    var SCAN_DEBOUNCE_MS = 250;
    var submitting = false;

    function norm(s) {
        return String(s || '').trim().toUpperCase();
    }

    function findInLookupSameSvc(code) {
        var n = norm(code);
        if (!n) return null;
        for (var i = 0; i < lookup.length; i++) {
            var row = lookup[i];
            if (row.service_type !== serviceType) continue;
            if (n === norm(row.tracking) || n === norm(row.warehouse)) return row;
        }
        return null;
    }

    function findOtherServiceMatch(code) {
        var n = norm(code);
        if (!n) return null;
        for (var i = 0; i < lookup.length; i++) {
            var row = lookup[i];
            if (row.service_type === serviceType) continue;
            if (n === norm(row.tracking) || n === norm(row.warehouse)) return row;
        }
        return null;
    }

    function setFeedback(text, cls) {
        feedback.textContent = text || '';
        feedback.className = 'cons-show-scan-feedback' + (cls ? ' ' + cls : '');
    }

    function clearScanDebounce() {
        if (scanDebounceTimer) {
            clearTimeout(scanDebounceTimer);
            scanDebounceTimer = null;
        }
    }

    function trySubmit() {
        if (submitting) return;
        var code = norm(input.value);
        if (!code) return;
        if (existingCodes.indexOf(code) !== -1) {
            setFeedback('Ese código ya está en el saco.', 'err');
            input.select();
            return;
        }
        var otherSvc = findOtherServiceMatch(code);
        if (otherSvc) {
            var sackWord = serviceType === 'AIR' ? 'aéreo' : 'marítimo';
            var pkgWord = otherSvc.service_type === 'AIR' ? 'aéreo' : 'marítimo';
            setFeedback('Alerta: el paquete está en preregistro como ' + pkgWord + ', no como ' + sackWord + '. Cambie el saco o use otro código.', 'err');
            input.select();
            return;
        }
        var hit = findInLookupSameSvc(code);
        setFeedback(hit ? 'Agregando ' + code + ' (' + (hit.label || 'preregistro') + ')…' : 'Agregando ' + code + ' sin preregistro…', hit ? 'ok' : 'warn');
        submitting = true;
        form.submit();
    }

    input.addEventListener('input', function() {
        clearScanDebounce();
        scanDebounceTimer = setTimeout(trySubmit, SCAN_DEBOUNCE_MS);
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (e.key === 'Tab' && norm(input.value) === '') return;
            e.preventDefault();
            clearScanDebounce();
            trySubmit();
        }
    });
    form.addEventListener('submit', function() {
        submitting = true;
    });

    setTimeout(function() {
        if (input) input.focus();
    }, 50);
})();
</script>
@endpush
@endif
@endsection
