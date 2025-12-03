@extends('admin.layouts.app')

@section('title', 'Tutup Buku Tahunan')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-2xl mx-auto pb-20 animate-enter">
    
    <div class="dashboard-card p-0 overflow-hidden shadow-2xl border-0 ring-1 ring-slate-900/5">
        
        {{-- HEADER MERAH (PERINGATAN) --}}
        <div class="bg-gradient-to-br from-red-600 to-red-800 px-8 py-12 text-center text-white relative overflow-hidden">
            <i class="material-icons absolute -right-6 -bottom-6 text-[150px] text-white opacity-10 transform -rotate-12 pointer-events-none select-none">lock</i>
            
            <div class="relative z-10">
                <div class="w-20 h-20 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-6 backdrop-blur-sm border border-white/20 shadow-lg">
                    <i class="material-icons text-5xl">folder_zip</i>
                </div>
                <h1 class="text-3xl font-bold tracking-tight mb-2">Tutup Buku Tahunan</h1>
                <p class="text-red-100 text-sm max-w-md mx-auto leading-relaxed opacity-90">
                    Proses ini akan memindahkan saldo Laba/Rugi ke Ekuitas dan mengunci semua transaksi pada periode tersebut.
                </p>
            </div>
        </div>

        <div class="p-8 bg-white">
            
            {{-- ALERT PERINGATAN --}}
            <div class="bg-amber-50 border-l-4 border-amber-500 p-5 mb-8 rounded-r-lg flex gap-4">
                <i class="material-icons text-amber-600 text-2xl mt-0.5">warning_amber</i>
                <div class="text-sm text-amber-800">
                    <h3 class="font-bold uppercase tracking-wider mb-1">Perhatian Penting!</h3>
                    <p class="leading-relaxed opacity-90">
                        Pastikan semua jurnal penyesuaian, depresiasi, dan rekonsiliasi bank sudah selesai. 
                        <span class="font-bold text-red-700 block mt-1">Tindakan ini tidak dapat dibatalkan.</span>
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.closing-book.store') }}" method="POST" id="closing-book-form">
                @csrf
                
                <div class="mb-8">
                    <label for="year" class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wider">Pilih Tahun Buku</label>
                    
                    <div class="relative">
                        <select name="year" id="year" class="form-input select2-basic w-full" required>
                            <option value="" disabled selected>-- Pilih Tahun --</option>
                            @forelse ($availableYears as $year)
                                @php $isClosed = in_array($year, $closedYears); @endphp
                                <option value="{{ $year }}" {{ $isClosed ? 'disabled' : '' }}>
                                    Tahun {{ $year }} {{ $isClosed ? '(Sudah Ditutup)' : '' }}
                                </option>
                            @empty
                                <option disabled>Tidak ada data tahun tersedia.</option>
                            @endforelse
                        </select>
                    </div>
                    @error('year') <p class="mt-1 text-xs text-red-600 flex items-center gap-1"><i class="material-icons text-[14px]">error</i> {{ $message }}</p> @enderror
                </div>
                
                <button type="submit" class="w-full flex justify-center items-center h-[54px] px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-red-600 hover:bg-red-700 transition-all transform active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed" id="btn-submit-closing" disabled>
                    <i class="material-icons mr-2 text-xl">lock_person</i> 
                    TUTUP BUKU <span id="selected-year-text" class="ml-1 font-mono text-lg"></span>
                </button>

            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                 <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-indigo-600 transition flex items-center justify-center gap-1">
                    <i class="material-icons text-[16px]">arrow_back</i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    
    // 1. Init Select2
    $('#year').select2({
        width: '100%', 
        placeholder: '-- Pilih Tahun --',
        allowClear: false,
        minimumResultsForSearch: -1,
        dropdownCssClass: 'select2-dropdown-clean' // Menggunakan style clean dari app.css
    });

    // 2. Update Button State
    $('#year').on('select2:select', function (e) {
        const val = e.params.data.id;
        $('#selected-year-text').text(val);
        $('#btn-submit-closing').prop('disabled', false).removeClass('disabled:opacity-50 bg-slate-400').addClass('bg-red-600 hover:bg-red-700');
    });

    // Cek initial value (jika browser auto-fill atau back button)
    const initialVal = $('#year').val();
    if(initialVal) {
        $('#selected-year-text').text(initialVal);
        $('#btn-submit-closing').prop('disabled', false);
    }

    // 3. Konfirmasi SweetAlert dengan Input
    $('#closing-book-form').on('submit', function(e) {
        e.preventDefault(); 
        const selectedYear = $('#year').val();
        const confirmationWord = "TUTUP"; 

        if(!selectedYear) {
            window.showToast('Silakan pilih tahun buku terlebih dahulu!', 'error');
            return;
        }

        Swal.fire({
            title: 'KONFIRMASI TUTUP BUKU',
            html: `
                <div class="text-center mb-6">
                    <div class="bg-red-50 text-red-800 p-3 rounded-lg mb-4 text-sm">
                        Anda akan menutup buku tahun <b class="font-mono text-lg">${selectedYear}</b>.
                    </div>
                    <p class="text-slate-500 text-xs mb-2">
                        Untuk melanjutkan, ketik kata <b>"${confirmationWord}"</b> di bawah ini:
                    </p>
                </div>
            `,
            input: 'text',
            inputAttributes: { 
                autocapitalize: 'off', 
                placeholder: 'Ketik TUTUP...', 
                class: 'swal2-input text-center font-bold tracking-widest uppercase !text-slate-800' 
            },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Proses!',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8', 
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'bg-white rounded-xl border border-slate-100 shadow-2xl p-6',
                title: 'text-xl font-bold text-red-600',
                confirmButton: 'px-6 py-2.5 rounded-lg font-bold shadow-md',
                cancelButton: 'px-6 py-2.5 rounded-lg font-bold hover:bg-slate-100 text-slate-600'
            },
            preConfirm: (inputValue) => {
                if (inputValue.toUpperCase() !== confirmationWord) {
                    Swal.showValidationMessage(`Kata kunci salah. Ketik: ${confirmationWord}`);
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Loading State Button
                const btn = document.getElementById('btn-submit-closing');
                btn.disabled = true;
                btn.innerHTML = '<i class="material-icons animate-spin mr-2 text-lg">sync</i> MEMPROSES...';
                btn.classList.add('opacity-75', 'cursor-not-allowed');
                
                e.target.submit(); 
            }
        });
    });

    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush