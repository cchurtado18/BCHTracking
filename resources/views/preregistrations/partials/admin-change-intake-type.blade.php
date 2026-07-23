@php
    $returnToPackage = !empty($returnToPackage);
    $currentType = $preregistration->intake_type === 'DROP_OFF' ? 'DROP_OFF' : 'COURIER';
    $currentLabel = $currentType === 'COURIER' ? 'Courier' : 'Drop Off';
@endphp
@if(auth()->user()?->is_admin)
<div class="admin-intake-panel">
    <div class="admin-intake-panel__row">
        <div class="admin-intake-panel__text">
            <span class="admin-intake-panel__label">Tipo de ingreso</span>
            <p class="admin-intake-panel__desc">
                Actual: <strong>{{ $currentLabel }}</strong>. Solo administradores pueden cambiar entre Courier y Drop Off.
                @if($currentType === 'DROP_OFF' && $preregistration->receipt_note_id)
                <span class="admin-intake-panel__note">Al pasar a Courier se desvincula de la nota de recepción.</span>
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('preregistrations.admin.intake-type', $preregistration->id) }}" class="admin-intake-panel__form" onsubmit="return confirm('¿Confirmar cambio de tipo de ingreso?');">
            @csrf
            @if($returnToPackage)
            <input type="hidden" name="return_to" value="package">
            @endif
            <label for="admin_intake_type_{{ $preregistration->id }}" class="sr-only">Nuevo tipo de ingreso</label>
            <select name="intake_type" id="admin_intake_type_{{ $preregistration->id }}" class="admin-intake-panel__select" required>
                <option value="COURIER" {{ $currentType === 'COURIER' ? 'selected' : '' }}>Courier</option>
                <option value="DROP_OFF" {{ $currentType === 'DROP_OFF' ? 'selected' : '' }}>Drop Off</option>
            </select>
            <button type="submit" class="admin-intake-panel__btn">Cambiar</button>
        </form>
    </div>
    @error('intake_type')
    <p class="admin-intake-panel__err">{{ $message }}</p>
    @enderror
</div>

<style>
.admin-intake-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    padding: 1rem 1.25rem;
    margin-bottom: 0.75rem;
}
.admin-intake-panel__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.admin-intake-panel__text { flex: 1; min-width: 0; }
.admin-intake-panel__label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.35rem;
}
.admin-intake-panel__desc {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.45;
    color: #334155;
}
.admin-intake-panel__note { display: block; margin-top: 0.25rem; color: #92400e; font-size: 0.8125rem; }
.admin-intake-panel__form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}
.admin-intake-panel__select {
    min-width: 140px;
    padding: 0.45rem 0.65rem;
    font-size: 0.875rem;
    font-weight: 600;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #fff;
    color: #0f172a;
}
.admin-intake-panel__select:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2);
}
.admin-intake-panel__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    background: #0d9488;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
}
.admin-intake-panel__btn:hover { background: #0f766e; }
.admin-intake-panel__err {
    margin: 0.5rem 0 0;
    font-size: 0.8125rem;
    color: #b91c1c;
}
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
@endif
