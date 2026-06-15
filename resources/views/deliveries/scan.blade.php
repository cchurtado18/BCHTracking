@extends('layouts.app')

@section('title', 'Escanear Entrega')

@section('content')
@php
    $scanRetirerSessionActive = $scanRetirerSessionActive ?? false;
    $scanRetirerSession = is_array($scanRetirerSession ?? null) ? $scanRetirerSession : [];
@endphp
<div class="py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Escanear entrega</h1>
            <p class="mt-2 text-sm text-gray-600">Indique una sola vez quién retira; luego escanee solo el código warehouse de cada paquete.</p>
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

    <div class="max-w-2xl space-y-6">
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
                        <label for="invoice_number" class="block text-sm font-medium text-gray-700">Nº factura *</label>
                        <input type="text" name="invoice_number" id="invoice_number" value="{{ old('invoice_number') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('invoice_number') border-red-500 @enderror" required placeholder="Ej. 17751">
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
                <span class="text-emerald-600 mx-1">·</span>
                <span class="font-semibold">Nº factura:</span> {{ $scanRetirerSession['invoice_number'] ?? '—' }}
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
            <h2 class="text-lg font-semibold text-gray-900 mb-4">2. Escanear código warehouse</h2>
            <form action="{{ route('deliveries.process-scan') }}" method="POST" id="delivery-standalone-scan-form">
                @csrf
                <input type="hidden" name="delivered_to" value="{{ $scanRetirerSession['delivered_to'] ?? '' }}">
                <input type="hidden" name="retirer_id_number" value="{{ $scanRetirerSession['retirer_id_number'] ?? '' }}">
                <input type="hidden" name="retirer_phone" value="{{ $scanRetirerSession['retirer_phone'] ?? '' }}">
                <input type="hidden" name="invoice_number" value="{{ $scanRetirerSession['invoice_number'] ?? '' }}">

                <div class="space-y-6">
                    <div>
                        <label for="warehouse_code" class="block text-sm font-medium text-gray-700">Warehouse (6 dígitos) *</label>
                        <input
                            type="text"
                            name="warehouse_code"
                            id="warehouse_code"
                            value="{{ old('warehouse_code') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-center text-2xl font-mono tracking-widest"
                            required
                            maxlength="6"
                            pattern="\d{6}"
                            placeholder="123456"
                            autofocus
                            inputmode="numeric"
                            autocomplete="off"
                        >
                        <p class="mt-1 text-sm text-gray-500">Con 6 dígitos se envía automáticamente (un bulto por código).</p>
                        @error('warehouse_code')
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
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Anti doble-submit en el formulario del retirante (paso 1)
    var retirerBtn = document.getElementById('btn-scan-retirer-submit');
    if (retirerBtn && retirerBtn.form) {
        retirerBtn.form.addEventListener('submit', function() {
            retirerBtn.disabled = true;
            retirerBtn.textContent = 'Guardando…';
        });
    }

    var form = document.getElementById('delivery-standalone-scan-form');
    var input = document.getElementById('warehouse_code');
    var scanBtn = document.getElementById('btn-standalone-scan-submit');
    if (!form || !input) return;

    if (document.getElementById('delivery-scan-error')) {
        input.value = '';
        input.removeAttribute('readonly');
    }
    input.focus();

    function digits(v) { return (v || '').replace(/\D/g, ''); }

    var submitting = false;
    function submitOnce() {
        if (submitting) return;
        submitting = true;
        if (scanBtn) { scanBtn.disabled = true; scanBtn.textContent = 'Registrando…'; }
        input.setAttribute('readonly', 'readonly');
        form.submit();
    }

    input.addEventListener('input', function() {
        var d = digits(this.value);
        if (d.length > 6) d = d.slice(0, 6);
        this.value = d;
        if (d.length === 6) submitOnce();
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var d = digits(this.value);
            if (d.length === 6) submitOnce();
        }
    });

    form.addEventListener('submit', function() {
        if (submitting) return;
        submitting = true;
        if (scanBtn) { scanBtn.disabled = true; scanBtn.textContent = 'Registrando…'; }
    });
});
</script>
@endpush
@endsection
