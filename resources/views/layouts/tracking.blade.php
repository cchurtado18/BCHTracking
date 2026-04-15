<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Consultar paquete') - BCH Tracking</title>
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
</head>
<body class="bg-gray-50" style="background:
    radial-gradient(circle at 20% 20%, rgba(16, 185, 129, 0.07), transparent 40%),
    radial-gradient(circle at 80% 0%, rgba(59, 130, 246, 0.06), transparent 42%),
    linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);">
    <style>.nav-logo-dark { display: inline-block; line-height: 0; } .nav-logo-dark img { height: 4rem; width: auto; display: block; object-fit: contain; }</style>
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 items-center">
                <a href="{{ route('tracking.index') }}" class="nav-logo-dark">
                    <img src="{{ asset('images/bch-tracking-logo.png') }}" alt="BCH Tracking">
                </a>
                @auth
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-teal-600">
                    ← Regresar
                </a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="py-8">
        <div class="tracking-layout-inner">
            @yield('content')
        </div>
    </main>
    <style>
    .tracking-surface,
    .tracking-layout-inner .bg-white,
    .tracking-layout-inner .card {
        background: #ffffff;
        border: 1px solid #e5ecf3;
        border-radius: 0.75rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 8px 24px rgba(15, 23, 42, 0.03);
    }
    .tracking-layout-inner { max-width: 96rem; margin-left: auto; margin-right: auto; padding-left: 1rem; padding-right: 1rem; width: 100%; }
    @media (min-width: 640px) { .tracking-layout-inner { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { .tracking-layout-inner { padding-left: 2rem; padding-right: 2rem; } }
    </style>
</body>
</html>
