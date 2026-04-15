@extends('layouts.app')

@section('title', 'Detalle Preregistro')

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
@endphp
<div class="preregs-page preregs-show-page">
    <header class="preregs-hero">
        <div class="preregs-hero-inner">
            <div class="preregs-hero-text">
                <h1 class="preregs-hero-title">Preregistro #{{ $preregistration->id }}</h1>
                <p class="preregs-hero-subtitle">Detalle del preregistro</p>
            </div>
            <div class="preregs-hero-actions">
                <div class="preregs-hero-actions-main">
                    @if($preregistration->status === 'RECEIVED_MIAMI' && !$preregistration->consolidationItem)
                    <form action="{{ route('preregistrations.create-single-consolidation', $preregistration->id) }}" method="POST" class="preregs-form-inline">
                        @csrf
                        <button type="submit" class="preregs-btn preregs-btn-primary">Enviar solo este paquete (saco de 1)</button>
                    </form>
                    @endif
                    <a href="{{ route('preregistrations.edit', $preregistration->id) }}" class="preregs-btn preregs-btn-outline-primary">Editar</a>
                    @if($preregistration->warehouse_code)
                    @if(!empty($dropoffLabelIds))
                    @php $dropoffIdsParam = implode(',', $dropoffLabelIds); @endphp
                    <a href="{{ route('preregistrations.dropoff-labels', ['ids' => $dropoffIdsParam]) }}" target="_blank" class="preregs-btn preregs-btn-outline-primary" title="Papel 4×6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
                        Etiquetas 4×6 ({{ count($dropoffLabelIds) }} bulto{{ count($dropoffLabelIds) !== 1 ? 's' : '' }})
                    </a>
                    <a href="{{ route('preregistrations.dropoff-labels', ['ids' => $dropoffIdsParam, 'format' => 'narrow']) }}" target="_blank" class="preregs-btn preregs-btn-outline-primary" title="Driver 2.25×4">
                        Etiquetas 2.25×4
                    </a>
                    @else
                    <a href="{{ route('preregistrations.label', $preregistration->id) }}" target="_blank" class="preregs-btn preregs-btn-outline-primary" title="Papel 4×6 pulgadas">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
                        Etiqueta 4×6
                    </a>
                    <a href="{{ route('preregistrations.label', ['id' => $preregistration->id, 'format' => 'narrow']) }}" target="_blank" class="preregs-btn preregs-btn-outline-primary" title="Si el driver solo muestra 2.25×4 pulgadas">
                        Etiqueta 2.25×4
                    </a>
                    @endif
                    @endif
                </div>
                <div class="preregs-hero-actions-secondary">
                    @if(in_array($preregistration->status, ['RECEIVED_MIAMI', 'CANCELLED']))
                    <form action="{{ route('preregistrations.destroy', $preregistration->id) }}" method="POST" class="preregs-form-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este preregistro? Esta acción no se puede deshacer.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="preregs-btn preregs-btn-danger">Eliminar</button>
                    </form>
                    @endif
                    <a href="{{ route('preregistrations.index', session('preregistrations_index_filters', [])) }}" class="preregs-btn preregs-btn-link">← Volver</a>
                </div>
            </div>
        </div>
    </header>

    <div class="preregs-show-grid">
        <div class="preregs-card">
            <div class="preregs-card-header preregs-table-header">
                <h2 class="preregs-card-title">Información</h2>
            </div>
            <div class="preregs-card-body">
                <dl class="preregs-dl">
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Tracking externo</dt>
                        <dd class="preregs-dd">{{ $preregistration->tracking_external ?? 'N/A' }}</dd>
                    </div>
                    @if($preregistration->warehouse_code)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Warehouse code</dt>
                        <dd class="preregs-dd preregs-code">{{ $preregistration->warehouse_code }}</dd>
                    </div>
                    @endif
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Nombre en etiqueta</dt>
                        <dd class="preregs-dd">{{ $preregistration->label_name }}</dd>
                    </div>
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Tipo de ingreso</dt>
                        <dd class="preregs-dd">
                            <span class="preregs-badge preregs-badge-intake">{{ $preregistration->intake_type == 'COURIER' ? 'Courier' : 'Drop Off' }}</span>
                        </dd>
                    </div>
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Tipo de servicio</dt>
                        <dd class="preregs-dd">
                            <span class="preregs-badge preregs-badge-{{ strtolower($preregistration->service_type ?? '') }}">{{ $preregistration->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span>
                        </dd>
                    </div>
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Peso (etiqueta)</dt>
                        <dd class="preregs-dd">{{ $preregistration->intake_weight_lbs }} lbs</dd>
                    </div>
                    @if($preregistration->dimension)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Dimensión</dt>
                        <dd class="preregs-dd">{{ $preregistration->dimension }}</dd>
                    </div>
                    @endif
                    @if($preregistration->description)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Descripción del contenido</dt>
                        <dd class="preregs-dd">{{ $preregistration->description }}</dd>
                    </div>
                    @endif
                    @if($preregistration->verified_weight_lbs)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Peso verificado</dt>
                        <dd class="preregs-dd">{{ $preregistration->verified_weight_lbs }} lbs</dd>
                    </div>
                    @endif
                    @if($preregistration->agency)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Agencia</dt>
                        <dd class="preregs-dd">{{ $preregistration->agency->name }}</dd>
                    </div>
                    @endif
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Estado</dt>
                        <dd class="preregs-dd">
                            @php
                                $statusReadable = [
                                    'PHOTO_PENDING' => 'Pendiente de datos',
                                    'RECEIVED_MIAMI' => 'Recibido en Miami',
                                    'IN_TRANSIT' => 'En tránsito',
                                    'IN_WAREHOUSE_NIC' => 'En almacén NIC',
                                    'READY' => 'Listo para retiro',
                                    'DELIVERED' => 'Entregado',
                                    'CANCELLED' => 'Inactivo',
                                ];
                            @endphp
                            <span class="preregs-badge preregs-badge-status">{{ $statusReadable[$preregistration->status] ?? $preregistration->status }}</span>
                        </dd>
                    </div>
                    @if($preregistration->received_nic_at)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Recibido en Nicaragua</dt>
                        <dd class="preregs-dd">{{ $preregistration->received_nic_at->timezone($displayTz)->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($preregistration->ready_at)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Listo para retiro</dt>
                        <dd class="preregs-dd">{{ $preregistration->ready_at->timezone($displayTz)->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Fecha de creación</dt>
                        <dd class="preregs-dd">{{ $preregistration->created_at->timezone($displayTz)->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="preregs-card">
            <div class="preregs-card-header preregs-table-header preregs-card-header-photo">
                <h2 class="preregs-card-title">Fotos del paquete ({{ $preregistration->photos->count() }}/3)</h2>
            </div>
            <div class="preregs-card-body">
                <form id="photoUploadForm" action="{{ route('preregistrations.upload-photo', $preregistration->id) }}" method="POST" enctype="multipart/form-data" class="preregs-hidden">
                    @csrf
                    <input type="file" name="photo" id="photoUpload" accept="image/jpeg,image/jpg,image/png,image/webp" capture="environment">
                </form>
                <div id="photoUploadUi" data-existing-count="{{ $preregistration->photos->count() }}">
                    <p class="preregs-muted" id="photoCounterText">Puedes subir hasta 3 fotos. Toma 1, 2 o 3 y luego pulsa "Subir fotos".</p>
                    <div class="preregs-photo-actions">
                        <button type="button" id="btnTakePhoto" class="preregs-btn preregs-btn-photo-subtle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h3l1.5-2.25h6L16.5 7.5h3A1.5 1.5 0 0 1 21 9v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18V9a1.5 1.5 0 0 1 1.5-1.5Z"/><circle cx="12" cy="13" r="3"/></svg>
                            Tomar foto
                        </button>
                        <button type="button" id="btnUploadPhotos" class="preregs-btn preregs-btn-photo-subtle" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V7.5m0 0-3 3m3-3 3 3M4.5 16.5v1.5A1.5 1.5 0 0 0 6 19.5h12a1.5 1.5 0 0 0 1.5-1.5v-1.5"/></svg>
                            Subir fotos
                        </button>
                    </div>
                    <div id="pendingPhotosWrap" class="preregs-pending-wrap preregs-hidden">
                        <p class="preregs-muted" style="margin-bottom: 8px;">Fotos pendientes:</p>
                        <div id="pendingPhotosGrid" class="preregs-photo-grid"></div>
                    </div>
                </div>

                @if($preregistration->photos->count() > 0)
                <p class="preregs-muted" style="margin: 12px 0 8px;">Fotos subidas:</p>
                <div class="preregs-photo-grid">
                    @foreach($preregistration->photos as $photo)
                    <div class="preregs-photo-item">
                        <img src="{{ $photo->url }}" alt="Foto del paquete" class="preregs-photo-img">
                        <p class="preregs-photo-link-wrap">
                            <a href="{{ $photo->url }}" target="_blank" class="preregs-link">Ver completa</a>
                        </p>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="preregs-photo-empty" style="margin-top: 12px;">
                    <p class="preregs-muted">No hay fotos subidas aún</p>
                </div>
                @endif
            </div>
        </div>
    </div>
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
            photoCounterText.textContent = `Fotos: ${totalInQueue}/3${pendingFiles.length ? ` (${pendingFiles.length} pendientes)` : ''}`;
        }

        if (btnTakePhoto) {
            btnTakePhoto.disabled = slots <= 0;
            btnTakePhoto.textContent = slots <= 0 ? 'Límite alcanzado (3/3)' : 'Tomar foto';
        }

        if (btnUploadPhotos) {
            btnUploadPhotos.disabled = pendingFiles.length === 0;
        }

        if (pendingPhotosWrap) {
            pendingPhotosWrap.classList.toggle('preregs-hidden', pendingFiles.length === 0);
        }
    }

    function renderPendingPhotos() {
        if (!pendingPhotosGrid) return;
        pendingPhotosGrid.innerHTML = '';
        pendingFiles.forEach((item, idx) => {
            const box = document.createElement('div');
            box.className = 'preregs-photo-item';

            const img = document.createElement('img');
            img.src = item.previewUrl;
            img.className = 'preregs-photo-img';
            img.alt = `Foto pendiente ${idx + 1}`;
            box.appendChild(img);

            const actions = document.createElement('div');
            actions.className = 'preregs-photo-item-actions';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'preregs-btn preregs-btn-sm preregs-btn-outline-primary';
            removeBtn.textContent = 'Quitar';
            removeBtn.addEventListener('click', function() {
                URL.revokeObjectURL(item.previewUrl);
                pendingFiles.splice(idx, 1);
                renderPendingPhotos();
                updatePhotoUiState();
            });
            actions.appendChild(removeBtn);
            box.appendChild(actions);
            pendingPhotosGrid.appendChild(box);
        });
    }

    async function uploadPendingFiles() {
        if (!photoUploadForm || pendingFiles.length === 0) return;
        btnUploadPhotos.disabled = true;
        btnTakePhoto.disabled = true;
        btnUploadPhotos.textContent = 'Subiendo...';

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        let uploaded = 0;
        for (let i = 0; i < pendingFiles.length; i++) {
            const fd = new FormData();
            fd.append('photo', pendingFiles[i].file);
            const resp = await fetch(photoUploadForm.action, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            });
            const data = await resp.json();
            if (!resp.ok) {
                throw new Error(data.message || 'Error al subir foto');
            }
            uploaded++;
        }
        return uploaded;
    }

    if (photoUpload && btnTakePhoto && btnUploadPhotos && photoUploadUi) {
        updatePhotoUiState();

        btnTakePhoto.addEventListener('click', function() {
            keepCameraOpen = true;
            if (remainingSlots() <= 0) {
                alert('Este preregistro ya alcanzó el máximo de 3 fotos.');
                return;
            }
            photoUpload.click();
        });

        photoUpload.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            if (remainingSlots() <= 0) {
                alert('Este preregistro ya alcanzó el máximo de 3 fotos.');
                e.target.value = '';
                return;
            }
            pendingFiles.push({
                file: file,
                previewUrl: URL.createObjectURL(file),
            });
            renderPendingPhotos();
            updatePhotoUiState();
            e.target.value = '';

            if (keepCameraOpen && remainingSlots() > 0) {
                setTimeout(function() { photoUpload.click(); }, 200);
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
                btnUploadPhotos.textContent = 'Subir fotos';
                updatePhotoUiState();
            }
        });
    }
</script>
@endpush

<style>
.preregs-show-page { padding: 1.5rem 1rem; max-width: 96rem; margin: 0 auto; width: 100%; box-sizing: border-box; }
@media (min-width: 640px) { .preregs-show-page { padding: 1.25rem 1.25rem; } }
@media (min-width: 1024px) { .preregs-show-page { padding: 1.25rem 2rem; max-width: 84rem; } }
.preregs-show-page .preregs-hero {
    background: linear-gradient(135deg, #f0fdfa 0%, #ecfdf5 100%);
    border: 1px solid #cce9e3;
    border-radius: 0.875rem; padding: 1rem 1.2rem; margin-bottom: 1.25rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}
@media (max-width: 639px) { .preregs-show-page .preregs-hero { padding: 1.25rem 1rem; border-radius: 0.75rem; } }
.preregs-show-page .preregs-hero-title { color: #0f172a; margin: 0; font-size: 1.62rem; font-weight: 700; }
@media (max-width: 639px) { .preregs-show-page .preregs-hero-title { font-size: 1.375rem; } }
.preregs-show-page .preregs-hero-subtitle { color: #475569; margin: 0.25rem 0 0; font-size: 0.9rem; }
.preregs-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.preregs-hero-text { min-width: 0; }
.preregs-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem; }
.preregs-hero-actions-main, .preregs-hero-actions-secondary { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.preregs-hero-actions-secondary { padding-left: 0.5rem; border-left: 1px solid #d1e5e1; }
.preregs-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 0.92rem; font-size: 0.86rem; font-weight: 500; border-radius: 0.58rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: all 0.2s ease; }
@media (max-width: 639px) { .preregs-btn { padding: 0.45rem 0.75rem; font-size: 0.8125rem; } }
.preregs-btn-print { background: #0d9488; color: #fff; border-color: #0d9488; }
.preregs-btn-print:hover { background: #0f766e; color: #fff; border-color: #0f766e; }
.preregs-btn-primary { background: #0f766e; color: #fff; border-color: #0f766e; font-weight: 600; }
.preregs-btn-primary:hover { background: #115e59; color: #fff; border-color: #115e59; }
.preregs-btn-outline-primary { background: #fff; color: #334155; border-color: #cbd5e1; }
.preregs-btn-outline-primary:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }
.preregs-btn-danger { background: #fff; color: #dc2626; border-color: #fecaca; }
.preregs-btn-danger:hover { background: #fef2f2; color: #b91c1c; border-color: #fca5a5; }
.preregs-btn-link {
    background: transparent;
    color: #475569;
    border-color: transparent;
    padding: 0.25rem 0.3rem;
}
.preregs-btn-link:hover { color: #0f172a; background: #f1f5f9; border-color: #e2e8f0; }
.preregs-btn-photo-subtle { background: #fff; color: #334155; border-color: #cbd5e1; flex-shrink: 0; }
.preregs-btn-photo-subtle:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }
.preregs-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.preregs-form-inline { display: inline; }
.preregs-show-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; min-width: 0; align-items: start; }
.preregs-show-grid > .preregs-card { min-width: 0; }
@media (min-width: 992px) { .preregs-show-grid { grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr); } }
.preregs-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 0; }
@media (max-width: 639px) { .preregs-card { border-radius: 0.5rem; } }
.preregs-card-header.preregs-table-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; padding: 0.75rem 1rem; }
@media (min-width: 640px) { .preregs-card-header.preregs-table-header { padding: 0.75rem 1.5rem; } }
.preregs-card-header-photo .preregs-card-title { margin: 0; min-width: 0; }
.preregs-card-header .preregs-card-title { color: #0f172a; font-size: 0.95rem; font-weight: 600; }
@media (max-width: 639px) { .preregs-card-header .preregs-card-title { font-size: 0.875rem; } }
.preregs-card-body { padding: 0.95rem 1rem; }
@media (min-width: 640px) { .preregs-card-body { padding: 1.1rem 1.25rem; } }
.preregs-dl {
    margin: 0;
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.55rem;
}
.preregs-dl-row {
    margin: 0;
    padding: 0.52rem 0.65rem;
    border: 1px solid #e5ebf3;
    border-radius: 0.55rem;
    background: #fcfdff;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.18rem;
    min-height: 64px;
    justify-content: center;
}
.preregs-dt { font-size: 0.68rem; font-weight: 600; color: #64748b; margin: 0; text-transform: uppercase; letter-spacing: 0.08em; flex-shrink: 0; line-height: 1.2; }
.preregs-dd { margin: 0; font-size: 0.97rem; font-weight: 700; color: #0f172a; text-align: left; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.3; }
.preregs-code { font-family: ui-monospace, monospace; font-weight: 600; }
.preregs-muted { color: #6b7280; font-size: 0.84rem; margin: 0; }
.preregs-badge { display: inline-block; padding: 0.24rem 0.55rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px; max-width: 100%; overflow-wrap: break-word; border: 1px solid transparent; }
.preregs-badge-intake { background: #e2e8f0; color: #334155; border-color: #cbd5e1; }
.preregs-badge-air { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.preregs-badge-sea { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
.preregs-badge-status { background: #e0f2fe; color: #075985; border-color: #bae6fd; }
.preregs-photo-wrap { text-align: center; min-width: 0; }
.preregs-photo-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 0.65rem;
    border: 1px solid #dbe3ec;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    display: block;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.preregs-photo-img:hover {
    transform: scale(1.02);
    box-shadow: 0 10px 18px rgba(15, 23, 42, 0.16);
}
.preregs-photo-link-wrap { margin-top: 1rem; }
.preregs-link { color: #0d9488; font-weight: 500; text-decoration: none; font-size: 0.875rem; }
.preregs-link:hover { color: #0f766e; text-decoration: underline; }
.preregs-photo-empty { text-align: center; padding: 1.5rem 1rem; border: 2px dashed #e5e7eb; border-radius: 0.75rem; }
@media (min-width: 640px) { .preregs-photo-empty { padding: 2rem; } }
.preregs-photo-empty .preregs-muted { margin-bottom: 1rem; }
.preregs-hidden { display: none !important; }
.preregs-photo-actions { display: flex; gap: 8px; flex-wrap: wrap; margin: 10px 0 14px; }
.preregs-photo-grid { display: grid; grid-template-columns: 1fr; gap: 14px; align-items: start; }
.preregs-photo-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; background: #fff; }
.preregs-photo-item-actions { margin-top: 8px; display: flex; justify-content: center; }
.preregs-pending-wrap { margin-top: 8px; margin-bottom: 10px; }
</style>
@endsection
