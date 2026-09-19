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
                <div class="pt-invoice-toolbar">
                    <div class="pt-invoice-kind" role="group" aria-label="Tipo de cuenta">
                        <button type="button" class="pt-kind-btn is-active js-invoice-kind" data-kind="all">Todas</button>
                        <button type="button" class="pt-kind-btn js-invoice-kind" data-kind="agency">Agencias</button>
                        <button type="button" class="pt-kind-btn js-invoice-kind" data-kind="client">Clientes finales</button>
                    </div>
                    <div class="pt-combo" id="invoice-search-combo">
                        <label class="pt-notes-search" for="invoice-notes-q">
                            <span class="pt-notes-search-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                            </span>
                            <input type="search" id="invoice-notes-q" class="pt-input" autocomplete="off"
                                   placeholder="Buscar por hoja, cliente o código">
                        </label>
                        <input type="hidden" id="invoice-account" value="">
                        <div class="pt-combo-list" id="invoice-agency-list" hidden>
                            @forelse($invoiceAgencies ?? [] as $agency)
                            <button type="button" class="pt-combo-item js-agency-option"
                                    data-id="{{ $agency['id'] }}"
                                    data-search="{{ $agency['search'] }}"
                                    data-name="{{ $agency['name'] }}">
                                <strong>{{ $agency['name'] }}</strong>
                                @if($agency['code'])
                                <span class="pt-muted">· {{ $agency['code'] }}</span>
                                @endif
                                <span class="pt-combo-meta">{{ $agency['count'] }} {{ $agency['count'] === 1 ? 'hoja' : 'hojas' }}@if(!empty($agency['children'])) · incluye {{ implode(', ', $agency['children']) }}@endif</span>
                            </button>
                            @empty
                            <p class="pt-combo-empty">No hay agencias con hojas pendientes.</p>
                            @endforelse
                            <p class="pt-combo-empty" id="invoice-agency-empty" hidden>Ninguna agencia coincide.</p>
                        </div>
                    </div>
                </div>
                <p class="pt-muted" id="invoice-filter-help">Puede marcar varias hojas de la misma cuenta. En Agencias, busque la subagencia para ver las hojas del padre y de sus hijas.</p>
                <div class="pt-table-wrap" id="invoice-notes-wrap">
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
                                    $origin = $note->agency;
                                    $fromChild = $origin && $origin->isNestedUnderPartner();
                                    $kind = $mixed ? 'mixed' : (($billTo && $billTo->isDirectClient()) ? 'client' : 'agency');
                                    $searchBits = strtolower(trim(implode(' ', array_filter([
                                    $note->code,
                                    $codeDigits,
                                    ltrim((string) $codeDigits, '0'),
                                    $billTo?->name,
                                    $billTo?->code,
                                    $billTo?->listingAccountLabel(),
                                    $origin?->name,
                                    $origin?->code,
                                    $kind === 'client' ? 'cliente final' : 'agencia',
                                    $fromChild ? 'hija padre' : null,
                                    $mixed ? 'mixtas' : null,
                                ]))));
                            @endphp
                            <tr data-search="{{ $searchBits }}" data-kind="{{ $kind }}" data-billto="{{ $billTo?->id }}">
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
                                    <span class="pt-note-kind pt-note-kind--{{ $kind }}">{{ $kind === 'client' ? 'Cliente final' : ($kind === 'mixed' ? 'Mixta' : 'Agencia') }}</span>
                                    {{ $billTo?->listingAccountLabel() ?? 'Sin agencia' }}@if($billTo?->code && ! $billTo->isDirectClient()) <span class="pt-muted">· {{ $billTo->code }}</span>@endif
                                    @if($fromChild && ! $mixed)
                                    <span class="pt-note-origin">Hoja de {{ $origin->name }}</span>
                                    @endif
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
.pt-invoice-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
}
.pt-invoice-kind {
    display: inline-flex;
    gap: 0.25rem;
    padding: 0.2rem;
    border: 1px solid #dbe4f5;
    border-radius: 0.7rem;
    background: #f8fafc;
}
.pt-kind-btn {
    border: 0;
    background: transparent;
    color: #475569;
    font-size: 0.8rem;
    font-weight: 650;
    padding: 0.4rem 0.75rem;
    border-radius: 0.5rem;
    cursor: pointer;
}
.pt-kind-btn.is-active { background: #0A2D6F; color: #fff; }
.pt-combo { position: relative; flex: 1 1 16rem; min-width: 14rem; }
.pt-combo-list {
    position: absolute;
    z-index: 20;
    left: 0; right: 0;
    top: calc(100% + 4px);
    max-height: 16rem;
    overflow: auto;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 0.6rem;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    padding: 0.3rem;
}
.pt-combo-item {
    display: block;
    width: 100%;
    text-align: left;
    border: 0;
    background: transparent;
    border-radius: 0.45rem;
    padding: 0.5rem 0.65rem;
    cursor: pointer;
    color: #0f172a;
}
.pt-combo-item:hover { background: #eef4ff; }
.pt-combo-meta, .pt-combo-empty {
    display: block;
    margin-top: 0.15rem;
    font-size: 0.75rem;
    color: #64748b;
}
.pt-combo-empty { padding: 0.55rem 0.65rem; margin: 0; }
.pt-notes-search { position: relative; display: block; margin: 0; }
.pt-notes-search-icon {
    position: absolute; left: 0.75rem; top: 50%;
    transform: translateY(-50%);
    color: #6b7280; display: flex; pointer-events: none;
}
.pt-notes-search .pt-input { padding-left: 2.35rem; }
.pt-note-kind {
    display: inline-block;
    margin: 0 0.4rem 0.15rem 0;
    padding: 0.08rem 0.4rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    vertical-align: middle;
}
.pt-note-kind--agency { background: #e0e7ff; color: #1e3a8a; }
.pt-note-kind--client { background: #dcfce7; color: #166534; }
.pt-note-kind--mixed { background: #fee2e2; color: #991b1b; }
.pt-note-origin { display: block; margin-top: 0.15rem; font-size: 0.78rem; color: #64748b; }
#invoice-filter-help { margin: 0.75rem 0 0; }
</style>
@if($notes->isNotEmpty())
<script>
(function () {
    var checks = Array.prototype.slice.call(document.querySelectorAll('.invoice-note-check'));
    var form = document.getElementById('invoice-notes-form');
    var search = document.getElementById('invoice-notes-q');
    var account = document.getElementById('invoice-account');
    var combo = document.getElementById('invoice-search-combo');
    var agencyList = document.getElementById('invoice-agency-list');
    var agencyEmpty = document.getElementById('invoice-agency-empty');
    var help = document.getElementById('invoice-filter-help');
    var kindBtns = Array.prototype.slice.call(document.querySelectorAll('.js-invoice-kind'));
    var agencyOpts = Array.prototype.slice.call(document.querySelectorAll('.js-agency-option'));
    var tableWrap = document.getElementById('invoice-notes-wrap');
    var empty = document.getElementById('invoice-notes-empty');
    var countEl = document.getElementById('invoice-notes-count');
    var total = countEl ? parseInt(countEl.getAttribute('data-total') || String(checks.length), 10) : checks.length;

    function currentKind() {
        var active = kindBtns.find(function (btn) { return btn.classList.contains('is-active'); });
        return active ? (active.getAttribute('data-kind') || 'all') : 'all';
    }

    function selectedAgency() {
        return account && account.value ? String(account.value) : '';
    }

    function normalizeQuery(raw) {
        var q = (raw || '').toLowerCase().trim().replace(/\s+/g, ' ');
        return q ? q.replace(/^(slo|bch)-?(?=\d)/, '') : '';
    }

    function setPlaceholder() {
        if (!search) {
            return;
        }
        search.placeholder = currentKind() === 'agency'
            ? 'Buscar subagencia…'
            : 'Buscar por hoja, cliente o código';
    }

    function filterAgencyOptions() {
        var q = (search && search.value ? search.value : '').toLowerCase().trim();
        var visible = 0;
        agencyOpts.forEach(function (btn) {
            var show = !q || (btn.getAttribute('data-search') || '').indexOf(q) !== -1;
            btn.hidden = !show;
            if (show) {
                visible += 1;
            }
        });
        if (agencyEmpty) {
            agencyEmpty.hidden = visible > 0 || agencyOpts.length === 0;
        }
    }

    function openAgencyList() {
        if (currentKind() !== 'agency' || !agencyList) {
            return;
        }
        filterAgencyOptions();
        agencyList.hidden = false;
    }

    function closeAgencyList() {
        if (agencyList) {
            agencyList.hidden = true;
        }
    }

    function selectAgency(id, name) {
        if (account) {
            account.value = id || '';
        }
        if (search) {
            search.value = name || '';
        }
        closeAgencyList();
        refresh();
    }

    function refresh() {
        var kind = currentKind();
        var agencyId = selectedAgency();
        var waitingAgency = kind === 'agency' && !agencyId;
        var q = kind === 'agency' ? '' : normalizeQuery(search ? search.value : '');
        var family = '';
        var checked = checks.filter(function (c) { return c.checked && !c.disabled; });
        if (checked.length) {
            family = checked[0].getAttribute('data-family') || '';
        }
        var visible = 0;

        setPlaceholder();
        if (help) {
            help.textContent = kind === 'agency'
                ? (agencyId
                    ? 'Hojas del padre y de sus hijas. Puede desmarcar las que no quiera incluir.'
                    : 'Busque la subagencia. Al elegirla salen las hojas del padre y de sus hijas.')
                : 'Puede marcar varias hojas de la misma cuenta. Las mixtas hay que separarlas en Salidas.';
        }

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
            var rowKind = row.getAttribute('data-kind') || '';
            var kindOk = kind === 'all' || rowKind === kind;
            var agencyOk = kind !== 'agency' || row.getAttribute('data-billto') === agencyId;
            var textOk = !q || (row.getAttribute('data-search') || '').indexOf(q) !== -1;
            var show = !waitingAgency && kindOk && agencyOk && (textOk || c.checked);
            row.hidden = !show;
            row.style.opacity = show && c.disabled ? '0.45' : '';
            if (show) {
                visible += 1;
            }
        });

        if (tableWrap) {
            tableWrap.hidden = waitingAgency;
        }
        if (empty) {
            empty.hidden = waitingAgency || visible > 0;
        }
        if (countEl) {
            if (waitingAgency) {
                countEl.textContent = 'Busque una subagencia';
            } else if (visible === total && kind === 'all' && !q) {
                countEl.textContent = total + (total === 1 ? ' disponible' : ' disponibles');
            } else {
                countEl.textContent = visible + ' de ' + total;
            }
        }
    }

    checks.forEach(function (c) {
        c.addEventListener('change', refresh);
    });
    kindBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            kindBtns.forEach(function (other) { other.classList.toggle('is-active', other === btn); });
            if (account) {
                account.value = '';
            }
            if (search) {
                search.value = '';
            }
            checks.forEach(function (c) { c.checked = false; });
            closeAgencyList();
            refresh();
            if (currentKind() === 'agency' && search) {
                search.focus();
                openAgencyList();
            }
        });
    });
    if (search) {
        search.addEventListener('input', function () {
            if (currentKind() === 'agency') {
                if (account) {
                    account.value = '';
                }
                checks.forEach(function (c) { c.checked = false; });
                openAgencyList();
            } else {
                closeAgencyList();
            }
            refresh();
        });
        search.addEventListener('focus', function () {
            if (currentKind() === 'agency') {
                openAgencyList();
            }
        });
    }
    agencyOpts.forEach(function (btn) {
        btn.addEventListener('click', function () {
            selectAgency(btn.getAttribute('data-id') || '', btn.getAttribute('data-name') || '');
        });
    });
    document.addEventListener('click', function (e) {
        if (combo && !combo.contains(e.target)) {
            closeAgencyList();
        }
    });
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
