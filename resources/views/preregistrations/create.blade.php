@extends('layouts.app')

@section('title', 'Crear Preregistro')

@section('content')
<div class="preregs-page preregs-form-page">
    <x-module-banner
        section="General"
        current="Nuevo preregistro"
        title="Nuevo preregistro"
        subtitle="Captura cuenta, ingreso, detalle del paquete y foto para ingresarlo al inventario de Miami."
        back-href="{{ route('preregistrations.index') }}"
        back-label="Volver a preregistros"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('preregistrations.quick-courier') }}" class="mb-btn mb-btn-secondary">Captura rápida Courier</a>
        </x-slot:actions>
    </x-module-banner>

    @if($errors->any())
    <div class="preregs-alert preregs-alert-danger">
        <p class="preregs-alert-title">No se pudo guardar el preregistro:</p>
        <ul class="preregs-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(isset($dropoffContinuation) && $dropoffContinuation)
    <div class="preregs-create-layout">
        <div class="preregs-create-main">
            <div class="preregs-card preregs-form-card">
                <div class="preregs-card-header preregs-form-header preregs-form-header--sheet">
                    <div class="preregs-form-header-text">
                        <h2 class="preregs-card-title">
                            <span class="preregs-card-title-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
                            </span>
                            Bulto {{ $dropoffStep }} de {{ $dropoffTotal }} (Drop Off)
                        </h2>
                        <p class="preregs-form-header-desc">Mismo código de almacén para todos. Al guardar podrás imprimir la etiqueta {{ $dropoffStep }}/{{ $dropoffTotal }}.</p>
                    </div>
                </div>
                <div class="preregs-card-body preregs-form-body">
                    @if($dropoffAgencyName)
                    <p class="preregs-dropoff-meta"><strong>Agencia:</strong> {{ $dropoffAgencyName }} · <strong>Servicio:</strong> {{ \App\Support\ServiceType::label($dropoffServiceType) }}</p>
                    @endif
                    <form action="{{ route('preregistrations.store') }}" method="POST" enctype="multipart/form-data" id="preregFormDropoffStep" class="preregs-create-formwrap">
                        @csrf
                        <input type="hidden" name="intake_type" value="DROP_OFF">
                        <input type="hidden" name="dropoff_step" value="{{ $dropoffStep }}">
                        <input type="hidden" name="bultos_count" value="{{ $dropoffTotal }}">
                        <div class="preregs-form-panel">
                            <h3 class="preregs-form-panel-title">Datos de este bulto</h3>
                            <div class="preregs-create-grid preregs-create-grid--root">
                            <div class="preregs-field">
                                <label for="dropoff_label_name" class="preregs-field-label">Nombre en etiqueta <span class="preregs-req">*</span></label>
                                <input type="text" name="label_name" id="dropoff_label_name" class="preregs-input" required>
                            </div>
                            <div class="preregs-field">
                                <label for="dropoff_intake_weight_lbs" class="preregs-field-label">Peso (lb) <span class="preregs-req">*</span></label>
                                <div class="preregs-input-affix">
                                    <input type="number" step="0.01" name="intake_weight_lbs" id="dropoff_intake_weight_lbs" class="preregs-input" required>
                                    <span class="preregs-affix">lb</span>
                                </div>
                            </div>
                            <div class="preregs-field preregs-field--full">
                                <label for="dropoff_dimension" class="preregs-field-label">Dimensión <span class="preregs-req">*</span></label>
                                <input type="text" name="dimension" id="dropoff_dimension" class="preregs-input" required placeholder="Ej: 10 x 8 x 5 in">
                            </div>
                            <div class="preregs-field preregs-field--full">
                                <label for="dropoff_description" class="preregs-field-label">Descripción <span class="preregs-opt">(opcional)</span></label>
                                <input type="text" name="description" id="dropoff_description" class="preregs-input" maxlength="500">
                            </div>
                            <div class="preregs-field preregs-field--full">
                                <label for="dropoff_photo" class="preregs-field-label">Foto del bulto <span class="preregs-req">*</span></label>
                                <input type="file" name="photo" id="dropoff_photo" class="preregs-input preregs-input--file" accept="image/jpeg,image/jpg,image/png,image/webp" required>
                                <p class="preregs-hint">JPG, PNG o WEBP. Máx. 10MB.</p>
                            </div>
                            </div>
                        </div>
                        <div class="preregs-form-actions">
                            <a href="{{ route('preregistrations.create', ['cancel_dropoff' => 1]) }}" class="preregs-btn preregs-btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                Cancelar
                            </a>
                            <button type="submit" class="preregs-btn preregs-btn-primary">Guardar e imprimir etiqueta {{ $dropoffStep }}/{{ $dropoffTotal }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <aside class="preregs-create-aside">
            <div class="preregs-side-card">
                <h3 class="preregs-side-title">
                    <span class="preregs-side-icon-wrap" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5ZM12 18.75h.008v.008H12v-.008Z"/></svg>
                    </span>
                    Información útil
                </h3>
                <div class="preregs-intake-guide">
                    <div class="preregs-intake-item">
                        <span class="preregs-intake-tag preregs-intake-tag--dropoff">Drop Off</span>
                        <p>Serie de bultos con el mismo código de almacén.</p>
                    </div>
                </div>
                <ul class="preregs-side-list">
                    <li>Guarda e imprime la etiqueta de cada bulto antes de continuar.</li>
                    <li>La foto de cada bulto es obligatoria.</li>
                </ul>
            </div>
        </aside>
    </div>
    @else
    <div class="preregs-create-layout">
        <div class="preregs-create-main">
            <div class="preregs-card preregs-form-card">
                <div class="preregs-card-header preregs-form-header preregs-form-header--sheet">
                    <div class="preregs-form-header-text">
                        <div class="preregs-form-header-row">
                            <div>
                                <h2 class="preregs-card-title">
                                    <span class="preregs-card-title-icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
                                    </span>
                                    Información del paquete
                                </h2>
                                <p class="preregs-form-header-desc">Completa los bloques en orden. La foto es obligatoria para guardar.</p>
                            </div>
                            <ol class="preregs-steps" aria-label="Pasos del formulario">
                                <li class="preregs-step is-active"><span>1</span> Origen</li>
                                <li class="preregs-step"><span>2</span> Paquete</li>
                                <li class="preregs-step"><span>3</span> Foto</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="preregs-card-body preregs-form-body">
        <form action="{{ route('preregistrations.store') }}" method="POST" enctype="multipart/form-data" class="preregs-create-formwrap" id="preregForm">
            @csrf
            <input type="hidden" name="service_type" id="service_type_post" value="AIR">

            <div class="preregs-form-panel">
                <div class="preregs-form-panel-head">
                    <span class="preregs-form-panel-num">1</span>
                    <div>
                        <h3 class="preregs-form-panel-title">Origen e ingreso</h3>
                        <p class="preregs-form-panel-sub">Cuenta destino y cómo llega el paquete a Miami.</p>
                    </div>
                </div>
                <div class="preregs-create-grid preregs-create-grid--root">
                <div class="preregs-field preregs-field--full">
                    <label for="agency_combobox" class="preregs-field-label">Cuenta (subagencia o SkyLink One) <span class="preregs-req">*</span></label>
                    @if($partnerAgencies->isEmpty())
                    <p class="preregs-inline-warn">No hay cuentas activas. <a href="{{ route('agencies.create') }}">Crear cliente</a> antes de registrar un preregistro.</p>
                    @else
                    @php
                        $sloId = $slo->id ?? null;
                        $oldAssigned = old('agency_id') ? $agencies->firstWhere('id', (int) old('agency_id')) : null;
                        $oldIsSloClient = $oldAssigned && $oldAssigned->isDirectClient();
                    @endphp
                    <div id="agency_combobox_wrap" class="preregs-combo-wrap">
                        <input type="text" id="agency_combobox" class="preregs-input" placeholder="Buscar por nombre o código…" autocomplete="off">
                        <input type="hidden" name="partner_agency_id" id="partner_agency_id" value="">
                        <input type="hidden" name="agency_id" id="agency_id" value="{{ old('agency_id') }}" required>
                        <div id="agency_dropdown" class="preregs-combo-dropdown" style="display: none;"></div>
                    </div>
                    <p class="preregs-hint">Subagencia: el paquete queda en esa cuenta. SkyLink One: después elige el cliente propio de SLO.</p>
                    @error('agency_id')
                    <p class="preregs-field-error">{{ $message }}</p>
                    @enderror
                    <script type="application/json" id="agencies-data">@json($partnerAgenciesJson)</script>
                    <script type="application/json" id="slo-clients-data">@json($sloClientsJson)</script>
                    @endif
                </div>

                <div class="preregs-field" id="slo_client_wrap" style="display: none;">
                    <label for="slo_client_id" class="preregs-field-label">Cliente de SkyLink One <span class="preregs-req">*</span></label>
                    <select id="slo_client_id" class="preregs-input preregs-select">
                        <option value="">— Seleccione el cliente SLO —</option>
                        @foreach($sloClients ?? [] as $client)
                        <option value="{{ $client->id }}" @selected((string) old('agency_id') === (string) $client->id)>{{ $client->code }} - {{ $client->name }}</option>
                        @endforeach
                    </select>
                    <p class="preregs-hint">El paquete se asigna a este cliente para control y facturación.</p>
                    @if(($sloClients ?? collect())->isEmpty())
                    <p class="preregs-inline-warn">SLO no tiene clientes propios. <a href="{{ route('agencies.create') }}">Crear cliente SLO</a>.</p>
                    @endif
                </div>

                <div class="preregs-field">
                    <label for="intake_type" class="preregs-field-label">Tipo de ingreso <span class="preregs-req">*</span></label>
                    <select name="intake_type" id="intake_type" class="preregs-input preregs-select" required>
                        <option value="COURIER">Courier</option>
                        <option value="DROP_OFF">Drop Off</option>
                    </select>
                </div>

                <div id="wrap_tracking" class="preregs-field">
                    <label for="tracking_external" class="preregs-field-label">Tracking externo</label>
                    <input type="text" name="tracking_external" id="tracking_external" class="preregs-input" placeholder="1Z999AA10123456784">
                    <p class="preregs-hint">Obligatorio en Courier.</p>
                </div>

                <div id="wrap_bultos_count" class="preregs-field" style="display: none;">
                    <label for="bultos_count" class="preregs-field-label">Cantidad de bultos <span class="preregs-req">*</span></label>
                    <input type="number" name="bultos_count" id="bultos_count" class="preregs-input preregs-input--narrow" min="1" max="20" value="1">
                    <p class="preregs-hint">Mismo warehouse; cada bulto se detalla abajo.</p>
                </div>
                </div>
            </div>

            <div class="preregs-form-panel">
                <div class="preregs-form-panel-head">
                    <span class="preregs-form-panel-num">2</span>
                    <div>
                        <h3 class="preregs-form-panel-title">Detalle del paquete</h3>
                        <p class="preregs-form-panel-sub">Datos para etiqueta, servicio y control de contenido.</p>
                    </div>
                </div>
                <div id="wrap_single_bulto" class="preregs-create-grid preregs-create-grid--nested">
                    <div class="preregs-field">
                        <label for="label_name" class="preregs-field-label">Nombre en etiqueta <span class="preregs-req">*</span></label>
                        <input type="text" name="label_name" id="label_name" class="preregs-input" placeholder="Nombre del destinatario">
                    </div>
                    <div class="preregs-field">
                        <label for="service_type" class="preregs-field-label">Tipo de servicio <span class="preregs-req">*</span></label>
                        <select id="service_type" class="preregs-input preregs-select" required>
                            <option value="AIR">Aéreo</option>
                            <option value="SEA">Marítimo</option>
                            <option value="CFT">Pie cúbico</option>
                        </select>
                    </div>
                    <div class="preregs-field">
                        <label for="intake_weight_lbs" class="preregs-field-label">Peso (lb) <span class="preregs-req">*</span></label>
                        <div class="preregs-input-affix">
                            <input type="number" step="0.01" name="intake_weight_lbs" id="intake_weight_lbs" class="preregs-input" placeholder="0.00">
                            <span class="preregs-affix">lb</span>
                        </div>
                    </div>
                    <div id="wrap_dimension" class="preregs-field" style="display: none;">
                        <label for="dimension" class="preregs-field-label">Dimensión <span class="preregs-req">*</span></label>
                        <input type="text" name="dimension" id="dimension" class="preregs-input" placeholder="Ej. 10 x 8 x 5 in">
                        <p class="preregs-cubic-line"><span class="preregs-cubic-label">Pie cúbico</span> <span id="cubic_feet_display" class="preregs-cubic-value">—</span></p>
                    </div>
                    <div class="preregs-field preregs-field--full">
                        <label for="description" class="preregs-field-label">Descripción del contenido <span class="preregs-opt">(opcional)</span></label>
                        <textarea name="description" id="description" class="preregs-input preregs-textarea" maxlength="500" rows="3" placeholder="Ej: Ropa, electrónicos, documentos…">{{ old('description') }}</textarea>
                        <p class="preregs-hint">Ayuda a identificar qué viene dentro del paquete.</p>
                    </div>
                </div>

                <div id="wrap_multi_bultos" class="preregs-multi-bultos-wrap" style="display: none;">
                    <p class="preregs-multi-lead">Se mostrará un formulario por cada bulto. Al guardar podrás imprimir la etiqueta de ese bulto y luego continuar con el siguiente.</p>
                    <div class="preregs-field preregs-field--inline">
                        <label for="service_type_multi" class="preregs-field-label">Tipo de servicio <span class="preregs-req">*</span></label>
                        <select id="service_type_multi" class="preregs-input preregs-select preregs-input--narrow">
                            <option value="AIR">Aéreo</option>
                            <option value="SEA">Marítimo</option>
                            <option value="CFT">Pie cúbico</option>
                        </select>
                    </div>
                    <div id="bultos_container" class="preregs-bultos-container"></div>
                    <input type="hidden" name="dropoff_step" id="dropoff_step_input" value="1">
                </div>
            </div>

            <div id="wrap_label_preview" class="preregs-form-section preregs-form-panel preregs-label-preview-wrap">
                <div class="preregs-form-panel-head">
                    <span class="preregs-form-panel-num">★</span>
                    <div>
                        <h3 class="preregs-form-panel-title">Vista previa de la etiqueta</h3>
                        <p class="preregs-form-panel-sub">Se actualiza al completar los campos. El código WRH se asigna al guardar.</p>
                    </div>
                </div>
                <div id="label_preview" class="preregs-label-preview-box">
                    <div class="preregs-label-preview-brand">PrimeTrack Group</div>
                    <div class="preregs-preview-label">Código de almacén</div>
                    <div id="preview_code" class="preregs-preview-code">------</div>
                    <div class="preregs-preview-label">Agencia (recepcionado para)</div>
                    <div id="preview_agency" class="preregs-preview-value">—</div>
                    <div class="preregs-preview-label">Nombre en etiqueta</div>
                    <div id="preview_name" class="preregs-preview-value">—</div>
                    <div class="preregs-preview-label">Servicio</div>
                    <div id="preview_service" class="preregs-label-preview-service preregs-label-preview-service-air">—</div>
                    <div class="preregs-preview-label">Peso (lbs)</div>
                    <div id="preview_weight" class="preregs-preview-value">—</div>
                    <div class="preregs-preview-label">Dimensión</div>
                    <div id="preview_dimension" class="preregs-preview-value">—</div>
                    <div id="preview_cubic_feet" class="preregs-preview-cubic" style="display: none;">—</div>
                    <div class="preregs-label-preview-note">
                        <div class="preregs-label-preview-note-label">Nota de recepción en almacén</div>
                        <div id="preview_reception" class="preregs-label-preview-note-text">Al registrar se asignará fecha y hora</div>
                    </div>
                </div>
            </div>

            <div id="wrap_photo_section" class="preregs-form-section preregs-form-panel preregs-photo-section">
                <div class="preregs-form-panel-head">
                    <span class="preregs-form-panel-num">3</span>
                    <div>
                        <h3 class="preregs-form-panel-title">Foto del paquete <span class="preregs-req">*</span></h3>
                        <p class="preregs-form-panel-sub">JPG, PNG o WEBP · máximo 10MB. En móvil suele abrir la cámara.</p>
                    </div>
                </div>
                <div class="preregs-photo-drop">
                    <div class="preregs-photo-drop-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                    </div>
                    <div class="preregs-field" style="margin:0;flex:1;min-width:0">
                        <label for="photo" class="preregs-field-label">Seleccionar o tomar foto</label>
                        <input type="file" name="photo" id="photo" class="preregs-input preregs-input--file" accept="image/jpeg,image/jpg,image/png,image/webp" required>
                        <p class="preregs-file-state" id="photoFileState">Ningún archivo seleccionado.</p>
                    </div>
                </div>
                <div id="photoPreview" class="preregs-photo-preview"></div>
            </div>

            <div class="preregs-form-actions preregs-form-actions--footer">
                <a href="{{ route('preregistrations.index') }}" class="preregs-btn preregs-btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    Cancelar
                </a>
                <button type="submit" class="preregs-btn preregs-btn-primary" id="submitPreregBtn" @if($agencies->isEmpty()) disabled @endif>
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 3v4H7V3m5 13a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
                    Guardar preregistro
                </button>
            </div>
        </form>
                </div>
            </div>
        </div>

        <aside class="preregs-create-aside">
            <div class="preregs-side-card">
                <h3 class="preregs-side-title">
                    <span class="preregs-side-icon-wrap" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5ZM12 18.75h.008v.008H12v-.008Z"/></svg>
                    </span>
                    Información útil
                </h3>

                <div class="preregs-intake-guide">
                    <div class="preregs-intake-item">
                        <span class="preregs-intake-tag preregs-intake-tag--courier">Courier</span>
                        <p>Paquetería entregada por agencias de logística.</p>
                    </div>
                    <div class="preregs-intake-item">
                        <span class="preregs-intake-tag preregs-intake-tag--dropoff">Drop Off</span>
                        <p>Entrega de clientes propios sin etiqueta de tracking.</p>
                    </div>
                </div>

                <p class="preregs-side-heading">Consejos</p>
                <ul class="preregs-side-list">
                    <li>Busca la cuenta por nombre o código.</li>
                    <li>En Courier el tracking externo es obligatorio.</li>
                    <li>En Drop Off puedes registrar varios bultos con el mismo warehouse.</li>
                    <li>La foto es requerida para guardar.</li>
                </ul>

                <p class="preregs-side-heading">Servicio</p>
                <p class="preregs-side-note">Aéreo, marítimo y pie cúbico tienen tarifa propia. En pie cúbico las dimensiones son obligatorias porque se cobra por pie³.</p>
            </div>
            <div class="preregs-side-card">
                <h3 class="preregs-side-title">
                    <span class="preregs-side-icon-wrap" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                    Flujo de estados
                </h3>
                <ol class="preregs-status-flow">
                    <li><span class="preregs-side-badge preregs-side-badge--received">Recibido Miami</span></li>
                    <li><span class="preregs-side-badge preregs-side-badge--transit">En tránsito</span></li>
                    <li><span class="preregs-side-badge preregs-side-badge--ready">Listo retiro</span></li>
                    <li><span class="preregs-side-badge preregs-side-badge--delivered">Entregado</span></li>
                </ol>
            </div>
        </aside>
    </div>
    @endif
</div>

<style>
.preregs-form-page {
    --pt-navy: #0A2D6F;
    --pt-blue: #1E4FA8;
    --pt-soft: #F4F8FD;
    --pt-line: #E8EEF8;
    --pt-border: #C5D4EB;
    --pt-muted: #5E6168;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}

/* Hero */
.preregs-form-page .preregs-hero {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 92% 18%, rgba(255,255,255,.14), transparent 28%),
        linear-gradient(135deg, #0A2D6F 0%, #143A8C 48%, #1E4FA8 100%);
    border-radius: 1rem;
    padding: 1.25rem 1.45rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 10px 28px rgba(10, 45, 111, 0.22);
}
.preregs-form-page .preregs-hero-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.preregs-hero-eyebrow {
    margin: 0 0 0.35rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,.72);
}
.preregs-form-page .preregs-hero-title {
    margin: 0;
    font-size: clamp(1.55rem, 2.2vw, 1.9rem);
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.03em;
    line-height: 1.15;
}
.preregs-form-page .preregs-hero-subtitle {
    margin: 0.4rem 0 0;
    font-size: 0.92rem;
    font-weight: 400;
    color: rgba(255,255,255,.86);
    max-width: 46rem;
    line-height: 1.45;
}
.preregs-form-page .preregs-hero-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; }
.preregs-form-page .preregs-hero-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.58rem 1.05rem;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    text-decoration: none;
    white-space: nowrap;
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease, color .15s ease;
}
.preregs-form-page .preregs-hero-btn:hover { transform: translateY(-1px); }
.preregs-form-page .preregs-hero-btn-primary {
    background: #fff;
    color: var(--pt-navy);
    box-shadow: 0 4px 14px rgba(0,0,0,.14);
}
.preregs-form-page .preregs-hero-btn-primary:hover { background: #E8EEF8; }
.preregs-form-page .preregs-hero-btn-secondary {
    background: rgba(255,255,255,.12);
    color: #fff;
    border-color: rgba(255,255,255,.35);
}
.preregs-form-page .preregs-hero-btn-secondary:hover {
    background: rgba(255,255,255,.2);
    border-color: #fff;
}

/* Layout */
.preregs-create-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(17rem, 21rem);
    gap: 1.25rem;
    align-items: start;
}
.preregs-create-main { min-width: 0; }
.preregs-create-aside {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    position: sticky;
    top: 1rem;
}

.preregs-alert { padding: 0.9rem 1.1rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.preregs-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.preregs-alert-title { font-weight: 700; margin-bottom: 0.3rem; }
.preregs-alert-list { margin: 0; padding-left: 1.2rem; }

/* Card */
.preregs-form-page .preregs-card {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid var(--pt-line);
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
    overflow: hidden;
    margin-bottom: 0 !important;
}
.preregs-card-header.preregs-form-header {
    padding: 0;
    border-bottom: 1px solid var(--pt-line);
    background: linear-gradient(180deg, #fff 0%, #FBFCFE 100%);
}
.preregs-form-header-text { padding: 1rem 1.15rem 0.95rem; }
.preregs-form-header-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.preregs-card-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 800;
    color: #0f172a;
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    letter-spacing: -0.02em;
}
.preregs-card-title-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 0.55rem;
    background: var(--pt-soft);
    color: var(--pt-navy);
    border: 1px solid var(--pt-border);
    flex-shrink: 0;
}
.preregs-form-header-desc {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: var(--pt-muted);
    line-height: 1.45;
    max-width: 38rem;
}
.preregs-steps {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}
.preregs-step {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.28rem 0.65rem 0.28rem 0.35rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
}
.preregs-step span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 999px;
    background: #fff;
    color: #64748b;
    border: 1px solid #e2e8f0;
    font-size: 0.68rem;
}
.preregs-step.is-active {
    background: var(--pt-soft);
    color: var(--pt-navy);
}
.preregs-step.is-active span {
    background: var(--pt-navy);
    color: #fff;
    border-color: var(--pt-navy);
}

.preregs-dropoff-meta {
    font-size: 0.875rem;
    color: var(--pt-navy);
    margin: 0 0 1.1rem;
    padding: 0.85rem 1rem;
    background: var(--pt-soft);
    border-radius: 0.65rem;
    border: 1px solid var(--pt-border);
}

.preregs-create-formwrap { margin: 0; max-width: none; width: 100%; }
.preregs-form-body { padding: 1rem 1.15rem 1.15rem; }

.preregs-form-panel {
    margin-bottom: 0.45rem;
    padding: 0.75rem 0.9rem 0.8rem;
    background: #fff;
    border: 1px solid var(--pt-line);
    border-radius: 0.75rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}
.preregs-form-panel-head {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    margin-bottom: 0.65rem;
}
.preregs-form-panel-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 0.55rem;
    background: linear-gradient(135deg, var(--pt-navy), var(--pt-blue));
    color: #fff;
    font-size: 0.78rem;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(10, 45, 111, 0.22);
}
.preregs-form-panel-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 800;
    text-transform: none;
    letter-spacing: -0.01em;
    color: #0f172a;
    display: block;
}
.preregs-form-panel-title::before { display: none; }
.preregs-form-panel-sub {
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: var(--pt-muted);
    line-height: 1.4;
}

.preregs-create-grid--root,
.preregs-create-grid--nested {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.55rem 0.85rem;
    align-items: start;
}
@media (min-width: 1180px) {
    .preregs-create-grid--root,
    .preregs-create-grid--nested { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .preregs-create-grid--root > .preregs-field:first-child,
    .preregs-field--full { grid-column: 1 / -1; }
}
.preregs-field--full { grid-column: 1 / -1; }
.preregs-field--inline { max-width: 14rem; }
.preregs-field-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.28rem;
}
.preregs-field-label-muted { font-weight: 500; color: #94a3b8; font-size: 0.74rem; }
.preregs-req { color: #D64545; font-weight: 800; }
.preregs-opt { color: #94a3b8; font-weight: 500; font-size: 0.74rem; }

.preregs-input {
    width: 100%;
    padding: 0.72rem 0.9rem;
    font-size: 0.9375rem;
    border: 1px solid #D8DCE2;
    border-radius: 0.65rem;
    background: #fff;
    color: #0f172a;
    box-sizing: border-box;
    transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}
.preregs-textarea { resize: vertical; min-height: 5.4rem; line-height: 1.45; }
.preregs-input::placeholder { color: #94a3b8; }
.preregs-input:hover { border-color: #9BB5D9; background: #fcfdff; }
.preregs-input--narrow { max-width: 9rem; }
.preregs-input-affix { position: relative; }
.preregs-input-affix .preregs-input { padding-right: 2.65rem; }
.preregs-affix {
    position: absolute;
    right: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--pt-navy);
    pointer-events: none;
}
.preregs-input--file {
    padding: 0.85rem 1rem;
    cursor: pointer;
    border-style: dashed;
    border-width: 1.5px;
    border-color: var(--pt-border);
    background: #fff;
    min-height: 3.1rem;
}
.preregs-input--file:hover { border-color: var(--pt-navy); background: var(--pt-soft); }
.preregs-input--file::file-selector-button {
    border: 1px solid var(--pt-border);
    background: var(--pt-soft);
    color: var(--pt-navy);
    border-radius: 0.5rem;
    padding: 0.4rem 0.7rem;
    margin-right: 0.65rem;
    font-weight: 700;
    cursor: pointer;
}
.preregs-input--file.has-file { border-color: var(--pt-navy); background: var(--pt-soft); }
.preregs-file-state { margin: 0.4rem 0 0; font-size: 0.78rem; color: #64748b; }
.preregs-select { cursor: pointer; appearance: auto; }
.preregs-hint { margin: 0.28rem 0 0; font-size: 0.76rem; color: #64748b; line-height: 1.35; }
.preregs-hint--block { margin-bottom: 0.75rem; }
.preregs-inline-warn {
    padding: 0.8rem 1rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.6rem;
    font-size: 0.8125rem;
    color: #92400e;
    margin: 0;
}
.preregs-inline-warn a { color: #b45309; font-weight: 700; }
.preregs-field-error { margin: 0.35rem 0 0; font-size: 0.78rem; color: #D64545; }
.preregs-combo-wrap { position: relative; }
.preregs-combo-dropdown {
    display: none;
    position: absolute;
    left: 0; right: 0; top: 100%;
    margin-top: 0.35rem;
    background: #fff;
    border: 1px solid var(--pt-border);
    border-radius: 0.7rem;
    box-shadow: 0 14px 36px rgba(10, 45, 111, 0.14);
    max-height: 250px;
    overflow-y: auto;
    z-index: 100;
}
#agency_dropdown .agency-combo-item {
    padding: 0.72rem 0.95rem;
    cursor: pointer;
    font-size: 0.9rem;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}
#agency_dropdown .agency-combo-item:last-child { border-bottom: none; }
#agency_dropdown .agency-combo-item:hover { background: var(--pt-soft); color: var(--pt-navy); }
.preregs-combo-empty { padding: 0.7rem 0.95rem; font-size: 0.875rem; color: #64748b; }
.preregs-create-grid--bulto3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.preregs-cubic-line { margin: 0.45rem 0 0; font-size: 0.8125rem; color: var(--pt-navy); }
.preregs-cubic-label { font-weight: 600; color: #64748b; margin-right: 0.35rem; }
.preregs-cubic-value { font-weight: 800; font-variant-numeric: tabular-nums; }
.preregs-multi-bultos-wrap { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--pt-border); }
.preregs-multi-lead { font-size: 0.85rem; color: #64748b; margin: 0 0 0.85rem; line-height: 1.45; }
.preregs-bultos-container { margin-top: 0.75rem; }
.preregs-bulto-block {
    padding: 1.05rem;
    margin-bottom: 0.85rem;
    border: 1px solid var(--pt-border);
    border-radius: 0.7rem;
    background: var(--pt-soft);
}
.preregs-bulto-block h4 {
    margin: 0 0 0.75rem;
    font-size: 0.8rem;
    font-weight: 800;
    color: var(--pt-navy);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.preregs-photo-drop {
    display: flex;
    align-items: flex-start;
    gap: 0.9rem;
    padding: 1rem;
    border-radius: 0.75rem;
    border: 1.5px dashed var(--pt-border);
    background: linear-gradient(180deg, #FBFCFE 0%, var(--pt-soft) 100%);
}
.preregs-photo-drop-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.7rem;
    background: #fff;
    color: var(--pt-navy);
    border: 1px solid var(--pt-border);
    flex-shrink: 0;
}
.preregs-photo-lead { font-size: 0.875rem; color: #64748b; margin: 0 0 0.95rem; }
.preregs-photo-preview { margin-top: 0.95rem; display: none; }
.preregs-photo-preview img,
.preregs-photo-preview-img {
    max-width: 22rem;
    width: 100%;
    height: auto;
    border-radius: 0.7rem;
    border: 1px solid var(--pt-border);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
}

.preregs-form-section { margin-top: 0; }
.preregs-label-preview-wrap { display: none; }
.preregs-label-preview-wrap[style*="display: block"] { display: block !important; }
.preregs-label-preview-box {
    width: 100%;
    max-width: 4.4in;
    min-height: 4in;
    background: #fff;
    border: 1px solid var(--pt-border);
    border-radius: 0.7rem;
    padding: 1rem 1.1rem;
    box-shadow: 0 8px 20px rgba(10, 45, 111, 0.08);
}
.preregs-label-preview-brand {
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--pt-navy);
    letter-spacing: 0.02em;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--pt-navy);
}
.preregs-preview-label {
    font-size: 0.68rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-top: 0.75rem;
    font-weight: 700;
}
.preregs-preview-code {
    font-size: 1.7rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-align: center;
    margin: 0.55rem 0;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    color: #111;
}
.preregs-preview-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #111;
    margin-top: 0.15rem;
}
.preregs-preview-cubic {
    font-size: 0.78rem;
    color: var(--pt-navy);
    margin-top: 0.2rem;
    font-weight: 700;
}
.preregs-label-preview-note {
    margin-top: 1rem;
    padding: 0.75rem 0.85rem;
    background: var(--pt-soft);
    border: 1px solid var(--pt-border);
    border-radius: 0.55rem;
}
.preregs-label-preview-note-label {
    font-size: 0.625rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--pt-navy);
    font-weight: 800;
    margin-bottom: 0.2rem;
}
.preregs-label-preview-note-text { font-size: 0.85rem; font-weight: 700; color: var(--pt-navy); }
.preregs-label-preview-service { margin-top: 0.2rem; font-size: 1.15rem; font-weight: 800; }
.preregs-label-preview-service-air { color: var(--pt-navy); }
.preregs-label-preview-service-sea { color: #1e40af; }

.preregs-form-actions {
    margin-top: 1.15rem;
    padding: 1rem 1.15rem;
    border: 1px solid var(--pt-line);
    border-radius: 0.85rem;
    background: linear-gradient(180deg, #fff 0%, var(--pt-soft) 100%);
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.7rem;
    align-items: center;
    position: sticky;
    bottom: 0.75rem;
    z-index: 5;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}
.preregs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.7rem 1.2rem;
    font-size: 0.9rem;
    font-weight: 750;
    border-radius: 0.65rem;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease, border-color .15s ease;
}
.preregs-btn-primary {
    background: var(--pt-navy);
    color: #fff;
    border-color: var(--pt-navy);
    box-shadow: 0 6px 16px rgba(10, 45, 111, 0.28);
}
.preregs-btn-primary:hover {
    background: var(--pt-blue);
    border-color: var(--pt-blue);
    color: #fff;
    transform: translateY(-1px);
}
.preregs-btn-primary:disabled {
    background: #9ca3af;
    border-color: #9ca3af;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}
.preregs-btn-secondary {
    background: #fff;
    color: var(--pt-navy);
    border-color: var(--pt-border);
}
.preregs-btn-secondary:hover {
    background: var(--pt-soft);
    border-color: var(--pt-navy);
}
.preregs-form-card input:focus,
.preregs-form-card select:focus,
.preregs-form-card textarea:focus {
    outline: none;
    border-color: var(--pt-blue);
    box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.16);
}

/* Aside */
.preregs-side-card {
    background: #fff;
    border: 1px solid var(--pt-line);
    border-radius: 1rem;
    padding: 1.15rem 1.15rem 1.2rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.preregs-side-title {
    margin: 0 0 0.95rem;
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.preregs-side-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 0.5rem;
    background: var(--pt-soft);
    color: var(--pt-navy);
    border: 1px solid var(--pt-border);
}
.preregs-intake-guide {
    display: grid;
    gap: 0.55rem;
    margin-bottom: 0.95rem;
}
.preregs-intake-item {
    padding: 0.7rem 0.75rem;
    border-radius: 0.65rem;
    background: var(--pt-soft);
    border: 1px solid var(--pt-line);
}
.preregs-intake-item p {
    margin: 0.35rem 0 0;
    font-size: 0.78rem;
    color: #475569;
    line-height: 1.4;
}
.preregs-intake-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.02em;
}
.preregs-intake-tag--courier { background: #dbeafe; color: #1d4ed8; }
.preregs-intake-tag--dropoff { background: #ffedd5; color: #c2410c; }
.preregs-side-heading {
    margin: 0.85rem 0 0.4rem;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--pt-blue);
}
.preregs-side-list {
    margin: 0;
    padding-left: 1.1rem;
    font-size: 0.8rem;
    color: #475569;
    line-height: 1.5;
}
.preregs-side-list li + li { margin-top: 0.28rem; }
.preregs-side-note { margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.5; }
.preregs-status-flow {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    position: relative;
}
.preregs-status-flow::before {
    content: "";
    position: absolute;
    left: 0.55rem;
    top: 0.55rem;
    bottom: 0.55rem;
    width: 2px;
    background: var(--pt-line);
}
.preregs-status-flow li {
    position: relative;
    padding-left: 1.5rem;
}
.preregs-status-flow li::before {
    content: "";
    position: absolute;
    left: 0.35rem;
    top: 0.55rem;
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 999px;
    background: var(--pt-blue);
    box-shadow: 0 0 0 3px #fff;
}
.preregs-side-badge {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 0.28rem 0.7rem;
    border-radius: 9999px;
    font-size: 0.74rem;
    font-weight: 750;
}
.preregs-side-badge--received { background: #ffedd5; color: #9a3412; }
.preregs-side-badge--transit { background: #dbeafe; color: #1d4ed8; }
.preregs-side-badge--ready { background: #dcfce7; color: #166534; }
.preregs-side-badge--delivered { background: #e5e7eb; color: #374151; }

@media (max-width: 1100px) {
    .preregs-create-layout { grid-template-columns: 1fr; }
    .preregs-create-aside {
        position: static;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
}
@media (max-width: 768px) {
    .preregs-create-grid--root,
    .preregs-create-grid--nested,
    .preregs-create-grid--bulto3 { grid-template-columns: 1fr; }
    .preregs-field--inline { max-width: none; }
    .preregs-create-aside { grid-template-columns: 1fr; }
    .preregs-form-body { padding: 1rem; }
    .preregs-form-header-text { padding: 1rem; }
    .preregs-photo-drop { flex-direction: column; }
    .preregs-form-actions { position: static; }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // — Registrar PRIMERO el envío del formulario para que siempre se intercepte (evita fallos en móvil por caché) —
    var form = document.getElementById('preregForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var serviceTypePost = document.getElementById('service_type_post');
            if (serviceTypePost) serviceTypePost.value = isMultiBultos() && document.getElementById('service_type_multi') ? document.getElementById('service_type_multi').value : (document.getElementById('service_type') ? document.getElementById('service_type').value : 'AIR');
            var formData = new FormData(form);
            var submitBtn = form.querySelector('button[type="submit"]');
            var originalText = submitBtn ? submitBtn.textContent : '';

            function sendFormData(bodyFormData) {
                if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Subiendo…'; }
                fetch(form.action, {
                    method: 'POST',
                    body: bodyFormData,
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function(res) {
                    if (res.status === 422) {
                        return res.json().then(function(data) {
                            var errs = data.errors || {};
                            var msg = (errs.photo && errs.photo[0]) || (errs.general && errs.general[0]) || (errs['photo_bulto_0'] && errs['photo_bulto_0'][0]) || data.message || 'Error de validación.';
                            var box = document.querySelector('.preregs-alert.preregs-alert-danger');
                            if (!box) {
                                box = document.createElement('div');
                                box.className = 'preregs-alert preregs-alert-danger';
                                var card = form.closest('.preregs-card');
                                if (card && card.parentNode) card.parentNode.insertBefore(box, card);
                                else form.parentNode.insertBefore(box, form);
                            }
                            box.innerHTML = '<p class="preregs-alert-title">No se pudo guardar:</p><ul class="preregs-alert-list"><li>' + msg + '</li></ul>';
                            box.scrollIntoView({ behavior: 'smooth' });
                        });
                    } else if (res.redirected && res.url) {
                        window.location.href = res.url;
                        return;
                    } else {
                        return res.text().then(function() {
                            alert('Error al guardar. Intente de nuevo.');
                        });
                    }
                }).catch(function() {
                    alert('Error de conexión. Revise la red e intente de nuevo.');
                }).finally(function() {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalText; }
                });
            }

            if (isMultiBultos()) {
                var dropoffStep = form.querySelector('input[name="dropoff_step"]');
                if (dropoffStep && parseInt(dropoffStep.value, 10) >= 1) {
                    var photoFile = formData.get('photo');
                    if (photoFile && photoFile.size > 0) {
                        compressImage(photoFile, 1280, 0.8).then(function(blobOrFile) {
                            formData.set('photo', blobOrFile, blobOrFile.name || 'photo.jpg');
                            sendFormData(formData);
                        }).catch(function() { sendFormData(formData); });
                    } else {
                        sendFormData(formData);
                    }
                    return;
                }
            }
            if (isMultiBultos()) {
                var n = getBultosCount();
                var promises = [];
                for (var i = 0; i < n; i++) {
                    var file = formData.get('photo_bulto_' + i);
                    if (file && file.size > 0) {
                        (function(idx) {
                            promises.push(compressImage(file, 1280, 0.8).then(function(f) { return { i: idx, f: f }; }).catch(function() { return { i: idx, f: file }; }));
                        })(i);
                    }
                }
                if (promises.length === 0) {
                    sendFormData(formData);
                    return;
                }
                Promise.all(promises).then(function(results) {
                    results.forEach(function(r) { formData.set('photo_bulto_' + r.i, r.f, r.f.name || 'photo.jpg'); });
                    sendFormData(formData);
                });
            } else {
                var photoFile = formData.get('photo');
                if (photoFile && photoFile.size > 0) {
                    compressImage(photoFile, 1280, 0.8).then(function(blobOrFile) {
                        formData.set('photo', blobOrFile, blobOrFile.name || 'photo.jpg');
                        sendFormData(formData);
                    }).catch(function() {
                        sendFormData(formData);
                    });
                } else {
                    sendFormData(formData);
                }
            }
        });
    }

    function compressImage(file, maxWidth, quality) {
        return new Promise(function(resolve) {
            if (!file.type || !file.type.match(/^image\/(jpeg|jpg|png|webp|heic)$/i)) {
                resolve(file);
                return;
            }
            var img = new Image();
            var url = URL.createObjectURL(file);
            img.onload = function() {
                URL.revokeObjectURL(url);
                var w = img.width, h = img.height;
                if (w <= maxWidth && h <= maxWidth && file.size < 500000) {
                    resolve(file);
                    return;
                }
                var scale = Math.min(maxWidth / w, maxWidth / h, 1);
                var cw = Math.round(w * scale), ch = Math.round(h * scale);
                var canvas = document.createElement('canvas');
                canvas.width = cw;
                canvas.height = ch;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, cw, ch);
                canvas.toBlob(function(blob) {
                    if (blob) resolve(new File([blob], 'photo.jpg', { type: 'image/jpeg' }));
                    else resolve(file);
                }, 'image/jpeg', quality);
            };
            img.onerror = function() { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    }

    var intakeType = document.getElementById('intake_type');
    var wrapDimension = document.getElementById('wrap_dimension');
    var wrapLabelPreview = document.getElementById('wrap_label_preview');
    var wrapTracking = document.getElementById('wrap_tracking');
    var wrapBultosCount = document.getElementById('wrap_bultos_count');
    var wrapSingleBulto = document.getElementById('wrap_single_bulto');
    var wrapMultiBultos = document.getElementById('wrap_multi_bultos');
    var bultosCountInput = document.getElementById('bultos_count');
    var bultosContainer = document.getElementById('bultos_container');
    var dimensionInput = document.getElementById('dimension');
    var photoInput = document.getElementById('photo');
    var wrapPhotoSection = document.getElementById('wrap_photo_section');

    function isDropOff() {
        return intakeType && intakeType.value === 'DROP_OFF';
    }

    function getBultosCount() {
        return bultosCountInput ? Math.max(1, parseInt(bultosCountInput.value, 10) || 1) : 1;
    }

    function isMultiBultos() {
        return isDropOff() && getBultosCount() > 1;
    }

    function buildBultosBlocks() {
        if (!bultosContainer) return;
        var n = getBultosCount();
        bultosContainer.innerHTML = '';
        var photoAccept = 'image/jpeg,image/jpg,image/png,image/webp';
        // Un solo bloque: Bulto 1 de N. Al guardar se imprime etiqueta 1/N y luego el usuario continúa con el siguiente.
        var div = document.createElement('div');
        div.className = 'preregs-bulto-block bulto-block bulto-block-step';
        div.setAttribute('data-index', 0);
        div.innerHTML =
            '<h4>Bulto 1 de ' + n + '</h4>' +
            '<div class="preregs-create-grid preregs-create-grid--nested preregs-create-grid--bulto3">' +
            '<div class="preregs-field"><label class="preregs-field-label">Nombre en etiqueta <span class="preregs-req">*</span></label><input type="text" name="label_name" class="preregs-input" required placeholder="Nombre del destinatario"></div>' +
            '<div class="preregs-field"><label class="preregs-field-label">Peso (lbs) <span class="preregs-req">*</span></label><input type="number" step="0.01" name="intake_weight_lbs" class="preregs-input" required placeholder="0.00"></div>' +
            '<div class="preregs-field preregs-field--full"><label class="preregs-field-label">Dimensión <span class="preregs-req">*</span> <span class="preregs-field-label-muted">(L × A × H pulg.)</span></label><input type="text" name="dimension" class="preregs-input dimension-input-multi" required placeholder="10 x 8 x 5 in"><p class="preregs-cubic-line"><span class="preregs-cubic-label">Pie cúbico</span> <span class="cubic-feet-display preregs-cubic-value">—</span></p></div>' +
            '</div>' +
            '<div class="preregs-field preregs-field--full" style="margin-top:0.75rem"><label class="preregs-field-label">Descripción <span class="preregs-opt">(opcional)</span></label><input type="text" name="description" class="preregs-input" maxlength="500" placeholder="Ej: Ropa, electrónicos…"></div>' +
            '<div class="preregs-field preregs-field--full" style="margin-top:0.75rem"><label class="preregs-field-label">Foto del bulto <span class="preregs-req">*</span></label><input type="file" name="photo" class="preregs-input preregs-input--file" accept="' + photoAccept + '" required><p class="preregs-hint">Al guardar podrá imprimir la etiqueta 1/' + n + '. Luego continúa con el siguiente. JPG, PNG o WEBP. Máx. 10MB.</p></div>';
        bultosContainer.appendChild(div);
        var dropoffStepInput = document.getElementById('dropoff_step_input');
        if (dropoffStepInput) dropoffStepInput.value = '1';
        var submitBtn = document.getElementById('submitPreregBtn');
        if (submitBtn) submitBtn.textContent = 'Guardar e imprimir etiqueta 1/' + n;
    }

    function currentService() {
        var multi = isMultiBultos() && document.getElementById('service_type_multi');
        var el = multi ? document.getElementById('service_type_multi') : document.getElementById('service_type');
        return el ? el.value : 'AIR';
    }

    function needsDimension() {
        return isDropOff() || currentService() === 'CFT';
    }

    function toggleDropOff() {
        if (!wrapDimension || !wrapLabelPreview || !wrapTracking) return;
        if (isDropOff()) {
            if (wrapBultosCount) wrapBultosCount.style.display = 'block';
            wrapDimension.style.display = 'block';
            wrapLabelPreview.style.display = 'block';
            if (dimensionInput) dimensionInput.setAttribute('required', 'required');
            var multi = isMultiBultos();
            if (wrapSingleBulto) wrapSingleBulto.style.display = multi ? 'none' : 'grid';
            if (wrapMultiBultos) wrapMultiBultos.style.display = multi ? 'block' : 'none';
            if (multi) buildBultosBlocks();
            if (photoInput) photoInput.removeAttribute('required');
            if (wrapPhotoSection) wrapPhotoSection.style.display = multi ? 'none' : 'block';
            if (typeof updatePreview === 'function') updatePreview();
        } else {
            if (wrapBultosCount) wrapBultosCount.style.display = 'none';
            wrapDimension.style.display = 'none';
            wrapLabelPreview.style.display = 'none';
            if (wrapSingleBulto) wrapSingleBulto.style.display = 'grid';
            if (wrapMultiBultos) wrapMultiBultos.style.display = 'none';
            if (dimensionInput) { dimensionInput.removeAttribute('required'); dimensionInput.value = ''; }
            if (photoInput) photoInput.setAttribute('required', 'required');
            if (wrapPhotoSection) wrapPhotoSection.style.display = 'block';
            if (needsDimension()) {
                wrapDimension.style.display = 'block';
                if (dimensionInput) dimensionInput.setAttribute('required', 'required');
            }
        }
    }

    function toggleBultosCount() {
        if (!isDropOff()) return;
        var multi = isMultiBultos();
        if (wrapSingleBulto) wrapSingleBulto.style.display = multi ? 'none' : 'grid';
        if (wrapMultiBultos) wrapMultiBultos.style.display = multi ? 'block' : 'none';
        if (multi) {
            buildBultosBlocks();
            var ln = document.getElementById('label_name'), w = document.getElementById('intake_weight_lbs'), d = document.getElementById('dimension');
            if (ln) ln.removeAttribute('required');
            if (w) w.removeAttribute('required');
            if (d) d.removeAttribute('required');
            if (wrapPhotoSection) wrapPhotoSection.style.display = 'none';
        } else {
            var ln = document.getElementById('label_name'), w = document.getElementById('intake_weight_lbs'), d = document.getElementById('dimension');
            if (ln) ln.setAttribute('required', 'required');
            if (w) w.setAttribute('required', 'required');
            if (dimensionInput) dimensionInput.setAttribute('required', 'required');
            if (photoInput) photoInput.setAttribute('required', 'required');
            if (wrapPhotoSection) wrapPhotoSection.style.display = 'block';
        }
        if (multi && photoInput) photoInput.removeAttribute('required');
        if (typeof updatePreview === 'function') updatePreview();
    }

    if (bultosCountInput) {
        bultosCountInput.addEventListener('change', toggleBultosCount);
        bultosCountInput.addEventListener('input', toggleBultosCount);
    }

    function updatePreview() {
        if (!isDropOff()) return;
        var name = (document.getElementById('label_name') && document.getElementById('label_name').value) || '—';
        var service = (document.getElementById('service_type') && document.getElementById('service_type').selectedOptions[0]) ? document.getElementById('service_type').selectedOptions[0].text : '—';
        var weight = (document.getElementById('intake_weight_lbs') && document.getElementById('intake_weight_lbs').value) ? parseFloat(document.getElementById('intake_weight_lbs').value).toFixed(2) : '—';
        var dim = (document.getElementById('dimension') && document.getElementById('dimension').value) || '—';
        var comboAgency = document.getElementById('agency_combobox');
        var agencyText = (comboAgency && comboAgency.value && comboAgency.value.trim()) ? comboAgency.value.trim() : '—';

        var previewName = document.getElementById('preview_name');
        var previewService = document.getElementById('preview_service');
        var previewWeight = document.getElementById('preview_weight');
        var previewDimension = document.getElementById('preview_dimension');
        var previewAgency = document.getElementById('preview_agency');
        var serviceSelect = document.getElementById('service_type');
        var serviceValue = (serviceSelect && serviceSelect.value) ? serviceSelect.value.toUpperCase() : 'AIR';
        if (previewName) previewName.textContent = name;
        if (previewService) {
            previewService.textContent = service;
            previewService.className = 'preregs-label-preview-service preregs-label-preview-service-' + (serviceValue === 'AIR' ? 'air' : 'sea');
        }
        if (previewWeight) previewWeight.textContent = weight;
        var cubicFeet = parseDimensionToCubicFeet(dim);
        if (previewDimension) previewDimension.textContent = dim + (cubicFeet !== null ? ' · ' + cubicFeet.toFixed(2) + ' pie³' : '');
        var previewCubic = document.getElementById('preview_cubic_feet');
        if (previewCubic) {
            if (cubicFeet !== null) {
                previewCubic.textContent = cubicFeet.toFixed(2) + ' pie³';
                previewCubic.style.display = 'block';
            } else {
                previewCubic.style.display = 'none';
            }
        }
        if (previewAgency) previewAgency.textContent = agencyText;
    }

    function parseDimensionToCubicFeet(str) {
        if (!str || typeof str !== 'string') return null;
        str = str.replace(/\s*in\.?\s*$/i, '').trim();
        var m = str.match(/\d+(?:\.\d+)?/g);
        if (!m || m.length < 3) return null;
        var l = parseFloat(m[0]), w = parseFloat(m[1]), h = parseFloat(m[2]);
        if (l <= 0 || w <= 0 || h <= 0) return null;
        return (l * w * h) / 1728;
    }

    function updateCubicFeetDisplay(inputEl, displayEl) {
        if (!displayEl) return;
        var val = inputEl && inputEl.value ? inputEl.value.trim() : '';
        var cf = parseDimensionToCubicFeet(val);
        displayEl.textContent = cf !== null ? cf.toFixed(2) + ' pie³' : '—';
    }

    (function initCubicFeet() {
        var dropoffDim = document.getElementById('dropoff_dimension');
        var dropoffDisplay = document.getElementById('dropoff_cubic_feet_display');
        if (dropoffDim && dropoffDisplay) {
            updateCubicFeetDisplay(dropoffDim, dropoffDisplay);
            dropoffDim.addEventListener('input', function() { updateCubicFeetDisplay(dropoffDim, dropoffDisplay); });
            dropoffDim.addEventListener('change', function() { updateCubicFeetDisplay(dropoffDim, dropoffDisplay); });
        }
        var dimInput = document.getElementById('dimension');
        var cfDisplay = document.getElementById('cubic_feet_display');
        if (dimInput && cfDisplay) {
            updateCubicFeetDisplay(dimInput, cfDisplay);
            dimInput.addEventListener('input', function() {
                updateCubicFeetDisplay(dimInput, cfDisplay);
                if (typeof updatePreview === 'function') updatePreview();
            });
            dimInput.addEventListener('change', function() {
                updateCubicFeetDisplay(dimInput, cfDisplay);
                if (typeof updatePreview === 'function') updatePreview();
            });
        }
        var container = document.getElementById('bultos_container');
        if (container) {
            container.addEventListener('input', function(e) {
                if (e.target && e.target.classList && e.target.classList.contains('dimension-input-multi')) {
                    var block = e.target.closest('.bulto-block');
                    var display = block ? block.querySelector('.cubic-feet-display') : null;
                    updateCubicFeetDisplay(e.target, display);
                }
            });
            container.addEventListener('change', function(e) {
                if (e.target && e.target.classList && e.target.classList.contains('dimension-input-multi')) {
                    var block = e.target.closest('.bulto-block');
                    var display = block ? block.querySelector('.cubic-feet-display') : null;
                    updateCubicFeetDisplay(e.target, display);
                }
            });
        }
    })();

    if (intakeType) {
        intakeType.addEventListener('change', toggleDropOff);
        toggleDropOff();
    }
    ['service_type', 'service_type_multi'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', toggleDropOff);
    });
    ['agency_combobox', 'label_name', 'service_type', 'intake_weight_lbs', 'dimension'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', updatePreview);
        if (el) el.addEventListener('change', updatePreview);
    });

    // Combobox agencia: un solo campo para buscar y elegir
    (function() {
        var dataEl = document.getElementById('agencies-data');
        var combo = document.getElementById('agency_combobox');
        var hidden = document.getElementById('agency_id');
        var partnerHidden = document.getElementById('partner_agency_id');
        var dropdown = document.getElementById('agency_dropdown');
        var sloWrap = document.getElementById('slo_client_wrap');
        var sloSelect = document.getElementById('slo_client_id');
        if (!combo || !hidden || !dropdown) return;
        var agencies = dataEl ? JSON.parse(dataEl.textContent || '[]') : [];
        function findAgency(id) {
            id = String(id);
            for (var i = 0; i < agencies.length; i++) {
                if (String(agencies[i].id) === id) return agencies[i];
            }
            return null;
        }
        function renderList(filter) {
            var q = (filter || '').trim().toLowerCase();
            var list = agencies.filter(function(a) {
                return !q || (a.name || '').toLowerCase().indexOf(q) !== -1 || (a.code || '').toLowerCase().indexOf(q) !== -1;
            });
            dropdown.innerHTML = list.length ? list.map(function(a) {
                var label = (a.code || '') + ' - ' + (a.name || '');
                return '<div class="agency-combo-item" data-id="' + a.id + '" data-slo="' + (a.is_slo ? '1' : '0') + '" data-label="' + label.replace(/"/g, '&quot;') + '">' + label + '</div>';
            }).join('') : '<div class="preregs-combo-empty">No hay coincidencias</div>';
            dropdown.style.display = 'block';
        }
        function selectAgency(id, label, isSlo) {
            if (partnerHidden) partnerHidden.value = id;
            combo.value = label;
            dropdown.style.display = 'none';
            if (isSlo) {
                if (sloWrap) sloWrap.style.display = '';
                var clientVal = sloSelect ? sloSelect.value : '';
                hidden.value = clientVal || '';
            } else {
                if (sloWrap) sloWrap.style.display = 'none';
                if (sloSelect) sloSelect.value = '';
                hidden.value = id;
            }
            if (typeof updatePreview === 'function') updatePreview();
        }
        combo.addEventListener('focus', function() {
            renderList(combo.value);
        });
        combo.addEventListener('input', function() {
            hidden.value = '';
            if (partnerHidden) partnerHidden.value = '';
            if (sloWrap) sloWrap.style.display = 'none';
            renderList(this.value);
        });
        combo.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { dropdown.style.display = 'none'; this.blur(); }
        });
        dropdown.addEventListener('click', function(e) {
            var item = e.target.closest('.agency-combo-item');
            if (item) selectAgency(item.getAttribute('data-id'), item.getAttribute('data-label'), item.getAttribute('data-slo') === '1');
        });
        if (sloSelect) {
            sloSelect.addEventListener('change', function() {
                hidden.value = this.value || '';
                if (typeof updatePreview === 'function') updatePreview();
            });
        }
        document.addEventListener('click', function(e) {
            if (dropdown.style.display === 'block' && !e.target.closest('#agency_combobox_wrap')) dropdown.style.display = 'none';
        });
        var initialId = hidden.value;
        if (initialId) {
            var asPartner = findAgency(initialId);
            if (asPartner) {
                selectAgency(asPartner.id, (asPartner.code || '') + ' - ' + (asPartner.name || ''), !!asPartner.is_slo);
            } else if (sloSelect) {
                var sloPartner = agencies.filter(function(a) { return a.is_slo; })[0];
                if (sloPartner) {
                    selectAgency(sloPartner.id, (sloPartner.code || '') + ' - ' + (sloPartner.name || ''), true);
                    sloSelect.value = initialId;
                    hidden.value = initialId;
                }
            }
        }
    })();

    // Foto preview
    var photoInput = document.getElementById('photo');
    var preview = document.getElementById('photoPreview');
    var photoState = document.getElementById('photoFileState');
    if (photoInput && preview) {
        photoInput.addEventListener('change', function(e) {
            preview.innerHTML = '';
            preview.style.display = 'none';
            var file = e.target.files[0];
            if (!file) {
                photoInput.classList.remove('has-file');
                if (photoState) photoState.textContent = 'Ningún archivo seleccionado.';
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                alert('La foto excede el tamaño máximo de 10MB');
                e.target.value = '';
                photoInput.classList.remove('has-file');
                if (photoState) photoState.textContent = 'Ningún archivo seleccionado.';
                return;
            }
            photoInput.classList.add('has-file');
            if (photoState) photoState.textContent = 'Archivo seleccionado: ' + file.name;
            var reader = new FileReader();
            reader.onload = function(event) {
                var img = document.createElement('img');
                img.src = event.target.result;
                img.alt = 'Vista previa';
                img.className = 'preregs-photo-preview-img';
                preview.appendChild(img);
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }

});
</script>
@endpush
@endsection
