@extends('layouts.app')

@section('title', 'Nuevo cliente')

@section('content')
@php
    $oldType = old('account_type', '');
    $oldScope = old('subagency_scope', '');
    $hasErrors = $errors->any();
    $errorStep = 1;
    if ($errors->has('user_password') || $errors->has('user_name')) {
        $errorStep = 4;
    } elseif ($errors->has('user_email') || $errors->has('name') || $errors->has('phone') || $errors->has('address') || $errors->has('logo')) {
        $errorStep = 3;
    } elseif ($errors->has('parent_agency_id') || $errors->has('subagency_scope')) {
        $errorStep = 2;
    }
@endphp
<div class="cx-page">
    <x-module-banner
        section="Administración"
        current="Nuevo cliente"
        title="Nuevo cliente"
        subtitle="Elija el tipo de cuenta, complete los datos y cree el acceso al panel."
        back-href="{{ route('agencies.index') }}"
        back-label="Volver a clientes"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if($errors->any())
    <div class="cx-alert cx-alert-danger">
        <strong>No se pudo guardar la cuenta.</strong>
        <ul class="cx-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <ol class="cx-steps" aria-label="Pasos">
        <li class="cx-step is-active" data-step-dot="1"><span>1</span> Tipo</li>
        <li class="cx-step" data-step-dot="2"><span>2</span> Afiliación</li>
        <li class="cx-step" data-step-dot="3"><span>3</span> Datos</li>
        <li class="cx-step" data-step-dot="4"><span>4</span> Acceso</li>
    </ol>

    <form action="{{ route('agencies.store') }}" method="POST" enctype="multipart/form-data" id="client-wizard" class="cx-wizard">
        @csrf

        <div class="cx-card cx-step-panel" data-step="1">
            <div class="cx-section-head">
                <h2 class="cx-section-title">¿Qué cuenta va a crear?</h2>
            </div>
            <div class="cx-card-body">
                <p class="cx-hint">Primero elija si es un cliente propio de SkyLink One o una subagencia.</p>
                <div class="cx-type-cards" role="radiogroup" aria-label="Tipo de cuenta">
                    <label class="cx-type-card">
                        <input type="radio" name="account_type" value="direct_client" {{ $oldType === 'direct_client' ? 'checked' : '' }}>
                        <span class="cx-type-card-body">
                            <strong>Cliente</strong>
                            <span>Persona o empresa de SkyLink One. Acceso al panel, sin red propia.</span>
                        </span>
                    </label>
                    <label class="cx-type-card">
                        <input type="radio" name="account_type" value="subagency" {{ $oldType === 'subagency' ? 'checked' : '' }}>
                        <span class="cx-type-card-body">
                            <strong>Subagencia</strong>
                            <span>Partner comercial. Puede colgar de SLO o de otra subagencia.</span>
                        </span>
                    </label>
                </div>
                @error('account_type')
                <p class="cx-field-error">{{ $message }}</p>
                @enderror
                <div class="cx-step-actions">
                    <a href="{{ route('agencies.index') }}" class="cx-btn cx-btn-secondary">Cancelar</a>
                    <button type="button" class="cx-btn cx-btn-primary" data-next>Continuar</button>
                </div>
            </div>
        </div>

        <div class="cx-card cx-step-panel" data-step="2" hidden>
            <div class="cx-section-head">
                <h2 class="cx-section-title">¿De quién es esta subagencia?</h2>
            </div>
            <div class="cx-card-body">
                <p class="cx-hint">Si es nueva de SkyLink One queda bajo SLO. Si pertenece a otra red, elija esa subagencia.</p>
                <div class="cx-type-cards" role="radiogroup" aria-label="Afiliación de la subagencia">
                    <label class="cx-type-card">
                        <input type="radio" name="subagency_scope" value="slo" {{ $oldScope === 'slo' ? 'checked' : '' }}>
                        <span class="cx-type-card-body">
                            <strong>Nueva subagencia de SLO</strong>
                            <span>Queda directamente bajo SkyLink One.</span>
                        </span>
                    </label>
                    <label class="cx-type-card">
                        <input type="radio" name="subagency_scope" value="nested" {{ $oldScope === 'nested' ? 'checked' : '' }}>
                        <span class="cx-type-card-body">
                            <strong>Subagencia de otra subagencia</strong>
                            <span>Se cuelga de un partner que ya existe (por ejemplo CH Logistics).</span>
                        </span>
                    </label>
                </div>

                <div class="cx-field" id="parent_field_wrap" style="margin-top: 1rem;" hidden>
                    <label for="parent_agency_id" class="cx-label">Subagencia padre *</label>
                    <select name="parent_agency_id" id="parent_agency_id" class="cx-input">
                        <option value="">— Seleccionar subagencia —</option>
                        @foreach($subagencyParents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_agency_id') === (string) $parent->id)>
                            {{ $parent->code ? $parent->code.' · ' : '' }}{{ $parent->name }}
                        </option>
                        @endforeach
                    </select>
                    <p class="cx-field-hint">No puede colgar de un cliente propio de SLO.</p>
                    @error('parent_agency_id')
                    <p class="cx-field-error">{{ $message }}</p>
                    @enderror
                </div>
                <p class="cx-slo-note" id="slo_parent_note" hidden>
                    Queda bajo <strong>SkyLink One</strong>.
                </p>
                @if(!empty($slo))
                <input type="hidden" name="parent_agency_id" id="parent_agency_id_slo" value="{{ $slo->id }}" disabled>
                @endif
                <div class="cx-step-actions">
                    <button type="button" class="cx-btn cx-btn-secondary" data-prev>Atrás</button>
                    <button type="button" class="cx-btn cx-btn-primary" data-next>Continuar</button>
                </div>
            </div>
        </div>

        <div class="cx-card cx-step-panel" data-step="3" hidden>
            <div class="cx-section-head">
                <h2 class="cx-section-title">Datos de la cuenta</h2>
            </div>
            <div class="cx-card-body">
                <p class="cx-hint" data-copy-sub="Nombre comercial de la subagencia. El código se asigna al guardar." data-copy-client="Nombre de la persona o empresa. El código se asigna al guardar.">Nombre comercial de la subagencia. El código se asigna al guardar.</p>
                <div class="cx-form-grid">
                    <div class="cx-field cx-field-wide">
                        <label for="name" class="cx-label" data-copy-sub="Nombre de la subagencia *" data-copy-client="Nombre del cliente *">Nombre de la subagencia *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               data-ph-sub="Ej. Agencia Norte" data-ph-client="Ej. Juan Pérez o Empresa S.A."
                               placeholder="Ej. Agencia Norte" class="cx-input">
                        @error('name')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field">
                        <label for="phone" class="cx-label">Teléfono</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="Ej. 8888-8888" class="cx-input">
                        @error('phone')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field">
                        <label for="user_email" class="cx-label">Correo electrónico *</label>
                        <input type="email" name="user_email" id="user_email" value="{{ old('user_email') }}" required placeholder="correo@ejemplo.com" class="cx-input" autocomplete="off">
                        <p class="cx-field-hint">Este correo también será el usuario de acceso al panel.</p>
                        @error('user_email')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field">
                        <label for="department" class="cx-label">Departamento</label>
                        <select name="department" id="department" class="cx-input">
                            <option value="">— Seleccionar —</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept }}" @selected(old('department') === $dept)>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cx-field cx-field-wide">
                        <label for="address" class="cx-label">Dirección</label>
                        <input type="text" name="address" id="address" value="{{ old('address') }}"
                               data-ph-sub="Dirección de la subagencia" data-ph-client="Dirección del cliente"
                               placeholder="Dirección de la subagencia" class="cx-input">
                    </div>
                    <div class="cx-field cx-field-wide" data-only="subagency">
                        <label for="logo" class="cx-label">Logo (opcional)</label>
                        <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="cx-input">
                        <p class="cx-field-hint">PNG, JPEG, GIF o WebP. Máx. 2 MB.</p>
                    </div>
                </div>
                <div class="cx-step-actions">
                    <button type="button" class="cx-btn cx-btn-secondary" data-prev>Atrás</button>
                    <button type="button" class="cx-btn cx-btn-primary" data-next>Continuar</button>
                </div>
            </div>
        </div>

        <div class="cx-card cx-step-panel" data-step="4" hidden>
            <div class="cx-section-head">
                <h2 class="cx-section-title">Acceso al sistema</h2>
            </div>
            <div class="cx-card-body">
                <p class="cx-hint">Cree el usuario con el que esta cuenta entra al panel.</p>
                <div class="cx-form-grid">
                    <div class="cx-field cx-field-wide">
                        <label for="user_name" class="cx-label">Nombre de usuario</label>
                        <input type="text" name="user_name" id="user_name" value="{{ old('user_name') }}" placeholder="Opcional. Si se omite se usa el nombre de la cuenta" class="cx-input">
                    </div>
                    <div class="cx-field">
                        <label for="user_password" class="cx-label">Contraseña *</label>
                        <input type="password" name="user_password" id="user_password" required minlength="8" placeholder="Mínimo 8 caracteres" class="cx-input" autocomplete="new-password">
                        @error('user_password')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field">
                        <label for="user_password_confirmation" class="cx-label">Confirmar contraseña *</label>
                        <input type="password" name="user_password_confirmation" id="user_password_confirmation" required minlength="8" placeholder="Repetir contraseña" class="cx-input" autocomplete="new-password">
                    </div>
                </div>
                <p class="cx-field-hint" style="margin-top:0.85rem">El correo de acceso es el que indicó en el paso anterior.</p>
                <div class="cx-step-actions">
                    <button type="button" class="cx-btn cx-btn-secondary" data-prev>Atrás</button>
                    <button type="submit" class="cx-btn cx-btn-primary" id="submit_btn">Crear cuenta</button>
                </div>
            </div>
        </div>
    </form>
</div>

@include('agencies.partials.cx-form-styles')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('client-wizard');
    var panels = Array.prototype.slice.call(document.querySelectorAll('.cx-step-panel'));
    var dots = Array.prototype.slice.call(document.querySelectorAll('[data-step-dot]'));
    var parentWrap = document.getElementById('parent_field_wrap');
    var parentSelect = document.getElementById('parent_agency_id');
    var parentSlo = document.getElementById('parent_agency_id_slo');
    var sloNote = document.getElementById('slo_parent_note');
    var hero = document.querySelector('.mb-subtitle');
    var current = {{ $hasErrors ? 1 : 1 }};

    function selectedType() {
        var checked = document.querySelector('input[name="account_type"]:checked');
        return checked ? checked.value : '';
    }
    function selectedScope() {
        var checked = document.querySelector('input[name="subagency_scope"]:checked');
        return checked ? checked.value : '';
    }
    function isClient() { return selectedType() === 'direct_client'; }

    function syncAffiliation() {
        var client = isClient();
        var scope = selectedScope();
        var useSlo = client || scope === 'slo';
        var useNested = !client && scope === 'nested';

        if (parentWrap) parentWrap.hidden = !useNested;
        if (sloNote) sloNote.hidden = !useSlo || client;
        if (parentSelect) {
            parentSelect.disabled = !useNested;
            parentSelect.required = useNested;
        }
        if (parentSlo) parentSlo.disabled = !useSlo;
    }

    function syncCopy() {
        var client = isClient();
        document.querySelectorAll('[data-only]').forEach(function (el) {
            var show = el.getAttribute('data-only') === selectedType();
            el.hidden = !show;
            el.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !show;
            });
        });
        document.querySelectorAll('[data-copy-sub]').forEach(function (el) {
            el.textContent = client ? el.getAttribute('data-copy-client') : el.getAttribute('data-copy-sub');
        });
        document.querySelectorAll('[data-ph-sub]').forEach(function (el) {
            el.placeholder = client ? el.getAttribute('data-ph-client') : el.getAttribute('data-ph-sub');
        });
        document.querySelectorAll('.cx-type-card').forEach(function (card) {
            var radio = card.querySelector('input');
            card.classList.toggle('is-selected', !!(radio && radio.checked));
        });
        if (hero) {
            hero.textContent = client
                ? 'Cliente de SkyLink One: datos de contacto y acceso al panel.'
                : 'Subagencia: a quién pertenece, datos comerciales y acceso al panel.';
        }
        syncAffiliation();
    }

    function showStep(n) {
        current = n;
        panels.forEach(function (panel) {
            panel.hidden = parseInt(panel.getAttribute('data-step'), 10) !== n;
        });
        dots.forEach(function (dot) {
            var step = parseInt(dot.getAttribute('data-step-dot'), 10);
            dot.classList.toggle('is-active', step === n);
            dot.classList.toggle('is-done', step < n);
        });
    }

    function nextStepFrom(n) {
        if (n === 1) return isClient() ? 3 : 2;
        if (n === 2) return 3;
        return Math.min(4, n + 1);
    }
    function prevStepFrom(n) {
        if (n === 3) return isClient() ? 1 : 2;
        return Math.max(1, n - 1);
    }

    function validateCurrent() {
        if (current === 1 && !selectedType()) {
            alert('Seleccione si es cliente o subagencia.');
            return false;
        }
        if (current === 2) {
            if (!selectedScope()) {
                alert('Indique si la subagencia es de SLO o de otra subagencia.');
                return false;
            }
            if (selectedScope() === 'nested' && parentSelect && !parentSelect.value) {
                alert('Seleccione la subagencia padre.');
                return false;
            }
        }
        if (current === 3) {
            var name = document.getElementById('name');
            var email = document.getElementById('user_email');
            if (name && !name.value.trim()) {
                alert('El nombre es obligatorio.');
                return false;
            }
            if (email && !email.value.trim()) {
                alert('El correo electrónico es obligatorio.');
                return false;
            }
        }
        return true;
    }

    document.querySelectorAll('input[name="account_type"], input[name="subagency_scope"]').forEach(function (input) {
        input.addEventListener('change', syncCopy);
    });
    form.querySelectorAll('[data-next]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!validateCurrent()) return;
            showStep(nextStepFrom(current));
        });
    });
    form.querySelectorAll('[data-prev]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showStep(prevStepFrom(current));
        });
    });
    form.addEventListener('submit', function () {
        syncAffiliation();
    });

    syncCopy();
    @if($hasErrors)
    showStep({{ (int) $errorStep }});
    @endif
});
</script>
@endsection
