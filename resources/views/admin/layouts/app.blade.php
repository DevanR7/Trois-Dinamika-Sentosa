<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config("app.name", "Aplikasi Internal") }}</title>
    
    {{-- 1. FONTS & ICONS (Google Fonts Tetap Dipertahankan) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    {{-- 2. CSS Library (Hanya Tom Select CSS, sisanya via Vite) --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    
    {{-- 3. VITE (Memanggil CSS Global & JS KHUSUS ADMIN) --}}
    @vite(["resources/css/admin/app.css", "resources/js/admin/app.js"])
    
    {{-- 4. SCRIPT: DARK MODE ANTI-FLICKER --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    
    {{-- 5. SCRIPT: SIDEBAR PRELOADER --}}
    <script>
        (function() {
            const isLocked = localStorage.getItem('isSidebarLocked') === 'true';
            const isDesktop = window.innerWidth >= 1024;
            if (isLocked && isDesktop) {
                document.write(`<style id="nuclear-sidebar-style">#mainSidebar, #mainSidebar.close { width: 260px !important; } .main-wrapper { margin-left: 260px !important; width: calc(100% - 260px) !important; }</style>`);
            }
        })();
    </script>
</head>

<body class="bg-[#f8fafc] dark:bg-[#0f172a] transition-colors duration-300 font-sans antialiased text-slate-600 dark:text-slate-300">
    
    @include('admin.layouts.sidebar') 

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden transition-opacity backdrop-blur-sm"></div>

    <main class="main-wrapper">
        
        {{-- NAVBAR --}}
        <nav class="top-navbar px-6 py-3 flex justify-between items-center h-16">
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 transition lg:hidden">
                    <i class="material-icons text-2xl">menu</i>
                </button>
                <h1 class="text-lg font-bold text-slate-800 dark:text-slate-100 tracking-tight">
                    @yield('title', 'Dashboard')
                </h1>
            </div>

            <div class="flex items-center gap-3">
                
                {{-- DARK MODE SWITCHER --}}
                <div x-data>
                    <button 
                        @click="$store.darkMode.toggle()" 
                        class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition-all duration-200 focus:outline-none flex items-center justify-center" title="Ganti Tema">
                        <i class="material-icons text-[22px]" x-show="$store.darkMode.on" style="display: none;">light_mode</i>
                        <i class="material-icons text-[22px]" x-show="!$store.darkMode.on">dark_mode</i>
                    </button>
                </div>

                <div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-700 mx-1"></div>

                <div class="hidden md:flex flex-col items-end mr-2">
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ Auth::user()->full_name ?? 'Admin' }}</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ Auth::user()->role ?? 'Super User' }}</span>
                </div>
            </div>
        </nav>

        <div class="main-content p-6">
            @yield("content")
        </div>
    </main>
    
    {{-- GLOBAL NOTIFICATION HANDLER --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Kita panggil safeToast setelah DOM siap, karena window.showToast di-init di app.js
            const fireSessionToast = (msg, type) => {
                // Cek apakah window.showToast sudah ada (dari alert.js)
                if (typeof window.showToast === 'function') {
                    window.showToast(msg, type);
                } else {
                    console.warn('Toast module belum dimuat, menampilkan alert standar');
                    alert(msg);
                }
            };

            @if(session('success')) fireSessionToast("{{ session('success') }}", 'success'); @endif
            @if(session('error')) fireSessionToast("{{ session('error') }}", 'error'); @endif
            @if(session('info')) fireSessionToast("{{ session('info') }}", 'info'); @endif
            @if(session('warning')) fireSessionToast("{{ session('warning') }}", 'warning'); @endif
        });
    </script>

    @stack("scripts")
</body>
</html>