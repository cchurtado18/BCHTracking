@props([
    'agency' => null,
    'showCode' => true,
])
@if(! $agency)
    <span {{ $attributes }}>{{ $slot->isEmpty() ? '—' : $slot }}</span>
@else
    <span {{ $attributes->merge(['title' => $agency->listingAccountLabel(), 'style' => 'display:block;white-space:normal;line-height:1.3;']) }}>
        {{ $agency->agencyColumnLabel($showCode) }}
        @if($agency->isDirectClient())
            <span style="display:block;margin-top:0.12rem;font-size:0.72rem;font-weight:700;color:#334155;">Cliente: {{ $agency->name }}</span>
        @endif
    </span>
@endif
