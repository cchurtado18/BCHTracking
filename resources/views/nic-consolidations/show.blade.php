@extends('layouts.app')

@section('title', 'Escanear Saco')

@section('content')
<div class="nic-page nic-show-page">
    {{-- Cabecera --}}
    <header class="nic-show-header">
        <div class="nic-show-header-inner">
            <div>
                <h1 class="nic-show-title">{{ $consolidation->code }}</h1>
                <p class="nic-show-subtitle">Escaneo de paquetes — Nicaragua</p>
            </div>
            <a href="{{ route('nic-consolidations.index') }}" class="nic-btn nic-btn-secondary">← Volver</a>
        </div>
    </header>

    <div class="nic-show-grid">
        {{-- Panel de escaneo --}}
        <div class="nic-show-main">
            <div class="nic-card">
                <div class="nic-card-header">
                    <h2 class="nic-card-title">Escanear paquete</h2>
                </div>
                <div class="nic-card-body">
                    <p class="nic-scan-hint mb-4">Use la pistola: al escanear un código válido se registra automáticamente (no hace falta Enter ni botón).</p>

                    <form id="nic-scan-form" action="{{ route('nic-consolidations.scan', $consolidation->id) }}" method="POST" class="nic-scan-form-block">
                        @csrf
                        <input type="text" name="code" id="code" class="nic-input nic-input-lg" placeholder="Escanear código del paquete (6 dígitos o tracking)" autofocus required autocomplete="off">
                        <button type="submit" class="nic-btn nic-btn-primary" tabindex="-1">Escanear</button>
                    </form>
                    <div id="nic-scan-feedback" class="nic-scan-feedback"></div>
                </div>
            </div>

            {{-- Resumen --}}
            <div class="nic-stats nic-show-stats">
                <div class="nic-stat-card nic-stat-total">
                    <span class="nic-stat-label">Total items</span>
                    <span id="nic-total-items" class="nic-stat-value">{{ $totalItems }}</span>
                </div>
                <div class="nic-stat-card nic-stat-success">
                    <span class="nic-stat-label">Escaneados</span>
                    <span id="nic-scanned-count" class="nic-stat-value nic-stat-value-success">{{ $scannedCount }}</span>
                </div>
                <div class="nic-stat-card nic-stat-danger">
                    <span class="nic-stat-label">Faltantes</span>
                    <span id="nic-missing-count" class="nic-stat-value nic-stat-value-danger">{{ $missingCount }}</span>
                </div>
            </div>

            @if($missingItems->count() > 0)
            <div class="nic-card">
                <div class="nic-card-header">
                    <h2 class="nic-card-title">Paquetes faltantes ({{ $missingItems->count() }})</h2>
                </div>
                <div class="nic-card-body">
                    <p class="nic-alert nic-alert-amber">Si un código tiene varios bultos (bulto 1 de 3, etc.), escanee el mismo código una vez por cada bulto.</p>
                    <div id="nic-missing-list" class="nic-item-list nic-missing-list">
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

        {{-- Paquetes escaneados --}}
        <div class="nic-show-sidebar">
            <div class="nic-card">
                <div class="nic-card-header nic-table-header">
                    <h2 class="nic-card-title">Escaneados (<span id="nic-scanned-label-count">{{ $scannedItems->count() }}</span>)</h2>
                </div>
                <div class="nic-card-body">
                    <div id="nic-scanned-list" class="nic-item-list nic-scanned-list">
                        @foreach($scannedItems as $item)
                        <div class="nic-item-row nic-scan-item nic-scanned-item" data-code="{{ $item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? '' }}">
                            <div class="nic-item-name">{{ $item->preregistration->label_name }}</div>
                            <div class="nic-item-meta">
                                Código: <span class="font-mono">{{ $item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? 'N/A' }}</span>
                                @if($item->preregistration->bultos_total && $item->preregistration->bultos_total > 1)
                                <span class="nic-item-bulto">(bulto {{ $item->preregistration->bulto_index }} de {{ $item->preregistration->bultos_total }})</span>
                                @endif
                            </div>
                            <div class="nic-item-scanned-at">✓ {{ $item->scanned_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</div>
                        </div>
                        @endforeach
                    </div>
                    @if($scannedItems->count() === 0)
                    <p id="nic-scanned-empty" class="nic-empty-list-msg">Aún no se han escaneado paquetes</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nic-show-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.nic-show-header { margin-bottom: 1.5rem; }
.nic-show-header-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.nic-show-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #111827; }
.nic-show-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: #6b7280; }
.nic-show-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
@media (min-width: 992px) {
    .nic-show-grid { grid-template-columns: 2fr 1fr; }
}
.nic-show-main { min-width: 0; }
.nic-show-sidebar { min-width: 0; }
.nic-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.nic-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
.nic-card-header.nic-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.nic-card-header.nic-table-header .nic-card-title { color: #fff; }
.nic-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.nic-card-body { padding: 1.25rem; }
.nic-scan-hint { font-size: 0.875rem; color: #6b7280; margin: 0; }
.mb-4 { margin-bottom: 1rem; }
.nic-scan-form-block { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 0.5rem; }
.nic-input { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827; flex: 1; min-width: 180px; }
.nic-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.nic-input-lg { padding: 0.65rem 1rem; font-size: 1rem; font-family: ui-monospace, monospace; }
.nic-scan-feedback { min-height: 1.5rem; font-size: 0.875rem; margin-top: 0.25rem; }
.nic-scan-feedback.text-success { color: #059669; }
.nic-scan-feedback.text-danger { color: #dc2626; }
.nic-scan-feedback.text-muted { color: #6b7280; }
.nic-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.nic-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.nic-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.nic-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.nic-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.nic-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.nic-show-stats .nic-stat-card { margin-bottom: 0; }
.nic-stat-card { background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb; display: flex; flex-direction: column; gap: 0.25rem; }
.nic-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.nic-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.nic-stat-total { border-left: 4px solid #0d9488; }
.nic-stat-success { border-left: 4px solid #059669; }
.nic-stat-success .nic-stat-label { color: #047857; }
.nic-stat-value-success { color: #059669; }
.nic-stat-danger { border-left: 4px solid #dc2626; }
.nic-stat-danger .nic-stat-label { color: #b91c1c; }
.nic-stat-value-danger { color: #dc2626; }
.nic-alert { padding: 0.5rem 0.75rem; font-size: 0.875rem; border-radius: 0.5rem; margin: 0 0 1rem; border: 1px solid; }
.nic-alert-amber { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
.nic-item-list { max-height: 16rem; overflow-y: auto; }
.nic-scanned-list { max-height: 24rem; }
.nic-item-row { padding: 0.5rem 0.75rem; border-radius: 0.5rem; margin-bottom: 0.35rem; font-size: 0.875rem; border: 1px solid #e5e7eb; }
.nic-missing-item { background: #fefce8; border-color: #fef08a; }
.nic-scanned-item { background: #f0fdf4; border-color: #bbf7d0; }
.nic-item-name { font-weight: 600; color: #111827; }
.nic-item-meta { font-size: 0.8125rem; color: #6b7280; margin-top: 0.2rem; }
.font-mono { font-family: ui-monospace, monospace; }
.nic-item-bulto { color: #9ca3af; }
.nic-item-scanned-at { font-size: 0.75rem; color: #059669; margin-top: 0.25rem; }
.nic-empty-list-msg { font-size: 0.875rem; color: #6b7280; margin: 0; }
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
