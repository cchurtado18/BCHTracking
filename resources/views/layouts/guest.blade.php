<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Iniciar sesión') - {{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

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
            .guest-page {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 1.5rem 1rem;
                /* Fondo: foto logística + degradado suave para que el texto se lea bien */
                background-image:
                    linear-gradient(160deg, rgba(15, 23, 42, 0.55) 0%, rgba(15, 23, 42, 0.40) 45%, rgba(15, 23, 42, 0.20) 100%),
                    url("{{ asset('images/login-warehouse.jpg') }}");
                background-size: cover;
                background-position: center center;
                background-repeat: no-repeat;
                gap: 0;
            }
            .guest-logo-wrap { margin-bottom: 1.5rem; text-align: center; }
            .guest-logo-wrap a { display: inline-block; line-height: 0; padding: 0.5rem; }
            .guest-logo-wrap img { height: 8rem; width: auto; max-width: 420px; object-fit: contain; display: block; }
            @media (min-width: 480px) {
                .guest-logo-wrap img { height: 10rem; max-width: 520px; }
            }
            .guest-card { width: 100%; max-width: 32rem; background: #fff; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08), 0 10px 20px -5px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; overflow: hidden; }
            .guest-slot { padding: 0; }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased" style="margin:0;">
        <div class="guest-page">
            <div class="guest-logo-wrap">
                <a href="{{ route('tracking.index') }}" title="BCH Tracking">
                    <img src="{{ asset('images/bch-tracking-logo.png') }}" alt="BCH Tracking" loading="eager">
                </a>
            </div>
            <div class="guest-card">
                <div class="guest-slot">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
