@extends('layouts.app')

@section('title', 'Detalle Cliente')

@section('content')
<div class="py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $client->full_name }}</h1>
            <p class="mt-2 text-sm text-gray-600">Cliente de {{ $client->agency->name }}</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('agency-clients.edit', $client->id) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Editar
            </a>
            <a href="{{ route('agency-clients.index', $client->agency_id) }}" class="text-gray-600 hover:text-gray-900">
                ← Volver
            </a>
        </div>
    </div>

    <div class="max-w-2xl">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Información</h2>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nombre Completo</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $client->full_name }}</dd>
                </div>
                @if($client->phone)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $client->phone }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">Agencia</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <a href="{{ route('agencies.show', $client->agency_id) }}" class="text-blue-600 hover:text-blue-900">
                            {{ $client->agency->name }} ({{ $client->agency->code }})
                        </a>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                    <dd class="mt-1">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $client->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="mt-6 border-t pt-6">
                <form action="{{ route('agency-clients.toggle', $client->id) }}" method="POST" class="inline-block">
                    @csrf
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        {{ $client->is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

