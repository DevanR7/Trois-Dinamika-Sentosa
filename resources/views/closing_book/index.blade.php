@extends('layouts.app')

@section('title', 'Tutup Buku Tahunan')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        
        {{-- HEADER MERAH (PERINGATAN) --}}
        <div class="bg-gradient-to-br from-red-600 to-red-700 px-8 py-10 text-center text-white relative overflow-hidden">
            {{-- Icon Dekorasi --}}
            <i class="material-icons absolute -right-6 -bottom-6 text-[120px] text-white opacity-10 transform -rotate-12 pointer-events-none select-none">lock</i>
            
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
                    <i class="material-icons text-4xl">folder_zip</i>
                </div>
                <h2 class="text-3xl font-bold tracking-tight">Tutup Buku Tahunan</h2>
                <p class="text-red-100 mt-2 text-sm">Periode Akuntansi Akhir Tahun</p>
            </div>
        </div>

        <div class="p-8">
            
            {{-- ALERT PERINGATAN --}}
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8 rounded-r-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="material-icons text-yellow-400 text-xl">warning</i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-yellow-800 uppercase tracking-wider">Penting!</h3>
                        <div class="mt-1 text-sm text-yellow-700">
                            <p>
                                Proses ini akan memindahkan saldo Laba/Rugi berjalan ke Ekuitas (Laba Ditahan) dan mengunci transaksi pada tahun tersebut.
                                <span class="font-bold text-red-600 block mt-1">Tindakan ini tidak dapat dibatalkan.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('closing-book.store') }}" method="POST" id="closing-book-form">
                @csrf
                
                <div class="mb-8">
                    <label for="year" class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wider">Pilih Tahun Buku</label>
                    
                    <div class="relative">
                        {{-- Icon Kalender Absolut --}}
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                            <i class="material-icons text-gray-400 text-lg">calendar_month</i>
                        </div>
                        
                        <select name="year" id="year" class="select2 form-select block w-full pl-10 pr-10 py-3 text-base border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-lg shadow-sm" required>
                            <option value="">-- Pilih Tahun --</option>
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
                    @error('year') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                
                <button type="submit" class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition transform active:scale-[0.98]" id="btn-submit-closing">
                    <i class="material-icons mr-2 text-lg">lock</i> 
                    TUTUP BUKU <span id="selected-year-text" class="ml-1">{{ $availableYears[0] ?? '' }}</span>
                </button>

            </form>
        </div>
    </div>
    
    {{-- Footer Note --}}
    <p class="text-center text-xs text-gray-400 mt-6">
        Pastikan semua jurnal penyesuaian sudah selesai sebelum melakukan tutup buku.
    </p>

</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    {{-- Override Select2 Style agar mirip Tailwind Input (Tinggi & Padding) --}}
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            border-color: #d1d5db !important; /* gray-300 */
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
            padding-left: 2.5rem !important; /* Space untuk icon kalender */
            height: auto !important;
            min-height: 50px !important; /* Lebih tinggi agar gagah */
            border-radius: 0.5rem !important;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 0 !important;
            line-height: 1.5 !important;
            color: #111827 !important;
            font-weight: 600 !important;
        }
        .select2-container--bootstrap-5 .select2-selection__arrow {
            top: 50% !important;
            transform: translateY(-50%) !important;
            right: 10px !important;
        }
    </style>
@endpush

@push('scripts')
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
        allowClear: false,
        minimumResultsForSearch: -1 // Sembunyikan search box karena pilih tahun sedikit
    });

    // Update teks tombol saat pilih tahun
    $('#year').on('select2:select', function (e) {
        $('#selected-year-text').text(e.params.data.id);
    });

    // SweetAlert Konfirmasi
    $('#closing-book-form').on('submit', function(e) {
        e.preventDefault(); 
        const selectedYear = $('#year').val();
        const confirmationWord = "KONFIRMASI"; 

        if(!selectedYear) {
            Swal.fire({
                icon: 'error',
                title: 'Pilih Tahun',
                text: 'Silakan pilih tahun buku terlebih dahulu!',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Tutup Buku',
            html: `
                <div class="text-center mb-4">
                    <p class="text-gray-600 text-sm mb-1">Anda akan menutup buku tahun:</p>
                    <b class="text-3xl text-red-600 font-mono">${selectedYear}</b>
                </div>
                <p class="text-xs text-gray-500 bg-gray-100 p-2 rounded border border-gray-200">
                    Ketik <b>"${confirmationWord}"</b> di bawah untuk melanjutkan:
                </p>
            `,
            icon: 'warning',
            input: 'text',
            inputAttributes: { autocapitalize: 'off', placeholder: 'Ketik KONFIRMASI...', class: 'swal2-input text-center font-bold tracking-widest' },
            showCancelButton: true,
            confirmButtonText: 'Ya, Proses Tutup Buku!',
            confirmButtonColor: '#dc2626', // Red-600
            cancelButtonColor: '#6b7280',  // Gray-500
            cancelButtonText: 'Batal',
            reverseButtons: true,
            preConfirm: (inputValue) => {
                if (inputValue !== confirmationWord) {
                    Swal.showValidationMessage(`Kode salah. Ketik: ${confirmationWord}`);
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = document.getElementById('btn-submit-closing');
                btn.disabled = true;
                btn.innerHTML = '<i class="material-icons animate-spin mr-2 text-lg">sync</i> Memproses...';
                btn.classList.add('opacity-75', 'cursor-not-allowed');
                e.target.submit(); 
            }
        });
    });
});
</script>
@endpush