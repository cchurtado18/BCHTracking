@extends('layouts.app')

@section('title', 'Nueva factura PrimeTrack')

@section('content')
<div class="pt-page">
    <x-module-banner
        section="Contabilidad"
        current="Nueva factura"
        title="Nueva factura PrimeTrack"
        subtitle="Elija una o más hojas del mismo cliente a facturar. Solo aparecen hojas con paquetes y sin factura activa."
        back-href="{{ route('accounting.invoices.index') }}"
        back-label="Volver a facturas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if($errors->any())
    <div class="pt-alert pt-alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="pt-card">
        <div class="pt-card-header pt-table-header">
            <h2 class="pt-card-title">Hojas de salida</h2>
            <span class="pt-card-badge" id="invoice-notes-count" data-total="{{ $notes->count() }}">{{ $notes->count() }} {{ $notes->count() === 1 ? 'disponible' : 'disponibles' }}</span>
        </div>
        <div class="pt-card-body">
            @if($notes->isEmpty())
            <p class="pt-muted">No hay hojas pendientes de facturar. Genere una salida o anule la factura activa de una hoja ya facturada.</p>
            <div class="pt-form-actions">
                <a href="{{ route('salidas.index') }}" class="pt-btn pt-btn-primary">Ir a Salidas</a>
            </div>
            @else
            <form method="POST" action="{{ route('accounting.invoices.start-create') }}" id="invoice-notes-form">
                @csrf
                <label class="pt-notes-search" for="invoice-notes-q">
                    <span class="pt-notes-search-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </span>
                    <input type="search" id="invoice-notes-q" class="pt-input" autocomplete="off" autofocus
                           placeholder="Buscar por hoja, cliente o código (ej. SLO-1071, 1071 o Caballo)">
                </label>
                <p class="pt-muted" style="margin-top:0.75rem">Puede marcar varias hojas del mismo cliente. Las que dicen «cuentas mixtas» hay que separarlas en Salidas antes de facturar.</p>
                <div class="pt-table-wrap">
                    <table class="pt-table" id="invoice-notes-table">
                        <thead>
                            <tr>
                                <th style="width:2.5rem"></th>
                                <th>Hoja</th>
                                <th>Cliente a facturar</th>
                                <th class="pt-num">Paquetes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notes as $note)
                            @php
                                $billTo = $note->billingAgency();
                                $mixed = $note->hasMixedBillTos();
                                $family = $note->invoiceGroupKey();
                                $oldIds = collect(old('delivery_note_ids', old('delivery_note_id') ? [old('delivery_note_id')] : []))->map(fn ($id) => (string) $id);
                                $codeDigits = preg_replace('/^(SLO|BCH)-?/i', '', (string) $note->code);
                                $searchBits = strtolower(trim(implode(' ', array_filter([
                                    $note->code,
                                    $codeDigits,
                                    ltrim((string) $codeDigits, '0'),
                                    $billTo?->name,
                                    $billTo?->code,
                                    $billTo?->listingAccountLabel(),
                                    $note->agency?->name,
                                    $note->agency?->code,
                                    $mixed ? 'mixtas' : null,
                                ]))));
                            @endphp
                            <tr data-search="{{ $searchBits }}">
                                <td>
                                    <input type="checkbox" name="delivery_note_ids[]" value="{{ $note->id }}"
                                           class="invoice-note-check"
                                           data-family="{{ $family }}"
                                           @disabled($mixed)
                                           title="{{ $mixed ? 'Esta hoja mezcla clientes. Sepárela en Salidas antes de facturar.' : '' }}"
                                           @checked($oldIds->contains((string) $note->id) && ! $mixed)>
                                </td>
                                <td><span class="pt-code">{{ $note->code }}</span></td>
                                <td>
                                    {{ $billTo?->listingAccountLabel() ?? 'Sin agencia' }}@if($billTo?->code && ! $billTo->isDirectClient()) <span class="pt-muted">· {{ $billTo->code }}</span>@endif
                                    @if($mixed)
                                    <span class="pt-muted"> · cuentas mixtas</span>
                                    @endif
                                </td>
                                <td class="pt-num">{{ $note->deliveries_count }}</td>
                            </tr>
                            @endforeach
                            <tr id="invoice-notes-empty" hidden>
                                <td colspan="4" class="pt-empty">Ninguna hoja coincide con la búsqueda.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="pt-field-hint">En el siguiente paso confirmará tarifas, el cargo de delivery y el tipo de cambio.</p>
                <div class="pt-form-actions">
                    <a href="{{ route('accounting.invoices.index') }}" class="pt-btn pt-btn-secondary">Cancelar</a>
                    <button type="submit" class="pt-btn pt-btn-primary" id="invoice-notes-submit">Continuar</button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>

@include('partials.primetrack-module-styles')
<style>
.pt-notes-search {
    position: relative;
    display: block;
    margin: 0;
}
.pt-notes-search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    display: flex;
    pointer-events: none;
}
.pt-notes-search .pt-input {
    padding-left: 2.35rem;
}
</style>
@if($notes->isNotEmpty())
<script>
(function () {
    var checks = Array.prototype.slice.call(document.querySelectorAll('.invoice-note-check'));
    var form = document.getElementById('invoice-notes-form');
    var search = document.getElementById('invoice-notes-q');
    var empty = document.getElementById('invoice-notes-empty');
    var countEl = document.getElementById('invoice-notes-count');
    var total = countEl ? parseInt(countEl.getAttribute('data-total') || String(checks.length), 10) : checks.length;

    function selectedFamily() {
        var checked = checks.filter(function (c) { return c.checked && !c.disabled; });
        return checked.length ? checked[0].getAttribute('data-family') : '';
    }

    function normalizeQuery(raw) {
        var q = (raw || '').toLowerCase().trim().replace(/\s+/g, ' ');
        if (!q) {
            return '';
        }
        return q.replace(/^(slo|bch)-?(?=\d)/, '');
    }

    function rowMatches(row, q) {
        if (!q) {
            return true;
        }
        var hay = (row.getAttribute('data-search') || '');
        if (hay.indexOf(q) !== -1) {
            return true;
        }
        var compact = q.replace(/\s+/g, '');
        return compact !== q && hay.indexOf(compact) !== -1;
    }

    function setCount(visible) {
        if (!countEl) {
            return;
        }
        if (!search || !search.value.trim() || visible === total) {
            countEl.textContent = total + (total === 1 ? ' disponible' : ' disponibles');
            return;
        }
        countEl.textContent = visible + ' de ' + total;
    }

    function refresh() {
        var family = selectedFamily();
        var q = search ? normalizeQuery(search.value) : '';
        var visible = 0;

        checks.forEach(function (c) {
            var mixed = (c.getAttribute('data-family') || '').indexOf('mixed:') === 0;
            var same = !family || c.getAttribute('data-family') === family;
            if (!same && c.checked) {
                c.checked = false;
            }
            c.disabled = mixed || (!!family && !same);
            var row = c.closest('tr');
            if (!row) {
                return;
            }
            var show = rowMatches(row, q) || c.checked;
            row.hidden = !show;
            row.style.opacity = show && c.disabled ? '0.45' : '';
            if (show) {
                visible += 1;
            }
        });

        if (empty) {
            empty.hidden = visible > 0;
        }
        setCount(visible);
    }

    checks.forEach(function (c) {
        c.addEventListener('change', refresh);
    });
    if (search) {
        search.addEventListener('input', refresh);
        search.addEventListener('search', refresh);
    }
    refresh();

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!checks.some(function (c) { return c.checked && !c.disabled; })) {
                e.preventDefault();
                alert('Seleccione al menos una hoja de salida.');
            }
        });
    }
})();
</script>
@endif
@endsection
