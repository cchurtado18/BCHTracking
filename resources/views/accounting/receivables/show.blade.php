@extends('layouts.app')

@section('title', 'Estado de cuenta — '.$agency->name)

@section('content')
@php
    $creditNotes = $creditNotes ?? collect();
    $creditMovements = $creditMovements ?? collect();
    $statement = $statement ?? (object) [];
    $aging = $statement->aging ?? (object) ['current' => 0, 'd1_30' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd90' => 0];
    $net = (float) ($statement->net_position ?? ($balance - ($creditBalance ?? 0)));
    $tz = (string) config('app.display_timezone');
@endphp
<div class="st-page">
    <x-module-banner
        class="no-print"
        section="Contabilidad"
        current="Estado de cuenta"
        title="Estado de cuenta"
        subtitle="{{ $agency->name }} · {{ $agency->typeLabel() }}. Saldos, facturas, cobros y crédito del cliente."
        back-href="{{ route('accounting.receivables.index') }}"
        back-label="Volver a CxC"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6.75m.75-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('agencies.show', $agency) }}" class="mb-btn mb-btn-secondary">Ver cliente</a>
            <a href="{{ route('accounting.credit-notes.create', ['agency_id' => $agency->id]) }}" class="mb-btn mb-btn-secondary">Nota de crédito</a>
            <button type="button" class="mb-btn mb-btn-secondary" onclick="window.print()">Imprimir</button>
            <a href="{{ route('accounting.payments.create', ['agency_id' => $agency->id]) }}" class="mb-btn mb-btn-primary">Registrar cobro</a>
        </x-slot:actions>
        <x-slot:strip>
            <span class="mb-strip-label">Cliente</span>
            <span class="mb-pill">Código <strong>{{ $agency->code }}</strong></span>
            <span class="mb-pill">{{ $agency->typeLabel() }}</span>
            @if($agency->parent)
            <span class="mb-pill">{{ $agency->parent->name }}</span>
            @endif
            @if($agency->phone)
            <span class="mb-pill">{{ $agency->phone }}</span>
            @endif
            <span class="mb-pill">{{ $agency->billingEmail() ?: 'Sin correo' }}</span>
            <span class="mb-pill">Plazo {{ (int) ($agency->credit_days ?? 0) }} días</span>
            <span class="mb-pill {{ $balance > 0 ? 'mb-pill--warn' : 'mb-pill--ok' }}">Pendiente <strong>${{ number_format($balance, 2) }}</strong></span>
            <span class="mb-pill mb-pill--ok">A favor <strong>${{ number_format($creditBalance ?? 0, 2) }}</strong></span>
        </x-slot:strip>
    </x-module-banner>

    @if(session('success'))
    <div class="st-alert st-alert-ok no-print">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="st-alert st-alert-err no-print">{{ session('error') }}</div>
    @endif

    <div class="st-kpis">
        <div class="st-kpi">
            <div class="st-kpi-top">
                <span class="st-kpi-icon st-kpi-icon--receipt" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                </span>
                <span class="st-kpi-label">Saldo pendiente</span>
            </div>
            <span class="st-kpi-value {{ $balance > 0 ? 'st-neg' : '' }}">${{ number_format($balance, 2) }}</span>
            <span class="st-kpi-note">{{ (int) ($statement->open_count ?? 0) }} factura(s) abierta(s)</span>
        </div>
        <div class="st-kpi">
            <div class="st-kpi-top">
                <span class="st-kpi-icon st-kpi-icon--wallet" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg>
                </span>
                <span class="st-kpi-label">Saldo a favor</span>
            </div>
            <span class="st-kpi-value st-pos">${{ number_format($creditBalance ?? 0, 2) }}</span>
            <span class="st-kpi-note">Disponible para aplicar</span>
        </div>
        <div class="st-kpi">
            <div class="st-kpi-top">
                <span class="st-kpi-icon st-kpi-icon--trend" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
                </span>
                <span class="st-kpi-label">Posición neta</span>
            </div>
            <span class="st-kpi-value {{ $net > 0 ? 'st-neg' : 'st-pos' }}">${{ number_format(abs($net), 2) }}</span>
            <span class="st-kpi-note">{{ $net > 0.005 ? 'A cobrar' : ($net < -0.005 ? 'A favor del cliente' : 'Cuenta al día') }}</span>
        </div>
        <div class="st-kpi">
            <div class="st-kpi-top">
                <span class="st-kpi-icon st-kpi-icon--alert" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                </span>
                <span class="st-kpi-label">En mora</span>
            </div>
            <span class="st-kpi-value {{ ($statement->overdue_usd ?? 0) > 0 ? 'st-neg' : '' }}">${{ number_format($statement->overdue_usd ?? 0, 2) }}</span>
            <span class="st-kpi-note">{{ (int) ($statement->overdue_count ?? 0) }} factura(s) vencida(s)</span>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-head">
            <h2 class="st-card-title">Antigüedad del saldo pendiente</h2>
            <span class="st-card-note">Según los días de atraso de cada factura abierta</span>
        </div>
        <div class="st-card-body st-aging">
            <div class="st-mini-kpi">
                <span class="st-kpi-label">Al día</span>
                <strong>${{ number_format($aging->current ?? 0, 2) }}</strong>
            </div>
            <div class="st-mini-kpi">
                <span class="st-kpi-label">1–30 días</span>
                <strong>${{ number_format($aging->d1_30 ?? 0, 2) }}</strong>
            </div>
            <div class="st-mini-kpi">
                <span class="st-kpi-label">31–60 días</span>
                <strong>${{ number_format($aging->d31_60 ?? 0, 2) }}</strong>
            </div>
            <div class="st-mini-kpi">
                <span class="st-kpi-label">61–90 días</span>
                <strong>${{ number_format($aging->d61_90 ?? 0, 2) }}</strong>
            </div>
            <div class="st-mini-kpi">
                <span class="st-kpi-label">Más de 90</span>
                <strong class="{{ ($aging->d90 ?? 0) > 0 ? 'st-neg' : '' }}">${{ number_format($aging->d90 ?? 0, 2) }}</strong>
            </div>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-head">
            <h2 class="st-card-title">Movimiento de la cuenta</h2>
        </div>
        <div class="st-card-body st-aging">
            <div class="st-mini-kpi">
                <span class="st-kpi-label">Facturado</span>
                <strong>${{ number_format($statement->billed ?? 0, 2) }}</strong>
            </div>
            <div class="st-mini-kpi">
                <span class="st-kpi-label">Cobrado</span>
                <strong class="st-pos">${{ number_format($statement->collected ?? 0, 2) }}</strong>
            </div>
            <div class="st-mini-kpi">
                <span class="st-kpi-label">Notas emitidas</span>
                <strong>${{ number_format($statement->credits_issued ?? 0, 2) }}</strong>
            </div>
            <div class="st-mini-kpi">
                <span class="st-kpi-label">Crédito disponible</span>
                <strong class="st-pos">${{ number_format($statement->credits_remaining ?? 0, 2) }}</strong>
            </div>
            <div class="st-mini-kpi">
                <span class="st-kpi-label">Pendiente</span>
                <strong class="{{ $balance > 0 ? 'st-neg' : '' }}">${{ number_format($balance, 2) }}</strong>
            </div>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-head">
            <h2 class="st-card-title">Facturas</h2>
            <span class="st-card-note">{{ $invoices->count() }} registro(s)</span>
        </div>
        <div class="st-scroll">
            <table class="st-table">
                <colgroup>
                    <col style="width:12%"><col style="width:12%"><col style="width:10%"><col style="width:10%">
                    <col style="width:10%"><col style="width:10%"><col style="width:10%"><col style="width:11%">
                    <col style="width:7%"><col class="st-hide-print" style="width:8%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Hoja</th>
                        <th>Emisión</th>
                        <th>Vencimiento</th>
                        <th class="st-num">Monto</th>
                        <th class="st-num">Cobrado</th>
                        <th class="st-num">Saldo</th>
                        <th>Estado</th>
                        <th class="st-num">Mora</th>
                        <th class="st-actions st-hide-print">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    @php $balanceRow = $invoice->balanceUsd(); $mora = $invoice->daysOverdue(); @endphp
                    <tr>
                        <td><a href="{{ route('accounting.invoices.show', $invoice) }}" class="st-link">{{ $invoice->folio }}</a></td>
                        <td class="st-nowrap">{{ $invoice->deliveryNote?->code ?? '—' }}</td>
                        <td class="st-nowrap">{{ optional($invoice->issued_at)->format('d/m/Y') ?? '—' }}</td>
                        <td class="st-nowrap">{{ $invoice->dueAt()?->format('d/m/Y') ?? '—' }}</td>
                        <td class="st-num">${{ number_format((float) $invoice->total_usd, 2) }}</td>
                        <td class="st-num st-pos">${{ number_format((float) $invoice->amount_paid, 2) }}</td>
                        <td class="st-num {{ $balanceRow > 0 ? 'st-neg' : 'st-pos' }}">${{ number_format($balanceRow, 2) }}</td>
                        <td><span class="st-status st-status--{{ $invoice->arStatus() }}">{{ $invoice->arStatusLabel() }}</span></td>
                        <td class="st-num {{ $mora > 0 ? 'st-neg' : 'st-dim' }}">{{ $mora > 0 ? $mora.' d' : '—' }}</td>
                        <td class="st-actions st-hide-print"><a href="{{ route('accounting.invoices.show', $invoice) }}" class="st-mini">Ver</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="st-empty">Sin facturas.</td></tr>
                    @endforelse
                </tbody>
                @if($invoices->isNotEmpty())
                <tfoot>
                    <tr>
                        <th colspan="4">Totales</th>
                        <th class="st-num">${{ number_format($statement->billed ?? 0, 2) }}</th>
                        <th class="st-num">${{ number_format($invoices->sum(fn ($i) => (float) $i->amount_paid), 2) }}</th>
                        <th class="st-num">${{ number_format($balance, 2) }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-head">
            <h2 class="st-card-title">Cobros</h2>
            <span class="st-card-note">{{ $payments->count() }} registro(s)</span>
        </div>
        <div class="st-scroll">
            <table class="st-table">
                <colgroup>
                    <col style="width:12%"><col style="width:12%"><col style="width:28%"><col style="width:28%">
                    <col style="width:12%"><col class="st-hide-print" style="width:8%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="st-num">Monto</th>
                        <th>Método</th>
                        <th>Aplicado a</th>
                        <th>Estado</th>
                        <th class="st-actions st-hide-print">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td class="st-nowrap">{{ $payment->paid_at->format('d/m/Y') }}</td>
                        <td class="st-num st-pos">${{ number_format((float) $payment->amount_usd, 2) }}</td>
                        <td>{{ $payment->methodLabel() }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</td>
                        <td>
                            @forelse($payment->allocations as $allocation)
                            <a href="{{ route('accounting.invoices.show', $allocation->accounting_invoice_id) }}" class="st-link">{{ $allocation->invoice?->folio }}</a>
                            (${{ number_format((float) $allocation->amount_usd, 2) }}){{ $loop->last ? '' : ', ' }}
                            @empty
                            <span class="st-dim">Sin aplicar a factura</span>
                            @endforelse
                        </td>
                        <td><span class="st-status {{ $payment->isVoid() ? 'st-status--overdue' : 'st-status--paid' }}">{{ $payment->isVoid() ? 'Cancelado' : 'Activo' }}</span></td>
                        <td class="st-actions st-hide-print"><a href="{{ route('accounting.payments.show', $payment) }}" class="st-mini">Ver</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="st-empty">
                            Sin cobros registrados.
                            <a href="{{ route('accounting.payments.create', ['agency_id' => $agency->id]) }}" class="st-link no-print">Registrar el primero</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($payments->isNotEmpty())
                <tfoot>
                    <tr>
                        <th>Cobrado activo</th>
                        <th class="st-num">${{ number_format($statement->collected ?? 0, 2) }}</th>
                        <th colspan="4"></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-head">
            <h2 class="st-card-title">Notas de crédito</h2>
            <span class="st-card-note">{{ $creditNotes->count() }} registro(s)</span>
        </div>
        <div class="st-scroll">
            <table class="st-table">
                <colgroup>
                    <col style="width:12%"><col style="width:12%"><col style="width:12%"><col style="width:12%">
                    <col style="width:32%"><col style="width:12%"><col class="st-hide-print" style="width:8%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th class="st-num">Monto</th>
                        <th class="st-num">Aplicado</th>
                        <th class="st-num">Restante</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th class="st-actions st-hide-print">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditNotes as $note)
                    @php $usage = $note->usageStatus(); @endphp
                    <tr>
                        <td><a href="{{ route('accounting.credit-notes.show', $note) }}" class="st-link">{{ $note->folio }}</a></td>
                        <td class="st-num">${{ number_format((float) $note->amount_usd, 2) }}</td>
                        <td class="st-num">${{ number_format($note->appliedUsd(), 2) }}</td>
                        <td class="st-num {{ $note->remainingUsd() > 0 ? 'st-pos' : '' }}">${{ number_format($note->remainingUsd(), 2) }}</td>
                        <td>{{ $note->reason }}</td>
                        <td>
                            <span class="st-status st-status--{{ $usage === 'applied' ? 'paid' : ($usage === 'void' ? 'overdue' : ($usage === 'partial' ? 'current' : 'open')) }}">{{ $note->usageStatusLabel() }}</span>
                        </td>
                        <td class="st-actions st-hide-print"><a href="{{ route('accounting.credit-notes.show', $note) }}" class="st-mini">Ver</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="st-empty">
                            Sin notas de crédito.
                            <a href="{{ route('accounting.credit-notes.create', ['agency_id' => $agency->id]) }}" class="st-link no-print">Crear una</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-head">
            <h2 class="st-card-title">Movimientos de saldo a favor</h2>
            <span class="st-card-note">{{ $creditMovements->count() }} registro(s)</span>
        </div>
        <div class="st-scroll">
            <table class="st-table">
                <colgroup>
                    <col style="width:16%"><col style="width:22%"><col style="width:14%"><col style="width:48%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th class="st-num">Monto</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditMovements as $movement)
                    <tr>
                        <td class="st-nowrap">{{ $movement->created_at?->timezone($tz)->format('d/m/Y H:i') }}</td>
                        <td>{{ $movement->typeLabel() }}</td>
                        <td class="st-num {{ (float) $movement->amount_usd >= 0 ? 'st-pos' : 'st-neg' }}">{{ (float) $movement->amount_usd >= 0 ? '+' : '' }}${{ number_format((float) $movement->amount_usd, 2) }}</td>
                        <td>
                            @if($movement->invoice)
                            <a href="{{ route('accounting.invoices.show', $movement->invoice) }}" class="st-link">{{ $movement->invoice->folio }}</a>
                            @endif
                            @if($movement->notes)
                            {{ $movement->invoice ? ' · ' : '' }}{{ $movement->notes }}
                            @elseif(! $movement->invoice)
                            —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="st-empty">Sin movimientos de crédito.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.st-page {
    --navy: #0A2D6F; --blue: #1E4FA8; --green: #16794C; --red: #D64545;
    --line: #E8EEF8; --soft: #F4F8FD;
    padding: 0 0 2.25rem; max-width: 96rem; margin: 0 auto; width: 100%;
}
.st-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1.15rem; font-size: 0.875rem; font-weight: 600; }
.st-alert-ok { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; }
.st-alert-err { background: #FDECEC; border: 1px solid #F6C9C9; color: #B03030; }
.st-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.7rem; margin-bottom: 1.15rem; }
.st-kpi {
    background: #fff; border: 1px solid var(--line); border-radius: 0.8rem;
    padding: 0.8rem 0.9rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex; flex-direction: column; gap: 0.45rem; min-width: 0;
}
.st-kpi-top { display: flex; align-items: center; gap: 0.4rem; }
.st-kpi-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.6rem; height: 1.6rem; border-radius: 0.45rem; flex-shrink: 0;
}
.st-kpi-icon--receipt { background: #FDECEC; color: var(--red); }
.st-kpi-icon--wallet { background: #EFFAF4; color: var(--green); }
.st-kpi-icon--trend { background: #EAF1FC; color: var(--blue); }
.st-kpi-icon--alert { background: #FDECEC; color: var(--red); }
.st-kpi-label { font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
.st-kpi-value { font-size: 1.28rem; font-weight: 800; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; color: #0f172a; line-height: 1.1; }
.st-kpi-note { font-size: 0.68rem; color: #94a3b8; border-top: 1px dashed var(--line); padding-top: 0.42rem; }
.st-card {
    background: #fff; border: 1px solid var(--line); border-radius: 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; margin-bottom: 1.15rem;
}
.st-card-head {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.6rem;
    padding: 0.85rem 1.1rem; border-bottom: 1px solid var(--line);
}
.st-card-title { margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; }
.st-card-note { font-size: 0.75rem; color: #94a3b8; }
.st-card-body { padding: 0.95rem 1.1rem; }
.st-aging { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.65rem; }
.st-mini-kpi { background: var(--soft); border: 1px solid var(--line); border-radius: 0.65rem; padding: 0.7rem 0.8rem; }
.st-mini-kpi strong { display: block; margin-top: 0.2rem; font-size: 1.1rem; font-variant-numeric: tabular-nums; }
.st-scroll { overflow-x: auto; }
.st-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 0.85rem; }
.st-table th, .st-table td { padding: 0.7rem 0.85rem; text-align: left; vertical-align: middle; box-sizing: border-box; }
.st-table thead th {
    background: linear-gradient(135deg, var(--navy), var(--blue)); color: #fff;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap;
}
.st-table td { border-bottom: 1px solid #f4f7fb; color: #334155; }
.st-table tbody tr:hover td { background: var(--soft); }
.st-table tfoot th, .st-table tfoot td { border-bottom: 0; border-top: 1px solid var(--line); font-weight: 800; background: #FAFCFF; }
.st-num { text-align: right !important; font-variant-numeric: tabular-nums; white-space: nowrap; }
.st-actions { text-align: right !important; white-space: nowrap; }
.st-nowrap { white-space: nowrap; }
.st-link { color: var(--blue); font-weight: 800; text-decoration: none; }
.st-link:hover { color: var(--navy); text-decoration: underline; }
.st-empty { text-align: center; color: #94a3b8; padding: 1.2rem 0.5rem !important; }
.st-dim { color: #94a3b8; }
.st-pos { color: var(--green); }
.st-neg { color: var(--red); }
.st-status { display: inline-flex; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.68rem; font-weight: 800; white-space: nowrap; }
.st-status--paid { background: #EFFAF4; color: #116039; border: 1px solid #A7DFC3; }
.st-status--current, .st-status--open { background: #EAF1FC; color: var(--blue); border: 1px solid #C9DAF3; }
.st-status--overdue { background: #FDECEC; color: #B03030; border: 1px solid #F6C9C9; }
.st-mini { font-size: 0.72rem; font-weight: 700; color: var(--blue); text-decoration: none; }
.st-mini:hover { text-decoration: underline; }
@media (max-width: 1100px) { .st-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .st-aging { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 640px) { .st-kpis, .st-aging { grid-template-columns: 1fr; } }
@media print {
    .no-print, .st-hide-print, .mb-banner, .sidebar, .sidebar-backdrop, #sidebar-open, #sidebar-close { display: none !important; }
    .app-layout { display: block; }
    .app-main, .app-main-inner { padding: 0 !important; margin: 0 !important; background: #fff !important; }
    .st-page { max-width: none; padding: 0; }
    .st-card { box-shadow: none; break-inside: avoid; }
    a { color: inherit !important; text-decoration: none !important; }
}
</style>
@endsection
