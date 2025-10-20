<nav class="sidebar locked">
    <div class="logo_items flex">
        <span class="nav_image">
            <img src="{{ asset('images/TDS-favicon.png') }}" alt="logo_img" />
        </span>
        <span class="logo_name">Internal</span>

        <i class="bx bx-lock-alt" id="lock-icon" title="Kunci/Buka Sidebar"></i>

        <i class="bx bx-x" id="sidebar-close"></i>
    </div>

    <div class="menu_container">
        <div class="menu_items">
            @include("layouts.partials.sidebar-links")
        </div>
    </div> <div class="sidebar_profile flex">
        <span class="nav_image">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->full_name) }}&background=4f46e5&color=fff" alt="profil" />
        </span>
        <div class="data_text">
            <span class="name">{{ Auth::user()->full_name }}</span>
            <span class="email">{{ Auth::user()->email }}</span>
        </div>

        <div class="dropdown ms-auto dropup">
            <a href="#" class="text-white"
               data-bs-toggle="dropdown"
               aria-expanded="false"
               data-bs-placement="top-end">
                <i class='bx bx-dots-vertical-rounded fs-5'></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="{{ route("profile.edit") }}">
                    <i class="bi bi-person-circle me-2"></i> Profil
                </a></li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <form method="POST" action="{{ route("logout") }}">
                        @csrf
                        <a class="dropdown-item text-danger" href="{{ route("logout") }}"
                           onclick="event.preventDefault(); this.closest('form').submit();">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </form>
                </li>
            </ul>
        </div>
    </div>

</nav>