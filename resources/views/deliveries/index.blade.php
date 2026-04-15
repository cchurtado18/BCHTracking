@extends('layouts.app')

@section('title', 'Entregas')

@section('content')
<div class="delivery-page">
    {{-- Hero --}}
    <header class="delivery-hero">
        <div class="delivery-hero-inner">
            <div class="delivery-hero-text">
                <h1 class="delivery-hero-title">Entregas</h1>
                <p class="delivery-hero-subtitle">Busque la agencia a la cual desea hacer entrega. Si tiene paquetes listos para retiro se mostrarán aquí; si no, se indicará que no hay paquetes.</p>
            </div>
        </div>
    </header>

    {{-- Buscar agencia para entrega --}}
    <div class="delivery-card delivery-agency-card">
        <div class="delivery-card-header">
            <h2 class="delivery-card-title">¿Para qué agencia es la entrega?</h2>
        </div>
        <div class="delivery-card-body">
            <form method="GET" action="{{ route('deliveries.index') }}" class="delivery-agency-form" id="deliveryAgencyForm">
                <div class="delivery-agency-row">
                    <label for="agency_id" class="delivery-label">Agencia</label>
                    <select name="agency_id" id="agency_id" class="delivery-select delivery-select-agency">
                        <option value="">Seleccione la agencia…</option>
                        @foreach($agenciesForSelect as $opt)
                        <option value="{{ $opt->id }}" {{ (string) $agencyId === (string) $opt->id ? 'selected' : '' }}>{{ $opt->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="delivery-btn delivery-btn-primary">Ver paquetes</button>
                    @if($agencyId)
                    <a href="{{ route('deliveries.index', ['clear_agency' => 1]) }}" class="delivery-btn delivery-btn-secondary">Limpiar</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($selectedAgency)
    {{-- Resultado: paquetes listos o mensaje de vacío --}}
    <div class="delivery-card">
        <div class="delivery-card-header delivery-table-header">
            <h2 class="delivery-card-title">Paquetes listos para retiro — {{ $selectedAgency->name }}</h2>
            @if($availableTotal > 0)
            <a href="{{ route('deliveries.batch', array_filter(['agency_id' => $selectedAgency->id, 'service_type' => $serviceType])) }}" class="delivery-btn delivery-btn-primary delivery-btn-sm">Generar reporte de entrega</a>
            @endif
        </div>
        <div class="delivery-card-body">
            @if(($availableAir + $availableSea) === 0)
            <div class="delivery-empty-state">
                <p class="delivery-empty-state-title">Esta agencia no tiene paquetes listos para retirar</p>
                <p class="delivery-empty-state-text">No hay paquetes en estado «Listo para retiro» para {{ $selectedAgency->name }}. Seleccione otra agencia o espere a que los paquetes estén listos.</p>
            </div>
            @else
            <div class="delivery-service-filter">
                <span class="delivery-service-filter-label">Servicio:</span>
                <a href="{{ route('deliveries.index', ['agency_id' => $selectedAgency->id]) }}" class="delivery-service-filter-link {{ !$serviceType ? 'active' : '' }}">Todos ({{ $availableAir + $availableSea }})</a>
                <a href="{{ route('deliveries.index', ['agency_id' => $selectedAgency->id, 'service_type' => 'AIR']) }}" class="delivery-service-filter-link {{ $serviceType === 'AIR' ? 'active' : '' }}">Aéreo ({{ $availableAir }})</a>
                <a href="{{ route('deliveries.index', ['agency_id' => $selectedAgency->id, 'service_type' => 'SEA']) }}" class="delivery-service-filter-link {{ $serviceType === 'SEA' ? 'active' : '' }}">Marítimo ({{ $availableSea }})</a>
            </div>
            <p class="delivery-hint">{{ $availableTotal }} {{ $availableTotal === 1 ? 'paquete listo' : 'paquetes listos' }}@if($serviceType) — {{ $serviceType === 'AIR' ? 'Aéreo' : 'Marítimo' }}@else ({{ $availableAir }} aéreo, {{ $availableSea }} marítimo)@endif. Use «Generar reporte de entrega» para escanear y registrar la entrega.</p>
            <div class="delivery-table-wrap">
                <table class="delivery-table">
                    <thead>
                        <tr>
                            <th>Cliente (etiqueta)</th>
                            <th>Warehouse</th>
                            <th>Tracking</th>
                            <th>Servicio</th>
                            <th>Peso (lbs)</th>
                            <th>Agencia</th>
                            <th>Listo desde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($availablePackages as $p)
                        <tr>
                            <td class="delivery-name-cell" title="{{ $p->label_name }}">{{ Str::limit($p->label_name, 25) }}</td>
                            <td><span class="delivery-code">{{ $p->warehouse_code ?? '—' }}</span></td>
                            <td class="delivery-code delivery-tracking-cell" title="{{ $p->tracking_external }}">{{ Str::limit($p->tracking_external, 18) }}</td>
                            <td>
                                <span class="delivery-badge delivery-badge-{{ strtolower($p->service_type ?? '') }}">{{ $p->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span>
                            </td>
                            <td class="delivery-num">{{ $p->verified_weight_lbs ?? $p->intake_weight_lbs ?? '—' }}</td>
                            <td class="delivery-muted delivery-name-cell" title="{{ $p->agency->name ?? '' }}">{{ Str::limit($p->agency->name ?? '—', 20) }}</td>
                            <td class="delivery-muted">{{ $p->ready_at ? $p->ready_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="delivery-card">
        <div class="delivery-card-body">
            <div class="delivery-empty-state delivery-empty-state-prompt">
                <p class="delivery-empty-state-title">Seleccione una agencia</p>
                <p class="delivery-empty-state-text">Elija la agencia en el selector de arriba y pulse «Ver paquetes» para ver si tiene paquetes listos para retirar.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Entregas realizadas (por nota de entrega) --}}
    <div class="delivery-card delivery-table-card">
        <div class="delivery-card-header delivery-table-header">
            <h2 class="delivery-card-title">Entregas realizadas (por nota de entrega)</h2>
            @if($selectedAgency)
            <span class="delivery-card-badge">Filtrado por {{ $selectedAgency->name }}</span>
            @else
            <span class="delivery-card-badge">{{ $deliveryNotes->total() }} {{ $deliveryNotes->total() === 1 ? 'nota' : 'notas' }}</span>
            @endif
        </div>
        <div class="delivery-table-wrap">
            <table class="delivery-table">
                <thead>
                    <tr>
                        <th>Código nota</th>
                        <th>Fecha</th>
                        <th>Paquetes</th>
                        <th>Retirado por</th>
                        <th>Tipo</th>
                        <th>Agencia</th>
                        <th class="delivery-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveryNotes as $note)
                    @php
                        $firstDelivery = $note->deliveries->first();
                        $agencyName = $note->agency?->name ?? $firstDelivery?->preregistration?->agency?->name ?? '—';
                    @endphp
                    <tr>
                        <td><span class="delivery-code">{{ $note->code }}</span></td>
                        <td class="delivery-muted">{{ $firstDelivery ? $firstDelivery->delivered_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : ($note->created_at ? $note->created_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '—') }}</td>
                        <td class="delivery-num">{{ $note->deliveries_count }}</td>
                        <td class="delivery-name-cell" title="{{ $firstDelivery?->delivered_to }}">{{ $firstDelivery?->delivered_to ?? '—' }}</td>
                        <td>
                            @if($firstDelivery)
                            <span class="delivery-badge delivery-badge-{{ strtolower($firstDelivery->delivery_type ?? '') }}">{{ $firstDelivery->delivery_type == 'PICKUP' ? 'Retiro' : 'Entrega' }}</span>
                            @else
                            —
                            @endif
                        </td>
                        <td class="delivery-muted delivery-name-cell" title="{{ $agencyName }}">{{ Str::limit($agencyName, 20) }}</td>
                        <td class="delivery-actions">
                            <a href="{{ route('deliveries.print-report', ['delivery_note_id' => $note->id]) }}" target="_blank" class="delivery-btn delivery-btn-sm delivery-btn-outline-primary" title="Ver / imprimir nota">Ver</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="delivery-empty">
                            <p class="delivery-empty-text">{{ $selectedAgency ? 'No hay notas de entrega para esta agencia.' : 'No hay notas de entrega.' }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deliveryNotes->total() > 0)
        <div class="delivery-card-footer">
            <span class="delivery-pagination-info">{{ $deliveryNotes->firstItem() }} – {{ $deliveryNotes->lastItem() }} de {{ $deliveryNotes->total() }}</span>
            @if($deliveryNotes->hasPages())
            <div class="delivery-pagination-links">{{ $deliveryNotes->links() }}</div>
            @endif
        </div>
        @endif
    </div>

</div>

<style>
.delivery-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

.delivery-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.delivery-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.delivery-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.delivery-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 52ch; }

.delivery-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.delivery-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.delivery-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.delivery-card-body { padding: 1.25rem; }
.delivery-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }
.delivery-card-badge { font-size: 0.8125rem; color: #6b7280; }

.delivery-agency-form { margin: 0; }
.delivery-agency-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
.delivery-agency-row .delivery-label { flex: 0 0 auto; margin-bottom: 0; font-size: 0.875rem; font-weight: 600; color: #374151; }
.delivery-select-agency { min-width: 280px; max-width: 100%; }
@media (max-width: 639px) { .delivery-select-agency { min-width: 100%; } }

.delivery-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.delivery-input, .delivery-select { display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827; }
.delivery-input:focus, .delivery-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.delivery-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

.delivery-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.delivery-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.delivery-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.delivery-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.delivery-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.delivery-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.delivery-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.delivery-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

.delivery-empty-state { text-align: center; padding: 2.5rem 1.5rem; }
.delivery-empty-state-prompt { padding: 2rem 1rem; }
.delivery-empty-state-title { margin: 0 0 0.5rem; font-size: 1.125rem; font-weight: 600; color: #374151; }
.delivery-empty-state-text { margin: 0; font-size: 0.9375rem; color: #6b7280; max-width: 42ch; margin-left: auto; margin-right: auto; }

.delivery-service-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem; margin-bottom: 1rem; }
.delivery-service-filter-label { font-size: 0.875rem; font-weight: 600; color: #374151; }
.delivery-service-filter-link { display: inline-block; padding: 0.4rem 0.75rem; font-size: 0.875rem; border-radius: 0.5rem; border: 1px solid #d1d5db; background: #fff; color: #374151; text-decoration: none; }
.delivery-service-filter-link:hover { background: #f3f4f6; border-color: #0d9488; color: #0d9488; }
.delivery-service-filter-link.active { background: #0d9488; border-color: #0d9488; color: #fff; }
.delivery-service-filter-link.active:hover { background: #0f766e; border-color: #0f766e; color: #fff; }

.delivery-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.delivery-table-header .delivery-card-title { color: #fff; }
.delivery-table-header .delivery-card-badge { color: rgba(255,255,255,0.9); }
.delivery-hint { font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem; }
.delivery-muted { color: #6b7280; font-size: 0.875rem; }
.delivery-table-wrap { overflow-x: auto; }
.delivery-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.delivery-table thead tr { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.delivery-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.2); white-space: nowrap; }
.delivery-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.delivery-table tbody tr:hover { background: #f9fafb; }
.delivery-code { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; }
.delivery-name-cell { max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.delivery-tracking-cell { max-width: 120px; }
.delivery-num { font-weight: 500; color: #374151; }
.delivery-th-actions { text-align: right; }
.delivery-actions { text-align: right; white-space: nowrap; }
.delivery-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.delivery-badge-pickup { background: #dbeafe; color: #1d4ed8; }
.delivery-badge-delivery { background: #d1fae5; color: #047857; }
.delivery-badge-air { background: #dbeafe; color: #1d4ed8; }
.delivery-badge-sea { background: #d1fae5; color: #047857; }
.delivery-empty { text-align: center; padding: 3rem 1rem !important; }
.delivery-empty-text { margin: 0; color: #6b7280; }
.delivery-pagination-info { font-weight: 500; }
.delivery-pagination-links { display: flex; align-items: center; }
.delivery-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.delivery-pagination-links a, .delivery-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.delivery-pagination-links a:hover { background: #f3f4f6; color: #0d9488; }
.delivery-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.delivery-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
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
