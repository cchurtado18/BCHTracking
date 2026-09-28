@extends('layouts.tracking')

@section('title', ! empty($receipt) ? 'Prealerta enviada' : 'Prealerta')

@section('content')
@if(! empty($receipt))
<div class="public-prealert-sent" role="status">
    <div class="public-prealert-sent-hero" aria-hidden="true">
        <span class="public-prealert-check">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        </span>
    </div>
    <h1 class="public-prealert-sent-title">¡Prealerta enviada!</h1>
    <p class="public-prealert-sent-lead">Hemos recibido la información de su paquete.</p>
    <dl class="public-prealert-sent-data">
        <div>
            <dt>Tracking</dt>
            <dd>{{ $receipt['tracking'] }}</dd>
        </div>
        <div>
            <dt>Destinatario</dt>
            <dd>{{ $receipt['name'] }}</dd>
        </div>
        <div>
            <dt>Agencia</dt>
            <dd>{{ $receipt['agency_name'] }}</dd>
        </div>
        <div>
            <dt>Servicio</dt>
            <dd>{{ $receipt['service_label'] }}</dd>
        </div>
        @if(! empty($receipt['notes']))
        <div class="public-prealert-sent-full">
            <dt>Notas</dt>
            <dd>{{ $receipt['notes'] }}</dd>
        </div>
        @endif
        <div>
            <dt>Estado</dt>
            <dd><span class="public-prealert-sent-badge">{{ $receipt['status'] }}</span></dd>
        </div>
    </dl>
    <a href="{{ route('prealerts.public.create', ['nuevo' => 1]) }}" class="public-prealert-sent-btn">Prealertar otro paquete</a>
</div>
@else
<div class="public-prealert">
    <p class="pt-kicker">Envío</p>
    <h1 class="pt-heading">Prealerta</h1>
    <p class="pt-lead">Avise su paquete antes de que llegue a bodega. Use el mismo tracking de la etiqueta. Los campos con * son obligatorios.</p>

    @if($errors->any())
    <div class="public-prealert-err" role="alert">
        <p class="public-prealert-err-title">No se pudo guardar la prealerta</p>
        <ul>
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('prealerts.public.store') }}" method="POST" class="public-prealert-form">
        @csrf
        <div class="public-prealert-field">
            <label for="name" class="guest-label">Nombre del cliente en el paquete <span class="public-prealert-req">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="guest-input public-prealert-upper" required maxlength="120" autocapitalize="characters" autocomplete="name">
        </div>
        <div class="public-prealert-field">
            <label for="tracking" class="guest-label">Tracking del paquete <span class="public-prealert-req">*</span></label>
            <input type="text" name="tracking" id="tracking" value="{{ old('tracking') }}" class="guest-input public-prealert-upper" required minlength="8" maxlength="48" autocapitalize="characters" autocomplete="off" spellcheck="false" placeholder="Ej: SPXMIA012462609040002615">
        </div>
        <div class="public-prealert-field">
            <label for="service_type" class="guest-label">Servicio <span class="public-prealert-req">*</span></label>
            <select name="service_type" id="service_type" class="guest-input" required>
                <option value="" disabled {{ old('service_type') ? '' : 'selected' }}>Seleccione aéreo o marítimo</option>
                <option value="AIR" @selected(old('service_type') === 'AIR')>Aéreo</option>
                <option value="SEA" @selected(old('service_type') === 'SEA')>Marítimo</option>
            </select>
        </div>
        <div class="public-prealert-field">
            <label for="agency_name" class="guest-label">Nombre de su agencia <span class="public-prealert-req">*</span></label>
            <input type="text" name="agency_name" id="agency_name" value="{{ old('agency_name') }}" class="guest-input public-prealert-upper" required maxlength="120" autocapitalize="characters" autocomplete="organization">
        </div>
        <div class="public-prealert-field">
            <label for="description" class="guest-label">Notas <span class="public-prealert-opt">(opcional)</span></label>
            <textarea name="description" id="description" class="guest-input public-prealert-notes public-prealert-upper" rows="3" maxlength="500" autocapitalize="characters">{{ old('description') }}</textarea>
        </div>
        <button type="submit" class="guest-submit public-prealert-submit">Guardar prealerta</button>
    </form>
</div>
@endif

<style>
.pt-kicker { margin: 0 0 0.35rem; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #1E4FA8; }
.pt-heading { margin: 0 0 0.35rem; font-size: 1.55rem; font-weight: 800; letter-spacing: -0.03em; color: #0A2D6F; }
.pt-lead { margin: 0 0 1.25rem; font-size: 0.9rem; color: #5E6168; line-height: 1.45; }
.guest-label { display: block; font-size: 0.78rem; font-weight: 700; color: #334155; margin-bottom: 0.4rem; }
.guest-input {
    width: 100%; padding: 0.78rem 0.95rem; font-size: 0.95rem;
    border: 1px solid #D8DCE2; border-radius: 0.7rem; background: #F8FAFC;
}
.guest-input:focus { outline: none; background: #fff; border-color: #1E4FA8; box-shadow: 0 0 0 4px rgba(30, 79, 168, 0.14); }
.guest-input::placeholder { color: #94a3b8; }
.guest-submit {
    width: 100%; padding: 0.9rem 1.25rem; margin-top: 0.2rem;
    font-size: 0.98rem; font-weight: 800; color: #fff;
    background: linear-gradient(180deg, #1E4FA8 0%, #0A2D6F 100%);
    border: none; border-radius: 0.8rem; cursor: pointer;
    box-shadow: 0 10px 22px rgba(10, 45, 111, 0.28);
}
.guest-submit:hover { filter: brightness(1.06); }
.public-prealert-form { display: flex; flex-direction: column; gap: 0.95rem; }
.public-prealert-req { color: #D64545; }
.public-prealert-opt { font-weight: 600; color: #94a3b8; }
.public-prealert-upper { text-transform: uppercase; }
.public-prealert-notes { min-height: 5.5rem; resize: vertical; }
.public-prealert-err {
    margin: 0 0 1.1rem; padding: 0.95rem 1.05rem; border-radius: 0.85rem;
    background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
}
.public-prealert-err-title { margin: 0 0 0.35rem; font-weight: 800; }
.public-prealert-err ul { margin: 0; padding-left: 1.15rem; font-size: 0.88rem; }
.public-prealert-sent { text-align: center; padding: 0.35rem 0 0.15rem; }
.public-prealert-sent-hero {
    width: 4.35rem; height: 4.35rem; margin: 0 auto 1rem;
    display: flex; align-items: center; justify-content: center;
}
.public-prealert-check {
    width: 4.35rem; height: 4.35rem; border-radius: 50%;
    background: #1E4FA8; color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 22px rgba(10, 45, 111, 0.28);
}
.public-prealert-check svg { width: 2.15rem; height: 2.15rem; }
.public-prealert-sent-title { margin: 0 0 0.4rem; font-size: 1.55rem; font-weight: 800; color: #0A2D6F; letter-spacing: -0.03em; }
.public-prealert-sent-lead { margin: 0 auto 1.25rem; max-width: 22rem; font-size: 0.98rem; font-weight: 650; color: #0A2D6F; line-height: 1.4; }
.public-prealert-sent-data {
    margin: 0 0 1.25rem; padding: 1.15rem 1.2rem; text-align: left;
    background: #F8FAFC; border: 1px solid #E8EBEF; border-radius: 0.95rem;
    display: grid; gap: 1.2rem 1rem;
}
.public-prealert-sent-data > div { display: grid; grid-template-columns: 7.4rem minmax(0, 1fr); gap: 0.5rem; align-items: start; }
.public-prealert-sent-data dt { margin: 0; font-size: 0.82rem; font-weight: 700; color: #64748b; }
.public-prealert-sent-data dd { margin: 0; font-size: 0.9rem; font-weight: 750; color: #0f172a; word-break: break-word; }
.public-prealert-sent-full { grid-column: 1 / -1; }
.public-prealert-sent-badge {
    display: inline-flex; align-items: center; padding: 0.18rem 0.65rem;
    border-radius: 999px; font-size: 0.75rem; font-weight: 800;
    background: #dcfce7; color: #166534; border: 1px solid #86efac;
}
.public-prealert-sent-btn {
    display: block; width: 100%; padding: 0.85rem 1.15rem; box-sizing: border-box;
    text-align: center; text-decoration: none; font-size: 0.95rem; font-weight: 800;
    color: #0A2D6F; background: #fff; border: 2px solid #0A2D6F; border-radius: 0.8rem;
}
.public-prealert-sent-btn:hover { background: #F4F8FD; color: #0A2D6F; }
@media (max-width: 520px) {
    .pt-heading, .public-prealert-sent-title { font-size: 1.35rem; }
    .public-prealert-sent-data > div { grid-template-columns: 1fr; gap: 0.15rem; }
}
</style>
@endsection
