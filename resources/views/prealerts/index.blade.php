@extends('layouts.app')

@section('title', 'Prealerta')

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
@endphp
<div class="prealerts-page">
    <x-module-banner
        section="General"
        current="Prealerta"
        title="Prealerta"
        subtitle="{{ $isClientView ? 'Avise al almacén de un paquete que está en camino. El tracking se usará para reconocerlo cuando llegue.' : 'Prealertas enviadas por el cliente. El tracking se usará al ingresar el paquete en preregistro.' }}"
        :hide-back="$isClientView"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('prealerts.create') }}" class="mb-btn mb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva prealerta
            </a>
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="prealerts-alert prealerts-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="prealerts-alert prealerts-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="prealerts-stats">
        <div class="prealerts-stat-card prealerts-stat-total">
            <span class="prealerts-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/></svg>
            </span>
            <div class="prealerts-stat-body">
                <span class="prealerts-stat-label">Total</span>
                <span class="prealerts-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
            </div>
        </div>
        <div class="prealerts-stat-card prealerts-stat-pending">
            <span class="prealerts-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <div class="prealerts-stat-body">
                <span class="prealerts-stat-label">Pendientes</span>
                <span class="prealerts-stat-value">{{ number_format($statsPending ?? 0) }}</span>
            </div>
        </div>
        <div class="prealerts-stat-card prealerts-stat-air">
            <span class="prealerts-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 14 8-3 3-8 2 2-2 7 7 2 2 2-8 1-2 4-2-2 1-4-7-1Z"/></svg>
            </span>
            <div class="prealerts-stat-body">
                <span class="prealerts-stat-label">Aéreo</span>
                <span class="prealerts-stat-value">{{ number_format($statsAir ?? 0) }}</span>
            </div>
        </div>
        <div class="prealerts-stat-card prealerts-stat-sea">
            <span class="prealerts-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 18s2.5 3 5 3 4-2 5-2 2.5 2 5 2 5-3 5-3M4 16V9h14v7M8 9l1.5-3h3L14 9"/></svg>
            </span>
            <div class="prealerts-stat-body">
                <span class="prealerts-stat-label">Marítimo</span>
                <span class="prealerts-stat-value">{{ number_format($statsSea ?? 0) }}</span>
            </div>
        </div>
        <div class="prealerts-stat-card prealerts-stat-matched">
            <span class="prealerts-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4 10-10"/></svg>
            </span>
            <div class="prealerts-stat-body">
                <span class="prealerts-stat-label">Ingresado</span>
                <span class="prealerts-stat-value">{{ number_format($statsMatched ?? 0) }}</span>
            </div>
        </div>
    </div>

    <div class="prealerts-filters">
        <form method="GET" action="{{ route('prealerts.index') }}" class="prealerts-filters-form">
            <div class="prealerts-field prealerts-field-search">
                <label for="q" class="prealerts-label">Buscar</label>
                <input type="text" name="q" id="q" value="{{ $search }}" class="prealerts-input" placeholder="Tracking, nombre o descripción">
            </div>
            <div class="prealerts-field prealerts-field-select">
                <label for="service_type" class="prealerts-label">Servicio</label>
                <select name="service_type" id="service_type" class="prealerts-select">
                    <option value="">Todos</option>
                    <option value="AIR" @selected($serviceType === 'AIR')>Aéreo</option>
                    <option value="SEA" @selected($serviceType === 'SEA')>Marítimo</option>
                </select>
            </div>
            <div class="prealerts-field prealerts-field-select">
                <label for="status" class="prealerts-label">Estado</label>
                <select name="status" id="status" class="prealerts-select">
                    <option value="">Todos</option>
                    <option value="pending" @selected($status === 'pending')>Pendiente</option>
                    <option value="matched" @selected($status === 'matched')>Ingresado</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Cancelada</option>
                </select>
            </div>
            <div class="prealerts-filters-actions">
                <button type="submit" class="prealerts-btn prealerts-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3-3"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('prealerts.index') }}" class="prealerts-btn prealerts-btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="prealerts-toolbar">
        <span class="prealerts-count">{{ $prealerts->total() }} {{ $prealerts->total() === 1 ? 'prealerta' : 'prealertas' }}</span>
    </div>

    <div class="prealerts-table-wrap">
        <table class="prealerts-table">
            <thead>
                <tr>
                    <th>Tracking</th>
                    <th>Nombre</th>
                    <th>Agencia</th>
                    <th>Servicio</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th class="prealerts-th-actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prealerts as $prealert)
                <tr class="prealerts-clickable-row" data-href="{{ route('prealerts.show', $prealert) }}">
                    <td>
                        <span class="prealerts-code" title="{{ $prealert->tracking }}">{{ $prealert->tracking }}</span>
                    </td>
                    <td>
                        <span class="prealerts-name" title="{{ $prealert->name }}">{{ $prealert->name }}</span>
                    </td>
                    <td>
                        <span class="prealerts-agency" title="{{ $prealert->agency?->listingAccountLabel() }}">{{ $prealert->agency?->listingAccountLabel() ?: '—' }}</span>
                    </td>
                    <td>
                        <span class="prealerts-badge prealerts-badge-{{ strtolower($prealert->service_type ?? '') }}">
                            {{ \App\Support\ServiceType::label($prealert->service_type) }}
                        </span>
                    </td>
                    <td>
                        <span class="prealerts-agency" title="{{ $prealert->description }}">{{ $prealert->description ? \Illuminate\Support\Str::limit($prealert->description, 42) : '—' }}</span>
                    </td>
                    <td>
                        @if($prealert->status === 'matched')
                        <span class="prealerts-badge prealerts-badge-matched">{{ $prealert->statusLabel() }}</span>
                        @elseif($prealert->status === 'cancelled')
                        <span class="prealerts-badge prealerts-badge-cancelled">{{ $prealert->statusLabel() }}</span>
                        @else
                        <span class="prealerts-badge prealerts-badge-pending">{{ $prealert->statusLabel() }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="prealerts-date">{{ $prealert->created_at?->timezone($displayTz)->format('d/m/Y H:i') }}</span>
                    </td>
                    <td class="prealerts-actions">
                        <div class="prealerts-action-group" role="group" aria-label="Acciones">
                            <a href="{{ route('prealerts.show', $prealert) }}" class="prealerts-icon-btn prealerts-icon-btn--view" title="Ver detalle" aria-label="Ver detalle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @if($prealert->status !== 'matched')
                            <form action="{{ route('prealerts.destroy', $prealert) }}" method="POST" class="prealerts-form-inline" onsubmit="return confirm('¿Eliminar esta prealerta?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="prealerts-icon-btn prealerts-icon-btn--danger" title="Eliminar" aria-label="Eliminar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="prealerts-empty">
                        <p class="prealerts-empty-text">No hay prealertas todavía.</p>
                        <a href="{{ route('prealerts.create') }}" class="prealerts-btn prealerts-btn-primary">Crear prealerta</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($prealerts->hasPages())
    <div class="prealerts-footer">
        <span>{{ $prealerts->firstItem() }} – {{ $prealerts->lastItem() }} de {{ $prealerts->total() }}</span>
        <div>{{ $prealerts->links() }}</div>
    </div>
    @endif
</div>

@include('prealerts.partials.styles')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.prealerts-clickable-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea, form, label')) return;
            const href = row.getAttribute('data-href');
            if (href) window.location.href = href;
        });
    });
});
</script>
@endsection
