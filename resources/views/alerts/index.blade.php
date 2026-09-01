@extends('layouts.app')

@section('title', 'Alertas')

@section('content')
<div class="pt-page">
    <x-module-banner section="Operaciones" current="Alertas" title="Alertas" subtitle="Paquetes que llevan demasiado tiempo en almacén o que se quedaron atrás cuando se entregó el resto del mismo día de recepción.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <form method="POST" action="{{ route('alerts.dispatch') }}">
                @csrf
                <button type="submit" class="mb-btn mb-btn-primary">Revisar ahora</button>
            </form>
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="pt-alert pt-alert-success">{{ session('success') }}</div>
    @endif

    <div class="al-rules">
        <p><strong>Aéreo:</strong> 24 horas en Miami o almacén NIC sin cambiar de estado.</p>
        <p><strong>Marítimo y pie cúbico:</strong> 3 días en almacén sin cambiar de estado.</p>
        <p><strong>Lote incompleto:</strong> se entregó al menos un paquete de un cliente/servicio recibido el mismo día, y este todavía no.</p>
    </div>

    <div class="pt-filters-actions" style="margin-bottom:1rem;">
        <a href="{{ route('alerts.index') }}" class="pt-btn {{ $rule === '' ? 'pt-btn-primary' : 'pt-btn-secondary' }}">Todas ({{ $openTotal }})</a>
        @foreach(\App\Models\PackageAlert::RULES as $key => $label)
        <a href="{{ route('alerts.index', ['rule' => $key]) }}" class="pt-btn {{ $rule === $key ? 'pt-btn-primary' : 'pt-btn-secondary' }}">{{ $label }} ({{ (int) ($openCounts[$key] ?? 0) }})</a>
        @endforeach
    </div>

    <div class="pt-card">
        <div class="pt-card-header">
            <h2 class="pt-card-title">Pendientes de revisión</h2>
        </div>
        <div class="pt-table-wrap">
            <table class="pt-table">
                <thead>
                    <tr>
                        <th>Guía</th>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th>Alerta</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                    @php $p = $alert->preregistration; @endphp
                    <tr>
                        <td>
                            @if($p)
                            <a href="{{ route('preregistrations.show', $p->id) }}" class="pt-code">{{ $p->warehouse_code ?: ($p->tracking_external ?: '#'.$p->id) }}</a>
                            <div class="pt-muted">{{ $p->label_name }}</div>
                            @else
                            —
                            @endif
                        </td>
                        <td class="pt-muted">{{ $p?->agency?->code }} {{ $p?->agency?->name }}</td>
                        <td>{{ $p ? \App\Support\ServiceType::label($p->service_type) : '—' }}</td>
                        <td>
                            <div>{{ $alert->ruleLabel() }}</div>
                            <div class="pt-muted">{{ $alert->message }}</div>
                        </td>
                        <td class="pt-muted">{{ \App\Models\PackageAlert::statusLabel($p?->status) }}</td>
                        <td class="pt-actions">
                            <form method="POST" action="{{ route('alerts.dismiss', $alert) }}">
                                @csrf
                                @if($rule !== '')<input type="hidden" name="rule" value="{{ $rule }}">@endif
                                <button type="submit" class="pt-btn pt-btn-secondary pt-btn-sm">Revisada</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="pt-empty"><p class="pt-empty-text">No hay alertas abiertas.</p></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($alerts->hasPages())
        <div class="pt-card-footer">{{ $alerts->links() }}</div>
        @endif
    </div>
</div>

@include('partials.primetrack-module-styles')
<style>
.al-rules { margin: 0 0 1.25rem; padding: 0.9rem 1.1rem; background: #F4F8FD; border: 1px solid #E8EEF8; border-radius: 0.75rem; }
.al-rules p { margin: 0 0 0.35rem; font-size: 0.875rem; color: #334155; }
.al-rules p:last-child { margin-bottom: 0; }
.pt-filters-actions { display: flex; flex-wrap: wrap; gap: 0.45rem; }
</style>
@endsection
