@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('content')
<div class="py-6">
    <x-module-banner
        section="Administración"
        current="Editar destinatario"
        title="Editar destinatario"
        subtitle="{{ $client->full_name }}. Actualice nombre y datos de contacto."
        back-href="{{ route('agency-clients.show', $client->id) }}"
        back-label="Volver a la ficha"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
        </x-slot:icon>
    </x-module-banner>

    <div class="max-w-2xl">
        <div class="bg-white shadow rounded-lg p-6">
            <form action="{{ route('agency-clients.update', $client->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Nombre Completo *</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            id="full_name" 
                            value="{{ old('full_name', $client->full_name) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                            required
                        >
                        @error('full_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input 
                            type="text" 
                            name="phone" 
                            id="phone" 
                            value="{{ old('phone', $client->phone) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="is_active" 
                                value="1"
                                {{ old('is_active', $client->is_active) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            >
                            <span class="ml-2 text-sm text-gray-700">Activo</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('agency-clients.show', $client->id) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
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

