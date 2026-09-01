<x-guest-layout>
    @section('title', 'Inicio')

    @php $showForm = $errors->isNotEmpty(); @endphp
    <div class="pt-login">
        <div id="guest-choice" class="guest-choice" style="{{ $showForm ? 'display: none;' : '' }}">
            <p class="pt-kicker">Bienvenido</p>
            <h1 class="pt-heading">¿Qué desea hacer?</h1>
            <p class="pt-lead">Elija cómo entrar. El rastreo no requiere cuenta.</p>
            <div class="guest-choice-grid">
                <button type="button" id="btn-show-login" class="guest-choice-card guest-choice-login">
                    <span class="guest-choice-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998-0.059A7.5 7.5 0 0 1 4.5 20.118Z" /></svg>
                    </span>
                    <span class="guest-choice-title">Iniciar sesión</span>
                    <span class="guest-choice-desc">Clientes, subagencias y personal. Use el correo de la cuenta.</span>
                </button>
                <a href="{{ route('tracking.index') }}" class="guest-choice-card guest-choice-tracking">
                    <span class="guest-choice-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                    </span>
                    <span class="guest-choice-title">Rastrear paquete</span>
                    <span class="guest-choice-desc">Consulte con código o tracking, sin sesión</span>
                </a>
            </div>
        </div>

        <div id="guest-login-form" class="guest-form-screen" style="{{ $showForm ? '' : 'display: none;' }}">
            <button type="button" id="btn-back-choice" class="guest-back-btn" aria-label="Volver">
                <svg class="guest-back-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Volver
            </button>
            <p class="pt-kicker">Acceso al panel</p>
            <h1 class="pt-heading">Iniciar sesión</h1>
            <p class="pt-lead">Ingrese el correo y la contraseña de la cuenta. Los clientes de SkyLink One entran aquí para ver sus paquetes.</p>

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
                    <div class="guest-pass-wrap">
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="guest-input" placeholder="••••••••">
                        <button type="button" class="guest-pass-toggle" id="togglePassword" aria-label="Mostrar contraseña">Ver</button>
                    </div>
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
                <button type="submit" class="guest-submit">Iniciar sesión</button>
            </form>
        </div>
    </div>

    <style>
        .pt-login { padding: 0; }
        .pt-kicker {
            margin: 0 0 0.35rem;
            font-size: 0.68rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #1E4FA8;
        }
        .pt-heading { margin: 0 0 0.35rem; font-size: 1.55rem; font-weight: 800; letter-spacing: -0.03em; color: #0A2D6F; }
        .pt-lead { margin: 0 0 1.35rem; font-size: 0.9rem; color: #5E6168; line-height: 1.45; }
        .guest-choice-grid { display: grid; gap: 0.85rem; }
        .guest-choice-card {
            display: flex; flex-direction: column; align-items: flex-start; text-align: left;
            padding: 1.15rem 1.15rem 1.2rem; border-radius: 1rem; border: 1px solid #E8EBEF;
            cursor: pointer; text-decoration: none; color: inherit; background: #fff;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }
        .guest-choice-card:hover {
            transform: translateY(-2px);
            border-color: #C5D4EB;
            box-shadow: 0 12px 28px rgba(10, 45, 111, 0.1);
        }
        .guest-choice-icon {
            width: 2.6rem; height: 2.6rem; border-radius: 0.75rem; margin-bottom: 0.85rem;
            display: inline-flex; align-items: center; justify-content: center;
            background: #F4F8FD; color: #0A2D6F;
        }
        .guest-choice-icon svg { width: 1.35rem; height: 1.35rem; }
        .guest-choice-tracking .guest-choice-icon { background: #EAF4FF; color: #1E4FA8; }
        .guest-choice-title { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; margin-bottom: 0.2rem; }
        .guest-choice-desc { font-size: 0.82rem; color: #5E6168; line-height: 1.4; }
        .guest-back-btn {
            display: inline-flex; align-items: center; gap: 0.35rem;
            background: none; border: 0; color: #5E6168; font-size: 0.82rem; font-weight: 600;
            cursor: pointer; padding: 0; margin-bottom: 1.1rem;
        }
        .guest-back-btn:hover { color: #0A2D6F; }
        .guest-back-arrow { width: 1rem; height: 1rem; }
        .guest-session-status { margin-bottom: 1rem; font-size: 0.875rem; padding: 0.75rem 1rem !important; border-radius: 0.7rem !important; }
        .guest-form-fields { display: flex; flex-direction: column; gap: 1.05rem; }
        .guest-label { display: block; font-size: 0.78rem; font-weight: 700; color: #334155; margin-bottom: 0.4rem; }
        .guest-input {
            width: 100%; padding: 0.78rem 0.95rem; font-size: 0.95rem;
            border: 1px solid #D8DCE2; border-radius: 0.7rem; background: #F8FAFC;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .guest-input:focus { outline: none; background: #fff; border-color: #1E4FA8; box-shadow: 0 0 0 4px rgba(30, 79, 168, 0.14); }
        .guest-input::placeholder { color: #94a3b8; }
        .guest-pass-wrap { position: relative; }
        .guest-pass-wrap .guest-input { padding-right: 3.4rem; }
        .guest-pass-toggle {
            position: absolute; right: 0.7rem; top: 50%; transform: translateY(-50%);
            border: 0; background: none; color: #1E4FA8; font-size: 0.75rem; font-weight: 800;
            letter-spacing: 0.04em; text-transform: uppercase; cursor: pointer;
        }
        .guest-error { margin-top: 0.3rem; font-size: 0.8rem; color: #D64545; }
        .guest-options { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; }
        .guest-checkbox-label { display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; color: #5E6168; cursor: pointer; }
        .guest-checkbox { width: 1rem; height: 1rem; accent-color: #0A2D6F; }
        .guest-forgot { font-size: 0.84rem; font-weight: 700; color: #1E4FA8; text-decoration: none; }
        .guest-forgot:hover { text-decoration: underline; }
        .guest-submit {
            width: 100%; padding: 0.9rem 1.25rem; margin-top: 0.15rem;
            font-size: 0.98rem; font-weight: 800; color: #fff;
            background: linear-gradient(180deg, #1E4FA8 0%, #0A2D6F 100%);
            border: none; border-radius: 0.8rem; cursor: pointer;
            box-shadow: 0 10px 22px rgba(10, 45, 111, 0.28);
            transition: filter 0.15s, transform 0.05s;
        }
        .guest-submit:hover { filter: brightness(1.06); }
        .guest-submit:active { transform: scale(0.99); }
        @media (max-width: 800px) { .pt-heading { font-size: 1.35rem; } }
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
                    var email = document.getElementById('email');
                    if (email) email.focus();
                });
                btnBack.addEventListener('click', function() {
                    formScreen.style.display = 'none';
                    choice.style.display = 'block';
                });
            }
            var toggle = document.getElementById('togglePassword');
            var pass = document.getElementById('password');
            if (toggle && pass) {
                toggle.addEventListener('click', function() {
                    var show = pass.type === 'password';
                    pass.type = show ? 'text' : 'password';
                    toggle.textContent = show ? 'Ocultar' : 'Ver';
                    toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
                });
            }
        });
    </script>
</x-guest-layout>
