<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Client Portal - {{ config('app.name', 'Laravel') }}</title>
    
    {{-- Aset CSS --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="font-family: 'Poppins', sans-serif;">
    
    <div class="sidebar-overlay"></div>
    {{-- Memanggil komponen sidebar klien (Sudah benar) --}}
    @include('layouts.client-sidebar')

    {{-- Wrapper untuk konten utama (Sudah benar) --}}
    <div class="main-wrapper flex-grow-1">
        <main class="main-content">
            @yield('content')
        </main>
    </div>
    
    </div> <button class="btn btn-primary d-lg-none floating-menu-btn"
        type="button"
        id="sidebar-open"> <i class="bi bi-list"></i>
    </button>

    {{-- Aset JavaScript --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        @if(session('success'))
            Swal.fire({
                icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}',
                timer: 3000, showConfirmButton: false
            });
        @endif
        @if(session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal!', text: '{{ session('error') }}' });
        @endif
    </script>

    @stack('scripts')
</body>
</html>