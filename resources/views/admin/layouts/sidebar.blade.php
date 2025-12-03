<nav class="sidebar preload" id="mainSidebar">
    
    {{-- LOGO & BRANDING --}}
    <div class="logo_items">
        <div class="nav_image">
            <img src="{{ asset('images/TDS-favicon.png') }}" alt="logo" class="w-5 h-5 object-contain brightness-0 invert" /> 
        </div>
        <div class="logo_text flex flex-col overflow-hidden transition-opacity duration-200">
            <span class="logo_name text-lg font-bold text-white tracking-tight leading-tight whitespace-nowrap">Internal</span>
            <span class="text-[10px] text-gray-500 font-medium uppercase tracking-wider leading-none whitespace-nowrap">System</span>
        </div>
        
        {{-- Lock Icon (Desktop Only) --}}
        <i class="material-icons text-slate-500 hover:text-indigo-400 cursor-pointer text-[20px] ml-auto transition-all duration-200 mr-2 select-none hidden lg:block" 
           id="lock-icon" 
           title="Kunci Sidebar">radio_button_unchecked</i>
        
        {{-- Close Icon (Mobile Only) --}}
        <i class="material-icons cursor-pointer text-slate-400 ml-auto text-2xl lg:hidden hover:text-white transition select-none" id="sidebar-close">close</i>
    </div>

    {{-- MENU ITEMS --}}
    <div class="menu_container" id="sidebarScrollContainer">
        <div class="menu_items">
            @include("admin.layouts.partials.sidebar-links")
        </div>
    </div>

    {{-- PROFILE SECTION --}}
    <div class="sidebar_profile relative group">
        {{-- Profile Menu Popup (Custom) --}}
        <div id="profile-menu" class="hidden absolute bottom-full left-0 w-[240px] bg-slate-800 border-t border-r border-slate-700 mb-0 transition-all duration-200 z-50 shadow-2xl ml-[10px] rounded-t-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-700 bg-slate-900/50">
                <p class="text-sm text-white font-bold truncate">{{ Auth::user()->full_name ?? 'User Name' }}</p>
                <p class="text-xs text-slate-400 truncate font-mono mt-0.5">{{ Auth::user()->username ?? 'username' }}</p>
            </div>
            <a href="{{ route('admin.profile.edit') }}" class="block px-4 py-2.5 text-sm text-slate-300 hover:bg-slate-700 hover:text-white transition flex items-center gap-3 border-b border-slate-700/50">
                <i class="material-icons text-[18px]">manage_accounts</i>
                <span>Edit Profil</span>
            </a>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:bg-slate-700 hover:text-red-300 transition flex items-center gap-3">
                    <i class="material-icons text-[18px]">logout</i>
                    <span>Keluar</span>
                </button>
            </form>
        </div>

        <div class="profile_trigger" onclick="document.getElementById('profile-menu').classList.toggle('hidden');">
            <div class="avatar-wrapper">
                <img src="https://ui-avatars.com/api/?name={{ Auth::user()->full_name ?? 'Admin' }}&background=6366f1&color=fff&bold=true" class="w-full h-full object-cover" alt="profile" />
            </div>
            <div class="profile_text flex flex-col overflow-hidden transition-opacity duration-200">
                <span class="text-sm font-bold text-white truncate w-[130px]">{{ Auth::user()->full_name ?? 'Administrator' }}</span>
                <span class="text-[10px] text-slate-400 truncate w-[130px]">{{ Auth::user()->email }}</span>
            </div>
            <i class="material-icons text-slate-500 text-[18px] ml-auto profile_text transition-transform duration-200 rotate-0 mr-2 group-hover:text-white">expand_less</i>
        </div>
    </div>
</nav>