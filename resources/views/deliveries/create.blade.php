@extends('layouts.app')

@section('title', 'Crear hoja de salida')

@section('content')
<div class="inv-page">
    @php
        $needsClientPick = $needsClientPick ?? false;
        $isSloAccount = $isSloAccount ?? false;
        $selectedSloClient = $selectedSloClient ?? null;
        $accountAgency = $accountAgency ?? $selectedAgency;
        $consignee = $consignee ?? '';
        $sloClients = $sloClients ?? collect();
        $sloConsignees = $sloConsignees ?? collect();
        $sloReadyClients = $sloClients->filter(fn ($c) => (int) ($c->ready_count ?? 0) > 0)->values();
        $createParams = array_filter([
            'agency_id' => $selectedAgency?->id,
            'consignee' => $consignee !== '' ? $consignee : null,
        ]);
        $packageHead = $selectedSloClient?->name
            ?? ($consignee !== '' ? $consignee : $selectedAgency?->name);
    @endphp
    <x-module-banner section="Operaciones" current="Nueva hoja" title="Crear hoja de salida" subtitle="Elija la cuenta y, si es SkyLink One, el cliente. Solo se cargan los paquetes de esa cuenta." back-href="{{ route('salidas.index') }}" back-label="Volver a Salidas">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if(session('success'))
    <div class="inv-alert inv-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="inv-alert inv-alert-danger">{{ session('error') }}</div>
    @endif

    @include('deliveries.partials._mixed-notes-banner')

    <div class="inv-card inv-filters-card">
        <form method="GET" action="{{ route('salidas.create') }}" class="inv-filters-form" id="deliveryAgencyForm">
            <div class="inv-field">
                <label class="inv-label" for="account_combobox">¿Para qué cuenta es la salida?</label>
                <div id="account_combobox_wrap" class="inv-combo-wrap">
                    <input type="text" id="account_combobox" class="inv-input" placeholder="Escriba para buscar la cuenta…" autocomplete="off" value="{{ $accountAgency?->name ?? '' }}">
                    <div id="account_dropdown" class="inv-combo-dropdown" role="listbox"></div>
                </div>
            </div>
            <div class="inv-field" id="slo_client_wrap" @if(! $isSloAccount) hidden @endif>
                <label class="inv-label" for="slo_client_combobox">Cliente de SkyLink One</label>
                <div id="slo_combobox_wrap" class="inv-combo-wrap">
                    <input type="text" id="slo_client_combobox" class="inv-input" placeholder="Escriba el nombre del cliente…" autocomplete="off" value="{{ $selectedSloClient?->name ?? ($consignee !== '' ? $consignee : '') }}">
                    <div id="slo_client_dropdown" class="inv-combo-dropdown" role="listbox"></div>
                </div>
            </div>
            <input type="hidden" name="agency_id" id="agency_id" value="{{ $agencyId }}">
            <input type="hidden" name="consignee" id="consignee" value="{{ $consignee }}">
            <div class="inv-filters-actions">
                <button type="submit" class="inv-btn inv-btn-primary">Ver paquetes</button>
                @if($agencyId)
                <a href="{{ route('salidas.create', ['clear_agency' => 1]) }}" class="inv-clear-link">Limpiar</a>
                @endif
            </div>
            <script type="application/json" id="delivery-accounts-data">@json($partnerAgenciesJson ?? [])</script>
            <script type="application/json" id="delivery-slo-clients-data">@json($sloClientsJson ?? [])</script>
            <script type="application/json" id="delivery-slo-consignees-data">@json(($sloConsignees ?? collect())->map(fn ($row) => ['name' => $row->label_name, 'ready_count' => (int) $row->ready_count])->values())</script>
        </form>
    </div>

    @if($needsClientPick)
    <div class="inv-alert inv-alert-info">
        SkyLink One es la agencia. Elija el <strong>cliente</strong> arriba para ver solo sus paquetes y armarle su hoja.
    </div>
    <div class="inv-card">
        <div class="inv-table-head">
            <span class="inv-table-head-note">Clientes con paquetes listos</span>
        </div>
        @if($sloReadyClients->isEmpty() && $sloConsignees->isEmpty())
        <div class="inv-empty">
            <p class="inv-empty-title">No hay paquetes listos en SkyLink One</p>
            <p>Ningún cliente tiene paquetes en «Listo para retiro».</p>
        </div>
        @else
        <div class="inv-table-scroll">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th class="inv-num">Listos</th>
                        <th class="inv-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sloReadyClients as $client)
                    <tr>
                        <td>
                            <span class="inv-client">{{ $client->name }}</span>
                            @if($client->code) <span class="inv-muted">{{ $client->code }}</span>@endif
                        </td>
                        <td class="inv-num"><span class="inv-paq">{{ $client->ready_count }}</span></td>
                        <td class="inv-actions">
                            <a href="{{ route('salidas.create', ['agency_id' => $client->id]) }}" class="inv-btn inv-btn-primary inv-btn-sm">Ver paquetes</a>
                        </td>
                    </tr>
                    @endforeach
                    @foreach($sloConsignees as $row)
                    <tr>
                        <td><span class="inv-client">{{ $row->label_name ?: 'Sin nombre' }}</span></td>
                        <td class="inv-num"><span class="inv-paq">{{ $row->ready_count }}</span></td>
                        <td class="inv-actions">
                            <a href="{{ route('salidas.create', ['agency_id' => $slo->id, 'consignee' => $row->label_name]) }}" class="inv-btn inv-btn-primary inv-btn-sm">Ver paquetes</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @elseif($selectedAgency)
    <div class="inv-card">
        <div class="inv-table-head">
            <span class="inv-table-head-note">Paquetes listos para retiro — {{ $packageHead }}</span>
            @if($availableTotal > 0)
            <a href="{{ route('salidas.batch', array_filter(['agency_id' => $selectedAgency->id, 'service_type' => $serviceType, 'consignee' => $consignee !== '' ? $consignee : null])) }}" class="inv-btn inv-btn-primary inv-btn-sm">Iniciar salida</a>
            @endif
        </div>
        @if(($availableAir + $availableSea + ($availableCft ?? 0)) === 0)
        <div class="inv-empty">
            <p class="inv-empty-title">Esta cuenta no tiene paquetes listos para retirar</p>
            <p>No hay paquetes en estado «Listo para retiro» para {{ $packageHead }}. Seleccione otra cuenta o espere a que los paquetes estén listos.</p>
        </div>
        @else
        <div class="inv-card-body">
            <div class="inv-service-filter">
                <span class="inv-service-label">Servicio:</span>
                <a href="{{ route('salidas.create', $createParams) }}" class="inv-chip {{ !$serviceType ? 'is-active' : '' }}">Todos ({{ $availableAir + $availableSea + ($availableCft ?? 0) }})</a>
                <a href="{{ route('salidas.create', $createParams + ['service_type' => 'AIR']) }}" class="inv-chip {{ $serviceType === 'AIR' ? 'is-active' : '' }}">Aéreo ({{ $availableAir }})</a>
                <a href="{{ route('salidas.create', $createParams + ['service_type' => 'SEA']) }}" class="inv-chip {{ $serviceType === 'SEA' ? 'is-active' : '' }}">Marítimo ({{ $availableSea }})</a>
                <a href="{{ route('salidas.create', $createParams + ['service_type' => 'CFT']) }}" class="inv-chip {{ $serviceType === 'CFT' ? 'is-active' : '' }}">Pie cúbico ({{ $availableCft ?? 0 }})</a>
            </div>
            <p class="inv-hint">{{ $availableTotal }} {{ $availableTotal === 1 ? 'paquete listo' : 'paquetes listos' }}@if($serviceType) — {{ \App\Support\ServiceType::label($serviceType) }}@else ({{ $availableAir }} aéreo, {{ $availableSea }} marítimo, {{ $availableCft ?? 0 }} pie cúbico)@endif. Use «Iniciar salida» para escanear y registrar la entrega.</p>
        </div>
        <div class="inv-table-scroll">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>Cliente (etiqueta)</th>
                        <th>Warehouse</th>
                        <th>Tracking</th>
                        <th>Servicio</th>
                        <th class="inv-num">Peso (lbs)</th>
                        <th>Agencia</th>
                        <th>Listo desde</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($availablePackages as $p)
                    <tr>
                        <td title="{{ $p->label_name }}"><span class="inv-client">{{ Str::limit($p->label_name, 25) }}</span></td>
                        <td><span class="inv-folio">{{ $p->warehouse_code ?? '—' }}</span></td>
                        <td class="inv-muted" title="{{ $p->tracking_external }}">{{ Str::limit($p->tracking_external, 18) }}</td>
                        <td>
                            <span class="inv-type inv-type--{{ strtolower($p->service_type ?? '') }}">{{ \App\Support\ServiceType::label($p->service_type) }}</span>
                        </td>
                        <td class="inv-num">{{ $p->verified_weight_lbs ?? $p->intake_weight_lbs ?? '—' }}</td>
                        <td class="inv-muted" title="{{ $p->agency?->listingAccountLabel() ?? '' }}"><x-account-label :agency="$p->agency" :show-code="false" /></td>
                        <td class="inv-nowrap inv-muted">{{ $p->ready_at ? $p->ready_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @else
    <div class="inv-card">
        <div class="inv-empty">
            <p class="inv-empty-title">Seleccione una cuenta</p>
            <p>Elija la subagencia o SkyLink One. Si es SkyLink One, después busque el cliente para ver solo sus paquetes.</p>
        </div>
    </div>
    @endif
</div>

<style>
.inv-page {
    --inv-navy: #0A2D6F;
    --inv-blue: #1E4FA8;
    --inv-green: #16794C;
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
.inv-alert-info { background: #EFF6FF; border: 1px solid #BFDBFE; color: #1e3a8a; }
.inv-mixed-list { display: flex; flex-direction: column; gap: 0.45rem; }
.inv-mixed-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; background: #fff; border: 1px solid #fecaca; border-radius: 0.55rem; padding: 0.45rem 0.65rem; color: #0f172a; }
.inv-card { background: #fff; border: 1px solid var(--inv-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; margin-bottom: 1.15rem; }
.inv-filters-card { padding: 0.9rem 1.1rem; overflow: visible; }
.inv-filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.inv-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 14rem; flex: 1 1 16rem; }
#slo_client_wrap[hidden] { display: none !important; }
.inv-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.inv-input { padding: 0.52rem 0.7rem; font-size: 0.85rem; border: 1px solid #D8DCE2; border-radius: 0.55rem; background: #fff; color: #0f172a; width: 100%; box-sizing: border-box; }
.inv-input:focus { outline: none; border-color: var(--inv-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.inv-combo-wrap { position: relative; }
.inv-combo-dropdown {
    position: absolute; z-index: 30; left: 0; right: 0; top: calc(100% + 4px);
    max-height: 16rem; overflow-y: auto; background: #fff;
    border: 1px solid var(--inv-border); border-radius: 0.55rem;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12); display: none;
}
.inv-combo-item { padding: 0.55rem 0.8rem; cursor: pointer; font-size: 0.85rem; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
.inv-combo-item:last-child { border-bottom: none; }
.inv-combo-item:hover, .inv-combo-item.is-active { background: var(--inv-soft); color: var(--inv-navy); }
.inv-combo-meta { display: block; font-size: 0.72rem; color: #94a3b8; font-weight: 600; }
.inv-combo-empty { padding: 0.7rem 0.85rem; font-size: 0.85rem; color: #94a3b8; }
.inv-filters-actions { display: flex; align-items: center; gap: 0.65rem; padding-bottom: 1px; flex: 0 0 auto; }
.inv-clear-link { font-size: 0.8rem; font-weight: 700; color: #64748b; text-decoration: none; white-space: nowrap; }
.inv-clear-link:hover { color: var(--inv-navy); text-decoration: underline; }
.inv-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.6rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; white-space: nowrap; }
.inv-btn-primary { background: var(--inv-navy); color: #fff; border-color: var(--inv-navy); box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25); }
.inv-btn-primary:hover { background: var(--inv-blue); border-color: var(--inv-blue); color: #fff; }
.inv-btn-sm { padding: 0.42rem 0.85rem; font-size: 0.8rem; }
.inv-table-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.7rem; padding: 0.85rem 1.1rem; border-bottom: 1px solid var(--inv-line); }
.inv-table-head-note { font-size: 0.85rem; font-weight: 700; color: #334155; }
.inv-card-body { padding: 1rem 1.1rem 0.25rem; }
.inv-service-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.85rem; }
.inv-service-label { font-size: 0.8rem; font-weight: 700; color: #334155; }
.inv-chip { display: inline-flex; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 700; border-radius: 999px; border: 1px solid #d1d9e6; background: #fff; color: #334155; text-decoration: none; }
.inv-chip:hover { border-color: var(--inv-border); color: var(--inv-navy); background: var(--inv-soft); }
.inv-chip.is-active { background: var(--inv-navy); border-color: var(--inv-navy); color: #fff; }
.inv-hint { font-size: 0.85rem; color: #64748b; margin: 0 0 0.85rem; }
.inv-table-scroll { overflow-x: auto; }
.inv-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.inv-table thead th { background: linear-gradient(135deg, var(--inv-navy), var(--inv-blue)); color: #fff; text-align: left; padding: 0.62rem 0.85rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
.inv-table thead th.inv-num { text-align: right; }
.inv-table td { padding: 0.66rem 0.85rem; border-bottom: 1px solid #f4f7fb; color: #334155; vertical-align: middle; }
.inv-table tbody tr:last-child td { border-bottom: none; }
.inv-table tbody tr:hover td { background: var(--inv-soft); }
.inv-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.inv-th-actions, .inv-actions { text-align: right; white-space: nowrap; }
.inv-nowrap { white-space: nowrap; }
.inv-muted { color: #94a3b8; font-size: 0.75rem; }
.inv-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }
.inv-empty-title { margin: 0 0 0.4rem; font-size: 1.05rem; font-weight: 700; color: #334155; }
.inv-folio { font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
.inv-client { font-weight: 700; color: #0f172a; }
.inv-paq { display: inline-flex; align-items: center; justify-content: center; min-width: 1.65rem; height: 1.65rem; padding: 0 0.35rem; border-radius: 999px; background: #EAF1FC; color: var(--inv-blue); font-size: 0.75rem; font-weight: 800; }
.inv-type { display: inline-flex; padding: 0.14rem 0.5rem; border-radius: 999px; font-size: 0.68rem; font-weight: 700; }
.inv-type--air { background: #EAF6FB; color: #0E6E8C; border: 1px solid #BFE3F0; }
.inv-type--sea { background: #FDF3E8; color: #9A5B12; border: 1px solid #F0D4A8; }
.inv-type--cft { background: #E8F6EE; color: #16794C; border: 1px solid #b7e0c8; }
@media (max-width: 768px) { .inv-field { min-width: 0; flex: 1 1 100%; } .inv-filters-actions { width: 100%; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('deliveryAgencyForm');
    var agencyHidden = document.getElementById('agency_id');
    var consigneeHidden = document.getElementById('consignee');
    var accountCombo = document.getElementById('account_combobox');
    var accountDropdown = document.getElementById('account_dropdown');
    var accountWrap = document.getElementById('account_combobox_wrap');
    var sloWrap = document.getElementById('slo_client_wrap');
    var sloCombo = document.getElementById('slo_client_combobox');
    var sloDropdown = document.getElementById('slo_client_dropdown');
    var sloComboWrap = document.getElementById('slo_combobox_wrap');
    var accountsEl = document.getElementById('delivery-accounts-data');
    var clientsEl = document.getElementById('delivery-slo-clients-data');
    var consigneesEl = document.getElementById('delivery-slo-consignees-data');
    var accounts = accountsEl ? JSON.parse(accountsEl.textContent || '[]') : [];
    var sloClients = clientsEl ? JSON.parse(clientsEl.textContent || '[]') : [];
    var sloConsignees = consigneesEl ? JSON.parse(consigneesEl.textContent || '[]') : [];
    var sloAccount = accounts.find(function(row) { return row.is_slo; }) || null;

    function matches(row, q) {
        if (!q) return true;
        return String(row.name || '').toLowerCase().indexOf(q) !== -1
            || String(row.code || '').toLowerCase().indexOf(q) !== -1;
    }
    function clientItems(filter) {
        var q = (filter || '').trim().toLowerCase();
        var items = sloClients.filter(function(row) { return matches(row, q); }).map(function(row) {
            return {
                type: 'client',
                id: row.id,
                name: row.name,
                code: row.code,
                ready_count: row.ready_count || 0,
                label: row.name + (row.code ? ' (' + row.code + ')' : '')
            };
        });
        sloConsignees.filter(function(row) {
            return !q || String(row.name || '').toLowerCase().indexOf(q) !== -1;
        }).forEach(function(row) {
            items.push({
                type: 'consignee',
                name: row.name,
                ready_count: row.ready_count || 0,
                label: row.name
            });
        });
        return items;
    }
    function renderList(target, html) {
        target.innerHTML = html;
        target.style.display = 'block';
    }
    function renderAccounts(filter) {
        var q = (filter || '').trim().toLowerCase();
        var filtered = accounts.filter(function(row) { return matches(row, q); });
        renderList(accountDropdown, filtered.length
            ? filtered.map(function(row) {
                return '<div class="inv-combo-item" data-id="' + row.id + '" data-slo="' + (row.is_slo ? '1' : '0') + '" data-name="' + String(row.name).replace(/"/g, '&quot;') + '">' + row.name + (row.code ? '<span class="inv-combo-meta">' + row.code + '</span>' : '') + '</div>';
            }).join('')
            : '<div class="inv-combo-empty">No hay coincidencias</div>');
    }
    function renderClients(filter) {
        var items = clientItems(filter);
        renderList(sloDropdown, items.length
            ? items.map(function(row) {
                var meta = row.ready_count ? (row.ready_count + ' listos') : '';
                return '<div class="inv-combo-item" data-type="' + row.type + '" data-id="' + (row.id || '') + '" data-name="' + String(row.name || '').replace(/"/g, '&quot;') + '">' + row.label + (meta ? '<span class="inv-combo-meta">' + meta + '</span>' : '') + '</div>';
            }).join('')
            : '<div class="inv-combo-empty">No hay coincidencias</div>');
    }
    function setSloVisible(show) {
        if (!sloWrap) return;
        sloWrap.hidden = !show;
        if (!show && sloCombo) sloCombo.value = '';
        if (!show && consigneeHidden) consigneeHidden.value = '';
    }
    function submitForm() {
        if (form) form.submit();
    }
    function selectAccount(id, name, isSlo) {
        accountCombo.value = name || '';
        accountDropdown.style.display = 'none';
        if (isSlo) {
            setSloVisible(true);
            agencyHidden.value = sloAccount ? String(sloAccount.id) : id;
            consigneeHidden.value = '';
            if (sloCombo) sloCombo.value = '';
            submitForm();
            return;
        }
        setSloVisible(false);
        agencyHidden.value = id || '';
        consigneeHidden.value = '';
        submitForm();
    }
    function selectClient(item) {
        if (!item) return;
        sloDropdown.style.display = 'none';
        if (item.type === 'consignee') {
            sloCombo.value = item.name || '';
            agencyHidden.value = sloAccount ? String(sloAccount.id) : '';
            consigneeHidden.value = item.name || '';
        } else {
            sloCombo.value = item.name || '';
            agencyHidden.value = item.id || '';
            consigneeHidden.value = '';
        }
        submitForm();
    }

    if (accountCombo && accountDropdown) {
        accountCombo.addEventListener('focus', function() {
            accountCombo.select();
            renderAccounts('');
        });
        accountCombo.addEventListener('input', function() {
            renderAccounts(accountCombo.value);
        });
        accountDropdown.addEventListener('mousedown', function(e) {
            var item = e.target.closest('.inv-combo-item');
            if (!item) return;
            e.preventDefault();
            selectAccount(item.getAttribute('data-id'), item.getAttribute('data-name'), item.getAttribute('data-slo') === '1');
        });
    }
    if (sloCombo && sloDropdown) {
        sloCombo.addEventListener('focus', function() {
            sloCombo.select();
            renderClients('');
        });
        sloCombo.addEventListener('input', function() {
            renderClients(sloCombo.value);
        });
        sloDropdown.addEventListener('mousedown', function(e) {
            var item = e.target.closest('.inv-combo-item');
            if (!item) return;
            e.preventDefault();
            selectClient({
                type: item.getAttribute('data-type'),
                id: item.getAttribute('data-id'),
                name: item.getAttribute('data-name')
            });
        });
    }
    document.addEventListener('click', function(e) {
        if (accountDropdown && accountWrap && !e.target.closest('#account_combobox_wrap')) {
            accountDropdown.style.display = 'none';
        }
        if (sloDropdown && sloComboWrap && !e.target.closest('#slo_combobox_wrap')) {
            sloDropdown.style.display = 'none';
        }
    });
});
</script>
@endsection
