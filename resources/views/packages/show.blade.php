@extends('layouts.app')

@section('title', 'Detalle Paquete')

@section('content')
@php
    $statusLabels = [
        'RECEIVED_MIAMI' => 'Recibido Miami',
        'IN_TRANSIT' => 'En tránsito',
        'IN_WAREHOUSE_NIC' => 'En almacén NIC',
        'READY' => 'Listo para retiro',
        'DELIVERED' => 'Entregado',
    ];
    $statusLabel = $statusLabels[$package->status] ?? $package->status;
@endphp
<div class="packages-page packages-show-page">
    <header class="packages-hero">
        <div class="packages-hero-inner">
            <div class="packages-hero-text">
                <h1 class="packages-hero-title">Paquete #{{ $package->id }}</h1>
                <p class="packages-hero-subtitle">Detalle del paquete</p>
            </div>
            <div class="packages-hero-actions">
                @if($package->warehouse_code && (!auth()->user() || !auth()->user()->isAgencyUser()))
                <a href="{{ route('preregistrations.label', $package->id) }}" target="_blank" class="packages-btn packages-btn-print">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
                    Imprimir etiqueta
                </a>
                @endif
                @if($package->status == 'IN_WAREHOUSE_NIC')
                <a href="{{ route('packages.process', $package->id) }}" class="packages-btn packages-btn-outline-primary">Procesar paquete</a>
                @endif
                <a href="{{ route('packages.index', session('packages_index_filters', [])) }}" class="packages-btn packages-btn-outline-light">← Volver</a>
            </div>
        </div>
    </header>

    <div class="packages-show-grid">
        {{-- Card Información --}}
        <div class="packages-card">
            <div class="packages-card-header packages-table-header">
                <h2 class="packages-card-title">Información</h2>
            </div>
            <div class="packages-card-body">
                <dl class="packages-dl">
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Código para escanear</dt>
                        <dd class="packages-dd packages-code">{{ $package->warehouse_code ?? $package->tracking_external ?? 'N/A' }}</dd>
                    </div>
                    @if($package->tracking_external)
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Tracking externo</dt>
                        <dd class="packages-dd">{{ $package->tracking_external }}</dd>
                    </div>
                    @endif
                    @if($package->warehouse_code)
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Warehouse code</dt>
                        <dd class="packages-dd packages-code">{{ $package->warehouse_code }}</dd>
                    </div>
                    @endif
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Nombre en etiqueta</dt>
                        <dd class="packages-dd">{{ $package->label_name }}</dd>
                    </div>
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Tipo de ingreso</dt>
                        <dd class="packages-dd">
                            <span class="packages-badge packages-badge-intake">{{ $package->intake_type == 'COURIER' ? 'Courier' : 'Drop Off' }}</span>
                        </dd>
                    </div>
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Tipo de servicio</dt>
                        <dd class="packages-dd">
                            <span class="packages-badge packages-badge-{{ strtolower($package->service_type ?? '') }}">{{ $package->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span>
                        </dd>
                    </div>
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Peso (etiqueta)</dt>
                        <dd class="packages-dd">{{ $package->intake_weight_lbs }} lbs</dd>
                    </div>
                    @if($package->verified_weight_lbs)
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Peso verificado</dt>
                        <dd class="packages-dd">{{ $package->verified_weight_lbs }} lbs</dd>
                    </div>
                    @endif
                    @if($package->agency)
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Agencia</dt>
                        <dd class="packages-dd">{{ $package->agency->name }}</dd>
                    </div>
                    @endif
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Estado</dt>
                        <dd class="packages-dd">
                            <span class="packages-badge packages-badge-status-{{ strtolower($package->status ?? '') }}">{{ $statusLabel }}</span>
                            @if(in_array($package->status, ['RECEIVED_MIAMI', 'IN_TRANSIT']))
                            <span class="packages-badge packages-badge-prereg">Preregistro</span>
                            @endif
                        </dd>
                    </div>
                    @if($package->received_nic_at)
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Recibido en Nicaragua</dt>
                        <dd class="packages-dd">{{ $package->received_nic_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($package->ready_at)
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Listo para retiro</dt>
                        <dd class="packages-dd">{{ $package->ready_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($package->label_print_count > 0)
                    <div class="packages-dl-row">
                        <dt class="packages-dt">Impresiones</dt>
                        <dd class="packages-dd">{{ $package->label_print_count }} vez(es) @if($package->label_last_printed_at)<span class="packages-muted">({{ $package->label_last_printed_at->format('d/m/Y H:i') }})</span>@endif</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="packages-show-sidebar">
            {{-- Fotos --}}
            <div class="packages-card">
                <div class="packages-card-header packages-table-header">
                    <h2 class="packages-card-title">Fotos ({{ $package->photos->count() }})</h2>
                </div>
                <div class="packages-card-body">
                    @if($package->photos->count() > 0)
                    <div class="packages-photos-grid">
                        @foreach($package->photos as $photo)
                        <div class="packages-photo-item">
                            <a href="{{ $photo->url }}" target="_blank" rel="noopener noreferrer" class="packages-photo-link" title="Clic para ver imagen completa">
                                <img src="{{ $photo->url }}" alt="Foto del paquete" class="packages-photo-img">
                            </a>
                            <p class="packages-photo-hint">Clic para ver completa</p>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="packages-muted">No hay fotos subidas</p>
                    @endif
                </div>
            </div>

            @if($package->warehouse_code && (!auth()->user() || !auth()->user()->isAgencyUser()))
            <div class="packages-card">
                <div class="packages-card-header packages-table-header">
                    <h2 class="packages-card-title">Etiqueta</h2>
                </div>
                <div class="packages-card-body">
                    <a href="{{ route('preregistrations.label', $package->id) }}" target="_blank" rel="noopener" class="packages-btn packages-btn-primary packages-btn-sm mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
                        Ver e imprimir etiqueta
                    </a>
                    <div class="packages-label-embed">
                        <iframe src="{{ route('preregistrations.label', $package->id) }}?embed=1" title="Etiqueta del paquete" class="packages-label-iframe"></iframe>
                    </div>
                </div>
            </div>
            @endif

            @if($package->consolidationItem && $package->consolidationItem->consolidation)
            <div class="packages-card">
                <div class="packages-card-header packages-table-header">
                    <h2 class="packages-card-title">Saco</h2>
                </div>
                <div class="packages-card-body">
                    <dl class="packages-dl packages-dl-compact">
                        <div class="packages-dl-row">
                            <dt class="packages-dt">Código</dt>
                            <dd class="packages-dd packages-code">{{ $package->consolidationItem->consolidation->code }}</dd>
                        </div>
                        <div class="packages-dl-row">
                            <dt class="packages-dt">Estado</dt>
                            <dd class="packages-dd">
                                <span class="packages-badge packages-badge-{{ strtolower($package->consolidationItem->consolidation->status ?? '') }}">{{ $package->consolidationItem->consolidation->status == 'SENT' ? 'Enviado' : ($package->consolidationItem->consolidation->status == 'OPEN' ? 'Abierto' : $package->consolidationItem->consolidation->status) }}</span>
                            </dd>
                        </div>
                        @if($package->consolidationItem->scanned_at)
                        <div class="packages-dl-row">
                            <dt class="packages-dt">Escaneado</dt>
                            <dd class="packages-dd packages-dd-success">{{ $package->consolidationItem->scanned_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.packages-show-page { padding: 1.5rem 1rem; max-width: 96rem; margin: 0 auto; width: 100%; box-sizing: border-box; }
@media (min-width: 640px) { .packages-show-page { padding: 1.5rem 1.25rem; } }
@media (min-width: 1024px) { .packages-show-page { padding: 1.5rem 2rem; } }
.packages-show-page .packages-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
@media (max-width: 639px) { .packages-show-page .packages-hero { padding: 1.25rem 1rem; border-radius: 0.75rem; } }
.packages-show-page .packages-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
@media (max-width: 639px) { .packages-show-page .packages-hero-title { font-size: 1.375rem; } }
.packages-show-page .packages-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.packages-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.packages-hero-text { min-width: 0; }
.packages-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.packages-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
@media (max-width: 639px) { .packages-btn { padding: 0.45rem 0.75rem; font-size: 0.8125rem; } }
/* Botones del banner: fondo blanco para que resalten sobre el hero */
.packages-show-page .packages-hero .packages-btn-print { background: #fff; color: #0d9488; border-color: rgba(255,255,255,0.9); }
.packages-show-page .packages-hero .packages-btn-print:hover { background: #f0fdfa; color: #0f766e; border-color: #fff; }
.packages-show-page .packages-hero .packages-btn-primary { background: #fff; color: #0d9488; border-color: rgba(255,255,255,0.9); }
.packages-show-page .packages-hero .packages-btn-primary:hover { background: #f0fdfa; color: #0f766e; border-color: #fff; }
.packages-show-page .packages-hero .packages-btn-outline-primary { background: #fff; color: #0d9488; border-color: rgba(255,255,255,0.9); }
.packages-show-page .packages-hero .packages-btn-outline-primary:hover { background: #f0fdfa; color: #0f766e; border-color: #fff; }
.packages-show-page .packages-hero .packages-btn-outline-light { background: #fff; color: #0f766e; border-color: rgba(255,255,255,0.9); }
.packages-show-page .packages-hero .packages-btn-outline-light:hover { background: #f0fdfa; color: #0d9488; border-color: #fff; }
/* Botones fuera del banner mantienen estilo original */
.packages-btn-print { background: #059669; color: #fff; border-color: #059669; }
.packages-btn-print:hover { background: #047857; color: #fff; }
.packages-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.packages-btn-primary:hover { background: #0f766e; color: #fff; }
.packages-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.packages-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.packages-btn-outline-light { background: rgba(255,255,255,0.2); color: #fff; border-color: rgba(255,255,255,0.5); }
.packages-btn-outline-light:hover { background: rgba(255,255,255,0.3); color: #fff; }
.packages-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.mb-3 { margin-bottom: 0.75rem; }
.packages-show-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; min-width: 0; }
.packages-show-grid > .packages-card { min-width: 0; }
.packages-show-grid > .packages-show-sidebar { min-width: 0; }
@media (min-width: 992px) { .packages-show-grid { grid-template-columns: 1fr 1fr; } }
.packages-show-sidebar { display: flex; flex-direction: column; gap: 1.5rem; min-width: 0; }
.packages-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 0; }
@media (max-width: 639px) { .packages-card { border-radius: 0.5rem; } }
.packages-card-header.packages-table-header {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;
    padding: 0.75rem 1rem;
}
@media (min-width: 640px) { .packages-card-header.packages-table-header { padding: 0.75rem 1.5rem; } }
.packages-card-header.packages-table-header .packages-card-title { color: #fff; margin: 0; min-width: 0; }
.packages-card-title { font-size: 0.9375rem; font-weight: 600; color: #374151; }
@media (max-width: 639px) { .packages-card-title { font-size: 0.875rem; } }
.packages-card-body { padding: 1rem 1rem; }
@media (min-width: 640px) { .packages-card-body { padding: 1.25rem 1.5rem; } }
.packages-dl { margin: 0; }
.packages-dl-row { margin-bottom: 1rem; }
.packages-dl-row:last-child { margin-bottom: 0; }
.packages-dl-compact .packages-dl-row { margin-bottom: 0.75rem; }
.packages-dt { font-size: 0.8125rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem; }
.packages-dd { margin: 0; font-size: 0.9375rem; color: #111827; word-wrap: break-word; overflow-wrap: break-word; }
.packages-code { font-family: ui-monospace, monospace; font-weight: 600; }
.packages-muted { color: #6b7280; font-size: 0.875rem; margin: 0; }
.packages-dd-success { color: #059669; font-weight: 500; }
.packages-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; max-width: 100%; overflow-wrap: break-word; }
.packages-badge-intake { background: #d1fae5; color: #047857; }
.packages-badge-air { background: #dbeafe; color: #1d4ed8; }
.packages-badge-sea { background: #d1fae5; color: #047857; }
.packages-badge-received_miami { background: #e5e7eb; color: #374151; }
.packages-badge-in_transit { background: #ffedd5; color: #c2410c; }
.packages-badge-in_warehouse_nic { background: #dbeafe; color: #1d4ed8; }
.packages-badge-ready { background: #d1fae5; color: #047857; }
.packages-badge-delivered { background: #ede9fe; color: #5b21b6; }
.packages-badge-status-received_miami { background: #e5e7eb; color: #374151; }
.packages-badge-status-in_transit { background: #ffedd5; color: #c2410c; }
.packages-badge-status-in_warehouse_nic { background: #dbeafe; color: #1d4ed8; }
.packages-badge-status-ready { background: #d1fae5; color: #047857; }
.packages-badge-status-delivered { background: #ede9fe; color: #5b21b6; }
.packages-badge-prereg { background: #fef3c7; color: #92400e; margin-left: 0.25rem; }
.packages-badge-open { background: #dbeafe; color: #1d4ed8; }
.packages-badge-sent { background: #d1fae5; color: #047857; }
.packages-photos-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; min-width: 0; }
@media (min-width: 480px) { .packages-photos-grid { gap: 1rem; } }
@media (max-width: 380px) { .packages-photos-grid { grid-template-columns: 1fr; } }
.packages-photo-item { min-width: 0; }
.packages-photo-link { display: block; border-radius: 0.5rem; border: 1px solid #e5e7eb; overflow: hidden; transition: border-color 0.2s, box-shadow 0.2s; }
.packages-photo-link:hover { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.packages-photo-img { width: 100%; height: 8rem; object-fit: cover; display: block; }
@media (max-width: 639px) { .packages-photo-img { height: 6.5rem; } }
.packages-photo-hint { font-size: 0.75rem; color: #6b7280; text-align: center; margin: 0.25rem 0 0; }
.packages-label-embed { border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; background: #f9fafb; max-width: 100%; min-width: 0; }
.packages-label-iframe { width: 100%; height: 380px; border: none; display: block; }
@media (max-width: 639px) { .packages-label-iframe { height: 320px; } }
</style>
@endsection
