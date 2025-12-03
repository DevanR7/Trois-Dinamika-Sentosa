<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config("app.name", "Aplikasi Internal") }}</title>
    
    {{-- 1. FONTS (KEMBALI KE INTER) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    {{-- 2. CSS Library --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    
    {{-- 3. VITE --}}
    @vite(["resources/css/admin/app.css", "resources/js/admin/app.js"])
    
    {{-- 4. DARK MODE SCRIPT --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>

    {{-- 5. PREVENT FLICKER SCRIPT --}}
    <script>
        (function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            const isDesktop = window.innerWidth >= 1024;
            if (isCollapsed && isDesktop) {
                document.write(`<style>.main-wrapper { margin-left: 70px !important; }</style>`);
            }
        })();
    </script>
</head>

<body class="bg-[#f8fafc] dark:bg-[#0f172a] font-sans antialiased text-slate-600 dark:text-slate-300">
    
    @include('admin.layouts.sidebar') 

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden transition-opacity backdrop-blur-sm"></div>

    <main class="main-wrapper min-h-screen ml-0 lg:ml-[260px] flex flex-col transition-all duration-300">
        
        {{-- NAVBAR --}}
        <nav class="sticky top-0 z-30 px-6 py-3 flex justify-between items-center h-16 bg-white/80 dark:bg-[#11121a]/90 backdrop-blur-md border-b border-slate-200 dark:border-[#222533]">
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle-mobile" class="lg:hidden p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                    <i class="material-icons">menu</i>
                </button>
                
                <h1 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">
                    @yield('title', 'Dashboard')
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <div x-data>
                    <button @click="$store.darkMode.toggle()" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
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

        <div class="flex-1 p-6">
            @yield("content")
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fireSessionToast = (msg, type) => {
                if (typeof window.showToast === 'function') {
                    window.showToast(msg, type);
                } else {
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