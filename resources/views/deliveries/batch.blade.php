@extends('layouts.app')

@section('title', 'Hoja de salida')

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
    $pendingCount = $availablePackages->count();
    $pendingLbs = round($availablePackages->sum(fn ($p) => (float) ($p->verified_weight_lbs ?? $p->intake_weight_lbs ?? 0)), 1);
    $serviceLabel = !empty($filterParams['service_type']) ? \App\Support\ServiceType::label($filterParams['service_type']) : null;
@endphp

<div class="inv-page">
    <x-module-banner
        section="Operaciones"
        current="Hoja de salida"
        title="Hoja de salida"
        subtitle="Salida de producto para {{ $agencyName }}{{ $serviceLabel ? ' · '.$serviceLabel : '' }}. Indique quién retira y escanee warehouse o tracking."
        back-href="{{ route('salidas.index') }}"
        back-label="Volver a Salidas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h11.25v10.5H3.75V6.75Zm11.25 3h3.19a1.5 1.5 0 0 1 1.22.63l1.59 2.24v4.63H15V9.75ZM7.5 18.75a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm12 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            @if($retirerSessionActive && $deliveryNote)
            <form action="{{ route('salidas.batch-clear-retirer-session') }}" method="POST" class="hs-inline-form">
                @csrf
                <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
                <input type="hidden" name="agency_id" value="{{ $agency->id }}">
                @if(!empty($filterParams['service_type']))
                <input type="hidden" name="service_type" value="{{ $filterParams['service_type'] }}">
                @endif
                <button type="submit" class="mb-btn mb-btn-secondary">Cambiar quien retira</button>
            </form>
            <a href="{{ route('salidas.print-report', $printReportParams) }}" target="_blank" class="mb-btn {{ $deliveredCount > 0 ? 'mb-btn-primary' : 'mb-btn-secondary' }}">
                {{ $deliveredCount > 0 ? 'Cerrar hoja e imprimir' : 'Imprimir hoja' }}
            </a>
            @endif
        </x-slot:actions>
        <x-slot:strip>
            @if($deliveryNote)
            <span class="mb-strip-label">Hoja</span>
            <span class="mb-pill"><strong>{{ $deliveryNote->code }}</strong></span>
            @endif
            <span class="mb-pill">{{ $agency->code }} · {{ $agencyName }}</span>
            @if($serviceLabel)
            <span class="mb-pill">{{ $serviceLabel }}</span>
            @endif
            <span class="mb-pill">Pendientes <strong>{{ $pendingCount }}</strong></span>
            @if($retirerSessionActive)
            <span class="mb-pill mb-pill--ok">Entregados <strong>{{ $deliveredCount }}</strong></span>
            <span class="mb-pill">Retira <strong>{{ $batchRetirerSession['delivered_to'] ?? '—' }}</strong></span>
            @if(filled($batchRetirerSession['retirer_id_number'] ?? null))
            <span class="mb-pill">Cédula {{ $batchRetirerSession['retirer_id_number'] }}</span>
            @endif
            @if(filled($batchRetirerSession['retirer_phone'] ?? null))
            <span class="mb-pill">Tel. {{ $batchRetirerSession['retirer_phone'] }}</span>
            @endif
            @endif
        </x-slot:strip>
    </x-module-banner>

    @if(session('success'))
    <div class="inv-alert inv-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div id="delivery-error" class="inv-alert inv-alert-danger">{{ session('error') }}</div>
    @endif

    @if(!$retirerSessionActive)
    <div class="inv-kpis">
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Pendientes</span>
            <span class="inv-kpi-value">{{ $pendingCount }}</span>
            <span class="inv-kpi-note">Listos para escanear</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Peso pendiente</span>
            <span class="inv-kpi-value">{{ number_format($pendingLbs, 1) }}</span>
            <span class="inv-kpi-note">Libras de esta salida</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Cliente</span>
            <span class="inv-kpi-value inv-kpi-value--sm">{{ $agency->code }}</span>
            <span class="inv-kpi-note">{{ $agencyName }}</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Paso actual</span>
            <span class="inv-kpi-value inv-kpi-value--sm">Quien retira</span>
            <span class="inv-kpi-note">Luego se escanean los paquetes</span>
        </div>
    </div>

    <div class="inv-card">
        <div class="inv-table-head">
            <span class="inv-table-head-note">Datos de quien retira</span>
            <span class="inv-muted">Una sola vez para todos los paquetes de esta hoja</span>
        </div>
        <div class="inv-card-body">
            <p class="inv-hint">Indique nombre completo, cédula y teléfono. Después podrá escanear por warehouse o tracking; cada escaneo se registra solo.</p>
            <form action="{{ route('salidas.batch-retirer-session') }}" method="POST" class="hs-retirer-form" id="delivery-retirer-form">
                @csrf
                @if($deliveryNote)
                <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
                @endif
                <input type="hidden" name="agency_id" value="{{ $agency->id }}">
                @if(!empty($filterParams['service_type']))
                <input type="hidden" name="service_type" value="{{ $filterParams['service_type'] }}">
                @endif
                <div class="hs-retirer-grid">
                    <div class="inv-field hs-field-name">
                        <label for="retirer_delivered_to" class="inv-label">Nombre completo *</label>
                        <input type="text" name="delivered_to" id="retirer_delivered_to" value="{{ old('delivered_to') }}" class="inv-input @error('delivered_to') inv-input-invalid @enderror" placeholder="Nombre completo" required autofocus>
                        @error('delivered_to')<span class="hs-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="inv-field">
                        <label for="retirer_id_number_form" class="inv-label">Cédula (opcional)</label>
                        <input type="text" name="retirer_id_number" id="retirer_id_number_form" value="{{ old('retirer_id_number') }}" class="inv-input @error('retirer_id_number') inv-input-invalid @enderror" placeholder="Nº cédula">
                        @error('retirer_id_number')<span class="hs-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="inv-field">
                        <label for="retirer_phone_form" class="inv-label">Teléfono (opcional)</label>
                        <input type="text" name="retirer_phone" id="retirer_phone_form" value="{{ old('retirer_phone') }}" class="inv-input @error('retirer_phone') inv-input-invalid @enderror" placeholder="Nº telefónico">
                        @error('retirer_phone')<span class="hs-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="hs-retirer-submit">
                        <button type="submit" class="inv-btn inv-btn-primary" id="btn-retirer-submit">Guardar y escanear paquetes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="inv-card">
        <div class="inv-table-head">
            <span class="inv-table-head-note">Paquetes pendientes de salida</span>
            <span class="inv-muted">{{ $pendingCount }} {{ $pendingCount === 1 ? 'paquete' : 'paquetes' }}</span>
        </div>
        @if($availablePackages->isEmpty())
        <div class="inv-empty">No hay paquetes pendientes de salida para esta agencia.</div>
        @else
        <div class="inv-table-scroll">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>Cliente (etiqueta)</th>
                        <th>Warehouse</th>
                        <th>Bulto</th>
                        <th>Tracking</th>
                        <th>Servicio</th>
                        <th class="inv-num">Peso (lbs)</th>
                        <th>Listo desde</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($availablePackages as $p)
                    <tr>
                        <td class="inv-client" title="{{ $p->label_name }}">{{ Str::limit($p->label_name, 28) }}</td>
                        <td><span class="inv-folio">{{ $p->warehouse_code ?? '—' }}</span></td>
                        <td>{{ ($p->bultos_total && $p->bultos_total > 1 && $p->bulto_index) ? $p->bulto_index . '/' . $p->bultos_total : '—' }}</td>
                        <td class="inv-muted" title="{{ $p->tracking_external }}">{{ Str::limit($p->tracking_external, 20) }}</td>
                        <td><span class="inv-type inv-type--{{ strtolower($p->service_type ?? 'air') }}">{{ \App\Support\ServiceType::label($p->service_type) }}</span></td>
                        <td class="inv-num">{{ $p->verified_weight_lbs ?? $p->intake_weight_lbs ?? '—' }}</td>
                        <td class="inv-muted">{{ $p->ready_at ? $p->ready_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    @elseif($deliveryNote)
    <div class="inv-kpis">
        <div class="inv-kpi-card inv-kpi-card--green">
            <span class="inv-kpi-label">Entregados</span>
            <span class="inv-kpi-value inv-text-green">{{ $deliveredCount }}</span>
            <span class="inv-kpi-note">En esta hoja</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Pendientes</span>
            <span class="inv-kpi-value">{{ $pendingCount }}</span>
            <span class="inv-kpi-note">{{ $pendingCount === 0 ? 'No quedan paquetes' : 'Aún por escanear' }}</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Peso pendiente</span>
            <span class="inv-kpi-value">{{ number_format($pendingLbs, 1) }}</span>
            <span class="inv-kpi-note">Libras restantes</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Hoja</span>
            <span class="inv-kpi-value inv-kpi-value--sm">{{ $deliveryNote->code }}</span>
            <span class="inv-kpi-note">{{ $agencyName }}</span>
        </div>
    </div>

    <div class="hs-workbench">
        <section class="inv-card hs-scan-card">
            <div class="inv-table-head">
                <span class="inv-table-head-note">Escanear</span>
            </div>
            <div class="inv-card-body">
                <form action="{{ route('salidas.process-scan') }}" method="POST" class="hs-scan-form" id="delivery-batch-scan-form">
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
                    <label for="scan_code" class="inv-label">Warehouse o tracking</label>
                    <input type="text" name="code" id="scan_code" value="{{ old('code', old('warehouse_code')) }}" class="inv-input hs-scan-input" placeholder="Escanee aquí" maxlength="100" required autofocus autocomplete="off">
                    <div class="inv-field" id="bulto-select-wrap" style="display: none; margin-top: 0.75rem;">
                        <label for="bulto_index" class="inv-label">Bulto (varios con este código) *</label>
                        <select name="bulto_index" id="bulto_index" class="inv-input">
                            <option value="">— Seleccione —</option>
                        </select>
                    </div>
                    <button type="submit" class="inv-btn inv-btn-primary hs-scan-btn" id="btn-register-delivery">Registrar entrega</button>
                </form>
                <p class="inv-hint hs-scan-hint">Use la pistola: warehouse o tracking se registran solos al terminar de leer el código. Si varios bultos comparten warehouse, elija cuál está entregando.</p>
            </div>
        </section>

        <section class="inv-card hs-scanned-card" id="delivery-scanned-panel">
            <div class="hs-scanned-head">
                <h2 class="hs-scanned-title">Escaneados</h2>
                <span class="hs-scanned-count">{{ $scannedDeliveries->count() }}</span>
            </div>
            @if($scannedDeliveries->isEmpty())
            <div class="hs-scanned-empty">
                <p>Los paquetes van a aparecer aquí conforme los escanee.</p>
            </div>
            @else
            <ol class="hs-scanned-list">
                @foreach($scannedDeliveries as $i => $delivery)
                    @php $pkg = $delivery->preregistration; @endphp
                    <li class="hs-scanned-item {{ $i === 0 ? 'is-latest' : '' }}">
                        <div class="hs-scanned-num">{{ $scannedDeliveries->count() - $i }}</div>
                        <div class="hs-scanned-body">
                            <div class="hs-scanned-name" title="{{ $pkg?->label_name }}">{{ $pkg?->label_name ?: 'Sin nombre' }}</div>
                            <div class="hs-scanned-meta">
                                <span class="inv-folio">{{ $pkg?->warehouse_code ?? '—' }}</span>
                                @if($pkg?->bultos_total && $pkg->bultos_total > 1 && $pkg->bulto_index)
                                <span class="hs-chip">{{ $pkg->bulto_index }}/{{ $pkg->bultos_total }}</span>
                                @endif
                                @if($pkg?->tracking_external)
                                <span class="inv-muted" title="{{ $pkg->tracking_external }}">{{ Str::limit($pkg->tracking_external, 24) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="hs-scanned-time">{{ $delivery->delivered_at?->timezone(config('app.display_timezone'))->format('H:i:s') ?? '—' }}</div>
                    </li>
                @endforeach
            </ol>
            @endif
        </section>
    </div>

    <div class="inv-card">
        <div class="inv-table-head">
            <span class="inv-table-head-note">Pendientes</span>
            <span class="inv-muted">{{ $pendingCount }} {{ $pendingCount === 1 ? 'paquete' : 'paquetes' }}</span>
        </div>
        @if($availablePackages->isEmpty())
        <div class="inv-empty">No quedan paquetes pendientes para esta agencia.</div>
        @else
        <div class="inv-table-scroll">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>Cliente (etiqueta)</th>
                        <th>Warehouse</th>
                        <th>Bulto</th>
                        <th>Tracking</th>
                        <th>Servicio</th>
                        <th class="inv-num">Peso (lbs)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($availablePackages as $p)
                    <tr>
                        <td class="inv-client" title="{{ $p->label_name }}">{{ Str::limit($p->label_name, 28) }}</td>
                        <td><span class="inv-folio">{{ $p->warehouse_code ?? '—' }}</span></td>
                        <td>{{ ($p->bultos_total && $p->bultos_total > 1 && $p->bulto_index) ? $p->bulto_index . '/' . $p->bultos_total : '—' }}</td>
                        <td class="inv-muted" title="{{ $p->tracking_external }}">{{ Str::limit($p->tracking_external, 20) }}</td>
                        <td><span class="inv-type inv-type--{{ strtolower($p->service_type ?? 'air') }}">{{ \App\Support\ServiceType::label($p->service_type) }}</span></td>
                        <td class="inv-num">{{ $p->verified_weight_lbs ?? $p->intake_weight_lbs ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif
</div>

<style>
.inv-page {
    --inv-navy: #0A2D6F; --inv-blue: #1E4FA8; --inv-green: #16794C;
    --inv-line: #E8EEF8; --inv-border: #C5D4EB; --inv-soft: #F4F8FD;
    padding: 0 0 2.25rem; max-width: 96rem; margin: 0 auto; width: 100%;
}
.inv-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.inv-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; font-weight: 600; }
.inv-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.inv-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.15rem; }
.inv-kpi-card {
    background: #fff; border: 1px solid var(--inv-line); border-radius: 0.85rem;
    padding: 0.9rem 1.05rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex; flex-direction: column; gap: 0.28rem;
}
.inv-kpi-card--green { border-color: #A7DFC3; background: linear-gradient(180deg, #fff 40%, #F2FBF6 140%); }
.inv-kpi-label { font-size: 0.66rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.inv-kpi-value { font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.inv-kpi-value--sm { font-size: 1.05rem; }
.inv-kpi-note { font-size: 0.7rem; color: #94a3b8; }
.inv-card { background: #fff; border: 1px solid var(--inv-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; margin-bottom: 1.15rem; }
.inv-table-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.7rem; padding: 0.85rem 1.1rem; border-bottom: 1px solid var(--inv-line); }
.inv-table-head-note { font-size: 0.85rem; font-weight: 700; color: #334155; }
.inv-card-body { padding: 1rem 1.1rem 1.15rem; }
.inv-hint { font-size: 0.85rem; color: #64748b; margin: 0 0 0.85rem; }
.inv-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 0; }
.inv-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.inv-input { padding: 0.52rem 0.7rem; font-size: 0.85rem; border: 1px solid #D8DCE2; border-radius: 0.55rem; background: #fff; color: #0f172a; width: 100%; box-sizing: border-box; }
.inv-input:focus { outline: none; border-color: var(--inv-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.inv-input-invalid { border-color: #f87171 !important; }
.inv-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.6rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.inv-btn[disabled] { opacity: 0.6; cursor: not-allowed; }
.inv-btn-primary { background: var(--inv-navy); color: #fff; border-color: var(--inv-navy); box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25); }
.inv-btn-primary:hover { background: var(--inv-blue); border-color: var(--inv-blue); color: #fff; }
.inv-table-scroll { overflow-x: auto; }
.inv-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.inv-table thead th { background: linear-gradient(135deg, var(--inv-navy), var(--inv-blue)); color: #fff; text-align: left; padding: 0.62rem 0.85rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
.inv-table thead th.inv-num { text-align: right; }
.inv-table td { padding: 0.66rem 0.85rem; border-bottom: 1px solid #f4f7fb; color: #334155; vertical-align: middle; }
.inv-table tbody tr:hover td { background: var(--inv-soft); }
.inv-num { text-align: right; font-variant-numeric: tabular-nums; }
.inv-muted { color: #94a3b8; font-size: 0.8rem; }
.inv-empty { padding: 2rem 1.25rem; text-align: center; color: #94a3b8; font-size: 0.9rem; }
.inv-folio { font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; font-family: ui-monospace, monospace; }
.inv-client { font-weight: 700; color: #0f172a; }
.inv-type { display: inline-flex; padding: 0.14rem 0.5rem; border-radius: 999px; font-size: 0.68rem; font-weight: 700; }
.inv-type--air { background: #EAF6FB; color: #0E6E8C; border: 1px solid #BFE3F0; }
.inv-type--sea { background: #FDF3E8; color: #9A5B12; border: 1px solid #F0D4A8; }
.inv-text-green { color: var(--inv-green); }
.hs-inline-form { margin: 0; }
.hs-field-error { display: block; font-size: 0.75rem; color: #b91c1c; }
.hs-retirer-grid { display: grid; grid-template-columns: minmax(12rem, 1.4fr) repeat(2, minmax(8rem, 1fr)) auto; gap: 0.75rem; align-items: end; }
.hs-field-name { min-width: 0; }
.hs-retirer-submit { display: flex; align-items: flex-end; }
.hs-workbench { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1.15rem; align-items: start; }
@media (min-width: 960px) { .hs-workbench { grid-template-columns: minmax(280px, 360px) 1fr; } }
.hs-scan-card, .hs-scanned-card { margin-bottom: 0; }
.hs-scan-card { position: sticky; top: 0.75rem; }
.hs-scan-input { text-align: center; font-family: ui-monospace, monospace; font-size: 1.25rem; font-weight: 700; letter-spacing: 0.06em; padding: 0.85rem 0.75rem; }
.hs-scan-btn { width: 100%; margin-top: 0.85rem; }
.hs-scan-hint { margin: 0.85rem 0 0; }
.hs-scanned-card { min-height: 280px; display: flex; flex-direction: column; }
.hs-scanned-head {
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
    background: linear-gradient(135deg, var(--inv-navy), var(--inv-blue)); padding: 0.75rem 1rem;
}
.hs-scanned-title { margin: 0; font-size: 0.95rem; font-weight: 800; color: #fff; }
.hs-scanned-count {
    min-width: 2rem; height: 2rem; padding: 0 0.55rem;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 999px; background: rgba(255,255,255,0.2); color: #fff; font-weight: 800; font-size: 0.95rem;
}
.hs-scanned-empty { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1.25rem; text-align: center; color: #94a3b8; font-size: 0.9rem; }
.hs-scanned-empty p { margin: 0; max-width: 16rem; }
.hs-scanned-list { list-style: none; margin: 0; padding: 0; max-height: 420px; overflow-y: auto; }
.hs-scanned-item { display: grid; grid-template-columns: auto 1fr auto; gap: 0.75rem; align-items: center; padding: 0.85rem 1rem; border-bottom: 1px solid #f4f7fb; }
.hs-scanned-item.is-latest { background: var(--inv-soft); box-shadow: inset 3px 0 0 var(--inv-navy); }
.hs-scanned-num { width: 2rem; height: 2rem; border-radius: 999px; background: #E8EEF8; color: var(--inv-navy); display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; }
.hs-scanned-item.is-latest .hs-scanned-num { background: var(--inv-navy); color: #fff; }
.hs-scanned-name { font-weight: 700; color: #0f172a; font-size: 0.95rem; line-height: 1.25; }
.hs-scanned-meta { display: flex; flex-wrap: wrap; gap: 0.4rem 0.65rem; margin-top: 0.25rem; font-size: 0.8rem; }
.hs-chip { display: inline-flex; padding: 0.1rem 0.45rem; border-radius: 999px; background: #e0f2fe; color: #075985; font-weight: 700; font-size: 0.75rem; }
.hs-scanned-time { font-variant-numeric: tabular-nums; color: #94a3b8; font-size: 0.8rem; white-space: nowrap; }
@media (max-width: 1100px) { .inv-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .hs-retirer-grid { grid-template-columns: 1fr 1fr; } .hs-retirer-submit { grid-column: 1 / -1; } }
@media (max-width: 640px) { .inv-kpis, .hs-retirer-grid { grid-template-columns: 1fr; } }
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
    } else if (document.querySelector('.inv-alert-success')) {
        var panel = document.getElementById('delivery-scanned-panel');
        if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    updateBultoSelect(codeInput.value);
});
</script>
@endpush
@endsection
