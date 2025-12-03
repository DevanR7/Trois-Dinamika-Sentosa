<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name', 'Laravel') }} - Client Portal</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/TDS-favicon.png') }}">

    {{-- FONTS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    
    {{-- VITE (Tailwind + Flowbite Styles) --}}
    @vite(['resources/css/admin/app.css', 'resources/css/pages/auth.css', 'resources/js/client/app.js'])

    {{-- Script Anti-Flash Dark Mode --}}
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="min-h-screen w-full font-sans antialiased text-slate-600 dark:text-slate-300 flex items-center justify-center bg-slate-50 dark:bg-[#0b1120] py-10 px-4 overflow-x-hidden">

    {{-- 1. BACKGROUND PARTIKEL --}}
    <div id="particles-bg"></div>

    {{-- 2. CARD WRAPPER UTAMA --}}
    <div class="login-card animate-enter relative w-full max-w-[1000px] min-h-[600px] h-auto bg-white dark:bg-[#1e293b] rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-2 z-10">
        
        {{-- A. PANEL KIRI (BRANDING CLIENT) --}}
        <div class="hidden lg:flex flex-col justify-between p-12 bg-[#0f172a] text-white h-full relative">
            
            {{-- Logo Atas --}}
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/TDS-side-text.png') }}" alt="Logo" class="login-logo h-8 w-auto brightness-0 invert cursor-pointer">
            </div>

            {{-- Teks Tengah --}}
            <div class="flex flex-col justify-center space-y-4 my-auto">
                <h1 class="welcome-title text-4xl font-bold leading-tight tracking-tight cursor-default">
                    Client Portal <br> 
                    <span class="text-indigo-400">Trois Dinamika</span>
                </h1>
                <div class="w-12 h-1 bg-indigo-500 rounded-full"></div>
                <p class="text-slate-300 text-sm leading-relaxed max-w-sm">
                    Selamat datang. Silakan masuk untuk mengakses dashboard proyek, memantau progress, dan melihat laporan Anda secara realtime.
                </p>
            </div>

            {{-- Footer Bawah --}}
            <div class="border-t border-slate-700/50 pt-6 mt-auto">
                <div class="flex flex-col">
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">Hari Ini</span>
                    <div class="flex items-end gap-2">
                        <div id="clock-time" class="text-2xl font-mono font-bold">--:--</div>
                        <div id="clock-date" class="text-xs text-slate-400 mb-1.5">...</div>
                    </div>
                </div>
                <div class="mt-4 text-[10px] text-slate-500">
                    &copy; 2025 Trois Dinamika Sentosa
                </div>
            </div>
        </div>

        {{-- B. PANEL KANAN (CONTAINER FORM) --}}
        <div class="relative p-8 md:p-12 flex flex-col justify-center bg-white dark:bg-[#151f32] h-full transition-colors duration-300">
            
            {{-- TOMBOL DARK MODE (Bulat Sempurna & Presisi) --}}
            <div class="absolute top-6 right-6 z-20" x-data x-cloak>
                <button 
                    @click="$store.darkMode.toggle()" 
                    class="w-10 h-10 p-0 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-yellow-400 hover:bg-slate-200 dark:hover:bg-slate-600 transition-all focus:outline-none shadow-sm hover:shadow-md leading-none"
                    title="Ganti Tema">
                    <i class="material-icons text-xl leading-none" x-show="!$store.darkMode.on">dark_mode</i>
                    <i class="material-icons text-xl leading-none" x-show="$store.darkMode.on" style="display: none;">light_mode</i>
                </button>
            </div>

            {{-- HEADER MOBILE (Hanya Icon Gambar) --}}
            <div class="lg:hidden text-center mb-4">
                <img src="{{ asset('images/TDS-favicon.png') }}" alt="Logo" class="h-12 w-auto mx-auto mb-3 drop-shadow-md">
            </div>

            {{-- CONTENT YIELD --}}
            @yield('content')
        </div>

    </div>

    @stack('scripts')
    
    {{-- Scripts Particles & Clock --}}
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. JAM
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

            // 2. PARTIKEL
            const particleEl = document.getElementById("particles-bg");
            if(particleEl) {
                tsParticles.load("particles-bg", {
                    fpsLimit: 60,
                    interactivity: {
                        events: { onHover: { enable: true, mode: "repulse" }, onClick: { enable: true, mode: "push" }, resize: true },
                        modes: { repulse: { distance: 100, duration: 0.4 }, push: { quantity: 4 } }
                    },
                    particles: {
                        color: { value: "#94a3b8" },
                        links: { color: "#cbd5e1", distance: 150, enable: true, opacity: 0.3, width: 1 },
                        move: { enable: true, speed: 1 },
                        number: { density: { enable: true, area: 800 }, value: 60 },
                        opacity: { value: 0.5 },
                        shape: { type: "circle" },
                        size: { value: { min: 1, max: 3 } }
                    },
                    detectRetina: true
                });
            }
        });
    </script>
</body>
</html>