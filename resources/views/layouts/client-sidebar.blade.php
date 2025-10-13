<div class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="clientSidebarMenu">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white">Client Portal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#clientSidebarMenu" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <div class="text-center mb-4">
            <img src="{{ asset('images/TDS-side-text.png') }}" alt="Logo Aplikasi" style="max-width: 80%;" class="d-none d-lg-block mx-auto">
        </div>
        
        <ul class="nav nav-pills flex-column mb-auto">
            {{-- Memanggil link menu klien --}}
            @include('layouts.partials.client-sidebar-links')
        </ul>
        
        <hr>
        
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <strong>{{ Str::limit(Auth::guard('client')->user()->client_name, 15) }}</strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li>
                    <form method="POST" action="{{ route('client.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</div>