@extends('layouts.app')

@section('title', 'Captura rápida Courier')

@section('content')
<div class="preregs-page preregs-form-page">
    <x-module-banner
        section="General"
        current="Captura rápida"
        title="Captura rápida – Courier"
        subtitle="Tome la foto del paquete y, si quiere, el tracking. Otro usuario podrá completar los datos después."
        back-href="{{ route('preregistrations.index') }}"
        back-label="Volver a preregistros"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.87 47.87 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if($errors->any())
    <div class="preregs-alert preregs-alert-danger">
        <p class="preregs-alert-title">No se pudo guardar el preregistro rápido:</p>
        <ul class="preregs-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="preregs-card preregs-form-card">
        <div class="preregs-card-header preregs-form-header">
            <h2 class="preregs-card-title">Datos mínimos del paquete (Courier)</h2>
        </div>
        <div class="preregs-card-body preregs-form-body">
            <form id="quickCourierForm" action="{{ route('preregistrations.store-quick-courier') }}" method="POST" enctype="multipart/form-data" style="margin: 0;">
                @csrf

                <div class="quick-grid">
                    <div class="quick-field">
                        <label for="tracking_external" class="preregs-label">Tracking externo (opcional)</label>
                        <input 
                            type="text" 
                            name="tracking_external" 
                            id="tracking_external" 
                            value="{{ old('tracking_external') }}"
                            class="preregs-input"
                            placeholder="1Z999AA10123456784"
                        >
                        <p class="quick-help">Si el paquete trae tracking de courier, ingrésalo aquí para poder buscarlo luego.</p>
                    </div>
                </div>

                <div class="preregs-form-section preregs-photo-section">
                    <h3 class="preregs-section-title">Foto del paquete *</h3>
                    <p class="quick-help">Puedes tomar hasta 3 fotos. Toma 1, 2 o 3 y luego pulsa Guardar.</p>

                    <div class="quick-field">
                        <label for="photo" class="preregs-label">Cámara del teléfono</label>
                        <input 
                            type="file" 
                            id="photo" 
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            capture="environment"
                            class="preregs-input"
                            style="display:none;"
                        >
                        <div class="quick-actions" style="margin-top:8px;">
                            <button type="button" id="quickTakePhoto" class="preregs-btn preregs-btn-primary">Tomar foto</button>
                            <button type="button" id="quickStopCamera" class="preregs-btn preregs-btn-secondary">Detener cámara</button>
                        </div>
                        <p class="quick-help" id="quickCounterText">Fotos: 0/3</p>
                        <div id="quickPendingWrap" class="preregs-hidden" style="margin-top:10px;">
                            <div id="quickPendingGrid" class="quick-photo-grid"></div>
                        </div>
                        <p class="quick-help">Formatos: JPG, PNG, WEBP. Máximo 10MB por foto.</p>
                    </div>
                </div>

                <div class="preregs-form-actions">
                    <a href="{{ route('preregistrations.index') }}" class="preregs-btn preregs-btn-secondary">Cancelar</a>
                    <button type="submit" class="preregs-btn preregs-btn-primary">
                        Guardar preregistro rápido
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
@include('partials.compress-image-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('quickCourierForm');
    var input = document.getElementById('photo');
    var btnTake = document.getElementById('quickTakePhoto');
    var btnStop = document.getElementById('quickStopCamera');
    var counter = document.getElementById('quickCounterText');
    var pendingWrap = document.getElementById('quickPendingWrap');
    var pendingGrid = document.getElementById('quickPendingGrid');
    var maxPhotos = 3;
    var keepCameraOpen = true;
    var files = [];

    function refreshCounter() {
        if (counter) counter.textContent = 'Fotos: ' + files.length + '/3';
        if (btnTake) {
            btnTake.disabled = files.length >= maxPhotos;
            btnTake.textContent = files.length >= maxPhotos ? 'Límite alcanzado (3/3)' : 'Tomar foto';
        }
        if (pendingWrap) pendingWrap.classList.toggle('preregs-hidden', files.length === 0);
    }

    function renderGrid() {
        if (!pendingGrid) return;
        pendingGrid.innerHTML = '';
        files.forEach(function(item, idx) {
            var box = document.createElement('div');
            box.className = 'quick-photo-item';

            var img = document.createElement('img');
            img.src = item.previewUrl;
            img.className = 'quick-photo-img';
            img.alt = 'Foto ' + (idx + 1);
            box.appendChild(img);

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'preregs-btn preregs-btn-sm preregs-btn-secondary';
            remove.textContent = 'Quitar';
            remove.style.marginTop = '8px';
            remove.addEventListener('click', function() {
                URL.revokeObjectURL(item.previewUrl);
                files.splice(idx, 1);
                renderGrid();
                refreshCounter();
            });
            box.appendChild(remove);
            pendingGrid.appendChild(box);
        });
    }

    if (btnTake && input) {
        btnTake.addEventListener('click', function() {
            keepCameraOpen = true;
            if (files.length >= maxPhotos) return;
            input.click();
        });
    }

    if (btnStop) {
        btnStop.addEventListener('click', function() {
            keepCameraOpen = false;
        });
    }

    if (input) {
        input.addEventListener('change', async function(e) {
            var file = e.target.files && e.target.files[0];
            if (!file) return;
            if (files.length >= maxPhotos) {
                alert('Máximo 3 fotos.');
                e.target.value = '';
                return;
            }
            if (btnTake) btnTake.disabled = true;
            try {
                file = await window.skylinkCompressImage(file);
            } catch (err) {}
            files.push({ file: file, previewUrl: URL.createObjectURL(file) });
            renderGrid();
            refreshCounter();
            e.target.value = '';

            if (keepCameraOpen && files.length < maxPhotos) {
                setTimeout(function() { input.click(); }, 200);
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (files.length === 0) {
                alert('Toma al menos una foto antes de guardar.');
                return;
            }
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Guardando...'; }

            var fd = new FormData();
            fd.append('_token', form.querySelector('input[name="_token"]').value);
            fd.append('tracking_external', (document.getElementById('tracking_external') || {}).value || '');
            files.forEach(function(item) { fd.append('photos[]', item.file); });

            fetch(form.action, {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' }
            })
            .then(async function(res) {
                var data = await res.json().catch(function() { return {}; });
                if (!res.ok) throw new Error(data.message || 'No se pudo guardar.');
                if (data.redirect_url) window.location.href = data.redirect_url;
                else window.location.reload();
            })
            .catch(function(err) {
                alert(err.message || 'Error al guardar');
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Guardar preregistro rápido'; }
            });
        });
    }

    refreshCounter();
});
</script>
@endpush

<style>
/* Reutilizamos el mismo estilo base de formularios de preregistros */
.preregs-form-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.preregs-form-page .preregs-hero {
    background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}
.preregs-form-page .preregs-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.preregs-form-page .preregs-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.preregs-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.preregs-hero-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; background: #fff; color: #0A2D6F; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none; }
.preregs-hero-btn:hover { background: #F4F8FD; color: #0A2D6F; }
.preregs-hero-btn.preregs-hero-btn-secondary { background: rgba(4, 120, 87,0.08); color: #ecfeff; border-color: rgba(255,255,255,0.4); }
.preregs-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.preregs-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.preregs-alert-title { font-weight: 600; margin-bottom: 0.35rem; }
.preregs-alert-list { margin: 0; padding-left: 1.25rem; }
.preregs-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem; }
.preregs-card-header.preregs-form-header { background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%); padding: 0.75rem 1.5rem; }
.preregs-form-header .preregs-card-title { color: #fff; margin: 0; font-size: 1rem; font-weight: 600; }
.preregs-card-body { padding: 1.25rem 1.5rem; }
.preregs-form-body { padding: 1.5rem; }
.preregs-form-section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.preregs-section-title { font-size: 1.125rem; font-weight: 600; color: #0A2D6F; margin-bottom: 0.75rem; }
.preregs-photo-section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.preregs-form-actions { margin-top: 1.5rem; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; }
.preregs-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.preregs-btn-primary { background: #0A2D6F; color: #fff; border-color: #0A2D6F; font-weight: 600; }
.preregs-btn-primary:hover { background: #0A2D6F; border-color: #0A2D6F; color: #fff; }
.preregs-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.preregs-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.preregs-label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.preregs-input { width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827; box-sizing: border-box; }
.preregs-input:focus { outline: none; border-color: #0A2D6F; box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }

.quick-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 0.5rem; }
.quick-field { max-width: 32rem; }
.quick-help { font-size: 0.8125rem; color: #6b7280; margin-top: 0.25rem; margin-bottom: 0; }
.quick-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.quick-photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; margin-top: 8px; }
.quick-photo-item { border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; text-align: center; }
.quick-photo-img { width: 100%; height: auto; border-radius: 6px; border: 1px solid #d1d5db; }
</style>
@endsection

