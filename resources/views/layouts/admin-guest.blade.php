<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name', 'Laravel') }} - Login</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/TDS-favicon.png') }}">

    {{-- 1. FONTS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Font Utama Login: Hedvig Letters Sans --}}
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Sans&display=swap" rel="stylesheet">
    {{-- Icon Google --}}
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    
    {{-- 2. VITE (Tailwind CSS & JS) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Override Font Khusus Halaman Login */
        body {
            font-family: 'Hedvig Letters Sans', sans-serif;
        }
        
        /* Memastikan partikel berada di layer paling belakang */
        #particles-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-600 antialiased h-screen w-full overflow-hidden relative flex items-center justify-center">

    {{-- CONTAINER BACKGROUND PARTIKEL --}}
    <div id="particles-bg"></div>

    {{-- WRAPPER KONTEN UTAMA (Z-Index tinggi agar di atas partikel) --}}
    <div class="relative z-10 w-full p-4 flex items-center justify-center">
        @yield('content')
    </div>

    {{-- SCRIPTS KHUSUS HALAMAN (Jika ada push stack) --}}
    @stack('scripts')
    
    {{-- LIBRARY PARTIKEL --}}
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
    
    {{-- KONFIGURASI PARTIKEL (Warna Slate agar selaras) --}}
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            tsParticles.load("particles-bg", {
                fpsLimit: 60,
                interactivity: {
                    events: {
                        onHover: {
                            enable: true,
                            mode: "repulse", // Efek menolak kursor
                        },
                        resize: true,
                    },
                    modes: {
                        repulse: {
                            distance: 100,
                            duration: 0.4,
                        },
                    },
                },
                particles: {
                    color: {
                        value: "#94a3b8", // Warna titik (Slate-400)
                    },
                    links: {
                        color: "#cbd5e1", // Warna garis penghubung (Slate-300)
                        distance: 150,
                        enable: true,
                        opacity: 0.5,
                        width: 1,
                    },
                    move: {
                        enable: true,
                        speed: 1, // Kecepatan gerak lambat & kalem
                        direction: "none",
                        random: false,
                        straight: false,
                        outModes: {
                            default: "bounce",
                        },
                    },
                    number: {
                        density: {
                            enable: true,
                            area: 800,
                        },
                        value: 80, // Jumlah partikel
                    },
                    opacity: {
                        value: 0.5,
                    },
                    shape: {
                        type: "circle",
                    },
                    size: {
                        value: { min: 1, max: 5 },
                    },
                },
                detectRetina: true,
            });
        });
    </script>
</body>
</html>