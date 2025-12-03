<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name', 'Laravel') }} - Admin Login</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/TDS-favicon.png') }}">

    {{-- 1. FONTS: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- 2. ICONS: Material Icons --}}
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    
    {{-- 3. BOOTSTRAP (PENTING: Tambahkan ini agar layout rapi seperti Client) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

    {{-- 4. CSS CUSTOM --}}
    @vite(['resources/css/login.css', 'resources/js/app.js'])
</head>
<body>

    {{-- Background Partikel --}}
    <div id="particles-bg"></div>

    {{-- Wrapper Utama --}}
    <div class="login-page-wrapper">
        @yield('content')
    </div>

    {{-- Script Tambahan --}}
    @stack('scripts')
    
    {{-- Library Partikel & Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
    
    {{-- Konfigurasi Partikel --}}
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            tsParticles.load("particles-bg", {
                fpsLimit: 60,
                interactivity: { events: { onHover: { enable: true, mode: "repulse" } } },
                particles: {
                    color: { value: "#94a3b8" },
                    links: { color: "#cbd5e1", distance: 150, enable: true, opacity: 0.4, width: 1 },
                    move: { enable: true, speed: 1 },
                    number: { value: 60, density: { enable: true, area: 800 } },
                    opacity: { value: 0.5 },
                    shape: { type: "circle" },
                    size: { value: { min: 1, max: 3 } },
                }
            });
        });
    </script>
</body>
</html>