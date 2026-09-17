@php $mixedNotesToSplit = $mixedNotesToSplit ?? collect(); @endphp
@if($mixedNotesToSplit->isNotEmpty())
<div class="inv-alert inv-alert-danger">
    <p style="margin:0 0 0.65rem"><strong>Hay {{ $mixedNotesToSplit->count() }} {{ $mixedNotesToSplit->count() === 1 ? 'hoja que mezcla' : 'hojas que mezclan' }} clientes</strong> y no se pueden facturar juntas. Sepárelas: cada cliente queda en su propia hoja, sin re-escanear.</p>
    <div class="inv-mixed-list">
        @foreach($mixedNotesToSplit as $mixedNote)
        @php $mixedNames = $mixedNote->packageBillToAgencies()->pluck('name')->implode(', '); @endphp
        <div class="inv-mixed-row">
            <div>
                <strong>{{ $mixedNote->code }}</strong>
                <span class="inv-muted"> · {{ $mixedNames ?: ($mixedNote->agency?->listingAccountLabel() ?? 'Varios clientes') }}</span>
            </div>
            <form action="{{ route('salidas.hojas.split-clients', $mixedNote) }}" method="POST" onsubmit="return confirm('¿Separar {{ $mixedNote->code }}? Se creará una hoja nueva por cada cliente distinto.');">
                @csrf
                <button type="submit" class="inv-btn inv-btn-primary inv-btn-sm">Separar por cliente</button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endif
