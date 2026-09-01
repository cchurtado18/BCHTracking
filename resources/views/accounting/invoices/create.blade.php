@extends('layouts.app')

@section('title', 'Nueva factura PrimeTrack')

@section('content')
<div class="pt-page">
    <x-module-banner
        section="Contabilidad"
        current="Nueva factura"
        title="Nueva factura PrimeTrack"
        subtitle="Elija la hoja de salida a facturar. Solo aparecen hojas con paquetes y sin factura activa."
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
            <h2 class="pt-card-title">Hoja de salida</h2>
            <span class="pt-card-badge">{{ $notes->count() }} {{ $notes->count() === 1 ? 'disponible' : 'disponibles' }}</span>
        </div>
        <div class="pt-card-body">
            @if($notes->isEmpty())
            <p class="pt-muted">No hay hojas pendientes de facturar. Genere una salida o anule la factura activa de una hoja ya facturada.</p>
            <div class="pt-form-actions">
                <a href="{{ route('salidas.index') }}" class="pt-btn pt-btn-primary">Ir a Salidas</a>
            </div>
            @else
            <form method="POST" action="{{ route('accounting.invoices.start-create') }}">
                @csrf
                <div class="pt-field pt-field-full">
                    <label class="pt-label" for="delivery_note_id">Hoja de salida *</label>
                    <select name="delivery_note_id" id="delivery_note_id" required class="pt-select">
                        <option value="">— Seleccionar hoja —</option>
                        @foreach($notes as $note)
                        <option value="{{ $note->id }}" @selected((string) old('delivery_note_id') === (string) $note->id)>
                            {{ $note->code }}
                            · {{ $note->agency?->name ?? 'Sin agencia' }}
                            · {{ $note->deliveries_count }} {{ $note->deliveries_count === 1 ? 'paquete' : 'paquetes' }}
                        </option>
                        @endforeach
                    </select>
                    <p class="pt-field-hint">En el siguiente paso confirmará tarifas (aéreo, marítimo o pie cúbico) y el tipo de cambio.</p>
                </div>
                <div class="pt-form-actions">
                    <a href="{{ route('accounting.invoices.index') }}" class="pt-btn pt-btn-secondary">Cancelar</a>
                    <button type="submit" class="pt-btn pt-btn-primary">Continuar</button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>

@include('partials.primetrack-module-styles')
@endsection
