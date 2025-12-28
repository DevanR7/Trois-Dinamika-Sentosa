@extends('admin.layouts.app')

@section('title', 'Tutup Buku Tahunan')

@section('content')

    {{-- HEADER --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Tutup Buku Tahunan</h1>
            <p class="page-subtitle">Proses akhir periode akuntansi untuk memindahkan Laba/Rugi ke Modal.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT COLUMN: FORM EKSEKUSI --}}
        <div class="lg:col-span-1 space-y-6">
            
            {{-- Alert Info --}}
            <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-xl text-indigo-800 text-sm leading-relaxed">
                <div class="flex items-center gap-2 font-bold mb-2">
                    <i class="material-icons text-base">info</i> Apa yang terjadi?
                </div>
                <ul class="list-disc ml-4 space-y-1 text-xs">
                    <li>Menghitung Laba/Rugi Bersih tahun tersebut.</li>
                    <li>Membuat <b>Jurnal Penutup</b> otomatis.</li>
                    <li>Memindahkan saldo ke akun <b>Laba Ditahan</b>.</li>
                    <li>Mengunci transaksi di periode tersebut.</li>
                </ul>
            </div>

            {{-- Form Card --}}
            <div class="card border-t-4 border-indigo-600">
                <div class="card-header">
                    <h3 class="card-header-title">Eksekusi Tutup Buku</h3>
                </div>
                <div class="card-body">
                    
                    <form id="closingForm" action="{{ route('admin.closing-book.store') }}" method="POST">
                        @csrf

                        <div class="mb-6">
                            <label class="form-label label-required">Pilih Tahun Buku</label>
                            <select name="year" class="tom-select" required>
                                <option value="">Pilih Tahun...</option>
                                @foreach($availableYears as $year)
                                    @php
                                        $isClosed = in_array($year, $closedYears);
                                    @endphp
                                    <option value="{{ $year }}" {{ $isClosed ? 'disabled' : '' }}>
                                        {{ $year }} {{ $isClosed ? '(Sudah Ditutup)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint mt-2">
                                Pastikan semua transaksi tahun tersebut sudah selesai diinput.
                            </div>
                        </div>

                        <button type="button" id="btnSubmit" class="btn btn-primary w-full justify-center">
                            <i class="material-icons text-sm mr-2">lock_clock</i> Proses Tutup Buku
                        </button>

                    </form>

                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN: RIWAYAT --}}
        <div class="lg:col-span-2">
            <div class="card h-full">
                <div class="card-header">
                    <h3 class="card-header-title">Riwayat Tutup Buku</h3>
                </div>
                <div class="table-container">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Periode Tahun</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th class="text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($closedYears as $year)
                                <tr>
                                    <td>
                                        <div class="font-bold text-lg text-slate-700 dark:text-slate-200 font-mono">
                                            {{ $year }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">
                                            <i class="material-icons text-[10px] mr-1">lock</i> Closed
                                        </span>
                                    </td>
                                    <td class="text-sm text-slate-500">
                                        Jurnal penutup telah dibuat.
                                    </td>
                                    <td class="text-center">
                                        {{-- Link ke Jurnal Manual (Cari berdasarkan deskripsi) --}}
                                        <a href="{{ route('admin.manual-journals.index', ['search' => 'Jurnal Penutup Tahun ' . $year]) }}" 
                                           class="btn btn-sm btn-secondary" target="_blank">
                                            Lihat Jurnal
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center p-8">
                                        <div class="flex flex-col items-center justify-center text-slate-400">
                                            <i class="material-icons text-5xl mb-2">history_edu</i>
                                            <span>Belum ada riwayat tutup buku.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnSubmit = document.getElementById('btnSubmit');
        const form = document.getElementById('closingForm');

        btnSubmit.addEventListener('click', function() {
            window.confirmDialog({
                title: 'Konfirmasi Tutup Buku?',
                text: "Tindakan ini akan membuat jurnal otomatis dan tidak bisa dibatalkan sembarangan. Pastikan data akuntansi sudah benar.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5', // Indigo primary
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Proses Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    btnSubmit.classList.add('is-loading');
                    btnSubmit.disabled = true;
                    form.submit();
                }
            });
        });
    });
</script>
@endpush