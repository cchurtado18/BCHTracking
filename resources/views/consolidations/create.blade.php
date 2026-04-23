@extends('layouts.app')

@section('title', 'Crear saco')

@section('content')
<div class="cons-choose-page">
    <header class="cons-choose-hero">
        <div class="cons-choose-hero-inner">
            <div>
                <h1 class="cons-choose-title">Crear consolidación (saco)</h1>
                <p class="cons-choose-sub">Elige cómo quieres armar el saco. Puedes volver atrás y cambiar de modo en cualquier momento.</p>
            </div>
            <a href="{{ route('consolidations.index') }}" class="cons-choose-back">← Volver a consolidaciones</a>
        </div>
    </header>

    <div class="cons-choose-grid">
        <a href="{{ route('consolidations.create-scan') }}" class="cons-choose-card cons-choose-card-scan">
            <span class="cons-choose-icon" aria-hidden="true">▦</span>
            <h2 class="cons-choose-card-title">Crear saco escaneando tracking</h2>
            <p class="cons-choose-card-text">Solo campo de código: escanea o pega cada tracking o warehouse. Si el paquete está en preregistro se marca con ✓; si no, verás una advertencia y aun así se guarda en el saco.</p>
            <span class="cons-choose-cta">Continuar →</span>
        </a>

        <a href="{{ route('consolidations.create-select') }}" class="cons-choose-card cons-choose-card-select">
            <span class="cons-choose-icon" aria-hidden="true">☰</span>
            <h2 class="cons-choose-card-title">Crear saco seleccionando</h2>
            <p class="cons-choose-card-text">Elige los preregistros disponibles en Miami desde la tabla, con filtros por fecha y tipo de servicio (como antes).</p>
            <span class="cons-choose-cta">Continuar →</span>
        </a>
    </div>
</div>

<style>
.cons-choose-page { padding: 1.5rem 0; max-width: 56rem; margin: 0 auto; width: 100%; }
.cons-choose-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.cons-choose-hero-inner { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.cons-choose-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.cons-choose-sub { margin: 0.5rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.92); max-width: 40rem; line-height: 1.45; }
.cons-choose-back {
    display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;
    color: #0f766e; background: #fff; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none;
}
.cons-choose-back:hover { background: #f0fdfa; color: #0d9488; }
.cons-choose-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}
@media (min-width: 768px) {
    .cons-choose-grid { grid-template-columns: 1fr 1fr; }
}
.cons-choose-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 1.5rem 1.35rem;
    border-radius: 0.85rem;
    border: 2px solid #e5e7eb;
    background: #fff;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.12s;
    min-height: 14rem;
}
.cons-choose-card:hover {
    border-color: #0d9488;
    box-shadow: 0 8px 24px rgba(13, 148, 136, 0.12);
    transform: translateY(-2px);
}
.cons-choose-card-scan { border-top: 4px solid #0d9488; }
.cons-choose-card-select { border-top: 4px solid #6366f1; }
.cons-choose-icon { font-size: 1.75rem; line-height: 1; margin-bottom: 0.75rem; opacity: 0.85; }
.cons-choose-card-title { margin: 0 0 0.5rem; font-size: 1.125rem; font-weight: 700; color: #111827; }
.cons-choose-card-text { margin: 0; font-size: 0.875rem; color: #4b5563; line-height: 1.5; flex: 1; }
.cons-choose-cta { margin-top: 1.25rem; font-size: 0.875rem; font-weight: 700; color: #0d9488; }
.cons-choose-card-select .cons-choose-cta { color: #4f46e5; }
</style>
@endsection
