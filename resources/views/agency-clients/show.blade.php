@extends('layouts.app')

@section('title', 'Detalle Cliente')

@section('content')
<div class="py-6">
    <x-module-banner
        section="Administración"
        current="Destinatario"
        title="{{ $client->full_name }}"
        subtitle="Destinatario de {{ $client->agency->name }}. Datos de contacto y paquetes asociados."
        back-href="{{ route('agency-clients.index', $client->agency_id) }}"
        back-label="Volver a destinatarios"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('agency-clients.edit', $client->id) }}" class="mb-btn mb-btn-primary">Editar</a>
        </x-slot:actions>
    </x-module-banner>

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

