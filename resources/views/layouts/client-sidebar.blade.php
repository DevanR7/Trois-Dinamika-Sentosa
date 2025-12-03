<nav class="sidebar locked" id="clientSidebar">
    
    {{-- LOGO & BRANDING --}}
    <div class="logo_items">
        <div class="nav_image">
            <img src="{{ asset('images/TDS-favicon.png') }}" alt="logo" class="w-5 h-5 object-contain brightness-0 invert" /> 
        </div>
        <div class="logo_text flex flex-col">
            <span class="logo_name text-lg font-bold text-white tracking-tight leading-tight">Client</span>
            <span class="text-[10px] text-gray-500 font-medium uppercase tracking-wider leading-none">Portal</span>
        </div>
        
        {{-- Close Icon (Mobile Only) - Trigger Alpine di Layout Utama --}}
        <i class="material-icons cursor-pointer text-slate-400 ml-auto text-2xl lg:hidden hover:text-white transition select-none" 
           @click="mobileSidebarOpen = false">close</i>
    </div>

    {{-- MENU ITEMS --}}
    <div class="menu_container" id="sidebarScrollContainer">
        <div class="menu_items">
            @include("layouts.partials.client-sidebar-links")
        </div>
    </div> 

    {{-- PROFILE SECTION (Alpine Dropdown) --}}
    <div class="sidebar_profile relative group" x-data="{ openProfile: false }">
        
        {{-- Profile Menu Popup --}}
        <div x-show="openProfile" 
             @click.away="openProfile = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
             class="absolute bottom-full left-0 w-[240px] bg-slate-800 border-t border-r border-slate-700 mb-2 z-50 shadow-2xl ml-[10px] rounded-t-lg overflow-hidden"
             style="display: none;">
            
            <div class="px-4 py-3 border-b border-slate-700 bg-slate-900/50">
                <p class="text-sm text-white font-bold truncate" title="{{ Auth::guard('client')->user()->client_name }}">
                    {{ Auth::guard('client')->user()->client_name }}
                </p>
                <p class="text-xs text-slate-400 truncate font-mono mt-0.5">
                    {{ Auth::guard('client')->user()->email }}
                </p>
            </div>

            <a href="{{ route('client.profile.edit') }}" class="block px-4 py-2.5 text-sm text-slate-300 hover:bg-slate-700 hover:text-white transition flex items-center gap-3 border-b border-slate-700/50">
                <i class="material-icons text-[18px]">manage_accounts</i>
                <span>Edit Profil</span>
            </a>

            <form action="{{ route('client.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:bg-slate-700 hover:text-red-300 transition flex items-center gap-3">
                    <i class="material-icons text-[18px]">logout</i>
                    <span>Keluar</span>
                </button>
            </form>
        </div>

        {{-- Trigger Button --}}
        <div class="profile_trigger" @click="openProfile = !openProfile">
            <div class="avatar-wrapper">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::guard('client')->user()->client_name) }}&background=6366f1&color=fff&bold=true" class="w-full h-full object-cover" alt="profile" />
            </div>
            
            {{-- LOGIC NAMA: Menggunakan CSS truncate agar tidak terpotong kasar --}}
            <div class="profile_text flex flex-col justify-center overflow-hidden">
                <span class="text-sm font-bold text-white truncate max-w-[140px]" title="{{ Auth::guard('client')->user()->client_name }}">
                    {{ Auth::guard('client')->user()->client_name }}
                </span>
                <span class="text-[10px] text-slate-400 truncate max-w-[140px]" title="{{ Auth::guard('client')->user()->email }}">
                    {{ Auth::guard('client')->user()->email }}
                </span>
            </div>
            
            <i class="material-icons text-slate-500 text-[18px] ml-auto transition-transform duration-200" 
               :class="openProfile ? 'rotate-180 text-white' : ''">expand_less</i>
        </div>
    </div>
</nav>