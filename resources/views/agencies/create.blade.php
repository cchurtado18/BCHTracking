@extends('layouts.app')

@section('title', 'Crear Agencia')

@section('content')
<div class="agency-page agency-form-page">
    <header class="agency-hero">
        <div class="agency-hero-inner">
            <div class="agency-hero-text">
                <h1 class="agency-hero-title">Crear Agencia</h1>
                <p class="agency-hero-subtitle">Nueva agencia B2B. Subagencia de SkyLink One o CH LOGISTICS.</p>
            </div>
            <a href="{{ route('agencies.index') }}" class="agency-hero-btn">← Volver</a>
        </div>
    </header>

    @if($errors->any())
    <div class="agency-alert agency-alert-danger">
        <p class="agency-alert-title">No se pudo guardar la agencia</p>
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
            <form action="{{ route('agencies.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="agency-form-section">
                    <span class="agency-step-badge">Paso 1</span>
                    <p class="agency-form-hint">Indique a cuál agencia principal pertenece esta subagencia.</p>
                    <div class="agency-field">
                        <label for="parent_agency_id" class="agency-label">Pertenece a *</label>
                        <select name="parent_agency_id" id="parent_agency_id" required class="agency-select">
                            <option value="">— Seleccionar SkyLink One o CH LOGISTICS —</option>
                            @foreach($mainAgencies as $main)
                            <option value="{{ $main->id }}" {{ (string) old('parent_agency_id') === (string) $main->id ? 'selected' : '' }}>{{ $main->name }}</option>
                            @endforeach
                        </select>
                        <p class="agency-field-hint">La subagencia quedará asociada a esta agencia principal.</p>
                        @error('parent_agency_id')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <hr class="agency-divider">

                <div class="agency-form-section">
                    <span class="agency-step-badge">Paso 2</span>
                    <p class="agency-form-hint">El código se asignará automáticamente al guardar.</p>
                    <div class="agency-fields-grid">
                        <div class="agency-field agency-field-full">
                            <label for="name" class="agency-label">Nombre *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Nombre de la subagencia" class="agency-input">
                            <p class="agency-field-hint">No puede coincidir con el nombre de otra subagencia ya registrada.</p>
                            @error('name')
                            <p class="agency-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="agency-field">
                            <label for="phone" class="agency-label">Teléfono</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="Ej. 8888-8888" class="agency-input">
                            @error('phone')
                            <p class="agency-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="agency-field">
                            <label for="department" class="agency-label">Departamento (Nicaragua)</label>
                            <select name="department" id="department" class="agency-select">
                                <option value="">— Seleccionar —</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ old('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                @endforeach
                            </select>
                            @error('department')
                            <p class="agency-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="agency-field agency-field-full">
                            <label for="address" class="agency-label">Dirección</label>
                            <input type="text" name="address" id="address" value="{{ old('address') }}" placeholder="Dirección de la subagencia" class="agency-input">
                            @error('address')
                            <p class="agency-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="agency-field agency-field-full">
                            <label for="logo" class="agency-label">Logo (opcional)</label>
                            <p class="agency-field-hint">Se muestra en la etiqueta sin fondo. Mejor resultado: PNG con fondo transparente. JPEG, PNG, GIF o WebP, máx. 2 MB.</p>
                            <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="agency-input-file">
                            @error('logo')
                            <p class="agency-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr class="agency-divider">

                <div class="agency-form-section">
                    <span class="agency-step-badge">Paso 3</span>
                    <p class="agency-form-hint">Cree el usuario y contraseña con el que la agencia iniciará sesión para ver sus paquetes.</p>
                    <div class="agency-fields-grid">
                        <div class="agency-field agency-field-full">
                            <label for="user_name" class="agency-label">Nombre del usuario</label>
                            <input type="text" name="user_name" id="user_name" value="{{ old('user_name') }}" placeholder="Ej. Juan Pérez o nombre de la agencia" class="agency-input">
                            <p class="agency-field-hint">Opcional. Si se deja vacío se usará el nombre de la agencia.</p>
                            @error('user_name')
                            <p class="agency-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="agency-field agency-field-full">
                            <label for="user_email" class="agency-label">Correo (usuario para iniciar sesión) *</label>
                            <input type="email" name="user_email" id="user_email" value="{{ old('user_email') }}" required placeholder="correo@ejemplo.com" class="agency-input">
                            @error('user_email')
                            <p class="agency-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="agency-field">
                            <label for="user_password" class="agency-label">Contraseña *</label>
                            <input type="password" name="user_password" id="user_password" required minlength="8" placeholder="Mínimo 8 caracteres" class="agency-input">
                            @error('user_password')
                            <p class="agency-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="agency-field">
                            <label for="user_password_confirmation" class="agency-label">Confirmar contraseña *</label>
                            <input type="password" name="user_password_confirmation" id="user_password_confirmation" required minlength="8" placeholder="Repetir contraseña" class="agency-input">
                        </div>
                    </div>
                </div>

                <div class="agency-form-actions">
                    <a href="{{ route('agencies.index') }}" class="agency-btn agency-btn-secondary">Cancelar</a>
                    <button type="submit" class="agency-btn agency-btn-primary">Crear Agencia</button>
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
.agency-alert-title { font-weight: 600; margin-bottom: 0.5rem; }
.agency-alert-list { margin: 0; padding-left: 1.25rem; }
.agency-alert-list li { margin-bottom: 0.25rem; }
.agency-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem; }
.agency-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
.agency-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.agency-card-body { padding: 1.25rem; }
.agency-form-card { max-width: 80rem; width: 100%; margin: 0 auto; }
.agency-form-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); padding: 0.75rem 1.5rem; }
.agency-form-header .agency-card-title { color: #fff; }
.agency-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.agency-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.agency-btn-primary:hover { background: #0f766e; color: #fff; }
.agency-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.agency-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.agency-form-section { margin-bottom: 1.5rem; }
.agency-step-badge {
    display: inline-block; background: rgba(13, 148, 136, 0.15); color: #0f766e; font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.05em; padding: 0.25rem 0.5rem; border-radius: 0.25rem; margin-bottom: 0.5rem;
}
.agency-form-hint { font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem; }
.agency-divider { border: 0; height: 1px; background: linear-gradient(90deg, transparent, #e5e7eb, transparent); margin: 1.5rem 0; }
.agency-fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 1.25rem; }
.agency-field-full { grid-column: 1 / -1; }
.agency-field { min-width: 0; }
.agency-label { display: block; font-size: 0.8125rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.agency-input, .agency-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
    background: #fff; color: #111827;
}
.agency-input:focus, .agency-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.agency-input-file { padding: 0.5rem 0; font-size: 0.8125rem; border: 1px dashed #d1d5db; border-radius: 0.5rem; background: #fafafa; width: 100%; }
.agency-field-hint { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
.agency-field-error { font-size: 0.875rem; color: #dc2626; margin-top: 0.25rem; }
.agency-form-actions { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; }
</style>
@endsection
