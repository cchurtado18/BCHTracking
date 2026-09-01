@php
    $clientName = $invoice->agency?->name ?? 'cliente';
@endphp
<x-mail::message>
# Factura {{ $invoice->folio }}

Hola {{ $clientName }},

Le enviamos su factura PrimeTrack{{ $invoice->deliveryNote ? ' correspondiente a la hoja de salida **'.$invoice->deliveryNote->code.'**' : '' }}.

**Fecha:** {{ optional($invoice->issued_at)->format('d/m/Y') ?? '—' }}  
**Total:** ${{ number_format((float) $invoice->total_usd, 2) }} USD  
**Saldo pendiente:** ${{ number_format($invoice->balanceUsd(), 2) }} USD

<x-mail::button :url="$voucherUrl">
Ver / imprimir factura
</x-mail::button>

Este enlace vence en 30 días. Si el botón no funciona, copie:

{{ $voucherUrl }}

Gracias por su preferencia.  
{{ config('app.name') }}
</x-mail::message>
