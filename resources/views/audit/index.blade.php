@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $agencyNames = $agencyNames ?? [];
    $recipientNames = $recipientNames ?? [];
    $actionOptions = $actionOptions ?? \App\Models\AuditLog::actionOptions();
    $typeOptions = $typeOptions ?? \App\Models\AuditLog::typeOptions();
@endphp
<div class="cx-page">
    <x-module-banner section="Administración" current="Auditoría" title="Auditoría PrimeTrack" subtitle="Quién hizo cada cambio, cuándo, sobre qué registro y con qué datos.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7.5 3.75h9a1.5 1.5 0 0 1 1.5 1.5v13.5a1.5 1.5 0 0 1-1.5 1.5h-9a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <span class="mb-btn mb-btn-secondary">{{ number_format($logs->total()) }} {{ $logs->total() === 1 ? 'evento' : 'eventos' }}</span>
        </x-slot:actions>
    </x-module-banner>

    <div class="cx-kpis">
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Eventos</span>
            <span class="cx-kpi-value">{{ number_format($statsTotal ?? 0) }}</span>
            <span class="cx-kpi-note">Con los filtros actuales</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Creados</span>
            <span class="cx-kpi-value">{{ number_format($statsCreated ?? 0) }}</span>
            <span class="cx-kpi-note">Altas de registros</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Modificados</span>
            <span class="cx-kpi-value">{{ number_format($statsUpdated ?? 0) }}</span>
            <span class="cx-kpi-note">Cambios de datos</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Eliminados</span>
            <span class="cx-kpi-value">{{ number_format($statsDeleted ?? 0) }}</span>
            <span class="cx-kpi-note">Bajas o anulaciones</span>
        </div>
    </div>

    <div class="cx-card cx-filters-card">
        <form method="GET" action="{{ route('audit.index') }}" class="cx-filters-form">
            <div class="cx-field cx-field-search">
                <label class="cx-label" for="search">Buscar</label>
                <input type="text" name="search" id="search" class="cx-input" value="{{ request('search') }}" placeholder="Código, tracking, folio, nombre…">
            </div>
            <div class="cx-field">
                <label class="cx-label" for="action">Acción</label>
                <select name="action" id="action" class="cx-input">
                    <option value="">Todas</option>
                    @foreach($actionOptions as $key => $label)
                    <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cx-field">
                <label class="cx-label" for="auditable_type">Módulo</label>
                <select name="auditable_type" id="auditable_type" class="cx-input">
                    <option value="">Todos</option>
                    @foreach($typeOptions as $key => $label)
                    <option value="{{ $key }}" @selected(request('auditable_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cx-field">
                <label class="cx-label" for="user_id">Quién</label>
                <select name="user_id" id="user_id" class="cx-input">
                    <option value="">Todos</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected((int) request('user_id') === (int) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cx-field">
                <label class="cx-label" for="date_from">Desde</label>
                <input type="date" name="date_from" id="date_from" class="cx-input" value="{{ request('date_from') }}">
            </div>
            <div class="cx-field">
                <label class="cx-label" for="date_to">Hasta</label>
                <input type="date" name="date_to" id="date_to" class="cx-input" value="{{ request('date_to') }}">
            </div>
            <div class="cx-filters-actions">
                <button class="cx-btn cx-btn-primary" type="submit">Filtrar</button>
                <a href="{{ route('audit.index') }}" class="cx-btn cx-btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="cx-toolbar">
        <span class="cx-count">Total: <strong>{{ number_format($logs->total()) }}</strong> {{ $logs->total() === 1 ? 'evento' : 'eventos' }}.</span>
    </div>

    <div class="cx-card">
        <div class="cx-table-scroll">
            <table class="cx-table">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Quién</th>
                        <th>Acción</th>
                        <th>Módulo</th>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Qué ocurrió</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    @php
                        $local = $log->created_at->timezone($displayTz);
                        $code = $log->displayCode();
                        $agencyId = $log->snapshotAgencyId();
                        $recipientId = $log->snapshotAgencyClientId();
                    @endphp
                    <tr class="cx-clickable" data-href="{{ route('audit.show', $log) }}">
                        <td class="cx-nowrap">
                            <div class="cx-strong">{{ $local->format('d/m/Y') }}</div>
                            <div class="cx-muted">{{ $local->format('H:i:s') }}</div>
                        </td>
                        <td>
                            <div class="cx-strong">{{ $log->actorName() }}</div>
                            <div class="cx-muted">{{ $log->actorRoleLabel() }}@if($log->actorEmail()) · {{ $log->actorEmail() }}@endif</div>
                        </td>
                        <td><span class="cx-status {{ $log->actionClass() }}">{{ $log->action_label }}</span></td>
                        <td>{{ $log->auditable_label }}</td>
                        <td class="cx-folio">{{ $code ?: '—' }}</td>
                        <td>
                            <div>{{ $agencyId ? ($agencyNames[$agencyId] ?? '—') : '—' }}</div>
                            @if($recipientId && ($recipientNames[$recipientId] ?? null))
                            <div class="cx-muted">{{ $recipientNames[$recipientId] }}</div>
                            @endif
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($log->summary ?: 'Sin resumen', 90) }}</td>
                        <td class="cx-muted">{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="cx-empty">No hay eventos con los filtros actuales.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->total() > 0)
        <div class="cx-card-footer">
            <span class="cx-muted">{{ $logs->firstItem() }} – {{ $logs->lastItem() }} de {{ number_format($logs->total()) }}</span>
            @if($logs->hasPages())
            <div>{{ $logs->links('vendor.pagination.primetrack') }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.cx-page { --cx-navy:#0A2D6F; --cx-blue:#1E4FA8; --cx-line:#E8EEF8; --cx-soft:#F4F8FD; padding:1.15rem 0 2.25rem; max-width:96rem; margin:0 auto; width:100%; }
.cx-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:0.75rem; margin-bottom:1.15rem; }
.cx-kpi-card { background:#fff; border:1px solid var(--cx-line); border-radius:0.85rem; padding:0.9rem 1.05rem; box-shadow:0 2px 8px rgba(15,23,42,0.04); display:flex; flex-direction:column; gap:0.28rem; }
.cx-kpi-label { font-size:0.66rem; font-weight:800; text-transform:uppercase; letter-spacing:0.07em; color:#94a3b8; }
.cx-kpi-value { font-size:1.35rem; font-weight:800; letter-spacing:-0.02em; }
.cx-kpi-note { font-size:0.7rem; color:#94a3b8; }
.cx-card { background:#fff; border:1px solid var(--cx-line); border-radius:0.85rem; box-shadow:0 2px 8px rgba(15,23,42,0.04); overflow:hidden; margin-bottom:1.15rem; }
.cx-filters-card { padding:0.9rem 1.1rem; overflow:visible; }
.cx-filters-form { display:flex; flex-wrap:wrap; align-items:flex-end; gap:0.7rem; }
.cx-field { display:flex; flex-direction:column; gap:0.28rem; min-width:9.5rem; flex:1; max-width:16rem; }
.cx-field-search { min-width:14rem; max-width:22rem; }
.cx-label { font-size:0.8rem; font-weight:700; color:#334155; }
.cx-input { padding:0.52rem 0.7rem; font-size:0.85rem; border:1px solid #D8DCE2; border-radius:0.55rem; background:#fff; color:#0f172a; width:100%; box-sizing:border-box; }
.cx-input:focus { outline:none; border-color:var(--cx-blue); box-shadow:0 0 0 3px rgba(30,79,168,0.15); }
.cx-filters-actions { display:flex; align-items:center; gap:0.55rem; }
.cx-btn { display:inline-flex; align-items:center; justify-content:center; gap:0.4rem; padding:0.58rem 1.05rem; font-size:0.875rem; font-weight:700; border-radius:0.6rem; border:1px solid transparent; cursor:pointer; text-decoration:none; }
.cx-btn-primary { background:var(--cx-navy); color:#fff; border-color:var(--cx-navy); }
.cx-btn-primary:hover { background:var(--cx-blue); color:#fff; }
.cx-btn-secondary { background:#fff; color:#334155; border-color:#d1d9e6; }
.cx-toolbar { display:flex; justify-content:flex-end; margin:0 0.1rem 0.75rem; }
.cx-count { font-size:0.85rem; color:#64748b; }
.cx-count strong { color:#0f172a; }
.cx-table-scroll { overflow-x:auto; }
.cx-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.cx-table thead th { background:var(--cx-navy); color:#fff; text-align:left; padding:0.62rem 0.8rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; white-space:nowrap; }
.cx-table td { padding:0.7rem 0.8rem; border-bottom:1px solid #f4f7fb; color:#334155; vertical-align:top; }
.cx-table tbody tr:nth-child(even) td { background:#FAFCFF; }
.cx-table tbody tr:hover td { background:var(--cx-soft); }
.cx-clickable { cursor:pointer; }
.cx-strong { font-weight:700; color:#0f172a; }
.cx-muted { color:#94a3b8; font-size:0.75rem; }
.cx-nowrap { white-space:nowrap; }
.cx-folio { font-weight:800; color:var(--cx-blue); }
.cx-empty { padding:1.4rem 1rem; text-align:center; color:#94a3b8; }
.cx-card-footer { display:flex; flex-wrap:wrap; justify-content:space-between; gap:0.75rem; padding:0.75rem 1rem; border-top:1px solid var(--cx-line); }
.cx-status { display:inline-block; padding:0.18rem 0.5rem; font-size:0.68rem; font-weight:800; border-radius:0.35rem; text-transform:uppercase; letter-spacing:0.03em; }
.cx-status.is-created { background:#E8EEF8; color:#0A2D6F; }
.cx-status.is-updated { background:#dbeafe; color:#1d4ed8; }
.cx-status.is-deleted { background:#FDECEC; color:#B03030; }
.cx-status.is-admin { background:#FFF4D6; color:#8A5A00; }
@media (max-width:900px) { .cx-kpis { grid-template-columns:1fr 1fr; } }
@media (max-width:640px) { .cx-kpis { grid-template-columns:1fr; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cx-clickable').forEach(function (row) {
        row.addEventListener('click', function () {
            var href = row.getAttribute('data-href');
            if (href) window.location.href = href;
        });
    });
});
</script>
@endsection
