@extends('layouts.app')

@section('title', 'Detalle de auditoría')

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $local = $log->created_at->timezone($displayTz);
    $actionMeta = [
        'created' => ['label' => 'Creado', 'class' => 'is-created'],
        'updated' => ['label' => 'Modificado', 'class' => 'is-updated'],
        'deleted' => ['label' => 'Eliminado', 'class' => 'is-deleted'],
        'admin_reset_to_miami' => ['label' => 'Admin: volver a Miami', 'class' => 'is-admin'],
        'admin_change_intake_type' => ['label' => 'Admin: tipo de ingreso', 'class' => 'is-admin'],
    ];
    $meta = $actionMeta[$log->action] ?? ['label' => $log->action_label, 'class' => 'is-admin'];

    $stringify = function ($value) {
        if ($value === null || $value === '') return '—';
        if (is_bool($value)) return $value ? 'Sí' : 'No';
        if (is_array($value) || is_object($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        return (string) $value;
    };
    $old = $log->old_values ?? [];
    $new = $log->new_values ?? [];
    $changeKeys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
@endphp
<div class="audit-page audit-show-page">
    {{-- ===== Banner ===== --}}
    <header class="audit-hero">
        <div class="audit-hero-inner">
            <div class="audit-hero-text">
                <a href="{{ route('audit.index') }}" class="audit-back">← Volver al listado</a>
                <div class="audit-title-row">
                    <h1 class="audit-hero-title">Evento #{{ $log->id }}</h1>
                    <span class="audit-status-pill">{{ $meta['label'] }}</span>
                </div>
                <p class="audit-hero-subtitle">{{ $log->auditable_label }} #{{ $log->auditable_id }} · {{ $local->locale('es')->isoFormat('D [de] MMMM [de] YYYY, HH:mm:ss') }}</p>
            </div>
        </div>
    </header>

    {{-- ===== Datos del evento ===== --}}
    <div class="audit-meta-grid">
        <div class="audit-meta-card">
            <span class="audit-meta-label">Usuario</span>
            <span class="audit-meta-value audit-meta-user">
                <span class="audit-avatar">{{ strtoupper(mb_substr($log->user?->name ?? '?', 0, 1)) }}</span>
                <span>
                    {{ $log->user?->name ?? 'Sistema' }}
                    @if($log->user)<small>{{ $log->user->email }}</small>@endif
                </span>
            </span>
        </div>
        <div class="audit-meta-card">
            <span class="audit-meta-label">Acción</span>
            <span class="audit-meta-value"><span class="audit-badge {{ $meta['class'] }}">{{ $meta['label'] }}</span></span>
        </div>
        <div class="audit-meta-card">
            <span class="audit-meta-label">Registro afectado</span>
            <span class="audit-meta-value">{{ $log->auditable_label }} <span class="audit-mono">#{{ $log->auditable_id }}</span></span>
        </div>
        <div class="audit-meta-card">
            <span class="audit-meta-label">Fecha y hora</span>
            <span class="audit-meta-value">{{ $local->format('d/m/Y') }} <span class="audit-mono">{{ $local->format('H:i:s') }}</span></span>
        </div>
        <div class="audit-meta-card">
            <span class="audit-meta-label">Dirección IP</span>
            <span class="audit-meta-value audit-mono">{{ $log->ip_address ?? '—' }}</span>
        </div>
    </div>

    {{-- ===== Resumen ===== --}}
    @if($log->summary)
    <div class="audit-summary-strip">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="audit-summary-icon"><path d="M13 16h-2v-5h2Zm0-7h-2V7h2Zm-1 13a10 10 0 1 1 0-20 10 10 0 0 1 0 20Z"/></svg>
        <p>{{ $log->summary }}</p>
    </div>
    @endif

    {{-- ===== Cambios ===== --}}
    @if(count($changeKeys) > 0)
    <div class="audit-card">
        <div class="audit-card-header">
            <h2 class="audit-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="audit-card-icon"><path d="M16 3h5v5M8 21H3v-5M21 3l-7.5 7.5M3 21l7.5-7.5"/></svg>
                Cambios registrados
            </h2>
            <span class="audit-card-count">{{ count($changeKeys) }} {{ count($changeKeys) === 1 ? 'campo' : 'campos' }}</span>
        </div>
        <div class="audit-diff-wrap">
            <table class="audit-diff-table">
                <thead>
                    <tr>
                        <th>Campo</th>
                        @if(count($old) > 0)<th>Valor anterior</th>@endif
                        @if(count($new) > 0)<th>{{ count($old) > 0 ? 'Valor nuevo' : 'Valor' }}</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($changeKeys as $key)
                    @php
                        $oldVal = $stringify($old[$key] ?? null);
                        $newVal = $stringify($new[$key] ?? null);
                        $changed = count($old) > 0 && count($new) > 0 && $oldVal !== $newVal;
                    @endphp
                    <tr>
                        <td class="audit-diff-key">{{ $key }}</td>
                        @if(count($old) > 0)
                        <td class="audit-diff-val {{ $changed ? 'is-removed' : '' }}">{{ $oldVal }}</td>
                        @endif
                        @if(count($new) > 0)
                        <td class="audit-diff-val {{ $changed ? 'is-added' : '' }}">{{ $newVal }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="audit-card">
        <div class="audit-empty">
            <p class="audit-empty-text">Este evento no registró cambios de campos.</p>
        </div>
    </div>
    @endif
</div>

<style>
.audit-show-page { padding: 1.25rem 0 2.5rem; max-width: 66rem; margin: 0 auto; width: 100%; }

/* ===== Banner ===== */
.audit-hero {
    background: linear-gradient(135deg, #047857 0%, #059669 50%, #10b981 100%);
    border-radius: 1rem; padding: 1.35rem 1.5rem 1.5rem; margin-bottom: 1.25rem;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}
.audit-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.audit-back { color: rgba(255,255,255,0.75); text-decoration: none; font-size: 0.8125rem; font-weight: 600; }
.audit-back:hover { color: #fff; }
.audit-title-row { display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem; margin-top: 0.4rem; }
.audit-hero-title { margin: 0; font-size: 1.55rem; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
.audit-status-pill {
    display: inline-flex; align-items: center; padding: 0.28rem 0.7rem; border-radius: 999px;
    font-size: 0.75rem; font-weight: 700; background: rgba(255,255,255,0.18);
    border: 1px solid rgba(255,255,255,0.35); color: #fff;
}
.audit-hero-subtitle { margin: 0.3rem 0 0; font-size: 0.88rem; color: rgba(255,255,255,0.85); }

/* ===== Meta cards ===== */
.audit-meta-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
@media (min-width: 900px) { .audit-meta-grid { grid-template-columns: 1.4fr 1fr 1.2fr 1fr 1fr; } }
.audit-meta-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.8rem 0.95rem;
    box-shadow: 0 1px 3px rgba(15,23,42,0.05);
}
.audit-meta-label { display: block; font-size: 0.64rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #94a3b8; margin-bottom: 0.35rem; }
.audit-meta-value { font-size: 0.88rem; font-weight: 700; color: #0f172a; word-break: break-word; }
.audit-meta-user { display: flex; align-items: center; gap: 0.5rem; }
.audit-meta-user small { display: block; font-size: 0.68rem; font-weight: 500; color: #94a3b8; }
.audit-avatar {
    width: 1.9rem; height: 1.9rem; border-radius: 999px; background: linear-gradient(135deg, #059669, #10b981);
    color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 800; flex-shrink: 0;
}
.audit-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

.audit-badge { display: inline-block; padding: 0.2rem 0.55rem; font-size: 0.66rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; border-radius: 0.35rem; }
.audit-badge.is-created { background: #d1fae5; color: #047857; }
.audit-badge.is-updated { background: #dbeafe; color: #1d4ed8; }
.audit-badge.is-deleted { background: #fee2e2; color: #b91c1c; }
.audit-badge.is-admin { background: #fef3c7; color: #b45309; }

/* ===== Resumen ===== */
.audit-summary-strip {
    display: flex; align-items: flex-start; gap: 0.65rem; background: #f8fafc;
    border: 1px solid #e2e8f0; border-left: 3px solid #10b981; border-radius: 0.75rem;
    padding: 0.85rem 1.1rem; margin-bottom: 1rem;
}
.audit-summary-icon { width: 1rem; height: 1rem; color: #059669; flex-shrink: 0; margin-top: 0.15rem; }
.audit-summary-strip p { margin: 0; font-size: 0.88rem; color: #334155; line-height: 1.5; }

/* ===== Card / diff ===== */
.audit-card { background: #fff; border-radius: 0.85rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(15,23,42,0.05); overflow: hidden; }
.audit-card-header {
    padding: 0.85rem 1.25rem; border-bottom: 1px solid #eef2f7;
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;
}
.audit-card-title { margin: 0; font-size: 0.95rem; font-weight: 750; color: #0f172a; display: inline-flex; align-items: center; gap: 0.5rem; }
.audit-card-icon { width: 1rem; height: 1rem; color: #059669; }
.audit-card-count { font-size: 0.75rem; font-weight: 600; color: #64748b; background: #f1f5f9; border-radius: 999px; padding: 0.2rem 0.65rem; }

.audit-diff-wrap { overflow-x: auto; }
.audit-diff-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.audit-diff-table th {
    text-align: left; padding: 0.65rem 1.25rem; font-size: 0.66rem; font-weight: 800;
    letter-spacing: 0.07em; text-transform: uppercase; color: #94a3b8; background: #f8fafc;
    border-bottom: 1px solid #e2e8f0; white-space: nowrap;
}
.audit-diff-table td { padding: 0.65rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.audit-diff-table tbody tr:last-child td { border-bottom: none; }
.audit-diff-key { width: 22%; font-weight: 700; color: #475569; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.78rem; white-space: nowrap; }
.audit-diff-val { color: #334155; word-break: break-word; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.8rem; }
.audit-diff-val.is-removed { background: #fef2f2; color: #b91c1c; text-decoration: line-through; text-decoration-color: rgba(185,28,28,0.4); }
.audit-diff-val.is-added { background: #f0fdf6; color: #047857; font-weight: 650; }

.audit-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
.audit-empty-text { margin: 0; font-size: 0.9rem; }
</style>
@endsection
