<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BCH Tracking') - {{ config('app.name', 'Laravel') }}</title>
    @php
        $manifestPath = public_path('build/manifest.json');
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (!empty($manifest['resources/css/app.css']['file'])) {
                echo '<link rel="stylesheet" href="' . asset('build/' . $manifest['resources/css/app.css']['file']) . '">';
            }
            if (!empty($manifest['resources/js/app.js']['file'])) {
                echo '<script type="module" src="' . asset('build/' . $manifest['resources/js/app.js']['file']) . '"></script>';
            }
        } else {
            echo '<script src="https://cdn.tailwindcss.com"></script>';
        }
    @endphp
    <style>
        .app-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; flex-shrink: 0; background: #fff; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 1.5rem 1.25rem; border-bottom: 1px solid #e5e7eb; }
        .sidebar-brand a { font-size: 1.25rem; font-weight: 700; color: #111827; text-decoration: none; letter-spacing: -0.02em; }
        .sidebar-brand a:hover { color: #0d9488; }
        .sidebar-brand .brand-logo { display: inline-block; line-height: 0; }
        .sidebar-brand .brand-logo img { height: 4rem; width: auto; vertical-align: middle; display: block; object-fit: contain; }
        .sidebar-nav { flex: 1; padding: 1rem 0.75rem; display: flex; flex-direction: column; gap: 0.25rem; }
        .sidebar-link { display: block; padding: 0.625rem 1rem; font-size: 0.9375rem; font-weight: 500; color: #6b7280; text-decoration: none; border-radius: 0.5rem; transition: color 0.15s, background 0.15s; }
        .sidebar-link:hover { color: #111827; background: #f3f4f6; }
        .sidebar-link-tracking { color: #0d9488; }
        .sidebar-link-tracking:hover { color: #0f766e; background: #ccfbf1; }
        .sidebar-link-active { color: #111827; font-weight: 600; background: #f3f4f6; }
        .sidebar-link-active.sidebar-link-tracking { color: #0f766e; background: #ccfbf1; }
        .sidebar-user { padding: 1rem 1.25rem; border-top: 1px solid #e5e7eb; }
        .sidebar-user-name { font-size: 0.8125rem; color: #6b7280; margin-bottom: 0.5rem; }
        .sidebar-logout { width: 100%; text-align: left; font-size: 0.875rem; font-weight: 500; color: #6b7280; background: none; border: none; cursor: pointer; padding: 0.5rem 0; border-radius: 0.375rem; }
        .sidebar-logout:hover { color: #111827; background: #f3f4f6; }
        .app-main { flex: 1; min-width: 0; background: #f9fafb; padding: 1.5rem; }
        .app-main-inner { max-width: 96rem; margin: 0 auto; }

        /* Mobile: header + drawer (laptop no se toca) */
        .mobile-header { display: none; height: 3.5rem; background: #fff; border-bottom: 1px solid #e5e7eb; align-items: center; justify-content: space-between; padding: 0 1rem; position: sticky; top: 0; z-index: 40; }
        .mobile-header .sidebar-brand { padding: 0; border: none; }
        .mobile-header .sidebar-brand a { font-size: 1.125rem; }
        .sidebar-open-btn { display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; background: none; border: none; border-radius: 0.5rem; color: #374151; cursor: pointer; }
        .sidebar-open-btn:hover { background: #f3f4f6; color: #111827; }
        .sidebar-open-btn svg { width: 1.5rem; height: 1.5rem; }
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 48; }
        .sidebar-close-btn { display: none; position: absolute; top: 1rem; right: 1rem; width: 2.5rem; height: 2.5rem; align-items: center; justify-content: center; background: none; border: none; border-radius: 0.5rem; color: #6b7280; cursor: pointer; }
        .sidebar-close-btn:hover { background: #f3f4f6; color: #111827; }

        @media (max-width: 767px) {
            .mobile-header { display: flex; }
            .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 280px; max-width: 85vw; z-index: 50; transform: translateX(-100%); transition: transform 0.25s ease; box-shadow: 4px 0 20px rgba(0,0,0,0.1); }
            .sidebar.is-open { transform: translateX(0); }
            .sidebar-close-btn { display: flex; }
            .sidebar-backdrop.is-open { display: block; }
            .app-main { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <header class="mobile-header" aria-hidden="true">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="brand-logo"><img src="{{ asset('images/bch-tracking-logo.png') }}" alt="BCH Tracking"></a>
        </div>
        <button type="button" class="sidebar-open-btn" id="sidebar-open" aria-label="Abrir menú">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </button>
    </header>

    <div class="app-layout">
        <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>
        <aside class="sidebar" id="sidebar">
            <button type="button" class="sidebar-close-btn" id="sidebar-close" aria-label="Cerrar menú">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
            <div class="sidebar-brand">
                @if(auth()->user() && auth()->user()->isAgencyUser())
                <a href="{{ route('packages.index') }}" class="brand-logo"><img src="{{ asset('images/bch-tracking-logo.png') }}" alt="BCH Tracking"></a>
                @elseif(auth()->user() && !auth()->user()->is_admin)
                <a href="{{ route('packages.index') }}" class="brand-logo"><img src="{{ asset('images/bch-tracking-logo.png') }}" alt="BCH Tracking"></a>
                @else
                <a href="{{ route('dashboard') }}" class="brand-logo"><img src="{{ asset('images/bch-tracking-logo.png') }}" alt="BCH Tracking"></a>
                @endif
            </div>
            <nav class="sidebar-nav">
                @if(auth()->user() && auth()->user()->isAgencyUser())
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">Inicio</a>
                <a href="{{ route('packages.index') }}" class="sidebar-link {{ request()->routeIs('packages.*') ? 'sidebar-link-active' : '' }}">Mis paquetes</a>
                @else
                @if(auth()->user() && auth()->user()->is_admin)
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">Panel</a>
                @endif
                <a href="{{ route('packages.index') }}" class="sidebar-link {{ request()->routeIs('packages.*') ? 'sidebar-link-active' : '' }}">Paquetes</a>
                <a href="{{ route('preregistrations.index') }}" class="sidebar-link {{ request()->routeIs('preregistrations.*') ? 'sidebar-link-active' : '' }}">Preregistros</a>
                <a href="{{ route('consolidations.index') }}" class="sidebar-link {{ request()->routeIs('consolidations.*') ? 'sidebar-link-active' : '' }}">Consolidaciones</a>
                <a href="{{ route('nic-consolidations.index') }}" class="sidebar-link {{ request()->routeIs('nic-consolidations.*') ? 'sidebar-link-active' : '' }}">Escaneo NIC</a>
                @if(auth()->user() && auth()->user()->is_admin)
                <a href="{{ route('agencies.index') }}" class="sidebar-link {{ request()->routeIs('agencies.*') ? 'sidebar-link-active' : '' }}">Agencias</a>
                @endif
                <a href="{{ route('deliveries.index') }}" class="sidebar-link {{ request()->routeIs('deliveries.*') ? 'sidebar-link-active' : '' }}">Entregas</a>
                @if(auth()->user() && auth()->user()->is_admin)
                <a href="{{ route('audit.index') }}" class="sidebar-link {{ request()->routeIs('audit.*') ? 'sidebar-link-active' : '' }}">Auditoría</a>
                <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'sidebar-link-active' : '' }}">Usuarios</a>
                @endif
                @endif
                <a href="{{ route('api-tokens.index') }}" class="sidebar-link {{ request()->routeIs('api-tokens.*') ? 'sidebar-link-active' : '' }}">Tokens API</a>
                <a href="{{ route('tracking.index') }}" class="sidebar-link sidebar-link-tracking {{ request()->routeIs('tracking.*') ? 'sidebar-link-active' : '' }}">Consultar tracking</a>
            </nav>
            @auth
            <div class="sidebar-user">
                <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout">Cerrar sesión</button>
                </form>
            </div>
            @endauth
        </aside>

        <main class="app-main">
            <div class="app-main-inner">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('warning') }}</span>
                </div>
            @endif

            @yield('content')
            </div>
        </main>
    </div>

    <script>
        (function() {
            var sidebar = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebar-backdrop');
            var openBtn = document.getElementById('sidebar-open');
            var closeBtn = document.getElementById('sidebar-close');
            if (!sidebar || !backdrop) return;
            function openSidebar() {
                sidebar.classList.add('is-open');
                backdrop.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                sidebar.classList.remove('is-open');
                backdrop.classList.remove('is-open');
                document.body.style.overflow = '';
            }
            openBtn && openBtn.addEventListener('click', openSidebar);
            closeBtn && closeBtn.addEventListener('click', closeSidebar);
            backdrop.addEventListener('click', closeSidebar);
            sidebar.querySelectorAll('.sidebar-link').forEach(function(link) {
                link.addEventListener('click', closeSidebar);
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
