<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PrimeTrack Group') - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --app-bg-base: #FFFFFF;
            --app-bg-soft: #F4F8FD;
            --app-surface: #FFFFFF;
            --app-surface-border: #E8EBEF;
            --app-surface-shadow: 0 1px 2px rgba(10, 45, 111, 0.05), 0 8px 24px rgba(10, 45, 111, 0.04);
            --brand-primary: #0A2D6F;
            --brand-primary-strong: #0A2D6F;
            --brand-primary-deep: #0A2D6F;
            --brand-secondary: #1E4FA8;
            --brand-muted: #5E6168;
            --brand-tertiary: #5E6168;
            --brand-form-border: #D8DCE2;
            --brand-row-hover: #F4F8FD;
            --state-success: #2BB673;
            --state-warning: #F6A623;
            --state-error: #D64545;
            --state-info: #3498DB;
        }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }
        .app-layout { display: flex; min-height: 100vh; align-items: stretch; }
        .sidebar {
            width: 278px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
            background: linear-gradient(180deg, #0A2D6F 0%, #0D367F 45%, #143A8C 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 4px 0 24px rgba(10, 45, 111, 0.28);
            display: flex;
            flex-direction: column;
            color: #fff;
        }
        .sidebar-brand {
            padding: 1.4rem 1.05rem 1.1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.14);
            text-align: center;
            background: rgba(0, 0, 0, 0.12);
            flex-shrink: 0;
        }
        .sidebar-brand a { font-size: 1.25rem; font-weight: 700; color: #fff; text-decoration: none; letter-spacing: -0.02em; }
        .sidebar-brand a:hover { color: #E8EEF8; }
        .sidebar-brand .brand-logo { display: inline-block; line-height: 0; }
        .sidebar-brand .brand-logo img { height: 3.75rem; width: auto; max-width: 100%; vertical-align: middle; display: block; object-fit: contain; margin: 0 auto; filter: brightness(0) invert(1); }
        .sidebar-nav {
            flex: 1;
            min-height: 0;
            padding: 1rem 0.85rem 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.95rem;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.35) transparent;
        }
        .sidebar-nav::-webkit-scrollbar { width: 6px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.28);
            border-radius: 999px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.45);
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
            color: rgba(255, 255, 255, 0.55);
        }
        .sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.14);
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
            color: rgba(255, 255, 255, 0.88);
            text-decoration: none;
            border-radius: 0.6rem;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .sidebar-link-icon {
            width: 1.05rem;
            height: 1.05rem;
            color: rgba(255, 255, 255, 0.7);
            flex-shrink: 0;
            transition: color 0.2s ease;
        }
        .sidebar-link-label {
            line-height: 1.25;
        }
        .sidebar-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.16);
        }
        .sidebar-link:hover .sidebar-link-icon {
            color: #fff;
        }
        .sidebar-link-active {
            color: #fff;
            font-weight: 600;
            background: #1E4FA8;
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: inset 3px 0 0 #fff;
        }
        .sidebar-link-active .sidebar-link-icon {
            color: #fff;
        }
        .sidebar-link-tracking {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.16);
        }
        .sidebar-link-tracking .sidebar-link-icon {
            color: #fff;
        }
        .sidebar-link-tracking:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.16);
            border-color: rgba(255, 255, 255, 0.22);
        }
        .sidebar-link-active.sidebar-link-tracking {
            color: #fff;
            background: #1E4FA8;
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: inset 3px 0 0 #fff;
        }
        .sidebar-user { padding: 0.85rem 0.85rem 1rem; border-top: 1px solid rgba(255, 255, 255, 0.14); background: rgba(0, 0, 0, 0.14); flex-shrink: 0; display: flex; flex-direction: column; gap: 0.2rem; }
        .sidebar-user-row {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.42rem 0.7rem 0.5rem;
        }
        .sidebar-avatar {
            width: 2.05rem;
            height: 2.05rem;
            border-radius: 999px;
            background: #1E4FA8;
            color: #fff;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.18);
        }
        .sidebar-user-meta { min-width: 0; }
        .sidebar-user-name { font-size: 0.88rem; color: #fff; font-weight: 600; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0; }
        .sidebar-logout {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 0.62rem;
            text-align: left;
            font-size: 0.89rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.88);
            background: none;
            border: 1px solid transparent;
            cursor: pointer;
            padding: 0.64rem 0.76rem;
            border-radius: 0.6rem;
            transition: all 0.2s ease;
        }
        .sidebar-logout-icon {
            width: 1.05rem;
            height: 1.05rem;
            color: rgba(255, 255, 255, 0.7);
            flex-shrink: 0;
        }
        .sidebar-logout:hover { color: #fff; background: rgba(255, 255, 255, 0.12); border-color: rgba(255, 255, 255, 0.16); }
        .sidebar-logout:hover .sidebar-logout-icon { color: #fff; }
        .app-main {
            flex: 1;
            min-width: 0;
            padding: 1.65rem;
            background:
                radial-gradient(circle at 20% 20%, rgba(10, 45, 111, 0.08), transparent 40%),
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
        .mobile-header { display: none; height: 3.5rem; background: #0A2D6F; border-bottom: 1px solid rgba(255,255,255,0.12); align-items: center; justify-content: space-between; padding: 0 1rem; position: sticky; top: 0; z-index: 40; }
        .mobile-header .sidebar-brand { padding: 0; border: none; background: transparent; }
        .mobile-header .sidebar-brand a { font-size: 1.125rem; color: #fff; }
        .sidebar-open-btn { display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; background: none; border: none; border-radius: 0.5rem; color: #fff; cursor: pointer; }
        .sidebar-open-btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
        .sidebar-open-btn svg { width: 1.5rem; height: 1.5rem; }
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 48; }
        .sidebar-close-btn { display: none; position: absolute; top: 1rem; right: 1rem; width: 2.5rem; height: 2.5rem; align-items: center; justify-content: center; background: none; border: none; border-radius: 0.5rem; color: rgba(255,255,255,0.75); cursor: pointer; }
        .sidebar-close-btn:hover { background: rgba(255,255,255,0.12); color: #fff; }

        @media (max-width: 767px) {
            .mobile-header { display: flex; }
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                width: 280px;
                max-width: 85vw;
                height: 100%;
                max-height: 100%;
                z-index: 50;
                transform: translateX(-100%);
                transition: transform 0.25s ease;
                box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            }
            .sidebar.is-open { transform: translateX(0); }
            .sidebar-close-btn { display: flex; }
            .sidebar-backdrop.is-open { display: block; }
            .app-main { margin-left: 0; padding: 1rem; }
        }
    </style>
    {{-- Estilos de módulo (paquetes, preregistros, etc.): en head para evitar FOUC --}}
    @stack('styles')
</head>
<body class="bg-gray-50" style="background: var(--app-bg-base);">
    <header class="mobile-header" aria-hidden="true">
        <div class="sidebar-brand">
            <a href="{{ auth()->user()?->is_admin ? route('dashboard') : route('packages.index') }}" class="brand-logo"><img src="{{ asset('images/primetrack-group-logo.png') }}?v=2" alt="PrimeTrack Group"></a>
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
                <a href="{{ route('packages.index') }}" class="brand-logo"><img src="{{ asset('images/primetrack-group-logo.png') }}?v=2" alt="PrimeTrack Group"></a>
                @elseif(auth()->user() && !auth()->user()->is_admin)
                <a href="{{ route('packages.index') }}" class="brand-logo"><img src="{{ asset('images/primetrack-group-logo.png') }}?v=2" alt="PrimeTrack Group"></a>
                @else
                <a href="{{ route('dashboard') }}" class="brand-logo"><img src="{{ asset('images/primetrack-group-logo.png') }}?v=2" alt="PrimeTrack Group"></a>
                @endif
            </div>
            <nav class="sidebar-nav">
                @if(auth()->user() && auth()->user()->isAgencyUser())
                @php $packagesOnlyPortal = auth()->user()->isPackagesOnlyPortal(); @endphp
                <div class="sidebar-section">
                    <p class="sidebar-section-title">General</p>
                    <a href="{{ route('packages.index') }}" class="sidebar-link {{ request()->routeIs('packages.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z" /></svg>
                        <span class="sidebar-link-label">Mis paquetes</span>
                    </a>
                    @unless($packagesOnlyPortal)
                    <a href="{{ route('salidas.index') }}" class="sidebar-link {{ request()->routeIs('salidas.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h11.25v10.5H3.75V6.75Zm11.25 3h3.19a1.5 1.5 0 0 1 1.22.63l1.59 2.24v4.63H15V9.75ZM7.5 18.75a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm12 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" /></svg>
                        <span class="sidebar-link-label">Mis entregas</span>
                    </a>
                    <a href="{{ route('accounting.invoices.index') }}" class="sidebar-link {{ request()->routeIs('accounting.invoices.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        <span class="sidebar-link-label">Mis facturas</span>
                    </a>
                    @endunless
                </div>
                <div class="sidebar-divider"></div>
                <div class="sidebar-section">
                    <p class="sidebar-section-title">Herramientas</p>
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
                    @if(auth()->user() && auth()->user()->is_admin)
                    <a href="{{ route('alerts.index') }}" class="sidebar-link {{ request()->routeIs('alerts.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        <span class="sidebar-link-label">Alertas</span>
                    </a>
                    @endif
                    <a href="{{ route('time-entries.index') }}" class="sidebar-link {{ request()->routeIs('time-entries.index') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <span class="sidebar-link-label">Fichaje</span>
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
                    <a href="{{ route('salidas.index') }}" class="sidebar-link {{ request()->routeIs('salidas.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h11.25v10.5H3.75V6.75Zm11.25 3h3.19a1.5 1.5 0 0 1 1.22.63l1.59 2.24v4.63H15V9.75ZM7.5 18.75a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm12 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" /></svg>
                        <span class="sidebar-link-label">Salidas</span>
                    </a>
                    <a href="{{ route('receipt-notes.index') }}" class="sidebar-link {{ request()->routeIs('receipt-notes.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7.5 3.75h9a1.5 1.5 0 0 1 1.5 1.5v13.5a1.5 1.5 0 0 1-1.5 1.5h-9a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Zm1.5 4.5h6"/></svg>
                        <span class="sidebar-link-label">Comprobantes recepción</span>
                    </a>
                </div>
                @if(auth()->user() && auth()->user()->is_admin)
                <div class="sidebar-divider"></div>
                <div class="sidebar-section">
                    <p class="sidebar-section-title">Contabilidad</p>
                    <a href="{{ route('accounting.invoices.index') }}" class="sidebar-link {{ request()->routeIs('accounting.invoices.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        <span class="sidebar-link-label">Facturas</span>
                    </a>
                    <a href="{{ route('accounting.payments.index') }}" class="sidebar-link {{ request()->routeIs('accounting.payments.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                        <span class="sidebar-link-label">Cobros</span>
                    </a>
                    <a href="{{ route('accounting.receivables.index') }}" class="sidebar-link {{ request()->routeIs('accounting.receivables.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <span class="sidebar-link-label">Cuentas por cobrar</span>
                    </a>
                    <a href="{{ route('accounting.credit-notes.index') }}" class="sidebar-link {{ request()->routeIs('accounting.credit-notes.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6.75m.75-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        <span class="sidebar-link-label">Notas de crédito</span>
                    </a>
                    <a href="{{ route('accounting.rates.index') }}" class="sidebar-link {{ request()->routeIs('accounting.rates.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z" /></svg>
                        <span class="sidebar-link-label">Tarifas</span>
                    </a>
                    <a href="{{ route('accounting.profitability.index') }}" class="sidebar-link {{ request()->routeIs('accounting.profitability.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.28m5.94 2.28-2.28 5.941" /></svg>
                        <span class="sidebar-link-label">Rentabilidad</span>
                    </a>
                    <a href="{{ route('accounting.expenses.index') }}" class="sidebar-link {{ request()->routeIs('accounting.expenses.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6m15 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" transform="rotate(45 12 12)" /></svg>
                        <span class="sidebar-link-label">Gastos</span>
                    </a>
                    <a href="{{ route('accounting.reports.index') }}" class="sidebar-link {{ request()->routeIs('accounting.reports.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3.75m8.5-3.75 1 3.75m-9.5 0h10m-10 0-.5 1.5m10.5-1.5.5 1.5m-3.75-9v-3m-3 3v-1.5m-3 1.5V9" /></svg>
                        <span class="sidebar-link-label">Reporte ejecutivo</span>
                    </a>
                    <a href="{{ route('accounting.settings.edit') }}" class="sidebar-link {{ request()->routeIs('accounting.settings.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                        <span class="sidebar-link-label">Parámetros</span>
                    </a>
                </div>
                @endif
                <div class="sidebar-divider"></div>
                <div class="sidebar-section">
                    <p class="sidebar-section-title">Administración</p>
                    @if(auth()->user() && auth()->user()->is_admin)
                    <a href="{{ route('agencies.index') }}" class="sidebar-link {{ request()->routeIs('agencies.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 20.25h16.5m-15-3V5.25A1.5 1.5 0 0 1 6.75 3.75h10.5a1.5 1.5 0 0 1 1.5 1.5v12M8.25 7.5h1.5m4.5 0h1.5m-7.5 3h1.5m4.5 0h1.5m-7.5 3h1.5m4.5 0h1.5" /></svg>
                        <span class="sidebar-link-label">Clientes</span>
                    </a>
                    <a href="{{ route('audit.index') }}" class="sidebar-link {{ request()->routeIs('audit.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9Z" /></svg>
                        <span class="sidebar-link-label">Auditoría</span>
                    </a>
                    <a href="{{ route('time-entries.admin.index') }}" class="sidebar-link {{ request()->routeIs('time-entries.admin.index') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5" /></svg>
                        <span class="sidebar-link-label">Fichaje equipo</span>
                    </a>
                    <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 1 1 15 0" /></svg>
                        <span class="sidebar-link-label">Usuarios</span>
                    </a>
                    @endif
                    @if(auth()->user() && auth()->user()->is_admin)
                    <a href="{{ route('api-tokens.index') }}" class="sidebar-link {{ request()->routeIs('api-tokens.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v3m0 0h3m-3 0h-3M6 8.25h4.5m-4.5 4.5h12m-12 4.5h12" /></svg>
                        <span class="sidebar-link-label">Tokens API</span>
                    </a>
                    @endif
                    <a href="{{ route('tracking.index') }}" class="sidebar-link sidebar-link-tracking {{ request()->routeIs('tracking.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="sidebar-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.75h3.75v3.75m0-3.75-5.25 5.25M7.5 17.25H3.75V13.5m0 3.75 5.25-5.25M9 7.5h.008v.008H9V7.5Zm0 9h.008v.008H9V16.5Zm6-9h.008v.008H15V7.5Zm0 9h.008v.008H15V16.5Z" /></svg>
                        <span class="sidebar-link-label">Consultar tracking</span>
                    </a>
                </div>
                @endif
            </nav>
            @auth
            @php
                $sidebarUser = auth()->user();
                $sidebarUserName = trim((string) $sidebarUser->name) !== '' ? $sidebarUser->name : 'Usuario';
                $sidebarInitial = mb_strtoupper(mb_substr($sidebarUserName, 0, 1));
            @endphp
            <div class="sidebar-user">
                <div class="sidebar-user-row">
                    <span class="sidebar-avatar" aria-hidden="true">{{ $sidebarInitial }}</span>
                    <div class="sidebar-user-meta">
                        <p class="sidebar-user-name">{{ $sidebarUser->is_admin ? 'Administrador' : $sidebarUserName }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout">
                        <svg class="sidebar-logout-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                        Cerrar sesión
                    </button>
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
