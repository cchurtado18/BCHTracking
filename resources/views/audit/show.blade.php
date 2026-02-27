@extends('layouts.app')

@section('title', 'Detalle de auditoría')

@section('content')
<div class="audit-page audit-show-page">
    <header class="audit-hero">
        <div class="audit-hero-inner">
            <div class="audit-hero-text">
                <h1 class="audit-hero-title">Registro de auditoría #{{ $log->id }}</h1>
                <p class="audit-hero-subtitle">Detalle del evento registrado</p>
            </div>
            <a href="{{ route('audit.index') }}" class="audit-hero-btn">← Volver al listado</a>
        </div>
    </header>

    <div class="audit-card">
        <div class="audit-card-header audit-table-header">
            <h2 class="audit-card-title">Datos del registro</h2>
        </div>
        <div class="audit-card-body">
            <dl class="audit-dl">
                <div class="audit-dl-row">
                    <dt class="audit-dt">Fecha y hora</dt>
                    <dd class="audit-dd">{{ $log->created_at->timezone(config('app.timezone', 'America/Managua'))->format('d/m/Y H:i:s') }}</dd>
                </div>
                <div class="audit-dl-row">
                    <dt class="audit-dt">Acción</dt>
                    <dd class="audit-dd">
                        @if($log->action === 'created')
                        <span class="audit-badge audit-badge-created">Creado</span>
                        @elseif($log->action === 'updated')
                        <span class="audit-badge audit-badge-updated">Modificado</span>
                        @else
                        <span class="audit-badge audit-badge-deleted">Eliminado</span>
                        @endif
                    </dd>
                </div>
                <div class="audit-dl-row">
                    <dt class="audit-dt">Tipo</dt>
                    <dd class="audit-dd">{{ $log->auditable_label }}</dd>
                </div>
                <div class="audit-dl-row">
                    <dt class="audit-dt">ID registro</dt>
                    <dd class="audit-dd">{{ $log->auditable_id }}</dd>
                </div>
                <div class="audit-dl-row">
                    <dt class="audit-dt">Usuario</dt>
                    <dd class="audit-dd">{{ $log->user?->name ?? '—' }} @if($log->user)<span class="audit-muted">({{ $log->user->email }})</span>@endif</dd>
                </div>
                <div class="audit-dl-row">
                    <dt class="audit-dt">IP</dt>
                    <dd class="audit-dd">{{ $log->ip_address ?? '—' }}</dd>
                </div>
                <div class="audit-dl-row">
                    <dt class="audit-dt">Resumen</dt>
                    <dd class="audit-dd">{{ $log->summary ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @if($log->old_values && count($log->old_values) > 0)
    <div class="audit-card">
        <div class="audit-card-header audit-table-header">
            <h2 class="audit-card-title">Valores anteriores</h2>
        </div>
        <div class="audit-card-body audit-card-body-table">
            <table class="audit-values-table">
                <tbody>
                    @foreach($log->old_values as $key => $value)
                    <tr>
                        <td class="audit-values-key">{{ $key }}</td>
                        <td class="audit-values-val">{{ is_array($value) || is_object($value) ? json_encode($value) : (string) $value }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($log->new_values && count($log->new_values) > 0)
    <div class="audit-card">
        <div class="audit-card-header audit-table-header">
            <h2 class="audit-card-title">Valores nuevos / modificados</h2>
        </div>
        <div class="audit-card-body audit-card-body-table">
            <table class="audit-values-table">
                <tbody>
                    @foreach($log->new_values as $key => $value)
                    <tr>
                        <td class="audit-values-key">{{ $key }}</td>
                        <td class="audit-values-val">{{ is_array($value) || is_object($value) ? json_encode($value) : (string) $value }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<style>
.audit-show-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.audit-show-page .audit-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.audit-show-page .audit-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.audit-show-page .audit-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.audit-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.audit-hero-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none; }
.audit-hero-btn:hover { background: #f0fdfa; color: #0d9488; }
.audit-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.audit-card-header.audit-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.audit-card-header.audit-table-header .audit-card-title { color: #fff; }
.audit-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.audit-card-body { padding: 1.25rem 1.5rem; }
.audit-card-body-table { padding: 0; }
.audit-dl { margin: 0; }
.audit-dl-row { margin-bottom: 1rem; display: flex; flex-wrap: wrap; }
.audit-dl-row:last-child { margin-bottom: 0; }
.audit-dt { font-size: 0.8125rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem; flex: 0 0 100%; }
.audit-dd { margin: 0; font-size: 0.9375rem; color: #111827; flex: 0 0 100%; }
.audit-muted { color: #6b7280; font-size: 0.875rem; }
.audit-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.audit-badge-created { background: #d1fae5; color: #047857; }
.audit-badge-updated { background: #dbeafe; color: #1d4ed8; }
.audit-badge-deleted { background: #fee2e2; color: #b91c1c; }
.audit-values-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.audit-values-table td { padding: 0.5rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
.audit-values-key { width: 35%; color: #6b7280; font-weight: 500; }
.audit-values-val { font-family: ui-monospace, monospace; word-break: break-all; }
</style>
@endsection
