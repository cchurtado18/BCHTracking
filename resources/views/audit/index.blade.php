@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $actionMeta = [
        'created' => ['label' => 'Creado', 'class' => 'is-created'],
        'updated' => ['label' => 'Modificado', 'class' => 'is-updated'],
        'deleted' => ['label' => 'Eliminado', 'class' => 'is-deleted'],
        'admin_reset_to_miami' => ['label' => 'Admin: volver a Miami', 'class' => 'is-admin'],
        'admin_change_intake_type' => ['label' => 'Admin: tipo de ingreso', 'class' => 'is-admin'],
    ];
@endphp
<div class="audit-page">
    {{-- ===== Banner ===== --}}
    <header class="audit-hero">
        <div class="audit-hero-inner">
            <div class="audit-hero-text">
                <h1 class="audit-hero-title">Auditoría</h1>
                <p class="audit-hero-subtitle">Trazabilidad de todas las acciones realizadas sobre los paquetes del sistema.</p>
            </div>
            <span class="audit-hero-count">{{ number_format($logs->total()) }} {{ $logs->total() === 1 ? 'evento' : 'eventos' }}</span>
        </div>
    </header>

    {{-- ===== KPIs ===== --}}
    <div class="audit-stats">
        <div class="audit-stat-card">
            <span class="audit-stat-icon is-total">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <div>
                <span class="audit-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
                <span class="audit-stat-label">Eventos totales</span>
            </div>
        </div>
        <div class="audit-stat-card">
            <span class="audit-stat-icon is-created">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m-7-7h14"/></svg>
            </span>
            <div>
                <span class="audit-stat-value">{{ number_format($statsCreated ?? 0) }}</span>
                <span class="audit-stat-label">Creados</span>
            </div>
        </div>
        <div class="audit-stat-card">
            <span class="audit-stat-icon is-updated">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            </span>
            <div>
                <span class="audit-stat-value">{{ number_format($statsUpdated ?? 0) }}</span>
                <span class="audit-stat-label">Modificados</span>
            </div>
        </div>
        <div class="audit-stat-card">
            <span class="audit-stat-icon is-deleted">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            </span>
            <div>
                <span class="audit-stat-value">{{ number_format($statsDeleted ?? 0) }}</span>
                <span class="audit-stat-label">Eliminados</span>
            </div>
        </div>
    </div>

    {{-- ===== Barra de filtros ===== --}}
    <form method="GET" action="{{ route('audit.index') }}" class="audit-toolbar">
        <div class="audit-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="audit-search-icon"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por código, peso, estado..." class="audit-search-input">
        </div>
        <select name="action" class="audit-toolbar-select" onchange="this.form.submit()">
            <option value="">Todas las acciones</option>
            @foreach($actionMeta as $key => $meta)
            <option value="{{ $key }}" {{ request('action') == $key ? 'selected' : '' }}>{{ $meta['label'] }}</option>
            @endforeach
        </select>
        <select name="user_id" class="audit-toolbar-select" onchange="this.form.submit()">
            <option value="">Todos los usuarios</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" {{ (int) request('user_id') === (int) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="audit-toolbar-date" title="Desde" onchange="this.form.submit()">
        <span class="audit-toolbar-sep">→</span>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="audit-toolbar-date" title="Hasta" onchange="this.form.submit()">
        <button type="submit" class="audit-btn audit-btn-primary">Buscar</button>
        @if(request()->hasAny(['search', 'action', 'user_id', 'date_from', 'date_to']))
        <a href="{{ route('audit.index') }}" class="audit-btn audit-btn-clear">Limpiar ✕</a>
        @endif
    </form>

    {{-- ===== Registro de actividad ===== --}}
    <div class="audit-card">
        @php $prevDay = null; @endphp
        <div class="audit-feed">
            @forelse($logs as $log)
            @php
                $local = $log->created_at->timezone($displayTz);
                $day = $local->toDateString();
                $dayLabel = $local->isToday() ? 'Hoy' : ($local->isYesterday() ? 'Ayer' : ucfirst($local->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY')));
                $meta = $actionMeta[$log->action] ?? ['label' => $log->action_label, 'class' => 'is-admin'];
            @endphp
            @if($day !== $prevDay)
            <div class="audit-day-sep"><span>{{ $dayLabel }}</span></div>
            @php $prevDay = $day; @endphp
            @endif
            <a href="{{ route('audit.show', $log->id) }}" class="audit-row">
                <span class="audit-row-icon {{ $meta['class'] }}">
                    @if($log->action === 'created')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14m-7-7h14"/></svg>
                    @elseif($log->action === 'updated')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    @elseif($log->action === 'deleted')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                    @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                    @endif
                </span>
                <div class="audit-row-main">
                    <div class="audit-row-top">
                        <span class="audit-badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                        <span class="audit-row-type">{{ $log->auditable_label }} #{{ $log->auditable_id }}</span>
                    </div>
                    <p class="audit-row-summary">{{ Str::limit($log->summary ?: 'Sin resumen', 130) }}</p>
                </div>
                <div class="audit-row-side">
                    <span class="audit-row-user">
                        <span class="audit-avatar">{{ strtoupper(mb_substr($log->user?->name ?? '?', 0, 1)) }}</span>
                        {{ $log->user?->name ?? 'Sistema' }}
                    </span>
                    <span class="audit-row-time">{{ $local->format('H:i:s') }}</span>
                </div>
                <span class="audit-row-arrow">›</span>
            </a>
            @empty
            <div class="audit-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="audit-empty-icon"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                <p class="audit-empty-text">No hay eventos de auditoría con los filtros aplicados.</p>
            </div>
            @endforelse
        </div>

        @if($logs->total() > 0)
        <div class="audit-card-footer">
            <span class="audit-pagination-info">{{ $logs->firstItem() }} – {{ $logs->lastItem() }} de {{ number_format($logs->total()) }}</span>
            @if($logs->hasPages())
            <div class="audit-pagination-links">{{ $logs->links() }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.audit-page { padding: 1.25rem 0 2.5rem; max-width: 76rem; margin: 0 auto; width: 100%; }

/* ===== Banner ===== */
.audit-hero {
    background: linear-gradient(135deg, #047857 0%, #059669 50%, #10b981 100%);
    border-radius: 1rem; padding: 1.6rem 1.5rem; margin-bottom: 1.25rem;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}
.audit-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.audit-hero-title { margin: 0; font-size: 1.6rem; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
.audit-hero-subtitle { margin: 0.3rem 0 0; font-size: 0.9rem; color: rgba(255,255,255,0.88); max-width: 56ch; }
.audit-hero-count {
    background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.35); color: #fff;
    border-radius: 999px; padding: 0.35rem 0.9rem; font-size: 0.8rem; font-weight: 700;
}

/* ===== KPIs ===== */
.audit-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.85rem; margin-bottom: 1.25rem; }
.audit-stat-card {
    background: #fff; border-radius: 0.85rem; padding: 1rem 1.15rem; border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15,23,42,0.05); display: flex; align-items: center; gap: 0.85rem;
}
.audit-stat-icon {
    width: 2.6rem; height: 2.6rem; border-radius: 0.7rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.audit-stat-icon svg { width: 1.2rem; height: 1.2rem; }
.audit-stat-icon.is-total { background: #f1f5f9; color: #475569; }
.audit-stat-icon.is-created { background: #d1fae5; color: #047857; }
.audit-stat-icon.is-updated { background: #dbeafe; color: #1d4ed8; }
.audit-stat-icon.is-deleted { background: #fee2e2; color: #b91c1c; }
.audit-stat-value { display: block; font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
.audit-stat-label { font-size: 0.72rem; font-weight: 600; color: #64748b; }

/* ===== Toolbar de filtros ===== */
.audit-toolbar {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 0.85rem;
    padding: 0.7rem 0.85rem; margin-bottom: 1.25rem; box-shadow: 0 1px 3px rgba(15,23,42,0.05);
}
.audit-search { position: relative; flex: 1 1 220px; min-width: 200px; }
.audit-search-icon { position: absolute; left: 0.7rem; top: 50%; transform: translateY(-50%); width: 0.95rem; height: 0.95rem; color: #94a3b8; pointer-events: none; }
.audit-search-input {
    width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.2rem; font-size: 0.85rem;
    border: 1px solid #e2e8f0; border-radius: 0.55rem; background: #f8fafc; color: #0f172a;
}
.audit-search-input:focus { outline: none; border-color: #059669; background: #fff; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12); }
.audit-toolbar-select, .audit-toolbar-date {
    padding: 0.5rem 0.65rem; font-size: 0.83rem; font-weight: 600; color: #334155;
    border: 1px solid #e2e8f0; border-radius: 0.55rem; background: #fff; cursor: pointer;
}
.audit-toolbar-select:focus, .audit-toolbar-date:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12); }
.audit-toolbar-sep { color: #94a3b8; font-size: 0.8rem; }

.audit-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 0.95rem; font-size: 0.83rem; font-weight: 650; border-radius: 0.55rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; white-space: nowrap; }
.audit-btn-primary { background: #059669; color: #fff; border-color: #059669; }
.audit-btn-primary:hover { background: #047857; border-color: #047857; color: #fff; }
.audit-btn-clear { background: #fff; color: #dc2626; border-color: #fecaca; }
.audit-btn-clear:hover { background: #fef2f2; }

/* ===== Feed de actividad ===== */
.audit-card { background: #fff; border-radius: 0.85rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(15,23,42,0.05); overflow: hidden; }
.audit-feed { display: flex; flex-direction: column; }

.audit-day-sep {
    padding: 0.55rem 1.25rem; background: #f8fafc; border-bottom: 1px solid #eef2f7;
    font-size: 0.7rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; color: #64748b;
    position: sticky; top: 0; z-index: 1;
}
.audit-feed > .audit-day-sep:not(:first-child) { border-top: 1px solid #eef2f7; }

.audit-row {
    display: grid; grid-template-columns: 2.4rem 1fr auto 1rem; gap: 0.9rem; align-items: center;
    padding: 0.85rem 1.25rem; border-bottom: 1px solid #f1f5f9;
    text-decoration: none; transition: background 0.12s;
}
.audit-row:last-child { border-bottom: none; }
.audit-row:hover { background: #f8fafc; }
.audit-row-icon {
    width: 2.4rem; height: 2.4rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.audit-row-icon svg { width: 1.05rem; height: 1.05rem; }
.audit-row-icon.is-created { background: #d1fae5; color: #047857; }
.audit-row-icon.is-updated { background: #dbeafe; color: #1d4ed8; }
.audit-row-icon.is-deleted { background: #fee2e2; color: #b91c1c; }
.audit-row-icon.is-admin { background: #fef3c7; color: #b45309; }

.audit-row-main { min-width: 0; }
.audit-row-top { display: flex; align-items: center; gap: 0.55rem; flex-wrap: wrap; }
.audit-badge { display: inline-block; padding: 0.18rem 0.5rem; font-size: 0.66rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; border-radius: 0.35rem; }
.audit-badge.is-created { background: #d1fae5; color: #047857; }
.audit-badge.is-updated { background: #dbeafe; color: #1d4ed8; }
.audit-badge.is-deleted { background: #fee2e2; color: #b91c1c; }
.audit-badge.is-admin { background: #fef3c7; color: #b45309; }
.audit-row-type { font-size: 0.72rem; font-weight: 650; color: #94a3b8; }
.audit-row-summary { margin: 0.3rem 0 0; font-size: 0.86rem; color: #334155; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; }

.audit-row-side { display: flex; flex-direction: column; align-items: flex-end; gap: 0.3rem; flex-shrink: 0; }
.audit-row-user { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; font-weight: 650; color: #475569; }
.audit-avatar {
    width: 1.5rem; height: 1.5rem; border-radius: 999px; background: linear-gradient(135deg, #059669, #10b981);
    color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 0.68rem; font-weight: 800; flex-shrink: 0;
}
.audit-row-time { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.72rem; color: #94a3b8; }
.audit-row-arrow { color: #cbd5e1; font-size: 1.1rem; font-weight: 700; }
.audit-row:hover .audit-row-arrow { color: #059669; }

@media (max-width: 640px) {
    .audit-row { grid-template-columns: 2.4rem 1fr; }
    .audit-row-side { grid-column: 2; flex-direction: row; align-items: center; gap: 0.75rem; }
    .audit-row-arrow { display: none; }
}

/* ===== Vacío / paginación ===== */
.audit-empty { text-align: center; padding: 3.5rem 1rem; color: #94a3b8; }
.audit-empty-icon { width: 2.4rem; height: 2.4rem; margin: 0 auto 0.75rem; display: block; color: #cbd5e1; }
.audit-empty-text { margin: 0; font-size: 0.9rem; }
.audit-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.83rem; color: #64748b; }
.audit-pagination-info { font-weight: 600; }
.audit-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.audit-pagination-links a, .audit-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8rem; border-radius: 0.4rem; border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none; }
.audit-pagination-links a:hover { background: #f8fafc; color: #059669; }
.audit-pagination-links .disabled span { background: #f8fafc; color: #cbd5e1; }
.audit-pagination-links .active span { background: #059669; color: #fff; border-color: #059669; }
</style>
@endsection
