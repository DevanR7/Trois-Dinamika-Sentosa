<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config("app.name", "Aplikasi Anda") }}</title>
    
    {{-- FONTS & ICONS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Sans&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    {{-- LIBRARY CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <link rel="icon" type="image/png" href="{{ asset('images/TDS-favicon.png') }}">

    @vite(["resources/css/app.css", "resources/js/app.js"])
    
    {{-- Stack Styles untuk CSS tambahan di view --}}
    @yield('styles')
    @stack('styles')
</head>
<body>

    <div class="sidebar-overlay"></div>
    @include('layouts.sidebar')

    <div class="main-wrapper">
        <main class="main-content p-4">
            @yield("content")
        </main>
    </div>

    <button class="btn btn-primary d-lg-none floating-menu-btn" type="button" id="sidebar-open">
        <i class="bi bi-list"></i>
    </button>

    {{-- LIBRARY JS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

    {{-- 1. NOTIFIKASI FLASH MESSAGE (GLOBAL) --}}
    <script>
        @if(session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}', timer: 2500, showConfirmButton: false });
        @endif
        @if(session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal!', text: '{{ session('error') }}' });
        @endif
        @if(session('info'))
            Swal.fire({ icon: 'info', title: 'Info', text: '{{ session('info') }}' });
        @endif
    </script>

    {{-- 2. SIDEBAR DROPDOWN LOGIC --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropdowns = document.querySelectorAll('.has-submenu > .link');

        dropdowns.forEach(trigger => {
            trigger.addEventListener('click', function (e) {
                const sidebar = document.querySelector('.sidebar');
                // Jika sidebar tertutup (minimize), jangan buka submenu (atau expand sidebar dulu)
                if (sidebar.classList.contains('close')) {
                    // Opsional: sidebar.classList.remove('close'); 
                    return; 
                }

                e.preventDefault();
                const parent = this.parentElement;
                parent.classList.toggle('open');
            });
        });

        // Sidebar Toggle Mobile
        const sidebarOpenBtn = document.getElementById('sidebar-open');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        const sidebar = document.querySelector('.sidebar');

        if(sidebarOpenBtn) {
            sidebarOpenBtn.addEventListener('click', () => {
                sidebar.classList.add('active'); // Tambah class active utk mobile
                sidebarOverlay.classList.add('active');
            });
        }
        if(sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }
    });
    </script>

    {{-- 3. GLOBAL AUTO-SAVE SCRIPT (UNTUK FORM SEDERHANA) --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Hanya cari form dengan class 'autosave-simple'
        const simpleForms = document.querySelectorAll('form.autosave-simple');

        simpleForms.forEach(form => {
            // Gunakan URL pathname sebagai ID unik storage agar tidak bentrok antar halaman create
            // Contoh Key: autosave_v1_/expenses/create
            const storageKey = 'autosave_v1_' + window.location.pathname;

            // A. Load Data
            const savedData = localStorage.getItem(storageKey);
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    
                    Array.from(form.elements).forEach(element => {
                        if (!element.name || !data[element.name]) return;
                        
                        // Jangan restore input file atau password
                        if (element.type === 'file' || element.type === 'password') return;

                        // Select2
                        if ($(element).hasClass('select2-hidden-accessible')) {
                            $(element).val(data[element.name]).trigger('change');
                        } 
                        // Checkbox/Radio
                        else if (element.type === 'checkbox' || element.type === 'radio') {
                            element.checked = (data[element.name] === true || data[element.name] === 'on');
                        } 
                        // Input Biasa
                        else {
                            element.value = data[element.name];
                        }
                        
                        // Trigger input event agar jika ada script lain (misal format rupiah) bisa bereaksi
                        element.dispatchEvent(new Event('input'));
                        element.dispatchEvent(new Event('keyup')); 
                    });

                    // Notifikasi Toast
                    const Toast = Swal.mixin({
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
                    });
                    Toast.fire({ icon: 'info', title: 'Draft formulir dipulihkan' });
                } catch (e) {
                    console.error("Gagal load autosave:", e);
                    localStorage.removeItem(storageKey); // Hapus jika corrupt
                }
            }

            // B. Save Data Logic
            const saveToStorage = () => {
                const formData = new FormData(form);
                const obj = {};
                
                formData.forEach((value, key) => {
                    // Jangan simpan token CSRF atau Method spoofing
                    if (key !== '_token' && key !== '_method') {
                        obj[key] = value;
                    }
                });
                
                // Simpan checkbox manual
                form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    obj[cb.name] = cb.checked;
                });

                localStorage.setItem(storageKey, JSON.stringify(obj));
            };

            // Trigger Save saat mengetik/berubah
            form.addEventListener('input', saveToStorage);
            form.addEventListener('change', saveToStorage);
            
            // Trigger khusus Select2
            $(form).find('select').on('change', saveToStorage);

            // C. Clear Data saat Submit Berhasil
            form.addEventListener('submit', function() {
                localStorage.removeItem(storageKey);
            });
        });
    });
    </script>

    {{-- Stack Scripts untuk script tambahan di view --}}
    @stack("scripts")
</body>
</html>