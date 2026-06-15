@extends('layouts.app')

@section('title', 'Nota de entrega')

@section('content')
@php
    $deliveryNote = $deliveryNote ?? null;
    $retirerSessionActive = $retirerSessionActive ?? false;
    $batchRetirerSession = is_array($batchRetirerSession ?? null) ? $batchRetirerSession : [];
    $deliveredCount = $deliveredCount ?? 0;
    $printReportParams = $deliveryNote
        ? array_merge($filterParams, ['date' => now()->toDateString(), 'delivery_note_id' => $deliveryNote->id])
        : array_merge($filterParams, ['date' => now()->toDateString()]);
@endphp

<div class="delivery-page delivery-batch-page">
    <header class="delivery-hero">
        <div class="delivery-hero-inner">
            <div class="delivery-hero-text">
                <h1 class="delivery-hero-title">Nota de entrega</h1>
                <p class="delivery-hero-subtitle">Entregas para {{ $agencyName }}</p>
                @if($deliveryNote)
                <div class="delivery-nota-code-label">Código de nota de entrega</div>
                <div class="delivery-nota-code">{{ $deliveryNote->code }}</div>
                @endif
            </div>
            <a href="{{ route('deliveries.index', session('deliveries_index_filters', $filterParams)) }}" class="delivery-hero-btn">← Volver a Entregas</a>
        </div>
    </header>

    @if(session('success'))
    <div class="delivery-alert delivery-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div id="delivery-error" class="delivery-alert delivery-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="delivery-card">
        <div class="delivery-card-header delivery-table-header">
            <h2 class="delivery-card-title">Paquetes a entregar ({{ $availablePackages->count() }})</h2>
        </div>
        <div class="delivery-card-body">
            <p class="delivery-hint">Solo se aceptan los paquetes de esta lista. Al escanear un código correcto, el paquete se marcará como entregado y saldrá de la lista. Si escanea un código que no está en la lista, el sistema no lo dará por entregado.</p>
            @if($availablePackages->isEmpty())
            <p class="delivery-muted">No hay más paquetes pendientes de escanear para esta agencia.</p>
            @if($deliveryNote)
            <p class="delivery-print-hint">Puede imprimir la nota de entrega con las entregas registradas en esta sesión.</p>
            <a href="{{ route('deliveries.print-report', $printReportParams) }}" target="_blank" class="delivery-btn delivery-btn-primary">Imprimir nota de entrega</a>
            @endif
            @else
            <div class="delivery-table-wrap">
                <table class="delivery-table">
                    <thead>
                        <tr>
                            <th>Cliente (etiqueta)</th>
                            <th>Warehouse</th>
                            <th>Bulto</th>
                            <th>Tracking</th>
                            <th>Servicio</th>
                            <th>Peso (lbs)</th>
                            <th>Listo desde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($availablePackages as $p)
                        <tr>
                            <td class="delivery-name-cell" title="{{ $p->label_name }}">{{ Str::limit($p->label_name, 28) }}</td>
                            <td><span class="delivery-code">{{ $p->warehouse_code ?? '—' }}</span></td>
                            <td class="delivery-code">{{ ($p->bultos_total && $p->bultos_total > 1 && $p->bulto_index) ? $p->bulto_index . '/' . $p->bultos_total : '—' }}</td>
                            <td class="delivery-code delivery-tracking-cell" title="{{ $p->tracking_external }}">{{ Str::limit($p->tracking_external, 20) }}</td>
                            <td><span class="delivery-badge delivery-badge-{{ strtolower($p->service_type ?? '') }}">{{ $p->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span></td>
                            <td class="delivery-num">{{ $p->verified_weight_lbs ?? $p->intake_weight_lbs ?? '—' }}</td>
                            <td class="delivery-muted">{{ $p->ready_at ? $p->ready_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(!$retirerSessionActive)
            <div class="delivery-retirer-confirm-box">
                <h3 class="delivery-retirer-step-title">1. Datos de quien retira (una sola vez)</h3>
                <p class="delivery-hint delivery-retirer-step-hint">Indique nombre completo, cédula y teléfono de la persona que retira. Luego podrá escanear todos los paquetes sin volver a escribir estos datos.</p>
                <form action="{{ route('deliveries.batch-retirer-session') }}" method="POST" class="delivery-retirer-form" id="delivery-retirer-form">
                    @csrf
                    @if($deliveryNote)
                    <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
                    @endif
                    <input type="hidden" name="agency_id" value="{{ $agency->id }}">
                    @if(!empty($filterParams['service_type']))
                    <input type="hidden" name="service_type" value="{{ $filterParams['service_type'] }}">
                    @endif
                    <div class="delivery-scan-row">
                        <div class="delivery-field">
                            <label for="retirer_delivered_to" class="delivery-label">Nombre completo *</label>
                            <input type="text" name="delivered_to" id="retirer_delivered_to" value="{{ old('delivered_to') }}" class="delivery-input @error('delivered_to') delivery-input-invalid @enderror" placeholder="Nombre completo" required autofocus>
                            @error('delivered_to')<span class="delivery-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="delivery-field">
                            <label for="retirer_invoice_number" class="delivery-label">Nº factura *</label>
                            <input type="text" name="invoice_number" id="retirer_invoice_number" value="{{ old('invoice_number') }}" class="delivery-input @error('invoice_number') delivery-input-invalid @enderror" placeholder="Ej. 17751" required>
                            @error('invoice_number')<span class="delivery-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="delivery-field">
                            <label for="retirer_id_number_form" class="delivery-label">Cédula (opcional)</label>
                            <input type="text" name="retirer_id_number" id="retirer_id_number_form" value="{{ old('retirer_id_number') }}" class="delivery-input @error('retirer_id_number') delivery-input-invalid @enderror" placeholder="Nº cédula">
                            @error('retirer_id_number')<span class="delivery-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="delivery-field">
                            <label for="retirer_phone_form" class="delivery-label">Teléfono (opcional)</label>
                            <input type="text" name="retirer_phone" id="retirer_phone_form" value="{{ old('retirer_phone') }}" class="delivery-input @error('retirer_phone') delivery-input-invalid @enderror" placeholder="Nº telefónico">
                            @error('retirer_phone')<span class="delivery-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="delivery-field delivery-field-btn">
                            <button type="submit" class="delivery-btn delivery-btn-primary" id="btn-retirer-submit">Guardar y escanear paquetes</button>
                        </div>
                    </div>
                </form>
            </div>
            @elseif($deliveryNote)
            <div class="delivery-retirer-banner">
                <div class="delivery-retirer-banner-text">
                    <strong>Quien retira:</strong> {{ $batchRetirerSession['delivered_to'] ?? '—' }}
                    <span class="delivery-retirer-banner-sep">·</span>
                    <strong>Nº factura:</strong> {{ $batchRetirerSession['invoice_number'] ?? '—' }}
                    @if(filled($batchRetirerSession['retirer_id_number'] ?? null))
                    <span class="delivery-retirer-banner-sep">·</span>
                    <strong>Cédula:</strong> {{ $batchRetirerSession['retirer_id_number'] }}
                    @endif
                    @if(filled($batchRetirerSession['retirer_phone'] ?? null))
                    <span class="delivery-retirer-banner-sep">·</span>
                    <strong>Tel.:</strong> {{ $batchRetirerSession['retirer_phone'] }}
                    @endif
                </div>
                <form action="{{ route('deliveries.batch-clear-retirer-session') }}" method="POST" class="delivery-retirer-banner-clear">
                    @csrf
                    <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
                    <input type="hidden" name="agency_id" value="{{ $agency->id }}">
                    @if(!empty($filterParams['service_type']))
                    <input type="hidden" name="service_type" value="{{ $filterParams['service_type'] }}">
                    @endif
                    <button type="submit" class="delivery-btn delivery-btn-ghost">Cambiar persona que retira</button>
                </form>
            </div>

            @if($deliveredCount > 0)
            <div class="delivery-close-note-banner">
                <div class="delivery-close-note-text">
                    <strong>{{ $deliveredCount }}</strong> {{ $deliveredCount === 1 ? 'paquete entregado' : 'paquetes entregados' }} en esta nota.
                    @if(! $availablePackages->isEmpty())
                    Quedan {{ $availablePackages->count() }} {{ $availablePackages->count() === 1 ? 'pendiente' : 'pendientes' }} para esta agencia: podés cerrar la nota ahora solo con los entregados o seguir escaneando.
                    @else
                    Ya no hay más paquetes pendientes para esta agencia.
                    @endif
                </div>
                <a href="{{ route('deliveries.print-report', $printReportParams) }}" target="_blank" class="delivery-btn delivery-btn-primary delivery-btn-close-note">Cerrar nota e imprimir</a>
            </div>
            @endif

            <div class="delivery-scan-box">
                <h3 class="delivery-scan-title">2. Escanear paquetes</h3>
                <form action="{{ route('deliveries.process-scan') }}" method="POST" class="delivery-scan-form" id="delivery-batch-scan-form">
                    @csrf
                    <input type="hidden" name="return_to_batch" value="1">
                    <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
                    <input type="hidden" name="agency_id" value="{{ $filterParams['agency_id'] ?? $agency->id }}">
                    @if(!empty($filterParams['service_type']))
                    <input type="hidden" name="service_type" value="{{ $filterParams['service_type'] }}">
                    @endif
                    <input type="hidden" name="delivered_to" value="{{ $batchRetirerSession['delivered_to'] ?? '' }}">
                    <input type="hidden" name="retirer_id_number" value="{{ $batchRetirerSession['retirer_id_number'] ?? '' }}">
                    <input type="hidden" name="retirer_phone" value="{{ $batchRetirerSession['retirer_phone'] ?? '' }}">
                    <input type="hidden" name="invoice_number" value="{{ $batchRetirerSession['invoice_number'] ?? '' }}">
                    <div class="delivery-scan-row delivery-scan-row-code">
                        <div class="delivery-field">
                            <label for="warehouse_code" class="delivery-label">Código warehouse (escanear) *</label>
                            <input type="text" name="warehouse_code" id="warehouse_code" value="{{ old('warehouse_code') }}" class="delivery-input delivery-input-scan" placeholder="Escanee aquí" maxlength="6" pattern="[0-9]{6}" required autofocus inputmode="numeric" autocomplete="off">
                        </div>
                        <div class="delivery-field" id="bulto-select-wrap" style="display: none;">
                            <label for="bulto_index" class="delivery-label">Bulto (varios con este código) *</label>
                            <select name="bulto_index" id="bulto_index" class="delivery-select">
                                <option value="">— Seleccione —</option>
                            </select>
                        </div>
                        <div class="delivery-field delivery-field-btn">
                            <button type="submit" class="delivery-btn delivery-btn-scan" id="btn-register-delivery">Registrar entrega</button>
                        </div>
                    </div>
                </form>
                <p class="delivery-scan-hint">Escanea cada paquete: al leer 6 dígitos el sistema registra automáticamente. Si varios bultos comparten el mismo código (ej. 1/11, 2/11…), seleccione cuál bulto está entregando. Solo se aceptan paquetes de la lista de arriba.</p>
                <p class="delivery-scan-print">
                    <a href="{{ route('deliveries.print-report', $printReportParams) }}" target="_blank" class="delivery-link">Imprimir nota de entrega ({{ $deliveryNote->code }})</a>
                </p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.delivery-batch-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.delivery-batch-page .delivery-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.delivery-batch-page .delivery-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.delivery-batch-page .delivery-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.delivery-nota-code-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.8); margin-top: 0.5rem; margin-bottom: 0.25rem; }
.delivery-nota-code { font-family: ui-monospace, monospace; font-size: 1.25rem; font-weight: 700; color: #f8fafc; letter-spacing: 0.08em; background: rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.2); display: inline-block; margin-top: 0.25rem; }
.delivery-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.delivery-hero-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none; }
.delivery-hero-btn:hover { background: #f0fdfa; color: #0d9488; }
.delivery-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.delivery-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.delivery-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.delivery-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.delivery-card-header.delivery-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); padding: 0.75rem 1.5rem; }
.delivery-card-header.delivery-table-header .delivery-card-title { color: #fff; }
.delivery-card-body { padding: 1.25rem; }
.delivery-hint, .delivery-muted { font-size: 0.875rem; color: #6b7280; margin: 0 0 0.5rem; }
.delivery-print-hint { margin-bottom: 1rem; }
.delivery-table-wrap { overflow-x: auto; margin-bottom: 1.5rem; }
.delivery-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.delivery-table thead tr { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.delivery-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.2); }
.delivery-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; }
.delivery-code { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; }
.delivery-name-cell { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.delivery-tracking-cell { max-width: 120px; }
.delivery-num { font-weight: 500; }
.delivery-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.delivery-badge-air { background: #dbeafe; color: #1d4ed8; }
.delivery-badge-sea { background: #d1fae5; color: #047857; }
.delivery-retirer-confirm-box {
    background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
    border: 2px solid #d97706; border-radius: 0.75rem; padding: 1.25rem; margin-top: 1.5rem;
}
.delivery-retirer-step-title { margin: 0 0 0.5rem; font-size: 1rem; font-weight: 600; color: #92400e; }
.delivery-retirer-step-hint { margin-bottom: 1rem !important; color: #78350f !important; }
.delivery-retirer-form { margin: 0; }
.delivery-field-error { display: block; font-size: 0.75rem; color: #b91c1c; margin-top: 0.25rem; }
.delivery-input-invalid { border-color: #f87171 !important; }
.delivery-retirer-banner {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;
    background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 0.75rem; padding: 0.875rem 1rem; margin-top: 1.5rem; font-size: 0.875rem; color: #065f46;
}
.delivery-retirer-banner-text { flex: 1; min-width: 0; line-height: 1.5; }
.delivery-retirer-banner-sep { color: #34d399; margin: 0 0.25rem; }
.delivery-retirer-banner-clear { margin: 0; }
.delivery-btn-ghost {
    background: #fff; color: #047857; border: 1px solid #6ee7b7; font-size: 0.8125rem; padding: 0.4rem 0.75rem;
}
.delivery-btn-ghost:hover { background: #f0fdf4; }
.delivery-close-note-banner {
    margin-top: 1rem;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    border: 2px solid #2563eb;
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    color: #1e3a8a;
    font-size: 0.9375rem;
    line-height: 1.4;
}
.delivery-close-note-text { flex: 1 1 280px; min-width: 0; }
.delivery-close-note-text strong { color: #1e3a8a; }
.delivery-btn-close-note { background: #2563eb; border-color: #2563eb; color: #fff; font-weight: 600; padding: 0.55rem 1.1rem; white-space: nowrap; }
.delivery-btn-close-note:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }

.delivery-scan-box { background: linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%); border: 2px solid #059669; border-radius: 0.75rem; padding: 1.25rem; margin-top: 1rem; }
.delivery-scan-title { margin: 0 0 1rem; font-size: 1rem; font-weight: 600; color: #047857; }
.delivery-scan-form { margin-bottom: 0.75rem; }
.delivery-scan-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; margin-bottom: 1rem; }
.delivery-scan-row-code { margin-bottom: 0.5rem; }
.delivery-field { min-width: 0; }
.delivery-field label.delivery-label { display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.delivery-input, .delivery-select { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; width: 100%; min-width: 120px; }
.delivery-input:focus, .delivery-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.delivery-input-scan { text-align: center; font-family: ui-monospace, monospace; max-width: 140px; }
.delivery-field-btn { display: flex; align-items: flex-end; }
.delivery-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.delivery-btn[disabled] { opacity: 0.6; cursor: not-allowed; }
.delivery-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.delivery-btn-primary:hover { background: #0f766e; color: #fff; }
.delivery-btn-scan { background: #059669; color: #fff; border-color: #059669; font-weight: 600; padding: 0.5rem 1.25rem; }
.delivery-btn-scan:hover { background: #047857; color: #fff; }
.delivery-scan-hint { font-size: 0.8125rem; color: #6b7280; margin: 0 0 0.5rem; }
.delivery-scan-print { margin: 0; font-size: 0.875rem; }
.delivery-link { color: #0d9488; text-decoration: none; font-weight: 500; }
.delivery-link:hover { text-decoration: underline; }
@media (min-width: 768px) {
    .delivery-scan-row .delivery-field { flex: 0 0 auto; }
    .delivery-scan-row .delivery-field:first-child { min-width: 180px; }
    .delivery-scan-row-code .delivery-field:first-child { min-width: 140px; max-width: 160px; }
}
</style>

@php
    $bultosByCode = $availablePackages->groupBy('warehouse_code')->map(function ($group) {
        if ($group->count() <= 1) return null;
        return $group->sortBy('bulto_index')->map(function ($p) {
            $label = ($p->bulto_index && $p->bultos_total) ? (int)$p->bulto_index . '/' . (int)$p->bultos_total : (string)($p->bulto_index ?? '');
            return ['bulto_index' => $p->bulto_index, 'bultos_total' => $p->bultos_total, 'label' => $label];
        })->values()->all();
    })->filter();
@endphp
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Anti doble-submit en el formulario del retirante
    var retirerForm = document.getElementById('delivery-retirer-form');
    var retirerBtn = document.getElementById('btn-retirer-submit');
    if (retirerForm && retirerBtn) {
        retirerForm.addEventListener('submit', function() {
            retirerBtn.disabled = true;
            retirerBtn.textContent = 'Guardando…';
        });
    }

    var form = document.getElementById('delivery-batch-scan-form');
    var codeInput = document.getElementById('warehouse_code');
    var bultoWrap = document.getElementById('bulto-select-wrap');
    var bultoSelect = document.getElementById('bulto_index');
    var scanBtn = document.getElementById('btn-register-delivery');
    if (!form || !codeInput) return;

    var bultosByCode = @json($bultosByCode);

    codeInput.focus();

    function getDigitsOnly(val) {
        return (val || '').replace(/\D/g, '');
    }

    function updateBultoSelect(code) {
        var digits = getDigitsOnly(code);
        if (digits.length !== 6 || !bultoSelect || !bultoWrap) return false;
        var bultos = bultosByCode[digits];
        if (!bultos || bultos.length <= 1) {
            bultoWrap.style.display = 'none';
            bultoSelect.innerHTML = '<option value="">— Seleccione —</option>';
            bultoSelect.removeAttribute('required');
            return false;
        }
        bultoWrap.style.display = 'block';
        bultoSelect.innerHTML = '<option value="">— Seleccione bulto —</option>' + bultos.map(function(b) {
            return '<option value="' + b.bulto_index + '">' + b.label + '</option>';
        }).join('');
        bultoSelect.setAttribute('required', 'required');
        return true;
    }

    function needsBultoSelection() {
        var digits = getDigitsOnly(codeInput.value);
        return digits.length === 6 && bultosByCode[digits] && bultosByCode[digits].length > 1;
    }

    function canSubmit() {
        if (needsBultoSelection() && (!bultoSelect || !bultoSelect.value)) return false;
        return true;
    }

    var submitting = false;
    function submitOnce() {
        if (submitting) return;
        submitting = true;
        if (scanBtn) { scanBtn.disabled = true; scanBtn.textContent = 'Registrando…'; }
        codeInput.setAttribute('readonly', 'readonly');
        form.submit();
    }

    codeInput.addEventListener('input', function() {
        var digits = getDigitsOnly(this.value);
        if (digits.length > 6) this.value = digits.slice(0, 6);
        else this.value = digits;
        updateBultoSelect(this.value);
        if (digits.length === 6 && canSubmit()) submitOnce();
    });

    if (bultoSelect) {
        bultoSelect.addEventListener('change', function() {
            if (canSubmit()) submitOnce();
        });
    }

    codeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var digits = getDigitsOnly(this.value);
            updateBultoSelect(this.value);
            if (digits.length === 6 && canSubmit()) submitOnce();
            else if (digits.length === 6 && needsBultoSelection() && bultoSelect) bultoSelect.focus();
        }
    });

    form.addEventListener('submit', function(e) {
        if (submitting) return;
        submitting = true;
        if (scanBtn) { scanBtn.disabled = true; scanBtn.textContent = 'Registrando…'; }
    });

    if (document.getElementById('delivery-error')) {
        codeInput.value = '';
        codeInput.removeAttribute('readonly');
        codeInput.focus();
    }
    updateBultoSelect(codeInput.value);
});
</script>
@endpush
@endsection
