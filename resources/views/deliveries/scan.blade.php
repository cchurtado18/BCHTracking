@extends('layouts.app')

@section('title', 'Escanear Entrega')

@section('content')
@php
    $scanRetirerSessionActive = $scanRetirerSessionActive ?? false;
    $scanRetirerSession = is_array($scanRetirerSession ?? null) ? $scanRetirerSession : [];
    $scannedDeliveries = $scannedDeliveries ?? collect();
@endphp
<div class="py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Escanear entrega</h1>
            <p class="mt-2 text-sm text-gray-600">Indique una sola vez quién retira; luego escanee warehouse (6 dígitos) o tracking de cada paquete.</p>
        </div>
        <a href="{{ route('deliveries.index') }}" class="text-gray-600 hover:text-gray-900">
            ← Volver
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 border border-green-200">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div id="delivery-scan-error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 border border-red-200">{{ session('error') }}</div>
    @endif

    <div class="max-w-3xl space-y-6">
        @if(!$scanRetirerSessionActive)
        <div class="bg-amber-50 border border-amber-200 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-amber-900 mb-2">1. Datos de quien retira (una sola vez)</h2>
            <p class="text-sm text-amber-800 mb-4">Después de guardar, podrá escanear varios paquetes seguidos sin volver a escribir nombre, cédula ni teléfono.</p>
            <form action="{{ route('deliveries.scan-retirer-session') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="delivered_to" class="block text-sm font-medium text-gray-700">Nombre completo *</label>
                        <input type="text" name="delivered_to" id="delivered_to" value="{{ old('delivered_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('delivered_to') border-red-500 @enderror" required autofocus placeholder="Nombre completo">
                        @error('delivered_to')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="invoice_number" class="block text-sm font-medium text-gray-700">Nº factura (opcional)</label>
                        <input type="text" name="invoice_number" id="invoice_number" value="{{ old('invoice_number') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('invoice_number') border-red-500 @enderror" placeholder="Ej. 17751">
                        @error('invoice_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="retirer_id_number" class="block text-sm font-medium text-gray-700">Cédula (opcional)</label>
                            <input type="text" name="retirer_id_number" id="retirer_id_number" value="{{ old('retirer_id_number') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('retirer_id_number') border-red-500 @enderror" placeholder="Nº cédula">
                            @error('retirer_id_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="retirer_phone" class="block text-sm font-medium text-gray-700">Teléfono (opcional)</label>
                            <input type="text" name="retirer_phone" id="retirer_phone" value="{{ old('retirer_phone') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('retirer_phone') border-red-500 @enderror" placeholder="Nº telefónico">
                            @error('retirer_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" id="btn-scan-retirer-submit" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700 font-medium disabled:opacity-60 disabled:cursor-not-allowed">
                        Guardar y escanear
                    </button>
                </div>
            </form>
        </div>
        @else
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-emerald-900">
            <div>
                <span class="font-semibold">Quien retira:</span> {{ $scanRetirerSession['delivered_to'] ?? '—' }}
                @if(filled($scanRetirerSession['invoice_number'] ?? null))
                <span class="text-emerald-600 mx-1">·</span>
                <span class="font-semibold">Nº factura:</span> {{ $scanRetirerSession['invoice_number'] }}
                @endif
                @if(filled($scanRetirerSession['retirer_id_number'] ?? null))
                <span class="text-emerald-600 mx-1">·</span>
                <span class="font-semibold">Cédula:</span> {{ $scanRetirerSession['retirer_id_number'] }}
                @endif
                @if(filled($scanRetirerSession['retirer_phone'] ?? null))
                <span class="text-emerald-600 mx-1">·</span>
                <span class="font-semibold">Tel.:</span> {{ $scanRetirerSession['retirer_phone'] }}
                @endif
            </div>
            <form action="{{ route('deliveries.scan-clear-retirer-session') }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit" class="text-sm font-medium text-emerald-800 underline hover:text-emerald-950">Cambiar persona que retira</button>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">2. Escanear warehouse o tracking</h2>
            <form action="{{ route('deliveries.process-scan') }}" method="POST" id="delivery-standalone-scan-form">
                @csrf
                <input type="hidden" name="delivered_to" value="{{ $scanRetirerSession['delivered_to'] ?? '' }}">
                <input type="hidden" name="retirer_id_number" value="{{ $scanRetirerSession['retirer_id_number'] ?? '' }}">
                <input type="hidden" name="retirer_phone" value="{{ $scanRetirerSession['retirer_phone'] ?? '' }}">
                <input type="hidden" name="invoice_number" value="{{ $scanRetirerSession['invoice_number'] ?? '' }}">

                <div class="space-y-6">
                    <div>
                        <label for="scan_code" class="block text-sm font-medium text-gray-700">Warehouse o tracking *</label>
                        <input
                            type="text"
                            name="code"
                            id="scan_code"
                            value="{{ old('code', old('warehouse_code')) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-center text-xl font-mono tracking-wide"
                            required
                            maxlength="100"
                            placeholder="Escanee aquí"
                            autofocus
                            autocomplete="off"
                        >
                        <p class="mt-1 text-sm text-gray-500">Use la pistola: warehouse o tracking se registran solos al terminar de leer el código.</p>
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notas (opcional)</label>
                        <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Notas adicionales">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="submit" id="btn-standalone-scan-submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed">
                        Registrar entrega
                    </button>
                </div>
            </form>
        </div>

        @if($scannedDeliveries->isNotEmpty())
        <div class="bg-white shadow rounded-lg overflow-hidden border-2 border-teal-600">
            <div class="px-4 py-3 bg-teal-700 text-white flex items-center justify-between gap-2">
                <h2 class="text-base font-semibold">Escaneados hoy</h2>
                <span class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-full bg-white/20 font-bold">{{ $scannedDeliveries->count() }}</span>
            </div>
            <ol class="divide-y divide-gray-100 max-h-96 overflow-y-auto m-0 p-0 list-none">
                @foreach($scannedDeliveries as $i => $delivery)
                    @php $pkg = $delivery->preregistration; @endphp
                    <li class="px-4 py-3 flex items-center gap-3 {{ $i === 0 ? 'bg-emerald-50' : '' }}">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold {{ $i === 0 ? 'bg-teal-600 text-white' : 'bg-teal-100 text-teal-800' }}">{{ $scannedDeliveries->count() - $i }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-gray-900 truncate">{{ $pkg?->label_name ?: 'Sin nombre' }}</div>
                            <div class="mt-0.5 flex flex-wrap gap-x-3 gap-y-1 text-xs font-mono text-gray-600">
                                <span>{{ $pkg?->warehouse_code ?? '—' }}</span>
                                @if($pkg?->tracking_external)
                                <span title="{{ $pkg->tracking_external }}">{{ Str::limit($pkg->tracking_external, 24) }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 tabular-nums whitespace-nowrap">{{ $delivery->delivered_at?->timezone(config('app.display_timezone'))->format('H:i:s') ?? '—' }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
        @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var retirerBtn = document.getElementById('btn-scan-retirer-submit');
    if (retirerBtn && retirerBtn.form) {
        retirerBtn.form.addEventListener('submit', function() {
            retirerBtn.disabled = true;
            retirerBtn.textContent = 'Guardando…';
        });
    }

    var form = document.getElementById('delivery-standalone-scan-form');
    var input = document.getElementById('scan_code');
    var scanBtn = document.getElementById('btn-standalone-scan-submit');
    if (!form || !input) return;

    if (document.getElementById('delivery-scan-error')) {
        input.value = '';
        input.removeAttribute('readonly');
    }
    input.focus();

    var DEBOUNCE_MS = 180;
    var debounceTimer = null;
    var submitting = false;

    function normalizeCode(val) {
        return (val || '').trim().toUpperCase();
    }

    function submitOnce() {
        if (submitting || !normalizeCode(input.value)) return;
        submitting = true;
        clearTimeout(debounceTimer);
        if (scanBtn) { scanBtn.disabled = true; scanBtn.textContent = 'Registrando…'; }
        input.value = normalizeCode(input.value);
        input.setAttribute('readonly', 'readonly');
        form.submit();
    }

    function scheduleAutoSubmit(expectedCode) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            if (normalizeCode(input.value) !== expectedCode) return;
            submitOnce();
        }, DEBOUNCE_MS);
    }

    input.addEventListener('input', function() {
        var raw = (this.value || '').trim();
        if (/^\d{0,6}$/.test(raw)) {
            this.value = raw.replace(/\D/g, '').slice(0, 6);
            if (this.value.length === 6) scheduleAutoSubmit(normalizeCode(this.value));
            else clearTimeout(debounceTimer);
            return;
        }
        var code = normalizeCode(this.value);
        if (code.length >= 4) scheduleAutoSubmit(code);
        else clearTimeout(debounceTimer);
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer);
            if (normalizeCode(this.value)) submitOnce();
        }
    });

    form.addEventListener('submit', function(e) {
        if (submitting) return;
        if (!normalizeCode(input.value)) {
            e.preventDefault();
            return;
        }
        submitting = true;
        clearTimeout(debounceTimer);
        input.value = normalizeCode(input.value);
        if (scanBtn) { scanBtn.disabled = true; scanBtn.textContent = 'Registrando…'; }
    });
});
</script>
@endpush
@endsection
