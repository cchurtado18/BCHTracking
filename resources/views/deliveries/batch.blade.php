@extends('layouts.app')

@section('title', 'Nota de entrega')

@section('content')
@php
    $deliveryNote = $deliveryNote ?? null;
    $retirerSessionActive = $retirerSessionActive ?? false;
    $batchRetirerSession = is_array($batchRetirerSession ?? null) ? $batchRetirerSession : [];
    $deliveredCount = $deliveredCount ?? 0;
    $scannedDeliveries = $scannedDeliveries ?? collect();
    $printReportParams = $deliveryNote
        ? array_merge($filterParams, ['date' => now()->toDateString(), 'delivery_note_id' => $deliveryNote->id])
        : array_merge($filterParams, ['date' => now()->toDateString()]);
@endphp

<div class="delivery-page delivery-batch-page">
    <header class="delivery-hero">
        <div class="delivery-hero-inner">
            <div class="delivery-hero-text">
                <h1 class="delivery-hero-title">Nota de entrega</h1>
                <p class="delivery-hero-subtitle">Entregas para {{ $agencyName }}</p>
                @if($deliveryNote)
                <div class="delivery-nota-code-label">Código de nota de entrega</div>
                <div class="delivery-nota-code">{{ $deliveryNote->code }}</div>
                @endif
            </div>
            <a href="{{ route('deliveries.index', session('deliveries_index_filters', $filterParams)) }}" class="delivery-hero-btn">← Volver a Entregas</a>
        </div>
    </header>

    @if(session('success'))
    <div class="delivery-alert delivery-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div id="delivery-error" class="delivery-alert delivery-alert-danger">{{ session('error') }}</div>
    @endif

    @if(!$retirerSessionActive)
    <div class="delivery-card">
        <div class="delivery-card-header delivery-table-header">
            <h2 class="delivery-card-title">Paquetes a entregar ({{ $availablePackages->count() }})</h2>
        </div>
        <div class="delivery-card-body">
            <p class="delivery-hint">Indique primero quién retira. Luego podrá escanear por <strong>warehouse</strong> o <strong>tracking</strong>; cada escaneo se registra solo.</p>
            @if($availablePackages->isEmpty())
            <p class="delivery-muted">No hay paquetes pendientes de entregar para esta agencia.</p>
            @else
            <div class="delivery-table-wrap">
                <table class="delivery-table">
                    <thead>
                        <tr>
                            <th>Cliente (etiqueta)</th>
                            <th>Warehouse</th>
                            <th>Bulto</th>
                            <th>Tracking</th>
                            <th>Servicio</th>
                            <th>Peso (lbs)</th>
                            <th>Listo desde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($availablePackages as $p)
                        <tr>
                            <td class="delivery-name-cell" title="{{ $p->label_name }}">{{ Str::limit($p->label_name, 28) }}</td>
                            <td><span class="delivery-code">{{ $p->warehouse_code ?? '—' }}</span></td>
                            <td class="delivery-code">{{ ($p->bultos_total && $p->bultos_total > 1 && $p->bulto_index) ? $p->bulto_index . '/' . $p->bultos_total : '—' }}</td>
                            <td class="delivery-code delivery-tracking-cell" title="{{ $p->tracking_external }}">{{ Str::limit($p->tracking_external, 20) }}</td>
                            <td><span class="delivery-badge delivery-badge-{{ strtolower($p->service_type ?? '') }}">{{ $p->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span></td>
                            <td class="delivery-num">{{ $p->verified_weight_lbs ?? $p->intake_weight_lbs ?? '—' }}</td>
                            <td class="delivery-muted">{{ $p->ready_at ? $p->ready_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="delivery-retirer-confirm-box">
                <h3 class="delivery-retirer-step-title">1. Datos de quien retira (una sola vez)</h3>
                <p class="delivery-hint delivery-retirer-step-hint">Indique nombre completo, cédula y teléfono. Después podrá escanear todos los paquetes sin volver a escribir estos datos.</p>
                <form action="{{ route('deliveries.batch-retirer-session') }}" method="POST" class="delivery-retirer-form" id="delivery-retirer-form">
                    @csrf
                    @if($deliveryNote)
                    <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
                    @endif
                    <input type="hidden" name="agency_id" value="{{ $agency->id }}">
                    @if(!empty($filterParams['service_type']))
                    <input type="hidden" name="service_type" value="{{ $filterParams['service_type'] }}">
                    @endif
                    <div class="delivery-scan-row">
                        <div class="delivery-field">
                            <label for="retirer_delivered_to" class="delivery-label">Nombre completo *</label>
                            <input type="text" name="delivered_to" id="retirer_delivered_to" value="{{ old('delivered_to') }}" class="delivery-input @error('delivered_to') delivery-input-invalid @enderror" placeholder="Nombre completo" required autofocus>
                            @error('delivered_to')<span class="delivery-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="delivery-field">
                            <label for="retirer_invoice_number" class="delivery-label">Nº factura (opcional)</label>
                            <input type="text" name="invoice_number" id="retirer_invoice_number" value="{{ old('invoice_number') }}" class="delivery-input @error('invoice_number') delivery-input-invalid @enderror" placeholder="Ej. 17751">
                            @error('invoice_number')<span class="delivery-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="delivery-field">
                            <label for="retirer_id_number_form" class="delivery-label">Cédula (opcional)</label>
                            <input type="text" name="retirer_id_number" id="retirer_id_number_form" value="{{ old('retirer_id_number') }}" class="delivery-input @error('retirer_id_number') delivery-input-invalid @enderror" placeholder="Nº cédula">
                            @error('retirer_id_number')<span class="delivery-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="delivery-field">
                            <label for="retirer_phone_form" class="delivery-label">Teléfono (opcional)</label>
                            <input type="text" name="retirer_phone" id="retirer_phone_form" value="{{ old('retirer_phone') }}" class="delivery-input @error('retirer_phone') delivery-input-invalid @enderror" placeholder="Nº telefónico">
                            @error('retirer_phone')<span class="delivery-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="delivery-field delivery-field-btn">
                            <button type="submit" class="delivery-btn delivery-btn-primary" id="btn-retirer-submit">Guardar y escanear paquetes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @elseif($deliveryNote)
    <div class="delivery-retirer-banner">
        <div class="delivery-retirer-banner-text">
            <strong>Quien retira:</strong> {{ $batchRetirerSession['delivered_to'] ?? '—' }}
            @if(filled($batchRetirerSession['invoice_number'] ?? null))
            <span class="delivery-retirer-banner-sep">·</span>
            <strong>Nº factura:</strong> {{ $batchRetirerSession['invoice_number'] }}
            @endif
            @if(filled($batchRetirerSession['retirer_id_number'] ?? null))
            <span class="delivery-retirer-banner-sep">·</span>
            <strong>Cédula:</strong> {{ $batchRetirerSession['retirer_id_number'] }}
            @endif
            @if(filled($batchRetirerSession['retirer_phone'] ?? null))
            <span class="delivery-retirer-banner-sep">·</span>
            <strong>Tel.:</strong> {{ $batchRetirerSession['retirer_phone'] }}
            @endif
        </div>
        <form action="{{ route('deliveries.batch-clear-retirer-session') }}" method="POST" class="delivery-retirer-banner-clear">
            @csrf
            <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
            <input type="hidden" name="agency_id" value="{{ $agency->id }}">
            @if(!empty($filterParams['service_type']))
            <input type="hidden" name="service_type" value="{{ $filterParams['service_type'] }}">
            @endif
            <button type="submit" class="delivery-btn delivery-btn-ghost">Cambiar persona que retira</button>
        </form>
    </div>

    @if($deliveredCount > 0)
    <div class="delivery-close-note-banner">
        <div class="delivery-close-note-text">
            <strong>{{ $deliveredCount }}</strong> {{ $deliveredCount === 1 ? 'paquete entregado' : 'paquetes entregados' }} en esta nota.
            @if(! $availablePackages->isEmpty())
            Quedan {{ $availablePackages->count() }} {{ $availablePackages->count() === 1 ? 'pendiente' : 'pendientes' }}.
            @else
            Ya no hay más paquetes pendientes para esta agencia.
            @endif
        </div>
        <a href="{{ route('deliveries.print-report', $printReportParams) }}" target="_blank" class="delivery-btn delivery-btn-primary delivery-btn-close-note">Cerrar nota e imprimir</a>
    </div>
    @endif

    <div class="delivery-workbench">
        <section class="delivery-scan-panel">
            <h2 class="delivery-panel-title">Escanear</h2>
            <form action="{{ route('deliveries.process-scan') }}" method="POST" class="delivery-scan-form" id="delivery-batch-scan-form">
                @csrf
                <input type="hidden" name="return_to_batch" value="1">
                <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
                <input type="hidden" name="agency_id" value="{{ $filterParams['agency_id'] ?? $agency->id }}">
                @if(!empty($filterParams['service_type']))
                <input type="hidden" name="service_type" value="{{ $filterParams['service_type'] }}">
                @endif
                <input type="hidden" name="delivered_to" value="{{ $batchRetirerSession['delivered_to'] ?? '' }}">
                <input type="hidden" name="retirer_id_number" value="{{ $batchRetirerSession['retirer_id_number'] ?? '' }}">
                <input type="hidden" name="retirer_phone" value="{{ $batchRetirerSession['retirer_phone'] ?? '' }}">
                <input type="hidden" name="invoice_number" value="{{ $batchRetirerSession['invoice_number'] ?? '' }}">
                <label for="scan_code" class="delivery-label">Warehouse o tracking</label>
                <input type="text" name="code" id="scan_code" value="{{ old('code', old('warehouse_code')) }}" class="delivery-input delivery-input-scan-lg" placeholder="Escanee aquí" maxlength="100" required autofocus autocomplete="off">
                <div class="delivery-field" id="bulto-select-wrap" style="display: none; margin-top: 0.75rem;">
                    <label for="bulto_index" class="delivery-label">Bulto (varios con este código) *</label>
                    <select name="bulto_index" id="bulto_index" class="delivery-select">
                        <option value="">— Seleccione —</option>
                    </select>
                </div>
                <button type="submit" class="delivery-btn delivery-btn-scan delivery-btn-scan-block" id="btn-register-delivery">Registrar entrega</button>
            </form>
            <p class="delivery-scan-hint">Use la pistola: warehouse o tracking se registran solos al terminar de leer el código. Si varios bultos comparten warehouse, elija cuál está entregando.</p>
            <p class="delivery-scan-print">
                <a href="{{ route('deliveries.print-report', $printReportParams) }}" target="_blank" class="delivery-link">Imprimir nota ({{ $deliveryNote->code }})</a>
            </p>
        </section>

        <section class="delivery-scanned-panel" id="delivery-scanned-panel">
            <div class="delivery-scanned-panel-head">
                <h2 class="delivery-panel-title delivery-panel-title-light">Escaneados</h2>
                <span class="delivery-scanned-count">{{ $scannedDeliveries->count() }}</span>
            </div>
            @if($scannedDeliveries->isEmpty())
            <div class="delivery-scanned-empty">
                <p>Los paquetes van a aparecer aquí conforme los escanee.</p>
            </div>
            @else
            <ol class="delivery-scanned-list">
                @foreach($scannedDeliveries as $i => $delivery)
                    @php $pkg = $delivery->preregistration; @endphp
                    <li class="delivery-scanned-item {{ $i === 0 ? 'is-latest' : '' }}">
                        <div class="delivery-scanned-item-num">{{ $scannedDeliveries->count() - $i }}</div>
                        <div class="delivery-scanned-item-body">
                            <div class="delivery-scanned-item-name" title="{{ $pkg?->label_name }}">{{ $pkg?->label_name ?: 'Sin nombre' }}</div>
                            <div class="delivery-scanned-item-meta">
                                <span class="delivery-code">{{ $pkg?->warehouse_code ?? '—' }}</span>
                                @if($pkg?->bultos_total && $pkg->bultos_total > 1 && $pkg->bulto_index)
                                <span class="delivery-scanned-chip">{{ $pkg->bulto_index }}/{{ $pkg->bultos_total }}</span>
                                @endif
                                @if($pkg?->tracking_external)
                                <span class="delivery-code delivery-scanned-tracking" title="{{ $pkg->tracking_external }}">{{ Str::limit($pkg->tracking_external, 24) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="delivery-scanned-item-time">{{ $delivery->delivered_at?->timezone(config('app.display_timezone'))->format('H:i:s') ?? '—' }}</div>
                    </li>
                @endforeach
            </ol>
            @endif
        </section>
    </div>

    <div class="delivery-card delivery-pending-card">
        <div class="delivery-card-header delivery-table-header">
            <h2 class="delivery-card-title">Pendientes ({{ $availablePackages->count() }})</h2>
        </div>
        <div class="delivery-card-body">
            @if($availablePackages->isEmpty())
            <p class="delivery-muted" style="margin:0;">No quedan paquetes pendientes para esta agencia.</p>
            @else
            <div class="delivery-table-wrap" style="margin-bottom:0;">
                <table class="delivery-table">
                    <thead>
                        <tr>
                            <th>Cliente (etiqueta)</th>
                            <th>Warehouse</th>
                            <th>Bulto</th>
                            <th>Tracking</th>
                            <th>Servicio</th>
                            <th>Peso (lbs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($availablePackages as $p)
                        <tr>
                            <td class="delivery-name-cell" title="{{ $p->label_name }}">{{ Str::limit($p->label_name, 28) }}</td>
                            <td><span class="delivery-code">{{ $p->warehouse_code ?? '—' }}</span></td>
                            <td class="delivery-code">{{ ($p->bultos_total && $p->bultos_total > 1 && $p->bulto_index) ? $p->bulto_index . '/' . $p->bultos_total : '—' }}</td>
                            <td class="delivery-code delivery-tracking-cell" title="{{ $p->tracking_external }}">{{ Str::limit($p->tracking_external, 20) }}</td>
                            <td><span class="delivery-badge delivery-badge-{{ strtolower($p->service_type ?? '') }}">{{ $p->service_type == 'AIR' ? 'Aéreo' : 'Marítimo' }}</span></td>
                            <td class="delivery-num">{{ $p->verified_weight_lbs ?? $p->intake_weight_lbs ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

<style>
.delivery-batch-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.delivery-batch-page .delivery-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.delivery-batch-page .delivery-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.delivery-batch-page .delivery-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.delivery-nota-code-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.8); margin-top: 0.5rem; margin-bottom: 0.25rem; }
.delivery-nota-code { font-family: ui-monospace, monospace; font-size: 1.25rem; font-weight: 700; color: #f8fafc; letter-spacing: 0.08em; background: rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.2); display: inline-block; margin-top: 0.25rem; }
.delivery-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.delivery-hero-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none; }
.delivery-hero-btn:hover { background: #f0fdfa; color: #0d9488; }
.delivery-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.delivery-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.delivery-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.delivery-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.delivery-card-header.delivery-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); padding: 0.75rem 1.5rem; }
.delivery-card-header.delivery-table-header .delivery-card-title { color: #fff; margin: 0; font-size: 1.05rem; }
.delivery-card-body { padding: 1.25rem; }
.delivery-hint, .delivery-muted { font-size: 0.875rem; color: #6b7280; margin: 0 0 0.5rem; }
.delivery-table-wrap { overflow-x: auto; margin-bottom: 1.5rem; }
.delivery-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.delivery-table thead tr { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.delivery-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.2); }
.delivery-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; }
.delivery-code { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; }
.delivery-name-cell { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.delivery-tracking-cell { max-width: 140px; }
.delivery-num { font-weight: 500; }
.delivery-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.delivery-badge-air { background: #dbeafe; color: #1d4ed8; }
.delivery-badge-sea { background: #d1fae5; color: #047857; }
.delivery-retirer-confirm-box {
    background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
    border: 2px solid #d97706; border-radius: 0.75rem; padding: 1.25rem; margin-top: 1rem;
}
.delivery-retirer-step-title { margin: 0 0 0.5rem; font-size: 1rem; font-weight: 600; color: #92400e; }
.delivery-retirer-step-hint { margin-bottom: 1rem !important; color: #78350f !important; }
.delivery-retirer-form { margin: 0; }
.delivery-field-error { display: block; font-size: 0.75rem; color: #b91c1c; margin-top: 0.25rem; }
.delivery-input-invalid { border-color: #f87171 !important; }
.delivery-retirer-banner {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;
    background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 0.75rem; padding: 0.875rem 1rem; margin-bottom: 1rem; font-size: 0.875rem; color: #065f46;
}
.delivery-retirer-banner-text { flex: 1; min-width: 0; line-height: 1.5; }
.delivery-retirer-banner-sep { color: #34d399; margin: 0 0.25rem; }
.delivery-retirer-banner-clear { margin: 0; }
.delivery-btn-ghost {
    background: #fff; color: #047857; border: 1px solid #6ee7b7; font-size: 0.8125rem; padding: 0.4rem 0.75rem;
}
.delivery-btn-ghost:hover { background: #f0fdf4; }
.delivery-close-note-banner {
    margin-bottom: 1rem;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    border: 2px solid #2563eb;
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    color: #1e3a8a;
    font-size: 0.9375rem;
    line-height: 1.4;
}
.delivery-close-note-text { flex: 1 1 280px; min-width: 0; }
.delivery-btn-close-note { background: #2563eb; border-color: #2563eb; color: #fff; font-weight: 600; padding: 0.55rem 1.1rem; white-space: nowrap; }
.delivery-btn-close-note:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }

.delivery-workbench {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 1.25rem;
    align-items: start;
}
@media (min-width: 960px) {
    .delivery-workbench { grid-template-columns: minmax(280px, 360px) 1fr; }
}
.delivery-scan-panel {
    background: linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 2px solid #059669;
    border-radius: 0.75rem;
    padding: 1.25rem;
    position: sticky;
    top: 0.75rem;
}
.delivery-scanned-panel {
    background: #fff;
    border: 2px solid #0d9488;
    border-radius: 0.75rem;
    overflow: hidden;
    min-height: 280px;
    display: flex;
    flex-direction: column;
}
.delivery-scanned-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    padding: 0.75rem 1rem;
}
.delivery-panel-title { margin: 0 0 0.85rem; font-size: 1.05rem; font-weight: 700; color: #047857; }
.delivery-panel-title-light { margin: 0; color: #fff; }
.delivery-scanned-count {
    min-width: 2rem; height: 2rem; padding: 0 0.55rem;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 999px; background: rgba(255,255,255,0.2); color: #fff;
    font-weight: 800; font-size: 0.95rem;
}
.delivery-scanned-empty {
    flex: 1; display: flex; align-items: center; justify-content: center;
    padding: 2rem 1.25rem; text-align: center; color: #64748b; font-size: 0.9375rem;
}
.delivery-scanned-empty p { margin: 0; max-width: 16rem; }
.delivery-scanned-list {
    list-style: none; margin: 0; padding: 0;
    max-height: 420px; overflow-y: auto;
}
.delivery-scanned-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 0.75rem;
    align-items: center;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #e5e7eb;
}
.delivery-scanned-item.is-latest {
    background: #ecfdf5;
    box-shadow: inset 3px 0 0 #059669;
}
.delivery-scanned-item-num {
    width: 2rem; height: 2rem; border-radius: 999px;
    background: #ccfbf1; color: #0f766e;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 0.85rem;
}
.delivery-scanned-item.is-latest .delivery-scanned-item-num { background: #059669; color: #fff; }
.delivery-scanned-item-name { font-weight: 700; color: #0f172a; font-size: 0.95rem; line-height: 1.25; }
.delivery-scanned-item-meta { display: flex; flex-wrap: wrap; gap: 0.4rem 0.65rem; margin-top: 0.25rem; font-size: 0.8rem; }
.delivery-scanned-chip {
    display: inline-flex; align-items: center; padding: 0.1rem 0.45rem;
    border-radius: 999px; background: #e0f2fe; color: #075985; font-weight: 700; font-size: 0.75rem;
}
.delivery-scanned-tracking { color: #475569; font-weight: 600; }
.delivery-scanned-item-time { font-variant-numeric: tabular-nums; color: #64748b; font-size: 0.8rem; white-space: nowrap; }
.delivery-pending-card { margin-top: 0.25rem; }

.delivery-scan-form { margin-bottom: 0.75rem; }
.delivery-scan-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; margin-bottom: 1rem; }
.delivery-field { min-width: 0; }
.delivery-field label.delivery-label, .delivery-label { display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.delivery-input, .delivery-select { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; width: 100%; min-width: 120px; }
.delivery-input:focus, .delivery-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.delivery-input-scan-lg {
    text-align: center; font-family: ui-monospace, monospace; font-size: 1.35rem; font-weight: 700;
    letter-spacing: 0.06em; padding: 0.85rem 0.75rem; width: 100%;
}
.delivery-field-btn { display: flex; align-items: flex-end; }
.delivery-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.delivery-btn[disabled] { opacity: 0.6; cursor: not-allowed; }
.delivery-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.delivery-btn-primary:hover { background: #0f766e; color: #fff; }
.delivery-btn-scan { background: #059669; color: #fff; border-color: #059669; font-weight: 600; padding: 0.5rem 1.25rem; }
.delivery-btn-scan:hover { background: #047857; color: #fff; }
.delivery-btn-scan-block { width: 100%; margin-top: 0.85rem; padding: 0.7rem 1rem; }
.delivery-scan-hint { font-size: 0.8125rem; color: #6b7280; margin: 0 0 0.5rem; line-height: 1.4; }
.delivery-scan-print { margin: 0; font-size: 0.875rem; }
.delivery-link { color: #0d9488; text-decoration: none; font-weight: 500; }
.delivery-link:hover { text-decoration: underline; }
@media (min-width: 768px) {
    .delivery-scan-row .delivery-field { flex: 0 0 auto; }
    .delivery-scan-row .delivery-field:first-child { min-width: 180px; }
}
</style>

@php
    $bultosByCode = $availablePackages->groupBy('warehouse_code')->map(function ($group) {
        if ($group->count() <= 1) return null;
        return $group->sortBy('bulto_index')->map(function ($p) {
            $label = ($p->bulto_index && $p->bultos_total) ? (int)$p->bulto_index . '/' . (int)$p->bultos_total : (string)($p->bulto_index ?? '');
            return ['bulto_index' => $p->bulto_index, 'bultos_total' => $p->bultos_total, 'label' => $label];
        })->values()->all();
    })->filter();
@endphp
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var retirerForm = document.getElementById('delivery-retirer-form');
    var retirerBtn = document.getElementById('btn-retirer-submit');
    if (retirerForm && retirerBtn) {
        retirerForm.addEventListener('submit', function() {
            retirerBtn.disabled = true;
            retirerBtn.textContent = 'Guardando…';
        });
    }

    var form = document.getElementById('delivery-batch-scan-form');
    var codeInput = document.getElementById('scan_code');
    var bultoWrap = document.getElementById('bulto-select-wrap');
    var bultoSelect = document.getElementById('bulto_index');
    var scanBtn = document.getElementById('btn-register-delivery');
    if (!form || !codeInput) return;

    var bultosByCode = @json($bultosByCode);
    var DEBOUNCE_MS = 180;
    var debounceTimer = null;

    codeInput.focus();

    function normalizeCode(val) {
        return (val || '').trim().toUpperCase();
    }

    function isWarehouseCode(val) {
        return /^\d{6}$/.test(normalizeCode(val));
    }

    function updateBultoSelect(code) {
        var normalized = normalizeCode(code);
        if (!isWarehouseCode(normalized) || !bultoSelect || !bultoWrap) {
            if (bultoWrap) bultoWrap.style.display = 'none';
            if (bultoSelect) {
                bultoSelect.innerHTML = '<option value="">— Seleccione —</option>';
                bultoSelect.removeAttribute('required');
            }
            return false;
        }
        var bultos = bultosByCode[normalized];
        if (!bultos || bultos.length <= 1) {
            bultoWrap.style.display = 'none';
            bultoSelect.innerHTML = '<option value="">— Seleccione —</option>';
            bultoSelect.removeAttribute('required');
            return false;
        }
        bultoWrap.style.display = 'block';
        bultoSelect.innerHTML = '<option value="">— Seleccione bulto —</option>' + bultos.map(function(b) {
            return '<option value="' + b.bulto_index + '">' + b.label + '</option>';
        }).join('');
        bultoSelect.setAttribute('required', 'required');
        return true;
    }

    function needsBultoSelection() {
        var code = normalizeCode(codeInput.value);
        return isWarehouseCode(code) && bultosByCode[code] && bultosByCode[code].length > 1;
    }

    function canSubmit() {
        var code = normalizeCode(codeInput.value);
        if (!code) return false;
        if (needsBultoSelection() && (!bultoSelect || !bultoSelect.value)) return false;
        return true;
    }

    var submitting = false;
    function submitOnce() {
        if (submitting || !canSubmit()) return;
        submitting = true;
        clearTimeout(debounceTimer);
        if (scanBtn) { scanBtn.disabled = true; scanBtn.textContent = 'Registrando…'; }
        codeInput.value = normalizeCode(codeInput.value);
        codeInput.setAttribute('readonly', 'readonly');
        form.submit();
    }

    function scheduleAutoSubmit(expectedCode) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            if (normalizeCode(codeInput.value) !== expectedCode) return;
            updateBultoSelect(expectedCode);
            if (needsBultoSelection()) {
                if (bultoSelect) bultoSelect.focus();
                return;
            }
            if (canSubmit()) submitOnce();
        }, DEBOUNCE_MS);
    }

    codeInput.addEventListener('input', function() {
        var raw = (this.value || '').trim();
        // Mientras solo hay hasta 6 dígitos, puede ser warehouse: esperamos debounce
        // por si el tracking numérico sigue creciendo.
        if (/^\d{0,6}$/.test(raw)) {
            this.value = raw.replace(/\D/g, '').slice(0, 6);
            updateBultoSelect(this.value);
            if (this.value.length === 6) scheduleAutoSubmit(normalizeCode(this.value));
            else clearTimeout(debounceTimer);
            return;
        }

        updateBultoSelect(this.value);
        var code = normalizeCode(this.value);
        if (code.length >= 4) scheduleAutoSubmit(code);
        else clearTimeout(debounceTimer);
    });

    if (bultoSelect) {
        bultoSelect.addEventListener('change', function() {
            if (canSubmit()) submitOnce();
        });
    }

    codeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer);
            updateBultoSelect(this.value);
            if (canSubmit()) submitOnce();
            else if (needsBultoSelection() && bultoSelect) bultoSelect.focus();
        }
    });

    form.addEventListener('submit', function(e) {
        if (submitting) return;
        if (!canSubmit()) {
            e.preventDefault();
            return;
        }
        submitting = true;
        clearTimeout(debounceTimer);
        codeInput.value = normalizeCode(codeInput.value);
        if (scanBtn) { scanBtn.disabled = true; scanBtn.textContent = 'Registrando…'; }
    });

    if (document.getElementById('delivery-error')) {
        codeInput.value = '';
        codeInput.removeAttribute('readonly');
        codeInput.focus();
    } else if (document.querySelector('.delivery-alert-success')) {
        var panel = document.getElementById('delivery-scanned-panel');
        if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    updateBultoSelect(codeInput.value);
});
</script>
@endpush
@endsection
