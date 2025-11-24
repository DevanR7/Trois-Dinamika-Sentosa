<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config("app.name", "Aplikasi Internal") }}</title>
    
    {{-- FONTS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    {{-- LIBRARIES --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    {{-- VITE ASSETS --}}
    @vite(["resources/css/app.css", "resources/js/app.js"])
    @stack('styles')

    {{-- ========================================================================= --}}
    {{-- [NUCLEAR FIX] SYNCHRONOUS STYLE INJECTION                                 --}}
    {{-- Menulis CSS langsung ke dokumen agar browser TIDAK BISA menggambar 80px   --}}
    {{-- ========================================================================= --}}
    <script>
        (function() {
            // Cek LocalStorage
            const isLocked = localStorage.getItem('isSidebarLocked') === 'true';
            
            // Jika Locked & Desktop, SUNTIKKAN CSS PAKSAAN SEKARANG JUGA
            if (isLocked && window.innerWidth >= 1024) {
                document.write(`
                    <style id="anti-flicker-style">
                        /* 1. Paksa Sidebar Lebar (Timpa class .close) */
                        #mainSidebar.sidebar, 
                        #mainSidebar.sidebar.close { 
                            width: 260px !important; 
                        }
                        
                        /* 2. Paksa Konten Geser */
                        .main-wrapper { 
                            margin-left: 260px !important; 
                            width: calc(100% - 260px) !important;
                            transition: none !important; /* Matikan animasi */
                        }

                        /* 3. Paksa Elemen Teks Muncul */
                        #mainSidebar .logo_text,
                        #mainSidebar .link span,
                        #mainSidebar .dropdown-icon,
                        #mainSidebar .profile_text,
                        #mainSidebar #sidebar-close {
                            opacity: 1 !important; visibility: visible !important; 
                            position: static !important; width: auto !important; 
                            pointer-events: auto !important;
                        }

                        /* 4. Fix Judul Menu */
                        #mainSidebar .menu_title {
                            height: auto !important; margin-top: 1.5rem !important; margin-bottom: 0.5rem !important;
                            width: auto !important; font-size: 10px !important; 
                            padding-left: 20px !important; text-align: left !important; opacity: 0.8 !important;
                        }
                        
                        /* 5. Tampilkan Icon Lock */
                        #mainSidebar #lock-icon { display: block !important; }

                        /* 6. Fix Dropdown (Sembunyikan kecuali yg open) */
                        #mainSidebar .submenu { display: none !important; }
                        #mainSidebar .has-submenu.open .submenu { display: flex !important; }
                    </style>
                `);
            }
        })();
    </script>
</head>
<body class="bg-[#f8fafc]">
    
    @include('layouts.sidebar') 

    <main class="main-wrapper">
        <nav class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center sticky top-0 z-40 shadow-sm h-16">
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle" class="p-2 rounded-md hover:bg-gray-100 text-gray-600 transition focus:outline-none">
                    <i class="material-icons text-2xl">menu</i>
                </button>
                <h1 class="text-lg font-bold text-gray-800">
                    @yield('title', 'Dashboard')
                </h1>
            </div>
            <div class="flex items-center gap-4">
                {{-- User Area --}}
            </div>
        </nav>

        <div class="main-content p-6">
            @yield("content")
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

    {{-- ======================================================= --}}
    {{-- LOGIKA JAVASCRIPT UTAMA (CLEANUP & HANDOVER)            --}}
    {{-- ======================================================= --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector(".sidebar");
        const sidebarToggle = document.querySelector("#sidebar-toggle");
        const sidebarCloseMobile = document.querySelector("#sidebar-close");
        const lockIcon = document.querySelector("#lock-icon");
        
        let isSidebarLocked = localStorage.getItem('isSidebarLocked') === 'true';

        // 1. SCROLL PERSISTENCE
        const scrollContainer = document.getElementById('sidebarScrollContainer');
        if (scrollContainer) {
            const savedScrollPos = localStorage.getItem('sidebarScrollPos');
            if (savedScrollPos) scrollContainer.scrollTop = parseInt(savedScrollPos);
            scrollContainer.addEventListener('scroll', () => {
                localStorage.setItem('sidebarScrollPos', scrollContainer.scrollTop);
            });
        }

        // 2. SEAMLESS HANDOVER (Preload CSS -> Real JS Class)
        // Gunakan setTimeout 0 agar script mengantri tepat setelah render awal selesai
        setTimeout(() => {
            // A. Pasang class 'locked' Javascript DULUAN
            if (window.innerWidth >= 1024 && isSidebarLocked) {
                sidebar.classList.remove('close');
                sidebar.classList.add('locked');
            }

            // B. Hapus Element Style Paksaan
            // Kita cari elemen style yg kita buat tadi dan HAPUS
            const antiFlickerStyle = document.getElementById('anti-flicker-style');
            if(antiFlickerStyle) {
                antiFlickerStyle.remove();
            }
            
            // C. Nyalakan Animasi
            setTimeout(() => {
                sidebar.classList.add('sidebar-ready');
            }, 50);
            
            // D. Update Icon Lock
            if (lockIcon) {
                if (isSidebarLocked) {
                    lockIcon.innerText = 'radio_button_unchecked'; // Locked
                    lockIcon.classList.add('text-indigo-400');
                } else {
                    lockIcon.innerText = 'radio_button_checked'; // Unlocked
                    lockIcon.classList.remove('text-indigo-400');
                }
            }
        }, 0); 

        // 3. FUNGSI UPDATE UI
        const updateLockUI = (locked) => {
            if(!lockIcon) return;
            if(locked) {
                lockIcon.innerText = 'radio_button_unchecked';
                lockIcon.classList.add('text-indigo-400');
            } else {
                lockIcon.innerText = 'radio_button_checked';
                lockIcon.classList.remove('text-indigo-400');
            }
        };

        // 4. EVENT LISTENERS
        sidebar.addEventListener('mouseenter', () => {
            if (window.innerWidth >= 1024 && !isSidebarLocked) {
                sidebar.classList.add('hover-expand');
            }
        });

        sidebar.addEventListener('mouseleave', () => {
            if (window.innerWidth >= 1024 && !isSidebarLocked) {
                sidebar.classList.remove('hover-expand');
            }
        });

        if (lockIcon) {
            lockIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                isSidebarLocked = !isSidebarLocked;
                localStorage.setItem('isSidebarLocked', isSidebarLocked);

                if (isSidebarLocked) {
                    sidebar.classList.add('locked');
                    sidebar.classList.remove('close', 'hover-expand');
                    updateLockUI(true);
                } else {
                    sidebar.classList.remove('locked');
                    sidebar.classList.add('hover-expand'); 
                    updateLockUI(false);
                }
            });
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    sidebar.classList.toggle('close');
                } else {
                    if (sidebar.classList.contains('locked')) {
                        isSidebarLocked = false;
                        sidebar.classList.remove('locked');
                        sidebar.classList.add('close');
                        updateLockUI(false);
                    } else {
                        isSidebarLocked = true;
                        sidebar.classList.add('locked');
                        sidebar.classList.remove('close', 'hover-expand');
                        updateLockUI(true);
                    }
                    localStorage.setItem('isSidebarLocked', isSidebarLocked);
                }
            });
        }
        
        const mobileClose = document.querySelector("#sidebar-close");
        if(mobileClose) mobileClose.addEventListener('click', () => sidebar.classList.add('close'));

        window.addEventListener('resize', () => {
            if (window.innerWidth < 1024) {
                sidebar.classList.add('close');
                sidebar.classList.remove('locked', 'hover-expand');
            } else {
                if(isSidebarLocked) {
                    sidebar.classList.add('locked');
                    sidebar.classList.remove('close');
                } else {
                    sidebar.classList.add('close');
                    sidebar.classList.remove('locked');
                }
            }
        });
        
        document.querySelectorAll('.has-submenu > .link').forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                this.parentElement.classList.toggle('open');
            });
        });

        const profileTrigger = document.querySelector('.profile_trigger');
        if (profileTrigger) {
            profileTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                const menu = document.getElementById('profile-menu');
                const chevron = document.getElementById('profile-chevron');
                if(menu) menu.classList.toggle('hidden');
                if(chevron) chevron.classList.toggle('rotate-180');
            });
        }

        document.addEventListener('click', function(event) {
            const sidebarProfile = document.querySelector('.sidebar_profile');
            const menu = document.getElementById('profile-menu');
            const chevron = document.getElementById('profile-chevron');
            if (sidebarProfile && !sidebarProfile.contains(event.target)) {
                 if(menu && !menu.classList.contains('hidden')) {
                     menu.classList.add('hidden');
                     if(chevron) chevron.classList.remove('rotate-180');
                 }
            }
        });
    });
    </script>
    
    @stack("scripts")
</body>
</html>