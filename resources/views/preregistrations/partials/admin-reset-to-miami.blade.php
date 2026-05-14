@php
    use App\Services\PreregistrationAdminResetService;
    $canAdminResetToMiami = auth()->user()?->is_admin && PreregistrationAdminResetService::canResetToMiami($preregistration);
    $adminResetBlockReason = auth()->user()?->is_admin ? PreregistrationAdminResetService::resetBlockReason($preregistration) : null;
    $dialogId = 'admin-return-dialog-' . $preregistration->id;
    $adminReturnErrors = $errors->has('admin_reset_reason') || $errors->has('admin_reset_confirm');
@endphp
@if(auth()->user()?->is_admin)
<div class="admin-return-panel">
    <div class="admin-return-panel__row">
        <div class="admin-return-panel__text">
            <span class="admin-return-panel__label">Administración</span>
            <p class="admin-return-panel__desc">
                @if($canAdminResetToMiami)
                    Devuelve el paquete a <strong>Recibido en Miami</strong>, quita el vínculo con el saco y limpia tránsito / Nicaragua / listo en sistema. Queda en <a href="{{ route('audit.index', ['action' => 'admin_reset_to_miami']) }}" class="admin-return-panel__link">Auditoría</a>.
                @else
                    <span class="admin-return-panel__muted">{{ $adminResetBlockReason ?? 'No aplica en el estado actual.' }}</span>
                @endif
            </p>
        </div>
        @if($canAdminResetToMiami)
        <button type="button" class="admin-return-panel__trigger" id="admin-return-open-{{ $preregistration->id }}">
            Devolver paquete
        </button>
        @endif
    </div>
</div>

@if($canAdminResetToMiami)
<dialog class="admin-return-dialog" id="{{ $dialogId }}" aria-labelledby="admin-return-title-{{ $preregistration->id }}">
    <div class="admin-return-dialog__inner">
        <header class="admin-return-dialog__head">
            <h2 class="admin-return-dialog__title" id="admin-return-title-{{ $preregistration->id }}">Devolver paquete a Miami</h2>
            <button type="button" class="admin-return-dialog__close" aria-label="Cerrar" data-admin-return-close>&times;</button>
        </header>
        <p class="admin-return-dialog__lead">Indique el motivo y confirme. Esta acción desvincula el paquete del saco actual y restablece el flujo desde Miami.</p>
        <form method="POST" action="{{ route('preregistrations.admin.reset-to-miami', $preregistration->id) }}" class="admin-return-dialog__form">
            @csrf
            @if(!empty($returnToPackage))
            <input type="hidden" name="return_to" value="package">
            @endif
            <div class="admin-return-dialog__field">
                <label for="admin_reset_reason_{{ $preregistration->id }}" class="admin-return-dialog__lbl">Motivo del cambio <span class="admin-return-dialog__req">(mín. 15 caracteres)</span></label>
                <textarea name="admin_reset_reason" id="admin_reset_reason_{{ $preregistration->id }}" rows="4" class="admin-return-dialog__textarea" required minlength="15" maxlength="2000" placeholder="Ej.: Paquete marítimo escaneado en saco aéreo por error; se devuelve a Miami para reenvío correcto.">{{ old('admin_reset_reason') }}</textarea>
                @error('admin_reset_reason')
                <p class="admin-return-dialog__err">{{ $message }}</p>
                @enderror
            </div>
            <div class="admin-return-dialog__field">
                <label class="admin-return-dialog__check">
                    <input type="hidden" name="admin_reset_confirm" value="0">
                    <input type="checkbox" name="admin_reset_confirm" value="1" {{ old('admin_reset_confirm') == '1' ? 'checked' : '' }} required>
                    <span>Confirmo esta corrección y entiendo que el paquete saldrá del saco actual.</span>
                </label>
                @error('admin_reset_confirm')
                <p class="admin-return-dialog__err">{{ $message }}</p>
                @enderror
            </div>
            <footer class="admin-return-dialog__actions">
                <button type="button" class="admin-return-dialog__btn admin-return-dialog__btn--secondary" data-admin-return-close>Cancelar</button>
                <button type="submit" class="admin-return-dialog__btn admin-return-dialog__btn--primary">Confirmar devolución a Miami</button>
            </footer>
        </form>
    </div>
</dialog>

<style>
.admin-return-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    padding: 1rem 1.25rem;
    margin-bottom: 0;
}
.admin-return-panel__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.admin-return-panel__text { flex: 1; min-width: 0; }
.admin-return-panel__label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.35rem;
}
.admin-return-panel__desc {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.45;
    color: #334155;
}
.admin-return-panel__muted { color: #64748b; }
.admin-return-panel__link { color: #0d9488; font-weight: 600; text-decoration: none; }
.admin-return-panel__link:hover { text-decoration: underline; }
.admin-return-panel__trigger {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1.125rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 55%, #14b8a6 100%);
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
}
.admin-return-panel__trigger:hover {
    filter: brightness(1.05);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
}
.admin-return-dialog {
    border: none;
    border-radius: 0.75rem;
    padding: 0;
    max-width: calc(100vw - 2rem);
    width: 32rem;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
}
.admin-return-dialog::backdrop {
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
}
.admin-return-dialog__inner {
    padding: 0;
    overflow: hidden;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    background: #fff;
}
.admin-return-dialog__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.125rem 1.25rem;
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
}
.admin-return-dialog__title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.3;
}
.admin-return-dialog__close {
    flex-shrink: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 0.375rem;
    background: rgba(255,255,255,0.2);
    color: #fff;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
}
.admin-return-dialog__close:hover { background: rgba(255,255,255,0.3); }
.admin-return-dialog__lead {
    margin: 0;
    padding: 1rem 1.25rem 0;
    font-size: 0.875rem;
    line-height: 1.5;
    color: #475569;
}
.admin-return-dialog__form { padding: 1rem 1.25rem 1.25rem; }
.admin-return-dialog__field { margin-bottom: 1rem; }
.admin-return-dialog__field:last-of-type { margin-bottom: 1.25rem; }
.admin-return-dialog__lbl {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.4rem;
}
.admin-return-dialog__req { font-weight: 500; color: #64748b; }
.admin-return-dialog__textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 0.65rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    resize: vertical;
    min-height: 5rem;
    font-family: inherit;
}
.admin-return-dialog__textarea:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2);
}
.admin-return-dialog__check {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    font-size: 0.8125rem;
    color: #334155;
    cursor: pointer;
    line-height: 1.4;
}
.admin-return-dialog__check input { margin-top: 0.15rem; flex-shrink: 0; }
.admin-return-dialog__err {
    margin: 0.35rem 0 0;
    font-size: 0.8125rem;
    color: #b91c1c;
}
.admin-return-dialog__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 0.25rem;
    border-top: 1px solid #f1f5f9;
    margin: 0 -1.25rem -1.25rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
}
.admin-return-dialog__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    cursor: pointer;
    border: 1px solid transparent;
}
.admin-return-dialog__btn--secondary {
    background: #fff;
    color: #475569;
    border-color: #cbd5e1;
}
.admin-return-dialog__btn--secondary:hover { background: #f1f5f9; }
.admin-return-dialog__btn--primary {
    background: #0d9488;
    color: #fff;
    border-color: #0d9488;
}
.admin-return-dialog__btn--primary:hover { background: #0f766e; }
</style>

<script>
(function () {
    var id = @json($dialogId);
    var dlg = document.getElementById(id);
    var openBtn = document.getElementById('admin-return-open-{{ $preregistration->id }}');
    if (!dlg || !openBtn) return;
    function closeDlg() { try { dlg.close(); } catch (e) {} }
    openBtn.addEventListener('click', function () { dlg.showModal(); });
    dlg.querySelectorAll('[data-admin-return-close]').forEach(function (el) {
        el.addEventListener('click', closeDlg);
    });
    dlg.addEventListener('click', function (e) {
        if (e.target === dlg) closeDlg();
    });
    @if($adminReturnErrors)
    dlg.showModal();
    @endif
})();
</script>
@endif
@endif
