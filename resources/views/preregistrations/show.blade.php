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
                        <dd class="preregs-dd">{{ $preregistration->received_nic_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($preregistration->ready_at)
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Listo para retiro</dt>
                        <dd class="preregs-dd">{{ $preregistration->ready_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    <div class="preregs-dl-row">
                        <dt class="preregs-dt">Fecha de creación</dt>
                        <dd class="preregs-dd">{{ $preregistration->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="preregs-card">
            <div class="preregs-card-header preregs-table-header preregs-card-header-photo">
                <h2 class="preregs-card-title">Foto del paquete</h2>
                <button type="button" onclick="document.getElementById('photoUpload').click()" class="preregs-btn preregs-btn-sm preregs-btn-replace">
                    {{ $preregistration->photos->count() > 0 ? 'Reemplazar foto' : 'Subir foto' }}
                </button>
            </div>
            <div class="preregs-card-body">
                <form id="photoUploadForm" action="{{ route('preregistrations.upload-photo', $preregistration->id) }}" method="POST" enctype="multipart/form-data" class="preregs-hidden">
                    @csrf
                    <input type="file" name="photo" id="photoUpload" accept="image/jpeg,image/jpg,image/png,image/webp" capture="environment" required>
                </form>

                @if($preregistration->photos->count() > 0)
                @php $photo = $preregistration->photos->first(); @endphp
                <div class="preregs-photo-wrap">
                    <img src="{{ $photo->url }}" alt="Foto del paquete" class="preregs-photo-img">
                    <p class="preregs-photo-link-wrap">
                        <a href="{{ $photo->url }}" target="_blank" class="preregs-link">Ver foto en tamaño completo</a>
                    </p>
                </div>
                @else
                <div class="preregs-photo-empty">
                    <p class="preregs-muted">No hay foto subida</p>
                    <button type="button" onclick="document.getElementById('photoUpload').click()" class="preregs-btn preregs-btn-primary">
                        Subir foto
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const photoUpload = document.getElementById('photoUpload');
    if (photoUpload) {
        photoUpload.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const form = document.getElementById('photoUploadForm');
                const formData = new FormData(form);
                const buttons = document.querySelectorAll('button[onclick*="photoUpload"]');
                const originalTexts = [];
                buttons.forEach(btn => {
                    originalTexts.push(btn.textContent);
                    btn.textContent = 'Subiendo...';
                    btn.disabled = true;
                });
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.id || data.url) {
                        window.location.reload();
                    } else {
                        alert('Error al subir foto: ' + (data.message || 'Error desconocido'));
                        buttons.forEach((btn, idx) => {
                            btn.textContent = originalTexts[idx];
                            btn.disabled = false;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al subir foto');
                    buttons.forEach((btn, idx) => {
                        btn.textContent = originalTexts[idx];
                        btn.disabled = false;
                    });
                });
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
</style>
@endsection
