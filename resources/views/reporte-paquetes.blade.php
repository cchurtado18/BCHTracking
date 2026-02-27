@extends('layouts.app')

@section('title', 'Reporte de paquetes')

@section('content')
<style>
    .reporte-wrap { max-width: 1200px; margin: 0 auto; padding: 1.5rem 1rem; }
    .reporte-title { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 0.25rem; }
    .reporte-subtitle { font-size: 0.9375rem; color: #64748b; margin-bottom: 1.25rem; }
    .reporte-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .reporte-table th { text-align: left; padding: 0.75rem 0.5rem; border-bottom: 2px solid #334155; font-weight: 700; color: #1e293b; }
    .reporte-table td { padding: 0.6rem 0.5rem; border-bottom: 1px solid #e2e8f0; }
    .reporte-table tbody tr:nth-child(even) { background: #f8fafc; }
    .reporte-table .text-end { text-align: right; }
    .btn-print { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: #0d9488; color: #fff; border: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: pointer; margin-bottom: 1rem; text-decoration: none; }
    .btn-print:hover { background: #0f766e; color: #fff; }
    @media print {
        nav { display: none !important; }
        .btn-print { display: none !important; }
        .reporte-wrap { padding: 0; }
    }
</style>
<div class="reporte-wrap">
    <button type="button" class="btn-print" onclick="window.print();">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
        Imprimir / Guardar PDF
    </button>
    <h1 class="reporte-title">Reporte de paquetes</h1>
    <p class="reporte-subtitle">{{ $periodLabel }}</p>

    @if($paquetes->isEmpty())
        <p style="color: #64748b;">No hay paquetes con los filtros aplicados.</p>
    @else
        <table class="reporte-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Agencia</th>
                    <th>Servicio</th>
                    <th class="text-end">Peso (lbs)</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paquetes as $index => $p)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-family: ui-monospace, monospace;">{{ $p->warehouse_code ?? $p->tracking_external ?? '—' }}</td>
                    <td>{{ $p->label_name ?? '—' }}</td>
                    <td>{{ $p->agency->name ?? '—' }}</td>
                    <td>{{ $p->service_type ?? '—' }}</td>
                    <td class="text-end">{{ number_format($p->verified_weight_lbs ?? $p->intake_weight_lbs ?? 0, 2) }}</td>
                    <td>{{ $p->status ?? '—' }}</td>
                    <td>{{ $p->created_at ? $p->created_at->format('d/m/Y H:i') : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin-top: 1rem; font-size: 0.8125rem; color: #64748b;">Total: {{ number_format($paquetes->count()) }} paquetes.</p>
    @endif
</div>
@endsection
