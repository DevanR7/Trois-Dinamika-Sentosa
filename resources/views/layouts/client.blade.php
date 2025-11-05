<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Client Portal - {{ config('app.name', 'Laravel') }}</title>

    {{-- Aset CSS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Sans&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css"> {{-- CSS jQuery UI (Untuk Draggable) --}}

    {{-- Stack CSS Tambahan dari Halaman Child --}}
    @stack('styles')

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Inline style untuk fix overlap sementara --}}
    <style>
        /* Naikkan tombol notifikasi di layar kecil */
        @media (max-width: 991.98px) { /* Bootstrap lg breakpoint - 1px */
            #notificationBellButton {
                bottom: 80px !important; /* Tinggikan dari bawah, sesuaikan jika perlu */
            }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay"></div>
    {{-- Memanggil komponen sidebar klien --}}
    @include('layouts.client-sidebar')

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
    {{--          FLOATING BUTTON & OFFCANVAS PENGUMUMAN           --}}
    {{-- ========================================================= --}}

    {{-- START: OFFCANVAS PENGUMUMAN --}}
    @if(isset($activeAnnouncements) && $activeAnnouncements->isNotEmpty())
    <div class="offcanvas offcanvas-end" tabindex="-1" id="announcementOffcanvas"
         aria-labelledby="announcementOffcanvasLabel" style="max-width: 400px;"> {{-- Atur lebar panel --}}
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="announcementOffcanvasLabel">
                <i class="bi bi-megaphone-fill me-2"></i> Pengumuman
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            {{-- Loop pengumuman --}}
            @foreach($activeAnnouncements as $announcement)
                <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    @if($announcement->title)
                        <h6 class="fw-bold">{{ $announcement->title }}</h6>
                    @endif
                    <p class="mb-1 small">{!! nl2br(e($announcement->content)) !!}</p>
                    <small class="text-muted">
                        {{ $announcement->created_at->diffForHumans() }}
                    </small>
                </div>
            @endforeach
        </div>
    </div>
    @endif
    {{-- END: OFFCANVAS PENGUMUMAN --}}

    {{-- ========================================================= --}}
    {{--                          AKHIR BAGIAN NOTIFIKASI           --}}
    {{-- ========================================================= --}}


    {{-- Aset JavaScript (jQuery -> jQuery UI -> Bootstrap -> SweetAlert) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script> {{-- jQuery UI (Untuk Draggable) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Script Notifikasi Global (SweetAlert) --}}
    <script>
        // Gunakan Toast untuk sukses
        @if(session('success'))
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '{{ session('success') }}', showConfirmButton: false, timer: 3000, timerProgressBar: true });
        @endif
        // Gunakan Modal standar untuk error
        @if(session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal!', text: '{{ session('error') }}' });
        @endif
         // Notifikasi Info
         @if(session('info'))
            Swal.fire({ icon: 'info', title: 'Informasi', text: '{{ session('info') }}' });
        @endif
    </script>

    {{-- @push untuk script halaman & logika draggable --}}
    @push('scripts')
    <script>
        $(document).ready(function() { // Gunakan jQuery ready

            // --- LOGIKA DRAGGABLE BUTTON ---
            const notificationBellButton = $('#notificationBellButton'); // Target ID baru

            if (notificationBellButton.length) {
                // Cek apakah draggable sudah dimuat
                if (typeof $.fn.draggable === 'function') {
                    try {
                        notificationBellButton.draggable({
                            containment: "window", // Batasi di dalam window
                            cursor: "grabbing",    // Ubah cursor

                            // Simpan posisi terakhir
                            stop: function( event, ui ) {
                                localStorage.setItem('notificationButtonPos', JSON.stringify(ui.offset)); // Simpan posisi absolut
                            }
                        });

                        // Terapkan posisi tersimpan saat load
                        const savedPosition = localStorage.getItem('notificationButtonPos');
                        if (savedPosition) {
                           try {
                               const pos = JSON.parse(savedPosition);
                               if (typeof pos.top !== 'undefined' && typeof pos.left !== 'undefined') {
                                   // Gunakan .css() untuk posisi fixed
                                   notificationBellButton.css({
                                       top: pos.top + 'px',
                                       left: pos.left + 'px',
                                       bottom: 'auto', // Override style inline awal
                                       right: 'auto'   // Override style inline awal
                                   });
                               } else { localStorage.removeItem('notificationButtonPos'); }
                           } catch(e) {
                               console.error("Gagal load posisi tombol:", e);
                               localStorage.removeItem('notificationButtonPos');
                           }
                        }
                    } catch (e) {
                         console.error("Error initializing draggable:", e);
                    }
                } else {
                    console.error("jQuery UI Draggable function not found!");
                }
            }

            // --- (Tidak perlu script untuk show toast lagi) ---

        }); // Akhir jQuery ready
    </script>
    @endpush

    {{-- PASTIKAN @stack ada di sini --}}
    @stack('scripts')
</body>
</html>