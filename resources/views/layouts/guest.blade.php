<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name', 'Laravel') }} - Client Portal</title>
    
    <link rel="icon" type="image/x-icon" href="{{ asset('images/TDS-favicon.png') }}">

    {{-- 1. FONTS & ICONS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    {{-- 2. BOOTSTRAP (Agar layout form rapi) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

    {{-- 3. MAIN CSS --}}
    @vite(['resources/css/login.css', 'resources/js/app.js'])

    {{-- 
        =====================================================
        OVERRIDE WARNA KHUSUS CLIENT (TEMA TEAL / EMERALD)
        =====================================================
    --}}
    <style>
    /* --- TEMA: ROYAL VIOLET --- */

    /* 1. Background Kiri */
    .login-welcome-section {
        background-color: #2e1065 !important; /* Violet-950 */
    }

    /* 2. Tombol Utama */
    .btn-login {
        background-color: #5b21b6 !important; /* Violet-800 */
        border-color: #5b21b6 !important;
    }
    .btn-login:hover {
        background-color: #4c1d95 !important; /* Violet-900 */
        border-color: #4c1d95 !important;
    }

    /* 3. Hiasan Blobs (Nuansa Ungu & Pink) */
    .blob-1 { background-color: #a855f7 !important; opacity: 0.3; } /* Purple-500 */
    .blob-2 { background-color: #d946ef !important; opacity: 0.3; } /* Fuchsia-500 */

    /* 4. Teks & Link */
    .welcome-subtitle { color: #e9d5ff !important; } /* Violet-200 */
    .forgot-link, .redirect-link, a.fw-bold { color: #6d28d9 !important; } /* Violet-700 */
    .forgot-link:hover, .redirect-link:hover, a.fw-bold:hover { color: #5b21b6 !important; }

    /* 5. Focus Input */
    .form-control:focus {
        border-color: #7c3aed !important;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15) !important;
    }
    .form-control:focus + .input-icon,
    .input-wrapper:focus-within .input-icon,
    .input-icon i[style*="color: #4f46e5"] { color: #7c3aed !important; }
</style>
</head>
<body>

    <div id="particles-bg"></div>

    <div class="login-page-wrapper">
        <div class="login-card-container">
            
            {{-- BAGIAN KIRI --}}
            <div class="login-welcome-section">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>

                <div style="position: relative; z-index: 10;">
                    <img src="{{ asset('images/TDS-side-text.png') }}" alt="Logo" class="login-logo">
                    
                    <h2 class="welcome-title">Client Portal</h2>
                    <p class="welcome-subtitle">Trois Dinamika Sentosa</p>
                    <p class="welcome-text">
                        Selamat datang. Silakan masuk untuk mengakses dashboard proyek dan laporan Anda.
                    </p>
                </div>

                {{-- Widget Jam --}}
                <div id="clock-widget">
                    <div class="clock-label" style="color: #99f6e4;">HARI INI</div> {{-- Teal muda --}}
                    <div class="clock-time" id="clock-time">--:--</div>
                    <div class="clock-date" id="clock-date" style="color: #ccfbf1;">...</div>
                </div>
            </div>

            {{-- BAGIAN KANAN --}}
            <div class="login-form-section">
                @yield('content')
            </div>

        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Logic Jam
            function updateClock() {
                const now = new Date();
                const timeEl = document.getElementById('clock-time');
                const dateEl = document.getElementById('clock-date');
                if (timeEl && dateEl) {
                    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                    const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: false };
                    timeEl.innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':') + ' WIB';
                    dateEl.innerText = now.toLocaleDateString('id-ID', dateOptions);
                }
            }
            setInterval(updateClock, 1000);
            updateClock();

            // Logic Partikel
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
    @stack('scripts')
</body>
</html>