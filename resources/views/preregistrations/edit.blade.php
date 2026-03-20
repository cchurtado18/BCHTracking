@extends('layouts.app')

@section('title', 'Editar Preregistro #' . $preregistration->id)

@section('content')
<div class="preregs-page preregs-form-page">
    <header class="preregs-hero">
        <div class="preregs-hero-inner">
            <div class="preregs-hero-text">
                <h1 class="preregs-hero-title">Editar Preregistro #{{ $preregistration->id }}</h1>
                <p class="preregs-hero-subtitle">Actualizar información del preregistro</p>
            </div>
            <a href="{{ route('preregistrations.show', $preregistration->id) }}" class="preregs-hero-btn">← Volver</a>
        </div>
    </header>

    @if($errors->any())
    <div class="preregs-alert preregs-alert-danger">
        <p class="preregs-alert-title">No se pudo actualizar el preregistro:</p>
        <ul class="preregs-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="preregs-card preregs-form-card">
        <div class="preregs-card-header preregs-form-header">
            <h2 class="preregs-card-title">Datos del preregistro</h2>
        </div>
        <div class="preregs-card-body preregs-form-body">
            <div class="preregs-edit-grid">
            <form action="{{ route('preregistrations.update', $preregistration->id) }}" method="POST" style="margin: 0;" class="preregs-edit-main">
                @csrf
                @method('PUT')

                <div class="preregs-fields-grid">
                    <div class="preregs-field preregs-field-full">
                        <label for="agency_id" class="preregs-label">Agencia (subagencia) *</label>
                        <select name="agency_id" id="agency_id" required class="preregs-select">
                            <option value="">Seleccione una agencia…</option>
                            @foreach($agencies as $agency)
                            <option value="{{ $agency->id }}" {{ (string) old('agency_id', $preregistration->agency_id) === (string) $agency->id ? 'selected' : '' }}>
                                {{ $agency->code ? $agency->code . ' - ' : '' }}{{ $agency->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('agency_id')
                        <p class="preregs-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="preregs-field">
                        <label for="label_name" class="preregs-label">Nombre en Etiqueta *</label>
                        <input type="text" name="label_name" id="label_name" value="{{ old('label_name', $preregistration->label_name) }}" required class="preregs-input">
                        @error('label_name')
                        <p class="preregs-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="preregs-field">
                        <label for="service_type" class="preregs-label">Tipo de Servicio *</label>
                        <select name="service_type" id="service_type" required class="preregs-select">
                            <option value="AIR" {{ old('service_type', $preregistration->service_type) == 'AIR' ? 'selected' : '' }}>Aéreo</option>
                            <option value="SEA" {{ old('service_type', $preregistration->service_type) == 'SEA' ? 'selected' : '' }}>Marítimo</option>
                        </select>
                        @error('service_type')
                        <p class="preregs-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="preregs-field">
                        <label for="tracking_external" class="preregs-label">Tracking Externo</label>
                        <input type="text" name="tracking_external" id="tracking_external" value="{{ old('tracking_external', $preregistration->tracking_external) }}" class="preregs-input">
                        @error('tracking_external')
                        <p class="preregs-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="preregs-field">
                        <label for="intake_weight_lbs" class="preregs-label">Peso (lbs) *</label>
                        <input type="number" step="0.01" name="intake_weight_lbs" id="intake_weight_lbs" value="{{ old('intake_weight_lbs', $preregistration->intake_weight_lbs) }}" required class="preregs-input">
                        @error('intake_weight_lbs')
                        <p class="preregs-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($preregistration->intake_type === 'DROP_OFF')
                    <div class="preregs-field">
                        <label for="dimension" class="preregs-label">Dimensión</label>
                        <input type="text" name="dimension" id="dimension" value="{{ old('dimension', $preregistration->dimension) }}" placeholder="ej: 10 x 8 x 5 in" class="preregs-input">
                        @error('dimension')
                        <p class="preregs-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif

                    <div class="preregs-field preregs-field-full">
                        <label for="description" class="preregs-label">Descripción del contenido</label>
                        <input type="text" name="description" id="description" value="{{ old('description', $preregistration->description) }}" maxlength="500" placeholder="Ej: Ropa, electrónicos, documentos..." class="preregs-input">
                        @error('description')
                        <p class="preregs-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="preregs-form-actions">
                    <a href="{{ route('preregistrations.show', $preregistration->id) }}" class="preregs-btn preregs-btn-secondary">Cancelar</a>
                    <button type="submit" class="preregs-btn preregs-btn-primary">Actualizar</button>
                </div>
            </form>
            @if($preregistration->photos->count() > 0)
            <aside class="preregs-edit-photo">
                <h3 class="preregs-edit-photo-title">Fotos del paquete ({{ $preregistration->photos->count() }})</h3>
                <div class="preregs-edit-photos-list">
                    @foreach($preregistration->photos as $idx => $photo)
                    <div class="preregs-photo-wrap">
                        <a href="{{ $photo->url }}" target="_blank" class="preregs-photo-link-block" title="Abrir foto {{ $idx + 1 }} en tamaño completo">
                            <img src="{{ $photo->url }}" alt="Foto del paquete {{ $idx + 1 }}" class="preregs-photo-img">
                        </a>
                        <p class="preregs-photo-link-wrap">
                            <a href="{{ $photo->url }}" target="_blank" class="preregs-link">Usar foto {{ $idx + 1 }} (ver completa)</a>
                        </p>
                    </div>
                    @endforeach
                </div>
                @if($preregistration->status === 'PHOTO_PENDING')
                <p class="preregs-edit-photo-hint">Este preregistro fue creado como captura rápida. Complete los datos usando la foto como referencia.</p>
                @endif
            </aside>
            @endif
            </div>
        </div>
    </div>
</div>

<style>
.preregs-form-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.preregs-form-page .preregs-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.preregs-form-page .preregs-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.preregs-form-page .preregs-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.preregs-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.preregs-hero-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none; }
.preregs-hero-btn:hover { background: #f0fdfa; color: #0d9488; }
.preregs-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.preregs-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.preregs-alert-title { font-weight: 600; margin-bottom: 0.35rem; }
.preregs-alert-list { margin: 0; padding-left: 1.25rem; }
.preregs-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem; }
.preregs-card-header.preregs-form-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); padding: 0.75rem 1.5rem; }
.preregs-form-header .preregs-card-title { color: #fff; margin: 0; font-size: 1rem; font-weight: 600; }
.preregs-card-body { padding: 1.25rem 1.5rem; }
.preregs-form-body { padding: 1.5rem; }
.preregs-edit-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1.2fr); gap: 1.75rem; align-items: flex-start; }
@media (max-width: 900px) { .preregs-edit-grid { grid-template-columns: 1fr; } }
.preregs-fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 1.25rem; }
.preregs-field-full { grid-column: 1 / -1; }
.preregs-label { display: block; font-size: 0.8125rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.preregs-input, .preregs-select { width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827; }
.preregs-input:focus, .preregs-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.preregs-field-error { font-size: 0.875rem; color: #dc2626; margin-top: 0.25rem; }
.preregs-form-actions { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; }
.preregs-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.preregs-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; font-weight: 600; }
.preregs-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.preregs-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.preregs-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.preregs-edit-photos-list { display: grid; grid-template-columns: 1fr; gap: 12px; }
.preregs-photo-wrap { text-align: center; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; background: #fff; }
.preregs-photo-link-block { display: block; text-decoration: none; }
.preregs-photo-img { max-width: 100%; height: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.preregs-photo-link-wrap { margin-top: 0.75rem; font-size: 0.8125rem; }
.preregs-edit-photo-title { font-size: 0.95rem; font-weight: 600; color: #374151; margin: 0 0 0.75rem; }
.preregs-edit-photo-hint { font-size: 0.8125rem; color: #6b7280; margin-top: 0.75rem; }
</style>
@endsection
