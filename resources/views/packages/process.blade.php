@extends('layouts.app')

@section('title', 'Procesar Paquete')

@section('content')
<div class="py-6">
    <x-module-banner
        section="General"
        current="Procesar"
        title="Procesar paquete #{{ $package->id }}"
        subtitle="Verifique el peso. La agencia del preregistro se conserva salvo que elija otra."
        back-href="{{ route('packages.show', $package->id) }}"
        back-label="Volver al paquete"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

    <div class="max-w-2xl">
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Información del Paquete</h2>
            <dl class="space-y-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $package->label_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Código</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900">{{ $package->warehouse_code ?? $package->tracking_external ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Peso Actual</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $package->intake_weight_lbs }} lbs</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Agencia del preregistro</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($package->agency)
                            {{ $package->agency->name }}@if($package->agency->code) ({{ $package->agency->code }})@endif
                        @else
                            <span class="text-amber-700">Sin agencia — deberá elegir una al procesar</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        @if($package->warehouse_code && (!auth()->user() || !auth()->user()->isAgencyUser()))
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-2">Etiqueta que se reimprimirá</h2>
            <p class="text-sm text-gray-500 mb-4">Al procesar se actualizará el peso en la etiqueta. Esta es la etiqueta actual del paquete.</p>
            <a href="{{ route('preregistrations.label', $package->id) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium mb-4">
                🖨️ Ver / Imprimir etiqueta
            </a>
            <div class="border border-gray-200 rounded-lg overflow-hidden bg-gray-50" style="max-width: 420px;">
                <iframe src="{{ route('preregistrations.label', $package->id) }}?embed=1" title="Vista previa etiqueta" class="w-full" style="height: 680px; border: none;"></iframe>
            </div>
        </div>
        @endif

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Procesar</h2>
            <form action="{{ route('packages.process', $package->id) }}" method="POST">
                @csrf

                <div class="space-y-6">
                    @php
                        $processAgencyOld = old('agency_id');
                        if ($processAgencyOld === null) {
                            $processAgencySelected = $package->agency_id !== null ? (string) $package->agency_id : '';
                        } else {
                            $processAgencySelected = (string) $processAgencyOld;
                        }
                        $processAgencyBlankSelected = $package->agency_id && $processAgencySelected === '';
                        $processAgencyPlaceholderSelected = ! $package->agency_id && $processAgencySelected === '';
                    @endphp
                    <div>
                        <label for="agency_id" class="block text-sm font-medium text-gray-700">Cambiar agencia (opcional)</label>
                        <select name="agency_id" id="agency_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="" @selected($processAgencyBlankSelected || $processAgencyPlaceholderSelected)>{{ $package->agency_id ? 'Mantener agencia del preregistro' : 'Seleccione una agencia' }}</option>
                            @foreach($agencies as $agency)
                                <option value="{{ $agency->id }}" @selected(! $processAgencyBlankSelected && (string) $agency->id === $processAgencySelected)>
                                    {{ $agency->name }} @if($agency->code)({{ $agency->code }})@endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Solo el peso verificado es obligatorio. Use esta lista si debe corregir la agencia.</p>
                        @error('agency_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="verified_weight_lbs" class="block text-sm font-medium text-gray-700">Peso Verificado (lbs) *</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="verified_weight_lbs" 
                            id="verified_weight_lbs" 
                            value="{{ old('verified_weight_lbs', $package->intake_weight_lbs) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                            required
                            min="0.01"
                        >
                        <p class="mt-1 text-sm text-gray-500">Peso real del paquete al llegar a Nicaragua</p>
                        @error('verified_weight_lbs')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('packages.show', $package->id) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Procesar y Generar Etiqueta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

