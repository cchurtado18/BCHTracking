@extends('layouts.app')

@section('title', $accessUser ? 'Editar acceso' : 'Crear acceso')

@section('content')
@php
    $isEdit = (bool) $accessUser;
@endphp
<div class="agency-page agency-form-page">
    <x-module-banner
        section="Administración"
        current="Acceso"
        title="{{ $isEdit ? 'Editar acceso del cliente' : 'Crear acceso del cliente' }}"
        subtitle="{{ $agency->name }} · {{ $agency->typeLabel() }}. Este acceso solo ve sus paquetes y facturas. No es personal interno."
        back-href="{{ route('agencies.show', $agency) }}"
        back-label="Volver a la ficha"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

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
            <h2 class="agency-card-title">Datos de acceso</h2>
        </div>
        <div class="agency-card-body">
            <form action="{{ $isEdit ? route('agencies.users.update', [$agency, $accessUser]) : route('agencies.users.store', $agency) }}" method="POST" autocomplete="off">
                @csrf
                @if($isEdit)
                @method('PUT')
                @endif

                <div class="agency-form-section">
                    <div class="agency-field">
                        <label for="name" class="agency-label">Nombre *</label>
                        <input type="text" name="name" id="name" required maxlength="255" class="agency-input"
                               value="{{ old('name', $accessUser->name ?? $agency->name) }}" autocomplete="name">
                        @error('name')<p class="agency-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="agency-field">
                        <label for="email" class="agency-label">Correo de acceso *</label>
                        <input type="email" name="email" id="email" required maxlength="255" class="agency-input"
                               value="{{ old('email', $accessUser->email ?? '') }}" autocomplete="email">
                        <p class="agency-field-hint">Con este correo entra al panel de cliente. No se le asignan permisos de la empresa.</p>
                        @error('email')<p class="agency-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="agency-field">
                        <label for="password" class="agency-label">{{ $isEdit ? 'Nueva contraseña' : 'Contraseña *' }}</label>
                        <input type="password" name="password" id="password" class="agency-input" minlength="8"
                               @unless($isEdit) required @endunless autocomplete="new-password">
                        <p class="agency-field-hint">{{ $isEdit ? 'Déjela en blanco si no desea cambiarla. Mínimo 8 caracteres.' : 'Mínimo 8 caracteres.' }}</p>
                        @error('password')<p class="agency-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="agency-field">
                        <label for="password_confirmation" class="agency-label">{{ $isEdit ? 'Confirmar nueva contraseña' : 'Confirmar contraseña *' }}</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="agency-input" minlength="8"
                               @unless($isEdit) required @endunless autocomplete="new-password">
                    </div>
                </div>

                <div class="agency-form-actions">
                    <a href="{{ route('agencies.show', $agency) }}" class="agency-btn agency-btn-secondary">Cancelar</a>
                    <button type="submit" class="agency-btn agency-btn-primary">{{ $isEdit ? 'Guardar acceso' : 'Crear acceso' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.agency-form-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.agency-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.agency-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.agency-alert-list { margin: 0; padding-left: 1.15rem; }
.agency-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem; }
.agency-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
.agency-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.agency-card-body { padding: 1.25rem; }
.agency-form-card { max-width: 36rem; margin: 0 auto; }
.agency-form-header { background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%); padding: 0.75rem 1.5rem; }
.agency-form-header .agency-card-title { color: #fff; }
.agency-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.agency-btn-primary { background: #0A2D6F; color: #fff; border-color: #0A2D6F; }
.agency-btn-primary:hover { background: #0A2D6F; color: #fff; }
.agency-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.agency-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.agency-form-section { display: flex; flex-direction: column; gap: 1rem; }
.agency-form-actions { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; }
.agency-label, .agency-input, .agency-field-hint, .agency-field-error { display: block; width: 100%; }
.agency-input { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; }
.agency-input:focus { outline: none; border-color: #0A2D6F; box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.agency-field-error { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
.agency-field-hint { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
</style>
@endsection
