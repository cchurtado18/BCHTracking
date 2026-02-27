@extends('layouts.app')

@section('title', 'Captura rápida Courier')

@section('content')
<div class="preregs-page preregs-form-page">
    <header class="preregs-hero">
        <div class="preregs-hero-inner">
            <div class="preregs-hero-text">
                <h1 class="preregs-hero-title">Captura rápida – Courier</h1>
                <p class="preregs-hero-subtitle">Toma la foto del paquete y, si quieres, ingresa el tracking. Otro usuario podrá completar los datos después.</p>
            </div>
            <a href="{{ route('preregistrations.index') }}" class="preregs-hero-btn">← Volver a preregistros</a>
        </div>
    </header>

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
            <form action="{{ route('preregistrations.store-quick-courier') }}" method="POST" enctype="multipart/form-data" style="margin: 0;">
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
                    <p class="quick-help">Solo se requiere una foto. En celular se abrirá directamente la cámara.</p>

                    <div class="quick-field">
                        <label for="photo" class="preregs-label">Seleccionar o tomar foto</label>
                        <input 
                            type="file" 
                            name="photo" 
                            id="photo" 
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            capture="environment"
                            required
                            class="preregs-input"
                        >
                        <p class="quick-help">Formatos: JPG, PNG, WEBP. Máximo 10MB.</p>
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

<style>
/* Reutilizamos el mismo estilo base de formularios de preregistros */
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
.preregs-hero-btn.preregs-hero-btn-secondary { background: rgba(15,118,110,0.08); color: #ecfeff; border-color: rgba(255,255,255,0.4); }
.preregs-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.preregs-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.preregs-alert-title { font-weight: 600; margin-bottom: 0.35rem; }
.preregs-alert-list { margin: 0; padding-left: 1.25rem; }
.preregs-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem; }
.preregs-card-header.preregs-form-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); padding: 0.75rem 1.5rem; }
.preregs-form-header .preregs-card-title { color: #fff; margin: 0; font-size: 1rem; font-weight: 600; }
.preregs-card-body { padding: 1.25rem 1.5rem; }
.preregs-form-body { padding: 1.5rem; }
.preregs-form-section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.preregs-section-title { font-size: 1.125rem; font-weight: 600; color: #0d9488; margin-bottom: 0.75rem; }
.preregs-photo-section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.preregs-form-actions { margin-top: 1.5rem; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; }
.preregs-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.preregs-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; font-weight: 600; }
.preregs-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.preregs-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.preregs-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.preregs-label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.preregs-input { width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827; box-sizing: border-box; }
.preregs-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }

.quick-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 0.5rem; }
.quick-field { max-width: 32rem; }
.quick-help { font-size: 0.8125rem; color: #6b7280; margin-top: 0.25rem; margin-bottom: 0; }
</style>
@endsection

