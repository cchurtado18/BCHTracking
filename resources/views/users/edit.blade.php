@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
@php
    $isAdmin = (string) old('is_admin', $user->is_admin ? '1' : '0') === '1';
    $editingSelf = $user->id === auth()->id();
@endphp
<div class="cx-page">
    <x-module-banner
        section="Administración"
        current="Editar usuario"
        title="Editar usuario"
        subtitle="Actualice nombre, correo, contraseña o el rol de este acceso interno."
        back-href="{{ route('users.index') }}"
        back-label="Volver a usuarios"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
        </x-slot:icon>
        <x-slot:strip>
            <span class="mb-strip-label">Usuario</span>
            <span class="mb-pill"><strong>{{ $user->name }}</strong></span>
            <span class="mb-pill">{{ $user->email }}</span>
            <span class="mb-pill">{{ $user->is_admin ? 'Administrador' : 'Operaciones' }}</span>
        </x-slot:strip>
    </x-module-banner>

    @if($errors->any())
    <div class="cx-alert cx-alert-danger">
        <strong>No se pudo guardar el usuario.</strong>
        <ul class="cx-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('users.update', $user) }}" method="POST" class="cx-card" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="cx-section-head">
            <h2 class="cx-section-title">Datos de acceso</h2>
            <p class="cx-section-note">Deje la contraseña en blanco si no desea cambiarla.</p>
        </div>
        <div class="cx-card-body">
            <div class="cx-form-grid">
                <div class="cx-field">
                    <label for="name" class="cx-label">Nombre <span class="cx-req">*</span></label>
                    <input type="text" name="name" id="name" class="cx-input {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                    @error('name')<p class="cx-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="cx-field">
                    <label for="email" class="cx-label">Correo electrónico <span class="cx-req">*</span></label>
                    <input type="email" name="email" id="email" class="cx-input {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                    @error('email')<p class="cx-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="cx-field">
                    <label for="password" class="cx-label">Nueva contraseña</label>
                    <input type="password" name="password" id="password" class="cx-input {{ $errors->has('password') ? 'is-invalid' : '' }}" minlength="8" autocomplete="new-password">
                    <p class="cx-field-hint">Opcional. Mínimo 8 caracteres.</p>
                    @error('password')<p class="cx-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="cx-field">
                    <label for="password_confirmation" class="cx-label">Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="cx-input" minlength="8" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="cx-section-head">
            <h2 class="cx-section-title">Rol</h2>
            <p class="cx-section-note">Define qué ve en el panel.</p>
        </div>
        <div class="cx-card-body">
            <div class="cx-type-cards" role="radiogroup" aria-label="Rol del usuario">
                <label class="cx-type-card {{ ! $isAdmin ? 'is-selected' : '' }} {{ $editingSelf ? 'is-locked' : '' }}">
                    <input type="radio" name="is_admin" value="0" {{ ! $isAdmin ? 'checked' : '' }} {{ $editingSelf ? 'disabled' : '' }}>
                    <span class="cx-type-card-body">
                        <strong>Operaciones</strong>
                        <span>Paquetes, salidas, consolidaciones y fichaje. Sin administración ni contabilidad.</span>
                    </span>
                </label>
                <label class="cx-type-card {{ $isAdmin ? 'is-selected' : '' }}">
                    <input type="radio" name="is_admin" value="1" {{ $isAdmin ? 'checked' : '' }} {{ $editingSelf ? 'disabled' : '' }}>
                    <span class="cx-type-card-body">
                        <strong>Administrador</strong>
                        <span>Usuarios, clientes, auditoría, facturas, cobros y parámetros.</span>
                    </span>
                </label>
            </div>
            @if($editingSelf)
            <input type="hidden" name="is_admin" value="{{ $user->is_admin ? '1' : '0' }}">
            <p class="cx-lock-note">No puede quitarse el rol de administrador mientras esté en esta sesión.</p>
            @endif
        </div>

        <div class="cx-card-foot">
            <a href="{{ route('users.index') }}" class="cx-btn cx-btn-secondary">Cancelar</a>
            <button type="submit" class="cx-btn cx-btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>

@include('users.partials.form-styles')
@endsection
