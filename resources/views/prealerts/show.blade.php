@extends('layouts.app')

@section('title', 'Prealerta '.$prealert->tracking)

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $fmtMeta = function ($date) use ($displayTz) {
        if (! $date) {
            return null;
        }
        $local = $date->copy()->timezone($displayTz);

        return $local->isToday() ? 'Hoy · '.$local->format('H:i') : $local->format('d/m H:i');
    };
    $stageIndex = match ($prealert->status) {
        'matched' => 2,
        'cancelled' => 0,
        default => 1,
    };
    $timeline = [
        ['title' => 'Creada', 'meta' => $fmtMeta($prealert->created_at) ?? 'Registrada'],
        ['title' => 'Pendiente', 'meta' => $prealert->status === 'pending' ? 'En espera' : ($prealert->status === 'cancelled' ? 'Cancelada' : 'Completada')],
        ['title' => 'Ingresado', 'meta' => $prealert->matched_at ? ($fmtMeta($prealert->matched_at) ?? 'Registrado') : ($prealert->status === 'matched' ? 'Registrado' : 'Pendiente')],
    ];
@endphp
<div class="prealerts-page">
    <x-module-banner
        section="General"
        current="Detalle"
        title="Prealerta"
        subtitle="{{ $prealert->name }}{{ $prealert->agency ? ' · '.$prealert->agency->listingAccountLabel() : '' }} · {{ $prealert->statusLabel() }}"
        back-href="{{ route('prealerts.index') }}"
        back-label="Volver a prealertas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/></svg>
        </x-slot:icon>
        @if($prealert->status !== 'matched')
        <x-slot:actions>
            <form action="{{ route('prealerts.destroy', $prealert) }}" method="POST" onsubmit="return confirm('¿Eliminar esta prealerta?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="mb-btn mb-btn-danger">Eliminar</button>
            </form>
        </x-slot:actions>
        @endif
        <x-slot:strip>
            <span class="mb-strip-label">Prealerta</span>
            <span class="mb-pill">{{ $prealert->tracking }}</span>
            <span class="mb-pill">{{ \App\Support\ServiceType::label($prealert->service_type) }}</span>
            <span class="mb-pill {{ $prealert->status === 'matched' ? 'mb-pill--ok' : '' }}">{{ $prealert->statusLabel() }}</span>
        </x-slot:strip>
    </x-module-banner>

    @if(session('success'))
    <div class="prealerts-flash-ok" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="prealerts-flash-err" role="alert">{{ session('error') }}</div>
    @endif

    <div class="prealerts-metrics">
        <div class="prealerts-metric prealerts-metric-accent">
            <span class="prealerts-metric-label">Tracking</span>
            <span class="prealerts-metric-value prealerts-mono">{{ $prealert->tracking }}</span>
        </div>
        <div class="prealerts-metric">
            <span class="prealerts-metric-label">Nombre</span>
            <span class="prealerts-metric-value">{{ $prealert->name }}</span>
        </div>
        <div class="prealerts-metric">
            <span class="prealerts-metric-label">Servicio</span>
            <span class="prealerts-metric-value">
                <span class="prealerts-chip prealerts-chip-{{ strtolower($prealert->service_type ?? '') }}">{{ \App\Support\ServiceType::label($prealert->service_type) }}</span>
            </span>
        </div>
        <div class="prealerts-metric">
            <span class="prealerts-metric-label">Estado</span>
            <span class="prealerts-metric-value">{{ $prealert->statusLabel() }}</span>
        </div>
    </div>

    <section class="prealerts-data-card">
        <header class="prealerts-data-head">
            <h2 class="prealerts-data-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prealerts-data-icon"><path d="M3 17 9 11l4 4 8-8M17 7h4v4"/></svg>
                Seguimiento
            </h2>
        </header>
        <div class="prealerts-data-body">
            <ol class="prealerts-htl">
                @foreach($timeline as $i => $step)
                <li class="prealerts-htl-step {{ $i <= $stageIndex ? 'is-done' : '' }} {{ $i === $stageIndex ? 'is-current' : '' }}">
                    <span class="prealerts-htl-icon">
                        @if($i <= $stageIndex)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 13 4 4L19 7"/></svg>
                        @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg>
                        @endif
                    </span>
                    <strong class="prealerts-htl-title">{{ $step['title'] }}</strong>
                    <span class="prealerts-htl-meta">{{ $step['meta'] }}</span>
                </li>
                @endforeach
            </ol>
        </div>
    </section>

    <div class="prealerts-show-layout" style="margin-top: 1rem;">
        <div class="prealerts-show-main">
            <section class="prealerts-data-card">
                <header class="prealerts-data-head">
                    <h2 class="prealerts-data-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prealerts-data-icon"><path d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
                        Datos de la prealerta
                    </h2>
                </header>
                <div class="prealerts-data-body">
                    <div class="prealerts-fields">
                        <div class="prealerts-data-field">
                            <span class="prealerts-data-label">Nombre</span>
                            <span class="prealerts-data-value">{{ $prealert->name }}</span>
                        </div>
                        <div class="prealerts-data-field">
                            <span class="prealerts-data-label">Agencia</span>
                            <span class="prealerts-data-value">{{ $prealert->agency?->listingAccountLabel() }} ({{ $prealert->agency?->code }})</span>
                        </div>
                        <div class="prealerts-data-field">
                            <span class="prealerts-data-label">Tracking</span>
                            <span class="prealerts-data-value prealerts-mono">{{ $prealert->tracking }}</span>
                        </div>
                        <div class="prealerts-data-field">
                            <span class="prealerts-data-label">Servicio</span>
                            <span class="prealerts-data-value">{{ \App\Support\ServiceType::label($prealert->service_type) }}</span>
                        </div>
                        <div class="prealerts-data-field prealerts-data-field--full">
                            <span class="prealerts-data-label">Descripción</span>
                            <span class="prealerts-data-value">{{ $prealert->description ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="prealerts-show-side">
            <section class="prealerts-data-card">
                <header class="prealerts-data-head">
                    <h2 class="prealerts-data-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prealerts-data-icon"><path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Registro
                    </h2>
                </header>
                <div class="prealerts-data-body">
                    <div class="prealerts-fields">
                        <div class="prealerts-data-field">
                            <span class="prealerts-data-label">Creada</span>
                            <span class="prealerts-data-value">{{ $prealert->created_at?->timezone($displayTz)->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($prealert->creator)
                        <div class="prealerts-data-field">
                            <span class="prealerts-data-label">Por</span>
                            <span class="prealerts-data-value">{{ $prealert->creator->name }}</span>
                        </div>
                        @endif
                        @if($prealert->matched_at)
                        <div class="prealerts-data-field">
                            <span class="prealerts-data-label">Ingresada</span>
                            <span class="prealerts-data-value">{{ $prealert->matched_at->timezone($displayTz)->format('d/m/Y H:i') }}</span>
                        </div>
                        @endif
                    </div>
                    @if($prealert->preregistration && ! $isClientView)
                    <a href="{{ route('preregistrations.show', $prealert->preregistration) }}" class="prealerts-side-link">
                        Ver preregistro {{ $prealert->preregistration->warehouse_code ?: '#'.$prealert->preregistration->id }}
                    </a>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>

@include('prealerts.partials.styles')
@endsection
