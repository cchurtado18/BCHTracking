@extends('layouts.app')

@section('title', 'Fichaje del equipo')

@push('styles')
@include('partials.packages-module-styles')
@endpush

@section('content')
@php
    $tz = $displayTz ?? config('app.display_timezone', 'America/New_York');
@endphp
<div class="packages-page">
    <header class="packages-hero">
        <div class="packages-hero-inner">
            <div class="packages-hero-text">
                <h1 class="packages-hero-title">Fichaje del equipo</h1>
                <p class="packages-hero-subtitle">Registros de trabajadores centrales (sin subagencia). Zona horaria: {{ $tz }}.</p>
            </div>
            <a href="{{ route('time-entries.index') }}" class="packages-hero-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Mi fichaje
            </a>
        </div>
    </header>

    <div class="packages-card packages-filters-card">
        <div class="packages-card-header">
            <h2 class="packages-card-title">Filtros</h2>
        </div>
        <div class="packages-card-body">
            <form method="GET" action="{{ route('time-entries.admin.index') }}" class="packages-filters-form">
                <div class="packages-filters-grid">
                    <div class="packages-field">
                        <label class="packages-label" for="user_id">Usuario</label>
                        <select name="user_id" id="user_id" class="packages-select">
                            <option value="">Todos</option>
                            @foreach($centralUsers as $u)
                                <option value="{{ $u->id }}" @selected((string)($userId ?? '') === (string)$u->id)>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="packages-field">
                        <label class="packages-label" for="date_from">Desde</label>
                        <input type="date" name="date_from" id="date_from" value="{{ $dateFrom?->toDateString() }}" class="packages-input">
                    </div>
                    <div class="packages-field">
                        <label class="packages-label" for="date_to">Hasta</label>
                        <input type="date" name="date_to" id="date_to" value="{{ $dateTo?->toDateString() }}" class="packages-input">
                    </div>
                </div>
                <div class="packages-filters-actions">
                    <button type="submit" class="packages-btn packages-btn-primary">Aplicar filtros</button>
                    <a href="{{ route('time-entries.admin.index') }}" class="packages-btn packages-btn-ghost">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="packages-card packages-table-card">
        <div class="packages-card-header packages-table-header">
            <h2 class="packages-card-title">Listado de fichajes</h2>
            <span class="packages-card-badge">{{ $entries->total() }} {{ $entries->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="packages-table-wrap">
            <table class="packages-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Duración</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td>
                            <span class="packages-name" style="max-width:14rem" title="{{ $entry->user?->email }}">{{ $entry->user?->name ?? '—' }}</span>
                            <span class="packages-agency" style="max-width:14rem;display:block;margin-top:0.2rem">{{ $entry->user?->email }}</span>
                        </td>
                        <td><span class="packages-date">{{ $entry->clock_in_at->timezone($tz)->format('d/m/Y H:i') }}</span></td>
                        <td>
                            @if($entry->clock_out_at)
                                <span class="packages-date">{{ $entry->clock_out_at->timezone($tz)->format('d/m/Y H:i') }}</span>
                            @else
                                <span class="packages-badge packages-status status-warning">En curso</span>
                            @endif
                        </td>
                        <td>
                            @if($entry->clock_out_at)
                                @php
                                    $mins = $entry->clock_in_at->diffInMinutes($entry->clock_out_at);
                                    $h = intdiv($mins, 60);
                                    $m = $mins % 60;
                                @endphp
                                <span class="packages-code">{{ $h }}h {{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}m</span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="packages-empty">
                            <p class="packages-empty-text">No hay registros con los filtros seleccionados.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
        <div class="packages-card-footer">
            <span class="packages-pagination-info">
                {{ $entries->firstItem() }} – {{ $entries->lastItem() }} de {{ $entries->total() }}
            </span>
            <div class="packages-pagination-links">{{ $entries->links() }}</div>
        </div>
        @endif
    </div>
</div>

@endsection
