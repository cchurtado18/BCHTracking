@extends('layouts.app')

@section('title', 'Generar factura · '.$deliveryNote->code)

@section('content')
@php
    $agency = \App\Models\Agency::find($preview['agency_id']) ?? $deliveryNote->agency;
    $hasAir = collect($preview['lines'])->contains(fn ($l) => $l['service_type'] === 'AIR');
    $hasSea = collect($preview['lines'])->contains(fn ($l) => $l['service_type'] === 'SEA');
    $hasCft = collect($preview['lines'])->contains(fn ($l) => $l['service_type'] === 'CFT');
    $freightLines = collect($preview['lines'])->reject(fn ($l) => $l['service_type'] === 'DELIVERY');
    $selectedNotes = $selectedNotes ?? collect([$deliveryNote]);
    $compatibleNotes = $compatibleNotes ?? collect();
    $noteSummaries = collect($preview['note_summaries'] ?? []);
    $freightUsd = (float) ($preview['freight_usd'] ?? $preview['total_usd']);
    $airRate = old('rate_air', $suggestedRates['AIR'] ?? null);
    $seaRate = old('rate_sea', $suggestedRates['SEA'] ?? null);
    $cftRate = old('rate_cft', $suggestedRates['CFT'] ?? null);
@endphp
<div class="pt-page">
    <x-module-banner
        section="Contabilidad"
        current="Generar factura"
        title="Generar factura PrimeTrack"
        subtitle="Desde {{ $selectedNotes->count() === 1 ? 'la hoja '.$deliveryNote->code : $selectedNotes->count().' hojas de salida' }}. Confirme tarifas, delivery y tipo de cambio."
        back-href="{{ route('accounting.invoices.index') }}"
        back-label="Volver a facturas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125 1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

    <div class="pt-card">
        <div class="pt-card-header pt-table-header">
            <h2 class="pt-card-title">Cliente (bill-to)</h2>
        </div>
        <div class="pt-card-body">
            <p class="pt-summary-line"><strong>{{ $agency?->name ?? '—' }}</strong> · Código <span class="pt-code">{{ $agency?->code ?? '—' }}</span></p>
            <p class="pt-muted">Paquetes en las hojas seleccionadas: {{ $selectedNotes->sum(fn ($n) => $n->deliveries->count()) }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('accounting.invoices.store-from-note', $deliveryNote) }}" id="invoice-issue-form">
        @csrf
        @foreach($selectedNotes as $note)
        <input type="hidden" name="delivery_note_ids[]" value="{{ $note->id }}">
        @endforeach

        <div class="pt-card">
            <div class="pt-card-header pt-table-header">
                <h2 class="pt-card-title">Hojas a facturar</h2>
                <span class="pt-card-badge">{{ $selectedNotes->count() }} {{ $selectedNotes->count() === 1 ? 'hoja' : 'hojas' }}</span>
            </div>
            <div class="pt-card-body">
                <p class="pt-muted" style="margin-bottom:0.75rem">Al marcar o desmarcar una hoja se recarga la vista previa con todos los paquetes y servicios.</p>
                @foreach($selectedNotes as $note)
                @php $noteBillTo = $note->billingAgency(); @endphp
                <label class="pt-checkbox-row">
                    <input type="checkbox" class="js-invoice-note" value="{{ $note->id }}" checked
                           @if((int) $note->id === (int) $deliveryNote->id) onclick="return false;" @endif>
                    <span class="pt-code">{{ $note->code }}</span>
                    · {{ $noteBillTo?->name ?? $note->agency?->name ?? $agency?->name ?? '—' }}
                    @if($note->hasMixedBillTos())
                    <span class="pt-muted">· cuentas mixtas</span>
                    @endif
                    · {{ $note->deliveries->count() }} {{ $note->deliveries->count() === 1 ? 'paquete' : 'paquetes' }}
                </label>
                @endforeach
                @if($compatibleNotes->isNotEmpty())
                <p class="pt-muted" style="margin-top:0.75rem">Otras hojas del mismo cliente:</p>
                @foreach($compatibleNotes as $note)
                @php $noteBillTo = $note->billingAgency(); @endphp
                <label class="pt-checkbox-row">
                    <input type="checkbox" class="js-invoice-note" value="{{ $note->id }}">
                    <span class="pt-code">{{ $note->code }}</span>
                    · {{ $noteBillTo?->name ?? $note->agency?->name ?? '—' }}
                    · {{ $note->deliveries_count }} {{ $note->deliveries_count === 1 ? 'paquete' : 'paquetes' }}
                </label>
                @endforeach
                @endif
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-card-header pt-table-header">
                <h2 class="pt-card-title">Vista previa por hoja</h2>
                <span class="pt-card-badge">{{ $selectedNotes->count() }} {{ $selectedNotes->count() === 1 ? 'hoja' : 'hojas' }}</span>
            </div>
            <div class="pt-table-wrap">
                <table class="pt-table">
                    <thead>
                        <tr>
                            <th>Hoja</th>
                            <th>Servicio</th>
                            <th>Paquetes</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($noteSummaries as $summary)
                            @foreach($summary['lines'] as $line)
                            <tr>
                                <td><span class="pt-code">{{ $summary['code'] }}</span></td>
                                <td>{{ $line['description'] }}</td>
                                <td class="pt-num">{{ $line['package_count'] }}</td>
                                <td class="pt-num">{{ number_format($line['quantity_lbs'], 2) }} {{ $line['unit'] }}</td>
                            </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="4" class="pt-empty">No hay paquetes en las hojas seleccionadas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-card-header pt-table-header">
                <h2 class="pt-card-title">Totales por servicio</h2>
                <span class="pt-card-badge">{{ $freightLines->count() }} {{ $freightLines->count() === 1 ? 'servicio' : 'servicios' }}</span>
            </div>
            <div class="pt-table-wrap">
                <table class="pt-table">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Paquetes</th>
                            <th>Cantidad</th>
                            <th>Tarifa sugerida</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($freightLines as $line)
                        <tr>
                            <td>{{ $line['description'] }}</td>
                            <td class="pt-num">{{ $line['package_count'] }}</td>
                            <td class="pt-num">{{ number_format($line['quantity_lbs'], 2) }} {{ $line['unit'] ?? \App\Support\ServiceType::unit($line['service_type']) }}</td>
                            <td class="pt-num">
                                @if(($suggestedRates[$line['service_type']] ?? null) === null)
                                <span class="pt-muted">Sin tarifa vigente</span>
                                @else
                                ${{ number_format($line['rate_per_lb'], 4) }} / {{ $line['unit'] ?? \App\Support\ServiceType::unit($line['service_type']) }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-card-header pt-table-header">
                <h2 class="pt-card-title">Tarifas, delivery y tipo de cambio</h2>
            </div>
            <div class="pt-card-body">
                <div class="pt-fields-grid">
                    @if($hasAir)
                    <div class="pt-field">
                        <label class="pt-label" for="rate_air">Tarifa aéreo (USD/lb) *</label>
                        <input type="number" step="0.0001" min="0" name="rate_air" id="rate_air" required
                               value="{{ $airRate === null || $airRate === '' ? '' : $airRate }}" class="pt-input"
                               placeholder="Indique la tarifa aérea">
                        @if(($suggestedRates['AIR'] ?? null) === null)
                        <p class="pt-muted">No hay tarifa aérea vigente. Indique el precio para las hojas con ese servicio.</p>
                        @endif
                    </div>
                    @endif
                    @if($hasSea)
                    <div class="pt-field">
                        <label class="pt-label" for="rate_sea">Tarifa marítimo (USD/lb) *</label>
                        <input type="number" step="0.0001" min="0" name="rate_sea" id="rate_sea" required
                               value="{{ $seaRate === null || $seaRate === '' ? '' : $seaRate }}" class="pt-input"
                               placeholder="Indique la tarifa marítima">
                        @if(($suggestedRates['SEA'] ?? null) === null)
                        <p class="pt-muted">No hay tarifa marítima vigente. Indique el precio para las hojas con ese servicio.</p>
                        @endif
                    </div>
                    @endif
                    <div class="pt-field">
                        <label class="pt-label" for="rate_cft">Tarifa pie cúbico (USD/pie³)@if($hasCft) * @endif</label>
                        <input type="number" step="0.0001" min="0" name="rate_cft" id="rate_cft"
                               @if($hasCft) required @endif
                               value="{{ $cftRate === null || $cftRate === '' ? '' : $cftRate }}" class="pt-input"
                               placeholder="Indique la tarifa de pie cúbico">
                        @if($hasCft && ($suggestedRates['CFT'] ?? null) === null)
                        <p class="pt-muted">No hay tarifa de pie cúbico vigente. Indique el precio para las hojas con ese servicio.</p>
                        @elseif(! $hasCft)
                        <p class="pt-muted">Se usa si hay paquetes de pie cúbico (CFT). Puede dejarlo vacío o guardarlo como tarifa vigente.</p>
                        @endif
                    </div>
                    <div class="pt-field">
                        <label class="pt-label" for="delivery_fee">Delivery (USD)</label>
                        <input type="number" step="0.01" min="0" name="delivery_fee" id="delivery_fee"
                               value="{{ old('delivery_fee', $deliveryFee ?? 0) }}" class="pt-input">
                    </div>
                    <div class="pt-field">
                        <label class="pt-label" for="exchange_rate">T.C. COR por 1 USD *</label>
                        <input type="number" step="0.0001" min="0.0001" name="exchange_rate" id="exchange_rate" required
                               value="{{ old('exchange_rate', $exchangeRate) }}" class="pt-input">
                    </div>
                </div>
                <p class="pt-summary-line" id="invoice-total-preview">
                    Flete $<span id="invoice-freight-preview">{{ number_format($freightUsd, 2) }}</span>
                    + Delivery $<span id="invoice-delivery-preview">{{ number_format((float) old('delivery_fee', $deliveryFee ?? 0), 2) }}</span>
                    = <strong>Total $<span id="invoice-grand-preview">{{ number_format($freightUsd + (float) old('delivery_fee', $deliveryFee ?? 0), 2) }}</span></strong>
                </p>
                <label class="pt-checkbox-row">
                    <input type="checkbox" name="persist_rates" value="1" @checked(old('persist_rates'))>
                    Guardar estas tarifas como vigentes para la agencia
                </label>
                @if(($creditBalance ?? 0) > 0)
                <label class="pt-checkbox-row">
                    <input type="checkbox" name="apply_credit" value="1" @checked(old('apply_credit', true))>
                    Aplicar saldo a favor (${{ number_format($creditBalance, 2) }} USD)
                </label>
                <div class="pt-field" style="max-width:14rem;margin-top:0.5rem">
                    <label class="pt-label" for="apply_credit_amount">Monto de crédito a aplicar</label>
                    <input type="number" step="0.01" min="0" max="{{ $creditBalance }}" name="apply_credit_amount" id="apply_credit_amount" class="pt-input"
                           value="{{ old('apply_credit_amount', number_format(min($creditBalance, $freightUsd + (float) old('delivery_fee', $deliveryFee ?? 0)), 2, '.', '')) }}">
                </div>
                @endif
                <div class="pt-form-actions">
                    <a href="{{ route('accounting.invoices.create') }}" class="pt-btn pt-btn-secondary">Cancelar</a>
                    <button type="submit" class="pt-btn pt-btn-primary">Emitir factura y abrir voucher</button>
                </div>
            </div>
        </div>
    </form>
</div>

@include('partials.primetrack-module-styles')
<script>
(function () {
    var primaryId = {{ json_encode((string) $deliveryNote->id) }};
    var previewUrl = {{ json_encode(route('accounting.invoices.create-from-note', $deliveryNote)) }};
    var freightLines = {!! json_encode($freightLines->map(fn ($l) => [
        'service' => $l['service_type'],
        'qty' => (float) $l['quantity_lbs'],
    ])->values()) !!};
    var fee = document.getElementById('delivery_fee');
    var freightEl = document.getElementById('invoice-freight-preview');
    var deliveryEl = document.getElementById('invoice-delivery-preview');
    var grandEl = document.getElementById('invoice-grand-preview');
    var rateInputs = {
        AIR: document.getElementById('rate_air'),
        SEA: document.getElementById('rate_sea'),
        CFT: document.getElementById('rate_cft')
    };

    function fmt(n) { return (Math.round(n * 100) / 100).toFixed(2); }
    function rateFor(service) {
        var el = rateInputs[service];
        if (!el) return 0;
        var n = parseFloat(el.value);
        return isNaN(n) || n < 0 ? 0 : n;
    }
    function freightTotal() {
        return freightLines.reduce(function (sum, line) {
            return sum + (line.qty * rateFor(line.service));
        }, 0);
    }
    function refreshTotal() {
        if (!deliveryEl || !grandEl || !freightEl) return;
        var d = fee ? parseFloat(fee.value) : 0;
        if (isNaN(d) || d < 0) d = 0;
        var f = freightTotal();
        freightEl.textContent = fmt(f);
        deliveryEl.textContent = fmt(d);
        grandEl.textContent = fmt(f + d);
    }

    document.querySelectorAll('.js-invoice-note').forEach(function (box) {
        box.addEventListener('change', function () {
            var ids = [primaryId];
            document.querySelectorAll('.js-invoice-note:checked').forEach(function (c) {
                if (ids.indexOf(c.value) === -1) {
                    ids.push(c.value);
                }
            });
            window.location = previewUrl + (ids.length ? ('?notes=' + encodeURIComponent(ids.join(','))) : '');
        });
    });

    if (fee) fee.addEventListener('input', refreshTotal);
    Object.keys(rateInputs).forEach(function (key) {
        if (rateInputs[key]) rateInputs[key].addEventListener('input', refreshTotal);
    });
    refreshTotal();
})();
</script>
@endsection
