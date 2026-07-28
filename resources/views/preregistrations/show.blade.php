@extends('layouts.app')

@section('title', 'Detalle Preregistro')

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $statusReadable = [
        'PHOTO_PENDING' => 'Pendiente de datos',
        'RECEIVED_MIAMI' => 'Recibido en Miami',
        'IN_TRANSIT' => 'En tránsito',
        'IN_WAREHOUSE_NIC' => 'En almacén NIC',
        'READY' => 'Listo para retiro',
        'DELIVERED' => 'Entregado',
        'CANCELLED' => 'Inactivo',
    ];
    $statusLabel = $statusReadable[$preregistration->status] ?? $preregistration->status;
    $weightLbs = $preregistration->verified_weight_lbs ?? $preregistration->intake_weight_lbs;
    $showAdmin = auth()->user()?->is_admin;

    $stageIndex = match ($preregistration->status) {
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
        return $local->isToday() ? 'Hoy · ' . $local->format('H:i') : $local->format('d/m H:i');
    };
    $sacoCode = $preregistration->consolidationItem?->consolidation?->code;
    $timeline = [
        ['title' => 'Creado', 'meta' => $fmtMeta($preregistration->created_at)],
        ['title' => 'Recibido', 'meta' => $stageIndex >= 1 ? 'Registrado' : 'Pendiente'],
        ['title' => 'Tránsito', 'meta' => $sacoCode ?: ($stageIndex >= 2 ? 'Registrado' : 'Pendiente')],
        ['title' => 'Almacén NIC', 'meta' => $fmtMeta($preregistration->received_nic_at) ?? ($stageIndex >= 3 ? 'Registrado' : 'Pendiente')],
        ['title' => 'Retiro', 'meta' => $fmtMeta($preregistration->ready_at) ?? ($stageIndex >= 4 ? 'Registrado' : 'Pendiente')],
        ['title' => 'Entregado', 'meta' => $preregistration->delivery
            ? ($fmtMeta($preregistration->delivery->delivered_at) ?? 'Registrado')
            : 'Pendiente'],
    ];
@endphp
<div class="preregs-page preregs-show-page">
    {{-- ===== Banner ===== --}}
    <header class="prd-hero">
        <div class="prd-hero-top">
            <div class="prd-hero-identity">
                <a href="{{ route('preregistrations.index', session('preregistrations_index_filters', [])) }}" class="prd-back">← Volver al listado</a>
                <div class="prd-title-row">
                    <h1 class="prd-title">Envío #{{ $preregistration->id }}</h1>
                    <span class="prd-status">{{ $statusLabel }}</span>
                </div>
                <p class="prd-subtitle">{{ $preregistration->label_name }}@if($preregistration->agency) · {{ $preregistration->agency->name }}@endif</p>
            </div>
            <div class="prd-hero-actions">
                <div class="prd-action-group">
                    @if($preregistration->status === 'RECEIVED_MIAMI' && !$preregistration->consolidationItem)
                    <form action="{{ route('preregistrations.create-single-consolidation', $preregistration->id) }}" method="POST" class="prd-inline">
                        @csrf
                        <button type="submit" class="prd-btn prd-btn-primary">Enviar solo este paquete</button>
                    </form>
                    @endif
                    @if($preregistration->intake_type === 'DROP_OFF')
                        @if($preregistration->receipt_note_id)
                            <a href="{{ route('receipt-notes.print', $preregistration->receipt_note_id) }}" target="_blank" class="prd-btn prd-btn-secondary">Ver comprobante REC</a>
                        @else
                            <button type="button" class="prd-btn prd-btn-secondary" onclick="document.getElementById('rn-quick-modal').style.display='flex';document.getElementById('rn-quick-delivered-by').focus();">Comprobante recepción</button>
                        @endif
                    @endif
                    <a href="{{ route('preregistrations.edit', $preregistration->id) }}" class="prd-btn prd-btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-btn-icon"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                        Editar
                    </a>
                </div>
                @if($preregistration->warehouse_code)
                <div class="prd-action-group prd-action-print">
                    @if(!empty($dropoffLabelIds))
                    @php $dropoffIdsParam = implode(',', $dropoffLabelIds); @endphp
                    <a href="{{ route('preregistrations.dropoff-labels', ['ids' => $dropoffIdsParam]) }}" target="_blank" class="prd-btn prd-btn-ghost" title="Papel 4×6">Etiquetas 4×6 ({{ count($dropoffLabelIds) }})</a>
                    <a href="{{ route('preregistrations.dropoff-labels', ['ids' => $dropoffIdsParam, 'format' => 'narrow']) }}" target="_blank" class="prd-btn prd-btn-ghost">2.25×4</a>
                    @else
                    <a href="{{ route('preregistrations.label', $preregistration->id) }}" target="_blank" class="prd-btn prd-btn-ghost" title="Papel 4×6">Etiqueta 4×6</a>
                    <a href="{{ route('preregistrations.label', ['id' => $preregistration->id, 'format' => 'narrow']) }}" target="_blank" class="prd-btn prd-btn-ghost">2.25×4</a>
                    @endif
                </div>
                @endif
                @if(in_array($preregistration->status, ['RECEIVED_MIAMI', 'CANCELLED']))
                <form action="{{ route('preregistrations.destroy', $preregistration->id) }}" method="POST" class="prd-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este preregistro? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="prd-btn prd-btn-danger">Eliminar</button>
                </form>
                @endif
            </div>
        </div>
    </header>

    {{-- ===== Franja de datos clave ===== --}}
    <div class="prd-metrics">
        <div class="prd-metric prd-metric-accent">
            <span class="prd-metric-label">Warehouse</span>
            <span class="prd-metric-value prd-mono">{{ $preregistration->warehouse_code ?: '—' }}</span>
        </div>
        <div class="prd-metric">
            <span class="prd-metric-label">Tracking ID</span>
            <span class="prd-metric-value prd-mono">{{ $preregistration->tracking_external ?: '—' }}</span>
        </div>
        <div class="prd-metric">
            <span class="prd-metric-label">Peso bruto</span>
            <span class="prd-metric-value">{{ $weightLbs !== null ? number_format((float) $weightLbs, 2).' lbs' : '—' }}</span>
        </div>
        <div class="prd-metric">
            <span class="prd-metric-label">Servicio</span>
            <span class="prd-metric-value prd-metric-service">
                <span class="prd-chip prd-chip-{{ strtolower($preregistration->service_type ?? '') }}">{{ $preregistration->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span>
                {{ $preregistration->agency?->name ?: '—' }}
            </span>
        </div>
        <div class="prd-metric">
            <span class="prd-metric-label">Fecha ingreso</span>
            <span class="prd-metric-value">{{ $preregistration->created_at->timezone($displayTz)->format('d/m/Y') }}</span>
        </div>
    </div>

    {{-- ===== Timeline operativo (horizontal) ===== --}}
    <section class="prd-card prd-timeline-card">
        <header class="prd-card-head">
            <h2 class="prd-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M3 17 9 11l4 4 8-8M17 7h4v4"/></svg>
                Timeline Operativo
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
                            <span class="prd-field-value">{{ $preregistration->label_name }}</span>
                        </div>
                        <div class="prd-field">
                            <span class="prd-field-label">Tracking externo</span>
                            <span class="prd-field-value prd-mono">{{ $preregistration->tracking_external ?? '—' }}</span>
                        </div>
                        <div class="prd-field">
                            <span class="prd-field-label">Warehouse code</span>
                            <span class="prd-field-value prd-mono">{{ $preregistration->warehouse_code ?? '—' }}</span>
                        </div>
                        <div class="prd-field">
                            <span class="prd-field-label">Peso etiqueta</span>
                            <span class="prd-field-value">{{ $preregistration->intake_weight_lbs !== null ? number_format((float) $preregistration->intake_weight_lbs, 2).' lbs' : '—' }}</span>
                        </div>
                        @if($preregistration->verified_weight_lbs)
                        <div class="prd-field">
                            <span class="prd-field-label">Peso verificado</span>
                            <span class="prd-field-value">{{ number_format((float) $preregistration->verified_weight_lbs, 2) }} lbs</span>
                        </div>
                        @endif
                        @if($preregistration->dimension)
                        <div class="prd-field">
                            <span class="prd-field-label">Dimensión</span>
                            <span class="prd-field-value">{{ $preregistration->dimension }}</span>
                        </div>
                        @endif
                        @if($preregistration->description)
                        <div class="prd-field prd-field-span">
                            <span class="prd-field-label">Contenido declarado</span>
                            <span class="prd-field-value">{{ $preregistration->description }}</span>
                        </div>
                        @endif
                        <div class="prd-field {{ $preregistration->description ? '' : 'prd-field-span' }}">
                            <span class="prd-field-label">Método ingreso</span>
                            <span class="prd-field-value prd-field-intake">
                                <span class="prd-intake-dot"></span>
                                {{ $preregistration->intake_type == 'COURIER' ? 'Courier' : 'Drop Off' }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="prd-side">
            {{-- ===== Evidencia (fotos) ===== --}}
            <section class="prd-card">
                <header class="prd-card-head">
                    <h2 class="prd-card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M3 9a2 2 0 0 1 2-2h1.2a2 2 0 0 0 1.66-.9l.68-1.2A2 2 0 0 1 10.2 4h3.6a2 2 0 0 1 1.66.9l.68 1.2a2 2 0 0 0 1.66.9H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><circle cx="12" cy="13" r="3.2"/></svg>
                        Evidencia
                    </h2>
                    <div class="prd-head-actions">
                        <button type="button" id="btnTakePhoto" class="prd-icon-btn" title="Tomar foto">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9a2 2 0 0 1 2-2h1.2a2 2 0 0 0 1.66-.9l.68-1.2A2 2 0 0 1 10.2 4h3.6a2 2 0 0 1 1.66.9l.68 1.2a2 2 0 0 0 1.66.9H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><circle cx="12" cy="13" r="3.2"/></svg>
                        </button>
                        <button type="button" id="btnUploadPhotos" class="prd-icon-btn prd-icon-btn-primary" title="Subir fotos" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m0 0 4 4m-4-4-4 4M4 20h16"/></svg>
                        </button>
                    </div>
                </header>
                <div class="prd-card-body">
                    <form id="photoUploadForm" action="{{ route('preregistrations.upload-photo', $preregistration->id) }}" method="POST" enctype="multipart/form-data" class="preregs-hidden">
                        @csrf
                        <input type="file" name="photo" id="photoUpload" accept="image/jpeg,image/jpg,image/png,image/webp" capture="environment">
                    </form>

                    <div id="photoUploadUi" data-existing-count="{{ $preregistration->photos->count() }}">
                        <div id="pendingPhotosWrap" class="prd-pending-wrap preregs-hidden">
                            <p class="prd-help">Pendientes de subir:</p>
                            <div id="pendingPhotosGrid" class="prd-photo-grid"></div>
                        </div>
                    </div>

                    @if($preregistration->photos->count() > 0)
                    <div class="prd-photo-grid prd-photo-grid-uploaded">
                        @foreach($preregistration->photos as $photo)
                        <div class="prd-photo-item">
                            <a href="{{ $photo->url }}" target="_blank" rel="noopener">
                                <img src="{{ $photo->url }}" alt="Foto del paquete" class="prd-photo-img">
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="prd-photo-empty">Sin fotos aún</div>
                    @endif

                    <p class="prd-help prd-photo-counter" id="photoCounterText"></p>
                </div>
            </section>
        </aside>
    </div>

    @php $hasBottomSide = $preregistration->consolidationItem?->consolidation || $preregistration->delivery; @endphp
    @if($hasBottomSide || $showAdmin)
    <div class="prd-bottom {{ ($hasBottomSide && $showAdmin) ? 'prd-bottom-split' : '' }}">
        @if($hasBottomSide)
        <div class="prd-bottom-stack">
            @if($preregistration->consolidationItem?->consolidation)
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
                            <span class="prd-field-value prd-mono">{{ $preregistration->consolidationItem->consolidation->code }}</span>
                        </div>
                        <div class="prd-saco-status">
                            <span class="prd-field-label">Estado</span>
                            <span class="prd-badge">{{ $preregistration->consolidationItem->consolidation->status }}</span>
                        </div>
                    </div>
                    <a href="{{ route('consolidations.show', $preregistration->consolidationItem->consolidation->id) }}" class="prd-side-link">Ver saco completo →</a>
                </div>
            </section>
            @endif

            @if($preregistration->delivery)
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
                            <span class="prd-field-value">{{ $preregistration->delivery->delivered_at?->timezone($displayTz)->format('d/m/Y H:i') ?? '—' }}</span>
                        </div>
                        <div class="prd-field">
                            <span class="prd-field-label">Retiró</span>
                            <span class="prd-field-value">{{ $preregistration->delivery->delivered_to ?: '—' }}</span>
                        </div>
                        <div class="prd-field prd-field-span">
                            <span class="prd-field-label">Nota de salida</span>
                            <span class="prd-field-value">
                                @if($preregistration->delivery->deliveryNote)
                                <span class="prd-mono">{{ $preregistration->delivery->deliveryNote->code }}</span>
                                <a href="{{ route('deliveries.print-report', ['delivery_note_id' => $preregistration->delivery->delivery_note_id]) }}" target="_blank" class="prd-inline-link">Ver nota</a>
                                @else
                                Sin nota
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </section>
            @endif
        </div>
        @endif

        @if($showAdmin)
        <section class="prd-card prd-card-admin">
            <header class="prd-card-head">
                <h2 class="prd-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prd-card-icon"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.001a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.001a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                    Administración
                </h2>
            </header>
            <div class="prd-card-body prd-admin-stack">
                @include('preregistrations.partials.admin-change-intake-type', ['preregistration' => $preregistration])
                @include('preregistrations.partials.admin-reset-to-miami', ['preregistration' => $preregistration])
            </div>
        </section>
        @endif
    </div>
    @endif
</div>

@push('scripts')
<script>
    const photoUpload = document.getElementById('photoUpload');
    const photoUploadUi = document.getElementById('photoUploadUi');
    const btnTakePhoto = document.getElementById('btnTakePhoto');
    const btnUploadPhotos = document.getElementById('btnUploadPhotos');
    const pendingPhotosWrap = document.getElementById('pendingPhotosWrap');
    const pendingPhotosGrid = document.getElementById('pendingPhotosGrid');
    const photoCounterText = document.getElementById('photoCounterText');
    const photoUploadForm = document.getElementById('photoUploadForm');
    const MAX_PHOTOS = 3;
    let pendingFiles = [];
    let keepCameraOpen = true;

    function getExistingCount() {
        if (!photoUploadUi) return 0;
        return parseInt(photoUploadUi.dataset.existingCount || '0', 10) || 0;
    }

    function remainingSlots() {
        return Math.max(0, MAX_PHOTOS - getExistingCount() - pendingFiles.length);
    }

    function updatePhotoUiState() {
        if (!photoUploadUi) return;
        const existing = getExistingCount();
        const totalInQueue = existing + pendingFiles.length;
        const slots = remainingSlots();

        if (photoCounterText) {
            photoCounterText.textContent = slots > 0
                ? `Puedes agregar ${slots} foto${slots === 1 ? '' : 's'} más (${totalInQueue}/${MAX_PHOTOS}).`
                : `Límite alcanzado (${MAX_PHOTOS}/${MAX_PHOTOS}).`;
        }
        if (btnTakePhoto) btnTakePhoto.disabled = slots <= 0;
        if (btnUploadPhotos) {
            btnUploadPhotos.disabled = pendingFiles.length === 0;
            btnUploadPhotos.title = pendingFiles.length > 0
                ? `Subir fotos (${pendingFiles.length})`
                : 'Subir fotos';
        }
        if (pendingPhotosWrap) {
            pendingPhotosWrap.classList.toggle('preregs-hidden', pendingFiles.length === 0);
        }
    }

    function renderPendingPhotos() {
        if (!pendingPhotosGrid) return;
        pendingPhotosGrid.innerHTML = '';
        pendingFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'prd-photo-item';
            const img = document.createElement('img');
            img.className = 'prd-photo-img';
            img.alt = 'Foto pendiente';
            img.src = URL.createObjectURL(file);
            const actions = document.createElement('div');
            actions.className = 'prd-photo-item-actions';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'prd-btn prd-btn-outline prd-btn-sm';
            removeBtn.textContent = 'Quitar';
            removeBtn.addEventListener('click', function() {
                pendingFiles.splice(index, 1);
                renderPendingPhotos();
                updatePhotoUiState();
            });
            actions.appendChild(removeBtn);
            item.appendChild(img);
            item.appendChild(actions);
            pendingPhotosGrid.appendChild(item);
        });
    }

    async function uploadPendingFiles() {
        if (!photoUploadForm || pendingFiles.length === 0) return 0;
        let uploaded = 0;
        for (const file of pendingFiles) {
            const formData = new FormData(photoUploadForm);
            formData.set('photo', file);
            const response = await fetch(photoUploadForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });
            if (!response.ok) {
                let message = 'Error al subir fotos';
                try {
                    const data = await response.json();
                    message = data.message || message;
                } catch (e) {}
                throw new Error(message);
            }
            uploaded += 1;
        }
        return uploaded;
    }

    if (photoUpload && btnTakePhoto && btnUploadPhotos) {
        updatePhotoUiState();

        btnTakePhoto.addEventListener('click', function() {
            if (remainingSlots() <= 0) return;
            keepCameraOpen = true;
            photoUpload.click();
        });

        photoUpload.addEventListener('change', function() {
            const file = photoUpload.files && photoUpload.files[0];
            photoUpload.value = '';
            if (!file) return;
            if (remainingSlots() <= 0) {
                alert('Ya alcanzaste el máximo de 3 fotos.');
                return;
            }
            pendingFiles.push(file);
            renderPendingPhotos();
            updatePhotoUiState();
            if (keepCameraOpen && remainingSlots() > 0) {
                setTimeout(function() { photoUpload.click(); }, 180);
            }
        });

        btnUploadPhotos.addEventListener('click', async function() {
            keepCameraOpen = false;
            try {
                const uploaded = await uploadPendingFiles();
                if (uploaded > 0) {
                    window.location.reload();
                }
            } catch (err) {
                alert(err.message || 'Error al subir fotos');
                updatePhotoUiState();
            }
        });
    }
</script>
@endpush

<style>
.preregs-show-page { padding: 1.25rem 1rem 2rem; max-width: 92rem; margin: 0 auto; width: 100%; box-sizing: border-box; }
@media (min-width: 768px) { .preregs-show-page { padding: 1.5rem 1.5rem 2.5rem; } }

/* ===== Banner verde ===== */
.prd-hero {
    background: linear-gradient(135deg, #047857 0%, #059669 55%, #10b981 100%);
    color: #fff;
    border-radius: 1rem;
    padding: 1.35rem 1.5rem 3.4rem;
    box-shadow: 0 10px 30px rgba(5, 150, 105, 0.28);
}
.prd-hero-top { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; }
.prd-back { color: rgba(255,255,255,0.75); text-decoration: none; font-size: 0.8125rem; font-weight: 600; }
.prd-back:hover { color: #fff; }
.prd-title-row { display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem; margin-top: 0.45rem; }
.prd-title { margin: 0; font-size: 1.55rem; font-weight: 800; letter-spacing: -0.02em; color: #fff; }
.prd-subtitle { margin: 0.3rem 0 0; color: rgba(255,255,255,0.85); font-size: 0.925rem; }
.prd-status {
    display: inline-flex; align-items: center; padding: 0.28rem 0.7rem;
    border-radius: 999px; font-size: 0.75rem; font-weight: 700;
    background: #d1fae5; border: 1px solid #a7f3d0; color: #047857;
}

.prd-hero-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: flex-start; justify-content: flex-end; max-width: 100%; }
.prd-action-group { display: flex; flex-wrap: wrap; gap: 0.45rem; align-items: center; }
.prd-action-print { padding-left: 0.55rem; border-left: 1px solid rgba(255,255,255,0.3); }
.prd-inline { display: inline; margin: 0; }

.prd-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
    padding: 0.48rem 0.85rem; font-size: 0.8125rem; font-weight: 650; border-radius: 0.55rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none; white-space: nowrap;
}
.prd-btn-icon { width: 0.85rem; height: 0.85rem; }
.prd-btn-sm { padding: 0.38rem 0.7rem; font-size: 0.78rem; }
.prd-btn-primary { background: #fff; color: #047857; border-color: #fff; }
.prd-btn-primary:hover { background: #ecfdf5; }
.prd-btn-secondary { background: rgba(255,255,255,0.14); color: #fff; border-color: rgba(255,255,255,0.4); }
.prd-btn-secondary:hover { background: rgba(255,255,255,0.24); }
.prd-btn-ghost { background: transparent; color: rgba(255,255,255,0.9); border-color: rgba(255,255,255,0.35); }
.prd-btn-ghost:hover { background: rgba(255,255,255,0.12); color: #fff; }
.prd-btn-danger { background: transparent; color: #fecaca; border-color: rgba(254,202,202,0.5); }
.prd-btn-danger:hover { background: rgba(127,29,29,0.3); color: #fee2e2; }
.prd-btn-outline { background: #fff; color: #475569; border-color: #cbd5e1; }
.prd-btn-outline:hover { background: #f8fafc; color: #0f172a; }

/* ===== Franja de datos clave ===== */
.prd-metrics {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem;
    margin: -2.4rem 0.75rem 1.15rem; position: relative; z-index: 2;
}
@media (min-width: 1000px) { .prd-metrics { grid-template-columns: 1fr 1.35fr 1fr 1.25fr 1fr; margin-left: 1rem; margin-right: 1rem; } }
.prd-metric {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.85rem 1rem;
    box-shadow: 0 6px 18px rgba(15,23,42,0.08);
}
.prd-metric-accent { border-left: 3px solid #10b981; }
.prd-metric-accent .prd-metric-value { color: #059669; }
.prd-metric-label { display: block; font-size: 0.66rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #64748b; margin-bottom: 0.3rem; }
.prd-metric-value { font-size: 1.05rem; font-weight: 800; color: #0f172a; line-height: 1.25; word-break: break-word; }
.prd-metric-service { display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; font-size: 0.92rem; }
.prd-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: 0.02em; }

.prd-chip { display: inline-flex; align-items: center; padding: 0.16rem 0.5rem; border-radius: 999px; font-size: 0.66rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; border: 1px solid transparent; }
.prd-chip-air { background: #d1fae5; color: #047857; border-color: #a7f3d0; }
.prd-chip-sea { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }

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
.prd-htl-step.is-done::before { background: #10b981; }
.prd-htl-icon {
    position: relative; z-index: 1; width: 1.9rem; height: 1.9rem; border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
    background: #fff; border: 2px solid #e2e8f0; color: #cbd5e1;
}
.prd-htl-icon svg { width: 0.9rem; height: 0.9rem; }
.prd-htl-step.is-done .prd-htl-icon { background: #10b981; border-color: #a7f3d0; color: #fff; }
.prd-htl-step.is-current .prd-htl-icon {
    background: #d1fae5; border-color: #10b981; color: #047857;
    box-shadow: 0 0 0 4px rgba(16,185,129,0.16);
}
.prd-htl-title { display: block; font-size: 0.8rem; font-weight: 700; color: #b6c2d1; line-height: 1.2; }
.prd-htl-step.is-done .prd-htl-title { color: #0f172a; }
.prd-htl-step.is-current .prd-htl-title { color: #047857; }
.prd-htl-meta { display: block; font-size: 0.68rem; font-weight: 600; color: #b6c2d1; margin-top: -0.2rem; word-break: break-word; padding: 0 0.25rem; }
.prd-htl-step.is-done .prd-htl-meta { color: #64748b; }
.prd-htl-step.is-current .prd-htl-meta { color: #059669; }
@media (max-width: 700px) {
    .prd-htl { overflow-x: auto; padding-bottom: 0.5rem; }
    .prd-htl-step { min-width: 92px; }
}

/* ===== Layout 2 columnas ===== */
.prd-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; align-items: start; }
@media (min-width: 1024px) { .prd-layout { grid-template-columns: minmax(0, 1.6fr) minmax(300px, 0.85fr); } }
.prd-main, .prd-side { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }

/* ===== Fila inferior ===== */
.prd-bottom { display: grid; gap: 1rem; margin-top: 1rem; align-items: start; }
@media (min-width: 1024px) { .prd-bottom-split { grid-template-columns: minmax(280px, 1fr) 1.9fr; } }
.prd-bottom-stack { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }

.prd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.85rem; overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
.prd-card-head {
    padding: 0.85rem 1.15rem; border-bottom: 1px solid #eef2f7;
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;
}
.prd-card-title { margin: 0; font-size: 0.95rem; font-weight: 750; color: #0f172a; display: inline-flex; align-items: center; gap: 0.5rem; }
.prd-card-icon { width: 1rem; height: 1rem; color: #059669; flex-shrink: 0; }
.prd-card-body { padding: 1.1rem 1.15rem 1.2rem; }

/* ===== Datos del envío ===== */
.prd-fields { display: grid; grid-template-columns: 1fr; gap: 1rem 1.5rem; }
@media (min-width: 640px) { .prd-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.prd-fields-compact { gap: 0.75rem; }
.prd-field { display: flex; flex-direction: column; gap: 0.22rem; min-width: 0; }
.prd-field-span { grid-column: 1 / -1; }
.prd-field-label { font-size: 0.66rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.07em; text-transform: uppercase; }
.prd-field-value { font-size: 0.92rem; font-weight: 650; color: #0f172a; word-break: break-word; }
.prd-field-intake { display: inline-flex; align-items: center; gap: 0.45rem; }
.prd-intake-dot { width: 0.5rem; height: 0.5rem; border-radius: 999px; background: #10b981; flex-shrink: 0; }

/* ===== Evidencia ===== */
.prd-head-actions { display: flex; gap: 0.4rem; }
.prd-icon-btn {
    width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 0.5rem; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer;
}
.prd-icon-btn svg { width: 1rem; height: 1rem; }
.prd-icon-btn:hover { background: #f8fafc; color: #0f172a; }
.prd-icon-btn-primary { background: #059669; border-color: #059669; color: #fff; }
.prd-icon-btn-primary:hover { background: #047857; color: #fff; }
.prd-icon-btn:disabled { opacity: 0.45; cursor: not-allowed; }

.prd-help { margin: 0 0 0.65rem; color: #64748b; font-size: 0.8125rem; line-height: 1.4; }
.prd-photo-counter { margin: 0.75rem 0 0; text-align: center; color: #94a3b8; font-size: 0.75rem; }
.prd-photo-grid { display: grid; grid-template-columns: 1fr; gap: 0.75rem; }
.prd-photo-item { border: 1px solid #e2e8f0; border-radius: 0.7rem; overflow: hidden; background: #fff; }
.prd-photo-item a { display: block; line-height: 0; }
.prd-photo-img { width: 100%; height: 170px; object-fit: cover; display: block; }
.prd-photo-item-actions { padding: 0.45rem; display: flex; justify-content: center; }
.prd-photo-empty {
    border: 1.5px dashed #cbd5e1; border-radius: 0.7rem; padding: 1.5rem 1rem;
    text-align: center; color: #94a3b8; font-size: 0.875rem; font-weight: 600;
}
.prd-pending-wrap { margin-bottom: 0.75rem; }

/* ===== Saco ===== */
.prd-saco-row {
    display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
    background: #f8fafc; border: 1px solid #eef2f7; border-radius: 0.65rem; padding: 0.8rem 0.95rem;
}
.prd-saco-row .prd-field-label { display: block; margin-bottom: 0.25rem; }
.prd-saco-row .prd-field-value { font-size: 1rem; font-weight: 800; }
.prd-saco-status { text-align: right; }
.prd-badge {
    display: inline-block; font-size: 0.62rem; font-weight: 800; letter-spacing: 0.06em;
    padding: 0.25rem 0.55rem; border-radius: 0.35rem; background: #dbeafe; color: #1e40af;
}
.prd-side-link {
    display: block; margin-top: 0.9rem; padding-top: 0.8rem; border-top: 1px solid #f1f5f9;
    color: #059669; font-weight: 700; font-size: 0.84rem; text-decoration: none; text-align: center;
}
.prd-side-link:hover { color: #047857; text-decoration: underline; }
.prd-inline-link { color: #059669; font-weight: 700; font-size: 0.84rem; text-decoration: none; margin-left: 0.5rem; }
.prd-inline-link:hover { color: #047857; text-decoration: underline; }

/* ===== Administración ===== */
.prd-card-admin { background: #f6fdf9; border-color: #bbf0d8; }
.prd-card-admin .prd-card-head { border-bottom-color: #d9f5e7; }
.prd-card-admin .prd-card-body { padding: 0.9rem; }
.prd-admin-stack { display: grid; gap: 0.75rem; }
.prd-admin-stack .admin-intake-panel,
.prd-admin-stack .admin-return-panel { margin: 0; box-shadow: none; }

.preregs-hidden { display: none !important; }

/* ===== Modal comprobante ===== */
.rn-quick-modal {
    display: none; position: fixed; inset: 0; z-index: 60;
    background: rgba(15, 23, 42, 0.55); align-items: center; justify-content: center; padding: 1rem;
}
.rn-quick-modal-card {
    background: #fff; border-radius: 0.85rem; width: 100%; max-width: 30rem;
    box-shadow: 0 24px 48px rgba(0,0,0,0.25); overflow: hidden;
}
.rn-quick-modal-head {
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #047857 0%, #059669 50%, #10b981 100%);
    color: #fff; display: flex; justify-content: space-between; align-items: center;
}
.rn-quick-modal-title { margin: 0; font-size: 1rem; font-weight: 700; }
.rn-quick-modal-close {
    background: rgba(255,255,255,0.18); border: none; color: #fff;
    width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 16px; line-height: 1;
}
.rn-quick-modal-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem; }
.rn-quick-modal-row { display: flex; flex-direction: column; gap: 0.3rem; }
.rn-quick-modal-row label { font-size: 0.8125rem; font-weight: 600; color: #374151; }
.rn-quick-modal-row input {
    width: 100%; padding: 0.55rem 0.75rem; font-size: 0.875rem;
    border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827;
}
.rn-quick-modal-row input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105,0.15); }
.rn-quick-modal-foot {
    padding: 0.85rem 1.25rem; background: #f8fafc; border-top: 1px solid #e5e7eb;
    display: flex; justify-content: flex-end; gap: 0.5rem; flex-wrap: wrap;
}
.rn-quick-hint { margin: 0; font-size: 0.75rem; color: #6b7280; line-height: 1.4; }
</style>

@if($preregistration->intake_type === 'DROP_OFF' && !$preregistration->receipt_note_id)
<div id="rn-quick-modal" class="rn-quick-modal" role="dialog" aria-modal="true" aria-labelledby="rn-quick-modal-title">
    <form action="{{ route('preregistrations.quick-receipt', $preregistration->id) }}" method="POST" class="rn-quick-modal-card">
        @csrf
        <div class="rn-quick-modal-head">
            <h3 id="rn-quick-modal-title" class="rn-quick-modal-title">Generar comprobante de recepción</h3>
            <button type="button" class="rn-quick-modal-close" onclick="document.getElementById('rn-quick-modal').style.display='none';" aria-label="Cerrar">×</button>
        </div>
        <div class="rn-quick-modal-body">
            <p class="rn-quick-hint">Capture los datos del cliente que entregó este paquete. Se generará una nota REC-XXXXX para este único bulto.</p>
            <div class="rn-quick-modal-row">
                <label for="rn-quick-delivered-by">Nombre completo *</label>
                <input type="text" name="delivered_by" id="rn-quick-delivered-by" required maxlength="200" placeholder="Nombre y apellidos">
            </div>
            <div class="rn-quick-modal-row">
                <label for="rn-quick-id">Cédula / Identificación</label>
                <input type="text" name="delivered_by_id_number" id="rn-quick-id" maxlength="50" placeholder="Opcional">
            </div>
            <div class="rn-quick-modal-row">
                <label for="rn-quick-phone">Teléfono</label>
                <input type="text" name="delivered_by_phone" id="rn-quick-phone" maxlength="50" placeholder="Opcional">
            </div>
        </div>
        <div class="rn-quick-modal-foot">
            <button type="button" class="prd-btn prd-btn-outline" onclick="document.getElementById('rn-quick-modal').style.display='none';">Cancelar</button>
            <button type="submit" class="prd-btn" style="color:#fff;background:#059669;border-color:#059669;">Generar e imprimir</button>
        </div>
    </form>
</div>
@endif
@endsection
