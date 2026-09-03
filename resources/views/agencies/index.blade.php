@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="cx-page">
    <x-module-banner section="Administración" current="Clientes" title="Clientes PrimeTrack" subtitle="Cuentas y subagencias: datos, tipo de cuenta y accesos al panel.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('agencies.create') }}" class="mb-btn mb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nuevo cliente
            </a>
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="cx-alert cx-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="cx-alert cx-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="cx-kpis">
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Total</span>
            <span class="cx-kpi-value">{{ number_format($statsTotal ?? 0) }}</span>
            <span class="cx-kpi-note">{{ number_format($statsActive ?? 0) }} activa(s) · {{ number_format($statsInactive ?? 0) }} inactiva(s)</span>
        </div>
        <div class="cx-kpi-card cx-kpi-card--green">
            <span class="cx-kpi-label">Subagencias</span>
            <span class="cx-kpi-value cx-text-green">{{ number_format($statsSubagencies ?? 0) }}</span>
            <span class="cx-kpi-note">Partners y redes propias</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Clientes SLO</span>
            <span class="cx-kpi-value">{{ number_format($statsDirectClients ?? 0) }}</span>
            <span class="cx-kpi-note">Clientes propios de SkyLink One</span>
        </div>
    </div>

    <div class="cx-card cx-filters-card">
        <form method="GET" action="{{ route('agencies.index') }}" class="cx-filters-form">
            <div class="cx-field">
                <label class="cx-label" for="search">Cliente</label>
                <input type="text" name="search" id="search" class="cx-input" value="{{ request('search') }}" placeholder="Nombre…">
            </div>
            <div class="cx-field">
                <label class="cx-label" for="account_type">Tipo</label>
                <select name="account_type" id="account_type" class="cx-input">
                    <option value="">Todos los tipos</option>
                    <option value="subagency" @selected(request('account_type') === 'subagency')>Subagencia</option>
                    <option value="direct_client" @selected(request('account_type') === 'direct_client')>Cliente SLO</option>
                </select>
            </div>
            <div class="cx-field">
                <label class="cx-label" for="affiliation">Afiliación</label>
                <select name="affiliation" id="affiliation" class="cx-input">
                    <option value="">Todas</option>
                    <option value="slo" @selected(request('affiliation') === 'slo')>Hijas de SLO</option>
                    <option value="nested" @selected(request('affiliation') === 'nested')>Hijas de otra subagencia</option>
                </select>
            </div>
            <div class="cx-field">
                <label class="cx-label" for="is_active">Estado</label>
                <select name="is_active" id="is_active" class="cx-input">
                    <option value="">Todos los estados</option>
                    <option value="1" @selected(request('is_active') === '1')>Activa</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactiva</option>
                </select>
            </div>
            <div class="cx-filters-actions">
                <button class="cx-btn cx-btn-primary" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('agencies.index') }}" class="cx-btn cx-btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="cx-toolbar">
        <span class="cx-count">Total: <strong>{{ number_format($agencies->total()) }}</strong> {{ $agencies->total() === 1 ? 'registro' : 'registros' }}.</span>
    </div>

    <div class="cx-card">
        <div class="cx-table-scroll">
            <table class="cx-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Pertenece a</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Estado</th>
                        <th class="cx-th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agencies as $agency)
                    @php
                        $email = $agency->billing_email ?: $agency->users->first()?->email;
                    @endphp
                    <tr>
                        <td><a href="{{ route('agencies.show', $agency) }}" class="cx-folio">{{ $agency->code }}</a></td>
                        <td>
                            <div class="cx-client">{{ $agency->name }}</div>
                            <span class="cx-type-badge">{{ $agency->typeLabel() }}</span>
                        </td>
                        <td>{{ $agency->is_main || $agency->account_type === 'root' ? '—' : ($agency->parent->name ?? '—') }}</td>
                        <td class="cx-nowrap">{{ $agency->phone ?: '—' }}</td>
                        <td>{{ $email ?: '—' }}</td>
                        <td>
                            <span class="cx-status {{ $agency->is_active ? 'cx-status--paid' : 'cx-status--overdue' }}">{{ $agency->is_active ? 'Activa' : 'Inactiva' }}</span>
                        </td>
                        <td class="cx-actions">
                            <a href="{{ route('agencies.show', $agency) }}" class="cx-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                Ver
                            </a>
                            <a href="{{ route('agencies.edit', $agency) }}" class="cx-action-btn">Editar</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="cx-empty">
                            No hay clientes.
                            <a href="{{ route('agencies.create') }}" class="cx-folio">Crear el primero</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($agencies->hasPages())
        <div class="cx-card-footer">
            <div class="cx-pager-wrap">{{ $agencies->links('vendor.pagination.primetrack') }}</div>
        </div>
        @endif
    </div>
</div>

<style>
.cx-page {
    --cx-navy: #0A2D6F; --cx-blue: #1E4FA8; --cx-green: #16794C; --cx-red: #D64545;
    --cx-line: #E8EEF8; --cx-border: #C5D4EB; --cx-soft: #F4F8FD; --cx-muted: #5E6168;
    padding: 1.15rem 0 2.25rem; max-width: 96rem; margin: 0 auto; width: 100%;
}
.cx-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    background: #fff;
    border: 1px solid var(--cx-line);
    border-radius: 1rem;
    padding: 1.05rem 1.25rem 1.1rem;
    margin-bottom: 1.15rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.cx-breadcrumb { display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.45rem; }
.cx-breadcrumb strong { color: #334155; font-weight: 700; }
.cx-title-row { display: flex; align-items: center; gap: 0.6rem; }
.cx-title-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2.35rem; height: 2.35rem; border-radius: 0.65rem;
    background: linear-gradient(135deg, var(--cx-navy), var(--cx-blue));
    color: #fff; box-shadow: 0 6px 14px rgba(10, 45, 111, 0.28); flex-shrink: 0;
}
.cx-title { margin: 0; font-size: 1.45rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
.cx-subtitle { margin: 0.4rem 0 0; font-size: 0.875rem; color: var(--cx-muted); line-height: 1.45; max-width: 44rem; }
.cx-header-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; align-self: center; }
.cx-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.6rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
}
.cx-btn-primary { background: var(--cx-navy); color: #fff; border-color: var(--cx-navy); box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25); }
.cx-btn-primary:hover { background: var(--cx-blue); border-color: var(--cx-blue); color: #fff; }
.cx-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.cx-btn-secondary:hover { background: var(--cx-soft); color: var(--cx-navy); border-color: var(--cx-border); }
.cx-btn-sm { padding: 0.42rem 0.85rem; font-size: 0.8rem; }
.cx-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600; }
.cx-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; }
.cx-alert-danger { background: #FDECEC; border: 1px solid #F6C9C9; color: #B03030; }
.cx-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.15rem; }
.cx-kpi-card {
    background: #fff; border: 1px solid var(--cx-line); border-radius: 0.85rem;
    padding: 0.9rem 1.05rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex; flex-direction: column; gap: 0.28rem;
}
.cx-kpi-card--green { border-color: #A7DFC3; background: linear-gradient(180deg, #fff 40%, #F2FBF6 140%); }
.cx-kpi-label { font-size: 0.66rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.cx-kpi-value { font-size: 1.35rem; font-weight: 800; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; color: #0f172a; }
.cx-kpi-note { font-size: 0.7rem; color: #94a3b8; }
.cx-card { background: #fff; border: 1px solid var(--cx-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; margin-bottom: 1.15rem; }
.cx-filters-card { padding: 0.9rem 1.1rem; overflow: visible; }
.cx-filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.cx-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 10rem; flex: 1; max-width: 16rem; }
.cx-label { font-size: 0.8rem; font-weight: 700; color: #334155; }
.cx-input { padding: 0.52rem 0.7rem; font-size: 0.85rem; border: 1px solid #D8DCE2; border-radius: 0.55rem; background: #fff; color: #0f172a; width: 100%; box-sizing: border-box; }
.cx-input:focus { outline: none; border-color: var(--cx-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.cx-filters-actions { display: flex; align-items: center; gap: 0.55rem; }
.cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.6rem; margin: 0 0.1rem 0.75rem; }
.cx-back-link { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.85rem; font-weight: 700; color: var(--cx-blue); text-decoration: none; }
.cx-back-link:hover { color: var(--cx-navy); text-decoration: underline; }
.cx-toolbar-right { display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap; }
.cx-count { font-size: 0.85rem; color: #64748b; }
.cx-count strong { color: #0f172a; }
.cx-table-scroll { overflow-x: auto; }
.cx-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.cx-table thead th {
    background: var(--cx-navy); color: #fff; text-align: left; padding: 0.62rem 0.8rem;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap;
}
.cx-table td { padding: 0.7rem 0.8rem; border-bottom: 1px solid #f4f7fb; color: #334155; vertical-align: middle; }
.cx-table tbody tr:nth-child(even) td { background: #FAFCFF; }
.cx-table tbody tr:hover td { background: var(--cx-soft); }
.cx-th-actions, .cx-actions { text-align: right; }
.cx-actions { white-space: nowrap; }
.cx-nowrap { white-space: nowrap; }
.cx-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; }
.cx-folio { font-weight: 800; color: var(--cx-blue); text-decoration: none; }
.cx-folio:hover { color: var(--cx-navy); text-decoration: underline; }
.cx-client { font-weight: 700; color: #0f172a; }
.cx-type-badge {
    display: inline-flex; margin-top: 0.22rem; padding: 0.12rem 0.5rem; border-radius: 999px;
    background: #EFFAF4; color: #116039; font-size: 0.66rem; font-weight: 700; border: 1px solid #A7DFC3;
}
.cx-status { display: inline-flex; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.68rem; font-weight: 800; white-space: nowrap; }
.cx-status--paid { background: #EFFAF4; color: #116039; border: 1px solid #A7DFC3; }
.cx-status--overdue { background: #FDECEC; color: #B03030; border: 1px solid #F6C9C9; }
.cx-action-btn {
    display: inline-flex; align-items: center; gap: 0.28rem; padding: 0.32rem 0.6rem;
    font-size: 0.72rem; font-weight: 700; border-radius: 0.45rem; border: 1px solid #C5D4EB;
    background: #fff; color: var(--cx-blue); text-decoration: none; margin-left: 0.25rem;
}
.cx-action-btn:hover { background: var(--cx-soft); color: var(--cx-navy); }
.cx-card-footer { padding: 0.75rem 1.1rem; border-top: 1px solid var(--cx-line); }
.cx-pager-wrap .pt-pager { display: flex; align-items: center; gap: 0.3rem; }
.cx-pager-wrap .pt-pager-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2rem; height: 2rem; padding: 0 0.45rem;
    border-radius: 0.45rem; border: 1px solid #d1d9e6;
    background: #fff; color: #334155; text-decoration: none; font-size: 0.8rem; font-weight: 700;
}
.cx-pager-wrap .pt-pager-btn-active { background: var(--cx-navy); color: #fff; border-color: var(--cx-navy); }
.cx-text-green { color: var(--cx-green); }
@media (max-width: 900px) { .cx-kpis { grid-template-columns: 1fr; } .cx-field { max-width: none; } }
</style>
@endsection
