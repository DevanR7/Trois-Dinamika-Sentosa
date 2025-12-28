<aside id="mainSidebar" class="sidebar">
    
    {{-- 1. HEADER (Logo & Toggle) --}}
    <div class="h-[70px] flex items-center relative shrink-0 border-b border-white/5 bg-[#0f172a]">
        
        {{-- Logo Area --}}
        <div class="hide-on-collapsed flex items-center w-full px-5 transition-opacity duration-300">
            <div class="flex items-center gap-3">
                {{-- Pastikan path logo sesuai --}}
                <img src="{{ asset('images/TDS-favicon.png') }}" alt="Logo" class="w-8 h-8 object-contain brightness-0 invert">
                <div class="flex flex-col">
                    <span class="text-white font-bold text-[16px] tracking-tight leading-none">Client</span>
                    <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mt-0.5">Portal</span>
                </div>
            </div>
        </div>

        {{-- Toggle Button (Desktop) --}}
        {{-- Tombol ini ditangkap oleh sidebar.js (initSidebarLogic) --}}
        <button id="toggle-desktop" class="toggle-btn-custom hidden lg:flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 z-20 focus:outline-none">
            <span class="hide-on-collapsed material-icons text-[20px]">keyboard_double_arrow_left</span>
            <span class="show-on-collapsed material-icons text-[24px] text-indigo-400">keyboard_double_arrow_right</span>
        </button>

        {{-- Close Button (Mobile Only) --}}
        <button id="sidebar-toggle" class="lg:hidden absolute right-4 text-slate-400 hover:text-white">
            <i class="material-icons">close</i>
        </button>
    </div>

    {{-- 2. MENU CONTENT --}}
    <div class="flex-1 sidebar-scroll py-4">
        @include('client.layouts.partials.sidebar-links')
    </div>

    {{-- 3. FOOTER (PROFIL USER) --}}
    <div class="shrink-0 p-3 border-t border-white/5 bg-[#0b1120] flex flex-col justify-center">
        
        <div class="relative group w-full">
            
            {{-- TOMBOL TRIGGER PROFIL --}}
            {{-- Menggunakan ID 'profile-trigger-btn' agar sidebar.js bisa meng-handle klik --}}
            <div id="profile-trigger-btn" class="menu-item-profile flex items-center p-2 rounded-xl hover:bg-white/5 cursor-pointer transition-colors w-full">
                
                {{-- Avatar --}}
                <div class="menu-icon-col !w-10 !min-w-[40px] flex justify-center shrink-0 mx-auto lg:mx-0 transition-all">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::guard('client')->user()->client_name ?? 'Client') }}&background=6366f1&color=fff&bold=true" 
                         class="w-9 h-9 rounded-full border border-slate-600 shadow-sm object-cover" alt="Avatar">
                </div>
                
                {{-- Teks (Nama & Email) --}}
                <div class="hide-on-collapsed flex-1 pl-3 min-w-0">
                    <p class="text-[13px] font-bold text-slate-200 truncate" title="{{ Auth::guard('client')->user()->client_name }}">
                        {{ Auth::guard('client')->user()->client_name ?? 'Client User' }}
                    </p>
                    <p class="text-[9px] text-slate-500 uppercase tracking-wider truncate" title="{{ Auth::guard('client')->user()->email }}">
                        {{ Auth::guard('client')->user()->email }}
                    </p>
                </div>

                {{-- Panah --}}
                <i class="hide-on-collapsed material-icons text-slate-500 text-[18px] ml-auto">expand_less</i>
            </div>
            
            {{-- POPUP MENU --}}
            {{-- Menggunakan ID 'profile-popup' agar sidebar.js bisa meng-handle show/hide --}}
            <div id="profile-popup" class="absolute bottom-[calc(100%+12px)] left-0 w-64 bg-white dark:bg-[#1e293b] rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
               
               <div class="p-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                    <p class="text-[10px] text-slate-500">Signed in as</p>
                    <p class="text-xs font-bold dark:text-white truncate">{{ Auth::guard('client')->user()->email }}</p>
               </div>
               
               <a href="{{ route('client.profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                   <i class="material-icons text-[18px]">manage_accounts</i> Edit Profil
               </a>

               <form action="{{ route('client.logout') }}" method="POST">
                @csrf
                <button class="w-full flex items-center gap-3 px-4 py-3 text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition text-left font-medium">
                    <i class="material-icons text-[18px]">logout</i> Keluar
                </button>
            </form>
            </div>
        </div>
    </div>
</aside>