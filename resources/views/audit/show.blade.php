@extends('layouts.app')

@section('title', 'Detalle de auditoría')

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $local = $log->created_at->timezone($displayTz);
    $relative = $local->locale('es')->diffForHumans();
    $actionMeta = [
        'created' => ['label' => 'Creado', 'class' => 'is-created'],
        'updated' => ['label' => 'Modificado', 'class' => 'is-updated'],
        'deleted' => ['label' => 'Eliminado', 'class' => 'is-deleted'],
        'admin_reset_to_miami' => ['label' => 'Admin: volver a Miami', 'class' => 'is-admin'],
        'admin_change_intake_type' => ['label' => 'Admin: tipo de ingreso', 'class' => 'is-admin'],
        'invoice_emailed' => ['label' => 'Factura enviada', 'class' => 'is-created'],
        'invoice_voided' => ['label' => 'Factura anulada', 'class' => 'is-deleted'],
        'invoice_deleted' => ['label' => 'Factura eliminada', 'class' => 'is-deleted'],
        'payment_registered' => ['label' => 'Cobro registrado', 'class' => 'is-created'],
        'payment_voided' => ['label' => 'Cobro cancelado', 'class' => 'is-deleted'],
        'credit_note_registered' => ['label' => 'Nota de crédito', 'class' => 'is-created'],
        'credit_note_voided' => ['label' => 'Nota de crédito anulada', 'class' => 'is-deleted'],
        'expense_registered' => ['label' => 'Gasto registrado', 'class' => 'is-created'],
        'expense_deleted' => ['label' => 'Gasto eliminado', 'class' => 'is-deleted'],
    ];
    $meta = $actionMeta[$log->action] ?? ['label' => $log->action_label, 'class' => 'is-admin'];
    $old = $log->old_values ?? [];
    $new = $log->new_values ?? [];
    $hidden = \App\Models\AuditLog::hiddenValueKeys();
    $changeKeys = array_values(array_filter(
        array_unique(array_merge(array_keys($old), array_keys($new))),
        fn ($key) => ! in_array($key, $hidden, true)
    ));
    $code = $log->displayCode();
    $agencyNames = $agencyNames ?? [];
    $recipientNames = $recipientNames ?? [];
    $categoryNames = $categoryNames ?? [];
    $recordUrl = $recordUrl ?? null;
    $highlightKeys = ['warehouse_code', 'folio', 'tracking_external', 'label_name', 'agency_id', 'agency_client_id', 'status', 'service_type', 'intake_type', 'intake_weight_lbs', 'verified_weight_lbs', 'amount_usd', 'total_usd', 'email', 'method', 'deposit_account', 'category_id'];
    $highlights = [];
    foreach ($highlightKeys as $key) {
        $value = $log->snapshotGet($key);
        if ($value === null || $value === '') {
            continue;
        }
        $highlights[] = [
            'label' => \App\Models\AuditLog::fieldLabel($key),
            'value' => \App\Models\AuditLog::formatValue($key, $value, $displayTz, $agencyNames, $recipientNames, $categoryNames),
        ];
    }
    $live = $log->liveContext ?? [];
    $liveItems = [];
    $liveMap = [
        'code' => 'Código',
        'tracking_external' => 'Tracking',
        'label_name' => 'Nombre en etiqueta',
        'agency_id' => 'Cliente',
        'agency_client_id' => 'Destinatario',
        'status' => 'Estado actual',
        'service_type' => 'Servicio',
        'total_usd' => 'Total USD',
        'amount_usd' => 'Monto USD',
        'method' => 'Método de pago',
        'category_id' => 'Categoría',
    ];
    foreach ($liveMap as $key => $label) {
        $value = $live[$key] ?? null;
        if ($value === null || $value === '') {
            continue;
        }
        $liveItems[] = [
            'label' => $label,
            'value' => $key === 'code'
                ? (string) $value
                : \App\Models\AuditLog::formatValue($key, $value, $displayTz, $agencyNames, $recipientNames, $categoryNames),
        ];
    }
@endphp
<div class="audit-page audit-show-page">
    <x-module-banner
        section="Administración"
        current="Evento"
        title="{{ $meta['label'] }} · {{ $log->auditable_label }}"
        subtitle="{{ $log->summary ?: 'Detalle de quién hizo el cambio, cuándo y qué datos se guardaron.' }}"
        back-href="{{ route('audit.index') }}"
        back-label="Volver a auditoría"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </x-slot:icon>
        @if($recordUrl)
        <x-slot:actions>
            <a href="{{ $recordUrl }}" class="mb-btn mb-btn-primary">Ver registro</a>
        </x-slot:actions>
        @endif
        <x-slot:strip>
            <span class="mb-strip-label">Hecho por</span>
            <span class="mb-pill"><strong>{{ $log->actorName() }}</strong></span>
            <span class="mb-pill">{{ $log->actorRoleLabel() }}</span>
            <span class="mb-pill">{{ $local->format('d/m/Y H:i') }}</span>
            @if($code)
            <span class="mb-pill">{{ $code }}</span>
            @endif
        </x-slot:strip>
    </x-module-banner>

    <div class="audit-who-grid">
        <div class="audit-who-card">
            <span class="audit-who-label">Quién lo hizo</span>
            <div class="audit-who-user">
                <span class="audit-avatar">{{ strtoupper(mb_substr($log->actorName(), 0, 1)) }}</span>
                <div>
                    <strong>{{ $log->actorName() }}</strong>
                    <small>{{ $log->actorRoleLabel() }}@if($log->actorEmail()) · {{ $log->actorEmail() }}@endif</small>
                </div>
            </div>
        </div>
        <div class="audit-who-card">
            <span class="audit-who-label">Cuándo</span>
            <strong class="audit-who-time">{{ $local->format('d/m/Y') }} · {{ $local->format('H:i:s') }}</strong>
            <small>{{ $relative }} · hora de Miami</small>
        </div>
        <div class="audit-who-card">
            <span class="audit-who-label">Qué</span>
            <strong>{{ $meta['label'] }}</strong>
            <small>{{ $log->auditable_label }} #{{ $log->auditable_id }}</small>
        </div>
        <div class="audit-who-card">
            <span class="audit-who-label">Desde</span>
            <strong>{{ $log->ip_address ?: '—' }}</strong>
            <small>Dirección IP del equipo</small>
        </div>
    </div>

    @if(count($highlights) > 0)
    <div class="audit-highlights">
        @foreach($highlights as $item)
        <div class="audit-highlight">
            <span>{{ $item['label'] }}</span>
            <strong>{{ $item['value'] }}</strong>
        </div>
        @endforeach
    </div>
    @endif

    @if(count($liveItems) > 0)
    <div class="audit-card" style="margin-bottom:1.15rem;">
        <div class="audit-card-header">
            <h2 class="audit-card-title">Registro actual</h2>
            <span class="audit-card-count">Así está hoy el registro</span>
        </div>
        <div class="audit-highlights" style="margin:0; padding:0.85rem;">
            @foreach($liveItems as $item)
            <div class="audit-highlight">
                <span>{{ $item['label'] }}</span>
                <strong>{{ $item['value'] }}</strong>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if(count($changeKeys) > 0)
    <div class="audit-card">
        <div class="audit-card-header">
            <h2 class="audit-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="audit-card-icon"><path d="M16 3h5v5M8 21H3v-5M21 3l-7.5 7.5M3 21l7.5-7.5"/></svg>
                {{ count($old) > 0 && count($new) > 0 ? 'Qué cambió' : 'Datos del registro' }}
            </h2>
            <span class="audit-card-count">{{ count($changeKeys) }} {{ count($changeKeys) === 1 ? 'dato' : 'datos' }}</span>
        </div>
        <div class="audit-diff-wrap">
            <table class="audit-diff-table">
                <thead>
                    <tr>
                        <th>Dato</th>
                        @if(count($old) > 0)<th>Antes</th>@endif
                        @if(count($new) > 0)<th>{{ count($old) > 0 ? 'Después' : 'Valor' }}</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($changeKeys as $key)
                    @php
                        $oldVal = \App\Models\AuditLog::formatValue($key, $old[$key] ?? null, $displayTz, $agencyNames, $recipientNames, $categoryNames);
                        $newVal = \App\Models\AuditLog::formatValue($key, $new[$key] ?? null, $displayTz, $agencyNames, $recipientNames, $categoryNames);
                        $changed = count($old) > 0 && count($new) > 0 && $oldVal !== $newVal;
                    @endphp
                    <tr>
                        <td class="audit-diff-key">{{ \App\Models\AuditLog::fieldLabel($key) }}</td>
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
            <p class="audit-empty-text">Este evento no guardó cambios de campos. Quien lo hizo y la hora están arriba.</p>
        </div>
    </div>
    @endif
</div>

<style>
.audit-show-page { padding: 1.25rem 0 2.5rem; max-width: 66rem; margin: 0 auto; width: 100%; }
.audit-who-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 1.15rem; }
@media (max-width: 900px) { .audit-who-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 600px) { .audit-who-grid { grid-template-columns: 1fr; } }
.audit-highlights { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 0.65rem; margin-bottom: 1.15rem; }
.audit-highlight { background: #fff; border: 1px solid #E8EEF8; border-radius: 0.75rem; padding: 0.75rem 0.9rem; }
.audit-highlight span { display: block; font-size: 0.64rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #94a3b8; margin-bottom: 0.25rem; }
.audit-highlight strong { font-size: 0.92rem; color: #0A2D6F; word-break: break-word; }
.audit-who-card {
    background: #fff; border: 1px solid #E8EEF8; border-radius: 0.85rem;
    padding: 0.95rem 1.05rem; box-shadow: 0 2px 8px rgba(15,23,42,0.04);
    display: flex; flex-direction: column; gap: 0.35rem;
}
.audit-who-label { font-size: 0.66rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; color: #94a3b8; }
.audit-who-card strong { font-size: 1rem; color: #0f172a; }
.audit-who-card small { font-size: 0.78rem; color: #64748b; }
.audit-who-user { display: flex; align-items: center; gap: 0.65rem; }
.audit-who-user strong, .audit-who-user small { display: block; }
.audit-who-time { font-variant-numeric: tabular-nums; }
.audit-avatar {
    width: 2.25rem; height: 2.25rem; border-radius: 999px; background: linear-gradient(135deg, #0A2D6F, #1E4FA8);
    color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 800; flex-shrink: 0;
}
.audit-card { background: #fff; border-radius: 0.85rem; border: 1px solid #E8EEF8; box-shadow: 0 2px 8px rgba(15,23,42,0.04); overflow: hidden; }
.audit-card-header {
    padding: 0.85rem 1.15rem; border-bottom: 1px solid #E8EEF8;
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;
}
.audit-card-title { margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; display: inline-flex; align-items: center; gap: 0.5rem; }
.audit-card-icon { width: 1rem; height: 1rem; color: #0A2D6F; }
.audit-card-count { font-size: 0.75rem; font-weight: 600; color: #64748b; background: #F4F8FD; border-radius: 999px; padding: 0.2rem 0.65rem; }
.audit-diff-wrap { overflow-x: auto; }
.audit-diff-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.audit-diff-table th {
    text-align: left; padding: 0.65rem 1.15rem; font-size: 0.66rem; font-weight: 800;
    letter-spacing: 0.07em; text-transform: uppercase; color: #fff; background: #0A2D6F; white-space: nowrap;
}
.audit-diff-table td { padding: 0.7rem 1.15rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.audit-diff-table tbody tr:nth-child(even) td { background: #FAFCFF; }
.audit-diff-table tbody tr:last-child td { border-bottom: none; }
.audit-diff-key { width: 28%; font-weight: 700; color: #0A2D6F; }
.audit-diff-val { color: #334155; word-break: break-word; }
.audit-diff-val.is-removed { background: #FDECEC; color: #B03030; text-decoration: line-through; text-decoration-color: rgba(176,48,48,0.4); }
.audit-diff-val.is-added { background: #EFFAF4; color: #116039; font-weight: 650; }
.audit-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
.audit-empty-text { margin: 0; font-size: 0.9rem; }
</style>
@endsection
