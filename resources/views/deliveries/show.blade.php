@extends('layouts.app')

@section('title', 'Detalle de salida')

@section('content')
<div class="delivery-page delivery-show-page">
    <x-module-banner
        section="Operaciones"
        current="Detalle de salida"
        title="Salida #{{ $delivery->id }}"
        subtitle="Paquete registrado en la hoja {{ $delivery->deliveryNote?->code ?? 'de salida' }} · {{ $delivery->preregistration?->label_name ?? 'Sin etiqueta' }}."
        back-href="{{ route('salidas.index', session('deliveries_index_filters', [])) }}"
        back-label="Volver a Salidas"
        :hide-back="(bool) auth()->user()?->isAgencyUser()"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h11.25v10.5H3.75V6.75Zm11.25 3h3.19a1.5 1.5 0 0 1 1.22.63l1.59 2.24v4.63H15V9.75ZM7.5 18.75a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm12 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

    <div class="delivery-show-grid">
        <div class="delivery-card">
            <div class="delivery-card-header delivery-table-header">
                <h2 class="delivery-card-title">Información de la salida</h2>
            </div>
            <div class="delivery-card-body">
                <dl class="delivery-dl">
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Fecha de salida</dt>
                        <dd class="delivery-dd">{{ $delivery->delivered_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Nombre de quien retira</dt>
                        <dd class="delivery-dd">{{ $delivery->delivered_to }}</dd>
                    </div>
                    @if($delivery->retirer_id_number)
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Cédula de quien retira</dt>
                        <dd class="delivery-dd">{{ $delivery->retirer_id_number }}</dd>
                    </div>
                    @endif
                    @if($delivery->retirer_phone)
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Teléfono de quien retira</dt>
                        <dd class="delivery-dd">{{ $delivery->retirer_phone }}</dd>
                    </div>
                    @endif
                    @if($delivery->deliveryNote)
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Hoja de salida</dt>
                        <dd class="delivery-dd"><span class="delivery-code">{{ $delivery->deliveryNote->code }}</span></dd>
                    </div>
                    @endif
                    @if($delivery->notes)
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Notas</dt>
                        <dd class="delivery-dd">{{ $delivery->notes }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="delivery-card">
            <div class="delivery-card-header delivery-table-header">
                <h2 class="delivery-card-title">Información del paquete</h2>
            </div>
            <div class="delivery-card-body">
                <dl class="delivery-dl">
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Código</dt>
                        <dd class="delivery-dd delivery-code">{{ $delivery->preregistration->warehouse_code ?? 'N/A' }}</dd>
                    </div>
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Nombre en etiqueta</dt>
                        <dd class="delivery-dd">{{ $delivery->preregistration->label_name }}</dd>
                    </div>
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Tipo de servicio</dt>
                        <dd class="delivery-dd">
                            <span class="delivery-badge delivery-badge-{{ strtolower($delivery->preregistration->service_type ?? '') }}">{{ \App\Support\ServiceType::label($delivery->preregistration->service_type) }}</span>
                        </dd>
                    </div>
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Estado</dt>
                        <dd class="delivery-dd">
                            <span class="delivery-badge delivery-badge-delivery">{{ $delivery->preregistration->status }}</span>
                        </dd>
                    </div>
                    @if($delivery->preregistration->agency)
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Agencia</dt>
                        <dd class="delivery-dd">
                            <a href="{{ route('agencies.show', $delivery->preregistration->agency_id) }}" class="delivery-link">{{ $delivery->preregistration->agency->name }} ({{ $delivery->preregistration->agency->code }})</a>
                        </dd>
                    </div>
                    @endif
                    @if($delivery->preregistration->agencyClient)
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Cliente interno</dt>
                        <dd class="delivery-dd">
                            <a href="{{ route('agency-clients.show', $delivery->preregistration->agency_client_id) }}" class="delivery-link">{{ $delivery->preregistration->agencyClient->full_name }}</a>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>

<style>
.delivery-show-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.delivery-show-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
@media (min-width: 992px) { .delivery-show-grid { grid-template-columns: 1fr 1fr; } }
.delivery-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem; }
.delivery-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
.delivery-card-header.delivery-table-header { background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%); padding: 0.75rem 1.5rem; }
.delivery-card-header.delivery-table-header .delivery-card-title { color: #fff; }
.delivery-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.delivery-card-body { padding: 1.25rem; }
.delivery-dl { margin: 0; }
.delivery-dl-row { margin-bottom: 1rem; }
.delivery-dt { font-size: 0.8125rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem; }
.delivery-dd { margin: 0; font-size: 0.9375rem; color: #111827; }
.delivery-code { font-family: ui-monospace, monospace; font-weight: 600; }
.delivery-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.delivery-badge-pickup { background: #dbeafe; color: #1d4ed8; }
.delivery-badge-delivery { background: #E8EEF8; color: #0A2D6F; }
.delivery-badge-air { background: #dbeafe; color: #1d4ed8; }
.delivery-badge-sea { background: #E8EEF8; color: #0A2D6F; }
.delivery-link { color: #0A2D6F; text-decoration: none; font-weight: 500; }
.delivery-link:hover { text-decoration: underline; }
</style>
@endsection
