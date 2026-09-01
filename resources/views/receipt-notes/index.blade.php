@extends('layouts.app')

@section('title', 'Comprobantes de recepción')

@section('content')
<div class="rn-page">
    <x-module-banner section="Operaciones" current="Comprobantes" title="Comprobantes de recepción" subtitle="Notas REC-XXXXX generadas al recibir paquetes Drop Off en Miami. Cada nota agrupa los bultos entregados por un mismo cliente.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7.5 3.75h9a1.5 1.5 0 0 1 1.5 1.5v13.5a1.5 1.5 0 0 1-1.5 1.5h-9a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Zm1.5 4.5h6"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('receipt-notes.batch') }}" class="mb-btn mb-btn-primary">Nueva nota de recepción</a>
        </x-slot:actions>
    </x-module-banner>

    <div class="rn-card">
        <div class="rn-card-header">
            <h2 class="rn-card-title">Filtros</h2>
        </div>
        <div class="rn-card-body">
            <form method="GET" action="{{ route('receipt-notes.index') }}" class="rn-filters">
                <div class="rn-field">
                    <label for="q" class="rn-label">Buscar (código, nombre o ID)</label>
                    <input type="text" name="q" id="q" value="{{ request('q') }}" class="rn-input" placeholder="REC-… o nombre">
                </div>
                <div class="rn-field">
                    <label for="agency_id" class="rn-label">Agencia</label>
                    <select name="agency_id" id="agency_id" class="rn-select">
                        <option value="">— Todas —</option>
                        @foreach($agencies as $a)
                        <option value="{{ $a->id }}" {{ (string) request('agency_id') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rn-field">
                    <label for="date_from" class="rn-label">Desde</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="rn-input">
                </div>
                <div class="rn-field">
                    <label for="date_to" class="rn-label">Hasta</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="rn-input">
                </div>
                <div class="rn-field rn-field-actions">
                    <button type="submit" class="rn-btn rn-btn-primary">Filtrar</button>
                    <a href="{{ route('receipt-notes.index') }}" class="rn-btn rn-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="rn-card">
        <div class="rn-card-header rn-table-header">
            <h2 class="rn-card-title">Notas de recepción</h2>
            <span class="rn-card-badge">{{ $notes->total() }} {{ $notes->total() === 1 ? 'nota' : 'notas' }}</span>
        </div>
        <div class="rn-table-wrap">
            <table class="rn-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Fecha</th>
                        <th>Entregado por</th>
                        <th>ID</th>
                        <th>Agencia</th>
                        <th>Bultos</th>
                        <th>Recibió</th>
                        <th class="rn-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notes as $note)
                    <tr>
                        <td><span class="rn-code">{{ $note->code }}</span></td>
                        <td class="rn-muted">{{ $note->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="rn-name">{{ $note->delivered_by }}</td>
                        <td class="rn-muted rn-code-mono">{{ $note->delivered_by_id_number ?: '—' }}</td>
                        <td class="rn-muted">{{ $note->agency?->name ?? '—' }}</td>
                        <td class="rn-num">{{ $note->preregistrations_count }}</td>
                        <td class="rn-muted">{{ $note->receivedBy?->name ?? '—' }}</td>
                        <td class="rn-actions">
                            <a href="{{ route('receipt-notes.print', $note->id) }}" target="_blank" class="rn-btn rn-btn-sm rn-btn-outline-primary">Imprimir</a>
                            <a href="{{ route('receipt-notes.batch', ['receipt_note_id' => $note->id]) }}" class="rn-btn rn-btn-sm rn-btn-secondary">Editar</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="rn-empty">
                            <p class="rn-empty-text">No hay notas de recepción aún. Use «+ Nueva nota de recepción» para crear la primera.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($notes->total() > 0)
        <div class="rn-card-footer">
            <span class="rn-pagination-info">{{ $notes->firstItem() }} – {{ $notes->lastItem() }} de {{ $notes->total() }}</span>
            @if($notes->hasPages())
            <div class="rn-pagination-links">{{ $notes->links() }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.rn-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

.rn-hero {
    background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}
.rn-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.rn-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.rn-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 52ch; }
.rn-hero-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.rn-btn-primary-light {
    background: #fff; color: #0A2D6F; border-color: #fff; font-weight: 600;
}
.rn-btn-primary-light:hover { background: #F4F8FD; color: #0A2D6F; }

.rn-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.rn-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.rn-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.rn-card-body { padding: 1.25rem; }
.rn-card-badge { font-size: 0.8125rem; color: #6b7280; }
.rn-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }

.rn-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.85rem 1rem; align-items: end; }
.rn-field { display: flex; flex-direction: column; gap: 0.35rem; }
.rn-field-actions { flex-direction: row; gap: 0.5rem; align-items: stretch; }
.rn-label { font-size: 0.8125rem; font-weight: 600; color: #374151; }
.rn-input, .rn-select {
    width: 100%; padding: 0.55rem 0.75rem; font-size: 0.875rem;
    border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827;
}
.rn-input:focus, .rn-select:focus {
    outline: none; border-color: #0A2D6F; box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15);
}

.rn-btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0.55rem 1rem; font-size: 0.875rem; font-weight: 500;
    border-radius: 0.5rem; border: 1px solid transparent;
    cursor: pointer; text-decoration: none; transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.rn-btn-primary { background: #0A2D6F; color: #fff; border-color: #0A2D6F; font-weight: 600; }
.rn-btn-primary:hover { background: #0A2D6F; border-color: #0A2D6F; color: #fff; }
.rn-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.rn-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.rn-btn-outline-primary { background: #fff; color: #0A2D6F; border-color: #0A2D6F; }
.rn-btn-outline-primary:hover { background: #E8EEF8; color: #0A2D6F; }
.rn-btn-sm { padding: 0.35rem 0.7rem; font-size: 0.8125rem; }

.rn-table-wrap { overflow-x: auto; }
.rn-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.rn-table thead { background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%); }
.rn-table th { padding: 0.65rem 0.85rem; text-align: left; font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #fff; }
.rn-table td { padding: 0.7rem 0.85rem; border-top: 1px solid #e5e7eb; vertical-align: middle; }
.rn-table tbody tr:hover { background: #f9fafb; }
.rn-th-actions { text-align: right; }
.rn-actions { text-align: right; white-space: nowrap; display: flex; gap: 0.35rem; justify-content: flex-end; }

.rn-code { font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a; }
.rn-code-mono { font-family: ui-monospace, monospace; }
.rn-num { font-variant-numeric: tabular-nums; font-weight: 600; color: #0f172a; }
.rn-muted { color: #6b7280; }
.rn-name { font-weight: 500; color: #111827; }

.rn-empty { text-align: center; padding: 2rem 1rem; }
.rn-empty-text { margin: 0; font-size: 0.9375rem; color: #6b7280; }
</style>
@endsection
