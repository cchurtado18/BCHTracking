<style>
    .page-banner { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25); }
    .page-banner-title { color: #fff !important; }
    .page-banner-subtitle { color: rgba(255,255,255,0.9) !important; }
    .btn-banner { background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.5); padding: 0.5rem 1rem; font-weight: 600; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); text-decoration: none; }
    .btn-banner:hover { background: #f0fdfa; color: #0d9488; }
    .page-banner-create { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25); border-radius: 0.75rem; }
    .btn-banner-create { background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.35); padding: 0.5rem 1rem; font-weight: 600; border-radius: 0.5rem; font-size: 0.875rem; text-decoration: none; }
    .btn-banner-create:hover { background: #f0fdfa; color: #0d9488; }
    .card-create { background: #fff; border: 1px solid #dee2e6; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
    .card-create-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); color: #fff; padding: 0.75rem 1.25rem; font-weight: 600; font-size: 1rem; }
    .card-create-body { padding: 1.5rem 1.75rem; }
    .card { border-radius: 0.5rem; background: #fff; border: 1px solid #dee2e6; }
    .card-body { padding: 1rem 1.5rem; }
    .card-footer { border-top: 1px solid #dee2e6; }
    .table-header-teal { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); color: #fff; }
    .table-header-teal th { border-bottom-color: rgba(255,255,255,0.2); color: #fff; font-weight: 600; }
    .form-label { margin-bottom: 0.25rem; color: #6c757d; font-weight: 500; }
    .form-control, .form-select { display: block; width: 100%; padding: 0.375rem 0.75rem; font-size: 0.875rem; border: 1px solid #ced4da; border-radius: 0.375rem; background: #fff; }
    .form-control:focus { outline: 0; border-color: #0d9488; box-shadow: 0 0 0 0.2rem rgba(13, 148, 136, 0.25); }
    .form-control.is-invalid { border-color: #dc3545; }
    .invalid-feedback { color: #dc3545; font-size: 0.875em; margin-top: 0.25rem; }
    .form-control-sm, .form-select-sm { padding: 0.25rem 0.5rem; font-size: 0.8125rem; }
    .form-check { display: flex; align-items: center; gap: 0.5rem; }
    .form-check-input { width: 1.25rem; height: 1.25rem; }
    .form-check-label { margin-bottom: 0; }
    .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.375rem 0.75rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.375rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
    .btn-primary { color: #fff; background: #0d6efd; border-color: #0d6efd; }
    .btn-outline-primary { color: #0d6efd; background: transparent; border-color: #0d6efd; }
    .btn-outline-secondary { color: #6c757d; background: transparent; border-color: #6c757d; }
    .btn-outline-danger { color: #dc3545; background: transparent; border-color: #dc3545; }
    .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8125rem; }
    .btn-group { display: inline-flex; flex-wrap: nowrap; gap: 0.25rem; }
    .table { width: 100%; margin-bottom: 0; border-collapse: collapse; }
    .table th, .table td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid #dee2e6; text-align: left; }
    .table th { font-weight: 600; }
    .table-striped tbody tr:nth-of-type(odd) { background: #f8f9fa; }
    .table-hover tbody tr:hover { background: #e9ecef; }
    .table-responsive { overflow-x: auto; }
    .badge { display: inline-block; padding: 0.35em 0.65em; font-size: 0.75em; font-weight: 600; line-height: 1; border-radius: 0.375rem; }
    .bg-primary { background: #0d6efd; color: #fff; }
    .bg-secondary { background: #6c757d; color: #fff; }
    .bg-danger { background: #dc3545; color: #fff; }
    .text-body-secondary { color: #6c757d; }
    .text-primary { color: #0d6efd; }
    .text-danger { color: #dc3545; }
    .text-warning { color: #856404; }
    .small { font-size: 0.875em; }
    .fw-medium { font-weight: 500; }
    .fw-semibold { font-weight: 600; }
    .rounded-3 { border-radius: 0.75rem; }
    .row { display: flex; flex-wrap: wrap; margin: -0.5rem; }
    .row > * { padding: 0.5rem; }
    .col-12 { flex: 0 0 100%; max-width: 100%; }
    @media (min-width: 768px) { .col-md-6 { flex: 0 0 50%; max-width: 50%; } }
    @media (min-width: 992px) { .col-lg-4 { flex: 0 0 33.333%; max-width: 33.333%; } .col-lg { flex: 0 0 auto; flex-grow: 1; max-width: 100%; } }
    .d-flex { display: flex; }
    .d-inline { display: inline; }
    .d-inline-flex { display: inline-flex; }
    .flex-wrap { flex-wrap: wrap; }
    .align-items-end { align-items: flex-end; }
    .align-items-center { align-items: center; }
    .justify-content-between { justify-content: space-between; }
    .gap-2 { gap: 0.5rem; }
    .gap-3 { gap: 0.75rem; }
    .mb-3 { margin-bottom: 1rem; }
    .mb-4 { margin-bottom: 1.5rem; }
    .py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
    .py-5 { padding-top: 3rem; padding-bottom: 3rem; }
    @media (min-width: 576px) { .flex-sm-row { flex-direction: row; } }
</style>
