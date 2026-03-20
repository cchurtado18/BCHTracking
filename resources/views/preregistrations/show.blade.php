@extends('layouts.app')

@section('title', 'Detalle Preregistro')

@section('content')
<div class="preregs-page preregs-show-page">
    <header class="preregs-hero">
        <div class="preregs-hero-inner">
            <div class="preregs-hero-text">
                <h1 class="preregs-hero-title">Preregistro #{{ $preregistration->id }}</h1>
                <p class="preregs-hero-subtitle">Detalle del preregistro</p>
            </div>
            <div class="preregs-hero-actions">
                @if($preregistration->warehouse_code)
                @if(!empty($dropoffLabelIds))
                <a href="{{ route('preregistrations.dropoff-labels', ['ids' => implode(',', $dropoffLabelIds)]) }}" target="_blank" class="preregs-btn preregs-btn-print">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
                    Imprimir etiqueta{{ count($dropoffLabelIds) > 1 ? 's' : '' }} ({{ count($dropoffLabelIds) }} bultos)
                </a>
                @else
                <a href="{{ route('preregistrations.label', $preregistration->id) }}" target="_blank" class="preregs-btn preregs-btn-print">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
                    Imprimir etiqueta
                </a>
                @endif
                @endif
                <a href="{{ route('preregistrations.edit', $preregistration->id) }}" class="preregs-btn preregs-btn-outline-primary">Editar</a>
                @if($preregistration->status === 'RECEIVED_MIAMI' && !$preregistration->consolidationItem)
                <form action="{{ route('preregistrations.create-single-consolidation', $preregistration->id) }}" method="POST" class="preregs-form-inline">
                    @csrf
                    <button type="submit" class="preregs-btn preregs-btn-primary">Enviar solo este paquete (saco de 1)</button>
                </form>
                @endif
                @if(in_array($preregistration->status, ['RECEIVED_MIAMI', 'CANCELLED']))
                <form action="{{ route('preregistrations.destroy', $preregistration->id) }}" method="POST" class="preregs-form-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este preregistro? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="preregs-btn preregs-btn-danger">Eliminar</button>
                </form>
                @endif
                <a href="{{ route('preregistrations.index', session('preregistrations_index_filters', [])) }}" class="preregs-btn preregs-btn-outline-light">← Volver</a>
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
                            <span class="preregs-badge preregs-badge-status">{{ $preregistration->status }}</span>
                        </dd>
                    </div>
                    @if($preregistration->received_nic_at)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Recibido en Nicaragua</dt>
                        <dd class="preregs-dd">{{ $preregistration->received_nic_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($preregistration->ready_at)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Listo para retiro</dt>
                        <dd class="preregs-dd">{{ $preregistration->ready_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Fecha de creación</dt>
                        <dd class="preregs-dd">{{ $preregistration->created_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</dd>
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
                        <button type="button" id="btnTakePhoto" class="preregs-btn preregs-btn-primary">Tomar foto</button>
                        <button type="button" id="btnUploadPhotos" class="preregs-btn preregs-btn-replace" disabled>Subir fotos</button>
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
@media (min-width: 640px) { .preregs-show-page { padding: 1.5rem 1.25rem; } }
@media (min-width: 1024px) { .preregs-show-page { padding: 1.5rem 2rem; } }
.preregs-show-page .preregs-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
@media (max-width: 639px) { .preregs-show-page .preregs-hero { padding: 1.25rem 1rem; border-radius: 0.75rem; } }
.preregs-show-page .preregs-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
@media (max-width: 639px) { .preregs-show-page .preregs-hero-title { font-size: 1.375rem; } }
.preregs-show-page .preregs-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.preregs-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.preregs-hero-text { min-width: 0; }
.preregs-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.preregs-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
@media (max-width: 639px) { .preregs-btn { padding: 0.45rem 0.75rem; font-size: 0.8125rem; } }
/* Botones del banner: fondo blanco para que resalten sobre el hero */
.preregs-show-page .preregs-hero .preregs-btn-print { background: #fff; color: #0d9488; border-color: rgba(255,255,255,0.9); }
.preregs-show-page .preregs-hero .preregs-btn-print:hover { background: #f0fdfa; color: #0f766e; border-color: #fff; }
.preregs-show-page .preregs-hero .preregs-btn-primary { background: #fff; color: #0d9488; border-color: rgba(255,255,255,0.9); }
.preregs-show-page .preregs-hero .preregs-btn-primary:hover { background: #f0fdfa; color: #0f766e; border-color: #fff; }
.preregs-show-page .preregs-hero .preregs-btn-outline-primary { background: #fff; color: #0d9488; border-color: rgba(255,255,255,0.9); }
.preregs-show-page .preregs-hero .preregs-btn-outline-primary:hover { background: #f0fdfa; color: #0f766e; border-color: #fff; }
.preregs-show-page .preregs-hero .preregs-btn-danger { background: #fff; color: #dc2626; border-color: rgba(255,255,255,0.9); }
.preregs-show-page .preregs-hero .preregs-btn-danger:hover { background: #fef2f2; color: #b91c1c; border-color: #fff; }
.preregs-show-page .preregs-hero .preregs-btn-outline-light { background: #fff; color: #0f766e; border-color: rgba(255,255,255,0.9); }
.preregs-show-page .preregs-hero .preregs-btn-outline-light:hover { background: #f0fdfa; color: #0d9488; border-color: #fff; }
/* Botones fuera del banner (ej. en cards) mantienen estilo original */
.preregs-btn-print { background: #0d9488; color: #fff; border-color: #0d9488; }
.preregs-btn-print:hover { background: #0f766e; color: #fff; border-color: #0f766e; }
.preregs-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.preregs-btn-primary:hover { background: #0f766e; color: #fff; border-color: #0f766e; }
.preregs-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.preregs-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; border-color: #0d9488; }
.preregs-btn-danger { background: #0f766e; color: #fff; border-color: #0f766e; }
.preregs-btn-danger:hover { background: #115e59; color: #fff; border-color: #115e59; }
.preregs-btn-outline-light { background: rgba(255,255,255,0.2); color: #fff; border-color: rgba(255,255,255,0.5); }
.preregs-btn-outline-light:hover { background: rgba(255,255,255,0.3); color: #fff; }
.preregs-btn-replace { background: #0d9488; color: #fff; border-color: #0d9488; flex-shrink: 0; }
.preregs-btn-replace:hover { background: #0f766e; color: #fff; border-color: #0f766e; }
.preregs-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.preregs-form-inline { display: inline; }
.preregs-show-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; min-width: 0; }
.preregs-show-grid > .preregs-card { min-width: 0; }
@media (min-width: 992px) { .preregs-show-grid { grid-template-columns: 1fr 1fr; } }
.preregs-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 0; }
@media (max-width: 639px) { .preregs-card { border-radius: 0.5rem; } }
.preregs-card-header.preregs-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; padding: 0.75rem 1rem; }
@media (min-width: 640px) { .preregs-card-header.preregs-table-header { padding: 0.75rem 1.5rem; } }
.preregs-card-header-photo .preregs-card-title { margin: 0; min-width: 0; }
.preregs-card-header .preregs-card-title { color: #fff; font-size: 0.9375rem; font-weight: 600; }
@media (max-width: 639px) { .preregs-card-header .preregs-card-title { font-size: 0.875rem; } }
.preregs-card-body { padding: 1rem 1rem; }
@media (min-width: 640px) { .preregs-card-body { padding: 1.25rem 1.5rem; } }
.preregs-dl { margin: 0; }
.preregs-dl-row { margin-bottom: 1rem; }
.preregs-dl-row:last-child { margin-bottom: 0; }
.preregs-dt { font-size: 0.8125rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem; }
.preregs-dd { margin: 0; font-size: 0.9375rem; color: #111827; word-wrap: break-word; overflow-wrap: break-word; }
.preregs-code { font-family: ui-monospace, monospace; font-weight: 600; }
.preregs-muted { color: #6b7280; font-size: 0.875rem; margin: 0; }
.preregs-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; max-width: 100%; overflow-wrap: break-word; }
.preregs-badge-intake { background: #ccfbf1; color: #0f766e; }
.preregs-badge-air { background: #ccfbf1; color: #0f766e; }
.preregs-badge-sea { background: #99f6e4; color: #0f766e; }
.preregs-badge-status { background: #ccfbf1; color: #0f766e; }
.preregs-photo-wrap { text-align: center; min-width: 0; }
.preregs-photo-img { max-width: 100%; height: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: block; }
.preregs-photo-link-wrap { margin-top: 1rem; }
.preregs-link { color: #0d9488; font-weight: 500; text-decoration: none; font-size: 0.875rem; }
.preregs-link:hover { color: #0f766e; text-decoration: underline; }
.preregs-photo-empty { text-align: center; padding: 1.5rem 1rem; border: 2px dashed #e5e7eb; border-radius: 0.75rem; }
@media (min-width: 640px) { .preregs-photo-empty { padding: 2rem; } }
.preregs-photo-empty .preregs-muted { margin-bottom: 1rem; }
.preregs-hidden { display: none !important; }
.preregs-photo-actions { display: flex; gap: 8px; flex-wrap: wrap; margin: 10px 0; }
.preregs-photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
.preregs-photo-item { border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; background: #fff; }
.preregs-photo-item-actions { margin-top: 8px; display: flex; justify-content: center; }
.preregs-pending-wrap { margin-top: 8px; margin-bottom: 10px; }
</style>
@endsection
