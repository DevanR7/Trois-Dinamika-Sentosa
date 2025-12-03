<aside id="mainSidebar" class="sidebar">
    
    {{-- 1. HEADER --}}
    {{-- Menggunakan relative positioning untuk handling collapse --}}
    <div class="sidebar-header h-16 flex items-center justify-between px-4 border-b border-[#222533] bg-[#11121a] shrink-0 transition-all duration-300">
        
        {{-- Logo Wrapper (Akan disembunyikan saat collapsed) --}}
        <div class="logo-wrapper flex items-center gap-3 overflow-hidden whitespace-nowrap w-full">
            {{-- REVISI: Mengembalikan IMG tag untuk logo --}}
            <div class="flex items-center justify-center w-8 h-8 shrink-0">
                 <img src="{{ asset('images/TDS-favicon.png') }}" alt="Logo" class="w-full h-full object-contain brightness-0 invert">
            </div>
            
            <div class="logo-text flex flex-col justify-center min-w-0 transition-opacity duration-300">
                <span class="text-white font-bold text-lg tracking-tight leading-none truncate">Internal</span>
                <span class="text-[10px] text-[#b0b3c1] uppercase tracking-widest font-semibold mt-0.5 truncate">Portal System</span>
            </div>
        </div>

        {{-- Toggle Button --}}
        {{-- Saat collapsed, button ini akan menjadi satu-satunya elemen di tengah --}}
        <button id="toggle-sidebar-btn" class="hidden lg:flex items-center justify-center w-8 h-8 rounded-lg text-[#b0b3c1] hover:text-white hover:bg-[#1a1c29] transition-all focus:outline-none shrink-0">
            <i class="material-icons text-[20px] transition-transform duration-300" id="toggle-icon">keyboard_double_arrow_left</i>
        </button>
    </div>

    {{-- 2. MENU LIST --}}
    <div class="flex-1 sidebar-scroll py-4 space-y-1">
        @include('admin.layouts.partials.sidebar-links')
    </div>

    {{-- 3. FOOTER (Profile) --}}
    <div class="border-t border-[#222533] bg-[#0b0c11] p-4 shrink-0">
        <div class="relative group w-full">
            <div class="flex items-center gap-3 cursor-pointer overflow-hidden profile-wrapper" onclick="document.getElementById('profile-popup').classList.toggle('hidden')">
                <div class="w-9 h-9 rounded-full border border-[#222533] overflow-hidden shrink-0">
                    <img src="https://ui-avatars.com/api/?name={{ Auth::user()->full_name ?? 'AD' }}&background=5e63ff&color=fff&bold=true" 
                         class="w-full h-full object-cover" alt="Avatar">
                </div>
                <div class="sidebar-text flex flex-col min-w-0 flex-1 transition-opacity duration-300">
                    <span class="text-sm font-bold text-white truncate">{{ Auth::user()->full_name ?? 'Admin' }}</span>
                    <span class="text-[10px] text-[#b0b3c1] truncate">Online</span>
                </div>
            </div>

            <div id="profile-popup" class="hidden absolute bottom-16 left-0 w-60 bg-white dark:bg-[#1a1c29] rounded-xl shadow-2xl border border-slate-200 dark:border-[#222533] overflow-hidden z-[110]">
                <div class="p-3 border-b border-slate-100 dark:border-[#222533] bg-slate-50 dark:bg-[#11121a]">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Signed in as</p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->email }}</p>
                </div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 transition text-left">
                        <i class="material-icons text-[18px]">logout</i> Keluar Aplikasi
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>