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
        :root {
            --app-bg-base: #f5f7fb;
            --app-bg-soft: #eef2f7;
            --app-surface: #ffffff;
            --app-surface-border: #e5ecf3;
            --app-surface-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 8px 24px rgba(15, 23, 42, 0.03);
        }
        .app-layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: 278px;
            flex-shrink: 0;
            background: #f8fafc;
            border-right: 1px solid #e6edf5;
            box-shadow: 2px 0 10px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            padding: 1.4rem 1.05rem 1.1rem;
            border-bottom: 1px solid #e6edf5;
            text-align: center;
        }
        .sidebar-brand a { font-size: 1.25rem; font-weight: 700; color: #111827; text-decoration: none; letter-spacing: -0.02em; }
        .sidebar-brand a:hover { color: #0d9488; }
        .sidebar-brand .brand-logo { display: inline-block; line-height: 0; }
        .sidebar-brand .brand-logo img { height: 3.5rem; width: auto; vertical-align: middle; display: block; object-fit: contain; margin: 0 auto; }
        .sidebar-brand-name {
            margin-top: 0.6rem;
            font-size: 0.73rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #64748b;
        }
        .sidebar-nav {
            flex: 1;
            padding: 1rem 0.85rem 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.95rem;
            overflow-y: auto;
        }
        .sidebar-section {
            display: flex;
            flex-direction: column;
            gap: 0.36rem;
        }
        .sidebar-section-title {
            padding: 0 0.7rem;
            margin: 0.15rem 0 0.2rem;
            font-size: 0.67rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
        }
        .sidebar-divider {
            height: 1px;
            background: #e5edf5;
            margin: 0.15rem 0.6rem;
        }
        .sidebar-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.62rem;
            padding: 0.64rem 0.76rem;
            font-size: 0.89rem;
            font-weight: 500;
            color: #475569;
            text-decoration: none;
            border-radius: 0.6rem;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .sidebar-link-icon {
            width: 1.05rem;
            height: 1.05rem;
            color: #94a3b8;
            flex-shrink: 0;
            transition: color 0.2s ease;
        }
        .sidebar-link-label {
            line-height: 1.25;
        }
        .sidebar-link:hover {
            color: #0f172a;
            background: #edf2f8;
            border-color: #dbe4ef;
        }
        .sidebar-link:hover .sidebar-link-icon {
            color: #64748b;
        }
        .sidebar-link-active {
            color: #0f766e;
            font-weight: 600;
            background: #e7f8f5;
            border-color: #b8ece4;
            box-shadow: inset 3px 0 0 #0d9488;
        }
        .sidebar-link-active .sidebar-link-icon {
            color: #0d9488;
        }
        .sidebar-link-tracking {
            color: #0f766e;
            background: #ecfdf5;
            border-color: #bbf7d0;
        }
        .sidebar-link-tracking .sidebar-link-icon {
            color: #059669;
        }
        .sidebar-link-tracking:hover {
            color: #065f46;
            background: #dcfce7;
            border-color: #86efac;
        }
        .sidebar-link-active.sidebar-link-tracking {
            color: #065f46;
            background: #d1fae5;
            border-color: #86efac;
            box-shadow: inset 3px 0 0 #059669;
        }
        .sidebar-user { padding: 0.95rem 1rem 1.05rem; border-top: 1px solid #e6edf5; background: #f8fafc; }
        .sidebar-user-name { font-size: 0.8rem; color: #64748b; margin-bottom: 0.45rem; font-weight: 500; }
        .sidebar-logout {
            width: 100%;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
            background: none;
            border: 1px solid transparent;
            cursor: pointer;
            padding: 0.5rem 0.7rem;
            border-radius: 0.6rem;
            transition: all 0.2s ease;
        }
        .sidebar-logout:hover { color: #0f172a; background: #eef2f7; border-color: #dbe4ef; }
        .app-main {
            flex: 1;
            min-width: 0;
            padding: 1.65rem;
            background:
                radial-gradient(circle at 20% 20%, rgba(16, 185, 129, 0.07), transparent 40%),
                radial-gradient(circle at 80% 0%, rgba(59, 130, 246, 0.06), transparent 42%),
                linear-gradient(180deg, #f8fafc 0%, var(--app-bg-soft) 100%);
        }
        .app-main-inner { max-width: 96rem; margin: 0 auto; }

        /* Surface consistency across modules */
        .app-main .preregs-card,
        .app-main .packages-card,
        .app-main .deliveries-card,
        .app-main .audit-card,
        .app-main .tracking-card,
        .app-main .dashboard-card,
        .app-main .card,
        .app-main [class*=" table-card"],
        .app-main [class$="-table-card"],
        .app-main [class*=" filters-card"],
        .app-main [class$="-filters-card"],
        .app-main [class*=" form-card"],
        .app-main [class$="-form-card"] {
            background: var(--app-surface);
            border: 1px solid var(--app-surface-border);
            border-radius: 0.75rem;
            box-shadow: var(--app-surface-shadow);
            margin-bottom: 1.35rem;
        }

        /* Mobile: header + drawer (laptop no se toca) */
        .mobile-header { display: none; height: 3.5rem; background: #f8fafc; border-bottom: 1px solid #e5e7eb; align-items: center; justify-content: space-between; padding: 0 1rem; position: sticky; top: 0; z-index: 40; }
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
<body class="bg-gray-50" style="background: var(--app-bg-base);">
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
                <div class="sidebar-brand-name">BCH Tracking Suite</div>
            </div>
            <nav class="sidebar-nav">
                @if(auth()->user() && auth()->user()->isAgencyUser())
                <div class="sidebar-section">
                    <p class="sidebar-section-title">General</p>
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 10.5 12 3l8.25 7.5V20.25a.75.75 0 0 1-.75.75h-5.25v-6h-4.5v6H4.5a.75.75 0 0 1-.75-.75V10.5Z" /></svg>
                        <span class="sidebar-link-label">Inicio</span>
                    </a>
                    <a href="{{ route('packages.index') }}" class="sidebar-link {{ request()->routeIs('packages.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z" /></svg>
                        <span class="sidebar-link-label">Mis paquetes</span>
                    </a>
                </div>
                <div class="sidebar-divider"></div>
                <div class="sidebar-section">
                    <p class="sidebar-section-title">Herramientas</p>
                    <a href="{{ route('api-tokens.index') }}" class="sidebar-link {{ request()->routeIs('api-tokens.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v3m0 0h3m-3 0h-3M6 8.25h4.5m-4.5 4.5h12m-12 4.5h12" /></svg>
                        <span class="sidebar-link-label">Tokens API</span>
                    </a>
                    <a href="{{ route('tracking.index') }}" class="sidebar-link sidebar-link-tracking {{ request()->routeIs('tracking.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.75h3.75v3.75m0-3.75-5.25 5.25M7.5 17.25H3.75V13.5m0 3.75 5.25-5.25M9 7.5h.008v.008H9V7.5Zm0 9h.008v.008H9V16.5Zm6-9h.008v.008H15V7.5Zm0 9h.008v.008H15V16.5Z" /></svg>
                        <span class="sidebar-link-label">Consultar tracking</span>
                    </a>
                </div>
                @else
                <div class="sidebar-section">
                    <p class="sidebar-section-title">General</p>
                    @if(auth()->user() && auth()->user()->is_admin)
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5h8.25V3H3v10.5Zm0 7.5h8.25v-4.5H3V21Zm9.75 0H21V10.5h-8.25V21Zm0-12h8.25V3h-8.25v6Z" /></svg>
                        <span class="sidebar-link-label">Panel</span>
                    </a>
                    @endif
                    <a href="{{ route('packages.index') }}" class="sidebar-link {{ request()->routeIs('packages.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z" /></svg>
                        <span class="sidebar-link-label">Paquetes</span>
                    </a>
                    <a href="{{ route('preregistrations.index') }}" class="sidebar-link {{ request()->routeIs('preregistrations.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 6h9m-9 4.5h9m-9 4.5h5.25M5.25 3.75h13.5A1.5 1.5 0 0 1 20.25 5.25v13.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Z" /></svg>
                        <span class="sidebar-link-label">Preregistros</span>
                    </a>
                </div>
                <div class="sidebar-divider"></div>
                <div class="sidebar-section">
                    <p class="sidebar-section-title">Operaciones</p>
                    <a href="{{ route('consolidations.index') }}" class="sidebar-link {{ request()->routeIs('consolidations.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 8.25h16.5M3.75 15.75h16.5M7.5 3.75v16.5m9-16.5v16.5" /></svg>
                        <span class="sidebar-link-label">Consolidaciones</span>
                    </a>
                    <a href="{{ route('nic-consolidations.index') }}" class="sidebar-link {{ request()->routeIs('nic-consolidations.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15v10.5h-15V6.75Zm3 3h4.5m-4.5 3h9" /></svg>
                        <span class="sidebar-link-label">Escaneo NIC</span>
                    </a>
                    <a href="{{ route('deliveries.index') }}" class="sidebar-link {{ request()->routeIs('deliveries.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h11.25v10.5H3.75V6.75Zm11.25 3h3.19a1.5 1.5 0 0 1 1.22.63l1.59 2.24v4.63H15V9.75ZM7.5 18.75a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm12 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" /></svg>
                        <span class="sidebar-link-label">Entregas</span>
                    </a>
                </div>
                <div class="sidebar-divider"></div>
                <div class="sidebar-section">
                    <p class="sidebar-section-title">Administracion</p>
                    @if(auth()->user() && auth()->user()->is_admin)
                    <a href="{{ route('agencies.index') }}" class="sidebar-link {{ request()->routeIs('agencies.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 20.25h16.5m-15-3V5.25A1.5 1.5 0 0 1 6.75 3.75h10.5a1.5 1.5 0 0 1 1.5 1.5v12M8.25 7.5h1.5m4.5 0h1.5m-7.5 3h1.5m4.5 0h1.5m-7.5 3h1.5m4.5 0h1.5" /></svg>
                        <span class="sidebar-link-label">Agencias</span>
                    </a>
                    <a href="{{ route('audit.index') }}" class="sidebar-link {{ request()->routeIs('audit.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9Z" /></svg>
                        <span class="sidebar-link-label">Auditoria</span>
                    </a>
                    <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 1 1 15 0" /></svg>
                        <span class="sidebar-link-label">Usuarios</span>
                    </a>
                    @endif
                    <a href="{{ route('api-tokens.index') }}" class="sidebar-link {{ request()->routeIs('api-tokens.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v3m0 0h3m-3 0h-3M6 8.25h4.5m-4.5 4.5h12m-12 4.5h12" /></svg>
                        <span class="sidebar-link-label">Tokens API</span>
                    </a>
                    <a href="{{ route('tracking.index') }}" class="sidebar-link sidebar-link-tracking {{ request()->routeIs('tracking.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.75h3.75v3.75m0-3.75-5.25 5.25M7.5 17.25H3.75V13.5m0 3.75 5.25-5.25M9 7.5h.008v.008H9V7.5Zm0 9h.008v.008H9V16.5Zm6-9h.008v.008H15V7.5Zm0 9h.008v.008H15V16.5Z" /></svg>
                        <span class="sidebar-link-label">Consultar tracking</span>
                    </a>
                </div>
                @endif
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
