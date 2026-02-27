@extends('layouts.app')

@section('title', 'Tokens API')

@section('content')
<div class="api-tokens-page">
    <header class="api-tokens-hero">
        <div class="api-tokens-hero-inner">
            <div class="api-tokens-hero-text">
                <h1 class="api-tokens-hero-title">Tokens API</h1>
                <p class="api-tokens-hero-subtitle">Crea tokens para acceder a la API (app móvil, integraciones). Cada token se muestra una sola vez al crearlo.</p>
            </div>
        </div>
    </header>

    @if(session('new_token_plain'))
    <div class="api-tokens-alert api-tokens-alert-success">
        <p class="api-tokens-alert-title">Token creado — cópialo ahora</p>
        <p class="api-tokens-alert-hint">No se volverá a mostrar. Úsalo en el header: <code>Authorization: Bearer &lt;token&gt;</code></p>
        <div class="api-tokens-token-box">
            <code id="new-token-value">{{ session('new_token_plain') }}</code>
            <button type="button" class="api-tokens-copy-btn" data-copy-target="new-token-value">Copiar</button>
        </div>
    </div>
    @endif

    @if(session('success') && !session('new_token_plain'))
    <div class="api-tokens-alert api-tokens-alert-info">
        {{ session('success') }}
    </div>
    @endif

    <div class="api-tokens-card">
        <div class="api-tokens-card-header">
            <h2 class="api-tokens-card-title">Crear nuevo token</h2>
        </div>
        <div class="api-tokens-card-body">
            <form action="{{ route('api-tokens.store') }}" method="POST">
                @csrf
                <div class="api-tokens-field">
                    <label for="name" class="api-tokens-label">Nombre del token</label>
                    <input type="text" name="name" id="name" class="api-tokens-input" placeholder="Ej: App móvil, Integración X" value="{{ old('name') }}" required>
                    @error('name')
                    <p class="api-tokens-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="api-tokens-btn api-tokens-btn-primary">Crear token</button>
            </form>
        </div>
    </div>

    <div class="api-tokens-card">
        <div class="api-tokens-card-header">
            <h2 class="api-tokens-card-title">Tokens activos</h2>
        </div>
        <div class="api-tokens-card-body">
            @if($tokens->isEmpty())
            <p class="api-tokens-muted">No tienes tokens. Crea uno arriba para usar la API.</p>
            @else
            <ul class="api-tokens-list">
                @foreach($tokens as $token)
                <li class="api-tokens-list-item">
                    <span class="api-tokens-token-name">{{ $token->name }}</span>
                    <span class="api-tokens-token-meta">Creado {{ $token->created_at->format('d/m/Y H:i') }}</span>
                    <form action="{{ route('api-tokens.destroy', $token->id) }}" method="POST" class="api-tokens-form-inline" onsubmit="return confirm('¿Revocar este token? Quien lo use dejará de tener acceso.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="api-tokens-btn api-tokens-btn-danger api-tokens-btn-sm">Revocar</button>
                    </form>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    <div class="api-tokens-help">
        <h3 class="api-tokens-help-title">Uso de la API</h3>
        <p>Obtén un token con <code>POST /api/auth/token</code> (email + password) o creando uno aquí. Luego envía en cada petición:</p>
        <pre class="api-tokens-pre">Authorization: Bearer &lt;tu_token&gt;</pre>
        <p class="api-tokens-muted">El endpoint público <code>GET /api/public/tracking/{código}</code> no requiere token (consulta de tracking para clientes).</p>
    </div>
</div>

<style>
.api-tokens-page { padding: 1.5rem 0; max-width: 48rem; margin: 0 auto; width: 100%; }
.api-tokens-hero { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); border-radius: 1rem; padding: 1.5rem 1.5rem; margin-bottom: 1.5rem; }
.api-tokens-hero-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: #fff; }
.api-tokens-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); }
.api-tokens-alert { padding: 1rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1rem; }
.api-tokens-alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }
.api-tokens-alert-info { background: #e0f2fe; border: 1px solid #7dd3fc; color: #0c4a6e; }
.api-tokens-alert-title { font-weight: 600; margin: 0 0 0.35rem; }
.api-tokens-alert-hint { font-size: 0.875rem; margin: 0 0 0.5rem; }
.api-tokens-token-box { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; background: #fff; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid #0d9488; }
.api-tokens-token-box code { flex: 1; min-width: 0; font-size: 0.8125rem; word-break: break-all; }
.api-tokens-copy-btn { padding: 0.35rem 0.75rem; font-size: 0.8125rem; font-weight: 600; background: #0d9488; color: #fff; border: none; border-radius: 0.375rem; cursor: pointer; }
.api-tokens-copy-btn:hover { background: #0f766e; }
.api-tokens-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; margin-bottom: 1.5rem; overflow: hidden; }
.api-tokens-card-header { background: #f9fafb; padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb; }
.api-tokens-card-title { margin: 0; font-size: 1rem; font-weight: 600; color: #374151; }
.api-tokens-card-body { padding: 1.25rem; }
.api-tokens-field { margin-bottom: 1rem; }
.api-tokens-label { display: block; font-size: 0.8125rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; }
.api-tokens-input { width: 100%; max-width: 320px; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; }
.api-tokens-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.api-tokens-error { font-size: 0.875rem; color: #dc2626; margin-top: 0.25rem; }
.api-tokens-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.api-tokens-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.api-tokens-btn-primary:hover { background: #0f766e; color: #fff; }
.api-tokens-btn-danger { background: #fff; color: #dc2626; border-color: #dc2626; }
.api-tokens-btn-danger:hover { background: #fef2f2; color: #b91c1c; }
.api-tokens-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.api-tokens-form-inline { display: inline; }
.api-tokens-list { list-style: none; margin: 0; padding: 0; }
.api-tokens-list-item { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; }
.api-tokens-list-item:last-child { border-bottom: none; }
.api-tokens-token-name { font-weight: 600; color: #111827; }
.api-tokens-token-meta { font-size: 0.8125rem; color: #6b7280; }
.api-tokens-muted { color: #6b7280; font-size: 0.875rem; margin: 0; }
.api-tokens-help { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.25rem; }
.api-tokens-help-title { margin: 0 0 0.5rem; font-size: 1rem; font-weight: 600; color: #374151; }
.api-tokens-help p { margin: 0 0 0.5rem; font-size: 0.875rem; }
.api-tokens-pre { background: #1e293b; color: #e2e8f0; padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-size: 0.8125rem; margin: 0.5rem 0; overflow-x: auto; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.querySelector('.api-tokens-copy-btn');
    if (btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-copy-target');
            var el = id ? document.getElementById(id) : null;
            if (el) {
                navigator.clipboard.writeText(el.textContent).then(function() {
                    btn.textContent = 'Copiado';
                    setTimeout(function() { btn.textContent = 'Copiar'; }, 2000);
                });
            }
        });
    }
});
</script>
@endsection
