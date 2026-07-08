@extends('layouts.app')

@section('title', 'Fichaje')

@push('styles')
@include('partials.packages-module-styles')
@endpush

@section('content')
@php
    $tz = $displayTz ?? config('app.display_timezone', 'America/New_York');
    $hasShift = $openEntry !== null;
    $onBreak = isset($activeBreak) && $activeBreak !== null;
    $statEstado = ! $hasShift ? 'Sin jornada' : ($onBreak ? 'En break' : 'Activo');
    $statEntrada = ($hasShift && $openEntry) ? $openEntry->clock_in_at->timezone($tz)->format('d/m H:i') : '—';
    $statHistorial = $history->total();
@endphp
<div class="packages-page">
    <header class="packages-hero">
        <div class="packages-hero-inner">
            <div class="packages-hero-text">
                <h1 class="packages-hero-title">Fichaje</h1>
                <p class="packages-hero-subtitle">Use <strong>Iniciar</strong> al comenzar la jornada, <strong>Break</strong> para pausas y <strong>Salida</strong> al terminar. Horas mostradas en {{ $tz }}.</p>
            </div>
            @if(auth()->user()?->is_admin)
                <a href="{{ route('time-entries.admin.index') }}" class="packages-hero-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5"/></svg>
                    Fichaje equipo
                </a>
            @endif
        </div>
    </header>

    @if ($errors->has('clock'))
        <div class="packages-alert-error" role="alert">{{ $errors->first('clock') }}</div>
    @endif

    <div class="packages-stats">
        <div class="packages-stat-card packages-stat-total">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <span class="packages-stat-label">Estado</span>
            <span class="packages-stat-value" style="font-size:1.35rem">{{ $statEstado }}</span>
        </div>
        <div class="packages-stat-card packages-stat-air">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"/></svg>
            </span>
            <span class="packages-stat-label">Entrada actual</span>
            <span class="packages-stat-value" style="font-size:1.1rem;font-variant-numeric:tabular-nums">{{ $statEntrada }}</span>
        </div>
        <div class="packages-stat-card packages-stat-delivered">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h7.5M3.75 4.5h16.5v15H3.75v-15Z"/></svg>
            </span>
            <span class="packages-stat-label">Historial (total)</span>
            <span class="packages-stat-value">{{ number_format($statHistorial) }}</span>
        </div>
    </div>

    <div class="packages-card">
        <div class="packages-card-header">
            <h2 class="packages-card-title">Acciones de fichaje</h2>
        </div>
        <div class="packages-card-body">
            @if($hasShift)
                <p class="packages-time-status">Jornada iniciada el <span class="packages-code">{{ $openEntry->clock_in_at->timezone($tz)->format('d/m/Y H:i') }}</span>.
                    @if($onBreak)
                        <strong>En break</strong> desde <span class="packages-code">{{ $activeBreak->started_at->timezone($tz)->format('d/m/Y H:i') }}</span>.
                    @else
                        Jornada activa (sin break en curso).
                    @endif
                </p>
            @else
                <p class="packages-time-status">Sin jornada activa. Pulse <strong>Iniciar</strong> cuando comience.</p>
            @endif

            <div class="packages-filters-actions">
                @if(!$hasShift)
                    <form method="POST" action="{{ route('time-entries.clock-in') }}" class="inline">
                        @csrf
                        <button type="submit" class="packages-time-btn packages-time-btn--iniciar">Iniciar</button>
                    </form>
                @else
                    <button type="button" disabled class="packages-time-btn packages-time-btn--iniciar">Iniciar</button>
                @endif

                @if($hasShift)
                    <form method="POST" action="{{ route('time-entries.clock-out') }}" class="inline">
                        @csrf
                        <button type="submit" class="packages-time-btn packages-time-btn--salida">Salida</button>
                    </form>
                @else
                    <button type="button" disabled class="packages-time-btn packages-time-btn--salida">Salida</button>
                @endif

                @if($hasShift && !$onBreak)
                    <form method="POST" action="{{ route('time-entries.break-start') }}" class="inline">
                        @csrf
                        <button type="submit" class="packages-time-btn packages-time-btn--break">Break</button>
                    </form>
                @elseif($hasShift && $onBreak)
                    <form method="POST" action="{{ route('time-entries.break-end') }}" class="inline">
                        @csrf
                        <button type="submit" class="packages-time-btn packages-time-btn--break-end">Fin break</button>
                    </form>
                @else
                    <button type="button" disabled class="packages-time-btn packages-time-btn--break">Break</button>
                @endif
            </div>
        </div>
    </div>

    <div class="packages-card packages-table-card">
        <div class="packages-card-header packages-table-header">
            <h2 class="packages-card-title">Historial reciente</h2>
            <span class="packages-card-badge">{{ $history->total() }} {{ $history->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="packages-table-wrap">
            <table class="packages-table">
                <thead>
                    <tr>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Duración</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $entry)
                    <tr>
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
                        <td colspan="3" class="packages-empty">
                            <p class="packages-empty-text">Aún no hay registros de fichaje.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($history->hasPages())
        <div class="packages-card-footer">
            <span class="packages-pagination-info">
                {{ $history->firstItem() }} – {{ $history->lastItem() }} de {{ $history->total() }}
            </span>
            <div class="packages-pagination-links">{{ $history->links() }}</div>
        </div>
        @endif
    </div>
</div>

@endsection
