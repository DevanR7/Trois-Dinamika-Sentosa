<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config("app.name", "Aplikasi Anda") }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="icon" type="image/png" href="{{ asset('images/TDS-favicon.png') }}">

    @vite(["resources/css/app.css", "resources/js/app.js"])
</head>
<body>

    <div class="sidebar-overlay"></div>
    @include('layouts.sidebar')

    <div class="main-wrapper">

        <main class="main-content p-4">
            @yield("content")
        </main>
    </div>

    <button class="btn btn-primary d-lg-none floating-menu-btn"
            type="button"
            id="sidebar-open">
        <i class="bi bi-list"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

    <script>
        @if(session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}', timer: 2500, showConfirmButton: false });
        @endif
        @if(session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal!', text: '{{ session('error') }}' });
        @endif
    </script>

    @stack("scripts")
</body>
</html>