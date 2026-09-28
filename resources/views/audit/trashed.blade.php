@extends('layouts.app')

@section('title', 'Preregistros eliminados')

@section('content')
@php
    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $statusLabels = [
        'PHOTO_PENDING' => 'Pendiente datos',
        'RECEIVED_MIAMI' => 'Recibido Miami',
        'CANCELLED' => 'Inactivo',
    ];
@endphp
<div class="cx-page">
    <x-module-banner
        section="Administración"
        current="Auditoría"
        title="Preregistros eliminados"
        subtitle="Solo el administrador puede recuperarlos. Quedan disponibles {{ $retentionDays }} días y después se borran de forma definitiva, con sus fotos."
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('audit.index') }}" class="mb-btn mb-btn-secondary">Volver a auditoría</a>
        </x-slot:actions>
    </x-module-banner>

    <div class="cx-toolbar">
        <span class="cx-count">Total: <strong>{{ number_format($packages->total()) }}</strong> {{ $packages->total() === 1 ? 'registro recuperable' : 'registros recuperables' }}.</span>
    </div>

    <div class="cx-card">
        <div class="cx-table-scroll">
            <table class="cx-table">
                <thead>
                    <tr>
                        <th>Tracking</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Agencia</th>
                        <th>Estado</th>
                        <th>Eliminado</th>
                        <th>Plazo</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                    @php
                        $daysLeft = $trash->daysRemaining($package);
                        $expiresAt = $trash->expiresAt($package)->timezone($displayTz);
                    @endphp
                    <tr>
                        <td class="cx-folio">{{ $package->tracking_external ?: '—' }}</td>
                        <td class="cx-strong">{{ $package->warehouse_code ?: '—' }}</td>
                        <td>{{ $package->label_name ?: '—' }}</td>
                        <td>{{ $package->agency?->listingAccountLabel() ?: '—' }}</td>
                        <td>{{ $statusLabels[$package->status] ?? ($package->status ?: '—') }}</td>
                        <td class="cx-nowrap">
                            <div class="cx-strong">{{ $package->deleted_at?->timezone($displayTz)->format('d/m/Y') ?? '—' }}</div>
                            <div class="cx-muted">{{ $package->deleted_at?->timezone($displayTz)->format('H:i') ?? '' }}</div>
                        </td>
                        <td>
                            <div class="cx-strong">{{ $daysLeft === 1 ? 'Queda 1 día' : 'Quedan '.$daysLeft.' días' }}</div>
                            <div class="cx-muted">Hasta {{ $expiresAt->format('d/m/Y') }}</div>
                        </td>
                        <td>
                            <form action="{{ route('audit.preregistrations.restore', $package->id) }}" method="POST" onsubmit="return confirm('¿Recuperar este preregistro y devolverlo a la lista?');">
                                @csrf
                                <button type="submit" class="cx-btn cx-btn-primary">Recuperar</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="cx-empty">No hay preregistros para recuperar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($packages->total() > 0)
        <div class="cx-card-footer">
            <span class="cx-muted">{{ $packages->firstItem() }} – {{ $packages->lastItem() }} de {{ number_format($packages->total()) }}</span>
            @if($packages->hasPages())
            <div>{{ $packages->links('vendor.pagination.primetrack') }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.cx-page { --cx-navy:#0A2D6F; --cx-blue:#1E4FA8; --cx-line:#E8EEF8; --cx-soft:#F4F8FD; padding:1.15rem 0 2.25rem; max-width:96rem; margin:0 auto; width:100%; }
.cx-card { background:#fff; border:1px solid var(--cx-line); border-radius:0.85rem; box-shadow:0 2px 8px rgba(15,23,42,0.04); overflow:hidden; margin-bottom:1.15rem; }
.cx-toolbar { display:flex; justify-content:flex-end; margin:0 0.1rem 0.75rem; }
.cx-count { font-size:0.85rem; color:#64748b; }
.cx-count strong { color:#0f172a; }
.cx-table-scroll { overflow-x:auto; }
.cx-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.cx-table thead th { background:var(--cx-navy); color:#fff; text-align:left; padding:0.62rem 0.8rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; white-space:nowrap; }
.cx-table td { padding:0.7rem 0.8rem; border-bottom:1px solid #f4f7fb; color:#334155; vertical-align:middle; }
.cx-table tbody tr:nth-child(even) td { background:#FAFCFF; }
.cx-table tbody tr:hover td { background:var(--cx-soft); }
.cx-strong { font-weight:700; color:#0f172a; }
.cx-muted { color:#94a3b8; font-size:0.75rem; }
.cx-nowrap { white-space:nowrap; }
.cx-folio { font-weight:800; color:var(--cx-blue); }
.cx-empty { padding:1.4rem 1rem; text-align:center; color:#94a3b8; }
.cx-card-footer { display:flex; flex-wrap:wrap; justify-content:space-between; gap:0.75rem; padding:0.75rem 1rem; border-top:1px solid var(--cx-line); }
.cx-btn { display:inline-flex; align-items:center; justify-content:center; gap:0.4rem; padding:0.45rem 0.9rem; font-size:0.8125rem; font-weight:700; border-radius:0.55rem; border:1px solid transparent; cursor:pointer; text-decoration:none; }
.cx-btn-primary { background:var(--cx-navy); color:#fff; border-color:var(--cx-navy); }
.cx-btn-primary:hover { background:var(--cx-blue); color:#fff; }
</style>
@endsection
