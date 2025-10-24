<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name', 'Laravel') }} - Login</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/TDS-favicon.png') }}">

    {{-- Fonts & CSS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Sans&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    
    @vite(['resources/css/login.css', 'resources/js/app.js'])
</head>
<body>

    <div id="particles-bg"></div>

    <div class="login-page-wrapper">
        <div class="login-card-container">
            
            <div class="login-welcome-section">
                
                <img src="{{ asset('images/TDS-side-text.png') }}" alt="Logo" class="login-logo">

                <h2 class="welcome-title">Internal Portal</h2>
                <p class="welcome-subtitle">
                    Trois Dinamika Sentosa
                </p>
                <p class="welcome-text">
                    Silakan masuk untuk melanjutkan ke dasbor Anda.
                </p>
                <div id="clock-widget" class="mt-4"></div>
            </div>

            <div class="login-form-section">
                
                {{-- Logo sudah dipindah kembali ke kiri --}}

                @yield('content')
            </div>

        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @push('scripts')
    <script>
        function updateClock() {
            const now = new Date();
            const clockElement = document.getElementById('clock-widget');
            if (clockElement) {
                const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const formattedDate = now.toLocaleDateString('id-ID', dateOptions);
                const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'Asia/Jakarta' };
                const formattedTime = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g,':');
                clockElement.innerHTML = `<div>${formattedDate}</div><div>${formattedTime} WIB</div>`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
    @endpush
    @stack('scripts')

    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            tsParticles.load("particles-bg", {
                fpsLimit: 60,
                interactivity: {
                    events: {
                        onHover: {
                            enable: true,
                            mode: "repulse",
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
                        value: "#aaaaaa", // Warna partikel (abu-abu)
                    },
                    links: {
                        color: "#bbbbbb", // Warna garis penghubung
                        distance: 150,
                        enable: true,
                        opacity: 0.5,
                        width: 1,
                    },
                    collisions: {
                        enable: true,
                    },
                    move: {
                        direction: "none",
                        enable: true,
                        outModes: {
                            default: "bounce",
                        },
                        random: false,
                        speed: 1, // Kecepatan gerak
                        straight: false,
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