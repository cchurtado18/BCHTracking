@extends('layouts.app')

@section('title', 'Editar Agencia')

@section('content')
<div class="agency-page agency-form-page">
    <header class="agency-hero">
        <div class="agency-hero-inner">
            <div class="agency-hero-text">
                <h1 class="agency-hero-title">Editar Agencia</h1>
                <p class="agency-hero-subtitle">{{ $agency->name }}</p>
            </div>
            <a href="{{ route('agencies.show', $agency->id) }}" class="agency-hero-btn">← Volver</a>
        </div>
    </header>

    @if($errors->any())
    <div class="agency-alert agency-alert-danger">
        <ul class="agency-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="agency-card agency-form-card">
        <div class="agency-card-header agency-form-header">
            <h2 class="agency-card-title">Datos de la agencia</h2>
        </div>
        <div class="agency-card-body">
            <form action="{{ route('agencies.update', $agency->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="agency-form-section">
                    <div class="agency-field">
                        <label class="agency-label">Código</label>
                        <p class="agency-readonly agency-code">{{ $agency->code }}</p>
                        <p class="agency-field-hint">Asignado por el sistema; no se puede modificar.</p>
                    </div>
                    <div class="agency-field">
                        <label for="name" class="agency-label">Nombre *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $agency->name) }}" required class="agency-input">
                        <p class="agency-field-hint">No puede coincidir con el nombre de otra subagencia.</p>
                        @error('name')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label for="phone" class="agency-label">Teléfono</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $agency->phone) }}" class="agency-input">
                        @error('phone')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label for="address" class="agency-label">Dirección</label>
                        <input type="text" name="address" id="address" value="{{ old('address', $agency->address) }}" placeholder="Dirección de la subagencia" class="agency-input">
                        @error('address')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label for="department" class="agency-label">Departamento (Nicaragua)</label>
                        <select name="department" id="department" class="agency-select">
                            <option value="">— Seleccionar —</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept }}" {{ old('department', $agency->department) === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                        @error('department')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label for="logo" class="agency-label">Logo (opcional)</label>
                        @if($agency->logo_url)
                        <div class="agency-logo-row">
                            <img src="{{ $agency->logo_url }}" alt="Logo actual" class="agency-logo-preview">
                            <label class="agency-checkbox-label">
                                <input type="checkbox" name="remove_logo" value="1" class="agency-checkbox">
                                <span>Quitar logo</span>
                            </label>
                        </div>
                        @endif
                        <p class="agency-field-hint">Se muestra en la etiqueta sin fondo. PNG con fondo transparente. JPEG, PNG, GIF o WebP, máx. 2 MB.</p>
                        <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="agency-input-file">
                        @error('logo')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label class="agency-checkbox-label">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $agency->is_active) ? 'checked' : '' }} class="agency-checkbox">
                            <span>Activa</span>
                        </label>
                    </div>
                </div>

                <div class="agency-form-actions">
                    <a href="{{ route('agencies.show', $agency->id) }}" class="agency-btn agency-btn-secondary">Cancelar</a>
                    <button type="submit" class="agency-btn agency-btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.agency-form-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.agency-form-page .agency-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.agency-form-page .agency-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.agency-form-page .agency-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.agency-form-page .agency-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.agency-form-page .agency-hero-btn { background: #fff; color: #0f766e; padding: 0.5rem 1rem; font-weight: 600; border-radius: 0.5rem; text-decoration: none; border: 1px solid rgba(255,255,255,0.5); }
.agency-form-page .agency-hero-btn:hover { background: #f0fdfa; color: #0d9488; }
.agency-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.agency-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.agency-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem; }
.agency-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
.agency-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.agency-card-body { padding: 1.25rem; }
.agency-form-card { max-width: 36rem; margin: 0 auto; }
.agency-form-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); padding: 0.75rem 1.5rem; }
.agency-form-header .agency-card-title { color: #fff; }
.agency-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.agency-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.agency-btn-primary:hover { background: #0f766e; color: #fff; }
.agency-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.agency-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.agency-form-section { display: flex; flex-direction: column; gap: 1rem; }
.agency-readonly { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; margin: 0; }
.agency-logo-row { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem; }
.agency-logo-preview { height: 3rem; width: auto; max-width: 180px; object-fit: contain; }
.agency-checkbox-label { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #374151; cursor: pointer; }
.agency-checkbox { width: 1rem; height: 1rem; }
.agency-input-file { padding: 0.5rem 0; font-size: 0.8125rem; border: 1px dashed #d1d5db; border-radius: 0.5rem; background: #fafafa; width: 100%; }
.agency-form-actions { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; }
.agency-label, .agency-input, .agency-select, .agency-field-hint, .agency-field-error { display: block; width: 100%; }
.agency-input, .agency-select { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; }
.agency-input:focus, .agency-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.agency-field-error { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
.agency-field-hint { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
</style>
@endsection
