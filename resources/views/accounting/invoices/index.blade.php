@extends('layouts.app')

@section('title', 'Facturas PrimeTrack')

@section('content')
@php
    $statusClass = fn ($status) => match ($status) {
        'paid' => 'inv-status--green',
        'partially_paid' => 'inv-status--blue',
        'issued' => 'inv-status--amber',
        'void' => 'inv-status--red',
        default => 'inv-status--gray',
    };
    $serviceLabels = \App\Support\ServiceType::options();
    $isAdmin = auth()->user()?->is_admin;
    $isClientView = auth()->user()?->isAgencyUser();
@endphp
<div class="inv-page">
    <x-module-banner section="Contabilidad" current="Facturas" title="{{ $isClientView ? 'Mis facturas' : 'Facturas PrimeTrack' }}" subtitle="{{ $isClientView ? 'Facturas emitidas a su cuenta. Puede ver el detalle y descargar el voucher.' : 'Facturación vinculada a cada hoja de salida. Envíe el comprobante al correo de facturación del cliente.' }}" :hide-back="$isClientView">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
        </x-slot:icon>
        @if($isAdmin)
        <x-slot:actions>
            <a href="{{ route('accounting.invoices.create') }}" class="mb-btn mb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva factura
            </a>
        </x-slot:actions>
        @endif
    </x-module-banner>

    @if(session('success'))
    <div class="inv-alert inv-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="inv-alert inv-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="inv-kpis">
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Total</span>
            <span class="inv-kpi-value">{{ number_format($statsTotal) }}</span>
            <span class="inv-kpi-note">En el filtro actual</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Pendientes</span>
            <span class="inv-kpi-value">{{ number_format($statsPending) }}</span>
            <span class="inv-kpi-note">Emitidas sin cobro</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Parciales</span>
            <span class="inv-kpi-value">{{ number_format($statsPartial) }}</span>
            <span class="inv-kpi-note">Con abono</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">Pagadas</span>
            <span class="inv-kpi-value">{{ number_format($statsPaid) }}</span>
            <span class="inv-kpi-note">Saldo en cero</span>
        </div>
        <div class="inv-kpi-card inv-kpi-card--green">
            <span class="inv-kpi-label">USD facturado</span>
            <span class="inv-kpi-value inv-text-green">${{ number_format((float) $statsTotalUsd, 2) }}</span>
            <span class="inv-kpi-note">Sin anuladas</span>
        </div>
    </div>

    <div class="inv-card inv-filters-card">
        <form method="GET" action="{{ route('accounting.invoices.index') }}" class="inv-filters-form">
            @unless($isClientView)
            <div class="inv-field">
                <label class="inv-label" for="client">Cliente</label>
                <input type="text" name="client" id="client" class="inv-input" value="{{ request('client') }}" placeholder="Buscar cliente…">
            </div>
            @endunless
            <div class="inv-field">
                <label class="inv-label" for="issued_at">Fecha</label>
                <input type="date" name="issued_at" id="issued_at" class="inv-input" value="{{ request('issued_at') }}">
            </div>
            <div class="inv-field">
                <label class="inv-label" for="status">Estado</label>
                <select name="status" id="status" class="inv-input">
                    <option value="">Todos</option>
                    @foreach(['issued'=>'Pendiente','partially_paid'=>'Parcial','paid'=>'Pagado','void'=>'Anulada'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($isAdmin)
            <label class="inv-check">
                <input type="checkbox" name="include_void" value="1" @checked(request()->boolean('include_void'))>
                Incluir anuladas
            </label>
            @endif
            <div class="inv-filters-actions">
                <button class="inv-btn inv-btn-primary" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('accounting.invoices.index') }}" class="inv-clear-link">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="inv-card">
        <div class="inv-table-head">
            <span class="inv-table-head-note">Total: {{ number_format($invoices->total()) }} {{ $invoices->total() === 1 ? 'factura' : 'facturas' }}</span>
            <div class="inv-legend">
                <span class="inv-legend-item"><span class="inv-dot inv-dot--air"></span> Aéreo</span>
                <span class="inv-legend-item"><span class="inv-dot inv-dot--sea"></span> Marítimo</span>
                <span class="inv-legend-item"><span class="inv-dot inv-dot--cft"></span> Pie cúbico</span>
            </div>
            @if($isAdmin)
            <a href="{{ route('accounting.invoices.create') }}" class="inv-btn inv-btn-primary inv-btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva factura
            </a>
            @endif
        </div>
        <div class="inv-table-scroll">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Hoja de salida</th>
                        <th class="inv-num">Paq.</th>
                        <th class="inv-num">Monto</th>
                        <th>Moneda</th>
                        <th>Estado</th>
                        <th class="inv-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    @php
                        $services = $invoice->lines->pluck('service_type')->unique()->filter()->values();
                        $email = $invoice->agency?->billingEmail();
                    @endphp
                    <tr>
                        <td><span class="inv-folio">{{ $invoice->folio }}</span></td>
                        <td>
                            <div class="inv-types">
                                @forelse($services as $svc)
                                <span class="inv-type inv-type--{{ strtolower($svc) }}">
                                    {{ \App\Support\ServiceType::icon($svc) }} {{ $serviceLabels[$svc] ?? $svc }}
                                </span>
                                @empty
                                <span class="inv-muted">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            <div class="inv-client">{{ $invoice->agency?->name ?? '—' }}</div>
                            @if($invoice->agency?->code)
                            <div class="inv-muted">{{ $invoice->agency->code }}</div>
                            @endif
                        </td>
                        <td class="inv-nowrap">{{ optional($invoice->issued_at)->format('d/m/Y') ?? '—' }}</td>
                        <td class="inv-muted">{{ $invoice->deliveryNote?->code ?? '—' }}</td>
                        <td class="inv-num">
                            <span class="inv-paq">{{ $invoice->lines_count }}</span>
                        </td>
                        <td class="inv-num"><strong class="inv-text-green">${{ number_format((float) $invoice->total_usd, 2) }}</strong></td>
                        <td class="inv-muted">USD</td>
                        <td>
                            <span class="inv-status {{ $statusClass($invoice->status) }}">{{ $invoice->statusLabel() }}</span>
                        </td>
                        <td class="inv-actions">
                            <a href="{{ route('accounting.invoices.show', $invoice) }}" class="inv-icon-btn" title="Ver detalle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                            @if(! $invoice->isVoid())
                            <a href="{{ route('accounting.invoices.voucher', $invoice) }}" target="_blank" class="inv-icon-btn" title="Imprimir voucher">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.082m.72-.082a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.082m-.72-.082L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V6.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v.753"/></svg>
                            </a>
                            <a href="{{ route('accounting.invoices.pdf', $invoice) }}" class="inv-icon-btn" title="Descargar voucher PDF">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            </a>
                            @if($isAdmin)
                            <form action="{{ route('accounting.invoices.send', $invoice) }}" method="POST" class="inv-form-inline" onsubmit="return confirm('¿Enviar la factura {{ addslashes($invoice->folio) }} a {{ addslashes($email ?: '—') }}?');">
                                @csrf
                                <button type="submit" class="inv-icon-btn {{ $email ? '' : 'is-disabled' }} {{ $invoice->emailed_at ? 'is-sent' : '' }}" title="{{ $email ? ($invoice->emailed_at ? 'Reenviar a '.$email : 'Enviar a '.$email) : 'Sin correo registrado' }}" @disabled(! $email)>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                </button>
                            </form>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="inv-empty">
                            Aún no hay facturas en este filtro.
                            @if($isAdmin)
                            <a href="{{ route('accounting.invoices.create') }}">Crear una factura</a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->total() > 0)
        <div class="inv-card-footer">
            <span class="inv-muted">{{ $invoices->firstItem() }} – {{ $invoices->lastItem() }} de {{ $invoices->total() }}</span>
            @if($invoices->hasPages())
            <div class="inv-pager-wrap">{{ $invoices->links('vendor.pagination.primetrack') }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.inv-page {
    --inv-navy: #0A2D6F;
    --inv-blue: #1E4FA8;
    --inv-green: #16794C;
    --inv-red: #D64545;
    --inv-amber: #B27A0E;
    --inv-line: #E8EEF8;
    --inv-border: #C5D4EB;
    --inv-soft: #F4F8FD;
    --inv-muted: #5E6168;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}
.inv-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    background: #fff;
    border: 1px solid var(--inv-line);
    border-radius: 1rem;
    padding: 1.05rem 1.25rem 1.1rem;
    margin-bottom: 1.15rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.inv-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 0.45rem;
}
.inv-breadcrumb strong { color: #334155; font-weight: 700; }
.inv-title-row { display: flex; align-items: center; gap: 0.6rem; }
.inv-title-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.65rem;
    background: linear-gradient(135deg, var(--inv-navy), var(--inv-blue));
    color: #fff;
    box-shadow: 0 6px 14px rgba(10, 45, 111, 0.28);
    flex-shrink: 0;
}
.inv-title { margin: 0; font-size: 1.45rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
.inv-subtitle { margin: 0.4rem 0 0; font-size: 0.875rem; color: var(--inv-muted); line-height: 1.45; max-width: 44rem; }
.inv-header-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; align-self: center; }

.inv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.58rem 1.05rem;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: transform .15s ease, background .15s ease, color .15s ease, border-color .15s ease;
}
.inv-btn-primary {
    background: var(--inv-navy);
    color: #fff;
    border-color: var(--inv-navy);
    box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25);
}
.inv-btn-primary:hover { background: var(--inv-blue); border-color: var(--inv-blue); color: #fff; transform: translateY(-1px); }
.inv-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.inv-btn-secondary:hover { background: var(--inv-soft); color: var(--inv-navy); border-color: var(--inv-border); }
.inv-btn-sm { padding: 0.42rem 0.85rem; font-size: 0.8rem; }

.inv-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.inv-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; font-weight: 600; }
.inv-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

.inv-kpis {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.15rem;
}
.inv-kpi-card {
    background: #fff;
    border: 1px solid var(--inv-line);
    border-radius: 0.85rem;
    padding: 0.9rem 1.05rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    gap: 0.28rem;
}
.inv-kpi-card--green { border-color: #A7DFC3; background: linear-gradient(180deg, #fff 40%, #F2FBF6 140%); }
.inv-kpi-label { font-size: 0.66rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.inv-kpi-value { font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.inv-kpi-note { font-size: 0.7rem; color: #94a3b8; }

.inv-card {
    background: #fff;
    border: 1px solid var(--inv-line);
    border-radius: 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 1.15rem;
}
.inv-filters-card { padding: 0.9rem 1.1rem; overflow: visible; }
.inv-filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.inv-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 9rem; flex: 1; max-width: 16rem; }
.inv-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.inv-input {
    padding: 0.52rem 0.7rem;
    font-size: 0.85rem;
    border: 1px solid #D8DCE2;
    border-radius: 0.55rem;
    background: #fff;
    color: #0f172a;
    width: 100%;
    box-sizing: border-box;
}
.inv-input:focus { outline: none; border-color: var(--inv-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.inv-check {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    padding-bottom: 0.45rem;
    white-space: nowrap;
}
.inv-filters-actions { display: flex; align-items: center; gap: 0.65rem; }
.inv-clear-link { font-size: 0.8rem; font-weight: 700; color: #64748b; text-decoration: none; }
.inv-clear-link:hover { color: var(--inv-navy); text-decoration: underline; }

.inv-table-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.7rem;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid var(--inv-line);
}
.inv-table-head-note { font-size: 0.85rem; font-weight: 700; color: #334155; }
.inv-legend { display: flex; gap: 0.85rem; margin-right: auto; }
.inv-legend-item { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 600; color: #64748b; }
.inv-dot { width: 0.7rem; height: 0.7rem; border-radius: 0.2rem; display: inline-block; }
.inv-dot--air { background: #5BB8E4; }
.inv-dot--sea { background: #F0A04B; }
.inv-dot--cft { background: #16794C; }

.inv-table-scroll { overflow-x: auto; }
.inv-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.inv-table thead th {
    background: linear-gradient(135deg, var(--inv-navy), var(--inv-blue));
    color: #fff;
    text-align: left;
    padding: 0.62rem 0.85rem;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}
.inv-table thead th.inv-num { text-align: right; }
.inv-table td {
    padding: 0.66rem 0.85rem;
    border-bottom: 1px solid #f4f7fb;
    color: #334155;
    vertical-align: middle;
}
.inv-table tbody tr:last-child td { border-bottom: none; }
.inv-table tbody tr:hover td { background: var(--inv-soft); }
.inv-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.inv-th-actions { text-align: right; }
.inv-actions { text-align: right; white-space: nowrap; }
.inv-nowrap { white-space: nowrap; }
.inv-muted { color: #94a3b8; font-size: 0.75rem; }
.inv-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }
.inv-empty a { color: var(--inv-navy); font-weight: 700; }
.inv-folio { font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
.inv-client { font-weight: 700; color: #0f172a; }
.inv-types { display: flex; flex-wrap: wrap; gap: 0.28rem; }
.inv-type {
    display: inline-flex;
    align-items: center;
    gap: 0.22rem;
    padding: 0.14rem 0.5rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    white-space: nowrap;
}
.inv-type--air { background: #EAF6FB; color: #0E6E8C; border: 1px solid #BFE3F0; }
.inv-type--sea { background: #FDF3E8; color: #9A5B12; border: 1px solid #F0D4A8; }
.inv-paq {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.65rem;
    height: 1.65rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    background: #EAF1FC;
    color: var(--inv-blue);
    font-size: 0.75rem;
    font-weight: 800;
}
.inv-status {
    display: inline-flex;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.03em;
    white-space: nowrap;
}
.inv-status--green { background: #EFFAF4; color: #116039; border: 1px solid #A7DFC3; }
.inv-status--blue { background: #EAF1FC; color: var(--inv-blue); border: 1px solid #C9DAF3; }
.inv-status--amber { background: #FDF7E8; color: #92610B; border: 1px solid #F0D48A; }
.inv-status--red { background: #FDECEC; color: #B03030; border: 1px solid #F6C9C9; }
.inv-status--gray { background: #F1F5F9; color: #64748b; }

.inv-form-inline { display: inline-flex; margin: 0; }
.inv-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    border: 1px solid #d1d9e6;
    background: #fff;
    color: #475569;
    cursor: pointer;
    text-decoration: none;
    margin-left: 0.2rem;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.inv-icon-btn:hover { background: var(--inv-soft); color: var(--inv-navy); border-color: var(--inv-border); }
.inv-icon-btn.is-sent { color: var(--inv-green); border-color: #A7DFC3; background: #EFFAF4; }
.inv-icon-btn.is-disabled,
.inv-icon-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.inv-text-green { color: var(--inv-green); }

.inv-card-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1.1rem;
    border-top: 1px solid var(--inv-line);
}
.inv-pager-wrap .pt-pager { display: flex; align-items: center; gap: 0.3rem; }
.inv-pager-wrap .pt-pager-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2rem; height: 2rem; padding: 0 0.45rem;
    border-radius: 0.45rem; border: 1px solid #d1d9e6;
    background: #fff; color: #334155; text-decoration: none; font-size: 0.8rem; font-weight: 700;
}
.inv-pager-wrap .pt-pager-btn-active { background: var(--inv-navy); color: #fff; border-color: var(--inv-navy); }
.inv-pager-wrap .pt-pager-btn-disabled { opacity: 0.4; }
.inv-pager-wrap .pt-pager-ellipsis { color: #94a3b8; padding: 0 0.2rem; }

@media (max-width: 1100px) {
    .inv-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .inv-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .inv-field { max-width: none; }
}
</style>
@endsection
