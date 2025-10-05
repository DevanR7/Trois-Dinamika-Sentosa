<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>{{ config("app.name", "Trois Dinamika Sentosa") }}</title>

        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
            rel="stylesheet"
        />
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        />
        <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        />
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="icon" type="image/png" href="{{ asset('images/TDS-favicon.png') }}"> 
    @vite(["resources/css/app.css", "resources/js/app.js"])
    </head>
    <body style="font-family: 'Poppins', sans-serif">
        <!-- Sidebar -->
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
                <div class="text-center mb-4">
    <img src="{{ asset('images/TDS-side-text.png') }}" alt="Logo Aplikasi" style="width: 200px;" class="d-none d-lg-block mx-auto">
</div>
                <ul class="nav nav-pills flex-column mb-auto">
                    @include("layouts.partials.sidebar-links")
                </ul>
                <hr class="d-none d-lg-block" />
                <div class="dropdown d-none d-lg-block mt-auto">
                    <a
                        href="#"
                        class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <strong>{{ Auth::user()->full_name }}</strong>
                    </a>
                    <ul
                        class="dropdown-menu dropdown-menu-dark text-small shadow"
                    >
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

        <!-- Main Content -->
        <div class="main-wrapper">
            <main class="main-content">
                @yield("content")
            </main>
        </div>

        <!-- Floating Menu Button (Mobile) -->
        <button
            class="btn btn-primary btn-lg d-lg-none floating-menu-btn"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebarMenu"
            aria-controls="sidebarMenu"
        >
            <i class="bi bi-list"></i>
        </button>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> {{-- jQuery dibutuhkan oleh Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script>
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                timer: 2500,
                showConfirmButton: false
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '{{ session('error') }}'
            });
        @endif
    </script>
    
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Menargetkan semua form dengan kelas 'delete-form'
                const deleteForms = document.querySelectorAll('.delete-form');

                deleteForms.forEach((form) => {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault(); // Mencegah form langsung submit

                        Swal.fire({
                            title: 'Apakah Anda yakin?',
                            text: 'Data yang dihapus tidak bisa dikembalikan!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, hapus!',
                            cancelButtonText: 'Batal',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit(); // Jika dikonfirmasi, baru submit form
                            }
                        });
                    });
                });
            });
        </script>

        @stack("scripts")
    </body>
</html>
