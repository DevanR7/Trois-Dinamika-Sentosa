<nav class="sidebar close" id="mainSidebar">
    {{-- A. LOGO & BRANDING --}}
    <div class="logo_items">
        <div class="nav_image">
            <img src="{{ asset('images/TDS-favicon.png') }}" alt="logo" class="w-5 h-5 object-contain brightness-0 invert" /> 
        </div>
        <div class="logo_text flex flex-col overflow-hidden transition-opacity duration-200">
            <span class="logo_name text-lg font-bold text-white tracking-tight leading-tight whitespace-nowrap">Internal</span>
            <span class="text-[10px] text-gray-500 font-medium uppercase tracking-wider leading-none whitespace-nowrap">System</span>
        </div>
        
        {{-- Default Icon: Penuh (Checked) artinya TIDAK TERKUNCI --}}
        <i class="material-icons text-gray-500 hover:text-indigo-400 cursor-pointer text-[20px] ml-auto transition-all duration-200 mr-2 select-none" 
           id="lock-icon" 
           title="Kunci Sidebar">radio_button_checked</i>
        
        <i class="material-icons cursor-pointer text-gray-400 ml-auto text-2xl lg:hidden hover:text-white transition select-none" id="sidebar-close">close</i>
    </div>

    {{-- B. MENU ITEMS --}}
    <div class="menu_container" id="sidebarScrollContainer">
        <div class="menu_items">
            @include("layouts.partials.sidebar-links")
        </div>
    </div>

    {{-- C. PROFILE SECTION --}}
    <div class="sidebar_profile relative">
        <div id="profile-menu" class="hidden absolute bottom-full left-0 w-full bg-gray-800 border-b border-gray-700 mb-1 transition-all duration-200 z-50 shadow-lg rounded-t-lg">
            <div class="px-4 py-3 border-b border-gray-700 bg-gray-900/30">
                <p class="text-sm text-white font-medium truncate">{{ Auth::user()->full_name ?? 'User Name' }}</p>
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email ?? 'user@example.com' }}</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition flex items-center gap-3">
                <i class="material-icons text-[20px]">manage_accounts</i>
                <span>Edit Profil</span>
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:bg-gray-700 hover:text-red-300 transition flex items-center gap-3">
                    <i class="material-icons text-[20px]">logout</i>
                    <span>Keluar</span>
                </button>
            </form>
        </div>

        <div class="profile_trigger" id="profile-trigger-btn">
            <div class="avatar-wrapper">
                <img src="https://ui-avatars.com/api/?name={{ Auth::user()->full_name ?? 'Admin' }}&background=3730a3&color=fff" class="w-full h-full object-cover" alt="profile" />
            </div>
            <div class="profile_text flex flex-col overflow-hidden transition-opacity duration-200">
                <span class="text-sm font-semibold text-white truncate w-[130px]">{{ Auth::user()->full_name ?? 'Administrator' }}</span>
                <span class="text-[10px] text-gray-500 truncate w-[130px]">Klik untuk opsi</span>
            </div>
            <i class="material-icons text-gray-500 text-[18px] ml-auto profile_text transition-transform duration-200 rotate-0 mr-2" id="profile-chevron">expand_less</i>
        </div>
    </div>
</nav>

{{-- PENTING: JANGAN ADA SCRIPT DISINI. SCRIPT PINDAH KE APP.BLADE.PHP --}}