@php
    $alertsUrl = route('alerts.index');
@endphp
<x-mail::message>
# Paquetes que necesitan revisión

Hay **{{ $alerts->count() }}** {{ $alerts->count() === 1 ? 'paquete' : 'paquetes' }} que el sistema marcó para que no se pierdan en el almacén o en un lote a medias.

@foreach($alerts->groupBy('rule') as $rule => $rows)
**{{ \App\Models\PackageAlert::RULES[$rule] ?? $rule }}**
@foreach($rows as $alert)
- {{ $alert->message }}
@endforeach

@endforeach

<x-mail::button :url="$alertsUrl">
Ver alertas
</x-mail::button>

Este correo se envía solo cuando aparecen casos nuevos. Los que ya revisó no se vuelven a notificar.

{{ config('app.name') }}
</x-mail::message>
