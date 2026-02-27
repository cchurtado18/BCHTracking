@extends('layouts.app')

@section('title', 'Escanear Entrega')

@section('content')
<div class="py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Escanear Entrega</h1>
            <p class="mt-2 text-sm text-gray-600">Escanea el código warehouse para entregar un paquete</p>
        </div>
        <a href="{{ route('deliveries.index') }}" class="text-gray-600 hover:text-gray-900">
            ← Volver
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="bg-white shadow rounded-lg p-6">
            <form action="{{ route('deliveries.process-scan') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <div>
                        <label for="warehouse_code" class="block text-sm font-medium text-gray-700">Warehouse Code (6 dígitos) *</label>
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
                        >
                        <p class="mt-1 text-sm text-gray-500">Escanea o ingresa el código de 6 dígitos del paquete</p>
                        @error('warehouse_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="delivered_to" class="block text-sm font-medium text-gray-700">Nombre de quien retira *</label>
                        <input 
                            type="text" 
                            name="delivered_to" 
                            id="delivered_to" 
                            value="{{ old('delivered_to') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                            required
                            placeholder="Nombre completo"
                        >
                        @error('delivered_to')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="retirer_id_number" class="block text-sm font-medium text-gray-700">Cédula de quien retira *</label>
                            <input type="text" name="retirer_id_number" id="retirer_id_number" value="{{ old('retirer_id_number') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required placeholder="Nº cédula">
                            @error('retirer_id_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="retirer_phone" class="block text-sm font-medium text-gray-700">Teléfono de quien retira *</label>
                            <input type="text" name="retirer_phone" id="retirer_phone" value="{{ old('retirer_phone') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required placeholder="Nº telefónico">
                            @error('retirer_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="delivery_type" class="block text-sm font-medium text-gray-700">Tipo de Entrega</label>
                        <select name="delivery_type" id="delivery_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="PICKUP" {{ old('delivery_type', 'PICKUP') == 'PICKUP' ? 'selected' : '' }}>Retiro en Almacén</option>
                            <option value="DELIVERY" {{ old('delivery_type') == 'DELIVERY' ? 'selected' : '' }}>Entrega a Domicilio</option>
                        </select>
                        @error('delivery_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notas</label>
                        <textarea 
                            name="notes" 
                            id="notes" 
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            placeholder="Notas adicionales sobre la entrega"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('deliveries.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Procesar Entrega
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-focus en el campo de código
    document.getElementById('warehouse_code').focus();
</script>
@endpush
@endsection

