@extends('layouts.app')

@section('title', 'Detalle Entrega')

@section('content')
<div class="delivery-page delivery-show-page">
    <header class="delivery-hero">
        <div class="delivery-hero-inner">
            <div class="delivery-hero-text">
                <h1 class="delivery-hero-title">Entrega #{{ $delivery->id }}</h1>
                <p class="delivery-hero-subtitle">Detalle de la entrega</p>
            </div>
            <a href="{{ route('deliveries.index', session('deliveries_index_filters', [])) }}" class="delivery-hero-btn">← Volver</a>
        </div>
    </header>

    <div class="delivery-show-grid">
        <div class="delivery-card">
            <div class="delivery-card-header delivery-table-header">
                <h2 class="delivery-card-title">Información de entrega</h2>
            </div>
            <div class="delivery-card-body">
                <dl class="delivery-dl">
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Fecha de entrega</dt>
                        <dd class="delivery-dd">{{ $delivery->delivered_at->format('d/m/Y H:i') }}</dd>
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
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Tipo de entrega</dt>
                        <dd class="delivery-dd">
                            <span class="delivery-badge delivery-badge-{{ strtolower($delivery->delivery_type ?? '') }}">{{ $delivery->delivery_type == 'PICKUP' ? 'Retiro en almacén' : 'Entrega a domicilio' }}</span>
                        </dd>
                    </div>
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
                        <dt class="delivery-dt">Warehouse code</dt>
                        <dd class="delivery-dd delivery-code">{{ $delivery->preregistration->warehouse_code ?? 'N/A' }}</dd>
                    </div>
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Nombre en etiqueta</dt>
                        <dd class="delivery-dd">{{ $delivery->preregistration->label_name }}</dd>
                    </div>
                    <div class="delivery-dl-row">
                        <dt class="delivery-dt">Tipo de servicio</dt>
                        <dd class="delivery-dd">
                            <span class="delivery-badge delivery-badge-{{ strtolower($delivery->preregistration->service_type ?? '') }}">{{ $delivery->preregistration->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span>
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
.delivery-show-page .delivery-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.delivery-show-page .delivery-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.delivery-show-page .delivery-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.delivery-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.delivery-hero-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none; }
.delivery-hero-btn:hover { background: #f0fdfa; color: #0d9488; }
.delivery-show-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
@media (min-width: 992px) { .delivery-show-grid { grid-template-columns: 1fr 1fr; } }
.delivery-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem; }
.delivery-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
.delivery-card-header.delivery-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); padding: 0.75rem 1.5rem; }
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
.delivery-badge-delivery { background: #d1fae5; color: #047857; }
.delivery-badge-air { background: #dbeafe; color: #1d4ed8; }
.delivery-badge-sea { background: #d1fae5; color: #047857; }
.delivery-link { color: #0d9488; text-decoration: none; font-weight: 500; }
.delivery-link:hover { text-decoration: underline; }
</style>
@endsection
