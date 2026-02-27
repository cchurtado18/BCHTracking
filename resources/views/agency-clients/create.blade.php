@extends('layouts.app')

@section('title', 'Crear Cliente')

@section('content')
<div class="py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Crear Cliente</h1>
            <p class="mt-2 text-sm text-gray-600">El cliente quedará asignado a <strong>{{ $agency->name }}</strong> ({{ $agency->code }})</p>
            @if($agency->parent)
            <p class="mt-1 text-xs text-gray-500">Subagencia de {{ $agency->parent->name }}</p>
            @endif
        </div>
        <a href="{{ route('agencies.show', $agency->id) }}" class="text-gray-600 hover:text-gray-900">
            ← Volver a la agencia
        </a>
    </div>

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

