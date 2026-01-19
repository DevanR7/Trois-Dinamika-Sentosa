@extends('admin.layouts.app')

@section('title', 'Tutup Buku Tahunan')

@section('content')
<div class="flex flex-col gap-6">
    
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Tutup Buku Tahunan (Closing Book)</h1>
            <p class="page-subtitle">Proses pemindahan saldo Laba/Rugi tahun berjalan ke akun Laba Ditahan.</p>
        </div>
    </div>

    {{-- Information Alert --}}
    <div class="bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500 p-5 rounded-r shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="material-icons text-indigo-500">info</i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-bold text-indigo-800 dark:text-indigo-300 uppercase tracking-wide">Penting</h3>
                <div class="mt-2 text-sm text-indigo-700 dark:text-indigo-200 space-y-1">
                    <p>1. Proses ini akan menjurnal otomatis saldo akun <strong>Pendapatan, HPP, dan Beban</strong> menjadi nol.</p>
                    <p>2. Selisih (Laba/Rugi Bersih) akan dipindahkan ke akun <strong>Laba Ditahan (Retained Earnings)</strong>.</p>
                    <p>3. Pastikan semua transaksi pada tahun tersebut telah selesai diinput dan direkonsiliasi.</p>
                    <p>4. Tahun yang sudah ditutup <strong>tidak dapat diedit kembali</strong> transaksi-transaksinya.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Years Table --}}
    <div class="card card-plain">
        <div class="card-header">
            <h3 class="card-header-title">Periode Tahun Buku</h3>
        </div>
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-32 text-center">Tahun</th>
                        <th class="w-48 text-center">Status</th>
                        <th>Keterangan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($availableYears as $year)
                        @php
                            $isClosed = in_array($year, $closedYears);
                            // Cek apakah tahun ini adalah tahun berjalan (biasanya belum boleh ditutup kecuali sudah akhir tahun)
                            $isCurrentYear = $year == date('Y');
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            {{-- Tahun --}}
                            <td class="text-center">
                                <span class="text-lg font-bold font-mono text-slate-700 dark:text-slate-200">
                                    {{ $year }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="text-center">
                                @if($isClosed)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <i class="material-icons text-[14px]">lock</i>
                                        DITUTUP
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                        <i class="material-icons text-[14px]">lock_open</i>
                                        TERBUKA
                                    </span>
                                @endif
                            </td>

                            {{-- Keterangan --}}
                            <td class="text-slate-600 dark:text-slate-400 text-sm">
                                @if($isClosed)
                                    <span class="flex items-center gap-2">
                                        <i class="material-icons text-emerald-500 text-[16px]">check_circle</i>
                                        Saldo Laba/Rugi telah dipindahkan ke Ekuitas.
                                    </span>
                                @else
                                    @if($isCurrentYear)
                                        <span class="text-amber-600 dark:text-amber-400 flex items-center gap-2">
                                            <i class="material-icons text-[16px]">warning</i>
                                            Tahun berjalan. Disarankan menutup setelah 31 Des.
                                        </span>
                                    @else
                                        <span class="text-slate-500">Siap untuk proses tutup buku akhir tahun.</span>
                                    @endif
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="text-right">
                                @if(!$isClosed)
                                    <form action="{{ route('admin.closing-book.store') }}" method="POST" class="form-closing-book">
                                        @csrf
                                        <input type="hidden" name="year" value="{{ $year }}">
                                        
                                        <button type="button" 
                                                class="btn btn-sm btn-primary btn-process-closing"
                                                data-year="{{ $year }}">
                                            <i class="material-icons text-[16px] mr-1">history_edu</i>
                                            Tutup Buku
                                        </button>
                                    </form>
                                @else
                                    <button disabled class="btn btn-sm btn-secondary opacity-50 cursor-not-allowed">
                                        <i class="material-icons text-[16px] mr-1">lock</i>
                                        Selesai
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8 text-slate-500">
                                Tidak ada data transaksi jurnal umum ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Script Khusus untuk Konfirmasi Tutup Buku --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.btn-process-closing');
        
        buttons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const year = this.getAttribute('data-year');
                const form = this.closest('form');

                window.confirmDialog({
                    title: 'Konfirmasi Tutup Buku ' + year,
                    html: `Apakah Anda yakin ingin menutup buku tahun <strong>${year}</strong>?<br><br>
                           <ul class="text-left text-sm list-disc pl-4 text-slate-600 dark:text-slate-300">
                               <li>Jurnal Penutup otomatis akan dibuat.</li>
                               <li>Transaksi tahun ${year} akan dikunci permanen.</li>
                               <li>Pastikan neraca saldo sudah sesuai.</li>
                           </ul>`,
                    icon: 'warning',
                    confirmText: 'Ya, Proses Tutup Buku',
                    cancelText: 'Batal',
                    confirmColor: 'danger' // Merah agar user hati-hati
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
@endsection