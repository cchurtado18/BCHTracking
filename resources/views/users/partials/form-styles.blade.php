<style>
.cx-page {
    --cx-navy: #0A2D6F; --cx-blue: #1E4FA8; --cx-green: #16794C; --cx-red: #D64545;
    --cx-line: #E8EEF8; --cx-border: #C5D4EB; --cx-soft: #F4F8FD; --cx-muted: #5E6168;
    padding: 1.15rem 0 2.25rem; max-width: 46rem; margin: 0 auto; width: 100%;
}
.cx-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.cx-alert-danger { background: #FDECEC; border: 1px solid #F6C9C9; color: #B03030; }
.cx-alert-danger strong { display: block; margin-bottom: 0.25rem; }
.cx-alert-list { margin: 0.35rem 0 0; padding-left: 1.15rem; }
.cx-card { background: #fff; border: 1px solid var(--cx-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; }
.cx-section-head { padding: 0.9rem 1.15rem 0.8rem; border-bottom: 1px solid var(--cx-line); }
.cx-section-title { margin: 0; font-size: 0.98rem; font-weight: 800; color: #0f172a; }
.cx-section-note { margin: 0.22rem 0 0; font-size: 0.78rem; color: #94a3b8; }
.cx-card-body { padding: 1.15rem 1.2rem; }
.cx-card-foot { display: flex; justify-content: flex-end; gap: 0.65rem; padding: 0.9rem 1.15rem; border-top: 1px solid var(--cx-line); background: #FAFCFF; }
.cx-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.95rem; }
.cx-field { display: flex; flex-direction: column; gap: 0.32rem; }
.cx-label { font-size: 0.8rem; font-weight: 700; color: #334155; }
.cx-req { color: var(--cx-red); }
.cx-input { width: 100%; box-sizing: border-box; padding: 0.58rem 0.75rem; font-size: 0.9rem; border: 1px solid #D8DCE2; border-radius: 0.55rem; background: #fff; color: #0f172a; }
.cx-input:focus { outline: none; border-color: var(--cx-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.cx-input.is-invalid { border-color: #F6C9C9; }
.cx-field-hint { margin: 0; font-size: 0.75rem; color: #94a3b8; }
.cx-field-error { margin: 0; font-size: 0.8rem; color: #B03030; }
.cx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.6rem; border: 1px solid transparent; text-decoration: none; cursor: pointer; }
.cx-btn-primary { background: var(--cx-navy); color: #fff; border-color: var(--cx-navy); }
.cx-btn-primary:hover { background: var(--cx-blue); border-color: var(--cx-blue); color: #fff; }
.cx-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.cx-btn-secondary:hover { background: var(--cx-soft); color: var(--cx-navy); }
.cx-type-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.cx-type-card { display: block; cursor: pointer; }
.cx-type-card input { position: absolute; opacity: 0; pointer-events: none; }
.cx-type-card-body {
    display: flex; flex-direction: column; gap: 0.35rem; padding: 0.95rem 1rem;
    border: 2px solid #e5e7eb; border-radius: 0.75rem; background: #fff; min-height: 5.4rem;
}
.cx-type-card-body strong { font-size: 0.95rem; color: #0f172a; }
.cx-type-card-body span { font-size: 0.8rem; color: #64748b; line-height: 1.35; }
.cx-type-card:hover .cx-type-card-body { border-color: var(--cx-border); }
.cx-type-card.is-selected .cx-type-card-body { border-color: var(--cx-navy); background: var(--cx-soft); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.12); }
.cx-type-card.is-locked { cursor: default; opacity: 0.72; }
.cx-lock-note { margin: 0.85rem 0 0; padding: 0.7rem 0.9rem; border-radius: 0.55rem; background: #FFF8E8; border: 1px solid #F3D58A; color: #8A5A00; font-size: 0.8125rem; font-weight: 600; }
@media (max-width: 700px) {
    .cx-form-grid, .cx-type-cards { grid-template-columns: 1fr; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cx-type-cards').forEach(function (group) {
        var cards = group.querySelectorAll('.cx-type-card');
        function sync() {
            cards.forEach(function (card) {
                var input = card.querySelector('input');
                card.classList.toggle('is-selected', !!(input && input.checked));
            });
        }
        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                var input = card.querySelector('input');
                if (!input || input.disabled) return;
                input.checked = true;
                sync();
            });
        });
        sync();
    });
});
</script>
