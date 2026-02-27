<x-guest-layout>
    @section('title', 'Inicio')

    @php $showForm = $errors->isNotEmpty(); @endphp
    {{-- Pantalla 1: Elección --}}
    <div id="guest-choice" class="guest-choice" style="{{ $showForm ? 'display: none;' : '' }}">
        <div class="guest-choice-grid">
            <button type="button" id="btn-show-login" class="guest-choice-card guest-choice-login">
                <span class="guest-choice-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998-0.059A7.5 7.5 0 0 1 4.5 20.118Z" /></svg>
                </span>
                <span class="guest-choice-title">Iniciar sesión</span>
                <span class="guest-choice-desc">Acceder al panel de administración</span>
            </button>
            <a href="{{ route('tracking.index') }}" class="guest-choice-card guest-choice-tracking">
                <span class="guest-choice-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                </span>
                <span class="guest-choice-title">Rastrear mi paquete</span>
                <span class="guest-choice-desc">Ver estado con código o tracking (sin sesión)</span>
            </a>
        </div>
    </div>

    {{-- Pantalla 2: Formulario de login --}}
    <div id="guest-login-form" class="guest-form-screen" style="{{ $showForm ? '' : 'display: none;' }}">
        <div class="guest-form-inner">
            <button type="button" id="btn-back-choice" class="guest-back-btn" aria-label="Volver">
                <svg class="guest-back-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                <span>Volver</span>
            </button>

            <div class="guest-form-brand">
                <h1 class="guest-form-title">Iniciar sesión</h1>
                <p class="guest-form-desc">Ingrese sus datos para acceder al panel.</p>
            </div>

            <x-auth-session-status class="guest-session-status" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="guest-form-fields">
                @csrf
                <div class="guest-field">
                    <label for="email" class="guest-label">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="guest-input" placeholder="ejemplo@correo.com">
                    <x-input-error :messages="$errors->get('email')" class="guest-error" />
                </div>
                <div class="guest-field">
                    <label for="password" class="guest-label">Contraseña</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" class="guest-input" placeholder="••••••••">
                    <x-input-error :messages="$errors->get('password')" class="guest-error" />
                </div>
                <div class="guest-options">
                    <label for="remember_me" class="guest-checkbox-label">
                        <input id="remember_me" type="checkbox" name="remember" class="guest-checkbox">
                        <span>Recordarme</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="guest-forgot">¿Olvidó su contraseña?</a>
                    @endif
                </div>
                <button type="submit" class="guest-submit">
                    Iniciar sesión
                </button>
            </form>
        </div>
    </div>

    <style>
        .guest-choice { padding: 1.5rem 1.5rem 2rem; }
        .guest-choice-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 600px) { .guest-choice-grid { grid-template-columns: 1fr; } }
        .guest-choice-card { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 2rem 1.5rem; border-radius: 1rem; border: 2px solid #e5e7eb; cursor: pointer; transition: all 0.2s; text-decoration: none; color: inherit; background: #fff; min-height: 10rem; }
        .guest-choice-card:hover { border-color: #99f6e4; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.15); }
        .guest-choice-login:hover { border-color: #0d9488; background: #f0fdfa; }
        .guest-choice-tracking { border-color: #bae6fd; background: #f0f9ff; }
        .guest-choice-tracking:hover { border-color: #0ea5e9; background: #e0f2fe; }
        .guest-choice-icon { width: 3rem; height: 3rem; margin-bottom: 0.75rem; color: #0d9488; }
        .guest-choice-tracking .guest-choice-icon { color: #0284c7; }
        .guest-choice-title { font-size: 1.25rem; font-weight: 700; color: #111827; display: block; margin-bottom: 0.25rem; }
        .guest-choice-desc { font-size: 0.875rem; color: #6b7280; }
        .guest-form-screen { padding: 1.25rem 1.5rem 2rem; border-top: 3px solid #0d9488; }
        .guest-form-inner { max-width: 22rem; margin: 0 auto; }
        .guest-back-btn { display: inline-flex; align-items: center; gap: 0.375rem; background: #f3f4f6; border: none; color: #4b5563; font-size: 0.875rem; font-weight: 500; cursor: pointer; padding: 0.5rem 0.75rem; margin-bottom: 1.25rem; border-radius: 0.5rem; transition: background 0.2s, color 0.2s; }
        .guest-back-btn:hover { background: #e5e7eb; color: #0d9488; }
        .guest-back-arrow { width: 1.125rem; height: 1.125rem; }
        .guest-form-brand { text-align: center; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb; }
        .guest-form-title { font-size: 1.375rem; font-weight: 700; color: #111827; margin: 0 0 0.25rem; letter-spacing: -0.02em; }
        .guest-form-desc { font-size: 0.875rem; color: #6b7280; margin: 0; }
        .guest-session-status { margin-bottom: 1rem; font-size: 0.875rem; padding: 0.75rem 1rem !important; border-radius: 0.5rem !important; }
        .guest-form-fields { display: flex; flex-direction: column; gap: 1.25rem; }
        .guest-field { }
        .guest-label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.375rem; }
        .guest-input { width: 100%; padding: 0.75rem 1rem; font-size: 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box; }
        .guest-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
        .guest-input::placeholder { color: #9ca3af; }
        .guest-error { margin-top: 0.25rem; font-size: 0.8125rem; color: #dc2626; }
        .guest-options { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; }
        .guest-checkbox-label { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #4b5563; cursor: pointer; }
        .guest-checkbox { width: 1rem; height: 1rem; border-radius: 0.25rem; border: 1px solid #d1d5db; color: #0d9488; }
        .guest-forgot { font-size: 0.875rem; font-weight: 500; color: #0d9488; text-decoration: none; }
        .guest-forgot:hover { color: #0f766e; text-decoration: underline; }
        .guest-submit { width: 100%; padding: 0.875rem 1.25rem; font-size: 1rem; font-weight: 600; color: #fff; background: #0d9488; border: none; border-radius: 0.5rem; cursor: pointer; transition: background 0.2s, transform 0.05s; margin-top: 0.25rem; }
        .guest-submit:hover { background: #0f766e; }
        .guest-submit:active { transform: scale(0.99); }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var choice = document.getElementById('guest-choice');
            var formScreen = document.getElementById('guest-login-form');
            var btnShowLogin = document.getElementById('btn-show-login');
            var btnBack = document.getElementById('btn-back-choice');
            if (choice && formScreen && btnShowLogin && btnBack) {
                btnShowLogin.addEventListener('click', function() {
                    choice.style.display = 'none';
                    formScreen.style.display = 'block';
                });
                btnBack.addEventListener('click', function() {
                    formScreen.style.display = 'none';
                    choice.style.display = 'block';
                });
            }
        });
    </script>
</x-guest-layout>
