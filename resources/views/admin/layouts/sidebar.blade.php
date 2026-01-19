<aside id="mainSidebar" class="sidebar bg-[#0f172a] border-r border-slate-800 flex flex-col h-full fixed top-0 left-0 z-50 transition-all duration-300">
    
    {{-- 1. HEADER --}}
    <div class="h-[70px] flex items-center justify-between relative shrink-0 border-b border-slate-800 px-5">
        
        {{-- Logo Area --}}
        <div class="hide-on-collapsed flex items-center gap-3 transition-opacity duration-300 overflow-hidden whitespace-nowrap">
            {{-- Pastikan path gambar benar --}}
            <img src="{{ asset('images/TDS-favicon.png') }}" alt="Logo" class="w-8 h-8 object-contain">
            <div class="flex flex-col">
                <span class="text-white font-bold text-[16px] tracking-tight leading-none">Internal</span>
                <span class="text-[10px] text-indigo-400 uppercase tracking-widest font-bold mt-0.5">System Portal</span>
            </div>
        </div>

        {{-- Toggle Button (Desktop Only) --}}
        {{-- Posisi absolute agar rapi saat collapsed --}}
        <button id="toggle-desktop" class="toggle-btn-custom hidden lg:flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none absolute right-4">
            <span class="hide-on-collapsed material-icons text-[20px]">keyboard_double_arrow_left</span>
            <span class="show-on-collapsed material-icons text-[24px] text-indigo-400">keyboard_double_arrow_right</span>
        </button>
    </div>

    {{-- 2. MENU CONTENT (Scrollable) --}}
    <div class="flex-1 sidebar-scroll py-4 px-3 overflow-y-auto overflow-x-hidden custom-scrollbar">
        {{-- Include partials link --}}
        @include('admin.layouts.partials.sidebar-links')
    </div>

    {{-- 3. FOOTER (PROFIL USER) --}}
    <div class="shrink-0 p-4 border-t border-slate-800 bg-[#0b1120]">
        
        <div class="relative group w-full">
            
            {{-- TOMBOL TRIGGER PROFIL --}}
            <div id="profile-trigger-btn" class="menu-item-profile flex items-center gap-3 p-2 rounded-xl hover:bg-slate-800/50 cursor-pointer transition-all duration-200 w-full border border-transparent hover:border-slate-700">
                
                {{-- Avatar --}}
                <div class="shrink-0 relative">
                    <img src="{{ Auth::user()->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->full_name).'&background=6366f1&color=fff&bold=true' }}" 
                         class="w-10 h-10 rounded-full border-2 border-slate-700 shadow-sm object-cover" 
                         alt="Avatar">
                    <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-[#0b1120] rounded-full"></div>
                </div>
                
                {{-- Teks Info --}}
                <div class="hide-on-collapsed flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-slate-200 truncate leading-tight">{{ Auth::user()->full_name ?? 'Admin User' }}</p>
                    <p class="text-[11px] text-slate-500 truncate mt-0.5 capitalize">{{ Auth::user()->role ?? 'Staff' }}</p>
                </div>

                {{-- Icon More --}}
                <i class="hide-on-collapsed material-icons text-slate-500 text-[18px]">more_vert</i>
            </div>
            
            {{-- POPUP MENU --}}
            {{-- Tambahkan z-index tinggi dan logic bottom positioning --}}
            <div id="profile-popup" class="absolute bottom-[calc(100%+10px)] left-0 w-64 bg-white dark:bg-[#1e293b] rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden opacity-0 invisible transition-all duration-200 transform translate-y-2 z-[9999]">
               
               {{-- Header Popup --}}
               <div class="p-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1">Signed in as</p>
                    <p class="text-xs font-bold text-slate-800 dark:text-white truncate" title="{{ Auth::user()->email }}">
                        {{ Auth::user()->email }}
                    </p>
               </div>
               
               {{-- Menu Links --}}
               <div class="p-1">
                   <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-slate-800 hover:text-indigo-600 rounded-lg transition-colors group/link">
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-500 group-hover/link:bg-indigo-100 group-hover/link:text-indigo-600 transition-colors">
                           <i class="material-icons text-[18px]">badge</i>
                        </span>
                        Edit Profil
                   </a>
               </div>

               <div class="h-px bg-slate-100 dark:bg-slate-700 my-1 mx-3"></div>

               <div class="p-1">
                   <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors text-left font-medium group/logout">
                            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-500 group-hover/logout:bg-rose-100 group-hover/logout:text-rose-600 transition-colors">
                                <i class="material-icons text-[18px]">logout</i>
                            </span>
                            Keluar System
                        </button>
                    </form>
               </div>
            </div>

        </div>
    </div>
</aside>