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
    
    {{-- Stack CSS Tambahan dari Halaman Child --}}
    @stack('styles') 

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="font-family: 'Poppins', sans-serif;">
    
    <div class="sidebar-overlay"></div>
    {{-- Memanggil komponen sidebar klien --}}
    @include('layouts.client-sidebar') {{-- Ganti jika path sidebar klien berbeda --}}

    {{-- Wrapper untuk konten utama --}}
    <div class="main-wrapper flex-grow-1">
        
        {{-- KONTEN UTAMA HALAMAN --}}
        <main class="main-content">
            @yield('content')
        </main>

    </div> {{-- Akhir main-wrapper --}}
    
    {{-- Tombol Floating Menu (Mobile) --}}
    <button class="btn btn-primary d-lg-none floating-menu-btn"
            type="button"
            id="sidebar-open"> <i class="bi bi-list"></i>
    </button>

    {{-- ========================================================= --}}
    {{--                FLOATING BUTTON & MODAL PENGUMUMAN           --}}
    {{-- ========================================================= --}}

    {{-- ✅ START: FLOATING ANNOUNCEMENT BUTTON --}}
    {{-- Hanya tampilkan jika ada pengumuman aktif ($activeAnnouncements dari View Composer) --}}
    @if(isset($activeAnnouncements) && $activeAnnouncements->isNotEmpty())
        <button class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 shadow p-3" 
                style="z-index: 1050;" 
                type="button" 
                data-bs-toggle="modal" 
                data-bs-target="#announcementModal"
                aria-label="Lihat Pengumuman">
            
            <i class="bi bi-bell-fill fs-4 lh-1"></i> {{-- Ikon Lonceng --}}
            
            {{-- Badge Notifikasi --}}
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $activeAnnouncements->count() }}
                <span class="visually-hidden">pengumuman baru</span>
            </span>
        </button>
    @endif
    {{-- ✅ END: FLOATING ANNOUNCEMENT BUTTON --}}


    {{-- ✅ START: MODAL PENGUMUMAN --}}
    {{-- Hanya buat modal jika ada pengumuman --}}
    @if(isset($activeAnnouncements) && $activeAnnouncements->isNotEmpty())
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"> {{-- Large, Centered, Scrollable --}}
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="announcementModalLabel">
                        <i class="bi bi-megaphone-fill me-2"></i> Pengumuman Terbaru
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Loop melalui pengumuman --}}
                    @foreach($activeAnnouncements as $index => $announcement)
                        <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}"> 
                            {{-- Tampilkan Judul jika ada --}}
                            @if($announcement->title)
                                <h5 class="fw-bold">{{ $announcement->title }}</h5>
                            @endif
                            
                            {{-- Tampilkan Isi Pengumuman --}}
                            <p class="mb-1">{!! nl2br(e($announcement->content)) !!}</p> 
                            
                            {{-- Info Tambahan (opsional) --}}
                            <small class="text-muted">
                                Diterbitkan: {{ $announcement->created_at->diffForHumans() }} 
                                ({{ $announcement->created_at->format('d M Y') }})
                            </small>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    {{-- ✅ END: MODAL PENGUMUMAN --}}

    {{-- ========================================================= --}}
    {{--                          AKHIR BAGIAN PENGUMUMAN             --}}
    {{-- ========================================================= --}}


    {{-- Aset JavaScript --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Script Notifikasi Global (SweetAlert) --}}
    <script>
        // Gunakan Toast untuk sukses
        @if(session('success'))
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success', 
                title: '{{ session('success') }}', showConfirmButton: false, 
                timer: 3000, timerProgressBar: true
            });
        @endif
        // Gunakan Modal standar untuk error
        @if(session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal!', text: '{{ session('error') }}' });
        @endif
         // Notifikasi Info (dari middleware profile complete & lock)
         @if(session('info'))
            Swal.fire({ icon: 'info', title: 'Informasi', text: '{{ session('info') }}' });
        @endif
    </script>

    {{-- Stack Script Tambahan dari Halaman Child --}}
    @stack('scripts')
</body>
</html>