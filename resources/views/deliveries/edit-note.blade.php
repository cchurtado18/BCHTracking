@extends('layouts.app')

@section('title', 'Editar nota de entrega')

@section('content')
@php
    $firstDelivery = $firstDelivery ?? $deliveryNote->deliveries->first();
@endphp
<div class="delivery-page delivery-edit-note-page">
    <header class="delivery-hero">
        <div class="delivery-hero-inner">
            <div class="delivery-hero-text">
                <h1 class="delivery-hero-title">Editar nota de entrega</h1>
                <p class="delivery-hero-subtitle">Solo administradores · {{ $deliveryNote->code }}</p>
                <div class="delivery-nota-code">{{ $deliveryNote->code }}</div>
            </div>
            <a href="{{ route('deliveries.index', session('deliveries_index_filters', [])) }}" class="delivery-hero-btn">← Volver a Entregas</a>
        </div>
    </header>

    @if(session('success'))
    <div class="delivery-alert delivery-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="delivery-alert delivery-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="delivery-card">
        <div class="delivery-card-header delivery-table-header">
            <h2 class="delivery-card-title">Datos del retirante</h2>
        </div>
        <div class="delivery-card-body">
            <p class="delivery-hint">Los cambios se aplican a todos los paquetes de esta nota.</p>
            <form action="{{ route('deliveries.notes.update', $deliveryNote) }}" method="POST" class="delivery-retirer-form">
                @csrf
                @method('PUT')
                <div class="delivery-scan-row">
                    <div class="delivery-field">
                        <label for="delivered_to" class="delivery-label">Nombre completo *</label>
                        <input type="text" name="delivered_to" id="delivered_to" value="{{ old('delivered_to', $firstDelivery?->delivered_to) }}" class="delivery-input @error('delivered_to') delivery-input-invalid @enderror" required>
                        @error('delivered_to')<span class="delivery-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="delivery-field">
                        <label for="invoice_number" class="delivery-label">Nº factura (opcional)</label>
                        <input type="text" name="invoice_number" id="invoice_number" value="{{ old('invoice_number', $firstDelivery?->invoice_number) }}" class="delivery-input @error('invoice_number') delivery-input-invalid @enderror" placeholder="Ej. 17751">
                        @error('invoice_number')<span class="delivery-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="delivery-field">
                        <label for="retirer_id_number" class="delivery-label">Cédula (opcional)</label>
                        <input type="text" name="retirer_id_number" id="retirer_id_number" value="{{ old('retirer_id_number', $firstDelivery?->retirer_id_number) }}" class="delivery-input">
                    </div>
                    <div class="delivery-field">
                        <label for="retirer_phone" class="delivery-label">Teléfono (opcional)</label>
                        <input type="text" name="retirer_phone" id="retirer_phone" value="{{ old('retirer_phone', $firstDelivery?->retirer_phone) }}" class="delivery-input">
                    </div>
                    <div class="delivery-field delivery-field-btn">
                        <button type="submit" class="delivery-btn delivery-btn-primary">Guardar cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="delivery-card">
        <div class="delivery-card-header delivery-table-header">
            <h2 class="delivery-card-title">Paquetes en esta nota ({{ $deliveryNote->deliveries->count() }})</h2>
            <a href="{{ route('deliveries.print-report', ['delivery_note_id' => $deliveryNote->id]) }}" target="_blank" class="delivery-btn delivery-btn-sm delivery-btn-outline-light">Ver / imprimir</a>
        </div>
        <div class="delivery-table-wrap">
            <table class="delivery-table">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Cliente (etiqueta)</th>
                        <th>Bulto</th>
                        <th>Servicio</th>
                        <th>Peso (lbs)</th>
                        <th>Entregado</th>
                        <th class="delivery-th-actions">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveryNote->deliveries as $delivery)
                    @php $p = $delivery->preregistration; @endphp
                    <tr>
                        <td><span class="delivery-code">{{ $p?->warehouse_code ?? '—' }}</span></td>
                        <td class="delivery-name-cell" title="{{ $p?->label_name }}">{{ Str::limit($p?->label_name ?? '—', 28) }}</td>
                        <td class="delivery-code">{{ ($p?->bultos_total && $p->bultos_total > 1 && $p->bulto_index) ? $p->bulto_index . '/' . $p->bultos_total : '—' }}</td>
                        <td>
                            @if($p?->service_type)
                            <span class="delivery-badge delivery-badge-{{ strtolower($p->service_type) }}">{{ $p->service_type === 'AIR' ? 'Aéreo' : 'Marítimo' }}</span>
                            @else
                            —
                            @endif
                        </td>
                        <td class="delivery-num">{{ $p?->verified_weight_lbs ?? $p?->intake_weight_lbs ?? '—' }}</td>
                        <td class="delivery-muted">{{ $delivery->delivered_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="delivery-actions">
                            <form action="{{ route('deliveries.notes.remove-delivery', [$deliveryNote, $delivery]) }}" method="POST" class="delivery-remove-form" onsubmit="return confirm('¿Quitar este paquete de la nota? Volverá a «Listo para retiro».');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delivery-btn delivery-btn-sm delivery-btn-danger">Quitar</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="delivery-empty">
                            <p class="delivery-empty-text">Esta nota no tiene paquetes.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.delivery-edit-note-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.delivery-edit-note-page .delivery-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.delivery-edit-note-page .delivery-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.delivery-edit-note-page .delivery-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.delivery-nota-code { font-family: ui-monospace, monospace; font-size: 1.1rem; font-weight: 700; color: #f8fafc; letter-spacing: 0.08em; margin-top: 0.5rem; }
.delivery-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.delivery-hero-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none; }
.delivery-hero-btn:hover { background: #f0fdfa; color: #0d9488; }
.delivery-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.delivery-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.delivery-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.delivery-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.delivery-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.delivery-card-header.delivery-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.delivery-card-header.delivery-table-header .delivery-card-title { color: #fff; }
.delivery-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.delivery-card-body { padding: 1.25rem; }
.delivery-hint { font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem; }
.delivery-scan-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
.delivery-field { min-width: 0; flex: 1 1 160px; }
.delivery-field label.delivery-label { display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.delivery-input { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; width: 100%; }
.delivery-input-invalid { border-color: #f87171; }
.delivery-field-error { display: block; font-size: 0.75rem; color: #b91c1c; margin-top: 0.25rem; }
.delivery-field-btn { flex: 0 0 auto; }
.delivery-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.delivery-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.delivery-btn-primary:hover { background: #0f766e; color: #fff; }
.delivery-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.delivery-btn-outline-light { background: transparent; color: #fff; border-color: rgba(255,255,255,0.7); }
.delivery-btn-outline-light:hover { background: rgba(255,255,255,0.15); color: #fff; }
.delivery-btn-danger { background: #fff; color: #b91c1c; border-color: #fca5a5; }
.delivery-btn-danger:hover { background: #fef2f2; }
.delivery-table-wrap { overflow-x: auto; }
.delivery-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.delivery-table thead tr { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.delivery-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.2); }
.delivery-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.delivery-code { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; }
.delivery-name-cell { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.delivery-num { font-weight: 500; }
.delivery-muted { color: #6b7280; font-size: 0.875rem; }
.delivery-th-actions { text-align: right; }
.delivery-actions { text-align: right; white-space: nowrap; }
.delivery-remove-form { margin: 0; display: inline; }
.delivery-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.delivery-badge-air { background: #dbeafe; color: #1d4ed8; }
.delivery-badge-sea { background: #d1fae5; color: #047857; }
.delivery-empty { text-align: center; padding: 2rem 1rem !important; }
.delivery-empty-text { margin: 0; color: #6b7280; }
</style>
@endsection
