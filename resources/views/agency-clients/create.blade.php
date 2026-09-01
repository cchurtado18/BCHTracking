@extends('layouts.app')

@section('title', 'Crear Cliente')

@section('content')
<div class="py-6">
    <x-module-banner
        section="Administración"
        current="Nuevo destinatario"
        title="Crear destinatario"
        subtitle="Quedará asignado a {{ $agency->name }} ({{ $agency->code }}){{ $agency->parent ? ' · Subagencia de '.$agency->parent->name : '' }}."
        back-href="{{ route('agencies.show', $agency->id) }}"
        back-label="Volver a la cuenta"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.011a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

    <div class="max-w-2xl">
        <div class="bg-white shadow rounded-lg p-6">
            <form action="{{ route('agency-clients.store', $agency->id) }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Nombre Completo *</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            id="full_name" 
                            value="{{ old('full_name') }}"
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
                            value="{{ old('phone') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('agency-clients.index', $agency->id) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Crear Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

