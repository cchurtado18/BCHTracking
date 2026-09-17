@props([
    'agency' => null,
    'showCode' => true,
])
@if(! $agency)
    <span {{ $attributes }}>{{ $slot->isEmpty() ? '—' : $slot }}</span>
@else
    <span {{ $attributes->merge(['title' => $agency->agencyColumnLabel($showCode)]) }}>{{ $agency->agencyColumnLabel($showCode) }}</span>
@endif
