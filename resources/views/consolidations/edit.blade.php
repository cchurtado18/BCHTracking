@extends('layouts.app')

@section('title', 'Editar Consolidación')

@section('content')
<div class="py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Editar Consolidación</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $consolidation->code }}</p>
        </div>
        <a href="{{ route('consolidations.show', $consolidation->id) }}" class="text-gray-600 hover:text-gray-900">
            ← Volver
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="bg-white shadow rounded-lg p-6">
            <form action="{{ route('consolidations.update', $consolidation->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código</label>
                        <input 
                            type="text" 
                            value="{{ $consolidation->code }}" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" 
                            disabled
                        >
                        <p class="mt-1 text-sm text-gray-500">El código no se puede modificar</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo de Servicio</label>
                        <input 
                            type="text" 
                            value="{{ $consolidation->service_type }}" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" 
                            disabled
                        >
                        <p class="mt-1 text-sm text-gray-500">El tipo de servicio no se puede modificar</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <input 
                            type="text" 
                            value="{{ $consolidation->status }}" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" 
                            disabled
                        >
                        <p class="mt-1 text-sm text-gray-500">El estado se modifica mediante acciones específicas</p>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notas</label>
                        <textarea 
                            name="notes" 
                            id="notes" 
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            placeholder="Notas adicionales sobre la consolidación"
                        >{{ old('notes', $consolidation->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('consolidations.show', $consolidation->id) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

