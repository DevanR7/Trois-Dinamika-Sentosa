<div
    class="sidebar offcanvas-lg offcanvas-start"
    data-bs-scroll="true"
    tabindex="-1"
    id="sidebarMenu"
>
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas"
            data-bs-target="#sidebarMenu"
            aria-label="Close"
        ></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <h3 class="fs-4 text-center d-none d-lg-block mb-4">Nama Aplikasi</h3>
        <ul class="nav nav-pills flex-column mb-auto">
            {{-- Memanggil daftar link dari file partials --}}
            @include("layouts.partials.sidebar-links")
        </ul>
        <hr class="d-none d-lg-block" />
        <div class="dropdown d-none d-lg-block">
            <a
                href="#"
                class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                data-bs-toggle="dropdown"
                aria-expanded="false"
            >
                <strong>{{ Auth::user()->full_name }}</strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li>
                    <a
                        class="dropdown-item"
                        href="{{ route("profile.edit") }}"
                    >
                        Profil
                    </a>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <form method="POST" action="{{ route("logout") }}">
                        @csrf
                        <a
                            class="dropdown-item"
                            href="{{ route("logout") }}"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                        >
                            Logout
                        </a>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</div>
