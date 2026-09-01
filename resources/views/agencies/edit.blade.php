@extends('layouts.app')

@section('title', 'Editar Agencia')

@section('content')
<div class="agency-page agency-form-page">
    <x-module-banner
        section="Administración"
        current="Editar cliente"
        title="Editar cliente"
        subtitle="{{ $agency->name }} · {{ $agency->typeLabel() }}. Actualice datos de contacto, facturación y tipo de cuenta."
        back-href="{{ route('agencies.show', $agency->id) }}"
        back-label="Volver a la ficha"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
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
                        <label for="name" class="agency-label">{{ $agency->isDirectClient() ? 'Nombre del cliente' : 'Nombre de la subagencia' }} *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $agency->name) }}" required class="agency-input">
                        <p class="agency-field-hint">No puede coincidir con el nombre de otra cuenta.</p>
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
                        <input type="text" name="address" id="address" value="{{ old('address', $agency->address) }}" placeholder="{{ $agency->isDirectClient() ? 'Dirección del cliente' : 'Dirección de la subagencia' }}" class="agency-input">
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

                    <div class="agency-field" style="grid-column: 1 / -1; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                        <p class="agency-label" style="font-size: 0.95rem;">Contabilidad (crédito y datos fiscales)</p>
                    </div>
                    <div class="agency-field">
                        <label for="credit_limit_usd" class="agency-label">Crédito máximo (USD)</label>
                        <input type="number" step="0.01" min="0" name="credit_limit_usd" id="credit_limit_usd" value="{{ old('credit_limit_usd', $agency->credit_limit_usd) }}" placeholder="Sin límite" class="agency-input">
                        @error('credit_limit_usd')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label for="credit_days" class="agency-label">Días de crédito</label>
                        <input type="number" min="0" max="365" name="credit_days" id="credit_days" value="{{ old('credit_days', $agency->credit_days) }}" placeholder="Ej.: 30" class="agency-input">
                        @error('credit_days')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label for="tax_id" class="agency-label">RUC / identificación fiscal</label>
                        <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id', $agency->tax_id) }}" class="agency-input">
                        @error('tax_id')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label for="billing_contact_name" class="agency-label">Contacto de cobranza</label>
                        <input type="text" name="billing_contact_name" id="billing_contact_name" value="{{ old('billing_contact_name', $agency->billing_contact_name) }}" placeholder="Nombre" class="agency-input">
                        @error('billing_contact_name')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label for="billing_contact_phone" class="agency-label">Teléfono de cobranza</label>
                        <input type="text" name="billing_contact_phone" id="billing_contact_phone" value="{{ old('billing_contact_phone', $agency->billing_contact_phone) }}" class="agency-input">
                        @error('billing_contact_phone')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="agency-field">
                        <label for="billing_email" class="agency-label">Correo de facturación</label>
                        <input type="email" name="billing_email" id="billing_email" value="{{ old('billing_email', $agency->billing_email) }}" placeholder="facturacion@cliente.com" class="agency-input">
                        <p class="agency-field-hint">Aquí se envían las facturas. Si está vacío se usa el correo de acceso de la cuenta.</p>
                        @error('billing_email')
                        <p class="agency-field-error">{{ $message }}</p>
                        @enderror
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
    background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}
.agency-form-page .agency-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.agency-form-page .agency-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.agency-form-page .agency-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.agency-form-page .agency-hero-btn { background: #fff; color: #0A2D6F; padding: 0.5rem 1rem; font-weight: 600; border-radius: 0.5rem; text-decoration: none; border: 1px solid rgba(255,255,255,0.5); }
.agency-form-page .agency-hero-btn:hover { background: #F4F8FD; color: #0A2D6F; }
.agency-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.agency-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
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
.agency-readonly { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; margin: 0; }
.agency-logo-row { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem; }
.agency-logo-preview { height: 3rem; width: auto; max-width: 180px; object-fit: contain; }
.agency-checkbox-label { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #374151; cursor: pointer; }
.agency-checkbox { width: 1rem; height: 1rem; }
.agency-input-file { padding: 0.5rem 0; font-size: 0.8125rem; border: 1px dashed #d1d5db; border-radius: 0.5rem; background: #fafafa; width: 100%; }
.agency-form-actions { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; }
.agency-label, .agency-input, .agency-select, .agency-field-hint, .agency-field-error { display: block; width: 100%; }
.agency-input, .agency-select { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; }
.agency-input:focus, .agency-select:focus { outline: none; border-color: #0A2D6F; box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.agency-field-error { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
.agency-field-hint { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
</style>
@endsection
