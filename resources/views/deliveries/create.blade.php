@extends('layouts.app')

@section('title', 'Crear hoja de salida')

@section('content')
<div class="inv-page">
    <x-module-banner section="Operaciones" current="Nueva hoja" title="Crear hoja de salida" subtitle="Seleccione la agencia, revise los paquetes listos y pulse Iniciar salida para registrar quién retira y escanear." back-href="{{ route('salidas.index') }}" back-label="Volver a Salidas">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if(session('success'))
    <div class="inv-alert inv-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="inv-alert inv-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="inv-card inv-filters-card">
        <form method="GET" action="{{ route('salidas.create') }}" class="inv-filters-form" id="deliveryAgencyForm">
            <div class="inv-field inv-field-wide">
                <label class="inv-label" for="agency_id">¿Para qué agencia es la salida?</label>
                <select name="agency_id" id="agency_id" class="inv-input">
                    <option value="">Seleccione la agencia…</option>
                    @foreach($agenciesForSelect as $opt)
                    <option value="{{ $opt->id }}" @selected((string) $agencyId === (string) $opt->id)>{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inv-filters-actions">
                <button type="submit" class="inv-btn inv-btn-primary">Ver paquetes</button>
                @if($agencyId)
                <a href="{{ route('salidas.create', ['clear_agency' => 1]) }}" class="inv-clear-link">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    @if($selectedAgency)
    <div class="inv-card">
        <div class="inv-table-head">
            <span class="inv-table-head-note">Paquetes listos para retiro — {{ $selectedAgency->name }}</span>
            @if($availableTotal > 0)
            <a href="{{ route('salidas.batch', array_filter(['agency_id' => $selectedAgency->id, 'service_type' => $serviceType])) }}" class="inv-btn inv-btn-primary inv-btn-sm">Iniciar salida</a>
            @endif
        </div>
        <div class="inv-card-body">
            @if(($availableAir + $availableSea) === 0)
            <div class="inv-empty">
                <p class="inv-empty-title">Esta agencia no tiene paquetes listos para retirar</p>
                <p>No hay paquetes en estado «Listo para retiro» para {{ $selectedAgency->name }}. Seleccione otra agencia o espere a que los paquetes estén listos.</p>
            </div>
            @else
            <div class="inv-service-filter">
                <span class="inv-service-label">Servicio:</span>
                <a href="{{ route('salidas.create', ['agency_id' => $selectedAgency->id]) }}" class="inv-chip {{ !$serviceType ? 'is-active' : '' }}">Todos ({{ $availableAir + $availableSea }})</a>
                <a href="{{ route('salidas.create', ['agency_id' => $selectedAgency->id, 'service_type' => 'AIR']) }}" class="inv-chip {{ $serviceType === 'AIR' ? 'is-active' : '' }}">Aéreo ({{ $availableAir }})</a>
                <a href="{{ route('salidas.create', ['agency_id' => $selectedAgency->id, 'service_type' => 'SEA']) }}" class="inv-chip {{ $serviceType === 'SEA' ? 'is-active' : '' }}">Marítimo ({{ $availableSea }})</a>
                <a href="{{ route('salidas.create', ['agency_id' => $selectedAgency->id, 'service_type' => 'CFT']) }}" class="inv-chip {{ $serviceType === 'CFT' ? 'is-active' : '' }}">Pie cúbico ({{ $availableCft ?? 0 }})</a>
            </div>
            <p class="inv-hint">{{ $availableTotal }} {{ $availableTotal === 1 ? 'paquete listo' : 'paquetes listos' }}@if($serviceType) — {{ \App\Support\ServiceType::label($serviceType) }}@else ({{ $availableAir }} aéreo, {{ $availableSea }} marítimo, {{ $availableCft ?? 0 }} pie cúbico)@endif. Use «Iniciar salida» para escanear y registrar la entrega.</p>
            <div class="inv-table-scroll">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>Cliente (etiqueta)</th>
                            <th>Warehouse</th>
                            <th>Tracking</th>
                            <th>Servicio</th>
                            <th class="inv-num">Peso (lbs)</th>
                            <th>Agencia</th>
                            <th>Listo desde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($availablePackages as $p)
                        <tr>
                            <td title="{{ $p->label_name }}"><span class="inv-client">{{ Str::limit($p->label_name, 25) }}</span></td>
                            <td><span class="inv-folio">{{ $p->warehouse_code ?? '—' }}</span></td>
                            <td class="inv-muted" title="{{ $p->tracking_external }}">{{ Str::limit($p->tracking_external, 18) }}</td>
                            <td>
                                <span class="inv-type inv-type--{{ strtolower($p->service_type ?? '') }}">{{ \App\Support\ServiceType::label($p->service_type) }}</span>
                            </td>
                            <td class="inv-num">{{ $p->verified_weight_lbs ?? $p->intake_weight_lbs ?? '—' }}</td>
                            <td class="inv-muted" title="{{ $p->agency->name ?? '' }}">{{ Str::limit($p->agency->name ?? '—', 20) }}</td>
                            <td class="inv-nowrap inv-muted">{{ $p->ready_at ? $p->ready_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="inv-card">
        <div class="inv-empty">
            <p class="inv-empty-title">Seleccione una agencia</p>
            <p>Elija la agencia en el selector de arriba y pulse «Ver paquetes» para ver si tiene paquetes listos para retirar.</p>
        </div>
    </div>
    @endif
</div>

<style>
.inv-page {
    --inv-navy: #0A2D6F;
    --inv-blue: #1E4FA8;
    --inv-green: #16794C;
    --inv-line: #E8EEF8;
    --inv-border: #C5D4EB;
    --inv-soft: #F4F8FD;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}
.inv-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.inv-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; font-weight: 600; }
.inv-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.inv-card { background: #fff; border: 1px solid var(--inv-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; margin-bottom: 1.15rem; }
.inv-filters-card { padding: 0.9rem 1.1rem; overflow: visible; }
.inv-filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.inv-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 12rem; flex: 1; max-width: 28rem; }
.inv-field-wide { max-width: 36rem; }
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
.inv-card-body { padding: 1rem 1.1rem 1.15rem; }
.inv-service-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.85rem; }
.inv-service-label { font-size: 0.8rem; font-weight: 700; color: #334155; }
.inv-chip { display: inline-flex; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 700; border-radius: 999px; border: 1px solid #d1d9e6; background: #fff; color: #334155; text-decoration: none; }
.inv-chip:hover { border-color: var(--inv-border); color: var(--inv-navy); background: var(--inv-soft); }
.inv-chip.is-active { background: var(--inv-navy); border-color: var(--inv-navy); color: #fff; }
.inv-hint { font-size: 0.85rem; color: #64748b; margin: 0 0 0.85rem; }
.inv-table-scroll { overflow-x: auto; }
.inv-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.inv-table thead th { background: linear-gradient(135deg, var(--inv-navy), var(--inv-blue)); color: #fff; text-align: left; padding: 0.62rem 0.85rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
.inv-table thead th.inv-num { text-align: right; }
.inv-table td { padding: 0.66rem 0.85rem; border-bottom: 1px solid #f4f7fb; color: #334155; vertical-align: middle; }
.inv-table tbody tr:hover td { background: var(--inv-soft); }
.inv-num { text-align: right; font-variant-numeric: tabular-nums; }
.inv-nowrap { white-space: nowrap; }
.inv-muted { color: #94a3b8; font-size: 0.8rem; }
.inv-empty { padding: 2rem 1.25rem; text-align: center; color: #94a3b8; font-size: 0.9rem; }
.inv-empty-title { margin: 0 0 0.4rem; font-size: 1.05rem; font-weight: 700; color: #334155; }
.inv-folio { font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
.inv-client { font-weight: 700; color: #0f172a; }
.inv-type { display: inline-flex; padding: 0.14rem 0.5rem; border-radius: 999px; font-size: 0.68rem; font-weight: 700; }
.inv-type--air { background: #EAF6FB; color: #0E6E8C; border: 1px solid #BFE3F0; }
.inv-type--sea { background: #FDF3E8; color: #9A5B12; border: 1px solid #F0D4A8; }
.inv-type--cft { background: #E8F6EE; color: #16794C; border: 1px solid #b7e0c8; }
@media (max-width: 768px) { .inv-field, .inv-field-wide { max-width: none; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('deliveryAgencyForm');
    var select = document.getElementById('agency_id');
    if (form && select) {
        select.addEventListener('change', function() {
            if (select.value) form.submit();
        });
    }
});
</script>
@endsection
