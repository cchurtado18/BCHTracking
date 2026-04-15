@extends('layouts.app')

@section('title', 'Crear Preregistro')

@section('content')
<div class="preregs-page preregs-form-page">
    <header class="preregs-hero">
        <div class="preregs-hero-inner">
            <div class="preregs-hero-text">
                <h1 class="preregs-hero-title">Crear Preregistro</h1>
                <p class="preregs-hero-subtitle">Registra un nuevo paquete en Miami</p>
            </div>
            <a href="{{ route('preregistrations.index') }}" class="preregs-hero-btn">← Volver a preregistros</a>
        </div>
    </header>

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
    {{-- Formulario para el siguiente bulto (2 de N, 3 de N, …) — mismo WRH, imprimir etiqueta y continuar --}}
    <div class="preregs-card preregs-form-card">
        <div class="preregs-card-header preregs-form-header preregs-form-header--sheet">
            <div class="preregs-form-header-text">
                <h2 class="preregs-card-title">Bulto {{ $dropoffStep }} de {{ $dropoffTotal }} (Drop Off)</h2>
                <p class="preregs-form-header-desc">Mismo código de almacén para todos. Al guardar podrás imprimir la etiqueta {{ $dropoffStep }}/{{ $dropoffTotal }}.</p>
            </div>
        </div>
        <div class="preregs-card-body preregs-form-body">
            @if($dropoffAgencyName)
            <p class="preregs-dropoff-meta"><strong>Agencia:</strong> {{ $dropoffAgencyName }} · <strong>Servicio:</strong> {{ $dropoffServiceType === 'SEA' ? 'Marítimo' : 'Aéreo' }}</p>
            @endif
            <form action="{{ route('preregistrations.store') }}" method="POST" enctype="multipart/form-data" id="preregFormDropoffStep" class="preregs-create-formwrap">
                @csrf
                <input type="hidden" name="intake_type" value="DROP_OFF">
                <input type="hidden" name="dropoff_step" value="{{ $dropoffStep }}">
                <input type="hidden" name="bultos_count" value="{{ $dropoffTotal }}">
                <div class="preregs-form-panel">
                    <h3 class="preregs-form-panel-title"><span class="preregs-panel-icon" aria-hidden="true">📦</span> Datos de este bulto</h3>
                    <div class="preregs-create-grid preregs-create-grid--root">
                    <div class="preregs-field">
                        <label for="dropoff_label_name" class="preregs-field-label">Nombre en etiqueta <span class="preregs-req">*</span></label>
                        <input type="text" name="label_name" id="dropoff_label_name" class="preregs-input" required>
                    </div>
                    <div class="preregs-field">
                        <label for="dropoff_intake_weight_lbs" class="preregs-field-label">Peso (lbs) <span class="preregs-req">*</span></label>
                        <input type="number" step="0.01" name="intake_weight_lbs" id="dropoff_intake_weight_lbs" class="preregs-input" required>
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
                    <a href="{{ route('preregistrations.create', ['cancel_dropoff' => 1]) }}" class="preregs-btn preregs-btn-secondary">Cancelar (abandonar esta serie de bultos)</a>
                    <button type="submit" class="preregs-btn preregs-btn-primary">Guardar e imprimir etiqueta {{ $dropoffStep }}/{{ $dropoffTotal }}</button>
                </div>
            </form>
        </div>
    </div>
    @else
    <div class="preregs-card preregs-form-card">
        <div class="preregs-card-header preregs-form-header preregs-form-header--sheet">
            <div class="preregs-form-header-text">
                <h2 class="preregs-card-title">Datos del preregistro</h2>
                <p class="preregs-form-header-desc">Complete los campos; la foto es obligatoria antes de crear el registro.</p>
            </div>
        </div>
        <div class="preregs-card-body preregs-form-body">
        <form action="{{ route('preregistrations.store') }}" method="POST" enctype="multipart/form-data" class="preregs-create-formwrap" id="preregForm">
            @csrf
            <input type="hidden" name="service_type" id="service_type_post" value="AIR">

            <div class="preregs-form-panel">
                <h3 class="preregs-form-panel-title"><span class="preregs-panel-icon" aria-hidden="true">🏢</span> Origen e ingreso</h3>
                <div class="preregs-create-grid preregs-create-grid--root">
                <div class="preregs-field">
                    <label for="agency_combobox" class="preregs-field-label">Agencia (subagencia con la que envía el cliente) <span class="preregs-req">*</span></label>
                    @if($agencies->isEmpty())
                    <p class="preregs-inline-warn">No hay agencias activas. <a href="{{ route('agencies.create') }}">Crear agencia</a> antes de registrar un preregistro.</p>
                    @else
                    <div id="agency_combobox_wrap" class="preregs-combo-wrap">
                        <input type="text" id="agency_combobox" class="preregs-input" placeholder="Buscar y elegir agencia (nombre o código)..." autocomplete="off">
                        <input type="hidden" name="agency_id" id="agency_id" value="" required>
                        <div id="agency_dropdown" class="preregs-combo-dropdown" style="display: none;"></div>
                    </div>
                    <p class="preregs-hint">Escriba para buscar y haga clic en la agencia. La etiqueta mostrará esta agencia.</p>
                    <script type="application/json" id="agencies-data">@json($agencies->isEmpty() ? [] : $agencies->map(function($a) { return ['id' => $a->id, 'code' => $a->code, 'name' => $a->name]; })->values())</script>
                    @endif
                </div>

                <div class="preregs-field">
                    <label for="intake_type" class="preregs-field-label">Tipo de ingreso <span class="preregs-req">*</span></label>
                    <select name="intake_type" id="intake_type" class="preregs-input preregs-select" required>
                        <option value="COURIER">Courier</option>
                        <option value="DROP_OFF">Drop Off</option>
                    </select>
                </div>

                <div id="wrap_tracking" class="preregs-field preregs-field--full">
                    <label for="tracking_external" class="preregs-field-label">Tracking externo</label>
                    <input type="text" name="tracking_external" id="tracking_external" class="preregs-input" placeholder="1Z999AA10123456784">
                    <p class="preregs-hint">Requerido si es Courier.</p>
                </div>

                <div id="wrap_bultos_count" class="preregs-field preregs-field--full" style="display: none;">
                    <label for="bultos_count" class="preregs-field-label">Cantidad de bultos <span class="preregs-req">*</span></label>
                    <input type="number" name="bultos_count" id="bultos_count" class="preregs-input preregs-input--narrow" min="1" max="20" value="1">
                    <p class="preregs-hint">Mismo warehouse para todos; cada bulto lleva su detalle abajo.</p>
                </div>
                </div>
            </div>

            <div class="preregs-form-panel">
                <h3 class="preregs-form-panel-title"><span class="preregs-panel-icon" aria-hidden="true">📦</span> Detalle del paquete</h3>
                <div id="wrap_single_bulto" class="preregs-create-grid preregs-create-grid--nested">
                    <div class="preregs-field">
                        <label for="label_name" class="preregs-field-label">Nombre en etiqueta <span class="preregs-req">*</span></label>
                        <input type="text" name="label_name" id="label_name" class="preregs-input">
                    </div>
                    <div class="preregs-field">
                        <label for="service_type" class="preregs-field-label">Tipo de servicio <span class="preregs-req">*</span></label>
                        <select id="service_type" class="preregs-input preregs-select" required>
                            <option value="AIR">Aéreo</option>
                            <option value="SEA">Marítimo</option>
                        </select>
                    </div>
                    <div class="preregs-field">
                        <label for="intake_weight_lbs" class="preregs-field-label">Peso (lbs) <span class="preregs-req">*</span></label>
                        <input type="number" step="0.01" name="intake_weight_lbs" id="intake_weight_lbs" class="preregs-input">
                    </div>
                    <div id="wrap_dimension" class="preregs-field" style="display: none;">
                        <label for="dimension" class="preregs-field-label">Dimensión <span class="preregs-req">*</span> <span class="preregs-field-label-muted">(L × A × H en pulgadas, ej. 10 × 8 × 5 in)</span></label>
                        <input type="text" name="dimension" id="dimension" class="preregs-input" placeholder="Ej. 10 x 8 x 5 in">
                        <p class="preregs-cubic-line"><span class="preregs-cubic-label">Pie cúbico</span> <span id="cubic_feet_display" class="preregs-cubic-value">—</span></p>
                    </div>
                    <div class="preregs-field preregs-field--full">
                        <label for="description" class="preregs-field-label">Descripción del contenido <span class="preregs-opt">(opcional)</span></label>
                        <input type="text" name="description" id="description" class="preregs-input" maxlength="500" placeholder="Ej: Ropa, electrónicos, documentos…">
                        <p class="preregs-hint">Control de lo que viene dentro del paquete.</p>
                    </div>
                </div>

                <div id="wrap_multi_bultos" class="preregs-multi-bultos-wrap" style="display: none;">
                    <p class="preregs-multi-lead">Se mostrará un formulario por cada bulto. Al guardar podrás imprimir la etiqueta de ese bulto y luego continuar con el siguiente.</p>
                    <div class="preregs-field preregs-field--inline">
                        <label for="service_type_multi" class="preregs-field-label">Tipo de servicio <span class="preregs-req">*</span></label>
                        <select id="service_type_multi" class="preregs-input preregs-select preregs-input--narrow">
                            <option value="AIR">Aéreo</option>
                            <option value="SEA">Marítimo</option>
                        </select>
                    </div>
                    <div id="bultos_container" class="preregs-bultos-container"></div>
                    <input type="hidden" name="dropoff_step" id="dropoff_step_input" value="1">
                </div>
            </div>

            <!-- Preview de etiqueta (solo Drop Off) -->
            <div id="wrap_label_preview" class="preregs-form-section preregs-form-panel preregs-label-preview-wrap">
                <h3 class="preregs-form-panel-title"><span class="preregs-panel-icon" aria-hidden="true">🏷️</span> Vista previa de la etiqueta</h3>
                <p class="preregs-hint preregs-hint--block">Así se verá la etiqueta que se imprimirá al guardar. El código de almacén se asignará al crear el preregistro.</p>
                <div id="label_preview" class="preregs-label-preview-box">
                    <div class="preregs-label-preview-brand">BCH Tracking</div>
                    <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 8px;">Código de almacén</div>
                    <div id="preview_code" style="font-size: 28px; font-weight: 800; letter-spacing: 0.15em; text-align: center; margin: 12px 0; font-family: monospace; color: #111;">------</div>
                    <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 12px;">Agencia (recepcionado para)</div>
                    <div id="preview_agency" style="font-size: 15px; font-weight: 600; color: #111; margin-top: 2px;">—</div>
                    <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 12px;">Nombre en etiqueta</div>
                    <div id="preview_name" style="font-size: 15px; font-weight: 600; color: #111; margin-top: 2px;">—</div>
                    <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 12px;">Servicio</div>
                    <div id="preview_service" class="preregs-label-preview-service preregs-label-preview-service-air">—</div>
                    <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 12px;">Peso (lbs)</div>
                    <div id="preview_weight" style="font-size: 15px; font-weight: 600; color: #111; margin-top: 2px;">—</div>
                    <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 12px;">Dimensión</div>
                    <div id="preview_dimension" style="font-size: 15px; font-weight: 600; color: #111; margin-top: 2px;">—</div>
                    <div id="preview_cubic_feet" style="font-size: 12px; color: #0d9488; margin-top: 2px; display: none;">—</div>
                    <div class="preregs-label-preview-note">
                        <div class="preregs-label-preview-note-label">Nota de recepción en almacén</div>
                        <div id="preview_reception" class="preregs-label-preview-note-text">Al registrar se asignará fecha y hora</div>
                    </div>
                </div>
            </div>

            <!-- Sección de Fotos (un solo bulto o Courier; si hay varios bultos, cada uno lleva su foto en el bloque) -->
            <div id="wrap_photo_section" class="preregs-form-section preregs-form-panel preregs-photo-section">
                <h3 class="preregs-form-panel-title"><span class="preregs-panel-icon" aria-hidden="true">⬆️</span> Foto del paquete <span class="preregs-req">*</span></h3>
                <p class="preregs-photo-lead">La foto es obligatoria (máximo 10MB). Formatos: JPG, PNG o WEBP.</p>
                <div class="preregs-field">
                    <label for="photo" class="preregs-field-label">Seleccionar foto</label>
                    <input type="file" name="photo" id="photo" class="preregs-input preregs-input--file" accept="image/jpeg,image/jpg,image/png,image/webp" required>
                    <p class="preregs-file-state" id="photoFileState">Ningún archivo seleccionado.</p>
                    <p class="preregs-hint">En celular suele abrirse la cámara. Formatos: JPG, PNG, WEBP.</p>
                </div>

                <div id="photoPreview" class="preregs-photo-preview"></div>
            </div>

            <div class="preregs-form-actions preregs-form-actions--footer">
                <a href="{{ route('preregistrations.index') }}" class="preregs-btn preregs-btn-secondary">Cancelar</a>
                <button type="submit" class="preregs-btn preregs-btn-primary" id="submitPreregBtn" @if($agencies->isEmpty()) disabled @endif>
                    Crear Preregistro con Foto
                </button>
            </div>
        </form>
        </div>
    </div>
    @endif
</div>

<style>
.preregs-form-page {
    padding: 1.5rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
    background: linear-gradient(180deg, #f7f9fc 0%, #f8fafc 100%);
    border-radius: 1rem;
}
.preregs-form-page .preregs-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0f766e 50%, #128176 100%);
    border-radius: 0.9rem;
    padding: 1.2rem 1.4rem;
    margin: 0 1rem 1.5rem;
    box-shadow: 0 8px 22px rgba(15, 118, 110, 0.18);
}
.preregs-form-page .preregs-hero-title { color: #fff; margin: 0; font-size: 1.72rem; font-weight: 600; letter-spacing: -0.02em; }
.preregs-form-page .preregs-hero-subtitle { color: rgba(236, 253, 245, 0.95); margin: 0.3rem 0 0; font-size: 0.9rem; font-weight: 400; }
.preregs-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.preregs-hero-btn { display: inline-flex; align-items: center; padding: 0.56rem 1rem; font-size: 0.875rem; font-weight: 500; background: rgba(255, 255, 255, 0.95); color: #0f766e; border: 1px solid rgba(255,255,255,0.7); border-radius: 0.625rem; text-decoration: none; transition: all 0.2s ease; }
.preregs-hero-btn:hover { background: #ffffff; color: #0d9488; transform: translateY(-1px); box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12); }
.preregs-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.preregs-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.preregs-alert-title { font-weight: 600; margin-bottom: 0.35rem; }
.preregs-alert-list { margin: 0; padding-left: 1.25rem; }
.preregs-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06); overflow: hidden; margin: 0 1rem 1.6rem; }
.preregs-card-header.preregs-form-header { padding: 0; border-bottom: 1px solid #e2e8f0; background: #fafbfc; }
.preregs-form-header--sheet { background: #fff !important; }
.preregs-form-header-text { padding: 1.2rem 1.6rem; }
.preregs-form-header--sheet .preregs-card-title { color: #0f172a; margin: 0; font-size: 1.25rem; font-weight: 600; letter-spacing: -0.02em; }
.preregs-form-header-desc { margin: 0.35rem 0 0; font-size: 0.84rem; color: #64748b; line-height: 1.45; max-width: 48rem; font-weight: 400; }
.preregs-form-header--sheet .preregs-form-header-desc { margin-top: 0.25rem; }
.preregs-dropoff-meta { font-size: 0.875rem; color: #475569; margin: 0 0 1rem; padding: 0.75rem 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0; }
.preregs-create-formwrap {
    margin: 0 auto;
    max-width: 48rem;
    width: 100%;
    box-sizing: border-box;
}
.preregs-form-panel { margin-bottom: 1.4rem; padding: 1.25rem 1.3rem; background: #ffffff; border: 1px solid #e6edf5; border-radius: 0.75rem; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04); }
.preregs-form-panel-title { margin: 0 0 1rem; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.09em; color: #64748b; display: inline-flex; align-items: center; gap: 0.4rem; }
.preregs-panel-icon { font-size: 0.78rem; opacity: 0.8; }
.preregs-create-grid--root { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; align-items: start; }
.preregs-create-grid--nested { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; align-items: start; }
.preregs-field--full { grid-column: 1 / -1; }
.preregs-field--inline { max-width: 12rem; }
.preregs-field-label { display: block; font-size: 0.78rem; font-weight: 500; color: #334155; margin-bottom: 0.4rem; line-height: 1.35; }
.preregs-field-label-muted { font-weight: 500; color: #94a3b8; text-transform: none; letter-spacing: 0; font-size: 0.75rem; }
.preregs-req { color: #0d9488; font-weight: 500; opacity: 0.82; }
.preregs-opt { color: #94a3b8; font-weight: 500; font-size: 0.75rem; }
.preregs-input {
    width: 100%; padding: 0.68rem 0.82rem; font-size: 0.875rem; border: 1px solid #dbe5ef; border-radius: 0.625rem;
    background: #fff; color: #0f172a; box-sizing: border-box; transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.preregs-input::placeholder { color: #94a3b8; }
.preregs-input:hover { border-color: #c9d6e4; background: #fcfdff; }
.preregs-input--narrow { max-width: 8rem; }
.preregs-input--file {
    padding: 0.85rem 1rem;
    cursor: pointer;
    border-style: dashed;
    border-width: 1.5px;
    border-color: #cbd5e1;
    background: #f8fafc;
    min-height: 3.2rem;
    position: relative;
}
.preregs-input--file:hover {
    border-color: #0d9488;
    background: #f0fdfa;
}
.preregs-input--file::before {
    content: "↑  Arrastra o selecciona una foto";
    color: #475569;
    font-size: 0.8125rem;
    font-weight: 500;
    margin-right: 0.65rem;
}
.preregs-input--file::file-selector-button {
    border: 1px solid #dbe3ec;
    background: #ffffff;
    color: #334155;
    border-radius: 0.5rem;
    padding: 0.38rem 0.62rem;
    margin-right: 0.6rem;
    cursor: pointer;
    transition: all 0.2s ease;
}
.preregs-input--file::file-selector-button:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
.preregs-input--file.has-file {
    border-color: #86efac;
    background: #f0fdf4;
}
.preregs-file-state {
    margin: 0.45rem 0 0;
    font-size: 0.74rem;
    color: #64748b;
}
.preregs-select { cursor: pointer; appearance: auto; }
.preregs-hint { margin: 0.42rem 0 0; font-size: 0.73rem; color: #64748b; line-height: 1.35; }
.preregs-hint--block { margin-bottom: 0.75rem; }
.preregs-inline-warn { padding: 0.75rem 1rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 0.5rem; font-size: 0.8125rem; color: #92400e; margin: 0; }
.preregs-inline-warn a { color: #b45309; font-weight: 600; }
.preregs-combo-wrap { position: relative; }
.preregs-combo-dropdown {
    display: none; position: absolute; left: 0; right: 0; top: 100%; margin-top: 0.25rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 0.5rem;
    box-shadow: 0 10px 40px rgba(15, 23, 42, 0.12); max-height: 220px; overflow-y: auto; z-index: 100;
}
#agency_dropdown .agency-combo-item { padding: 0.65rem 0.875rem; cursor: pointer; font-size: 0.875rem; border-bottom: 1px solid #f1f5f9; color: #334155; }
#agency_dropdown .agency-combo-item:last-child { border-bottom: none; }
#agency_dropdown .agency-combo-item:hover { background: #f1f5f9; }
.preregs-combo-empty { padding: 0.65rem 0.875rem; font-size: 0.875rem; color: #64748b; }
.preregs-create-grid--bulto3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
@media (max-width: 900px) {
    .preregs-create-grid--bulto3 { grid-template-columns: 1fr; }
}
.preregs-cubic-line { margin: 0.5rem 0 0; font-size: 0.8125rem; color: #0f766e; }
.preregs-cubic-label { font-weight: 600; color: #64748b; margin-right: 0.35rem; }
.preregs-cubic-value { font-weight: 700; font-variant-numeric: tabular-nums; }
.preregs-multi-bultos-wrap { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #cbd5e1; }
.preregs-multi-lead { font-size: 0.8125rem; color: #64748b; margin: 0 0 0.75rem; line-height: 1.45; }
.preregs-bultos-container { margin-top: 0.75rem; }
.preregs-bulto-block { padding: 1rem; margin-bottom: 0.9rem; border: 1px solid #e5ebf2; border-radius: 0.625rem; background: #ffffff; }
.preregs-bulto-block h4 { margin: 0 0 0.75rem; font-size: 0.8125rem; font-weight: 700; color: #0d9488; text-transform: uppercase; letter-spacing: 0.04em; }
.preregs-photo-lead { font-size: 0.875rem; color: #64748b; margin: 0 0 1rem; line-height: 1.5; }
.preregs-photo-preview { margin-top: 1rem; display: none; }
.preregs-photo-preview img,
.preregs-photo-preview-img { max-width: 22rem; width: 100%; height: auto; border-radius: 0.5rem; border: 1px solid #e2e8f0; }
.preregs-form-actions--footer { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e2e8f0; }
@media (max-width: 768px) {
    .preregs-create-grid--root,
    .preregs-create-grid--nested { grid-template-columns: 1fr; }
    .preregs-field--inline { max-width: none; }
    .preregs-form-page { border-radius: 0; }
}
.preregs-card-body { padding: 1.3rem 1.55rem; }
.preregs-form-body { padding: 1.35rem 1.6rem 1.65rem; }
.preregs-form-section { margin-top: 1.25rem; padding-top: 0; border-top: none; }
.preregs-label-preview-wrap { display: none; margin-top: 1rem; margin-bottom: 0; }
.preregs-label-preview-wrap[style*="display: block"] { display: block !important; }
.preregs-section-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem; }
.preregs-label-preview-box { width: 100%; max-width: 4in; min-height: 4in; background: #fff; border: none; border-radius: 0; padding: 0.875rem 1rem; box-shadow: none; }
.preregs-label-preview-brand { font-size: 0.875rem; font-weight: 700; color: #0d9488; letter-spacing: 0.02em; margin-bottom: 0.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #0d9488; }
.preregs-label-preview-note { margin-top: 1rem; padding: 0.75rem 0.875rem; background: rgba(13, 148, 136, 0.1); border: 1px solid #0d9488; border-radius: 0.5rem; }
.preregs-label-preview-note-label { font-size: 0.625rem; text-transform: uppercase; letter-spacing: 0.06em; color: #0d9488; font-weight: 700; margin-bottom: 0.25rem; }
.preregs-label-preview-note-text { font-size: 0.875rem; font-weight: 700; color: #0f766e; }
.preregs-label-preview-service { margin-top: 0.25rem; font-size: 1.25rem; font-weight: 800; letter-spacing: 0.03em; }
.preregs-label-preview-service-air { color: #0f766e; }
.preregs-label-preview-service-sea { color: #1e40af; }
.preregs-photo-section { margin-top: 0; padding-top: 0; border-top: none; }
.preregs-form-panel.preregs-label-preview-wrap,
.preregs-form-panel.preregs-photo-section { margin-top: 1rem; }
.preregs-form-actions { margin-top: 1rem; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.85rem; align-items: center; }
.preregs-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.58rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.625rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: all 0.2s ease; }
.preregs-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; font-weight: 600; padding-left: 1.3rem; padding-right: 1.3rem; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.22); }
.preregs-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(15, 118, 110, 0.24); }
.preregs-btn-primary:disabled { background: #9ca3af; border-color: #9ca3af; cursor: not-allowed; }
.preregs-btn-secondary { background: #ffffff; color: #475569; border-color: #dbe3ec; }
.preregs-btn-secondary:hover { background: #f8fafc; color: #1e293b; border-color: #cbd5e1; }
.preregs-form-card input:focus, .preregs-form-card select:focus, .preregs-form-card textarea:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14); }
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
            previewService.className = 'preregs-label-preview-service preregs-label-preview-service-' + (serviceValue === 'SEA' ? 'sea' : 'air');
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
        var dropdown = document.getElementById('agency_dropdown');
        if (!combo || !hidden || !dropdown) return;
        var agencies = dataEl ? JSON.parse(dataEl.textContent || '[]') : [];
        function renderList(filter) {
            var q = (filter || '').trim().toLowerCase();
            var list = agencies.filter(function(a) {
                return !q || (a.name || '').toLowerCase().indexOf(q) !== -1 || (a.code || '').toLowerCase().indexOf(q) !== -1;
            });
            dropdown.innerHTML = list.length ? list.map(function(a) {
                var label = (a.code || '') + ' - ' + (a.name || '');
                return '<div class="agency-combo-item" data-id="' + a.id + '" data-label="' + label.replace(/"/g, '&quot;') + '">' + (a.code || '') + ' - ' + (a.name || '') + '</div>';
            }).join('') : '<div class="preregs-combo-empty">No hay coincidencias</div>';
            dropdown.style.display = 'block';
        }
        function selectAgency(id, label) {
            hidden.value = id;
            combo.value = label;
            dropdown.style.display = 'none';
            if (typeof updatePreview === 'function') updatePreview();
        }
        combo.addEventListener('focus', function() {
            if (!hidden.value) renderList(combo.value);
            else renderList('');
        });
        combo.addEventListener('input', function() {
            hidden.value = '';
            renderList(this.value);
        });
        combo.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { dropdown.style.display = 'none'; this.blur(); }
        });
        dropdown.addEventListener('click', function(e) {
            var item = e.target.closest('.agency-combo-item');
            if (item) selectAgency(item.getAttribute('data-id'), item.getAttribute('data-label'));
        });
        document.addEventListener('click', function(e) {
            if (dropdown.style.display === 'block' && !e.target.closest('#agency_combobox_wrap')) dropdown.style.display = 'none';
        });
        if (combo.placeholder) combo.placeholder = 'Buscar y elegir agencia (nombre o código)...';
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
