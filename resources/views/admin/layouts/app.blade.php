<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('app.name'))</title>
 
    {{-- Fonts & Icons --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    {{-- Libraries CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet" />
 
    {{-- Vite Resources --}}
    @vite(["resources/css/admin/app.css", "resources/js/admin/app.js"])
 
    {{-- Theme Script (Mencegah Flash of Light Content) --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>

<body class="layout-body">
 
    {{-- Sidebar --}}
    @include('admin.layouts.sidebar')

    {{-- Mobile Overlay --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"></div>

    {{-- Main Content --}}
    <div class="main-wrapper">
 
        {{-- Top Navigation --}}
        <header class="top-navbar">
            {{-- Left: Toggle & Title --}}
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle" class="nav-toggle-btn">
                    <i class="material-icons">menu</i>
                </button>

                <h1 class="nav-title">
                    @yield('title')
                </h1>
            </div>

            {{-- Right: User Tools --}}
            <div class="nav-tools">
                
                {{-- Dark Mode Toggle (UPDATED) --}}
                <div x-data>
                    <button 
                        @click="$store.darkMode.toggle()" 
                        class="flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-200 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 focus:outline-none"
                        title="Toggle Theme"
                    >
                        <i class="material-icons text-[20px]" x-show="$store.darkMode.on" style="display: none;">light_mode</i>
                        
                        <i class="material-icons text-[20px]" x-show="!$store.darkMode.on">dark_mode</i>
                    </button>
                </div>

                <div class="nav-divider"></div>

                {{-- User Profile --}}
                <div class="user-profile-wrapper">
                    <div class="user-info">
                        <div class="user-name">
                            {{ Auth::user()->full_name ?? 'Admin User' }}
                        </div>
                        <div class="user-role">
                            {{ Auth::user()->role ?? 'Super Admin' }}
                        </div>
                    </div>
 
                    <div class="user-avatar">
                        {{ substr(Auth::user()->full_name ?? 'A', 0, 1) }}
                    </div>
                </div>
            </div>
        </header>

        {{-- Main Page Content --}}
        <main class="flex-1 p-6 sm:p-8 animate-enter">
            @yield("content")
        </main>

        {{-- Footer --}}
        <footer class="main-footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </footer>
    </div>

    <div id="toast-container"></div>
    
    {{-- Flash Messages Handler --}}
    <script>
        window.laravelFlash = [];
        @if(session('success')) window.laravelFlash.push({ type: 'success', message: "{{ session('success') }}" }); @endif
        @if(session('error')) window.laravelFlash.push({ type: 'error', message: "{{ session('error') }}" }); @endif
        @if(session('info')) window.laravelFlash.push({ type: 'info', message: "{{ session('info') }}" }); @endif
        @if(session('warning')) window.laravelFlash.push({ type: 'warning', message: "{{ session('warning') }}" }); @endif
    </script>
    </div>

    {{-- Stack Scripts --}}
    @stack("scripts")
</body>
</html>