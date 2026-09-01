@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="users-page">
    <x-module-banner section="Administración" current="Usuarios" title="Usuarios PrimeTrack" subtitle="Crear y gestionar usuarios del sistema.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('users.create') }}" class="mb-btn mb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nuevo usuario
            </a>
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="users-alert users-alert-success">{{ session('success') }}</div>
    @endif

    <div class="users-stats">
        <div class="users-stat-card users-stat-total">
            <span class="users-stat-label">Total</span>
            <span class="users-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="users-stat-card users-stat-admin">
            <span class="users-stat-label">Administradores</span>
            <span class="users-stat-value">{{ number_format($statsAdmin ?? 0) }}</span>
        </div>
        <div class="users-stat-card users-stat-regular">
            <span class="users-stat-label">Usuarios</span>
            <span class="users-stat-value">{{ number_format($statsRegular ?? 0) }}</span>
        </div>
    </div>

    <div class="users-card users-filters-card">
        <div class="users-card-header">
            <h2 class="users-card-title">Filtros</h2>
        </div>
        <div class="users-card-body">
            <form method="GET" action="{{ route('users.index') }}" class="users-filters-form">
                <div class="users-filters-grid">
                    <div class="users-field users-field-search">
                        <label class="users-label">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nombre o correo" class="users-input">
                    </div>
                </div>
                <div class="users-filters-actions">
                    <button type="submit" class="users-btn users-btn-primary">Aplicar filtros</button>
                    <a href="{{ route('users.index') }}" class="users-btn users-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="users-card users-table-card">
        <div class="users-card-header users-table-header">
            <h2 class="users-card-title">Listado de usuarios</h2>
            <span class="users-card-badge">{{ $users->total() }} {{ $users->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th class="users-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td class="users-name">{{ $u->name }}</td>
                        <td class="users-muted">{{ $u->email }}</td>
                        <td>
                            @if($u->is_admin)
                            <span class="users-badge users-badge-admin">Administrador</span>
                            @else
                            <span class="users-badge users-badge-user">Usuario</span>
                            @endif
                        </td>
                        <td class="users-actions">
                            <a href="{{ route('users.edit', $u) }}" class="users-btn users-btn-sm users-btn-outline-primary">Editar</a>
                            @if($u->id !== auth()->id())
                            <form action="{{ route('users.destroy', $u) }}" method="POST" class="users-form-inline" onsubmit="return confirm('¿Eliminar al usuario «{{ addslashes($u->name) }}»?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="users-btn users-btn-sm users-btn-outline-danger">Eliminar</button>
                            </form>
                            @else
                            <span class="users-btn users-btn-sm users-btn-disabled" title="No puedes eliminar tu propio usuario">Eliminar</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="users-empty">
                            <p class="users-empty-text">No hay usuarios.</p>
                            <a href="{{ route('users.create') }}" class="users-btn users-btn-primary">Crear uno</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->total() > 0)
        <div class="users-card-footer">
            <span class="users-pagination-info">{{ $users->firstItem() }} – {{ $users->lastItem() }} de {{ $users->total() }}</span>
            @if($users->hasPages())
            <div class="users-pagination-links">{{ $users->links() }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.users-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.users-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.users-alert-success { background: #F4F8FD; border: 1px solid #C5D4EB; color: #0A2D6F; }
.users-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.users-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.users-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.users-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.users-stat-total { border-left: 4px solid #0A2D6F; }
.users-stat-admin { border-left: 4px solid #0A2D6F; }
.users-stat-regular { border-left: 4px solid #0A2D6F; }
.users-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.users-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.users-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.users-card-body { padding: 1.25rem; }
.users-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }
.users-card-badge { font-size: 0.8125rem; color: #6b7280; }
.users-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.users-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.users-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.users-input { display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827; }
.users-input:focus { outline: none; border-color: #0A2D6F; box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.users-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.users-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.users-btn-primary { background: #0A2D6F; color: #fff; border-color: #0A2D6F; }
.users-btn-primary:hover { background: #0A2D6F; border-color: #0A2D6F; color: #fff; }
.users-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.users-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.users-btn-outline-primary { background: #fff; color: #0A2D6F; border-color: #0A2D6F; }
.users-btn-outline-primary:hover { background: #E8EEF8; color: #0A2D6F; }
.users-btn-outline-danger { background: #fff; color: #dc2626; border-color: #dc2626; }
.users-btn-outline-danger:hover { background: #fef2f2; color: #b91c1c; }
.users-btn-disabled { background: #f9fafb; color: #9ca3af; border-color: #e5e7eb; cursor: not-allowed; pointer-events: none; }
.users-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.users-form-inline { display: inline; }
.users-table-header { background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%); }
.users-table-header .users-card-title { color: #fff; }
.users-table-header .users-card-badge { color: rgba(255,255,255,0.9); }
.users-table-wrap { overflow-x: auto; }
.users-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.users-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.users-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.users-table tbody tr:hover { background: #f9fafb; }
.users-name { font-weight: 600; color: #111827; }
.users-muted { color: #6b7280; }
.users-th-actions { text-align: right; }
.users-actions { text-align: right; white-space: nowrap; }
.users-actions .users-btn, .users-actions form { margin-left: 0.25rem; }
.users-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.users-badge-admin { background: #E8EEF8; color: #0A2D6F; }
.users-badge-user { background: #e5e7eb; color: #374151; }
.users-empty { text-align: center; padding: 3rem 1rem !important; }
.users-empty-text { margin: 0 0 0.75rem; color: #6b7280; }
.users-pagination-info { font-weight: 500; }
.users-pagination-links { display: flex; align-items: center; }
.users-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.users-pagination-links a, .users-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.users-pagination-links a:hover { background: #f3f4f6; color: #0A2D6F; }
.users-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.users-pagination-links .active span { background: #0A2D6F; color: #fff; border-color: #0A2D6F; }
</style>
@endsection
