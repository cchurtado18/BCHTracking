@extends('layouts.app')

@section('title', 'Editar Consolidación')

@section('content')
<div class="py-6">
    <x-module-banner
        section="Operaciones"
        current="Editar {{ $consolidation->unitNoun() }}"
        title="Editar {{ $consolidation->unitNoun() }}"
        subtitle="{{ $consolidation->code }}. Ajuste la guía o el número de contenedor y las observaciones."
        back-href="{{ route('consolidations.show', $consolidation->id) }}"
        back-label="Volver al {{ $consolidation->unitNoun() }}"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
        </x-slot:icon>
    </x-module-banner>

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
                        <label for="transport_number" class="block text-sm font-medium text-gray-700">{{ $consolidation->transportNumberLabel() }}</label>
                        <input
                            type="text"
                            name="transport_number"
                            id="transport_number"
                            value="{{ old('transport_number', $consolidation->transport_number) }}"
                            required
                            maxlength="80"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                        @error('transport_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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

