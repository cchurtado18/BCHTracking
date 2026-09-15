<style>
.prealerts-page {
    --pt-navy: #0A2D6F;
    --pt-blue: #1E4FA8;
    --pt-soft: #F4F8FD;
    --pt-line: #E8EEF8;
    --pt-border: #C5D4EB;
    --pt-muted: #5E6168;
    padding: 1.25rem 0 2rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}

.prealerts-alert { padding: 0.9rem 1.1rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600; }
.prealerts-alert-success { background: #ecfdf3; border: 1px solid #86c9a4; color: #14532d; }
.prealerts-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.prealerts-alert-title { font-weight: 700; margin: 0 0 0.3rem; }
.prealerts-alert-list { margin: 0; padding-left: 1.2rem; font-weight: 500; }

.prealerts-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.85rem;
    margin-bottom: 1.15rem;
}
.prealerts-stat-card {
    background: #fff;
    border-radius: 0.75rem;
    padding: 1rem 1.1rem;
    border: 1px solid #e8ecf1;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    display: flex;
    align-items: center;
    gap: 0.85rem;
}
.prealerts-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.65rem;
    height: 2.65rem;
    flex-shrink: 0;
    border-radius: 0.65rem;
}
.prealerts-stat-body { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
.prealerts-stat-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #9ca3af; }
.prealerts-stat-value { font-size: 1.55rem; line-height: 1.1; font-weight: 800; color: #1f2937; }
.prealerts-stat-total .prealerts-stat-icon { background: #e8eef8; color: #0A2D6F; }
.prealerts-stat-pending .prealerts-stat-icon { background: #fef3c7; color: #b45309; }
.prealerts-stat-air .prealerts-stat-icon { background: #dbeafe; color: #1d4ed8; }
.prealerts-stat-sea .prealerts-stat-icon { background: #e0f2fe; color: #0369a1; }
.prealerts-stat-matched .prealerts-stat-icon { background: #dcfce7; color: #16a34a; }

.prealerts-filters {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.05rem 1.2rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.prealerts-filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem 0.75rem; }
.prealerts-field { display: flex; flex-direction: column; gap: 0.32rem; min-width: 0; }
.prealerts-field-search { flex: 1 1 220px; min-width: 180px; }
.prealerts-field-select { flex: 0 1 150px; min-width: 130px; }
.prealerts-label { font-size: 0.75rem; font-weight: 500; color: #6b7280; }
.prealerts-input, .prealerts-select {
    display: block; width: 100%; padding: 0.52rem 0.7rem; font-size: 0.875rem;
    border: 1px solid #d1d5db; border-radius: 0.45rem; background: #fff; color: #111827;
}
.prealerts-input:focus, .prealerts-select:focus {
    outline: none; border-color: #0A2D6F; box-shadow: 0 0 0 3px rgba(10, 45, 111, 0.12);
}
.prealerts-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-left: auto; }

.prealerts-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.55rem 1rem; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none; white-space: nowrap;
}
.prealerts-btn-primary { background: #0A2D6F; color: #fff; border-color: #0A2D6F; }
.prealerts-btn-primary:hover { background: #143A8C; border-color: #143A8C; color: #fff; }
.prealerts-btn-secondary { background: #fff; color: #4b5563; border-color: #d1d5db; }
.prealerts-btn-secondary:hover { background: #f9fafb; color: #111827; }
.prealerts-btn-danger { background: #fff; color: #B03030; border-color: #F6C9C9; }
.prealerts-btn-danger:hover { background: #FDECEC; color: #B03030; }

.prealerts-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1rem; }
.prealerts-count { font-size: 0.875rem; color: #6b7280; font-weight: 500; }

.prealerts-table-wrap {
    overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e5e7eb; background: #fff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06); margin-bottom: 1.5rem;
}
.prealerts-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.875rem; }
.prealerts-table thead th {
    text-align: left; padding: 0.7rem 0.875rem; font-weight: 700; font-size: 0.78rem;
    color: #fff; background: #0A2D6F; white-space: nowrap; border: none;
}
.prealerts-table thead th:first-child { border-radius: 0.75rem 0 0 0; }
.prealerts-table thead th:last-child { border-radius: 0 0.75rem 0 0; }
.prealerts-table tbody td {
    padding: 0.62rem 0.875rem; vertical-align: middle; color: #111827; background: #fff;
    border-bottom: 1px solid #eef2f7;
}
.prealerts-table tbody tr:last-child td { border-bottom: none; }
.prealerts-table tbody tr:nth-child(even) td { background: #f8fafc; }
.prealerts-table tbody tr:hover td { background: #F4F8FD; }
.prealerts-clickable-row { cursor: pointer; }
.prealerts-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 600; color: #1E4FA8; }
.prealerts-name { display: block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
.prealerts-agency { display: block; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #6b7280; font-size: 0.8125rem; }
.prealerts-date { font-size: 0.8125rem; color: #4b5563; white-space: nowrap; }
.prealerts-badge { display: inline-block; padding: 0.17rem 0.58rem; font-size: 0.735rem; font-weight: 600; border-radius: 9999px; white-space: nowrap; }
.prealerts-badge-air { background: #dbeafe; color: #1e40af; }
.prealerts-badge-sea { background: #ffedd5; color: #9a3412; }
.prealerts-badge-pending { background: #fef3c7; color: #92400e; }
.prealerts-badge-matched { background: #dcfce7; color: #166534; }
.prealerts-badge-cancelled { background: #f3f4f6; color: #4b5563; }
.prealerts-th-actions { text-align: right; width: 1%; }
.prealerts-actions { text-align: right; white-space: nowrap; }
.prealerts-action-group { display: inline-flex; align-items: center; justify-content: flex-end; gap: 0.15rem; }
.prealerts-form-inline { display: inline-flex; margin: 0; padding: 0; }
.prealerts-sr-only {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
}
.prealerts-icon-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.875rem; height: 1.875rem; border: none; border-radius: 0.375rem;
    background: transparent; color: #4b5563; text-decoration: none; cursor: pointer;
}
.prealerts-icon-btn:hover { background: #f3f4f6; color: #111827; }
.prealerts-icon-btn--view:hover { color: #1E4FA8; background: #eff6ff; }
.prealerts-icon-btn--danger:hover { color: #be123c; background: #fff1f2; }
.prealerts-empty { text-align: center; padding: 3rem 1rem !important; background: #fff !important; }
.prealerts-empty-text { margin: 0 0 0.75rem; color: #6b7280; }
.prealerts-footer {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: 0.75rem; padding: 0.75rem 0.15rem 0; font-size: 0.875rem; color: #6b7280;
}

.prealerts-create-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(17rem, 21rem);
    gap: 1.25rem;
    align-items: start;
}
.prealerts-card {
    background: #fff; border-radius: 1rem; border: 1px solid var(--pt-line);
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); overflow: hidden;
}
.prealerts-card-header { padding: 1rem 1.15rem 0.95rem; border-bottom: 1px solid var(--pt-line); background: linear-gradient(180deg, #fff 0%, #FBFCFE 100%); }
.prealerts-card-title { margin: 0; font-size: 1.2rem; font-weight: 800; color: #0f172a; display: inline-flex; align-items: center; gap: 0.55rem; }
.prealerts-card-title-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2.1rem; height: 2.1rem; border-radius: 0.55rem;
    background: var(--pt-soft); color: var(--pt-navy); border: 1px solid var(--pt-border);
}
.prealerts-card-desc { margin: 0.35rem 0 0; font-size: 0.875rem; color: var(--pt-muted); line-height: 1.45; }
.prealerts-card-body { padding: 1rem 1.15rem 1.15rem; }
.prealerts-form-panel {
    margin-bottom: 0.45rem; padding: 0.75rem 0.9rem 0.8rem;
    background: #fff; border: 1px solid var(--pt-line); border-radius: 0.75rem;
}
.prealerts-form-panel-head { display: flex; align-items: flex-start; gap: 0.6rem; margin-bottom: 0.65rem; }
.prealerts-form-panel-num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.85rem; height: 1.85rem; border-radius: 0.55rem;
    background: linear-gradient(135deg, var(--pt-navy), var(--pt-blue));
    color: #fff; font-size: 0.78rem; font-weight: 800; flex-shrink: 0;
}
.prealerts-form-panel-title { margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; }
.prealerts-form-panel-sub { margin: 0.2rem 0 0; font-size: 0.8rem; color: var(--pt-muted); }
.prealerts-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.7rem 0.85rem; }
.prealerts-form-field--full { grid-column: 1 / -1; }
.prealerts-field-label { display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.28rem; }
.prealerts-req { color: #D64545; font-weight: 800; }
.prealerts-opt { color: #94a3b8; font-weight: 500; font-size: 0.74rem; }
.prealerts-form-page .prealerts-input,
.prealerts-form-page .prealerts-select {
    padding: 0.72rem 0.9rem; font-size: 0.9375rem; border-radius: 0.65rem; border-color: #D8DCE2;
}
.prealerts-textarea { resize: vertical; min-height: 5.4rem; line-height: 1.45; }
.prealerts-input-upper { text-transform: uppercase; }
.prealerts-input-upper::placeholder { text-transform: none; }
.prealerts-hint { margin: 0.28rem 0 0; font-size: 0.76rem; color: #64748b; }
.prealerts-form-actions {
    margin-top: 1.15rem; padding: 1rem 1.15rem; border: 1px solid var(--pt-line);
    border-radius: 0.85rem; background: linear-gradient(180deg, #fff 0%, var(--pt-soft) 100%);
    display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.7rem;
}
.prealerts-aside { display: flex; flex-direction: column; gap: 1rem; position: sticky; top: 1rem; }
.prealerts-side-card {
    background: #fff; border: 1px solid var(--pt-line); border-radius: 1rem;
    padding: 1rem 1.1rem; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
}
.prealerts-side-title { margin: 0 0 0.65rem; font-size: 0.95rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 0.45rem; }
.prealerts-side-list { margin: 0; padding-left: 1.1rem; color: #475569; font-size: 0.85rem; line-height: 1.5; }
.prealerts-side-list li + li { margin-top: 0.4rem; }

.prealerts-metrics {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem; margin-bottom: 1.15rem;
}
.prealerts-metric {
    background: #fff; border: 1px solid #e8ecf1; border-radius: 0.75rem;
    padding: 0.95rem 1.05rem; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}
.prealerts-metric-label { display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 0.3rem; }
.prealerts-metric-value { font-size: 1.05rem; font-weight: 800; color: #0f172a; word-break: break-word; }
.prealerts-metric-accent { border-left: 4px solid #0A2D6F; }
.prealerts-dl { margin: 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
.prealerts-dt { margin: 0 0 0.2rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
.prealerts-dd { margin: 0; font-size: 0.95rem; font-weight: 650; color: #0f172a; }
.prealerts-dl-full { grid-column: 1 / -1; }

.prealerts-flash-ok { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600; background: #ecfdf3; border: 1px solid #86c9a4; color: #14532d; }
.prealerts-flash-err { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.prealerts-show-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; align-items: start; }
@media (min-width: 1024px) { .prealerts-show-layout { grid-template-columns: minmax(0, 1.6fr) minmax(300px, 0.85fr); } }
.prealerts-show-main, .prealerts-show-side { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
.prealerts-data-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.85rem; overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
.prealerts-data-head {
    padding: 0.85rem 1.15rem; border-bottom: 1px solid #eef2f7;
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;
}
.prealerts-data-title { margin: 0; font-size: 0.95rem; font-weight: 750; color: #0f172a; display: inline-flex; align-items: center; gap: 0.5rem; }
.prealerts-data-icon { width: 1rem; height: 1rem; color: #0A2D6F; flex-shrink: 0; }
.prealerts-data-body { padding: 1.1rem 1.15rem 1.2rem; }
.prealerts-fields { display: grid; grid-template-columns: 1fr; gap: 1rem 1.5rem; }
@media (min-width: 640px) { .prealerts-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.prealerts-data-field { display: flex; flex-direction: column; gap: 0.22rem; min-width: 0; }
.prealerts-data-field--full { grid-column: 1 / -1; }
.prealerts-data-label { font-size: 0.66rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.07em; text-transform: uppercase; }
.prealerts-data-value { font-size: 0.92rem; font-weight: 650; color: #0f172a; word-break: break-word; }
.prealerts-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: 0.02em; color: #0A2D6F; }
.prealerts-chip { display: inline-flex; align-items: center; padding: 0.16rem 0.5rem; border-radius: 999px; font-size: 0.66rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; border: 1px solid transparent; }
.prealerts-chip-air { background: #E8EEF8; color: #0A2D6F; border-color: #C5D4EB; }
.prealerts-chip-sea { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.prealerts-side-link {
    display: block; margin-top: 0.9rem; padding-top: 0.8rem; border-top: 1px solid #f1f5f9;
    color: #0A2D6F; font-weight: 700; font-size: 0.84rem; text-decoration: none; text-align: center;
}
.prealerts-side-link:hover { color: #0A2D6F; text-decoration: underline; }
.prealerts-htl { list-style: none; margin: 0; padding: 0.35rem 0 0.1rem; display: flex; }
.prealerts-htl-step {
    flex: 1; min-width: 0; position: relative;
    display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.5rem;
}
.prealerts-htl-step::before {
    content: ''; position: absolute; top: 0.95rem; right: 50%; width: 100%; height: 2px;
    background: #e2e8f0; z-index: 0;
}
.prealerts-htl-step:first-child::before { display: none; }
.prealerts-htl-step.is-done::before { background: #1E4FA8; }
.prealerts-htl-icon {
    position: relative; z-index: 1; width: 1.9rem; height: 1.9rem; border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
    background: #fff; border: 2px solid #e2e8f0; color: #cbd5e1;
}
.prealerts-htl-icon svg { width: 0.9rem; height: 0.9rem; }
.prealerts-htl-step.is-done .prealerts-htl-icon { background: #1E4FA8; border-color: #C5D4EB; color: #fff; }
.prealerts-htl-step.is-current .prealerts-htl-icon {
    background: #E8EEF8; border-color: #1E4FA8; color: #0A2D6F;
    box-shadow: 0 0 0 4px rgba(30, 79, 168, 0.16);
}
.prealerts-htl-title { display: block; font-size: 0.8rem; font-weight: 700; color: #b6c2d1; line-height: 1.2; }
.prealerts-htl-step.is-done .prealerts-htl-title { color: #0f172a; }
.prealerts-htl-step.is-current .prealerts-htl-title { color: #0A2D6F; }
.prealerts-htl-meta { display: block; font-size: 0.68rem; font-weight: 600; color: #b6c2d1; margin-top: -0.2rem; word-break: break-word; padding: 0 0.25rem; }
.prealerts-htl-step.is-done .prealerts-htl-meta { color: #64748b; }
.prealerts-htl-step.is-current .prealerts-htl-meta { color: #0A2D6F; }

@media (max-width: 1100px) {
    .prealerts-stats, .prealerts-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .prealerts-create-layout { grid-template-columns: 1fr; }
    .prealerts-aside { position: static; }
}
@media (max-width: 640px) {
    .prealerts-stats, .prealerts-metrics, .prealerts-form-grid, .prealerts-dl { grid-template-columns: 1fr; }
    .prealerts-filters-actions { width: 100%; margin-left: 0; }
}
</style>
