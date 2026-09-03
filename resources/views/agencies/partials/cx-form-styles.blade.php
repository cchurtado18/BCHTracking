<style>
.cx-page {
    --cx-navy: #0A2D6F; --cx-blue: #1E4FA8; --cx-green: #16794C; --cx-red: #D64545;
    --cx-line: #E8EEF8; --cx-border: #C5D4EB; --cx-soft: #F4F8FD; --cx-muted: #5E6168;
    padding: 1.15rem 0 2.25rem; max-width: 52rem; margin: 0 auto; width: 100%;
}
.cx-page--wide { max-width: 96rem; }
.cx-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.cx-alert-danger { background: #FDECEC; border: 1px solid #F6C9C9; color: #B03030; }
.cx-alert-list { margin: 0.4rem 0 0; padding-left: 1.15rem; }
.cx-steps { display: flex; flex-wrap: wrap; gap: 0.5rem; list-style: none; margin: 0 0 1.15rem; padding: 0; }
.cx-step { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; font-weight: 700; color: #94a3b8; }
.cx-step span {
    width: 1.45rem; height: 1.45rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
    background: #E8EEF8; color: #64748b; font-size: 0.72rem;
}
.cx-step.is-active { color: var(--cx-navy); }
.cx-step.is-active span { background: var(--cx-navy); color: #fff; }
.cx-step.is-done { color: var(--cx-green); }
.cx-step.is-done span { background: #16794C; color: #fff; }
.cx-card { background: #fff; border: 1px solid var(--cx-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; }
.cx-stack { display: flex; flex-direction: column; gap: 0.85rem; }
.cx-section-head { padding: 0.85rem 1.1rem; border-bottom: 1px solid var(--cx-line); display: flex; align-items: flex-start; gap: 0.7rem; }
.cx-section-title { margin: 0; font-size: 1rem; font-weight: 800; color: #0f172a; }
.cx-section-sub { margin: 0.2rem 0 0; font-size: 0.8rem; color: var(--cx-muted); line-height: 1.4; }
.cx-panel-num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.85rem; height: 1.85rem; border-radius: 0.55rem; flex-shrink: 0;
    background: linear-gradient(135deg, var(--cx-navy), var(--cx-blue));
    color: #fff; font-size: 0.78rem; font-weight: 800;
    box-shadow: 0 4px 10px rgba(10, 45, 111, 0.22);
}
.cx-card-body { padding: 1.1rem 1.15rem 1.2rem; }
.cx-hint { margin: 0 0 1rem; font-size: 0.875rem; color: var(--cx-muted); }
.cx-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem 1.1rem; }
.cx-page--wide .cx-form-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.cx-page--wide .cx-form-grid > .cx-field-wide { grid-column: 1 / -1; }
.cx-page--wide .cx-form-grid--pair,
.cx-form-grid--pair { grid-template-columns: minmax(12rem, 1fr) minmax(18rem, 2fr); }
.cx-page--wide .cx-card-body { padding: 1.2rem 1.35rem 1.35rem; }
.cx-field { display: flex; flex-direction: column; gap: 0.28rem; }
.cx-field[hidden], .cx-step-panel[hidden], .cx-form-grid[hidden], [data-only][hidden] { display: none !important; }
.cx-field-wide { grid-column: 1 / -1; }
.cx-label { font-size: 0.8rem; font-weight: 700; color: #334155; }
.cx-input, .cx-select { padding: 0.52rem 0.7rem; font-size: 0.85rem; border: 1px solid #D8DCE2; border-radius: 0.55rem; background: #fff; color: #0f172a; width: 100%; box-sizing: border-box; }
.cx-input:focus, .cx-select:focus { outline: none; border-color: var(--cx-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.cx-field-hint { font-size: 0.75rem; color: #94a3b8; }
.cx-field-error { font-size: 0.8rem; color: #B03030; margin: 0.35rem 0 0; }
.cx-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.55rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
}
.cx-btn-primary { background: var(--cx-navy); color: #fff; border-color: var(--cx-navy); }
.cx-btn-primary:hover { background: var(--cx-blue); color: #fff; }
.cx-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.cx-btn-secondary:hover { background: var(--cx-soft); color: var(--cx-navy); }
.cx-step-actions { display: flex; justify-content: flex-end; gap: 0.55rem; margin-top: 0.35rem; }
.cx-type-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.cx-type-card { display: block; cursor: pointer; }
.cx-type-card input { position: absolute; opacity: 0; pointer-events: none; }
.cx-type-card-body {
    display: flex; flex-direction: column; gap: 0.35rem; padding: 0.95rem 1rem;
    border: 2px solid #e5e7eb; border-radius: 0.75rem; background: #fff; min-height: 5.4rem;
}
.cx-type-card-body strong { font-size: 0.95rem; color: #0f172a; }
.cx-type-card-body span { font-size: 0.8rem; color: #64748b; line-height: 1.35; }
.cx-type-card:hover .cx-type-card-body { border-color: #C5D4EB; }
.cx-type-card.is-selected .cx-type-card-body { border-color: var(--cx-navy); background: var(--cx-soft); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.12); }
.cx-slo-note { margin: 1rem 0 0; padding: 0.75rem 1rem; border-radius: 0.5rem; background: var(--cx-soft); border: 1px solid #C5D4EB; color: var(--cx-navy); font-size: 0.875rem; }
.cx-code {
    display: inline-flex; align-items: center; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-weight: 800; font-size: 0.95rem; color: var(--cx-navy); letter-spacing: 0.04em;
    padding: 0.45rem 0.7rem; background: var(--cx-soft); border: 1px solid var(--cx-border); border-radius: 0.55rem;
}
.cx-logo-row { display: flex; align-items: center; gap: 0.85rem; }
.cx-logo-preview { height: 2.75rem; width: auto; max-width: 160px; object-fit: contain; }
.cx-check { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #334155; cursor: pointer; }
.cx-check input { width: 1rem; height: 1rem; }
@media (max-width: 1100px) {
    .cx-page--wide .cx-form-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 700px) {
    .cx-form-grid, .cx-type-cards, .cx-page--wide .cx-form-grid, .cx-form-grid--pair, .cx-page--wide .cx-form-grid--pair { grid-template-columns: 1fr; }
    .cx-field-wide { grid-column: auto; }
}
</style>
