<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('app.name') . ' - Client Portal')</title>
 
    {{-- Fonts & Icons --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    {{-- Libraries CSS (Tom Select) --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet" />
 
    {{-- Vite Resources (Khusus Client) --}}
    @vite(["resources/css/client/app.css", "resources/js/client/app.js"])
 
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
 
    {{-- Sidebar Client --}}
    @include('client.layouts.sidebar')

    {{-- Mobile Overlay --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"></div>

    {{-- Main Content Wrapper --}}
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
                {{-- Dark Mode Toggle --}}
                <div x-data>
                    <button 
                        @click="$store.darkMode.toggle()" 
                        class="theme-toggle-btn"
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
                            {{ Auth::guard('client')->user()->client_name ?? 'Client User' }}
                        </div>
                        <div class="user-role">
                            Client Portal
                        </div>
                    </div>
 
                    <div class="user-avatar bg-indigo-600">
                        {{ substr(Auth::guard('client')->user()->client_name ?? 'C', 0, 1) }}
                    </div>
                </div>
            </div>
        </header>

        {{-- Main Page Content --}}
        <main class="flex-1 p-6 sm:p-8 animate-enter relative">
            @yield("content")
        </main>

        {{-- Footer --}}
        <footer class="main-footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Client Portal.
        </footer>
    </div>

    {{-- =====================================================================
         FLOATING ANNOUNCEMENT WIDGET (NEW)
         ===================================================================== --}}
    <div x-data="announcementWidget()" x-init="fetchAnnouncements()" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3 print:hidden">

        {{-- 1. Floating Button --}}
        <button @click="openModal" 
                class="group relative flex items-center justify-center w-14 h-14 bg-[#0f172a] text-white rounded-full shadow-lg hover:bg-indigo-600 hover:scale-105 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-500">
            
            {{-- Icon Lonceng --}}
            <i class="material-icons text-2xl group-hover:animate-swing">campaign</i>
            
            {{-- Badge Count --}}
            <span x-show="count > 0" 
                  x-transition.scale
                  class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white shadow-sm border-2 border-white dark:border-slate-800"
                  x-text="count" style="display: none;">
            </span>
        </button>

        {{-- 2. Announcement Modal / Popover --}}
        <div x-show="isOpen" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-10 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-10 scale-95"
             @click.outside="isOpen = false"
             class="absolute bottom-16 right-0 w-80 sm:w-96 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden origin-bottom-right">
            
            {{-- Header --}}
            <div class="px-5 py-4 bg-[#0f172a] dark:bg-slate-900 flex justify-between items-center">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="material-icons text-base text-indigo-400">notifications_active</i>
                    Pengumuman Terbaru
                </h3>
                <button @click="isOpen = false" class="text-slate-400 hover:text-white transition-colors">
                    <i class="material-icons text-sm">close</i>
                </button>
            </div>

            {{-- Content List --}}
            <div class="max-h-[400px] overflow-y-auto custom-scrollbar bg-slate-50 dark:bg-slate-800/50">
                
                {{-- Loading State --}}
                <div x-show="loading" class="p-6 text-center text-slate-500">
                    <svg class="inline w-6 h-6 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <p class="text-xs mt-2">Memuat...</p>
                </div>

                {{-- Empty State --}}
                <div x-show="!loading && items.length === 0" class="p-8 text-center text-slate-400 dark:text-slate-500">
                    <i class="material-icons text-4xl mb-2 opacity-50">notifications_off</i>
                    <p class="text-sm">Tidak ada pengumuman baru.</p>
                </div>

                {{-- List Items --}}
                <ul x-show="!loading && items.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700">
                    <template x-for="item in items" :key="item.id">
                        <li class="p-4 hover:bg-white dark:hover:bg-slate-700/50 transition-colors group relative">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 mt-0.5">
                                    <span class="flex h-2 w-2 rounded-full bg-indigo-600 ring-2 ring-white dark:ring-slate-800"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-800 dark:text-white truncate" x-text="item.title"></p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5" x-text="item.date"></p>
                                    
                                    {{-- Content Preview --}}
                                    <div class="mt-2 text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-100 dark:bg-slate-900/50 p-2 rounded-lg border border-slate-200 dark:border-slate-700/50">
                                        <span x-html="item.content"></span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
            
            {{-- Footer --}}
            <div class="p-2 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 text-center">
                <button @click="fetchAnnouncements()" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold uppercase tracking-wider flex items-center justify-center gap-1 w-full py-1">
                    <i class="material-icons text-[12px]">refresh</i> Segarkan Data
                </button>
            </div>
        </div>

    </div>

    {{-- Container untuk Toast Notification --}}
    <div id="toast-container"></div>
    
    {{-- Flash Messages Handler --}}
    <script>
        window.laravelFlash = [];
        @if(session('success')) window.laravelFlash.push({ type: 'success', message: "{{ session('success') }}" }); @endif
        @if(session('error')) window.laravelFlash.push({ type: 'error', message: "{{ session('error') }}" }); @endif
        @if(session('info')) window.laravelFlash.push({ type: 'info', message: "{{ session('info') }}" }); @endif
        @if(session('warning')) window.laravelFlash.push({ type: 'warning', message: "{{ session('warning') }}" }); @endif

        // Alpine Logic untuk Announcement Widget
        document.addEventListener('alpine:init', () => {
            Alpine.data('announcementWidget', () => ({
                isOpen: false,
                loading: false,
                items: [],
                count: 0,

                openModal() {
                    this.isOpen = !this.isOpen;
                    if(this.isOpen && this.items.length === 0) {
                        this.fetchAnnouncements();
                    }
                },

                fetchAnnouncements() {
                    this.loading = true;
                    // Pastikan route 'client.api.announcements' sudah didefinisikan di routes/client.php
                    fetch('{{ route("client.api.announcements") }}')
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            this.items = data.data;
                            this.count = data.count;
                        })
                        .catch(error => {
                            console.error('Error fetching announcements:', error);
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                }
            }));
        });
    </script>

    {{-- Stack Scripts --}}
    @stack("scripts")
</body>
</html>