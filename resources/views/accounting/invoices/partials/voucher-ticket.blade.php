@php
    $company = $company ?? config('accounting.company');
    $companyName = $company['name'] ?? 'SkyLink One';
    if (str_contains(mb_strtolower($companyName), 'primetrack')) {
        $companyName = 'SkyLink One';
    }
    $agency = $invoice->agency;
    $note = $invoice->deliveryNote;
    $printedAt = $printedAt ?? now()->timezone(config('app.display_timezone', config('app.timezone')));
    $printer = $printer ?? (auth()->user()?->name ?? '—');
@endphp
<div class="vch-ticket">
    <div class="vch-center">
        <div class="vch-company">{{ $companyName }}</div>
        @if(!empty($company['tax_id']))
            <div class="vch-muted">{{ $company['tax_id'] }}</div>
        @endif
    </div>
    @if(!empty($company['address']))
        <div class="vch-muted">{{ $company['address'] }}</div>
    @endif
    @if(!empty($company['phones']))
        <div class="vch-muted">Tel: {{ $company['phones'] }}</div>
    @endif

    <hr class="vch-sep">
    <div class="vch-center vch-title">NOTA DE COBRO SKYLINK ONE</div>
    <hr class="vch-sep">

    <div class="vch-block">
        <div>OC#: {{ $invoice->folio }}</div>
        <div>FECHA: {{ optional($invoice->issued_at)->format('d/m/Y') ?? '—' }}</div>
        <div>REG.POR: {{ $invoice->createdBy?->name ?? '—' }}</div>
    </div>

    <hr class="vch-sep">

    <div class="vch-block">
        <div>F/H Impreso: {{ $printedAt->format('d/m/Y H:i') }}</div>
        <div>Impreso por: {{ $printer }}</div>
        @if($invoice->noteCodesLabel() !== '—')
            <div>Hoja salida: {{ $invoice->noteCodesLabel() }}</div>
        @endif
    </div>

    <hr class="vch-sep">

    <div class="vch-block">
        <div>COD CLI: {{ $agency?->code ?? '—' }}</div>
        <div>CED/RUC: —</div>
        <div>Cliente: {{ $agency?->name ?? '—' }}</div>
        <div>Telefono: {{ $agency?->phone ?: '—' }}</div>
        <div>Email: {{ $agency?->billingEmail() ?: '—' }}</div>
        <div>Ciudad: {{ $agency?->department ?: '—' }}</div>
        @if($agency?->address)
            <div>Dir: {{ $agency->address }}</div>
        @endif
    </div>

    <hr class="vch-sep">

    <div class="vch-cols">
        <span>SERVICIO</span>
        <span class="vch-right">CANT.</span>
        <span class="vch-right">TARIFA</span>
        <span class="vch-right">IMPORTE</span>
    </div>

    @foreach($invoice->lines as $line)
        <div class="vch-line-name">{{ $line->description }}</div>
        <div class="vch-line-nums">
            <span></span>
            <span>{{ number_format((float) $line->quantity_lbs, 2, '.', ',') }} {{ \App\Support\ServiceType::unit($line->service_type) }}</span>
            <span>{{ number_format((float) $line->rate_per_lb, 2, '.', ',') }}</span>
            <span>{{ number_format((float) $line->amount_usd, 2, '.', ',') }}</span>
        </div>
    @endforeach

    <hr class="vch-sep">

    <div class="vch-totals">
        @if((float) $invoice->total_lbs > 0)
        <div class="vch-row"><span>TOTAL LBS</span><strong>{{ number_format((float) $invoice->total_lbs, 2, '.', ',') }}</strong></div>
        @endif
        @php $voucherCft = $invoice->lines->where('service_type', 'CFT')->sum('quantity_lbs'); @endphp
        @if($voucherCft > 0)
        <div class="vch-row"><span>TOTAL PIE³</span><strong>{{ number_format((float) $voucherCft, 2, '.', ',') }}</strong></div>
        @endif
        <div class="vch-row"><span>TOTAL COR</span><strong>{{ number_format((float) $invoice->total_cor, 2, '.', ',') }}</strong></div>
        <div class="vch-row"><span>TOTAL USD</span><strong>{{ number_format((float) $invoice->total_usd, 2, '.', ',') }}</strong></div>
    </div>

    <hr class="vch-sep">

    <div class="vch-pay">
        <div class="vch-row"><span>Estado</span><strong>{{ $invoice->statusLabel() }}</strong></div>
        <div class="vch-row"><span>Pagado</span><strong>{{ number_format((float) $invoice->amount_paid, 2, '.', ',') }}</strong></div>
        <div class="vch-row"><span>Saldo</span><strong>{{ number_format($invoice->balanceUsd(), 2, '.', ',') }}</strong></div>
    </div>

    <hr class="vch-sep">

    <div class="vch-sig"></div>
    <div class="vch-sig-note vch-center">Firma cliente igual a Ced.</div>
    <div class="vch-sig-note vch-center">Reconozco el monto adeudado</div>

    <hr class="vch-sep">
    <div class="vch-footer vch-center">{{ $company['footer'] ?? 'Es un gusto atenderle!' }}</div>
</div>
