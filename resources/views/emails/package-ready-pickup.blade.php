@php
    $clientName = $package->agency?->name ?? 'cliente';
    $tz = config('app.display_timezone', 'America/New_York');
    $readyAt = optional($package->ready_at)->timezone($tz)?->format('d/m/Y H:i');
@endphp
<x-mail::message>
# Paquete listo para retiro

Hola {{ $clientName }},

Su paquete **{{ $packageCode }}** ya está **listo para retiro** en nuestro almacén de Nicaragua.

**Destinatario:** {{ $package->label_name ?: '—' }}  
@if($package->warehouse_code)
**Warehouse:** {{ $package->warehouse_code }}  
@endif
@if($package->tracking_external)
**Tracking:** {{ $package->tracking_external }}  
@endif
@if($readyAt)
**Disponible desde:** {{ $readyAt }}  
@endif

Puede presentarse a recogerlo. Si tiene dudas, responda a este correo o comuníquese con su ejecutivo.

<x-mail::button :url="$trackingUrl">
Ver seguimiento
</x-mail::button>

Si el botón no funciona, copie:

{{ $trackingUrl }}

{{ config('app.name') }}
</x-mail::message>
