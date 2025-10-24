<nav class="sidebar locked"> {{-- Asumsi 'locked' adalah class saat tertutup --}}
    <div class="logo_items flex">
        <span class="nav_image">
            <img src="{{ asset('images/TDS-favicon.png') }}" alt="logo_img" />
        </span>
        <span class="logo_name">Client</span>

        <i class="bx bx-lock-alt" id="lock-icon" title="Kunci/Buka Sidebar"></i>
        <i class="bx bx-x" id="sidebar-close"></i>
    </div>

    {{-- Container Menu dengan Scroll & Fade Effect --}}
    <div class="menu_container">
        <div class="menu_items">
            @include("layouts.partials.client-sidebar-links")
        </div>
    </div> 

    {{-- Profil User di Bawah --}}
    <div class="sidebar_profile flex">
        <span class="nav_image">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::guard('client')->user()->client_name) }}&background=4f46e5&color=fff" alt="profil" />
        </span>
        <div class="data_text">
            <span class="name">{{ Str::limit(Auth::guard('client')->user()->client_name, 15) }}</span>
            <span class="email">{{ Str::limit(Auth::guard('client')->user()->email, 18) }}</span>
        </div>
        {{-- Dropdown Profil --}}
        <div class="dropdown ms-auto dropup">
            <a href="#" class="text-white"
               data-bs-toggle="dropdown" aria-expanded="false" data-bs-placement="top-end">
                <i class='bx bx-dots-vertical-rounded fs-5'></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="{{ route("client.profile.edit") }}">
                    <i class="bi bi-person-circle me-2"></i> Profil
                </a></li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <form method="POST" action="{{ route("client.logout") }}">
                        @csrf
                        <a class="dropdown-item text-danger" href="{{ route("client.logout") }}"
                           onclick="event.preventDefault(); this.closest('form').submit();">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    {{-- ✅ START: TOMBOL LONCENG PENGUMUMAN (DITEMPEL DI SINI) --}}
    @if(isset($activeAnnouncements) && $activeAnnouncements->isNotEmpty())
        <button class="btn shadow notification-bell-top-right p-2" {{-- ✅ Class baru & padding --}}
                style="z-index: 1050;" {{-- Hanya z-index --}}
                type="button"
                id="notificationBellButton"
                data-bs-toggle="offcanvas"
                data-bs-target="#announcementOffcanvas"
                aria-controls="announcementOffcanvas"
                aria-label="Tampilkan Pengumuman">

            <i class="bi bi-bell-fill fs-5 lh-1"></i> {{-- Ikon --}}

            {{-- Badge Notifikasi --}}
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $activeAnnouncements->count() }}
                <span class="visually-hidden">notifikasi baru</span>
            </span>
        </button>
    @endif
    {{-- ✅ END: TOMBOL LONCENG PENGUMUMAN --}}

</nav> {{-- Akhir sidebar nav --}}