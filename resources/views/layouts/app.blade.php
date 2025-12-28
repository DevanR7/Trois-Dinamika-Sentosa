<!DOCTYPE html>
{{-- Tambahkan class 'dark' secara conditional jika perlu, tapi script di head lebih aman --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config("app.name", "Aplikasi Internal") }}</title>
    
    {{-- FONTS & ICONS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    {{-- LIBRARIES --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    {{-- VITE --}}
    @vite(["resources/css/app.css", "resources/js/app.js"])
    
    {{-- SCRIPT PENTING: DARK MODE ANTI-FLICKER --}}
    <script>
        // Cek LocalStorage segera setelah head dimuat
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    
    {{-- SCRIPT: SIDEBAR PRELOADER --}}
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

{{-- Body class updated untuk transition background --}}
<body class="bg-[#f8fafc] dark:bg-[#0f172a] transition-colors duration-300 font-sans antialiased text-slate-600 dark:text-slate-300">
    
    @include('layouts.sidebar') 

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden transition-opacity backdrop-blur-sm"></div>

    <main class="main-wrapper">
        
        {{-- NAVBAR DENGAN TOMBOL TOGGLE --}}
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
                
                {{-- TOMBOL DARK MODE SWITCHER (Alpine Component) --}}
                <div x-data>
                    <button 
                        @click="$store.darkMode.toggle()" 
                        class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition-all duration-200 focus:outline-none 
                        flex items-center justify-center" title="Ganti Tema">
                        
                        {{-- Icon Matahari (Muncul saat Dark Mode ON) --}}
                        <i class="material-icons text-[22px]" x-show="$store.darkMode.on" style="display: none;">light_mode</i>
                        
                        {{-- Icon Bulan (Muncul saat Dark Mode OFF) --}}
                        <i class="material-icons text-[22px]" x-show="!$store.darkMode.on">dark_mode</i>
                    </button>
                </div>

                <div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-700 mx-1"></div>

                {{-- User Info (Contoh Sederhana) --}}
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

    {{-- SCRIPTS LIBRARY --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
    
    {{-- ✅ GLOBAL NOTIFICATION HANDLER (PERBAIKAN UTAMA) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fungsi helper jika window.showToast belum ready (fallback)
            const safeToast = (msg, type) => {
                if (typeof window.showToast === 'function') {
                    window.showToast(msg, type);
                } else {
                    // Fallback ke SweetAlert biasa jika showToast belum ter-load
                    window.confirmDialog({
                        toast: true,
                        position: 'top-end',
                        icon: type,
                        title: msg,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                }
            };

            @if(session('success'))
                safeToast("{{ session('success') }}", 'success');
            @endif

            @if(session('error'))
                safeToast("{{ session('error') }}", 'error');
            @endif

            @if(session('info'))
                safeToast("{{ session('info') }}", 'info');
            @endif
            
            @if(session('warning'))
                safeToast("{{ session('warning') }}", 'warning');
            @endif
        });
    </script>

    @stack("scripts")
</body>
</html>