@extends('layouts.app')

@section('title', 'Nueva prealerta')

@section('content')
<div class="prealerts-page prealerts-form-page">
    <x-module-banner
        section="General"
        current="Nueva prealerta"
        title="Nueva prealerta"
        subtitle="Ingrese el tracking del courier y los datos del paquete. Cuando llegue al almacén, se reconocerá por ese tracking."
        back-href="{{ route('prealerts.index') }}"
        back-label="Volver a prealertas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if($errors->any())
    <div class="prealerts-alert prealerts-alert-danger">
        <p class="prealerts-alert-title">No se pudo guardar la prealerta:</p>
        <ul class="prealerts-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="prealerts-create-layout">
        <div class="prealerts-card">
            <div class="prealerts-card-header">
                <h2 class="prealerts-card-title">
                    <span class="prealerts-card-title-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/></svg>
                    </span>
                    Datos de la prealerta
                </h2>
                <p class="prealerts-card-desc">Complete los bloques en orden. El tracking debe ser el mismo de la etiqueta del paquete.</p>
            </div>
            <div class="prealerts-card-body">
                <form action="{{ route('prealerts.store') }}" method="POST">
                    @csrf

                    <div class="prealerts-form-panel">
                        <div class="prealerts-form-panel-head">
                            <span class="prealerts-form-panel-num">1</span>
                            <div>
                                <h3 class="prealerts-form-panel-title">Destinatario</h3>
                                <p class="prealerts-form-panel-sub">Nombre y cuenta a la que pertenece el paquete.</p>
                            </div>
                        </div>
                        <div class="prealerts-form-grid">
                            <div class="prealerts-form-field--full">
                                <label for="name" class="prealerts-field-label">Nombre <span class="prealerts-req">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" class="prealerts-input prealerts-input-upper" autocapitalize="characters" required maxlength="120">
                            </div>
                            <div class="prealerts-form-field--full">
                                <label for="agency_id" class="prealerts-field-label">Agencia <span class="prealerts-req">*</span></label>
                                @if($agencies->count() === 1)
                                <input type="hidden" name="agency_id" value="{{ $agencies->first()->id }}">
                                <input type="text" class="prealerts-input" value="{{ $agencies->first()->listingAccountLabel() }} ({{ $agencies->first()->code }})" readonly>
                                @else
                                <select name="agency_id" id="agency_id" class="prealerts-select" required>
                                    <option value="">Seleccione agencia</option>
                                    @foreach($agencies as $agency)
                                    <option value="{{ $agency->id }}" @selected((string) old('agency_id') === (string) $agency->id)>
                                        {{ $agency->listingAccountLabel() }} ({{ $agency->code }})
                                    </option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="prealerts-form-panel">
                        <div class="prealerts-form-panel-head">
                            <span class="prealerts-form-panel-num">2</span>
                            <div>
                                <h3 class="prealerts-form-panel-title">Tracking y servicio</h3>
                                <p class="prealerts-form-panel-sub">El almacén usará este tracking para reconocer el paquete.</p>
                            </div>
                        </div>
                        <div class="prealerts-form-grid">
                            <div>
                                <label for="tracking" class="prealerts-field-label">Tracking <span class="prealerts-req">*</span></label>
                                <input type="text" name="tracking" id="tracking" value="{{ old('tracking') }}" class="prealerts-input prealerts-input-upper" autocapitalize="characters" autocomplete="off" spellcheck="false" required maxlength="48" placeholder="Ej: SPXMIA012462609040002615">
                                <p class="prealerts-hint">El mismo número que vendrá en la etiqueta del paquete.</p>
                            </div>
                            <div>
                                <label for="service_type" class="prealerts-field-label">Servicio <span class="prealerts-req">*</span></label>
                                <select name="service_type" id="service_type" class="prealerts-select" required>
                                    <option value="" disabled {{ old('service_type') ? '' : 'selected' }}>Seleccione aéreo o marítimo</option>
                                    <option value="AIR" @selected(old('service_type') === 'AIR')>Aéreo</option>
                                    <option value="SEA" @selected(old('service_type') === 'SEA')>Marítimo</option>
                                </select>
                            </div>
                            <div class="prealerts-form-field--full">
                                <label for="description" class="prealerts-field-label">Descripción <span class="prealerts-opt">(opcional)</span></label>
                                <textarea name="description" id="description" class="prealerts-input prealerts-textarea prealerts-input-upper" rows="3" maxlength="500" autocapitalize="characters">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="prealerts-form-actions">
                        <a href="{{ route('prealerts.index') }}" class="prealerts-btn prealerts-btn-secondary">Cancelar</a>
                        <button type="submit" class="prealerts-btn prealerts-btn-primary">Guardar prealerta</button>
                    </div>
                </form>
            </div>
        </div>

        <aside class="prealerts-aside">
            <div class="prealerts-side-card">
                <h3 class="prealerts-side-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5ZM12 18.75h.008v.008H12v-.008Z"/></svg>
                    Información útil
                </h3>
                <ul class="prealerts-side-list">
                    <li>Use el tracking completo del courier, sin espacios.</li>
                    <li>El servicio solo puede ser aéreo o marítimo.</li>
                    <li>Cuando el paquete llegue a Miami, el almacén verá esta prealerta al escanear el tracking.</li>
                    <li>Cada tracking solo se puede prealertar una vez.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>

@include('prealerts.partials.styles')
@endsection
