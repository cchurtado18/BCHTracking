@extends('layouts.app')

@section('title', $agency->isDirectClient() ? 'Editar cliente' : 'Editar subagencia')

@section('content')
@php
    $canReparent = (bool) ($canReparent ?? false);
    $isClient = $agency->isDirectClient();
    $currentScope = $currentScope ?? 'slo';
    $panelDatos = $canReparent ? 2 : 1;
    $panelConta = $canReparent ? 3 : 2;
@endphp
<div class="cx-page cx-page--wide">
    <x-module-banner
        section="Administración"
        current="Editar cliente"
        title="{{ $isClient ? 'Editar cliente' : 'Editar subagencia' }}"
        subtitle="{{ $agency->name }} · {{ $agency->typeLabel() }}. Actualice afiliación, datos de contacto y facturación."
        back-href="{{ route('agencies.show', $agency->id) }}"
        back-label="Volver a la ficha"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
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

    <ol class="cx-steps" aria-label="Secciones">
        @if($canReparent)
        <li class="cx-step is-active"><span>1</span> Afiliación</li>
        @endif
        <li class="cx-step is-active"><span>{{ $panelDatos }}</span> Datos</li>
        <li class="cx-step is-active"><span>{{ $panelConta }}</span> Contabilidad</li>
    </ol>

    <form action="{{ route('agencies.update', $agency->id) }}" method="POST" enctype="multipart/form-data" id="agency-edit-form" class="cx-stack">
        @csrf
        @method('PUT')

        @if($canReparent)
        <div class="cx-card">
            <div class="cx-section-head">
                <span class="cx-panel-num">1</span>
                <div>
                    <h2 class="cx-section-title">¿De quién es esta subagencia?</h2>
                    <p class="cx-section-sub">Si pertenece a CH u otra red, cámbielo aquí. El portal de una hija de subagencia solo ve paquetes.</p>
                </div>
            </div>
            <div class="cx-card-body">
                <div class="cx-type-cards" role="radiogroup" aria-label="Afiliación de la subagencia">
                    <label class="cx-type-card {{ $currentScope === 'slo' ? 'is-selected' : '' }}">
                        <input type="radio" name="subagency_scope" value="slo" {{ $currentScope === 'slo' ? 'checked' : '' }}>
                        <span class="cx-type-card-body">
                            <strong>Hija de SkyLink One</strong>
                            <span>Partner propio de SLO. Ve paquetes, entregas y facturas.</span>
                        </span>
                    </label>
                    <label class="cx-type-card {{ $currentScope === 'nested' ? 'is-selected' : '' }}">
                        <input type="radio" name="subagency_scope" value="nested" {{ $currentScope === 'nested' ? 'checked' : '' }}>
                        <span class="cx-type-card-body">
                            <strong>Hija de otra subagencia</strong>
                            <span>Se cuelga de un partner que ya existe (por ejemplo CH Logistics).</span>
                        </span>
                    </label>
                </div>
                @error('subagency_scope')
                <p class="cx-field-error">{{ $message }}</p>
                @enderror

                <div class="cx-form-grid cx-form-grid--pair" id="parent_field_wrap" style="margin-top: 1rem;" @if($currentScope !== 'nested') hidden @endif>
                    <div class="cx-field">
                        <label for="parent_agency_filter" class="cx-label">Buscar padre</label>
                        <input type="search" id="parent_agency_filter" class="cx-input" autocomplete="off"
                               placeholder="Nombre o código…" @if($currentScope !== 'nested') disabled @endif>
                    </div>
                    <div class="cx-field">
                        <label for="parent_agency_id" class="cx-label">Subagencia padre *</label>
                        <select name="parent_agency_id" id="parent_agency_id" class="cx-input" @if($currentScope !== 'nested') disabled @endif>
                            <option value="">— Seleccionar subagencia —</option>
                            @foreach($subagencyParents as $parent)
                            <option value="{{ $parent->id }}"
                                    data-search="{{ strtolower(trim(($parent->code ? $parent->code.' ' : '').$parent->name)) }}"
                                    @selected((string) old('parent_agency_id', $agency->parent_agency_id) === (string) $parent->id)>
                                {{ $parent->code ? $parent->code.' · ' : '' }}{{ $parent->name }}
                            </option>
                            @endforeach
                        </select>
                        <p class="cx-field-hint">No puede colgar de un cliente propio de SLO ni de una de sus hijas.</p>
                        @error('parent_agency_id')
                        <p class="cx-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="cx-slo-note" id="slo_parent_note" @if($currentScope !== 'slo') hidden @endif>
                    Queda bajo <strong>SkyLink One</strong>.
                </p>
                @if(!empty($slo))
                <input type="hidden" name="parent_agency_id" id="parent_agency_id_slo" value="{{ $slo->id }}" @if($currentScope !== 'slo') disabled @endif>
                @endif
            </div>
        </div>
        @endif

        <div class="cx-card">
            <div class="cx-section-head">
                <span class="cx-panel-num">{{ $panelDatos }}</span>
                <div>
                    <h2 class="cx-section-title">{{ $isClient ? 'Datos del cliente' : 'Datos de la subagencia' }}</h2>
                    <p class="cx-section-sub">Nombre comercial, contacto y estado de la cuenta.</p>
                </div>
            </div>
            <div class="cx-card-body">
                <div class="cx-form-grid">
                    <div class="cx-field">
                        <label class="cx-label">Código</label>
                        <p class="cx-code">{{ $agency->code }}</p>
                        <p class="cx-field-hint">Asignado por el sistema; no se puede modificar.</p>
                    </div>
                    <div class="cx-field">
                        <label class="cx-check" style="margin-top: 1.55rem;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $agency->is_active) ? 'checked' : '' }}>
                            <span>Cuenta activa</span>
                        </label>
                    </div>
                    <div class="cx-field cx-field-wide">
                        <label for="name" class="cx-label">{{ $isClient ? 'Nombre del cliente' : 'Nombre de la subagencia' }} *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $agency->name) }}" required class="cx-input"
                               placeholder="{{ $isClient ? 'Ej. Juan Pérez o Empresa S.A.' : 'Ej. Agencia Norte' }}">
                        <p class="cx-field-hint">No puede coincidir con el nombre de otra cuenta.</p>
                        @error('name')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field">
                        <label for="phone" class="cx-label">Teléfono</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $agency->phone) }}" class="cx-input" placeholder="Ej. 8888-8888">
                        @error('phone')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field">
                        <label for="department" class="cx-label">Departamento</label>
                        <select name="department" id="department" class="cx-input">
                            <option value="">— Seleccionar —</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept }}" @selected(old('department', $agency->department) === $dept)>{{ $dept }}</option>
                            @endforeach
                        </select>
                        @error('department')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field cx-field-wide">
                        <label for="address" class="cx-label">Dirección</label>
                        <input type="text" name="address" id="address" value="{{ old('address', $agency->address) }}" class="cx-input"
                               placeholder="{{ $isClient ? 'Dirección del cliente' : 'Dirección de la subagencia' }}">
                        @error('address')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    @unless($isClient)
                    <div class="cx-field cx-field-wide">
                        <label for="logo" class="cx-label">Logo (opcional)</label>
                        @if($agency->logo_url)
                        <div class="cx-logo-row">
                            <img src="{{ $agency->logo_url }}" alt="Logo actual" class="cx-logo-preview">
                            <label class="cx-check">
                                <input type="checkbox" name="remove_logo" value="1">
                                <span>Quitar logo</span>
                            </label>
                        </div>
                        @endif
                        <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="cx-input">
                        <p class="cx-field-hint">Se muestra en la etiqueta. PNG con fondo transparente. Máx. 2 MB.</p>
                        @error('logo')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    @endunless
                </div>
            </div>
        </div>

        <div class="cx-card">
            <div class="cx-section-head">
                <span class="cx-panel-num">{{ $panelConta }}</span>
                <div>
                    <h2 class="cx-section-title">Contabilidad</h2>
                    <p class="cx-section-sub">Crédito, datos fiscales y correo donde se envían las facturas.</p>
                </div>
            </div>
            <div class="cx-card-body">
                <div class="cx-form-grid">
                    <div class="cx-field">
                        <label for="credit_limit_usd" class="cx-label">Crédito máximo (USD)</label>
                        <input type="number" step="0.01" min="0" name="credit_limit_usd" id="credit_limit_usd" class="cx-input"
                               value="{{ old('credit_limit_usd', $agency->credit_limit_usd) }}" placeholder="Sin límite">
                        @error('credit_limit_usd')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field">
                        <label for="credit_days" class="cx-label">Días de crédito</label>
                        <input type="number" min="0" max="365" name="credit_days" id="credit_days" class="cx-input"
                               value="{{ old('credit_days', $agency->credit_days) }}" placeholder="Ej. 30">
                        @error('credit_days')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field cx-field-wide">
                        <label for="tax_id" class="cx-label">RUC / identificación fiscal</label>
                        <input type="text" name="tax_id" id="tax_id" class="cx-input" value="{{ old('tax_id', $agency->tax_id) }}">
                        @error('tax_id')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field">
                        <label for="billing_contact_name" class="cx-label">Contacto de cobranza</label>
                        <input type="text" name="billing_contact_name" id="billing_contact_name" class="cx-input"
                               value="{{ old('billing_contact_name', $agency->billing_contact_name) }}" placeholder="Nombre">
                        @error('billing_contact_name')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field">
                        <label for="billing_contact_phone" class="cx-label">Teléfono de cobranza</label>
                        <input type="text" name="billing_contact_phone" id="billing_contact_phone" class="cx-input"
                               value="{{ old('billing_contact_phone', $agency->billing_contact_phone) }}">
                        @error('billing_contact_phone')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cx-field cx-field-wide">
                        <label for="billing_email" class="cx-label">Correo de facturación</label>
                        <input type="email" name="billing_email" id="billing_email" class="cx-input"
                               value="{{ old('billing_email', $agency->billing_email) }}" placeholder="facturacion@cliente.com">
                        <p class="cx-field-hint">Aquí se envían las facturas. Si está vacío se usa el correo de acceso de la cuenta.</p>
                        @error('billing_email')<p class="cx-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="cx-step-actions">
            <a href="{{ route('agencies.show', $agency->id) }}" class="cx-btn cx-btn-secondary">Cancelar</a>
            <button type="submit" class="cx-btn cx-btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>

@include('agencies.partials.cx-form-styles')
@if($canReparent)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var parentWrap = document.getElementById('parent_field_wrap');
    var parentSelect = document.getElementById('parent_agency_id');
    var parentSlo = document.getElementById('parent_agency_id_slo');
    var sloNote = document.getElementById('slo_parent_note');
    var filter = document.getElementById('parent_agency_filter');
    var cards = document.querySelectorAll('#agency-edit-form .cx-type-card');

    function scope() {
        var checked = document.querySelector('input[name="subagency_scope"]:checked');
        return checked ? checked.value : 'slo';
    }

    function syncCards() {
        cards.forEach(function (card) {
            var radio = card.querySelector('input');
            card.classList.toggle('is-selected', !!(radio && radio.checked));
        });
    }

    function sync() {
        var nested = scope() === 'nested';
        if (parentWrap) parentWrap.hidden = !nested;
        if (sloNote) sloNote.hidden = nested;
        if (parentSelect) {
            parentSelect.disabled = !nested;
            parentSelect.required = nested;
        }
        if (filter) filter.disabled = !nested;
        if (parentSlo) parentSlo.disabled = nested;
        syncCards();
    }

    function applyFilter() {
        if (!parentSelect || !filter) return;
        var q = (filter.value || '').toLowerCase().trim();
        Array.prototype.forEach.call(parentSelect.options, function (opt, i) {
            if (i === 0) {
                opt.hidden = false;
                return;
            }
            var hay = opt.getAttribute('data-search') || (opt.textContent || '').toLowerCase();
            opt.hidden = q !== '' && hay.indexOf(q) === -1;
        });
    }

    document.querySelectorAll('input[name="subagency_scope"]').forEach(function (input) {
        input.addEventListener('change', sync);
    });
    if (filter) {
        filter.addEventListener('input', applyFilter);
    }
    sync();
});
</script>
@endif
@endsection
