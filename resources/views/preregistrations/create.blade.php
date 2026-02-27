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
        <div class="preregs-card-header preregs-form-header">
            <h2 class="preregs-card-title">Bulto {{ $dropoffStep }} de {{ $dropoffTotal }} (Drop Off)</h2>
        </div>
        <div class="preregs-card-body preregs-form-body">
            <p style="margin-bottom: 1rem; font-size: 14px; color: #374151;">Mismo código de almacén para todos. Completa los datos de este bulto y al guardar podrás imprimir la etiqueta {{ $dropoffStep }}/{{ $dropoffTotal }}. Luego continúa con el siguiente.</p>
            @if($dropoffAgencyName)
            <p style="margin-bottom: 1rem; font-size: 13px; color: #6b7280;"><strong>Agencia:</strong> {{ $dropoffAgencyName }} · <strong>Servicio:</strong> {{ $dropoffServiceType === 'SEA' ? 'Marítimo' : 'Aéreo' }}</p>
            @endif
            <form action="{{ route('preregistrations.store') }}" method="POST" enctype="multipart/form-data" id="preregFormDropoffStep">
                @csrf
                <input type="hidden" name="intake_type" value="DROP_OFF">
                <input type="hidden" name="dropoff_step" value="{{ $dropoffStep }}">
                <input type="hidden" name="bultos_count" value="{{ $dropoffTotal }}">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px;">
                    <div>
                        <label for="dropoff_label_name" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Nombre en etiqueta *</label>
                        <input type="text" name="label_name" id="dropoff_label_name" required style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label for="dropoff_intake_weight_lbs" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Peso (lbs) *</label>
                        <input type="number" step="0.01" name="intake_weight_lbs" id="dropoff_intake_weight_lbs" required style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label for="dropoff_dimension" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Dimensión *</label>
                        <input type="text" name="dimension" id="dropoff_dimension" required placeholder="Ej: 10 x 8 x 5 in" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label for="dropoff_description" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Descripción del contenido (opcional)</label>
                        <input type="text" name="description" id="dropoff_description" maxlength="500" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label for="dropoff_photo" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Foto del bulto *</label>
                        <input type="file" name="photo" id="dropoff_photo" accept="image/jpeg,image/jpg,image/png,image/webp" required style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
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
        <div class="preregs-card-header preregs-form-header">
            <h2 class="preregs-card-title">Datos del preregistro</h2>
        </div>
        <div class="preregs-card-body preregs-form-body">
        <form action="{{ route('preregistrations.store') }}" method="POST" enctype="multipart/form-data" style="margin: 0;" id="preregForm">
            @csrf
            <input type="hidden" name="service_type" id="service_type_post" value="AIR">

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px;">
                <div>
                    <label for="agency_combobox" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Agencia (subagencia con la que envía el cliente) *</label>
                    @if($agencies->isEmpty())
                    <p style="padding: 10px; background: #fef3c7; color: #92400e; border-radius: 6px; font-size: 13px;">No hay agencias activas. <a href="{{ route('agencies.create') }}" class="font-medium underline">Crear agencia</a> antes de registrar un preregistro.</p>
                    @else
                    <div id="agency_combobox_wrap" style="position: relative;">
                        <input type="text" id="agency_combobox" placeholder="Buscar y elegir agencia (nombre o código)..." autocomplete="off" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; box-sizing: border-box;">
                        <input type="hidden" name="agency_id" id="agency_id" value="" required>
                        <div id="agency_dropdown" style="display: none; position: absolute; left: 0; right: 0; top: 100%; margin-top: 4px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-height: 220px; overflow-y: auto; z-index: 100;"></div>
                    </div>
                    <p style="margin-top: 4px; font-size: 12px; color: #6b7280;">Escriba para buscar y haga clic en la agencia. La etiqueta mostrará esta agencia.</p>
                    <script type="application/json" id="agencies-data">@json($agencies->isEmpty() ? [] : $agencies->map(function($a) { return ['id' => $a->id, 'code' => $a->code, 'name' => $a->name]; })->values())</script>
                    @endif
                </div>

                <div>
                    <label for="intake_type" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Tipo de Ingreso</label>
                    <select name="intake_type" id="intake_type" required style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                        <option value="COURIER">Courier</option>
                        <option value="DROP_OFF">Drop Off</option>
                    </select>
                </div>

                <div id="wrap_tracking" style="grid-column: span 2;">
                    <label for="tracking_external" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Tracking Externo</label>
                    <input 
                        type="text" 
                        name="tracking_external" 
                        id="tracking_external" 
                        style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: text;"
                        placeholder="1Z999AA10123456784"
                    >
                    <p style="margin-top: 4px; font-size: 12px; color: #6b7280;">Requerido si es Courier</p>
                </div>

                <!-- Cantidad de bultos: solo Drop Off, default 1 -->
                <div id="wrap_bultos_count" style="display: none;">
                    <label for="bultos_count" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Cantidad de bultos *</label>
                    <input type="number" name="bultos_count" id="bultos_count" min="1" max="20" value="1" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white;">
                    <p style="margin-top: 4px; font-size: 12px; color: #6b7280;">Mismo warehouse para todos; cada bulto lleva su detalle abajo.</p>
                </div>

                <!-- Un solo bulto (Drop Off 1 o Courier) -->
                <div id="wrap_single_bulto" style="display: grid; grid-column: span 2; grid-template-columns: repeat(2, 1fr); gap: 24px; align-items: start;">
                    <div>
                        <label for="label_name" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Nombre en Etiqueta *</label>
                        <input 
                            type="text" 
                            name="label_name" 
                            id="label_name" 
                            style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: text;"
                        >
                    </div>
                    <div>
                        <label for="service_type" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Tipo de Servicio *</label>
                        <select id="service_type" required style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="AIR">Aéreo</option>
                            <option value="SEA">Marítimo</option>
                        </select>
                    </div>
                    <div>
                        <label for="intake_weight_lbs" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Peso (lbs) *</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="intake_weight_lbs" 
                            id="intake_weight_lbs" 
                            style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: text;"
                        >
                    </div>
                    <div id="wrap_dimension" style="display: none;">
                        <label for="dimension" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Dimensión * <span style="font-weight: normal; color: #6b7280;">(Largo x Ancho x Alto en pulgadas, ej: 10 x 8 x 5 in)</span></label>
                        <input 
                            type="text" 
                            name="dimension" 
                            id="dimension" 
                            style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: text;"
                            placeholder="Largo x Ancho x Alto"
                        >
                        <p style="margin-top: 4px; font-size: 13px; color: #0d9488;"><strong>Pie cúbico:</strong> <span id="cubic_feet_display">—</span></p>
                    </div>
                    <div style="grid-column: span 2;">
                        <label for="description" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Descripción del contenido (opcional)</label>
                        <input 
                            type="text" 
                            name="description" 
                            id="description" 
                            maxlength="500"
                            style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: text;"
                            placeholder="Ej: Ropa, electrónicos, documentos..."
                        >
                        <p style="margin-top: 4px; font-size: 12px; color: #6b7280;">Control de lo que viene dentro del paquete.</p>
                    </div>
                </div>

                <!-- Varios bultos (Drop Off): un bulto por vez, imprimir etiqueta y continuar -->
                <div id="wrap_multi_bultos" style="display: none; grid-column: span 2; margin-top: 16px;">
                    <p style="margin-bottom: 12px; font-size: 13px; color: #6b7280;">Se mostrará un formulario por cada bulto. Al guardar podrás imprimir la etiqueta de ese bulto y luego continuar con el siguiente.</p>
                    <div>
                        <label for="service_type_multi" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Tipo de Servicio *</label>
                        <select id="service_type_multi" style="width: 100%; max-width: 200px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="AIR">Aéreo</option>
                            <option value="SEA">Marítimo</option>
                        </select>
                    </div>
                    <div id="bultos_container" style="margin-top: 20px;"></div>
                    <input type="hidden" name="dropoff_step" id="dropoff_step_input" value="1">
                </div>
            </div>

            <!-- Preview de etiqueta (solo Drop Off) -->
            <div id="wrap_label_preview" class="preregs-form-section preregs-label-preview-wrap">
                <h3 class="preregs-section-title">Vista previa de la etiqueta</h3>
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 12px;">Así se verá la etiqueta que se imprimirá al guardar. El código de almacén se asignará al crear el preregistro.</p>
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
            <div id="wrap_photo_section" class="preregs-form-section preregs-photo-section">
                <h3 class="preregs-section-title">Foto del Paquete *</h3>
                <p style="font-size: 14px; color: #6b7280; margin-bottom: 16px;">La foto es obligatoria. Solo se permite una foto por paquete (máximo 10MB).</p>
                
                <div>
                    <label for="photo" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Seleccionar Foto</label>
                    <input 
                        type="file" 
                        name="photo" 
                        id="photo" 
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                        capture="environment"
                        required
                        style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;"
                    >
                    <p style="margin-top: 4px; font-size: 12px; color: #6b7280;">En celular se abrirá la cámara. Formatos: JPG, PNG, WEBP. Máximo 10MB.</p>
                </div>

                <div id="photoPreview" style="margin-top: 16px; display: none;"></div>
            </div>

            <div class="preregs-form-actions">
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
.preregs-form-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.preregs-form-page .preregs-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.preregs-form-page .preregs-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.preregs-form-page .preregs-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.preregs-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.preregs-hero-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none; }
.preregs-hero-btn:hover { background: #f0fdfa; color: #0d9488; }
.preregs-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.preregs-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.preregs-alert-title { font-weight: 600; margin-bottom: 0.35rem; }
.preregs-alert-list { margin: 0; padding-left: 1.25rem; }
.preregs-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem; }
.preregs-card-header.preregs-form-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); padding: 0.75rem 1.5rem; }
.preregs-form-header .preregs-card-title { color: #fff; margin: 0; font-size: 1rem; font-weight: 600; }
.preregs-card-body { padding: 1.25rem 1.5rem; }
.preregs-form-body { padding: 1.5rem; }
.preregs-form-body #preregForm { margin: 0; }
.preregs-form-section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.preregs-label-preview-wrap { display: none; margin-bottom: 1.5rem; }
.preregs-label-preview-wrap[style*="display: block"] { display: block !important; }
.preregs-section-title { font-size: 1.125rem; font-weight: 600; color: #0d9488; margin-bottom: 0.75rem; }
.preregs-label-preview-box { width: 100%; max-width: 4in; min-height: 4in; background: #fff; border: 2px solid #111; border-radius: 0.5rem; padding: 0.875rem 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.preregs-label-preview-brand { font-size: 0.875rem; font-weight: 700; color: #0d9488; letter-spacing: 0.02em; margin-bottom: 0.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #0d9488; }
.preregs-label-preview-note { margin-top: 1rem; padding: 0.75rem 0.875rem; background: rgba(13, 148, 136, 0.1); border: 1px solid #0d9488; border-radius: 0.5rem; }
.preregs-label-preview-note-label { font-size: 0.625rem; text-transform: uppercase; letter-spacing: 0.06em; color: #0d9488; font-weight: 700; margin-bottom: 0.25rem; }
.preregs-label-preview-note-text { font-size: 0.875rem; font-weight: 700; color: #0f766e; }
.preregs-label-preview-service { margin-top: 0.25rem; font-size: 1.25rem; font-weight: 800; letter-spacing: 0.03em; }
.preregs-label-preview-service-air { color: #0f766e; }
.preregs-label-preview-service-sea { color: #1e40af; }
.preregs-photo-section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.preregs-form-actions { margin-top: 1.5rem; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; }
.preregs-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.preregs-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; font-weight: 600; }
.preregs-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.preregs-btn-primary:disabled { background: #9ca3af; border-color: #9ca3af; cursor: not-allowed; }
.preregs-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.preregs-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.preregs-form-card input:focus, .preregs-form-card select:focus, .preregs-form-card textarea:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
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
        var styleRow = 'padding: 16px; margin-bottom: 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;';
        var styleLabel = 'display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px;';
        var styleInput = 'width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;';
        var photoAccept = 'image/jpeg,image/jpg,image/png,image/webp';
        // Un solo bloque: Bulto 1 de N. Al guardar se imprime etiqueta 1/N y luego el usuario continúa con el siguiente.
        var div = document.createElement('div');
        div.className = 'bulto-block bulto-block-step';
        div.setAttribute('data-index', 0);
        div.innerHTML =
            '<h4 style="font-size: 14px; color: #0d9488; margin-bottom: 12px;">Bulto 1 de ' + n + '</h4>' +
            '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">' +
            '<div><label style="' + styleLabel + '">Nombre en etiqueta *</label><input type="text" name="label_name" required style="' + styleInput + '" placeholder="Nombre del destinatario"></div>' +
            '<div><label style="' + styleLabel + '">Peso (lbs) *</label><input type="number" step="0.01" name="intake_weight_lbs" required style="' + styleInput + '" placeholder="0.00"></div>' +
            '<div><label style="' + styleLabel + '">Dimensión * <span style="font-weight: normal; color: #6b7280;">(L x A x H pulg)</span></label><input type="text" name="dimension" class="dimension-input-multi" required style="' + styleInput + '" placeholder="10 x 8 x 5 in"><p style="margin-top: 4px; font-size: 12px; color: #0d9488;"><strong>Pie cúbico:</strong> <span class="cubic-feet-display">—</span></p></div>' +
            '</div>' +
            '<div style="margin-bottom: 12px;"><label style="' + styleLabel + '">Descripción del contenido (opcional)</label><input type="text" name="description" maxlength="500" style="' + styleInput + '" placeholder="Ej: Ropa, electrónicos..."></div>' +
            '<div><label style="' + styleLabel + '">Foto del bulto *</label>' +
            '<input type="file" name="photo" accept="' + photoAccept + '" capture="environment" required style="' + styleInput + '">' +
            '<p style="margin-top: 4px; font-size: 11px; color: #6b7280;">Completa este bulto y al guardar podrás imprimir la etiqueta 1/' + n + '. Luego continúa con el siguiente. JPG, PNG o WEBP. Máx. 10MB.</p></div>';
        div.style.cssText = styleRow;
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
                return '<div class="agency-combo-item" data-id="' + a.id + '" data-label="' + label.replace(/"/g, '&quot;') + '" style="padding: 10px 12px; cursor: pointer; font-size: 14px; border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'\'">' + (a.code || '') + ' - ' + (a.name || '') + '</div>';
            }).join('') : '<div style="padding: 10px 12px; font-size: 14px; color: #6b7280;">No hay coincidencias</div>';
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
    if (photoInput && preview) {
        photoInput.addEventListener('change', function(e) {
            preview.innerHTML = '';
            preview.style.display = 'none';
            var file = e.target.files[0];
            if (!file) return;
            if (file.size > 10 * 1024 * 1024) {
                alert('La foto excede el tamaño máximo de 10MB');
                e.target.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function(event) {
                var img = document.createElement('img');
                img.src = event.target.result;
                img.alt = 'Vista previa';
                img.style.cssText = 'max-width: 400px; height: auto; border-radius: 6px; border: 1px solid #d1d5db;';
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
