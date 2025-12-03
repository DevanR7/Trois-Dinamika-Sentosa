<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Client Portal - {{ config('app.name', 'Laravel') }}</title>

    {{-- FONTS & ICONS (Samakan dengan Admin agar CSS .sidebar icon berfungsi) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    
    {{-- CSS Libraries (Select2 & SweetAlert - Sesuai Admin) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    @stack('styles')

    {{-- VITE (Tailwind) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Script Dark Mode (Anti-Flicker) --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
{{-- Tambahkan x-data untuk mengontrol state global (Sidebar Mobile & Pengumuman) --}}
<body class="bg-[#f8fafc] dark:bg-[#0f172a] transition-colors duration-300 font-sans antialiased text-slate-600 dark:text-slate-300"
      x-data="{ 
          mobileSidebarOpen: false, 
          announcementOpen: false 
      }">

    {{-- Overlay untuk Mobile Sidebar --}}
    <div x-show="mobileSidebarOpen" 
         @click="mobileSidebarOpen = false"
         x-transition.opacity
         class="fixed inset-0 bg-black/50 z-40 lg:hidden backdrop-blur-sm"
         style="display: none;"></div>

    {{-- SIDEBAR --}}
    @include('layouts.client-sidebar')

    {{-- MAIN WRAPPER (Sesuai app.css) --}}
    <main class="main-wrapper">
        
        {{-- NAVBAR CLIENT (Sederhana) --}}
        <nav class="top-navbar px-6 py-3 flex justify-between items-center h-16">
            <div class="flex items-center gap-4">
                {{-- Tombol Toggle Sidebar Mobile --}}
                <button @click="mobileSidebarOpen = !mobileSidebarOpen" 
                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 transition lg:hidden">
                    <i class="material-icons text-2xl">menu</i>
                </button>
                <h1 class="text-lg font-bold text-slate-800 dark:text-slate-100 tracking-tight">
                    @yield('title', 'Portal Client')
                </h1>
            </div>

            <div class="flex items-center gap-3">
                 {{-- Dark Mode Toggle --}}
                <div x-data>
    <button 
        @click="$store.darkMode.toggle()" 
        class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors duration-200 focus:outline-none
               text-slate-500 hover:bg-slate-100 hover:text-slate-700
               dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
        title="Ganti Tema">
        
        {{-- Icon Matahari --}}
        <i class="material-icons text-[20px]" x-show="$store.darkMode.on" style="display: none;">light_mode</i>
        
        {{-- Icon Bulan --}}
        <i class="material-icons text-[20px]" x-show="!$store.darkMode.on">dark_mode</i>
    </button>
</div>

                {{-- Tombol Lonceng (Trigger Panel Pengumuman) --}}
                @if(isset($activeAnnouncements) && $activeAnnouncements->isNotEmpty())
                <button @click="announcementOpen = true" class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition">
                    <i class="material-icons text-[22px]">notifications</i>
                    <span class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                    </span>
                </button>
                @endif
            </div>
        </nav>

        {{-- KONTEN UTAMA --}}
        <div class="main-content p-6">
            @yield('content')
        </div>

    </main>

    {{-- PANEL PENGUMUMAN (Pengganti Offcanvas Bootstrap) --}}
    @if(isset($activeAnnouncements) && $activeAnnouncements->isNotEmpty())
    <div class="relative z-[1001]" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" style="display: none;" x-show="announcementOpen">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             x-show="announcementOpen"
             x-transition.opacity
             @click="announcementOpen = false"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-md"
                         x-show="announcementOpen"
                         x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full">
                        
                        <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-800 shadow-xl border-l border-slate-200 dark:border-slate-700">
                            <div class="px-4 py-6 sm:px-6 border-b border-slate-100 dark:border-slate-700">
                                <div class="flex items-start justify-between">
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100" id="slide-over-title">Pengumuman</h2>
                                    <div class="ml-3 flex h-7 items-center">
                                        <button type="button" class="rounded-md bg-white dark:bg-slate-800 text-slate-400 hover:text-slate-500 focus:outline-none" @click="announcementOpen = false">
                                            <span class="sr-only">Close panel</span>
                                            <i class="material-icons">close</i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="relative mt-6 flex-1 px-4 sm:px-6">
                                {{-- List Pengumuman --}}
                                <div class="space-y-6">
                                    @foreach($activeAnnouncements as $announcement)
                                    <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-100 dark:border-slate-600">
                                        @if($announcement->title)
                                            <h3 class="font-bold text-indigo-600 dark:text-indigo-400 mb-1">{{ $announcement->title }}</h3>
                                        @endif
                                        <div class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line leading-relaxed">
                                            {!! $announcement->content !!}
                                        </div>
                                        <div class="mt-3 text-xs text-slate-400 flex items-center gap-1">
                                            <i class="material-icons text-[14px]">schedule</i>
                                            {{ $announcement->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- SCRIPTS LIBRARY --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

    {{-- NOTIFICATION HANDLER --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const safeToast = (msg, type) => {
                if (typeof window.showToast === 'function') {
                    window.showToast(msg, type);
                } else {
                    Swal.fire({ toast: true, position: 'top-end', icon: type, title: msg, showConfirmButton: false, timer: 3000, timerProgressBar: true });
                }
            };

            @if(session('success')) safeToast("{{ session('success') }}", 'success'); @endif
            @if(session('error')) safeToast("{{ session('error') }}", 'error'); @endif
            @if(session('info')) safeToast("{{ session('info') }}", 'info'); @endif
        });
    </script>

    @stack('scripts')
</body>
</html>