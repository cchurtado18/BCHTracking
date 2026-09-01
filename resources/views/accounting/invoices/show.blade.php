@extends('layouts.app')

@section('title', $invoice->folio)

@section('content')
@php
    $sendEmail = $invoice->agency?->billingEmail();
    $balance = $invoice->balanceUsd();
    $paid = (float) $invoice->amount_paid;
    $tz = (string) config('app.display_timezone', config('app.timezone'));
    $statusClass = match ($invoice->status) {
        'paid' => 'inv-status--green',
        'partially_paid' => 'inv-status--blue',
        'issued' => 'inv-status--amber',
        'void' => 'inv-status--red',
        default => 'inv-status--gray',
    };
    $stripPill = match ($invoice->status) {
        'paid' => 'mb-pill--ok',
        'void' => 'mb-pill--warn',
        'partially_paid' => 'inv-pill--blue',
        'issued' => 'inv-pill--amber',
        default => '',
    };
    $saldoTone = $invoice->isVoid() ? 'gray' : ($balance > 0 ? 'red' : 'green');
    $statusTone = match ($invoice->status) {
        'paid' => 'green',
        'partially_paid' => 'blue',
        'issued' => 'amber',
        'void' => 'red',
        default => 'gray',
    };
    $serviceLabels = \App\Support\ServiceType::options();
    $due = $invoice->dueAt();
    $isAdmin = auth()->user()?->is_admin;
    $noteHref = $invoice->deliveryNote
        ? ($isAdmin
            ? route('salidas.hojas.edit', $invoice->deliveryNote)
            : route('salidas.print-report', ['delivery_note_id' => $invoice->deliveryNote->id]))
        : null;
@endphp
<div class="inv-page">
    <x-module-banner
        section="Contabilidad"
        current="Factura"
        title="{{ $invoice->folio }}"
        subtitle="Factura PrimeTrack{{ $invoice->deliveryNote ? ' · Hoja '.$invoice->deliveryNote->code : '' }} · {{ $invoice->agency?->name ?? 'Cliente' }}"
        back-href="{{ route('accounting.invoices.index') }}"
        back-label="Volver a facturas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            @if(! $invoice->isVoid())
            <a href="{{ route('accounting.invoices.voucher', $invoice) }}" target="_blank" class="mb-btn mb-btn-secondary">Imprimir voucher</a>
            <a href="{{ route('accounting.invoices.pdf', $invoice) }}" class="mb-btn mb-btn-secondary">Descargar PDF</a>
            @if($isAdmin)
            <form action="{{ route('accounting.invoices.send', $invoice) }}" method="POST" onsubmit="return confirm('¿Enviar la factura {{ addslashes($invoice->folio) }} a {{ addslashes($sendEmail ?: '—') }}?');">
                @csrf
                <button type="submit" class="mb-btn mb-btn-primary" @disabled(! $sendEmail) title="{{ $sendEmail ? 'Enviar a '.$sendEmail : 'Sin correo registrado' }}">Enviar al cliente</button>
            </form>
            @if($balance > 0)
            <a href="{{ route('accounting.payments.create', ['invoice_id' => $invoice->id]) }}" class="mb-btn mb-btn-secondary">Registrar cobro</a>
            @endif
            @endif
            @endif
            @if($noteHref)
            <a href="{{ $noteHref }}" @unless($isAdmin) target="_blank" @endunless class="mb-btn mb-btn-secondary">Ver hoja de salida</a>
            @endif
        </x-slot:actions>
        <x-slot:strip>
            <span class="mb-strip-label">Estado</span>
            <span class="mb-pill {{ $stripPill }}">{{ $invoice->statusLabel() }}</span>
            @if($invoice->agency)
            <span class="mb-pill">{{ $invoice->agency->code }} · {{ $invoice->agency->name }}</span>
            @endif
            @if($invoice->deliveryNote)
            <span class="mb-pill">Hoja {{ $invoice->deliveryNote->code }}</span>
            @endif
            @if($due)
            <span class="mb-pill {{ $invoice->arStatus() === 'overdue' ? 'mb-pill--warn' : '' }}">Vence {{ $due->format('d/m/Y') }}</span>
            @endif
            @if($isAdmin)
            @if($invoice->emailed_at)
            <span class="mb-pill mb-pill--ok">Enviada {{ $invoice->emailed_at->timezone($tz)->format('d/m/Y H:i') }}</span>
            @elseif(! $invoice->isVoid())
            <span class="mb-pill">Sin enviar</span>
            @endif
            @endif
        </x-slot:strip>
    </x-module-banner>

    @if(session('success'))
    <div class="inv-alert inv-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="inv-alert inv-alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="inv-alert inv-alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="inv-kpis">
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">
                <span class="inv-kpi-ico inv-kpi-ico--blue" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33"/></svg>
                </span>
                Total USD
            </span>
            <span class="inv-kpi-value inv-text-blue">${{ number_format((float) $invoice->total_usd, 2) }}</span>
            <span class="inv-kpi-note">C$ {{ number_format((float) $invoice->total_cor, 2) }}</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">
                <span class="inv-kpi-ico inv-kpi-ico--green" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                Pagado
            </span>
            <span class="inv-kpi-value inv-text-green">${{ number_format($paid, 2) }}</span>
            <span class="inv-kpi-note">{{ $paid > 0 ? 'Cobros aplicados' : 'Sin cobros' }}</span>
        </div>
        <div class="inv-kpi-card inv-kpi-card--{{ $saldoTone }}">
            <span class="inv-kpi-label">
                <span class="inv-kpi-ico inv-kpi-ico--{{ $saldoTone }}" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
                </span>
                Saldo
            </span>
            <span class="inv-kpi-value inv-text-{{ $saldoTone }}">${{ number_format($balance, 2) }}</span>
            <span class="inv-kpi-note">{{ $invoice->isVoid() ? 'Anulada' : $invoice->arStatusLabel() }}</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">
                <span class="inv-kpi-ico inv-kpi-ico--blue" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                </span>
                Libras
            </span>
            <span class="inv-kpi-value">{{ number_format((float) $invoice->total_lbs, 2) }}</span>
            <span class="inv-kpi-note">{{ $invoice->lines->count() }} {{ $invoice->lines->count() === 1 ? 'línea' : 'líneas' }}</span>
        </div>
        <div class="inv-kpi-card">
            <span class="inv-kpi-label">
                <span class="inv-kpi-ico inv-kpi-ico--slate" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                T.C. COR/USD
            </span>
            <span class="inv-kpi-value">{{ number_format((float) $invoice->exchange_rate, 4) }}</span>
            <span class="inv-kpi-note">Emitida {{ optional($invoice->issued_at)->format('d/m/Y') ?? '—' }}</span>
        </div>
        <div class="inv-kpi-card inv-kpi-card--{{ $statusTone }}">
            <span class="inv-kpi-label">
                <span class="inv-kpi-ico inv-kpi-ico--{{ $statusTone }}" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                Estado
            </span>
            <span class="inv-kpi-status inv-text-{{ $statusTone }}">{{ $invoice->statusLabel() }}</span>
        </div>
    </div>

    <div class="inv-layout">
        <div class="inv-main">
            <div class="inv-card">
                <div class="inv-table-head">
                    <div class="inv-table-head-left">
                        <span class="inv-section-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        </span>
                        <h2 class="inv-section-title">Cliente y emisión</h2>
                    </div>
                    @if($isAdmin && $invoice->agency)
                    <a href="{{ route('accounting.receivables.show', $invoice->agency) }}" class="inv-ghost-link">Estado de cuenta</a>
                    @endif
                </div>
                <dl class="inv-meta">
                    <div>
                        <dt>Cliente</dt>
                        <dd>
                            <strong>{{ $invoice->agency?->name ?? '—' }}</strong>
                            @if($invoice->agency?->code)
                            <span class="inv-muted">{{ $invoice->agency->code }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Emitida</dt>
                        <dd>{{ optional($invoice->issued_at)->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Registró</dt>
                        <dd>{{ $invoice->createdBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Correo de facturación</dt>
                        <dd>{{ $sendEmail ?: 'Sin correo' }}</dd>
                    </div>
                    <div>
                        <dt>Hoja de salida</dt>
                        <dd>
                            @if($invoice->deliveryNote && $noteHref)
                            <a href="{{ $noteHref }}" @unless($isAdmin) target="_blank" @endunless class="inv-inline-link">{{ $invoice->deliveryNote->code }}</a>
                            @else
                            —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Vencimiento</dt>
                        <dd>{{ $due?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    @if($invoice->isVoid())
                    <div>
                        <dt>Anulada</dt>
                        <dd>{{ optional($invoice->voided_at)->timezone($tz)->format('d/m/Y H:i') ?? '—' }}@if($invoice->voidedBy) · {{ $invoice->voidedBy->name }}@endif</dd>
                    </div>
                    <div class="inv-meta-full">
                        <dt>Motivo</dt>
                        <dd>{{ $invoice->void_reason ?: '—' }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <div class="inv-card">
                <div class="inv-table-head">
                    <div class="inv-table-head-left">
                        <span class="inv-section-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                        </span>
                        <h2 class="inv-section-title">Líneas por servicio</h2>
                    </div>
                    <span class="inv-table-head-note">{{ $invoice->lines->count() }} {{ $invoice->lines->count() === 1 ? 'línea' : 'líneas' }}</span>
                </div>
                <div class="inv-table-scroll">
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th class="inv-num">Cantidad</th>
                                <th class="inv-num">Tarifa</th>
                                <th class="inv-num">Importe USD</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->lines as $line)
                            <tr>
                                <td>
                                    <div class="inv-line-name">{{ $line->description }}</div>
                                    @if($line->service_type)
                                    <span class="inv-type inv-type--{{ strtolower($line->service_type) }}">
                                        {{ \App\Support\ServiceType::icon($line->service_type) }} {{ $serviceLabels[$line->service_type] ?? $line->service_type }}
                                    </span>
                                    @endif
                                </td>
                                <td class="inv-num">{{ number_format((float) $line->quantity_lbs, 2) }} {{ \App\Support\ServiceType::unit($line->service_type) }}</td>
                                <td class="inv-num">${{ number_format((float) $line->rate_per_lb, 4) }}</td>
                                <td class="inv-num"><strong>${{ number_format((float) $line->amount_usd, 2) }}</strong></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="inv-empty">Esta factura no tiene líneas.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($invoice->lines->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td class="inv-tfoot-label">Totales</td>
                                <td class="inv-num">{{ number_format((float) $invoice->total_lbs, 2) }}</td>
                                <td></td>
                                <td class="inv-num inv-text-green">${{ number_format((float) $invoice->total_usd, 2) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            @if($isAdmin && ($invoice->canVoid() || ($invoice->isVoid() && $invoice->canDelete()) || ((float) $invoice->amount_paid > 0 && ! $invoice->isVoid() && ! $invoice->canVoid())))
            <div class="inv-card" id="anular">
                <div class="inv-table-head">
                    <div class="inv-table-head-left">
                        <span class="inv-section-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                        </span>
                        <h2 class="inv-section-title">Acciones</h2>
                    </div>
                </div>
                <div class="inv-card-body">
                    @if($invoice->canVoid())
                    <p class="inv-help">Anular conserva el folio {{ $invoice->folio }} y libera la hoja de salida para emitir otra factura.</p>
                    <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" onsubmit="return confirm('¿Anular la factura {{ addslashes($invoice->folio) }}?');">
                        @csrf
                        <div class="inv-field inv-field-full">
                            <label class="inv-label" for="void_reason">Motivo de anulación *</label>
                            <input type="text" name="void_reason" id="void_reason" value="{{ old('void_reason') }}" required minlength="5" maxlength="500" class="inv-input" placeholder="Ej. Error en tarifas o se emitió al cliente incorrecto">
                            @error('void_reason')
                            <p class="inv-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="inv-form-actions">
                            <button type="submit" class="inv-btn inv-btn-danger">Anular factura</button>
                        </div>
                    </form>
                    @elseif($invoice->isVoid() && $invoice->canDelete())
                    <p class="inv-help">Esta factura está anulada. Eliminarla quita el registro; el folio no se reutiliza de forma garantizada.</p>
                    <form action="{{ route('accounting.invoices.destroy', $invoice) }}" method="POST" class="inv-form-actions" onsubmit="return confirm('¿Eliminar la factura {{ addslashes($invoice->folio) }}? Esta acción no se puede deshacer.');">
                        @csrf
                        @method('DELETE')
                        @if($invoice->deliveryNote)
                        <a href="{{ route('accounting.invoices.create-from-note', $invoice->deliveryNote) }}" class="inv-btn inv-btn-primary">Emitir nueva para esta hoja</a>
                        @endif
                        <button type="submit" class="inv-btn inv-btn-danger">Eliminar factura</button>
                    </form>
                    @elseif((float) $invoice->amount_paid > 0)
                    <p class="inv-help">No se puede anular mientras tenga cobros aplicados.</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <aside class="inv-preview-col">
            <div class="inv-card inv-preview-card">
                <div class="inv-preview-head">
                    <div class="inv-table-head-left">
                        <span class="inv-section-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.082m.72-.082a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.082m-.72-.082L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V6.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v.753"/></svg>
                        </span>
                        <h2 class="inv-section-title">Voucher</h2>
                    </div>
                    <span class="inv-status {{ $statusClass }}">{{ $invoice->statusLabel() }}</span>
                </div>
                <div class="inv-preview-stage">
                    <div class="inv-preview-paper {{ $invoice->isVoid() ? 'is-void' : '' }}">
                        @include('accounting.invoices.partials.voucher-ticket')
                        @if($invoice->isVoid())
                        <div class="inv-void-stamp" aria-hidden="true">ANULADA</div>
                        @endif
                    </div>
                </div>
                <div class="inv-preview-foot">
                    @if(! $invoice->isVoid())
                    <a href="{{ route('accounting.invoices.voucher', $invoice) }}" target="_blank" class="inv-btn inv-btn-secondary inv-btn-sm">Abrir a tamaño real</a>
                    <a href="{{ route('accounting.invoices.pdf', $invoice) }}" class="inv-btn inv-btn-primary inv-btn-sm">Descargar PDF</a>
                    @else
                    <span class="inv-muted">El voucher de una factura anulada no se imprime.</span>
                    @endif
                </div>
            </div>
        </aside>
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
.inv-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.inv-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; font-weight: 600; }
.inv-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

.inv-kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.7rem;
    margin-bottom: 1.15rem;
}
.inv-kpi-card {
    background: #fff;
    border: 1px solid var(--inv-line);
    border-radius: 0.8rem;
    padding: 0.9rem 1rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
}
.inv-kpi-card--green { border-color: #A7DFC3; background: linear-gradient(180deg, #fff 35%, #F2FBF6 140%); }
.inv-kpi-card--amber { border-color: #F0D48A; background: linear-gradient(180deg, #fff 35%, #FDF7E8 140%); }
.inv-kpi-card--red { border-color: #F6C9C9; background: linear-gradient(180deg, #fff 35%, #FDECEC 140%); }
.inv-kpi-card--blue { border-color: #C9DAF3; background: linear-gradient(180deg, #fff 35%, #F4F8FD 140%); }
.inv-kpi-card--gray { border-color: #E2E8F0; background: linear-gradient(180deg, #fff 35%, #F8FAFC 140%); }
.inv-kpi-label {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
}
.inv-kpi-ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.45rem;
    height: 1.45rem;
    border-radius: 0.42rem;
    flex-shrink: 0;
}
.inv-kpi-ico--blue { background: #EAF1FC; color: var(--inv-blue); }
.inv-kpi-ico--slate { background: #F1F5F9; color: #64748b; }
.inv-kpi-ico--green { background: #EFFAF4; color: var(--inv-green); }
.inv-kpi-ico--amber { background: #FDF7E8; color: var(--inv-amber); }
.inv-kpi-ico--red { background: #FDECEC; color: var(--inv-red); }
.inv-kpi-ico--gray { background: #F1F5F9; color: #94a3b8; }
.inv-kpi-value {
    font-size: 1.28rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.inv-kpi-note { font-size: 0.7rem; color: #94a3b8; font-weight: 600; }
.inv-kpi-status { font-size: 1.05rem; font-weight: 800; }
.inv-text-green { color: var(--inv-green); }
.inv-text-red { color: var(--inv-red); }
.inv-text-blue { color: var(--inv-blue); }
.inv-text-amber { color: var(--inv-amber); }
.inv-text-gray { color: #64748b; }

.inv-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 21.5rem;
    gap: 1.15rem;
    align-items: start;
}
.inv-main { min-width: 0; }
.inv-card {
    background: #fff;
    border: 1px solid var(--inv-line);
    border-radius: 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 1.15rem;
}
.inv-card-body { padding: 1rem 1.1rem 1.15rem; }
.inv-table-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid var(--inv-line);
}
.inv-table-head-left { display: flex; align-items: center; gap: 0.55rem; }
.inv-table-head-note { font-size: 0.75rem; color: #94a3b8; }
.inv-section-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.5rem;
    background: var(--inv-soft);
    border: 1px solid var(--inv-line);
    color: var(--inv-navy);
    flex-shrink: 0;
}
.inv-section-title { margin: 0; font-size: 1.02rem; font-weight: 800; color: #0f172a; }

.inv-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.85rem 1.1rem;
    margin: 0;
    padding: 1.05rem 1.15rem 1.15rem;
}
.inv-meta > div { min-width: 0; }
.inv-meta dt {
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
    margin-bottom: 0.22rem;
}
.inv-meta dd {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 650;
    color: #0f172a;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}
.inv-meta-full { grid-column: 1 / -1; }
.inv-muted { color: #94a3b8; font-size: 0.75rem; font-weight: 600; }
.inv-inline-link, .inv-ghost-link {
    color: var(--inv-blue);
    font-weight: 800;
    text-decoration: none;
}
.inv-inline-link:hover, .inv-ghost-link:hover { color: var(--inv-navy); text-decoration: underline; }
.inv-ghost-link { font-size: 0.8rem; }

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
    padding: 0.72rem 0.85rem;
    border-bottom: 1px solid #f4f7fb;
    color: #334155;
    vertical-align: middle;
}
.inv-table tbody tr:last-child td { border-bottom: none; }
.inv-table tbody tr:hover td { background: var(--inv-soft); }
.inv-table tfoot td {
    background: var(--inv-soft);
    border-top: 2px solid var(--inv-line);
    font-weight: 800;
    color: #0f172a;
    padding: 0.72rem 0.85rem;
}
.inv-tfoot-label { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; }
.inv-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.inv-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }
.inv-line-name { font-weight: 700; color: #0f172a; margin-bottom: 0.22rem; }
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
.inv-pill--amber { background: #FDF7E8 !important; border-color: #F0D48A !important; color: #92610B !important; }
.inv-pill--blue { background: #EAF1FC !important; border-color: #C9DAF3 !important; color: #1E4FA8 !important; }

.inv-help { margin: 0 0 0.85rem; font-size: 0.85rem; color: var(--inv-muted); line-height: 1.45; }
.inv-field { display: flex; flex-direction: column; gap: 0.28rem; }
.inv-field-full { width: 100%; }
.inv-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.inv-input {
    padding: 0.58rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #D8DCE2;
    border-radius: 0.55rem;
    background: #fff;
    color: #0f172a;
    width: 100%;
    box-sizing: border-box;
}
.inv-input:focus { outline: none; border-color: var(--inv-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.inv-field-error { margin: 0; font-size: 0.78rem; color: #B03030; font-weight: 600; }
.inv-form-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; margin-top: 0.9rem; }
.inv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.55rem 1rem;
    font-size: 0.85rem;
    font-weight: 700;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
}
.inv-btn-primary {
    background: var(--inv-navy);
    color: #fff;
    border-color: var(--inv-navy);
    box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25);
}
.inv-btn-primary:hover { background: var(--inv-blue); border-color: var(--inv-blue); color: #fff; }
.inv-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.inv-btn-secondary:hover { background: var(--inv-soft); color: var(--inv-navy); border-color: var(--inv-border); }
.inv-btn-danger { background: #fff; color: #B03030; border-color: #F6C9C9; }
.inv-btn-danger:hover { background: #FDECEC; color: #B03030; }
.inv-btn-sm { padding: 0.42rem 0.8rem; font-size: 0.78rem; }

.inv-preview-col { position: sticky; top: 1rem; }
.inv-preview-card { margin-bottom: 0; }
.inv-preview-head { padding: 0.85rem 1rem; border-bottom: 1px solid var(--inv-line); display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
.inv-preview-stage {
    background:
        radial-gradient(circle at 20% 10%, rgba(30, 79, 168, 0.08), transparent 42%),
        linear-gradient(180deg, #EEF3FB 0%, #E4EBF6 100%);
    padding: 1.15rem 0.85rem 1.3rem;
    display: flex;
    justify-content: center;
}
.inv-preview-paper {
    position: relative;
    width: 72mm;
    max-width: 100%;
    background: #fff;
    padding: 10px 11px 14px;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.16);
    color: #111;
    font-family: "Courier New", Courier, monospace;
    font-size: 11px;
    line-height: 1.25;
}
.inv-preview-paper::before,
.inv-preview-paper::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    height: 8px;
    background:
        radial-gradient(circle at 6px 0, transparent 5px, #fff 5.5px) 0 0 / 12px 8px;
}
.inv-preview-paper::before { top: -8px; transform: rotate(180deg); }
.inv-preview-paper::after { bottom: -8px; }
.inv-preview-paper .vch-ticket { width: 100%; max-width: none; margin: 0; padding: 0; }
.inv-preview-paper .vch-center { text-align: center; }
.inv-preview-paper .vch-right { text-align: right; }
.inv-preview-paper .vch-sep { border: 0; border-top: 1px dashed #222; margin: 6px 0; }
.inv-preview-paper .vch-company { font-weight: 700; font-size: 12px; text-transform: uppercase; }
.inv-preview-paper .vch-muted { font-size: 10px; }
.inv-preview-paper .vch-title { font-weight: 700; font-size: 12.5px; letter-spacing: 0.04em; margin: 2px 0; }
.inv-preview-paper .vch-row { display: flex; justify-content: space-between; gap: 6px; }
.inv-preview-paper .vch-block { margin: 2px 0; }
.inv-preview-paper .vch-cols,
.inv-preview-paper .vch-line-nums {
    display: grid;
    grid-template-columns: 1.4fr 0.7fr 0.9fr 1fr;
    gap: 2px;
    font-size: 10px;
}
.inv-preview-paper .vch-cols { font-weight: 700; }
.inv-preview-paper .vch-line-name { margin-top: 4px; }
.inv-preview-paper .vch-line-nums span:nth-child(n+2) { text-align: right; }
.inv-preview-paper .vch-totals .vch-row,
.inv-preview-paper .vch-pay .vch-row { font-size: 11px; }
.inv-preview-paper .vch-sig { margin-top: 12px; min-height: 22px; border-bottom: 1px solid #222; }
.inv-preview-paper .vch-sig-note { margin-top: 5px; font-size: 10px; }
.inv-preview-paper .vch-footer { margin-top: 7px; font-weight: 700; }
.inv-void-stamp {
    position: absolute;
    inset: 28% 8%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #D64545;
    color: #D64545;
    font-family: Inter, system-ui, sans-serif;
    font-size: 1.35rem;
    font-weight: 900;
    letter-spacing: 0.18em;
    transform: rotate(-18deg);
    pointer-events: none;
    background: rgba(255, 255, 255, 0.35);
}
.inv-preview-paper.is-void { opacity: 0.92; }
.inv-preview-foot {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    padding: 0.8rem 1rem;
    border-top: 1px solid var(--inv-line);
    background: #fff;
}

@media (max-width: 1280px) {
    .inv-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .inv-layout { grid-template-columns: minmax(0, 1fr) 19rem; }
}
@media (max-width: 980px) {
    .inv-layout { grid-template-columns: 1fr; }
    .inv-preview-col { position: static; }
    .inv-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
    .inv-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .inv-meta { grid-template-columns: 1fr; }
}
</style>
@endsection
