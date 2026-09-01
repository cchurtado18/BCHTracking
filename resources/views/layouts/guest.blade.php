<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Iniciar sesión') - {{ config('app.name', 'Laravel') }}</title>

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
            .guest-page {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem 1.1rem;
                background-color: var(--pt-navy);
                background-image:
                    radial-gradient(ellipse 80% 60% at 12% 18%, rgba(30, 79, 168, 0.55) 0%, transparent 55%),
                    radial-gradient(ellipse 70% 50% at 92% 88%, rgba(10, 45, 111, 0.15) 0%, transparent 50%),
                    linear-gradient(165deg, #0A2D6F 0%, #123A86 42%, #1E4FA8 100%),
                    url("{{ asset('images/login-bg-texture.png') }}");
                background-size: auto, auto, auto, 420px;
                background-blend-mode: normal, normal, normal, overlay;
            }
            .guest-shell {
                width: 100%;
                max-width: 56rem;
                display: grid;
                grid-template-columns: minmax(0, 0.92fr) minmax(0, 1.08fr);
                background: #fff;
                border-radius: 1.35rem;
                overflow: hidden;
                box-shadow: 0 24px 64px rgba(4, 16, 48, 0.35);
                min-height: 32rem;
            }
            .guest-brand {
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 2.1rem 1.85rem 1.7rem;
                color: #fff;
                background:
                    radial-gradient(circle at 80% 0%, rgba(255,255,255,0.12) 0%, transparent 42%),
                    linear-gradient(180deg, #123A86 0%, #0A2D6F 100%);
            }
            .guest-brand::after {
                content: "";
                position: absolute;
                inset: auto -2rem -4rem auto;
                width: 14rem;
                height: 14rem;
                border-radius: 50%;
                border: 1px solid rgba(255,255,255,0.12);
                pointer-events: none;
            }
            .guest-brand-mark a {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                border-radius: 1rem;
                padding: 0.55rem 0.85rem;
                line-height: 0;
            }
            .guest-brand-mark img {
                height: 5.75rem;
                width: auto;
                max-width: 100%;
                object-fit: contain;
            }
            .guest-brand-copy h2 {
                margin: 2.4rem 0 0.45rem;
                font-size: 1.55rem;
                font-weight: 800;
                letter-spacing: -0.03em;
                line-height: 1.2;
            }
            .guest-brand-copy p {
                margin: 0;
                max-width: 18rem;
                font-size: 0.9rem;
                line-height: 1.5;
                color: rgba(255,255,255,0.78);
            }
            .guest-brand-foot {
                margin-top: 2rem;
                font-size: 0.72rem;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: rgba(255,255,255,0.5);
                font-weight: 700;
            }
            .guest-panel {
                background: #fff;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .guest-slot { padding: 2rem 2.1rem 2.25rem; height: 100%; }
            @media (max-width: 800px) {
                .guest-shell { grid-template-columns: 1fr; min-height: 0; }
                .guest-brand {
                    padding: 1.35rem 1.4rem 1.2rem;
                    align-items: center;
                    text-align: center;
                }
                .guest-brand-copy h2 { margin-top: 0.85rem; font-size: 1.25rem; }
                .guest-brand-copy p { max-width: none; font-size: 0.84rem; }
                .guest-brand-mark img { height: 4.6rem; }
                .guest-brand-foot { margin-top: 1rem; }
                .guest-slot { padding: 1.35rem 1.25rem 1.6rem; }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="guest-page">
            <div class="guest-shell">
                <aside class="guest-brand">
                    <div class="guest-brand-mark">
                        <a href="{{ route('tracking.index') }}" title="PrimeTrack Group">
                            <img src="{{ asset('images/primetrack-group-logo.png') }}?v=2" alt="PrimeTrack Group" loading="eager">
                        </a>
                    </div>
                    <div class="guest-brand-copy">
                        <h2>Panel de operaciones</h2>
                        <p>Acceda a envíos, rastreo y cobranza desde un solo lugar.</p>
                    </div>
                    <div class="guest-brand-foot">PrimeTrack Group</div>
                </aside>
                <div class="guest-panel">
                        <div class="guest-slot">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
