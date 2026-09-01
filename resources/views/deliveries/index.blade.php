@extends('layouts.app')

@section('title', 'Salidas PrimeTrack')

@section('content')
<div class="inv-page">
    <x-module-banner section="Operaciones" current="Salidas" title="{{ auth()->user()?->isAgencyUser() ? 'Mis entregas' : 'Salidas PrimeTrack' }}" subtitle="{{ auth()->user()?->isAgencyUser() ? 'Hojas de salida de su cuenta. Consulte quién retiró y los paquetes entregados.' : 'Hojas de salida (SLO-) registradas. Cree una nueva hoja para elegir agencia, registrar quién retira y escanear los paquetes.' }}" :hide-back="(bool) auth()->user()?->isAgencyUser()">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h11.25v10.5H3.75V6.75Zm11.25 3h3.19a1.5 1.5 0 0 1 1.22.63l1.59 2.24v4.63H15V9.75ZM7.5 18.75a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm12 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            @unless(auth()->user()?->isAgencyUser())
            <a href="{{ route('salidas.create') }}" class="mb-btn mb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Crear hoja de salida
            </a>
            @endunless
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="inv-alert inv-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="inv-alert inv-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="inv-kpis">
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Hojas</span>
            <span class="inv-kpi-value">{{ number_format($statsTotal) }}</span>
            <span class="inv-kpi-note">Registradas{{ $selectedAgency ? ' para este cliente' : '' }}</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Este mes</span>
            <span class="inv-kpi-value">{{ number_format($statsMonth) }}</span>
            <span class="inv-kpi-note">Hojas con entregas</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Paquetes</span>
            <span class="inv-kpi-value">{{ number_format($statsPackagesMonth) }}</span>
            <span class="inv-kpi-note">Entregados este mes</span>
        </div>
        @unless(auth()->user()?->isAgencyUser())
        <div class="inv-kpi-card inv-kpi-card--green">
            <span class="inv-kpi-label">Listos</span>
            <span class="inv-kpi-value inv-text-green">{{ number_format($statsReady) }}</span>
            <span class="inv-kpi-note">Pendientes de salida</span>
        </div>
        @endunless
    </div>

    <div class="inv-card inv-filters-card">
        <form method="GET" action="{{ route('salidas.index') }}" class="inv-filters-form">
            <div class="inv-field inv-field-wide">
                <label class="inv-label" for="delivery_notes_q">Buscar</label>
                <input type="search" name="q" id="delivery_notes_q" class="inv-input" value="{{ $searchQuery ?? '' }}" placeholder="Código SLO, warehouse, tracking, cliente o quien retira" autocomplete="off">
            </div>
            @unless(auth()->user()?->isAgencyUser())
            <div class="inv-field">
                <label class="inv-label" for="agency_id">Cliente</label>
                <select name="agency_id" id="agency_id" class="inv-input">
                    <option value="">Todos</option>
                    @foreach($agenciesForSelect as $opt)
                    <option value="{{ $opt->id }}" @selected((string) $agencyId === (string) $opt->id)>{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            @endunless
            <div class="inv-filters-actions">
                <button class="inv-btn inv-btn-primary" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('salidas.index') }}" class="inv-clear-link">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="inv-card">
        <div class="inv-table-head">
            <span class="inv-table-head-note">Total: {{ number_format($deliveryNotes->total()) }} {{ $deliveryNotes->total() === 1 ? 'hoja' : 'hojas' }}</span>
            @unless(auth()->user()?->isAgencyUser())
            <a href="{{ route('salidas.create') }}" class="inv-btn inv-btn-primary inv-btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Crear hoja de salida
            </a>
            @endunless
        </div>
        <div class="inv-table-scroll">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th class="inv-num">Paq.</th>
                        <th>Retirado por</th>
                        <th class="inv-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveryNotes as $note)
                    @php
                        $firstDelivery = $note->firstDelivery;
                        $agencyName = $note->agency?->name ?? $firstDelivery?->preregistration?->agency?->name ?? '—';
                        $agencyCode = $note->agency?->code ?? $firstDelivery?->preregistration?->agency?->code;
                    @endphp
                    <tr>
                        <td><span class="inv-folio">{{ $note->code }}</span></td>
                        <td>
                            <div class="inv-client">{{ $agencyName }}</div>
                            @if($agencyCode)
                            <div class="inv-muted">{{ $agencyCode }}</div>
                            @endif
                        </td>
                        <td class="inv-nowrap">{{ $firstDelivery?->delivered_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? ($note->created_at ? $note->created_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '—') }}</td>
                        <td class="inv-num"><span class="inv-paq">{{ $note->deliveries_count }}</span></td>
                        <td>{{ $firstDelivery?->delivered_to ?? '—' }}</td>
                        <td class="inv-actions">
                            <a href="{{ route('salidas.print-report', ['delivery_note_id' => $note->id]) }}" target="_blank" class="inv-icon-btn" title="Ver / imprimir hoja">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                            @if(auth()->user()?->is_admin)
                            <a href="{{ route('salidas.hojas.edit', $note) }}" class="inv-icon-btn" title="Editar hoja (admin)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="inv-empty">
                            @if(!empty($searchQuery))
                                No hay hojas que coincidan con «{{ $searchQuery }}».
                            @elseif($selectedAgency)
                                No hay hojas de salida para este cliente.
                            @else
                                Aún no hay hojas de salida registradas.
                            @endif
                            @unless(auth()->user()?->isAgencyUser())
                            <a href="{{ route('salidas.create') }}">Crear hoja de salida</a>
                            @endunless
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deliveryNotes->total() > 0)
        <div class="inv-card-footer">
            <span class="inv-muted">{{ $deliveryNotes->firstItem() }} – {{ $deliveryNotes->lastItem() }} de {{ $deliveryNotes->total() }}</span>
            @if($deliveryNotes->hasPages())
            <div class="inv-pager-wrap">{{ $deliveryNotes->links('vendor.pagination.primetrack') }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.inv-page {
    --inv-navy: #0A2D6F;
    --inv-blue: #1E4FA8;
    --inv-green: #16794C;
    --inv-line: #E8EEF8;
    --inv-border: #C5D4EB;
    --inv-soft: #F4F8FD;
    --inv-muted: #5E6168;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}
.inv-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.inv-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; font-weight: 600; }
.inv-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.inv-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.15rem; }
.inv-kpi-card {
    background: #fff; border: 1px solid var(--inv-line); border-radius: 0.85rem;
    padding: 0.9rem 1.05rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex; flex-direction: column; gap: 0.28rem;
}
.inv-kpi-card--green { border-color: #A7DFC3; background: linear-gradient(180deg, #fff 40%, #F2FBF6 140%); }
.inv-kpi-label { font-size: 0.66rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.inv-kpi-value { font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.inv-kpi-note { font-size: 0.7rem; color: #94a3b8; }
.inv-card { background: #fff; border: 1px solid var(--inv-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; margin-bottom: 1.15rem; }
.inv-filters-card { padding: 0.9rem 1.1rem; overflow: visible; }
.inv-filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.inv-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 9rem; flex: 1; max-width: 18rem; }
.inv-field-wide { max-width: 28rem; flex: 2; }
.inv-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.inv-input { padding: 0.52rem 0.7rem; font-size: 0.85rem; border: 1px solid #D8DCE2; border-radius: 0.55rem; background: #fff; color: #0f172a; width: 100%; box-sizing: border-box; }
.inv-input:focus { outline: none; border-color: var(--inv-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.inv-filters-actions { display: flex; align-items: center; gap: 0.65rem; }
.inv-clear-link { font-size: 0.8rem; font-weight: 700; color: #64748b; text-decoration: none; }
.inv-clear-link:hover { color: var(--inv-navy); text-decoration: underline; }
.inv-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.6rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.inv-btn-primary { background: var(--inv-navy); color: #fff; border-color: var(--inv-navy); box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25); }
.inv-btn-primary:hover { background: var(--inv-blue); border-color: var(--inv-blue); color: #fff; }
.inv-btn-sm { padding: 0.42rem 0.85rem; font-size: 0.8rem; }
.inv-table-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.7rem; padding: 0.85rem 1.1rem; border-bottom: 1px solid var(--inv-line); }
.inv-table-head-note { font-size: 0.85rem; font-weight: 700; color: #334155; }
.inv-table-scroll { overflow-x: auto; }
.inv-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.inv-table thead th { background: linear-gradient(135deg, var(--inv-navy), var(--inv-blue)); color: #fff; text-align: left; padding: 0.62rem 0.85rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
.inv-table thead th.inv-num { text-align: right; }
.inv-table td { padding: 0.66rem 0.85rem; border-bottom: 1px solid #f4f7fb; color: #334155; vertical-align: middle; }
.inv-table tbody tr:last-child td { border-bottom: none; }
.inv-table tbody tr:hover td { background: var(--inv-soft); }
.inv-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.inv-th-actions, .inv-actions { text-align: right; white-space: nowrap; }
.inv-nowrap { white-space: nowrap; }
.inv-muted { color: #94a3b8; font-size: 0.75rem; }
.inv-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }
.inv-empty a { color: var(--inv-navy); font-weight: 700; }
.inv-folio { font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
.inv-client { font-weight: 700; color: #0f172a; }
.inv-paq { display: inline-flex; align-items: center; justify-content: center; min-width: 1.65rem; height: 1.65rem; padding: 0 0.35rem; border-radius: 999px; background: #EAF1FC; color: var(--inv-blue); font-size: 0.75rem; font-weight: 800; }
.inv-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 0.5rem; border: 1px solid #d1d9e6; background: #fff; color: #475569; text-decoration: none; margin-left: 0.2rem; }
.inv-icon-btn:hover { background: var(--inv-soft); color: var(--inv-navy); border-color: var(--inv-border); }
.inv-text-green { color: var(--inv-green); }
.inv-card-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.75rem 1.1rem; border-top: 1px solid var(--inv-line); }
.inv-pager-wrap .pt-pager { display: flex; align-items: center; gap: 0.3rem; }
.inv-pager-wrap .pt-pager-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 2rem; height: 2rem; padding: 0 0.45rem; border-radius: 0.45rem; border: 1px solid #d1d9e6; background: #fff; color: #334155; text-decoration: none; font-size: 0.8rem; font-weight: 700; }
.inv-pager-wrap .pt-pager-btn-active { background: var(--inv-navy); color: #fff; border-color: var(--inv-navy); }
.inv-pager-wrap .pt-pager-btn-disabled { opacity: 0.4; }
.inv-pager-wrap .pt-pager-ellipsis { color: #94a3b8; padding: 0 0.2rem; }
@media (max-width: 900px) { .inv-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .inv-field, .inv-field-wide { max-width: none; } }
</style>
@endsection
