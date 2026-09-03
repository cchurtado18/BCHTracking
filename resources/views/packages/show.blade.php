@extends('layouts.app')

@section('title', 'Detalle Paquete')

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $statusLabels = [
        'RECEIVED_MIAMI' => 'Recibido Miami',
        'IN_TRANSIT' => 'En tránsito',
        'IN_WAREHOUSE_NIC' => 'En almacén NIC',
        'READY' => 'Listo para retiro',
        'DELIVERED' => 'Entregado',
    ];
    $statusLabel = $statusLabels[$package->status] ?? $package->status;
    $isAgencyUser = auth()->user() && auth()->user()->isAgencyUser();
    $packagesOnlyPortal = auth()->user()?->isPackagesOnlyPortal();
    $weightLbs = $package->verified_weight_lbs ?? $package->intake_weight_lbs;
    $showAdmin = auth()->user()?->is_admin;

    $stageIndex = match ($package->status) {
        'RECEIVED_MIAMI' => 1,
        'IN_TRANSIT' => 2,
        'IN_WAREHOUSE_NIC' => 3,
        'READY' => 4,
        'DELIVERED' => 5,
        default => 0,
    };
    $fmtMeta = function ($date) use ($displayTz) {
        if (!$date) return null;
        $local = $date->copy()->timezone($displayTz);
        return $local->isToday() ? 'Hoy · ' . $local->format('H:i') : $local->format('d/m/Y H:i');
    };
    $sacoCode = $package->consolidationItem?->consolidation?->code;
    $timeline = [
        ['title' => 'Creado', 'meta' => $fmtMeta($package->created_at)],
        ['title' => 'Recibido', 'meta' => $stageIndex >= 1 ? 'Registrado' : 'Pendiente'],
        ['title' => 'Tránsito', 'meta' => $sacoCode ?: ($stageIndex >= 2 ? 'Registrado' : 'Pendiente')],
        ['title' => 'Almacén NIC', 'meta' => $fmtMeta($package->received_nic_at) ?? ($stageIndex >= 3 ? 'Registrado' : 'Pendiente')],
        ['title' => 'Retiro', 'meta' => $fmtMeta($package->ready_at) ?? ($stageIndex >= 4 ? 'Registrado' : 'Pendiente')],
        ['title' => 'Entregado', 'meta' => $package->delivery
            ? ($fmtMeta($package->delivery->delivered_at) ?? 'Registrado')
            : 'Pendiente'],
    ];
@endphp
<div class="packages-page packages-show-page">
    <x-module-banner
        section="General"
        current="Detalle"
        title="Paquete #{{ $package->id }}"
        subtitle="{{ $package->label_name }}{{ $package->agency ? ' · '.$package->agency->name : '' }} · {{ $statusLabel }}"
        back-href="{{ route('packages.index', session('packages_index_filters', [])) }}"
        back-label="Volver a paquetes"
        :hide-back="$isAgencyUser"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            @if($package->status == 'IN_WAREHOUSE_NIC' && ! $isAgencyUser)
            <a href="{{ route('packages.process', $package->id) }}" class="mb-btn mb-btn-secondary">Procesar paquete</a>
            @endif
            @if(!$isAgencyUser)
            <a href="{{ route('preregistrations.edit', $package->id) }}" class="mb-btn mb-btn-secondary">Editar</a>
            @endif
            @if($package->warehouse_code && !$isAgencyUser)
            <a href="{{ route('preregistrations.label', $package->id) }}" target="_blank" class="mb-btn mb-btn-primary">Imprimir etiqueta</a>
            @endif
        </x-slot:actions>
        <x-slot:strip>
            <span class="mb-strip-label">Paquete</span>
            <span class="mb-pill">Código <strong>{{ $package->warehouse_code ?: '—' }}</strong></span>
            <span class="mb-pill">{{ $statusLabel }}</span>
            @if(! $isAgencyUser && in_array($package->status, ['RECEIVED_MIAMI', 'IN_TRANSIT']))
            <span class="mb-pill">Preregistro</span>
            @endif
        </x-slot:strip>
    </x-module-banner>

    {{-- ===== Franja de datos clave ===== --}}
    <div class="prd-metrics">
        <div class="prd-metric prd-metric-accent">
            <span class="prd-metric-label">Código</span>
            <span class="prd-metric-value prd-mono">{{ $package->warehouse_code ?: '—' }}</span>
        </div>
        <div class="prd-metric">
            <span class="prd-metric-label">Tracking</span>
            <span class="prd-metric-value prd-mono">{{ $package->tracking_external ?: '—' }}</span>
        </div>
        <div class="prd-metric">
            <span class="prd-metric-label">Peso bruto</span>
            <span class="prd-metric-value">{{ $weightLbs !== null ? number_format((float) $weightLbs, 2).' lbs' : '—' }}</span>
        </div>
        <div class="prd-metric">
            <span class="prd-metric-label">Servicio</span>
            <span class="prd-metric-value prd-metric-service">
                <span class="prd-chip prd-chip-{{ strtolower($package->service_type ?? '') }}">{{ \App\Support\ServiceType::label($package->service_type) }}</span>
                {{ $package->agency?->name ?: '—' }}
            </span>
        </div>
        <div class="prd-metric">
            <span class="prd-metric-label">Fecha ingreso</span>
            <span class="prd-metric-value">{{ $package->created_at->timezone($displayTz)->format('d/m/Y') }}</span>
        </div>
    </div>

    {{-- ===== Timeline operativo (horizontal) ===== --}}
    <section class="prd-card prd-timeline-card">
        <header class="prd-card-head">
            <h2 class="prd-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M3 17 9 11l4 4 8-8M17 7h4v4"/></svg>
                Seguimiento
            </h2>
        </header>
        <div class="prd-card-body">
            <ol class="prd-htl">
                @foreach($timeline as $i => $step)
                <li class="prd-htl-step {{ $i <= $stageIndex ? 'is-done' : '' }} {{ $i === $stageIndex ? 'is-current' : '' }}">
                    <span class="prd-htl-icon">
                        @if($i <= $stageIndex)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 13 4 4L19 7"/></svg>
                        @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg>
                        @endif
                    </span>
                    <strong class="prd-htl-title">{{ $step['title'] }}</strong>
                    <span class="prd-htl-meta">{{ $step['meta'] }}</span>
                </li>
                @endforeach
            </ol>
        </div>
    </section>

    <div class="prd-layout">
        <div class="prd-main">
            {{-- ===== Datos del envío ===== --}}
            <section class="prd-card">
                <header class="prd-card-head">
                    <h2 class="prd-card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
                        Datos del Envío
                    </h2>
                </header>
                <div class="prd-card-body">
                    <div class="prd-fields">
                        <div class="prd-field">
                            <span class="prd-field-label">Nombre en etiqueta</span>
                            <span class="prd-field-value">{{ $package->label_name }}</span>
                        </div>
                        <div class="prd-field">
                            <span class="prd-field-label">Tracking externo</span>
                            <span class="prd-field-value prd-mono">{{ $package->tracking_external ?? '—' }}</span>
                        </div>
                        <div class="prd-field">
                            <span class="prd-field-label">Código</span>
                            <span class="prd-field-value prd-mono">{{ $package->warehouse_code ?? $package->tracking_external ?? '—' }}</span>
                        </div>
                        <div class="prd-field">
                            <span class="prd-field-label">Peso etiqueta</span>
                            <span class="prd-field-value">{{ $package->intake_weight_lbs !== null ? number_format((float) $package->intake_weight_lbs, 2).' lbs' : '—' }}</span>
                        </div>
                        @if($package->verified_weight_lbs)
                        <div class="prd-field">
                            <span class="prd-field-label">Peso verificado</span>
                            <span class="prd-field-value">{{ number_format((float) $package->verified_weight_lbs, 2) }} lbs</span>
                        </div>
                        @endif
                        @unless($isAgencyUser)
                        <div class="prd-field">
                            <span class="prd-field-label">Método ingreso</span>
                            <span class="prd-field-value prd-field-intake">
                                <span class="prd-intake-dot"></span>
                                {{ $package->intake_type == 'COURIER' ? 'Courier' : 'Drop Off' }}
                            </span>
                        </div>
                        @if($package->label_print_count > 0)
                        <div class="prd-field">
                            <span class="prd-field-label">Impresiones de etiqueta</span>
                            <span class="prd-field-value">{{ $package->label_print_count }} vez(es)@if($package->label_last_printed_at) · {{ $package->label_last_printed_at->timezone($displayTz)->format('d/m/Y H:i') }}@endif</span>
                        </div>
                        @endif
                        @endunless
                    </div>
                </div>
            </section>

            {{-- ===== Saco + Entrega (lado a lado) ===== --}}
            @if(($package->consolidationItem && $package->consolidationItem->consolidation) || $package->delivery)
            <div class="prd-mid-row">
                @if($package->consolidationItem && $package->consolidationItem->consolidation)
                <section class="prd-card">
                    <header class="prd-card-head">
                        <h2 class="prd-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M5 8h14l-1.5 12.5a2 2 0 0 1-2 1.5h-7a2 2 0 0 1-2-1.5Z M8 8V6a4 4 0 0 1 8 0v2"/></svg>
                            Saco Asignado
                        </h2>
                    </header>
                    <div class="prd-card-body">
                        <div class="prd-saco-row">
                            <div>
                                <span class="prd-field-label">Código</span>
                                <span class="prd-field-value prd-mono">{{ $package->consolidationItem->consolidation->code }}</span>
                            </div>
                            <div class="prd-saco-status">
                                <span class="prd-field-label">Estado</span>
                                <span class="prd-badge">{{ $package->consolidationItem->consolidation->status == 'SENT' ? 'Enviado' : ($package->consolidationItem->consolidation->status == 'OPEN' ? 'Abierto' : $package->consolidationItem->consolidation->status) }}</span>
                            </div>
                        </div>
                        @if($package->consolidationItem->scanned_at)
                        <p class="prd-saco-scanned">Escaneado: {{ $package->consolidationItem->scanned_at->timezone($displayTz)->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                </section>
                @endif

                @if($package->delivery)
                <section class="prd-card">
                    <header class="prd-card-head">
                        <h2 class="prd-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M3.75 6.75h11.25v10.5H3.75V6.75Zm11.25 3h3.19a1.5 1.5 0 0 1 1.22.63l1.59 2.24v4.63H15V9.75ZM7.5 18.75a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm12 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
                            Entrega
                        </h2>
                    </header>
                    <div class="prd-card-body">
                        <div class="prd-fields prd-fields-compact">
                            <div class="prd-field">
                                <span class="prd-field-label">Fecha</span>
                                <span class="prd-field-value">{{ $package->delivery->delivered_at?->timezone($displayTz)->format('d/m/Y H:i') ?? '—' }}</span>
                            </div>
                            <div class="prd-field">
                                <span class="prd-field-label">Retiró</span>
                                <span class="prd-field-value">{{ $package->delivery->delivered_to ?: '—' }}</span>
                            </div>
                            <div class="prd-field prd-field-span">
                                <span class="prd-field-label">Hoja de salida</span>
                                <span class="prd-field-value">
                                    @if($package->delivery->deliveryNote)
                                    <span class="prd-mono">{{ $package->delivery->deliveryNote->code }}</span>
                                    @unless($packagesOnlyPortal)
                                    <a href="{{ route('salidas.print-report', ['delivery_note_id' => $package->delivery->delivery_note_id]) }}" target="_blank" class="prd-side-link">Ver hoja</a>
                                    @endunless
                                    @else
                                    Sin hoja vinculada
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
                @endif
            </div>
            @endif

            {{-- ===== Administración ===== --}}
            @if($showAdmin)
            <section class="prd-card prd-card-admin">
                <header class="prd-card-head">
                    <h2 class="prd-card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.001a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.001a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                        Administración
                    </h2>
                </header>
                <div class="prd-card-body prd-admin-stack">
                    @include('preregistrations.partials.admin-change-intake-type', ['preregistration' => $package, 'returnToPackage' => true])
                    @include('preregistrations.partials.admin-reset-to-miami', ['preregistration' => $package, 'returnToPackage' => true])
                </div>
            </section>
            @endif
        </div>

        <aside class="prd-side">
            {{-- ===== Evidencia ===== --}}
            <section class="prd-card">
                <header class="prd-card-head">
                    <h2 class="prd-card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M3 9a2 2 0 0 1 2-2h1.2a2 2 0 0 0 1.66-.9l.68-1.2A2 2 0 0 1 10.2 4h3.6a2 2 0 0 1 1.66.9l.68 1.2a2 2 0 0 0 1.66.9H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><circle cx="12" cy="13" r="3.2"/></svg>
                        Evidencia ({{ $package->photos->count() }})
                    </h2>
                </header>
                <div class="prd-card-body">
                    @if($package->photos->count() > 0)
                    <div class="prd-photo-grid">
                        @foreach($package->photos as $photo)
                        <div class="prd-photo-item">
                            <a href="{{ $photo->url }}" target="_blank" rel="noopener noreferrer" title="Clic para ver imagen completa">
                                <img src="{{ $photo->url }}" alt="Foto del paquete" class="prd-photo-img">
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="prd-photo-empty">Sin fotos subidas</div>
                    @endif
                </div>
            </section>

            {{-- ===== Etiqueta ===== --}}
            @if($package->warehouse_code && !$isAgencyUser)
            <section class="prd-card">
                <header class="prd-card-head">
                    <h2 class="prd-card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M20.59 13.41 12 22 2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82ZM7 7h.01"/></svg>
                        Etiqueta
                    </h2>
                    <a href="{{ route('preregistrations.label', $package->id) }}" target="_blank" rel="noopener" class="prd-head-link">Ver e imprimir →</a>
                </header>
                <div class="prd-card-body">
                    <div class="prd-label-embed">
                        <iframe src="{{ route('preregistrations.label', $package->id) }}?embed=1" title="Etiqueta del paquete" class="prd-label-iframe"></iframe>
                    </div>
                </div>
            </section>
            @endif
        </aside>
    </div>

    @if(session('open_label_autoprint') && $package->warehouse_code)
    <iframe src="{{ route('preregistrations.label', $package->id) }}?autoprint=1" title="Impresión de etiqueta" class="prd-label-print-helper" aria-hidden="true"></iframe>
    @endif
</div>

<style>
.packages-show-page { padding: 1.25rem 1rem 2rem; max-width: 92rem; margin: 0 auto; width: 100%; box-sizing: border-box; }
@media (min-width: 768px) { .packages-show-page { padding: 1.5rem 1.5rem 2.5rem; } }

/* ===== Franja de datos clave ===== */
.prd-metrics {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem;
    margin: 0 0 1.15rem;
}
@media (min-width: 1000px) { .prd-metrics { grid-template-columns: 1fr 1.35fr 1fr 1.25fr 1fr; } }
.prd-metric {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.85rem 1rem;
    box-shadow: 0 6px 18px rgba(15,23,42,0.08);
}
.prd-metric-accent { border-left: 3px solid #1E4FA8; }
.prd-metric-label { display: block; font-size: 0.66rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #64748b; margin-bottom: 0.3rem; }
.prd-metric-value { font-size: 1.05rem; font-weight: 800; color: #0f172a; line-height: 1.25; word-break: break-word; }
.prd-metric-service { display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; font-size: 0.92rem; }
.prd-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: 0.02em; }

.prd-chip { display: inline-flex; align-items: center; padding: 0.16rem 0.5rem; border-radius: 999px; font-size: 0.66rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; border: 1px solid transparent; }
.prd-chip-air { background: #E8EEF8; color: #0A2D6F; border-color: #C5D4EB; }
.prd-chip-sea { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.prd-chip-cft { background: #E8F6EE; color: #16794C; border-color: #b7e0c8; }

/* ===== Timeline horizontal ===== */
.prd-timeline-card { margin-bottom: 1rem; }
.prd-htl { list-style: none; margin: 0; padding: 0.35rem 0 0.1rem; display: flex; }
.prd-htl-step {
    flex: 1; min-width: 0; position: relative;
    display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.5rem;
}
.prd-htl-step::before {
    content: ''; position: absolute; top: 0.95rem; right: 50%; width: 100%; height: 2px;
    background: #e2e8f0; z-index: 0;
}
.prd-htl-step:first-child::before { display: none; }
.prd-htl-step.is-done::before { background: #1E4FA8; }
.prd-htl-icon {
    position: relative; z-index: 1; width: 1.9rem; height: 1.9rem; border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
    background: #fff; border: 2px solid #e2e8f0; color: #cbd5e1;
}
.prd-htl-icon svg { width: 0.9rem; height: 0.9rem; }
.prd-htl-step.is-done .prd-htl-icon { background: #1E4FA8; border-color: #C5D4EB; color: #fff; }
.prd-htl-step.is-current .prd-htl-icon {
    background: #E8EEF8; border-color: #1E4FA8; color: #0A2D6F;
    box-shadow: 0 0 0 4px rgba(16,185,129,0.16);
}
.prd-htl-title { display: block; font-size: 0.8rem; font-weight: 700; color: #b6c2d1; line-height: 1.2; }
.prd-htl-step.is-done .prd-htl-title { color: #0f172a; }
.prd-htl-step.is-current .prd-htl-title { color: #0A2D6F; }
.prd-htl-meta { display: block; font-size: 0.68rem; font-weight: 600; color: #b6c2d1; margin-top: -0.2rem; word-break: break-word; padding: 0 0.25rem; }
.prd-htl-step.is-done .prd-htl-meta { color: #64748b; }
.prd-htl-step.is-current .prd-htl-meta { color: #0A2D6F; }
@media (max-width: 700px) {
    .prd-htl { overflow-x: auto; padding-bottom: 0.5rem; }
    .prd-htl-step { min-width: 92px; }
}

/* ===== Layout 2 columnas ===== */
.prd-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; align-items: start; }
@media (min-width: 1024px) { .prd-layout { grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.8fr); } }
.prd-main, .prd-side { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }

.prd-mid-row { display: grid; grid-template-columns: 1fr; gap: 1rem; }
@media (min-width: 720px) { .prd-mid-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.prd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.85rem; overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
.prd-card-head {
    padding: 0.85rem 1.15rem; border-bottom: 1px solid #eef2f7;
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;
}
.prd-card-title { margin: 0; font-size: 0.95rem; font-weight: 750; color: #0f172a; display: inline-flex; align-items: center; gap: 0.5rem; }
.prd-card-icon { width: 1rem; height: 1rem; color: #0A2D6F; flex-shrink: 0; }
.prd-card-body { padding: 1.1rem 1.15rem 1.2rem; }
.prd-head-link { font-size: 0.78rem; font-weight: 700; color: #0A2D6F; text-decoration: none; }
.prd-head-link:hover { color: #0A2D6F; text-decoration: underline; }

/* ===== Datos del envío ===== */
.prd-fields { display: grid; grid-template-columns: 1fr; gap: 1rem 1.5rem; }
@media (min-width: 640px) { .prd-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.prd-fields-compact { gap: 0.75rem; }
.prd-field { display: flex; flex-direction: column; gap: 0.22rem; min-width: 0; }
.prd-field-span { grid-column: 1 / -1; }
.prd-field-label { font-size: 0.66rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.07em; text-transform: uppercase; }
.prd-field-value { font-size: 0.92rem; font-weight: 650; color: #0f172a; word-break: break-word; }
.prd-field-intake { display: inline-flex; align-items: center; gap: 0.45rem; }
.prd-intake-dot { width: 0.5rem; height: 0.5rem; border-radius: 999px; background: #1E4FA8; flex-shrink: 0; }

/* ===== Evidencia ===== */
.prd-photo-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
@media (max-width: 380px) { .prd-photo-grid { grid-template-columns: 1fr; } }
.prd-photo-item { border: 1px solid #e2e8f0; border-radius: 0.7rem; overflow: hidden; background: #fff; }
.prd-photo-item a { display: block; line-height: 0; }
.prd-photo-item a:hover { opacity: 0.9; }
.prd-photo-img { width: 100%; height: 8rem; object-fit: cover; display: block; }
.prd-photo-empty {
    border: 1.5px dashed #cbd5e1; border-radius: 0.7rem; padding: 1.5rem 1rem;
    text-align: center; color: #94a3b8; font-size: 0.875rem; font-weight: 600;
}

/* ===== Etiqueta ===== */
.prd-label-embed { border: 1px solid #e2e8f0; border-radius: 0.6rem; overflow: hidden; background: #f8fafc; }
.prd-label-iframe { width: 100%; height: 380px; border: none; display: block; }
@media (max-width: 639px) { .prd-label-iframe { height: 320px; } }
.prd-label-print-helper { position: absolute; left: -9999px; top: 0; width: 1px; height: 1px; border: 0; visibility: hidden; pointer-events: none; }

/* ===== Saco ===== */
.prd-saco-row {
    display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
    background: #f8fafc; border: 1px solid #eef2f7; border-radius: 0.65rem; padding: 0.8rem 0.95rem;
}
.prd-saco-row .prd-field-label { display: block; margin-bottom: 0.25rem; }
.prd-saco-row .prd-field-value { font-size: 1rem; font-weight: 800; }
.prd-saco-status { text-align: right; }
.prd-saco-scanned { margin: 0.75rem 0 0; font-size: 0.78rem; color: #0A2D6F; font-weight: 600; }
.prd-badge {
    display: inline-block; font-size: 0.62rem; font-weight: 800; letter-spacing: 0.06em;
    padding: 0.25rem 0.55rem; border-radius: 0.35rem; background: #E8EEF8; color: #0A2D6F;
}
.prd-side-link { color: #0A2D6F; font-weight: 700; font-size: 0.84rem; text-decoration: none; margin-left: 0.5rem; }
.prd-side-link:hover { color: #0A2D6F; text-decoration: underline; }

/* ===== Administración ===== */
.prd-card-admin { background: #f6fdf9; border-color: #bbf0d8; }
.prd-card-admin .prd-card-head { border-bottom-color: #d9f5e7; }
.prd-card-admin .prd-card-body { padding: 0.9rem; }
.prd-admin-stack { display: grid; gap: 0.75rem; }
.prd-admin-stack .admin-intake-panel,
.prd-admin-stack .admin-return-panel { margin: 0; box-shadow: none; }
</style>
@endsection
