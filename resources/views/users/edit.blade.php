@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
<div class="users-page users-form-page">
    <header class="users-hero">
        <div class="users-hero-inner">
            <div class="users-hero-text">
                <h1 class="users-hero-title">Editar usuario</h1>
                <p class="users-hero-subtitle">{{ $user->name }}</p>
            </div>
            <a href="{{ route('users.index') }}" class="users-hero-btn">← Volver a usuarios</a>
        </div>
    </header>

    @if($errors->any())
    <div class="users-alert users-alert-danger">
        <ul class="users-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="users-card users-form-card">
        <div class="users-card-header users-form-header">
            <h2 class="users-card-title">Datos del usuario</h2>
        </div>
        <div class="users-card-body">
            <form action="{{ route('users.update', $user) }}" method="POST" class="users-form">
                @csrf
                @method('PUT')
                <div class="users-field">
                    <label for="name" class="users-label">Nombre <span class="users-required">*</span></label>
                    <input type="text" name="name" id="name" class="users-input {{ $errors->has('name') ? 'users-input-invalid' : '' }}" value="{{ old('name', $user->name) }}" required maxlength="255">
                    @error('name')<p class="users-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="users-field">
                    <label for="email" class="users-label">Correo electrónico <span class="users-required">*</span></label>
                    <input type="email" name="email" id="email" class="users-input {{ $errors->has('email') ? 'users-input-invalid' : '' }}" value="{{ old('email', $user->email) }}" required maxlength="255">
                    @error('email')<p class="users-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="users-field">
                    <label for="password" class="users-label">Nueva contraseña</label>
                    <input type="password" name="password" id="password" class="users-input {{ $errors->has('password') ? 'users-input-invalid' : '' }}">
                    @error('password')<p class="users-field-error">{{ $message }}</p>@enderror
                    <p class="users-field-hint">Dejar en blanco para no cambiar. Mínimo 8 caracteres.</p>
                </div>
                <div class="users-field">
                    <label for="password_confirmation" class="users-label">Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="users-input">
                </div>
                <div class="users-field">
                    <label class="users-checkbox-label">
                        <input type="hidden" name="is_admin" value="0">
                        <input type="checkbox" name="is_admin" id="is_admin" value="1" class="users-checkbox" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                        <span>Administrador (puede crear y editar usuarios)</span>
                    </label>
                    @if($user->id === auth()->id())
                    <p class="users-field-warning">No puedes quitar tu propio rol de administrador mientras estés logueado.</p>
                    @endif
                </div>
                <div class="users-form-actions">
                    <button type="submit" class="users-btn users-btn-primary">Guardar cambios</button>
                    <a href="{{ route('users.index') }}" class="users-btn users-btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.users-form-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.users-form-page .users-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.users-form-page .users-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.users-form-page .users-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.users-form-page .users-hero-btn { background: #fff; color: #0f766e; padding: 0.5rem 1rem; font-weight: 600; border-radius: 0.5rem; text-decoration: none; border: 1px solid rgba(255,255,255,0.5); }
.users-form-page .users-hero-btn:hover { background: #f0fdfa; color: #0d9488; }
.users-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.users-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.users-alert-list { margin: 0; padding-left: 1.25rem; }
.users-form-card { max-width: 36rem; margin: 0 auto; }
.users-form-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.users-form-header .users-card-title { color: #fff; }
.users-form .users-field { margin-bottom: 1.25rem; }
.users-label { display: block; font-size: 0.8125rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.users-required { color: #dc2626; }
.users-input { display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827; }
.users-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.users-input-invalid { border-color: #dc2626; }
.users-field-error { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
.users-field-hint { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
.users-field-warning { font-size: 0.8125rem; color: #92400e; margin-top: 0.5rem; background: #fffbeb; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #fcd34d; }
.users-checkbox-label { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #374151; cursor: pointer; }
.users-checkbox { width: 1.25rem; height: 1.25rem; }
.users-form-actions { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; gap: 0.75rem; }
</style>
@endsection
