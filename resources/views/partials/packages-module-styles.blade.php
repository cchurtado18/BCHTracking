<style>
.packages-page { padding: 1.25rem 0 2rem; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.packages-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0f766e 45%, #128176 100%);
    border-radius: 0.875rem;
    padding: 1.125rem 1.25rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 8px 24px rgba(15, 118, 110, 0.16);
}
.packages-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.packages-hero-title { margin: 0; font-size: 1.85rem; font-weight: 600; color: #fff; letter-spacing: -0.02em; text-shadow: 0 1px 0 rgba(0, 0, 0, 0.08); }
.packages-hero-subtitle { margin: 0.28rem 0 0; font-size: 0.925rem; color: rgba(236, 253, 245, 0.96); max-width: 54ch; }
.packages-hero-btn {
    display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.52rem 0.96rem; font-size: 0.875rem; font-weight: 600;
    color: #fff; background: #059669; border: 1px solid rgba(15, 118, 110, 0.45); border-radius: 0.625rem; text-decoration: none;
    box-shadow: 0 2px 8px rgba(2, 44, 34, 0.2); transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.packages-hero-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(2, 44, 34, 0.24); color: #fff; background: #047857; border-color: #047857; }

/* Stats */
.packages-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.95rem; margin-bottom: 1.75rem; }
.packages-stat-card {
    border-radius: 0.75rem; padding: 1rem 1rem 1.1rem; border: 1px solid #e6ecf3;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06); display: flex; flex-direction: column; gap: 0.28rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.packages-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1); }
.packages-stat-icon { display: inline-flex; align-items: center; justify-content: center; width: 1.85rem; height: 1.85rem; border-radius: 9999px; background: rgba(255, 255, 255, 0.62); }
.packages-stat-label { font-size: 0.73rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
.packages-stat-value { font-size: 1.72rem; line-height: 1.1; font-weight: 700; color: #0f172a; }
.packages-stat-total { background: linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%); }
.packages-stat-air { background: linear-gradient(180deg, #eff6ff 0%, #f8fbff 100%); }
.packages-stat-sea { background: linear-gradient(180deg, #ecfeff 0%, #f0fdfa 100%); }
.packages-stat-ready { background: linear-gradient(180deg, #f0fdf4 0%, #f7fee7 100%); }
.packages-stat-delivered { background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); }

/* Card */
.packages-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e7edf4; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05); margin-bottom: 1.75rem; overflow: hidden; }
.packages-card-header { padding: 1rem 1.2rem; border-bottom: 1px solid #edf2f7; background: #fbfcfe; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.packages-card-title { margin: 0; font-size: 0.94rem; font-weight: 600; color: #334155; }
.packages-card-badge { font-size: 0.8125rem; color: #64748b; font-weight: 500; }
.packages-card-body { padding: 1.3rem 1.2rem; }
.packages-card-footer { padding: 0.85rem 1.2rem; border-top: 1px solid #edf2f7; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #64748b; }

/* Filters */
.packages-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.packages-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
.packages-field-search { grid-column: 1 / -1; }
@media (min-width: 640px) { .packages-field-search { grid-column: span 2; max-width: 340px; } }
.packages-label { display: block; font-size: 0.74rem; font-weight: 500; color: #64748b; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em; }
.packages-input, .packages-select {
    display: block; width: 100%; padding: 0.58rem 0.82rem; font-size: 0.875rem; border: 1px solid #dbe3ec; border-radius: 0.625rem;
    background: #fff; color: #0f172a; transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.packages-input:hover, .packages-select:hover { border-color: #c7d2e0; background: #fcfdff; }
.packages-input:focus, .packages-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14); }
.packages-filters-actions { display: flex; flex-wrap: wrap; gap: 0.72rem; align-items: center; margin-top: 0.15rem; }

/* Buttons */
.packages-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.56rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.625rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; }
.packages-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25); }
.packages-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; box-shadow: 0 8px 16px rgba(15, 118, 110, 0.25); }
.packages-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.packages-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.packages-btn-ghost { background: transparent; color: #64748b; border-color: transparent; padding-left: 0.55rem; padding-right: 0.55rem; }
.packages-btn-ghost:hover { background: #f1f5f9; color: #334155; border-color: #e2e8f0; }
.packages-btn-outline { background: #fff; color: #6b7280; border-color: #d1d5db; }
.packages-btn-outline:hover { background: #f9fafb; color: #374151; }
.packages-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.packages-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.packages-btn-success { background: #059669; color: #fff; border-color: #059669; }
.packages-btn-success:hover { background: #047857; color: #fff; }
.packages-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

.packages-tracking {
    display: block;
    max-width: 11.5rem;
    font-size: 0.8125rem;
    color: #334155;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.packages-date {
    font-size: 0.8125rem;
    color: #4b5563;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.packages-action-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.34rem;
    flex-wrap: nowrap;
}
.packages-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.05rem;
    height: 2.05rem;
    padding: 0;
    border: 1px solid #dbe3ec;
    border-radius: 9999px;
    background: #fff;
    color: #475569;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}
.packages-icon-btn:hover {
    transform: translateY(-1px);
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.1);
}
.packages-icon-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.22); border-color: #0d9488; }
.packages-icon-btn--view:hover { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
.packages-icon-btn--accent {
    border-color: rgba(13, 148, 136, 0.35);
    color: #0f766e;
    background: #f0fdfa;
}
.packages-icon-btn--accent:hover {
    background: #ccfbf1;
    border-color: #0d9488;
    color: #0f766e;
}
.packages-icon-btn--success {
    border-color: rgba(5, 150, 105, 0.28);
    color: #047857;
    background: #ecfdf5;
}
.packages-icon-btn--success:hover {
    background: #dcfce7;
    border-color: #22c55e;
    color: #166534;
}
.packages-sr-only {
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

/* Table */
.packages-table-header { background: #fbfcfe; }
.packages-table-header .packages-card-title { color: #0f172a; }
.packages-table-header .packages-card-badge { color: #64748b; }
.packages-table-wrap { overflow-x: auto; }
.packages-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.packages-table th { text-align: left; padding: 0.82rem 1rem; font-weight: 500; font-size: 0.7rem; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.packages-table td { padding: 0.96rem 1rem; border-bottom: 1px solid #e9eef5; vertical-align: middle; color: #334155; }
.packages-table tbody tr:nth-child(even) { background: #fbfdff; }
.packages-table tbody tr:hover { background: #f0f9ff; }
.packages-clickable-row { cursor: pointer; transition: background 0.2s ease; }
.packages-code { font-family: ui-monospace, monospace; font-weight: 500; color: #0f172a; }
.packages-name { display: block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.packages-agency { display: block; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #64748b; }
.packages-weight { color: #334155; }
.packages-uom { font-size: 0.75rem; color: #94a3b8; }
.packages-th-actions { text-align: right; width: 1%; }
.packages-actions { text-align: right; white-space: nowrap; vertical-align: middle; }
.packages-actions .packages-btn { margin-left: 0.25rem; }
.packages-badge { display: inline-block; padding: 0.26rem 0.62rem; font-size: 0.74rem; font-weight: 600; border-radius: 9999px; }
.packages-badge-air { background: #dbeafe; color: #1e40af; }
.packages-badge-sea { background: #dcfce7; color: #166534; }
.packages-status { }
.packages-status.status-info { background: #e0f2fe; color: #075985; }
.packages-status.status-warning { background: #fef3c7; color: #92400e; }
.packages-status.status-primary { background: #e0f2fe; color: #0369a1; }
.packages-status.status-success { background: #dcfce7; color: #166534; }
.packages-status.status-delivered { background: #e2e8f0; color: #334155; }
.packages-status.status-default { background: #f1f5f9; color: #475569; }
.packages-empty { text-align: center; padding: 3rem 1rem !important; }
.packages-empty-text { margin: 0 0 0.75rem; color: #64748b; }
.packages-pagination-info { font-weight: 500; }
.packages-pagination-links { display: flex; align-items: center; }
.packages-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.packages-pagination-links a, .packages-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.45rem; border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none; }
.packages-pagination-links a:hover { background: #f8fafc; color: #0d9488; }
.packages-pagination-links .disabled span { background: #f8fafc; color: #94a3b8; }
.packages-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }

/* Fichaje — botones Iniciar / Salida / Break */
.packages-time-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 7.5rem; padding: 0.56rem 1.15rem; font-size: 0.875rem; font-weight: 600; border-radius: 0.625rem; border: 1px solid transparent; cursor: pointer; transition: background 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease; }
.packages-time-btn:disabled { cursor: not-allowed; opacity: 0.45; }
.packages-time-btn--iniciar { background: #16a34a; color: #fff; border-color: #16a34a; box-shadow: 0 2px 8px rgba(22, 163, 74, 0.25); }
.packages-time-btn--iniciar:hover:not(:disabled) { background: #15803d; border-color: #15803d; }
.packages-time-btn--salida { background: #dc2626; color: #fff; border-color: #dc2626; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.22); }
.packages-time-btn--salida:hover:not(:disabled) { background: #b91c1c; border-color: #b91c1c; }
.packages-time-btn--break { background: #2563eb; color: #fff; border-color: #2563eb; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.22); }
.packages-time-btn--break:hover:not(:disabled) { background: #1d4ed8; border-color: #1d4ed8; }
.packages-time-btn--break-end { background: #1d4ed8; color: #fff; border-color: #1d4ed8; box-shadow: 0 2px 8px rgba(29, 78, 216, 0.22); }
.packages-time-btn--break-end:hover:not(:disabled) { background: #1e40af; border-color: #1e40af; }
.packages-time-status { margin: 0 0 1rem; font-size: 0.9rem; color: #475569; line-height: 1.55; }
.packages-time-status strong { color: #0f172a; }
.packages-time-status .packages-code { font-size: 0.875rem; }

.packages-alert-error {
    margin-bottom: 1.75rem;
    padding: 0.85rem 1.1rem;
    border-radius: 0.75rem;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
    font-size: 0.875rem;
}
</style>
