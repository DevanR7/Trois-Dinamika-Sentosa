<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('app.name')) - Admin Portal</title>
 
    {{-- Fonts & Icons --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    {{-- Material Icons Google --}}
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    {{-- Libraries CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet" />
    
    {{-- Vite Resources --}}
    @vite(["resources/css/admin/app.css", "resources/js/admin/app.js"])
 
    {{-- Theme Script (Anti-FOUC) --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>

<body class="layout-body text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-[#0f172a] font-sans antialiased">
 
    {{-- Sidebar --}}
    @include('admin.layouts.sidebar')

    {{-- Mobile Overlay --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"></div>

    {{-- Main Content Wrapper --}}
    <div class="main-wrapper flex flex-col min-h-screen transition-all duration-300 ease-in-out">
 
        {{-- Top Navigation --}}
        <header class="top-navbar sticky top-0 z-30 flex items-center justify-between px-6 py-3 bg-white/80 dark:bg-[#0f172a]/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 shadow-sm transition-colors duration-300">
            
            {{-- Left: Toggle & Title --}}
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle" class="nav-toggle-btn lg:hidden p-2 -ml-2 text-slate-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <i class="material-icons">menu</i>
                </button>

                <h1 class="nav-title text-lg font-bold text-[#0f172a] dark:text-white tracking-tight hidden sm:block">
                    @yield('title')
                </h1>
            </div>

            {{-- CENTER: GLOBAL SEARCH (Alpine.js) --}}
            <div class="hidden md:block flex-1 max-w-xl mx-auto px-6 relative" x-data="globalSearch()">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="material-icons text-slate-400 text-[20px] group-focus-within:text-indigo-500 transition-colors">search</i>
                    </div>
                    <input 
                        type="text" 
                        x-model="query"
                        @input.debounce.400ms="performSearch"
                        @keydown.escape="reset"
                        @click.outside="isOpen = false"
                        @focus="isOpen = true"
                        class="block w-full pl-10 pr-12 py-2 text-sm text-slate-700 bg-slate-100 border-transparent rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all dark:bg-slate-800 dark:text-slate-200 dark:focus:bg-slate-900 placeholder:text-slate-400 border-none outline-none shadow-inner" 
                        placeholder="Cari Invoice, Produk, Klien (Ctrl + K)..."
                        autocomplete="off"
                    >
                    {{-- Loading Indicator --}}
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3" x-show="isLoading" style="display: none;">
                        <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    {{-- Shortcut Hint --}}
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" x-show="!query && !isLoading">
                        <span class="text-[10px] font-mono text-slate-400 border border-slate-300 dark:border-slate-600 rounded px-1.5 py-0.5">Ctrl K</span>
                    </div>
                </div>

                {{-- Search Results Dropdown --}}
                <div 
                    x-show="isOpen && (results.length > 0 || query.length >= 2)" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50 max-h-[400px] overflow-y-auto custom-scrollbar"
                    style="display: none;"
                >
                    <template x-if="results.length > 0">
                        <ul class="py-2">
                            <template x-for="item in results" :key="item.url">
                                <li>
                                    <a :href="item.url" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group border-b border-slate-50 dark:border-slate-700/50 last:border-0">
                                        <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-800/50 transition-colors shrink-0">
                                            <i class="material-icons text-[20px]" x-text="item.icon"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate" x-text="item.label"></p>
                                            <p class="text-[10px] text-slate-500 uppercase tracking-wide font-semibold mt-0.5" x-text="item.category"></p>
                                        </div>
                                        <i class="material-icons text-slate-300 text-[18px] group-hover:text-indigo-500 transition-colors -mr-1">chevron_right</i>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </template>
                    
                    <template x-if="results.length === 0 && query.length >= 2 && !isLoading">
                        <div class="p-8 text-center text-slate-500">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700/50 mb-3">
                                <i class="material-icons text-2xl text-slate-400">search_off</i>
                            </div>
                            <p class="text-sm font-medium">Tidak ditemukan hasil untuk "<span class="text-slate-800 dark:text-white" x-text="query"></span>"</p>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Right: User Tools --}}
            <div class="nav-tools flex items-center gap-3 sm:gap-4">
                
                {{-- Dark Mode Toggle --}}
                <div x-data>
                    <button 
                        @click="$store.darkMode.toggle()" 
                        class="theme-toggle-btn w-9 h-9 flex items-center justify-center rounded-full transition-all duration-200 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-yellow-400 focus:outline-none"
                        title="Ganti Tema"
                    >
                        <i class="material-icons text-[20px]" x-show="$store.darkMode.on" style="display: none;">light_mode</i>
                        <i class="material-icons text-[20px]" x-show="!$store.darkMode.on">dark_mode</i>
                    </button>
                </div>

                <div class="nav-divider h-6 w-px bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>

                {{-- User Profile (Info Singkat) --}}
                <div class="user-profile-wrapper flex items-center gap-3 cursor-pointer group" onclick="document.getElementById('profile-trigger-btn').click()">
                    <div class="user-info text-right hidden sm:block">
                        <div class="user-name text-sm font-bold text-slate-700 dark:text-slate-200 leading-tight group-hover:text-indigo-600 transition-colors">
                            {{ Auth::user()->full_name ?? 'Admin User' }}
                        </div>
                        <div class="user-role text-[10px] uppercase font-bold text-slate-400">
                            {{ Auth::user()->role ?? 'Staff' }}
                        </div>
                    </div>
 
                    <div class="user-avatar h-9 w-9 rounded-full bg-[#0f172a] text-white flex items-center justify-center font-bold text-sm shadow-md ring-2 ring-white dark:ring-slate-700 group-hover:ring-indigo-200 transition-all">
                        @if(Auth::user()->avatar_path)
                            <img src="{{ Auth::user()->avatar_url }}" alt="AV" class="w-full h-full object-cover rounded-full">
                        @else
                            {{ substr(Auth::user()->full_name ?? 'A', 0, 1) }}
                        @endif
                    </div>
                </div>
            </div>
        </header>

        {{-- Main Page Content --}}
        <main class="flex-1 p-6 sm:p-8 animate-enter">
            @yield("content")
        </main>

        {{-- Footer --}}
        <footer class="main-footer px-8 py-4 text-center sm:text-left text-xs text-slate-400 dark:text-slate-600 border-t border-slate-200 dark:border-slate-800/50 mt-auto">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-2">
                <span>&copy; {{ date('Y') }} <strong>{{ config('app.name') }}</strong>. All rights reserved.</span>
                <span class="text-[10px]">v{{ $systemVersion ?? '1.0' }}</span>
            </div>
        </footer>
    </div>

{{-- Toast Container --}}
    <div id="toast-container"></div>
    
    {{-- Flash Messages Handler (Robust Version) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            // 1. Data Flash Message dari Session Laravel
            const flashMessages = [
                @if(session('success')) { type: 'success', message: @json(session('success')) }, @endif
                @if(session('error'))   { type: 'error',   message: @json(session('error')) },   @endif
                @if(session('warning')) { type: 'warning', message: @json(session('warning')) }, @endif
                @if(session('info'))    { type: 'info',    message: @json(session('info')) },    @endif
            ];

            // 2. Fungsi untuk Menampilkan Toast dengan Retry Mechanism
            // Ini memastikan toast tetap muncul meskipun app.js lambat dimuat
            function fireToastWhenReady() {
                if (typeof window.showToast === 'function') {
                    // Jika fungsi showToast sudah siap, tampilkan semua pesan
                    flashMessages.forEach(msg => {
                        window.showToast(msg.message, msg.type);
                    });
                } else {
                    // Jika belum siap, cek lagi dalam 100ms
                    setTimeout(fireToastWhenReady, 100);
                }
            }

            // Jalankan jika ada pesan
            if (flashMessages.length > 0) {
                fireToastWhenReady();
            }

            // 3. Init Global Search (Opsional, jika menggunakan Alpine)
            if(typeof globalSearch === 'function' && document.querySelector('[x-data="globalSearch()"]')) {
                // Biarkan Alpine menangani
            }

            // 4. Loading Button Listener
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if(!form.checkValidity()) return;
                    
                    // Jangan loading jika ini form delete (karena ada confirm dialog)
                    const isDelete = form.querySelector('input[name="_method"][value="DELETE"]');
                    if (isDelete) return;

                    const btn = form.querySelector('button[type="submit"]');
                    if(btn && !btn.classList.contains('no-loading')) {
                        btn.classList.add('is-loading');
                        // Safety timeout 15s
                        setTimeout(() => btn.classList.remove('is-loading'), 15000);
                    }
                });
            });
        });
    </script>

    {{-- Stack Scripts --}}
    @stack("scripts")
</body>
</html>