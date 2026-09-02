@php
    $clientName = $package->agency?->name ?? 'cliente';
    $tz = config('app.display_timezone', 'America/New_York');
    $receivedAt = optional($package->created_at)->timezone($tz)?->format('d/m/Y H:i');
@endphp
<x-mail::message>
# Paquete recibido en Miami

Hola {{ $clientName }},

Su paquete **{{ $packageCode }}** ya está en nuestro almacén de Miami.

**Destinatario:** {{ $package->label_name ?: '—' }}  
@if($package->warehouse_code)
**Warehouse:** {{ $package->warehouse_code }}  
@endif
@if($package->tracking_external)
**Tracking:** {{ $package->tracking_external }}  
@endif
@if($receivedAt)
**Recibido:** {{ $receivedAt }}  
@endif
@if($package->bultos_total && $package->bultos_total > 1)
**Bulto:** {{ $package->bulto_index }} de {{ $package->bultos_total }}  
@endif

Cuando esté listo para retiro en Nicaragua le enviaremos otro correo a esta misma dirección.

<x-mail::button :url="$trackingUrl">
Ver seguimiento
</x-mail::button>

Si el botón no funciona, copie:

{{ $trackingUrl }}

{{ config('app.name') }}
</x-mail::message>
