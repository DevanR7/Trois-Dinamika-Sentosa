@extends('layouts.app')

{{-- Kita taruh CSS langsung di sini agar pasti terbaca --}}
@section('content')
<style>
    /* 1. FIX HEADER: Pastikan background merah muncul */
    .closing-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }
    .closing-header {
        /* Pakai !important untuk memaksa warna keluar */
        background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%) !important;
        padding: 2rem 1.5rem;
    }
    
    /* 2. FIX DROPDOWN: Agar Select2 sejajar dengan Ikon */
    .input-group > .select2-container--bootstrap-5 {
        flex: 1 1 auto !important;
        width: 1% !important; /* Trik CSS agar flexbox bootstrap bekerja */
    }
    .input-group > .select2-container--bootstrap-5 .select2-selection {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        height: 100% !important; /* Agar tinggi sama dengan ikon */
        display: flex;
        align-items: center;
    }
    
    /* Pemanis tambahan */
    .input-group-text {
        background-color: #f8f9fa;
        border-color: #ced4da;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-lg closing-card">
                
                <div class="card-header closing-header text-white text-center">
                    <div class="mb-2">
                        {{-- Pastikan icon bootstrap terpanggil, jika tidak muncul, cek library icon Anda --}}
                        <i class="bi bi-journal-check fs-1"></i> 
                    </div>
                    <h2 class="fw-bold mb-0">Tutup Buku Tahunan</h2>
                    <small class="text-white-50">Periode Akuntansi</small>
                </div>

                <div class="card-body p-4">
                    <div class="alert alert-light border-warning border-start border-4 mb-4 shadow-sm">
                        <div class="d-flex">
                            <div class="me-3 text-warning">
                                <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading fw-bold text-dark">Penting!</h5>
                                <p class="mb-0 text-secondary small">
                                    Proses ini akan memindahkan saldo Laba/Rugi ke Ekuitas dan mengunci transaksi. 
                                    <span class="fw-bold text-danger">Aksi tidak dapat dibatalkan.</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('closing-book.store') }}" method="POST" id="closing-book-form">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="year" class="form-label fw-bold text-muted text-uppercase small">Pilih Tahun Buku</label>
                            
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event text-danger"></i></span>
                                
                                <select name="year" id="year" class="form-select" data-placeholder="Pilih tahun...">
                                    <option></option>
                                    @forelse ($availableYears as $year)
                                        @php
                                            $isClosed = in_array($year, $closedYears);
                                        @endphp
                                        <option value="{{ $year }}" {{ $isClosed ? 'disabled' : '' }}>
                                            Tahun {{ $year }} {{ $isClosed ? '(Sudah Ditutup)' : '' }}
                                        </option>
                                    @empty
                                        <option disabled>Tidak ada data tahun tersedia.</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg py-3 fw-bold shadow-sm" id="btn-submit-closing">
                                <i class="bi bi-lock-fill me-2"></i> TUTUP BUKU <span id="selected-year-text">{{ $availableYears[0] ?? '' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Load Library CSS & JS di sini jika belum ada di layout --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Inisialisasi Select2
    $('#year').select2({
        theme: 'bootstrap-5',
        width: '100%', 
        placeholder: 'Pilih Tahun...',
        allowClear: false
    });

    // Update teks tombol
    $('#year').on('select2:select', function (e) {
        $('#selected-year-text').text(e.params.data.id);
    });

    // SweetAlert Logic
    $('#closing-book-form').on('submit', function(e) {
        e.preventDefault(); 
        const selectedYear = $('#year').val();
        const confirmationWord = "KONFIRMASI"; 

        if(!selectedYear) {
            Swal.fire('Error', 'Silakan pilih tahun terlebih dahulu!', 'error');
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Tutup Buku',
            html: `
                <div class="text-center mb-3">
                    Anda akan menutup buku tahun <br><b class="fs-3 text-danger">${selectedYear}</b>
                </div>
                <p class="small text-muted">Ketik <b>"${confirmationWord}"</b> untuk melanjutkan:</p>
            `,
            icon: 'warning',
            input: 'text',
            inputAttributes: { autocapitalize: 'off', placeholder: 'Ketik KONFIRMASI...' },
            showCancelButton: true,
            confirmButtonText: 'Ya, Proses!',
            confirmButtonColor: '#dc3545',
            preConfirm: (inputValue) => {
                if (inputValue !== confirmationWord) {
                    Swal.showValidationMessage(`Salah. Ketik: ${confirmationWord}`);
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = document.getElementById('btn-submit-closing');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
                e.target.submit(); 
            }
        });
    });
});
</script>
@endpush