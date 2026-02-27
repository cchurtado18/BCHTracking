@extends('layouts.app')

@section('title', 'Procesar Paquete')

@section('content')
<div class="py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Procesar Paquete #{{ $package->id }}</h1>
            <p class="mt-2 text-sm text-gray-600">Asignar agencia y verificar peso</p>
        </div>
        <a href="{{ route('packages.show', $package->id) }}" class="text-gray-600 hover:text-gray-900">
            ← Volver
        </a>
    </div>

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
                    <div>
                        <label for="agency_id" class="block text-sm font-medium text-gray-700">Agencia *</label>
                        <select name="agency_id" id="agency_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">Seleccione una agencia</option>
                            @foreach($agencies as $agency)
                                <option value="{{ $agency->id }}" {{ old('agency_id') == $agency->id ? 'selected' : '' }}>
                                    {{ $agency->name }} @if($agency->code)({{ $agency->code }})@endif
                                </option>
                            @endforeach
                        </select>
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

