@extends('layouts.app')

@section('title', 'Nueva factura PrimeTrack')

@section('content')
<div class="pt-page">
    <x-module-banner
        section="Contabilidad"
        current="Nueva factura"
        title="Nueva factura PrimeTrack"
        subtitle="Elija una o más hojas de la misma red de agencia. Solo aparecen hojas con paquetes y sin factura activa."
        back-href="{{ route('accounting.invoices.index') }}"
        back-label="Volver a facturas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if(session('error'))
    <div class="pt-alert pt-alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="pt-alert pt-alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="pt-card">
        <div class="pt-card-header pt-table-header">
            <h2 class="pt-card-title">Hojas de salida</h2>
            <span class="pt-card-badge">{{ $notes->count() }} {{ $notes->count() === 1 ? 'disponible' : 'disponibles' }}</span>
        </div>
        <div class="pt-card-body">
            @if($notes->isEmpty())
            <p class="pt-muted">No hay hojas pendientes de facturar. Genere una salida o anule la factura activa de una hoja ya facturada.</p>
            <div class="pt-form-actions">
                <a href="{{ route('salidas.index') }}" class="pt-btn pt-btn-primary">Ir a Salidas</a>
            </div>
            @else
            <form method="POST" action="{{ route('accounting.invoices.start-create') }}" id="invoice-notes-form">
                @csrf
                <p class="pt-muted" style="margin-top:0">Puede marcar varias hojas si son de la misma agencia o de sus subagencias.</p>
                <div class="pt-table-wrap">
                    <table class="pt-table" id="invoice-notes-table">
                        <thead>
                            <tr>
                                <th style="width:2.5rem"></th>
                                <th>Hoja</th>
                                <th>Agencia</th>
                                <th class="pt-num">Paquetes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notes as $note)
                            @php
                                $family = $note->invoiceFamilyKey();
                                $oldIds = collect(old('delivery_note_ids', old('delivery_note_id') ? [old('delivery_note_id')] : []))->map(fn ($id) => (string) $id);
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="delivery_note_ids[]" value="{{ $note->id }}"
                                           class="invoice-note-check"
                                           data-family="{{ $family }}"
                                           @checked($oldIds->contains((string) $note->id))>
                                </td>
                                <td><span class="pt-code">{{ $note->code }}</span></td>
                                <td>{{ $note->agency?->name ?? 'Sin agencia' }}@if($note->agency?->code) <span class="pt-muted">· {{ $note->agency->code }}</span>@endif</td>
                                <td class="pt-num">{{ $note->deliveries_count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="pt-field-hint">En el siguiente paso confirmará tarifas, el cargo de delivery y el tipo de cambio.</p>
                <div class="pt-form-actions">
                    <a href="{{ route('accounting.invoices.index') }}" class="pt-btn pt-btn-secondary">Cancelar</a>
                    <button type="submit" class="pt-btn pt-btn-primary" id="invoice-notes-submit">Continuar</button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>

@include('partials.primetrack-module-styles')
@if($notes->isNotEmpty())
<script>
(function () {
    var checks = Array.prototype.slice.call(document.querySelectorAll('.invoice-note-check'));
    var form = document.getElementById('invoice-notes-form');

    function selectedFamily() {
        var checked = checks.filter(function (c) { return c.checked && !c.disabled; });
        return checked.length ? checked[0].getAttribute('data-family') : '';
    }

    function refresh() {
        var family = selectedFamily();
        checks.forEach(function (c) {
            var same = !family || c.getAttribute('data-family') === family;
            if (!same && c.checked) {
                c.checked = false;
            }
            c.disabled = !!family && !same;
            var row = c.closest('tr');
            if (row) {
                row.style.opacity = c.disabled ? '0.45' : '';
            }
        });
    }

    checks.forEach(function (c) {
        c.addEventListener('change', refresh);
    });
    refresh();

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!checks.some(function (c) { return c.checked && !c.disabled; })) {
                e.preventDefault();
                alert('Seleccione al menos una hoja de salida.');
            }
        });
    }
})();
</script>
@endif
@endsection
