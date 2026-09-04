@extends('layouts.app')

@section('title', 'Escanear ' . $consolidation->code)

@section('content')
<div class="cx-page">
    <x-module-banner
        section="Operaciones"
        current="Escaneo NIC"
        title="{{ $consolidation->code }}"
        subtitle="Escaneo de paquetes en Nicaragua. Con la pistola, un código válido se registra solo."
        back-href="{{ route('nic-consolidations.index') }}"
        back-label="Volver al listado"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15v10.5h-15V6.75Zm3 3h4.5m-4.5 3h9"/></svg>
        </x-slot:icon>
        <x-slot:strip>
            <span class="mb-strip-label">{{ $consolidation->unitNounTitle() }}</span>
            <span class="mb-pill">{{ \App\Support\ServiceType::label($consolidation->service_type) }}</span>
        </x-slot:strip>
    </x-module-banner>

    @if(session('success'))
    <div class="cx-alert cx-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="cx-alert cx-alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('warning'))
    <div class="cx-alert cx-alert-warn">{{ session('warning') }}</div>
    @endif

    <div class="cx-card cx-scan-card">
        <form id="nic-scan-form" action="{{ route('nic-consolidations.scan', $consolidation->id) }}" method="POST" class="cx-scan-form">
            @csrf
            <div class="cx-scan-field">
                <label class="cx-label" for="code">Código del paquete</label>
                <input type="text" name="code" id="code" class="cx-input cx-input-lg" placeholder="6 dígitos o tracking…" autofocus required autocomplete="off">
            </div>
            <div class="cx-filters-actions">
                <button type="submit" class="cx-btn cx-btn-primary" tabindex="-1">Escanear</button>
            </div>
        </form>
        <div id="nic-scan-feedback" class="nic-scan-feedback"></div>
    </div>

    <div class="cx-kpis">
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Total items</span>
            <span id="nic-total-items" class="cx-kpi-value">{{ $totalItems }}</span>
            <span class="cx-kpi-note">En este {{ $consolidation->unitNoun() }}</span>
        </div>
        <div class="cx-kpi-card cx-kpi-card--green">
            <span class="cx-kpi-label">Escaneados</span>
            <span id="nic-scanned-count" class="cx-kpi-value cx-text-green">{{ $scannedCount }}</span>
            <span class="cx-kpi-note">Ya en bodega NIC</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Lbs escaneadas</span>
            <span id="nic-scanned-lbs" class="cx-kpi-value">{{ number_format($scannedLbsTotal ?? 0, 1) }}</span>
            <span class="cx-kpi-note">Peso verificado</span>
        </div>
        <div class="cx-kpi-card {{ $missingCount > 0 ? 'cx-kpi-card--red' : '' }}">
            <span class="cx-kpi-label">Faltantes</span>
            <span id="nic-missing-count" class="cx-kpi-value {{ $missingCount > 0 ? 'cx-text-red' : 'cx-text-green' }}">{{ $missingCount }}</span>
            <span class="cx-kpi-note">Pendientes de pistola</span>
        </div>
    </div>

    <div class="cx-scan-grid">
        <div class="cx-scan-main">
            @if(isset($unmatchedItems) && $unmatchedItems->count() > 0)
            <div class="cx-card">
                <div class="cx-panel-head">
                    <h2 class="cx-panel-title">Códigos en el {{ $consolidation->unitNoun() }} sin preregistro ({{ $unmatchedItems->count() }})</h2>
                </div>
                <div class="cx-panel-body">
                    <p class="cx-scan-hint">Estas líneas se guardaron en Miami sin coincidir en el sistema. No requieren escaneo en Nicaragua.</p>
                    <div class="cx-item-list">
                        @foreach($unmatchedItems as $uItem)
                        <div class="nic-item-row nic-item-row--warn">
                            <div class="nic-item-name font-mono">{{ $uItem->unmatched_code }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($missingItems->count() > 0)
            <div class="cx-card">
                <div class="cx-panel-head">
                    <h2 class="cx-panel-title">Paquetes faltantes ({{ $missingItems->count() }})</h2>
                </div>
                <div class="cx-panel-body">
                    <p class="cx-alert cx-alert-warn cx-alert-inline">Si un código tiene varios bultos (bulto 1 de 3, etc.), escanee el mismo código una vez por cada bulto.</p>
                    <div id="nic-missing-list" class="cx-item-list nic-missing-list">
                        @foreach($missingItems as $item)
                        <div class="nic-item-row nic-missing-item" data-code="{{ $item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? '' }}">
                            <div class="nic-item-name">{{ $item->preregistration->label_name }}</div>
                            <div class="nic-item-meta">
                                Código: <span class="font-mono">{{ $item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? 'N/A' }}</span>
                                @if($item->preregistration->bultos_total && $item->preregistration->bultos_total > 1)
                                <span class="nic-item-bulto">(bulto {{ $item->preregistration->bulto_index }} de {{ $item->preregistration->bultos_total }})</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="cx-scan-side">
            <div class="cx-card">
                <div class="cx-panel-head cx-panel-head--navy">
                    <h2 class="cx-panel-title">Escaneados (<span id="nic-scanned-label-count">{{ $scannedItems->count() }}</span>)</h2>
                </div>
                <div class="cx-panel-body">
                    <div id="nic-scanned-list" class="cx-item-list cx-item-list--tall nic-scanned-list">
                        @foreach($scannedItems as $item)
                        @include('nic-consolidations.partials.scanned-row', ['item' => $item])
                        @endforeach
                    </div>
                    @if($scannedItems->count() === 0)
                    <p id="nic-scanned-empty" class="cx-empty-msg">Aún no se han escaneado paquetes</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cx-page {
    --cx-navy: #0A2D6F; --cx-blue: #1E4FA8; --cx-green: #16794C; --cx-red: #D64545;
    --cx-line: #E8EEF8; --cx-border: #C5D4EB; --cx-soft: #F4F8FD; --cx-muted: #5E6168;
    padding: 1.15rem 0 2.25rem; max-width: 96rem; margin: 0 auto; width: 100%;
}
.cx-header {
    display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem;
    background: #fff; border: 1px solid var(--cx-line); border-radius: 1rem;
    padding: 1.05rem 1.25rem 1.1rem; margin-bottom: 1.15rem; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.cx-breadcrumb { display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.45rem; }
.cx-breadcrumb a { color: #64748b; text-decoration: none; font-weight: 600; }
.cx-breadcrumb a:hover { color: var(--cx-navy); text-decoration: underline; }
.cx-breadcrumb strong { color: #334155; font-weight: 700; }
.cx-title-row { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.cx-title-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2.35rem; height: 2.35rem; border-radius: 0.65rem;
    background: linear-gradient(135deg, var(--cx-navy), var(--cx-blue));
    color: #fff; box-shadow: 0 6px 14px rgba(10, 45, 111, 0.28); flex-shrink: 0;
}
.cx-title { margin: 0; font-size: 1.45rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
.cx-subtitle { margin: 0.4rem 0 0; font-size: 0.875rem; color: var(--cx-muted); line-height: 1.45; max-width: 44rem; }
.cx-header-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; align-self: center; }
.cx-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.6rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
}
.cx-btn-primary { background: var(--cx-navy); color: #fff; border-color: var(--cx-navy); box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25); }
.cx-btn-primary:hover { background: var(--cx-blue); border-color: var(--cx-blue); color: #fff; }
.cx-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.cx-btn-secondary:hover { background: var(--cx-soft); color: var(--cx-navy); border-color: var(--cx-border); }
.cx-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600; }
.cx-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; }
.cx-alert-danger { background: #FDECEC; border: 1px solid #F6C9C9; color: #B03030; }
.cx-alert-warn { background: #FFF6E8; border: 1px solid #F3D19C; color: #9A6700; }
.cx-alert-inline { margin: 0 0 0.85rem; font-weight: 600; }
.cx-scan-card {
    padding: 1rem 1.15rem 0.95rem; margin-bottom: 1.15rem;
    background: linear-gradient(180deg, #fff 45%, #F4F8FD 160%);
    border-color: var(--cx-border);
}
.cx-scan-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.cx-scan-field { display: flex; flex-direction: column; gap: 0.28rem; flex: 1; min-width: 16rem; max-width: 36rem; }
.cx-scan-hint { margin: 0 0 0.75rem; font-size: 0.78rem; color: #94a3b8; }
.cx-input-lg { font-size: 1rem; padding: 0.68rem 0.85rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; letter-spacing: 0.02em; }
.cx-label { font-size: 0.8rem; font-weight: 700; color: #334155; }
.cx-input { padding: 0.52rem 0.7rem; font-size: 0.85rem; border: 1px solid #D8DCE2; border-radius: 0.55rem; background: #fff; color: #0f172a; width: 100%; box-sizing: border-box; }
.cx-input:focus { outline: none; border-color: var(--cx-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.cx-filters-actions { display: flex; align-items: center; gap: 0.55rem; }
.nic-scan-feedback { min-height: 1.2rem; margin-top: 0.55rem; font-size: 0.82rem; font-weight: 700; }
.nic-scan-feedback.text-success { color: var(--cx-green); }
.nic-scan-feedback.text-danger { color: var(--cx-red); }
.nic-scan-feedback.text-muted { color: #94a3b8; }
.cx-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.15rem; }
.cx-kpi-card {
    background: #fff; border: 1px solid var(--cx-line); border-radius: 0.85rem;
    padding: 0.9rem 1.05rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex; flex-direction: column; gap: 0.28rem;
}
.cx-kpi-card--green { border-color: #A7DFC3; background: linear-gradient(180deg, #fff 40%, #F2FBF6 140%); }
.cx-kpi-card--red { border-color: #F6C9C9; background: linear-gradient(180deg, #fff 40%, #FDECEC 140%); }
.cx-kpi-label { font-size: 0.66rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.cx-kpi-value { font-size: 1.35rem; font-weight: 800; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; color: #0f172a; }
.cx-kpi-note { font-size: 0.7rem; color: #94a3b8; }
.cx-text-green { color: var(--cx-green); }
.cx-text-red { color: var(--cx-red); }
.cx-card { background: #fff; border: 1px solid var(--cx-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; margin-bottom: 1.15rem; }
.cx-panel-head { padding: 0.75rem 1.05rem; border-bottom: 1px solid var(--cx-line); background: #FAFCFF; }
.cx-panel-head--navy { background: var(--cx-navy); border-bottom-color: var(--cx-navy); }
.cx-panel-head--navy .cx-panel-title { color: #fff; }
.cx-panel-title { margin: 0; font-size: 0.82rem; font-weight: 800; color: #334155; }
.cx-panel-body { padding: 0.95rem 1.05rem; }
.cx-scan-grid { display: grid; grid-template-columns: 1fr; gap: 0; }
@media (min-width: 992px) {
    .cx-scan-grid { grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 1.15rem; align-items: start; }
}
.cx-type-badge {
    display: inline-flex; padding: 0.12rem 0.5rem; border-radius: 999px;
    background: #EAF1FC; color: var(--cx-blue); font-size: 0.66rem; font-weight: 700; border: 1px solid #C9DAF3;
}
.cx-type-badge--sea { background: #EFFAF4; color: #116039; border-color: #A7DFC3; }
.cx-item-list { max-height: 16rem; overflow-y: auto; }
.cx-item-list--tall { max-height: 24rem; }
.nic-item-row {
    padding: 0.55rem 0.7rem; border-radius: 0.55rem; margin-bottom: 0.4rem; font-size: 0.85rem;
    border: 1px solid var(--cx-line); background: #fff;
}
.nic-item-row--warn { background: #FFF6E8; border-color: #F3D19C; }
.nic-missing-item { background: #FFF6E8; border-color: #F3D19C; }
.nic-scanned-item { background: #F4F8FD; border-color: #C5D4EB; }
.nic-item-name { font-weight: 700; color: #0f172a; }
.nic-item-meta { font-size: 0.78rem; color: #64748b; margin-top: 0.18rem; }
.font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
.nic-item-bulto { color: #94a3b8; }
.nic-item-weight { font-size: 0.78rem; font-weight: 800; color: var(--cx-blue); margin-top: 0.2rem; font-variant-numeric: tabular-nums; }
.nic-item-scanned-at { font-size: 0.72rem; color: var(--cx-green); margin-top: 0.18rem; font-weight: 700; }
.cx-empty-msg { font-size: 0.85rem; color: #94a3b8; margin: 0; }
@media (max-width: 900px) { .cx-kpis { grid-template-columns: 1fr 1fr; } .cx-scan-field { max-width: none; } }
@media (max-width: 560px) { .cx-kpis { grid-template-columns: 1fr; } }
</style>

@push('scripts')
<script>
(function() {
    var form = document.getElementById('nic-scan-form');
    var input = document.getElementById('code');
    var feedback = document.getElementById('nic-scan-feedback');
    var debounceTimer = null;
    var DEBOUNCE_MS = 180;

    function showFeedback(msg, isError) {
        feedback.textContent = msg;
        feedback.className = 'nic-scan-feedback ' + (isError ? 'text-danger' : 'text-success');
        if (msg && !isError) setTimeout(function() { feedback.textContent = ''; }, 3000);
    }

    function submitScan() {
        var code = (input.value || '').trim();
        if (!code) return;
        input.disabled = true;
        feedback.textContent = 'Verificando...';
        feedback.className = 'nic-scan-feedback text-muted';

        var fd = new FormData(form);
        fd.set('code', code);

        fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(result) {
            input.value = '';
            input.disabled = false;
            input.focus();

            if (result.ok && result.data.success) {
                showFeedback(result.data.message, false);
                document.getElementById('nic-scanned-count').textContent = result.data.scanned_count;
                document.getElementById('nic-missing-count').textContent = result.data.missing_count;
                document.getElementById('nic-total-items').textContent = result.data.total_items;
                document.getElementById('nic-scanned-label-count').textContent = result.data.scanned_count;
                var lbsEl = document.getElementById('nic-scanned-lbs');
                if (lbsEl && result.data.scanned_lbs_total !== undefined) {
                    lbsEl.textContent = Number(result.data.scanned_lbs_total).toFixed(1);
                }

                var scannedList = document.getElementById('nic-scanned-list');
                if (scannedList) {
                    var wrap = document.createElement('div');
                    wrap.innerHTML = result.data.scanned_row_html;
                    scannedList.appendChild(wrap.firstElementChild);
                }
                var emptyMsg = document.getElementById('nic-scanned-empty');
                if (emptyMsg) emptyMsg.style.display = 'none';

                var missingList = document.getElementById('nic-missing-list');
                if (missingList && result.data.scanned_code) {
                    var items = missingList.querySelectorAll('.nic-missing-item');
                    for (var i = 0; i < items.length; i++) {
                        if (items[i].getAttribute('data-code') === result.data.scanned_code) {
                            items[i].remove();
                            break;
                        }
                    }
                }
            } else {
                showFeedback(result.data.message || 'Error', true);
            }
        })
        .catch(function() {
            input.disabled = false;
            input.focus();
            showFeedback('Error de conexión.', true);
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if ((input.value || '').trim()) submitScan();
    });

    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        var code = (input.value || '').trim();
        if (code.length >= 4) {
            debounceTimer = setTimeout(function() {
                if ((input.value || '').trim() === code) submitScan();
            }, DEBOUNCE_MS);
        }
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer);
            if ((input.value || '').trim()) submitScan();
        }
    });

    input.focus();
})();
</script>
@endpush
@endsection
