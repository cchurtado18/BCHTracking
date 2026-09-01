<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Consultar paquete') - PrimeTrack Group</title>
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
        } else {
            echo '<script src="https://cdn.tailwindcss.com"></script>';
        }
    @endphp
    <style>
        :root {
            --pt-navy: #0A2D6F;
            --pt-blue: #1E4FA8;
            --pt-muted: #5E6168;
            --pt-line: #E8EBEF;
            --pt-form: #D8DCE2;
            --pt-soft: #F4F8FD;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
        }
        .trk-page {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 1.5rem 1.1rem 2.5rem;
            background-color: var(--pt-navy);
            background-image:
                radial-gradient(ellipse 80% 60% at 12% 18%, rgba(30, 79, 168, 0.55) 0%, transparent 55%),
                radial-gradient(ellipse 70% 50% at 92% 88%, rgba(10, 45, 111, 0.15) 0%, transparent 50%),
                linear-gradient(165deg, #0A2D6F 0%, #123A86 42%, #1E4FA8 100%),
                url("{{ asset('images/login-bg-texture.png') }}");
            background-size: auto, auto, auto, 420px;
            background-blend-mode: normal, normal, normal, overlay;
        }
        .trk-shell {
            width: 100%;
            max-width: 40rem;
            margin-top: 1.25rem;
            background: #fff;
            border-radius: 1.35rem;
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(4, 16, 48, 0.35);
        }
        .trk-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.95rem 1.35rem;
            background: linear-gradient(180deg, #123A86 0%, #0A2D6F 100%);
        }
        .trk-bar a.trk-logo {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border-radius: 0.75rem;
            padding: 0.28rem 0.55rem;
            line-height: 0;
        }
        .trk-bar img { height: 2.65rem; width: auto; object-fit: contain; display: block; }
        .trk-bar-link {
            font-size: 0.82rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            padding: 0.45rem 0.85rem;
            border-radius: 0.6rem;
            border: 1px solid rgba(255,255,255,0.28);
        }
        .trk-bar-link:hover { background: rgba(255,255,255,0.12); }
        .trk-slot { padding: 1.7rem 1.7rem 1.9rem; }
        @media (max-width: 640px) {
            .trk-page { padding: 0.85rem 0.7rem 1.6rem; }
            .trk-shell { margin-top: 0.35rem; border-radius: 1.1rem; }
            .trk-slot { padding: 1.25rem 1.15rem 1.45rem; }
            .trk-bar { padding: 0.8rem 1rem; }
            .trk-bar img { height: 2.2rem; }
        }
    </style>
</head>
<body>
    <div class="trk-page">
        <div class="trk-shell">
            <header class="trk-bar">
                <a href="{{ route('tracking.index') }}" class="trk-logo" title="PrimeTrack Group">
                    <img src="{{ asset('images/primetrack-group-logo.png') }}?v=2" alt="PrimeTrack Group">
                </a>
                @auth
                @if(auth()->user()->is_admin)
                <a href="{{ route('dashboard') }}" class="trk-bar-link">Ir al panel</a>
                @else
                <a href="{{ route('packages.index') }}" class="trk-bar-link">Mis paquetes</a>
                @endif
                @else
                <a href="{{ route('login') }}" class="trk-bar-link">Iniciar sesión</a>
                @endauth
            </header>
            <div class="trk-slot">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
