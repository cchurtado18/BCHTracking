@extends('layouts.app')

@section('title', 'Crear usuario')

@section('content')
@php
    $isAdmin = (string) old('is_admin', '0') === '1';
@endphp
<div class="cx-page">
    <x-module-banner
        section="Administración"
        current="Crear usuario"
        title="Crear usuario"
        subtitle="Alta de personal interno de PrimeTrack. Los accesos de clientes se crean desde la ficha de cada cuenta."
        back-href="{{ route('users.index') }}"
        back-label="Volver a usuarios"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.011a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if($errors->any())
    <div class="cx-alert cx-alert-danger">
        <strong>No se pudo crear el usuario.</strong>
        <ul class="cx-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('users.store') }}" method="POST" class="cx-card" autocomplete="off">
        @csrf

        <div class="cx-section-head">
            <h2 class="cx-section-title">Datos de acceso</h2>
            <p class="cx-section-note">Nombre, correo y contraseña con los que iniciará sesión.</p>
        </div>
        <div class="cx-card-body">
            <div class="cx-form-grid">
                <div class="cx-field">
                    <label for="name" class="cx-label">Nombre <span class="cx-req">*</span></label>
                    <input type="text" name="name" id="name" class="cx-input {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                    @error('name')<p class="cx-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="cx-field">
                    <label for="email" class="cx-label">Correo electrónico <span class="cx-req">*</span></label>
                    <input type="email" name="email" id="email" class="cx-input {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                    @error('email')<p class="cx-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="cx-field">
                    <label for="password" class="cx-label">Contraseña <span class="cx-req">*</span></label>
                    <input type="password" name="password" id="password" class="cx-input {{ $errors->has('password') ? 'is-invalid' : '' }}" required minlength="8" autocomplete="new-password">
                    <p class="cx-field-hint">Mínimo 8 caracteres.</p>
                    @error('password')<p class="cx-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="cx-field">
                    <label for="password_confirmation" class="cx-label">Confirmar contraseña <span class="cx-req">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="cx-input" required minlength="8" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="cx-section-head">
            <h2 class="cx-section-title">Rol</h2>
            <p class="cx-section-note">Define qué ve en el panel. El acceso de un cliente se crea desde Clientes.</p>
        </div>
        <div class="cx-card-body">
            <div class="cx-type-cards" role="radiogroup" aria-label="Rol del usuario">
                <label class="cx-type-card {{ ! $isAdmin ? 'is-selected' : '' }}">
                    <input type="radio" name="is_admin" value="0" {{ ! $isAdmin ? 'checked' : '' }}>
                    <span class="cx-type-card-body">
                        <strong>Operaciones</strong>
                        <span>Paquetes, salidas, consolidaciones y fichaje. Sin administración ni contabilidad.</span>
                    </span>
                </label>
                <label class="cx-type-card {{ $isAdmin ? 'is-selected' : '' }}">
                    <input type="radio" name="is_admin" value="1" {{ $isAdmin ? 'checked' : '' }}>
                    <span class="cx-type-card-body">
                        <strong>Administrador</strong>
                        <span>Usuarios, clientes, auditoría, facturas, cobros y parámetros.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="cx-card-foot">
            <a href="{{ route('users.index') }}" class="cx-btn cx-btn-secondary">Cancelar</a>
            <button type="submit" class="cx-btn cx-btn-primary">Crear usuario</button>
        </div>
    </form>
</div>

@include('users.partials.form-styles')
@endsection
