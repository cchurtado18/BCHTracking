@extends('layouts.app')

@section('title', 'Solicitar reporte PDF')

@section('content')
<div class="reporte-solicitar-page">
    <header class="reporte-solicitar-hero">
        <div class="reporte-solicitar-hero-inner">
            <div class="reporte-solicitar-hero-text">
                <h1 class="reporte-solicitar-hero-title">Reporte de paquetes (PDF)</h1>
                <p class="reporte-solicitar-hero-subtitle">Indique los filtros que necesita y genere el reporte por subagencia y periodo. Luego podrá imprimirlo o guardarlo como PDF.</p>
            </div>
            <a href="{{ route('packages.index') }}" class="reporte-solicitar-hero-btn">← Volver a paquetes</a>
        </div>
    </header>

    <div class="reporte-solicitar-card">
        <div class="reporte-solicitar-card-header">
            <h2 class="reporte-solicitar-card-title">¿Qué reporte necesita?</h2>
        </div>
        <div class="reporte-solicitar-card-body">
            <form method="GET" action="{{ route('reporte.paquetes') }}" target="_blank" rel="noopener" class="reporte-solicitar-form">
                <div class="reporte-solicitar-grid">
                    @if($isAgencyUser && $currentAgency)
                        <div class="reporte-solicitar-field reporte-solicitar-field-full">
                            <label class="reporte-solicitar-label">Subagencia</label>
                            <p class="reporte-solicitar-fixed-value">{{ $currentAgency->name }} <span class="reporte-solicitar-code">({{ $currentAgency->code }})</span></p>
                            <input type="hidden" name="agency_id" value="{{ $currentAgency->id }}">
                        </div>
                    @else
                        <div class="reporte-solicitar-field reporte-solicitar-field-full">
                            <label for="agency_id" class="reporte-solicitar-label">Subagencia</label>
                            <select name="agency_id" id="agency_id" class="reporte-solicitar-select">
                                <option value="">Todas las subagencias</option>
                                @foreach($agencies as $agency)
                                    <option value="{{ $agency->id }}">{{ $agency->name }} ({{ $agency->code }})</option>
                                @endforeach
                            </select>
                            <p class="reporte-solicitar-hint">Seleccione una subagencia para el reporte mensual o deje "Todas" para ver todo.</p>
                        </div>
                    @endif

                    <div class="reporte-solicitar-field">
                        <label for="date_from" class="reporte-solicitar-label">Fecha desde *</label>
                        <input type="date" name="date_from" id="date_from" class="reporte-solicitar-input" value="{{ $defaultDateFrom }}" required>
                    </div>
                    <div class="reporte-solicitar-field">
                        <label for="date_to" class="reporte-solicitar-label">Fecha hasta *</label>
                        <input type="date" name="date_to" id="date_to" class="reporte-solicitar-input" value="{{ $defaultDateTo }}" required>
                    </div>
                    <div class="reporte-solicitar-field">
                        <label for="service_type" class="reporte-solicitar-label">Servicio</label>
                        <select name="service_type" id="service_type" class="reporte-solicitar-select">
                            <option value="">Todos</option>
                            <option value="AIR">Aéreo</option>
                            <option value="SEA">Marítimo</option>
                        </select>
                    </div>
                </div>

                <div class="reporte-solicitar-actions">
                    <button type="submit" class="reporte-solicitar-btn reporte-solicitar-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
                        Generar reporte (abre en nueva pestaña)
                    </button>
                </div>
                <p class="reporte-solicitar-note">En la nueva pestaña use «Imprimir / Guardar PDF» para descargar el PDF con los datos filtrados.</p>
            </form>
        </div>
    </div>
</div>

<style>
.reporte-solicitar-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

.reporte-solicitar-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.reporte-solicitar-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.reporte-solicitar-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.reporte-solicitar-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 52ch; }
.reporte-solicitar-hero-btn {
    display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;
    color: #0f766e; background: #fff; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none;
}
.reporte-solicitar-hero-btn:hover { background: #f0fdfa; color: #0d9488; }

.reporte-solicitar-card {
    background: #fff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    overflow: hidden;
    max-width: 42rem;
}
.reporte-solicitar-card-header {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    padding: 0.75rem 1.5rem;
}
.reporte-solicitar-card-title { margin: 0; font-size: 1rem; font-weight: 600; color: #fff; }
.reporte-solicitar-card-body { padding: 1.5rem 1.75rem; }

.reporte-solicitar-form { margin: 0; }
.reporte-solicitar-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 1.25rem; }
.reporte-solicitar-field-full { grid-column: 1 / -1; }
.reporte-solicitar-label { display: block; font-size: 0.8125rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.reporte-solicitar-input, .reporte-solicitar-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem;
    border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827;
}
.reporte-solicitar-input:focus, .reporte-solicitar-select:focus {
    outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
}
.reporte-solicitar-fixed-value { margin: 0; padding: 0.5rem 0.75rem; background: #f3f4f6; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827; }
.reporte-solicitar-code { color: #6b7280; font-weight: 400; }
.reporte-solicitar-hint { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
.reporte-solicitar-actions { margin-top: 1.5rem; }
.reporte-solicitar-btn {
    display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem; font-size: 0.9375rem; font-weight: 600;
    border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; background: #0d9488; color: #fff; border-color: #0d9488;
}
.reporte-solicitar-btn:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.reporte-solicitar-note { margin: 1rem 0 0; font-size: 0.8125rem; color: #6b7280; }
</style>
@endsection
