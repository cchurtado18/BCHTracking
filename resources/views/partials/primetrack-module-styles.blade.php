<style>
.pt-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

.pt-hero {
    background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(10, 45, 111, 0.2);
}
.pt-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.pt-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.pt-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 56ch; }
.pt-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.pt-hero-btn {
    display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;
    background: #fff; color: #0A2D6F; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem;
    text-decoration: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.pt-hero-btn:hover { background: #F4F8FD; color: #0A2D6F; border-color: #fff; }
.pt-hero-btn-outline { background: transparent; color: rgba(255,255,255,0.95); border-color: rgba(255,255,255,0.6); }
.pt-hero-btn-outline:hover { background: rgba(255,255,255,0.15); color: #fff; }

.pt-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.pt-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.pt-alert-success { background: #F4F8FD; border: 1px solid #C5D4EB; color: #0A2D6F; }

.pt-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.pt-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.pt-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.pt-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.pt-stat-total { border-left: 4px solid #0A2D6F; }
.pt-stat-pending { border-left: 4px solid #d97706; }
.pt-stat-partial { border-left: 4px solid #2563eb; }
.pt-stat-paid { border-left: 4px solid #0A2D6F; }
.pt-stat-void { border-left: 4px solid #6b7280; }
.pt-stat-usd { border-left: 4px solid #1E4FA8; }

.pt-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.pt-card-header {
    padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;
}
.pt-card-header.pt-table-header { background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%); }
.pt-card-header.pt-table-header .pt-card-title { color: #fff; }
.pt-card-header.pt-table-header .pt-card-badge { color: rgba(255,255,255,0.9); }
.pt-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.pt-card-badge { font-size: 0.8125rem; font-weight: 500; color: #6b7280; }
.pt-card-body { padding: 1.25rem; }
.pt-card-footer {
    padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;
    font-size: 0.875rem; color: #6b7280;
}

.pt-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.pt-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.pt-field-search { min-width: 180px; }
.pt-field { min-width: 0; }
.pt-field-full { grid-column: 1 / -1; }
.pt-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.pt-input, .pt-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
    background: #fff; color: #111827;
}
.pt-input:focus, .pt-select:focus { outline: none; border-color: #0A2D6F; box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.pt-field-hint { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
.pt-field-error { font-size: 0.875rem; color: #dc2626; margin-top: 0.25rem; }
.pt-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.pt-fields-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem 1.25rem; }
.pt-form-actions { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; }
.pt-checkbox-row { display: flex; align-items: center; gap: 0.5rem; margin: 1rem 0; font-size: 0.875rem; color: #6b7280; }

.pt-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
    padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
    transition: background 0.15s, color 0.15s;
}
.pt-btn-primary { background: #0A2D6F; color: #fff; border-color: #0A2D6F; }
.pt-btn-primary:hover { background: #143A8C; color: #fff; }
.pt-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.pt-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.pt-btn-outline-primary { background: #fff; color: #0A2D6F; border-color: #0A2D6F; }
.pt-btn-outline-primary:hover { background: #E8EEF8; color: #0A2D6F; }
.pt-btn-outline-danger { background: #fff; color: #dc2626; border-color: #dc2626; }
.pt-btn-outline-danger:hover { background: #fef2f2; color: #b91c1c; }
.pt-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.pt-form-inline { display: inline; }

.pt-table-wrap { overflow-x: auto; }
.pt-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.pt-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.pt-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.pt-table tbody tr:hover { background: #f9fafb; }
.pt-code { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; }
.pt-muted { color: #6b7280; font-size: 0.875rem; }
.pt-hint { margin: 0.35rem 0 0; color: #6b7280; font-size: 0.8rem; }
.pt-text-danger { color: #b91c1c; }
.pt-alert-list { margin: 0; padding-left: 1.1rem; }
.pt-num { font-weight: 500; color: #374151; font-variant-numeric: tabular-nums; }
.pt-th-actions, .pt-actions { text-align: right; white-space: nowrap; }
.pt-actions .pt-btn, .pt-actions form { margin-left: 0.25rem; display: inline-flex; }
.pt-empty { text-align: center; padding: 3rem 1rem !important; }
.pt-empty-text { margin: 0 0 0.75rem; color: #6b7280; }

.pt-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.pt-badge-success { background: #E8EEF8; color: #0A2D6F; }
.pt-badge-warning { background: #fffbeb; color: #b45309; }
.pt-badge-info { background: #eff6ff; color: #1d4ed8; }
.pt-badge-danger { background: #fee2e2; color: #b91c1c; }
.pt-badge-muted { background: #f3f4f6; color: #6b7280; }

.pt-show-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
@media (min-width: 992px) { .pt-show-grid { grid-template-columns: 1fr 1fr; } }
.pt-dl { margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem 1.25rem; }
.pt-dl-row { min-width: 0; }
.pt-dt { font-size: 0.8125rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem; }
.pt-dd { margin: 0; font-size: 0.9375rem; color: #111827; }

.pt-pagination-info { font-weight: 500; }
.pt-pagination-links { display: flex; align-items: center; }
.pt-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.pt-pagination-links a, .pt-pagination-links span {
    display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem;
    border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none;
}
.pt-pagination-links a:hover { background: #f3f4f6; color: #0A2D6F; }
.pt-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.pt-pagination-links .active span { background: #0A2D6F; color: #fff; border-color: #0A2D6F; }

.pt-summary-line { margin: 0 0 0.35rem; font-size: 0.9375rem; color: #111827; }
.pt-summary-line strong { font-weight: 600; }
</style>
