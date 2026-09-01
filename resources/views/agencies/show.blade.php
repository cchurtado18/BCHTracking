@extends('layouts.app')

@section('title', 'Detalle Agencia')

@section('content')
<div class="agency-page agency-show-page">
    <x-module-banner
        section="Administración"
        current="Ficha"
        title="{{ $agency->name }}"
        subtitle="{{ $agency->typeLabel() }} · Código {{ $agency->code }}. Datos de la cuenta, destinatarios y accesos."
        back-href="{{ route('agencies.index') }}"
        back-label="Volver a clientes"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('agencies.edit', $agency->id) }}" class="mb-btn mb-btn-primary">Editar</a>
            @if(!$agency->is_main)
                @if(($agency->preregistrations_count ?? 0) > 0)
                <span class="mb-btn mb-btn-secondary" style="opacity:.5;cursor:not-allowed;" title="No se puede eliminar: tiene {{ $agency->preregistrations_count }} paquete(s) asignado(s).">Eliminar</span>
                @else
                <form action="{{ route('agencies.destroy', $agency->id) }}" method="POST" onsubmit="return confirm('¿Eliminar la subagencia «{{ addslashes($agency->name) }}»? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="mb-btn mb-btn-danger">Eliminar</button>
                </form>
                @endif
            @endif
        </x-slot:actions>
    </x-module-banner>

    <div class="agency-show-grid">
        {{-- Información --}}
        <div class="agency-card">
            <div class="agency-card-header agency-table-header">
                <h2 class="agency-card-title">Información</h2>
            </div>
            <div class="agency-card-body">
                <dl class="agency-dl">
                    <div class="agency-dl-row">
                        <dt class="agency-dt">Código</dt>
                        <dd class="agency-dd agency-code">{{ $agency->code }}</dd>
                    </div>
                    <div class="agency-dl-row">
                        <dt class="agency-dt">Nombre</dt>
                        <dd class="agency-dd">{{ $agency->name }}</dd>
                    </div>
                    @if($agency->parent)
                    <div class="agency-dl-row">
                        <dt class="agency-dt">Pertenece a</dt>
                        <dd class="agency-dd">
                            <a href="{{ route('agencies.show', $agency->parent->id) }}" class="agency-link">{{ $agency->parent->name }}</a>
                            <span class="agency-muted"> ({{ $agency->parent->typeLabel() }})</span>
                        </dd>
                    </div>
                    @endif
                    @if($agency->phone)
                    <div class="agency-dl-row">
                        <dt class="agency-dt">Teléfono</dt>
                        <dd class="agency-dd">{{ $agency->phone }}</dd>
                    </div>
                    @endif
                    @if($agency->address)
                    <div class="agency-dl-row">
                        <dt class="agency-dt">Dirección</dt>
                        <dd class="agency-dd">{{ $agency->address }}</dd>
                    </div>
                    @endif
                    @if($agency->department)
                    <div class="agency-dl-row">
                        <dt class="agency-dt">Departamento</dt>
                        <dd class="agency-dd">{{ $agency->department }}</dd>
                    </div>
                    @endif
                    @if($agency->logo_url)
                    <div class="agency-dl-row">
                        <dt class="agency-dt">Logo</dt>
                        <dd class="agency-dd"><img src="{{ $agency->logo_url }}" alt="Logo" class="agency-logo-img"></dd>
                    </div>
                    @endif
                    <div class="agency-dl-row">
                        <dt class="agency-dt">Estado</dt>
                        <dd class="agency-dd">
                            @if($agency->is_active)
                            <span class="agency-badge agency-badge-success">Activa</span>
                            @else
                            <span class="agency-badge agency-badge-danger">Inactiva</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                @if($agency->users->isNotEmpty())
                <div class="agency-users-block">
                    <h3 class="agency-users-title">Usuario de acceso</h3>
                    <p class="agency-users-hint">Credenciales para que la agencia inicie sesión y vea sus paquetes.</p>
                    @if($errors->has('password'))
                    <p class="agency-field-error">{{ $errors->first('password') }}</p>
                    @endif
                    <ul class="agency-users-list">
                        @foreach($agency->users as $agencyUser)
                        <li class="agency-user-item">
                            <div class="agency-user-head">
                                <span><strong>Correo:</strong> <span class="agency-code">{{ $agencyUser->email }}</span> <span class="agency-muted">({{ $agencyUser->name }})</span></span>
                                @auth
                                @if(auth()->user()->is_admin)
                                <a href="{{ route('users.edit', $agencyUser) }}" class="agency-link">Editar usuario</a>
                                @endif
                                @endauth
                            </div>
                            @auth
                            @if(auth()->user()->is_admin)
                            <form action="{{ route('agencies.users.reset-password', [$agency->id, $agencyUser->id]) }}" method="POST" class="agency-reset-form">
                                @csrf
                                <p class="agency-reset-label">Restablecer contraseña (si la olvidó)</p>
                                <div class="agency-reset-row">
                                    <input type="password" name="password" id="password-{{ $agencyUser->id }}" required minlength="8" placeholder="Nueva contraseña" class="agency-input agency-input-sm">
                                    <input type="password" name="password_confirmation" id="password_confirmation-{{ $agencyUser->id }}" required minlength="8" placeholder="Confirmar" class="agency-input agency-input-sm">
                                    <button type="submit" class="agency-btn agency-btn-primary agency-btn-sm">Guardar contraseña</button>
                                </div>
                            </form>
                            @endif
                            @endauth
                        </li>
                        @endforeach
                    </ul>
                    @if(!auth()->user() || !auth()->user()->is_admin)
                    <p class="agency-alert agency-alert-amber">La contraseña no se muestra por seguridad. Si la olvidaron, use «¿Olvidó su contraseña?» en la pantalla de login.</p>
                    @endif
                </div>
                @else
                <div class="agency-users-block">
                    <p class="agency-muted">Esta agencia no tiene usuario de acceso creado. Puede crear un usuario desde <a href="{{ route('users.index') }}" class="agency-link">Usuarios</a> asignándole esta agencia.</p>
                </div>
                @endif

                <div class="agency-toggle-block">
                    <form action="{{ route('agencies.toggle', $agency->id) }}" method="POST" class="agency-form-inline">
                        @csrf
                        <button type="submit" class="agency-btn agency-btn-secondary">{{ $agency->is_active ? 'Desactivar' : 'Activar' }}</button>
                    </form>
                </div>
            </div>
        </div>

        @auth
        @if(auth()->user()->is_admin)
        {{-- Contabilidad (solo lectura) --}}
        <div class="agency-card">
            <div class="agency-card-header agency-table-header">
                <h2 class="agency-card-title">Contabilidad</h2>
            </div>
            <div class="agency-card-body">
                <dl class="agency-dl">
                    <div class="agency-dl-row">
                        <dt>Saldo pendiente (CxC)</dt>
                        <dd>
                            <strong>${{ number_format($openBalance, 2) }}</strong>
                            @if($openBalance > 0)
                            · <a href="{{ route('accounting.receivables.show', $agency->id) }}" class="agency-link">Ver estado de cuenta</a>
                            @endif
                        </dd>
                    </div>
                    <div class="agency-dl-row">
                        <dt>Crédito máximo</dt>
                        <dd>{{ $agency->credit_limit_usd !== null ? '$'.number_format((float) $agency->credit_limit_usd, 2) : 'Sin límite definido' }}</dd>
                    </div>
                    <div class="agency-dl-row">
                        <dt>Días de crédito</dt>
                        <dd>{{ $agency->credit_days !== null ? $agency->credit_days.' días' : '—' }}</dd>
                    </div>
                    <div class="agency-dl-row">
                        <dt>RUC / fiscal</dt>
                        <dd>{{ $agency->tax_id ?? '—' }}</dd>
                    </div>
                    <div class="agency-dl-row">
                        <dt>Contacto de cobranza</dt>
                        <dd>{{ $agency->billing_contact_name ?? '—' }}{{ $agency->billing_contact_phone ? ' · '.$agency->billing_contact_phone : '' }}</dd>
                    </div>
                    <div class="agency-dl-row">
                        <dt>Correo de facturación</dt>
                        <dd>{{ $agency->billingEmail() ?? '—' }}</dd>
                    </div>
                    <div class="agency-dl-row">
                        <dt>Tarifas vigentes</dt>
                        <dd>
                            @forelse($currentRates as $rate)
                            <span class="agency-code">{{ \App\Support\ServiceType::label($rate->service_type) }}: ${{ number_format((float) $rate->price_per_lb, 2) }}/{{ \App\Support\ServiceType::unit($rate->service_type) }}</span>{{ $loop->last ? '' : ' · ' }}
                            @empty
                            <span class="agency-muted">Sin tarifa. <a href="{{ route('accounting.rates.show', $agency) }}" class="agency-link">Definir precios</a></span>
                            @endforelse
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
        @endif
        @endauth

        @if($agency->canHaveChildren())
        <div class="agency-card agency-card-subagencias">
            <div class="agency-card-header agency-table-header">
                <h2 class="agency-card-title">Cuentas hijas ({{ $agency->children->count() }})</h2>
            </div>
            <div class="agency-card-body">
                <p class="agency-hint">Subagencias y clientes que pertenecen a <strong>{{ $agency->name }}</strong>.</p>
                @if($agency->children->isNotEmpty())
                <ul class="agency-subagencias-list">
                    @foreach($agency->children as $child)
                    <li class="agency-subagencia-item">
                        <div class="agency-subagencia-info">
                            <a href="{{ route('agencies.show', $child->id) }}" class="agency-subagencia-name">{{ $child->name }}</a>
                            <span class="agency-code agency-subagencia-code">{{ $child->code }}</span>
                            <span class="agency-muted">· {{ $child->typeLabel() }} · {{ $child->clients_count }} {{ $child->clients_count === 1 ? 'destinatario' : 'destinatarios' }}</span>
                        </div>
                        <div class="agency-subagencia-actions">
                            <a href="{{ route('agencies.show', $child->id) }}" class="agency-btn agency-btn-sm agency-btn-outline-secondary">Ver</a>
                            <a href="{{ route('agency-clients.create', $child->id) }}" class="agency-btn agency-btn-sm agency-btn-primary">+ Agregar cliente</a>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="agency-muted">No hay cuentas hijas. Cree una desde <a href="{{ route('agencies.create') }}" class="agency-link">Clientes</a> y seleccione «{{ $agency->name }}» como padre.</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Clientes (de esta agencia o subagencia) --}}
        <div class="agency-card">
            <div class="agency-card-header agency-table-header">
                <h2 class="agency-card-title">Destinatarios ({{ $agency->clients->count() }})</h2>
                <a href="{{ route('agency-clients.create', $agency->id) }}" class="agency-btn agency-btn-sm agency-btn-primary">+ Agregar</a>
            </div>
            <div class="agency-card-body">
                @if($agency->is_main)
                <p class="agency-hint">Los clientes se agregan por subagencia. Use «Ver» en cada subagencia de arriba y allí «+ Agregar» para que el cliente quede asignado a esa subagencia.</p>
                @if($agency->clients->count() > 0)
                <p class="agency-muted">Esta agencia principal tiene {{ $agency->clients->count() }} cliente(s) asignados directamente.</p>
                @endif
                @endif
                @if($agency->clients->count() > 0)
                <div class="agency-clients-list">
                    @foreach($agency->clients as $client)
                    <div class="agency-client-item">
                        <div class="agency-client-info">
                            <div class="agency-client-name">{{ $client->full_name }}</div>
                            @if($client->phone)
                            <div class="agency-client-meta">{{ $client->phone }}</div>
                            @endif
                        </div>
                        <div class="agency-client-actions">
                            <a href="{{ route('agency-clients.show', $client->id) }}" class="agency-link">Ver</a>
                            @if($client->is_active)
                            <span class="agency-badge agency-badge-success">Activo</span>
                            @else
                            <span class="agency-badge agency-badge-danger">Inactivo</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @elseif(!$agency->is_main)
                <p class="agency-muted">No hay clientes registrados para esta subagencia. Al hacer clic en «+ Agregar», el nuevo cliente quedará asignado a <strong>{{ $agency->name }}</strong>.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.agency-show-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.agency-show-page .agency-hero {
    background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}
.agency-show-page .agency-hero-title { color: #fff; margin: 0; font-size: 1.75rem; font-weight: 700; }
.agency-show-page .agency-hero-subtitle { color: rgba(255,255,255,0.9); margin: 0.35rem 0 0; font-size: 0.9375rem; }
.agency-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.agency-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.agency-hero-btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; background: #fff; color: #0A2D6F; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none; }
.agency-hero-btn:hover { background: #F4F8FD; color: #0A2D6F; }
.agency-hero-btn-outline { background: transparent; color: rgba(255,255,255,0.95); border-color: rgba(255,255,255,0.6); }
.agency-hero-btn-outline:hover { background: rgba(255,255,255,0.15); color: #fff; }
.agency-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.agency-btn-primary { background: #0A2D6F; color: #fff; border-color: #0A2D6F; }
.agency-btn-primary:hover { background: #0A2D6F; color: #fff; }
.agency-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.agency-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.agency-btn-danger { background: #dc2626; color: #fff; border-color: #dc2626; }
.agency-btn-danger:hover { background: #b91c1c; color: #fff; }
.agency-btn-disabled { background: #f3f4f6; color: #9ca3af; border-color: #e5e7eb; cursor: not-allowed; pointer-events: none; }
.agency-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.agency-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.agency-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.agency-card-header.agency-table-header { background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%); }
.agency-card-header.agency-table-header .agency-card-title { color: #fff; }
.agency-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.agency-card-body { padding: 1.25rem; }
.agency-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.agency-badge-success { background: #E8EEF8; color: #0A2D6F; }
.agency-badge-danger { background: #fee2e2; color: #b91c1c; }
.agency-code { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; }
.agency-muted { color: #6b7280; font-size: 0.875rem; }
.agency-field-error { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
.agency-input { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; width: 100%; }
.agency-input:focus { outline: none; border-color: #0A2D6F; box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.agency-alert { padding: 0.5rem 0.75rem; border-radius: 0.5rem; margin-top: 0.5rem; font-size: 0.875rem; border: 1px solid; }
.agency-show-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
@media (min-width: 992px) { .agency-show-grid { grid-template-columns: 1fr 1fr; } }
.agency-dl { margin: 0; }
.agency-dl-row { margin-bottom: 1rem; }
.agency-dt { font-size: 0.8125rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem; }
.agency-dd { margin: 0; font-size: 0.9375rem; color: #111827; }
.agency-logo-img { height: 3.5rem; width: auto; max-width: 200px; object-fit: contain; }
.agency-users-block { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.agency-users-title { font-size: 0.9375rem; font-weight: 600; margin: 0 0 0.25rem; }
.agency-users-hint { font-size: 0.75rem; color: #6b7280; margin-bottom: 0.75rem; }
.agency-users-list { list-style: none; padding: 0; margin: 0; }
.agency-user-item { padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #fafafa; margin-bottom: 0.5rem; font-size: 0.875rem; }
.agency-user-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem; }
.agency-link { color: #0A2D6F; text-decoration: none; font-weight: 500; }
.agency-link:hover { text-decoration: underline; }
.agency-reset-form { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; }
.agency-reset-label { font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.5rem; }
.agency-reset-row { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.agency-reset-row .agency-input { min-width: 140px; flex: 1; }
.agency-input-sm { padding: 0.35rem 0.5rem; font-size: 0.8125rem; }
.agency-alert-amber { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
.agency-toggle-block { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.agency-clients-list { max-height: 24rem; overflow-y: auto; }
.agency-client-item { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; }
.agency-client-name { font-weight: 600; color: #111827; font-size: 0.875rem; }
.agency-client-meta { font-size: 0.75rem; color: #6b7280; margin-top: 0.2rem; }
.agency-client-actions { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.agency-form-inline { display: inline; }
.agency-hint { font-size: 0.875rem; color: #6b7280; margin: 0 0 0.75rem; }
.agency-btn-outline-secondary { background: #fff; color: #6b7280; border-color: #d1d5db; }
.agency-btn-outline-secondary:hover { background: #f9fafb; color: #374151; }
.agency-card-subagencias { margin-bottom: 1.5rem; }
.agency-subagencias-list { list-style: none; padding: 0; margin: 0; }
.agency-subagencia-item { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; background: #fafafa; }
.agency-subagencia-info { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; min-width: 0; }
.agency-subagencia-name { font-weight: 600; color: #111827; text-decoration: none; }
.agency-subagencia-name:hover { color: #0A2D6F; text-decoration: underline; }
.agency-subagencia-code { font-size: 0.8125rem; }
.agency-subagencia-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; flex-shrink: 0; }
</style>
@endsection
