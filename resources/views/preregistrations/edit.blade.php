@extends('layouts.app')

@section('title', 'Editar Preregistro #' . $preregistration->id)

@section('content')
<div class="preregs-page preregs-form-page preregs-edit-page">
    <x-module-banner
        section="General"
        current="Editar preregistro"
        title="Editar preregistro #{{ $preregistration->id }}"
        subtitle="Actualice los datos del paquete. La foto permanece a la derecha como referencia."
        back-href="{{ route('preregistrations.show', $preregistration->id) }}"
        back-label="Volver al envío"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('preregistrations.index') }}" class="mb-btn mb-btn-secondary">Lista de preregistros</a>
        </x-slot:actions>
    </x-module-banner>

    @if($errors->any())
    <div class="preregs-alert preregs-alert-danger">
        <p class="preregs-alert-title">No se pudo actualizar el preregistro:</p>
        <ul class="preregs-alert-list">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="preregs-alert preregs-alert-success" role="status">
        <p class="preregs-alert-title">{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="preregs-alert preregs-alert-danger" role="alert">
        <p class="preregs-alert-title">{{ session('error') }}</p>
    </div>
    @endif

    <div class="preregs-edit-layout has-photo">
        <div class="preregs-edit-main-col">
            <div class="preregs-card preregs-form-card">
                <div class="preregs-card-header preregs-form-header preregs-form-header--sheet">
                    <div class="preregs-form-header-text">
                        <div class="preregs-form-header-row">
                            <div>
                                <h2 class="preregs-card-title">
                                    <span class="preregs-card-title-icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
                                    </span>
                                    Datos del preregistro
                                </h2>
                                <p class="preregs-form-header-desc">
                                    @if($preregistration->warehouse_code)
                                        Código WRH: <strong>{{ $preregistration->warehouse_code }}</strong>
                                        · {{ $preregistration->intake_type === 'DROP_OFF' ? 'Drop Off' : 'Courier' }}
                                    @else
                                        Completa o corrige la información del paquete.
                                    @endif
                                </p>
                            </div>
                            <ol class="preregs-steps" aria-label="Secciones del formulario">
                                <li class="preregs-step is-active"><span>1</span> Cuenta</li>
                                <li class="preregs-step"><span>2</span> Paquete</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="preregs-card-body preregs-form-body">
                    <form action="{{ route('preregistrations.update', $preregistration->id) }}" method="POST" class="preregs-create-formwrap">
                        @csrf
                        @method('PUT')

                        <div class="preregs-form-panel">
                            <div class="preregs-form-panel-head">
                                <span class="preregs-form-panel-num">1</span>
                                <div>
                                    <h3 class="preregs-form-panel-title">Cuenta</h3>
                                    <p class="preregs-form-panel-sub">Subagencia o cliente propio de SkyLink One.</p>
                                </div>
                            </div>
                            <div class="preregs-create-grid preregs-create-grid--root">
                                @php
                                    $current = $agencies->firstWhere('id', (int) old('agency_id', $preregistration->agency_id));
                                    $currentIsSloClient = $current && $current->isDirectClient();
                                    $selectedPartnerId = $currentIsSloClient ? $current->parent_agency_id : ($current->id ?? null);
                                @endphp
                                <div class="preregs-field preregs-field--full">
                                    <label for="partner_agency_id" class="preregs-field-label">Cuenta (subagencia o SkyLink One) <span class="preregs-req">*</span></label>
                                    <select id="partner_agency_id" class="preregs-input preregs-select">
                                        <option value="">Seleccione…</option>
                                        @foreach($partnerAgencies as $agency)
                                        <option value="{{ $agency->id }}" data-slo="{{ $agency->isRootAccount() ? '1' : '0' }}" @selected((string) $selectedPartnerId === (string) $agency->id)>
                                            {{ $agency->code ? $agency->code . ' - ' : '' }}{{ $agency->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="preregs-field preregs-field--full" id="slo_client_wrap" style="{{ $currentIsSloClient ? '' : 'display:none;' }}">
                                    <label for="slo_client_id" class="preregs-field-label">Cliente de SkyLink One <span class="preregs-req">*</span></label>
                                    <select id="slo_client_id" class="preregs-input preregs-select">
                                        <option value="">— Seleccione el cliente SLO —</option>
                                        @foreach($sloClients as $client)
                                        <option value="{{ $client->id }}" @selected($currentIsSloClient && (string) old('agency_id', $preregistration->agency_id) === (string) $client->id)>
                                            {{ $client->code }} - {{ $client->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <input type="hidden" name="agency_id" id="agency_id" value="{{ old('agency_id', $preregistration->agency_id) }}" required>
                                @error('agency_id')
                                <p class="preregs-field-error preregs-field--full">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="preregs-form-panel">
                            <div class="preregs-form-panel-head">
                                <span class="preregs-form-panel-num">2</span>
                                <div>
                                    <h3 class="preregs-form-panel-title">Detalle del paquete</h3>
                                    <p class="preregs-form-panel-sub">Etiqueta, servicio, tracking y peso. Usa la foto a la derecha como guía.</p>
                                </div>
                            </div>
                            <div class="preregs-create-grid preregs-create-grid--nested">
                                <div class="preregs-field">
                                    <label for="label_name" class="preregs-field-label">Nombre en etiqueta <span class="preregs-req">*</span></label>
                                    <input type="text" name="label_name" id="label_name" value="{{ old('label_name', $preregistration->label_name) }}" required class="preregs-input">
                                    @error('label_name')
                                    <p class="preregs-field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="preregs-field">
                                    <label for="service_type" class="preregs-field-label">Tipo de servicio <span class="preregs-req">*</span></label>
                                    <select name="service_type" id="service_type" required class="preregs-input preregs-select">
                                        <option value="AIR" {{ old('service_type', $preregistration->service_type) == 'AIR' ? 'selected' : '' }}>Aéreo</option>
                                        <option value="SEA" {{ old('service_type', $preregistration->service_type) == 'SEA' ? 'selected' : '' }}>Marítimo</option>
                                        <option value="CFT" {{ old('service_type', $preregistration->service_type) == 'CFT' ? 'selected' : '' }}>Pie cúbico</option>
                                    </select>
                                    @error('service_type')
                                    <p class="preregs-field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="preregs-field">
                                    <label for="tracking_external" class="preregs-field-label">Tracking externo</label>
                                    <input type="text" name="tracking_external" id="tracking_external" value="{{ old('tracking_external', $preregistration->tracking_external) }}" class="preregs-input">
                                    @error('tracking_external')
                                    <p class="preregs-field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="preregs-field">
                                    <label for="intake_weight_lbs" class="preregs-field-label">Peso (lb) <span class="preregs-req">*</span></label>
                                    <div class="preregs-input-affix">
                                        <input type="number" step="0.01" name="intake_weight_lbs" id="intake_weight_lbs" value="{{ old('intake_weight_lbs', $preregistration->intake_weight_lbs) }}" required class="preregs-input">
                                        <span class="preregs-affix">lb</span>
                                    </div>
                                    @error('intake_weight_lbs')
                                    <p class="preregs-field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                @if($preregistration->intake_type === 'DROP_OFF')
                                <div class="preregs-field">
                                    <label for="dimension" class="preregs-field-label">Dimensión</label>
                                    <input type="text" name="dimension" id="dimension" value="{{ old('dimension', $preregistration->dimension) }}" placeholder="ej: 10 x 8 x 5 in" class="preregs-input">
                                    @error('dimension')
                                    <p class="preregs-field-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                @endif

                                <div class="preregs-field preregs-field--full">
                                    <label for="description" class="preregs-field-label">Descripción del contenido <span class="preregs-opt">(opcional)</span></label>
                                    <textarea name="description" id="description" maxlength="500" rows="3" placeholder="Ej: Ropa, electrónicos, documentos…" class="preregs-input preregs-textarea">{{ old('description', $preregistration->description) }}</textarea>
                                    @error('description')
                                    <p class="preregs-field-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="preregs-form-actions">
                            <a href="{{ route('preregistrations.show', $preregistration->id) }}" class="preregs-btn preregs-btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                Cancelar
                            </a>
                            <button type="submit" class="preregs-btn preregs-btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 3v4H7V3m5 13a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
                                Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <aside class="preregs-edit-photo-col">
            <div class="preregs-side-card preregs-edit-photo-card">
                <h3 class="preregs-side-title">
                    <span class="preregs-side-icon-wrap" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/></svg>
                    </span>
                    Fotos del paquete ({{ $preregistration->photos->count() }})
                </h3>
                @if($preregistration->photos->isEmpty())
                <div class="preregs-photo-empty">
                    <div class="preregs-photo-empty-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                    </div>
                    <p class="preregs-photo-empty-title">Este preregistro no tiene fotos</p>
                    <p class="preregs-photo-empty-text">Puedes agregar fotos desde la vista de detalle del preregistro.</p>
                    <a href="{{ route('preregistrations.show', $preregistration->id) }}" class="preregs-btn preregs-btn-secondary preregs-photo-empty-btn">Ir al detalle</a>
                </div>
                @else
                @if($preregistration->photos->count() > 1)
                <p class="preregs-edit-photo-order-hint">Usa las flechas para cambiar el orden de las fotos.</p>
                @endif
                <div class="preregs-edit-photos-list">
                    @php $photoTotal = $preregistration->photos->count(); @endphp
                    @foreach($preregistration->photos as $idx => $photo)
                    <div class="preregs-photo-wrap">
                        <a href="{{ $photo->url }}" target="_blank" class="preregs-photo-link-block" title="Abrir foto {{ $idx + 1 }} en tamaño completo">
                            <img src="{{ $photo->url }}" alt="Foto del paquete {{ $idx + 1 }}" class="preregs-photo-img">
                        </a>
                        @if($photoTotal > 1)
                        <div class="preregs-photo-order-row" role="group" aria-label="Orden de la foto {{ $idx + 1 }}">
                            <form method="POST" action="{{ route('preregistrations.photos.move', ['id' => $preregistration->id, 'photo' => $photo->id]) }}" class="preregs-photo-order-form">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="preregs-photo-order-btn" title="Mover arriba" aria-label="Mover foto {{ $idx + 1 }} hacia arriba" @if($idx === 0) disabled @endif>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m18 15-6-6-6 6"/></svg>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('preregistrations.photos.move', ['id' => $preregistration->id, 'photo' => $photo->id]) }}" class="preregs-photo-order-form">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="preregs-photo-order-btn" title="Mover abajo" aria-label="Mover foto {{ $idx + 1 }} hacia abajo" @if($idx === $photoTotal - 1) disabled @endif>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                </button>
                            </form>
                        </div>
                        @endif
                        <p class="preregs-photo-link-wrap">
                            <a href="{{ $photo->url }}" target="_blank" class="preregs-link">Ver foto {{ $idx + 1 }} completa</a>
                        </p>
                    </div>
                    @endforeach
                </div>
                @if($preregistration->status === 'PHOTO_PENDING')
                <p class="preregs-edit-photo-hint">Captura rápida: completa los datos usando la foto como referencia.</p>
                @endif
                @endif
            </div>
        </aside>
    </div>
</div>

<style>
.preregs-edit-page {
    --pt-navy: #0A2D6F;
    --pt-blue: #1E4FA8;
    --pt-soft: #F4F8FD;
    --pt-line: #E8EEF8;
    --pt-border: #C5D4EB;
    --pt-muted: #5E6168;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}

.preregs-edit-page .preregs-hero {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 92% 18%, rgba(255,255,255,.14), transparent 28%),
        linear-gradient(135deg, #0A2D6F 0%, #143A8C 48%, #1E4FA8 100%);
    border-radius: 1rem;
    padding: 1.25rem 1.45rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 10px 28px rgba(10, 45, 111, 0.22);
}
.preregs-edit-page .preregs-hero-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.preregs-edit-page .preregs-hero-eyebrow {
    margin: 0 0 0.35rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,.72);
}
.preregs-edit-page .preregs-hero-title {
    margin: 0;
    font-size: clamp(1.55rem, 2.2vw, 1.9rem);
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.03em;
    line-height: 1.15;
}
.preregs-edit-page .preregs-hero-subtitle {
    margin: 0.4rem 0 0;
    font-size: 0.92rem;
    color: rgba(255,255,255,.86);
    max-width: 46rem;
    line-height: 1.45;
}
.preregs-edit-page .preregs-hero-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; }
.preregs-edit-page .preregs-hero-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.58rem 1.05rem;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    text-decoration: none;
    white-space: nowrap;
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease, color .15s ease;
}
.preregs-edit-page .preregs-hero-btn:hover { transform: translateY(-1px); }
.preregs-edit-page .preregs-hero-btn-primary {
    background: #fff;
    color: var(--pt-navy);
    box-shadow: 0 4px 14px rgba(0,0,0,.14);
}
.preregs-edit-page .preregs-hero-btn-primary:hover { background: #E8EEF8; }
.preregs-edit-page .preregs-hero-btn-secondary {
    background: rgba(255,255,255,.12);
    color: #fff;
    border-color: rgba(255,255,255,.35);
}
.preregs-edit-page .preregs-hero-btn-secondary:hover {
    background: rgba(255,255,255,.2);
    border-color: #fff;
}

.preregs-alert { padding: 0.9rem 1.1rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.preregs-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.preregs-alert-success { background: #F4F8FD; border: 1px solid #C5D4EB; color: #0A2D6F; }
.preregs-alert-title { font-weight: 700; margin-bottom: 0.3rem; }
.preregs-alert-list { margin: 0; padding-left: 1.2rem; }

/* Campos izquierda + foto derecha (sticky) */
.preregs-edit-layout {
    display: grid;
    gap: 1.15rem;
    align-items: start;
}
.preregs-edit-layout.has-photo {
    grid-template-columns: minmax(0, 1fr) minmax(21rem, 30rem);
}
@media (min-width: 1500px) {
    .preregs-edit-layout.has-photo {
        grid-template-columns: minmax(0, 1fr) minmax(26rem, 35rem);
    }
}
.preregs-edit-layout.no-photo {
    grid-template-columns: minmax(0, 1fr);
    max-width: 52rem;
}
.preregs-edit-main-col { min-width: 0; }
.preregs-edit-photo-col {
    position: sticky;
    top: 1rem;
    align-self: start;
}

.preregs-edit-page .preregs-card {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid var(--pt-line);
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
    overflow: hidden;
    margin-bottom: 0 !important;
}
.preregs-edit-page .preregs-card-header.preregs-form-header {
    padding: 0;
    border-bottom: 1px solid var(--pt-line);
    background: linear-gradient(180deg, #fff 0%, #FBFCFE 100%);
}
.preregs-edit-page .preregs-form-header-text { padding: 1rem 1.15rem 0.95rem; }
.preregs-edit-page .preregs-form-header-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.preregs-edit-page .preregs-card-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 800;
    color: #0f172a;
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    letter-spacing: -0.02em;
}
.preregs-edit-page .preregs-card-title-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 0.55rem;
    background: var(--pt-soft);
    color: var(--pt-navy);
    border: 1px solid var(--pt-border);
    flex-shrink: 0;
}
.preregs-edit-page .preregs-form-header-desc {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: var(--pt-muted);
    line-height: 1.45;
}
.preregs-edit-page .preregs-steps {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}
.preregs-edit-page .preregs-step {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.28rem 0.65rem 0.28rem 0.35rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
}
.preregs-edit-page .preregs-step span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 999px;
    background: #fff;
    color: #64748b;
    border: 1px solid #e2e8f0;
    font-size: 0.68rem;
}
.preregs-edit-page .preregs-step.is-active {
    background: var(--pt-soft);
    color: var(--pt-navy);
}
.preregs-edit-page .preregs-step.is-active span {
    background: var(--pt-navy);
    color: #fff;
    border-color: var(--pt-navy);
}

.preregs-edit-page .preregs-form-body { padding: 1rem 1.15rem 1.15rem; }
.preregs-edit-page .preregs-create-formwrap { margin: 0; max-width: none; width: 100%; }

.preregs-edit-page .preregs-form-panel {
    margin-bottom: 0.45rem;
    padding: 0.75rem 0.9rem 0.8rem;
    background: #fff;
    border: 1px solid var(--pt-line);
    border-radius: 0.75rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}
.preregs-edit-page .preregs-form-panel-head {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    margin-bottom: 0.65rem;
}
.preregs-edit-page .preregs-form-panel-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 0.55rem;
    background: linear-gradient(135deg, var(--pt-navy), var(--pt-blue));
    color: #fff;
    font-size: 0.78rem;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(10, 45, 111, 0.22);
}
.preregs-edit-page .preregs-form-panel-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}
.preregs-edit-page .preregs-form-panel-sub {
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: var(--pt-muted);
    line-height: 1.4;
}

.preregs-edit-page .preregs-create-grid--root,
.preregs-edit-page .preregs-create-grid--nested {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.55rem 0.85rem;
    align-items: start;
}
.preregs-edit-page .preregs-field--full { grid-column: 1 / -1; }
.preregs-edit-page .preregs-field-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.28rem;
}
.preregs-edit-page .preregs-req { color: #D64545; font-weight: 800; }
.preregs-edit-page .preregs-opt { color: #94a3b8; font-weight: 500; font-size: 0.74rem; }
.preregs-edit-page .preregs-input {
    width: 100%;
    padding: 0.72rem 0.9rem;
    font-size: 0.9375rem;
    border: 1px solid #D8DCE2;
    border-radius: 0.65rem;
    background: #fff;
    color: #0f172a;
    box-sizing: border-box;
    transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}
.preregs-edit-page .preregs-textarea { resize: vertical; min-height: 5.4rem; line-height: 1.45; }
.preregs-edit-page .preregs-input:hover { border-color: #9BB5D9; background: #fcfdff; }
.preregs-edit-page .preregs-input-affix { position: relative; }
.preregs-edit-page .preregs-input-affix .preregs-input { padding-right: 2.65rem; }
.preregs-edit-page .preregs-affix {
    position: absolute;
    right: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--pt-navy);
    pointer-events: none;
}
.preregs-edit-page .preregs-select { cursor: pointer; appearance: auto; }
.preregs-edit-page .preregs-field-error { font-size: 0.78rem; color: #D64545; margin-top: 0.28rem; }
.preregs-edit-page .preregs-form-card input:focus,
.preregs-edit-page .preregs-form-card select:focus,
.preregs-edit-page .preregs-form-card textarea:focus {
    outline: none;
    border-color: var(--pt-blue);
    box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.16);
}

.preregs-edit-page .preregs-form-actions {
    margin-top: 0.85rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--pt-line);
    border-radius: 0.85rem;
    background: linear-gradient(180deg, #fff 0%, var(--pt-soft) 100%);
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.7rem;
    align-items: center;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}
.preregs-edit-page .preregs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.7rem 1.2rem;
    font-size: 0.9rem;
    font-weight: 700;
    border-radius: 0.65rem;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease, border-color .15s ease;
}
.preregs-edit-page .preregs-btn-primary {
    background: var(--pt-navy);
    color: #fff;
    border-color: var(--pt-navy);
    box-shadow: 0 6px 16px rgba(10, 45, 111, 0.28);
}
.preregs-edit-page .preregs-btn-primary:hover {
    background: var(--pt-blue);
    border-color: var(--pt-blue);
    color: #fff;
    transform: translateY(-1px);
}
.preregs-edit-page .preregs-btn-secondary {
    background: #fff;
    color: var(--pt-navy);
    border-color: var(--pt-border);
}
.preregs-edit-page .preregs-btn-secondary:hover {
    background: var(--pt-soft);
    border-color: var(--pt-navy);
}

/* Columna foto */
.preregs-edit-page .preregs-side-card {
    background: #fff;
    border: 1px solid var(--pt-line);
    border-radius: 1rem;
    padding: 1.05rem 1.1rem 1.15rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.preregs-edit-page .preregs-side-title {
    margin: 0 0 0.75rem;
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.preregs-edit-page .preregs-side-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 0.5rem;
    background: var(--pt-soft);
    color: var(--pt-navy);
    border: 1px solid var(--pt-border);
}
.preregs-edit-photos-list { display: grid; grid-template-columns: 1fr; gap: 0.75rem; }
.preregs-photo-wrap {
    text-align: center;
    border: 1px solid var(--pt-line);
    border-radius: 0.75rem;
    padding: 0.4rem;
    background: var(--pt-soft);
}
.preregs-photo-link-block { display: block; text-decoration: none; }
.preregs-photo-img {
    display: block;
    width: 100%;
    height: auto;
    max-height: min(70vh, 40rem);
    object-fit: contain;
    border-radius: 0.55rem;
    border: 1px solid var(--pt-border);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    background: #fff;
}
.preregs-photo-wrap:only-child .preregs-photo-img { max-height: min(74vh, 45rem); }
.preregs-photo-link-wrap { margin-top: 0.55rem; font-size: 0.78rem; }
.preregs-link { color: var(--pt-navy); font-weight: 700; text-decoration: none; }
.preregs-link:hover { text-decoration: underline; }
.preregs-edit-photo-order-hint { font-size: 0.78rem; color: #64748b; margin: 0 0 0.7rem; line-height: 1.4; }
.preregs-photo-empty {
    text-align: center;
    padding: 1.4rem 1rem 1.5rem;
    border: 1.5px dashed var(--pt-border);
    border-radius: 0.75rem;
    background: var(--pt-soft);
}
.preregs-photo-empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    background: #fff;
    color: var(--pt-navy);
    border: 1px solid var(--pt-border);
    margin-bottom: 0.65rem;
}
.preregs-photo-empty-title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
}
.preregs-photo-empty-text {
    margin: 0.3rem 0 0.85rem;
    font-size: 0.78rem;
    color: #64748b;
    line-height: 1.45;
}
.preregs-photo-empty-btn { font-size: 0.8rem; padding: 0.5rem 0.9rem; }
.preregs-edit-photo-hint {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0.75rem 0 0;
    padding: 0.65rem 0.75rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.55rem;
    line-height: 1.4;
}
.preregs-photo-order-row {
    display: flex;
    gap: 0.35rem;
    justify-content: center;
    align-items: center;
    margin-top: 0.5rem;
}
.preregs-photo-order-form { margin: 0; display: inline; }
.preregs-photo-order-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    border: 1px solid var(--pt-border);
    border-radius: 0.45rem;
    background: #fff;
    color: var(--pt-navy);
    cursor: pointer;
    line-height: 0;
}
.preregs-photo-order-btn:hover:not(:disabled) {
    background: var(--pt-soft);
    border-color: var(--pt-navy);
}
.preregs-photo-order-btn:disabled { opacity: 0.35; cursor: not-allowed; }

@media (max-width: 960px) {
    .preregs-edit-layout.has-photo { grid-template-columns: 1fr; }
    .preregs-edit-photo-col { position: static; }
}
@media (max-width: 640px) {
    .preregs-edit-page .preregs-create-grid--root,
    .preregs-edit-page .preregs-create-grid--nested { grid-template-columns: 1fr; }
    .preregs-edit-page .preregs-form-body { padding: 0.9rem; }
    .preregs-edit-page .preregs-form-header-text { padding: 0.9rem; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var partner = document.getElementById('partner_agency_id');
    var sloWrap = document.getElementById('slo_client_wrap');
    var sloSelect = document.getElementById('slo_client_id');
    var hidden = document.getElementById('agency_id');
    if (!partner || !hidden) return;
    function sync() {
        var opt = partner.options[partner.selectedIndex];
        var isSlo = opt && opt.getAttribute('data-slo') === '1';
        if (isSlo) {
            if (sloWrap) sloWrap.style.display = '';
            hidden.value = sloSelect ? (sloSelect.value || '') : '';
        } else {
            if (sloWrap) sloWrap.style.display = 'none';
            hidden.value = partner.value || '';
        }
    }
    partner.addEventListener('change', sync);
    if (sloSelect) sloSelect.addEventListener('change', sync);
    sync();
});
</script>
@endsection
