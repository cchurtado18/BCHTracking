@extends('layouts.app')

@section('title', 'Detalle Consolidación')

@section('content')
<style>
    .page-banner-show {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
        box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
        border-radius: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .page-banner-show .page-banner-title { color: #fff !important; }
    .page-banner-show .page-banner-subtitle { color: rgba(255,255,255,0.9) !important; }
    .btn-banner-show {
        background: #fff;
        color: #0f766e;
        border: 1px solid rgba(255,255,255,0.5);
        padding: 0.5rem 1rem;
        font-weight: 600;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    .btn-banner-show:hover { background: #f0fdfa; color: #0d9488; }
    .btn-banner-show.btn-emerald { background: #fff; color: #059669; border-color: #dbdcde; }
    .btn-banner-show.btn-emerald:hover { background: #d1fae5; color: #047857; }
    .btn-banner-show.btn-indigo { background: #fff; color: #4f46e5; border-color: #dbdcde; }
    .btn-banner-show.btn-indigo:hover { background: #e0e7ff; color: #4338ca; }
    button.btn-banner-show { cursor: pointer; }
    .card-show {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    .card-show-header {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
        color: #fff;
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        font-size: 1rem;
    }
    .card-show-body { padding: 1.25rem 1.5rem; }
    .table-header-teal {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
        color: #fff;
    }
    .table-header-teal th { color: #fff; font-weight: 600; padding: 0.5rem 0.75rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; }
</style>

<div class="py-4">
    {{-- Banner superior (mismo estilo que preregistro) --}}
    <div class="page-banner-show">
        <div class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold page-banner-title">{{ $consolidation->code }}</h1>
                <p class="mt-0.5 text-sm page-banner-subtitle mb-0">Detalle de la consolidación</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('consolidations.label', $consolidation->id) }}" target="_blank" class="btn-banner-show btn-emerald">🖨️ Etiqueta del saco</a>
                @if($consolidation->status === 'OPEN')
                    <a href="{{ route('consolidations.edit', $consolidation->id) }}" class="btn-banner-show btn-indigo">Editar</a>
                    @if($consolidation->items->count() > 0)
                        <form action="{{ route('consolidations.send', $consolidation->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de enviar este saco? Esto cambiará el estado a SENT y los preregistros pasarán a IN_TRANSIT.');">
                            @csrf
                            <button type="submit" class="btn-banner-show btn-emerald border-0 cursor-pointer">Enviar Saco</button>
                        </form>
                    @endif
                    <form action="{{ route('consolidations.destroy', $consolidation->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este saco? Se quitarán los items y los preregistros quedarán disponibles de nuevo.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-banner-show border-0 cursor-pointer" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">Eliminar saco</button>
                    </form>
                @endif
                <a href="{{ route('consolidations.index', session('consolidations_index_filters', [])) }}" class="btn-banner-show" style="color: #dbdcde; border-color: rgba(255,255,255,0.3);">← Volver</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Card Información + Reporte --}}
        <div class="lg:col-span-2 card-show">
            <div class="card-show-header">Información</div>
            <div class="card-show-body">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Código</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $consolidation->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tipo de Servicio</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $consolidation->service_type == 'AIR' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $consolidation->service_type }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Estado</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $consolidation->status == 'OPEN' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $consolidation->status == 'SENT' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $consolidation->status == 'RECEIVED' ? 'bg-purple-100 text-purple-800' : '' }}">
                                {{ $consolidation->status }}
                            </span>
                        </dd>
                    </div>
                    @if($consolidation->notes)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Notas</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $consolidation->notes }}</dd>
                    </div>
                    @endif
                </dl>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Reporte</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Items</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $report['total_items'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Peso</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($report['total_lbs'], 2) }} lbs</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Escaneados</dt>
                            <dd class="mt-1 text-2xl font-bold text-green-600">{{ $report['scanned_count'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Faltantes</dt>
                            <dd class="mt-1 text-2xl font-bold text-red-600">{{ $report['missing_count'] }}</dd>
                        </div>
                    </div>
                    @if($consolidation->status === 'SENT' && $consolidation->sent_at)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-500">
                                <span class="font-medium">Enviado el:</span> {{ $consolidation->sent_at->format('d/m/Y H:i') }}
                            </p>
                            <p class="text-xs mt-1" style="color: #0d9488;">
                                ✓ Este saco está disponible para escaneo en Nicaragua
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card Items en el saco --}}
        <div class="card-show">
            <div class="card-show-header">Items en el Saco ({{ $consolidation->items->count() }})</div>
            <div class="card-show-body">
                @if($consolidation->items->count() > 0)
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($consolidation->items as $item)
                            <div class="border rounded p-3 {{ $item->scanned_at ? 'bg-green-50 border-green-200' : 'border-gray-200' }}">
                                <div class="text-sm font-medium text-gray-900">{{ $item->preregistration->label_name }}</div>
                                <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $item->preregistration->intake_weight_lbs }} lbs</div>
                                @if($item->scanned_at)
                                    <div class="text-xs text-green-600 mt-1 font-medium">✓ Escaneado</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No hay items en este saco</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Preregistros Disponibles (solo si está OPEN) --}}
    @if($consolidation->status === 'OPEN')
    <div class="mt-6 card-show">
        <div class="card-show-header">Preregistros Disponibles para Agregar ({{ $availablePreregistrations->count() }})</div>
        <div class="card-show-body">
            @if($availablePreregistrations->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200" style="border-collapse: collapse;">
                        <thead class="table-header-teal">
                            <tr>
                                <th>Warehouse</th>
                                <th>Nombre</th>
                                <th>Peso (lbs)</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($availablePreregistrations as $preregistration)
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-3 text-sm font-mono text-gray-600">{{ $preregistration->warehouse_code ?? $preregistration->tracking_external ?? 'N/A' }}</td>
                                <td class="py-2 px-3 text-sm font-medium text-gray-900">{{ $preregistration->label_name }}</td>
                                <td class="py-2 px-3 text-sm text-gray-500">{{ $preregistration->intake_weight_lbs }} lbs</td>
                                <td class="py-2 px-3 text-sm text-gray-500">{{ $preregistration->created_at->format('d/m/Y') }}</td>
                                <td class="py-2 px-3 text-sm">
                                    <form action="{{ route('consolidations.add-item', $consolidation->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="preregistration_id" value="{{ $preregistration->id }}">
                                        <button type="submit" class="text-blue-600 hover:text-blue-900 font-medium">+ Agregar</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 rounded-md" style="border: 2px dashed #dee2e6;">
                    <p class="text-sm text-gray-500">No hay preregistros disponibles para agregar</p>
                    <p class="text-xs text-gray-400 mt-2">Todos los preregistros con estado RECEIVED_MIAMI y tipo {{ $consolidation->service_type }} ya están en otros sacos</p>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
