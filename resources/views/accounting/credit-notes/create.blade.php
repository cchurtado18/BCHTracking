@extends('layouts.app')

@section('title', 'Nueva nota de crédito')

@section('content')
<div class="cb-page">
    <x-module-banner
        section="Contabilidad"
        current="Nota de crédito"
        title="Nueva nota de crédito"
        subtitle="El monto queda como saldo a favor del cliente para la próxima factura o cobro."
        back-href="{{ route('accounting.credit-notes.index') }}"
        back-label="Volver a notas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if($errors->any())
    <div class="cb-alert">
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.credit-notes.store') }}" class="cb-card">
        @csrf
        <div class="cb-card-head">
            <h2 class="cb-card-title">Datos de la nota</h2>
            <p class="cb-card-note">No reduce una factura de inmediato: aumenta el saldo a favor hasta que se aplique.</p>
        </div>
        <div class="cb-card-body">
            <div class="cb-field">
                <label class="cb-label" for="agency_id">Cliente</label>
                <select name="agency_id" id="agency_id" class="cb-input" required>
                    <option value="">Seleccione…</option>
                    @foreach($agencies as $agency)
                    <option value="{{ $agency->id }}" @selected((int) old('agency_id', $selectedAgencyId) === (int) $agency->id)>{{ $agency->code }} — {{ $agency->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cb-grid">
                <div class="cb-field">
                    <label class="cb-label" for="amount_usd">Monto (USD)</label>
                    <input type="number" name="amount_usd" id="amount_usd" class="cb-input" step="0.01" min="0.01" value="{{ old('amount_usd') }}" required>
                </div>
            </div>
            <div class="cb-field">
                <label class="cb-label" for="reason">Motivo</label>
                <textarea name="reason" id="reason" class="cb-input" rows="3" required minlength="5" maxlength="500">{{ old('reason') }}</textarea>
            </div>
        </div>
        <div class="cb-card-foot">
            <a href="{{ route('accounting.credit-notes.index') }}" class="cb-btn cb-btn-secondary">Cancelar</a>
            <button type="submit" class="cb-btn cb-btn-primary">Guardar nota de crédito</button>
        </div>
    </form>
</div>
<style>
.cb-page { --cb-navy:#0A2D6F; --cb-blue:#1E4FA8; --cb-line:#E8EEF8; padding:1.15rem 0 2.25rem; max-width:46rem; margin:0 auto; width:100%; }
.cb-alert { background:#FDECEC; border:1px solid #F6C9C9; color:#B03030; border-radius:0.7rem; padding:0.85rem 1rem; margin-bottom:1rem; font-size:0.85rem; font-weight:600; }
.cb-alert ul { margin:0; padding-left:1.1rem; }
.cb-card { background:#fff; border:1px solid var(--cb-line); border-radius:0.85rem; overflow:hidden; box-shadow:0 2px 8px rgba(15,23,42,0.04); }
.cb-card-head { padding:0.95rem 1.15rem 0.85rem; border-bottom:1px solid var(--cb-line); }
.cb-card-title { margin:0; font-size:0.98rem; font-weight:800; color:#0f172a; }
.cb-card-note { margin:0.22rem 0 0; font-size:0.78rem; color:#94a3b8; }
.cb-card-body { padding:1.15rem 1.2rem; }
.cb-card-foot { display:flex; justify-content:flex-end; gap:0.65rem; padding:0.9rem 1.15rem; border-top:1px solid var(--cb-line); background:#FAFCFF; }
.cb-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.95rem; }
.cb-field { display:flex; flex-direction:column; gap:0.32rem; margin-bottom:0.95rem; }
.cb-label { font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:#94a3b8; }
.cb-input { width:100%; box-sizing:border-box; padding:0.58rem 0.75rem; font-size:0.9rem; border:1px solid #D8DCE2; border-radius:0.55rem; }
.cb-input:focus { outline:none; border-color:var(--cb-blue); box-shadow:0 0 0 3px rgba(30,79,168,0.15); }
.cb-btn { display:inline-flex; align-items:center; gap:0.4rem; padding:0.58rem 1.05rem; font-size:0.875rem; font-weight:700; border-radius:0.6rem; border:1px solid transparent; text-decoration:none; cursor:pointer; }
.cb-btn-primary { background:var(--cb-navy); color:#fff; }
.cb-btn-secondary { background:#fff; color:#334155; border-color:#d1d9e6; }
@media (max-width:640px) { .cb-grid { grid-template-columns:1fr; } }
</style>
@endsection
