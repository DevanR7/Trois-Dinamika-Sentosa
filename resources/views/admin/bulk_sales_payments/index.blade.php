@extends('admin.layouts.app')

@section('title', 'Riwayat Pembayaran Massal')

@section('content')
<div class="flex flex-col gap-6">

    {{-- HEADER & ACTIONS --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg dark:bg-indigo-900/30 dark:text-indigo-400">
                    <i class="material-icons text-xl">playlist_add_check</i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-white">Pembayaran Massal</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Daftar penerimaan pembayaran gabungan (Bulk).</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- [FITUR BARU] Tombol ke Halaman Pending --}}
            @can('review-bulk-payments')
                <a href="{{ route('admin.bulk-sales-payments.pending') }}" class="btn btn-secondary text-amber-600 border-amber-200 hover:bg-amber-50">
                    <i class="material-icons text-lg mr-2">hourglass_empty</i> Menunggu Verifikasi
                </a>
            @endcan

            @can('create-bulk-payments')
                <a href="{{ route('admin.bulk-sales-payments.create') }}" class="btn btn-primary shadow-lg shadow-indigo-500/20">
                    <i class="material-icons text-lg mr-2">add</i> Buat Baru
                </a>
            @endcan
        </div>
    </div>

    {{-- FILTER CARD --}}
    <div class="card p-5" x-data="{ showFilters: {{ request()->hasAny(['start_date', 'end_date', 'status']) ? 'true' : 'false' }} }">
        <form action="{{ route('admin.bulk-sales-payments.index') }}" method="GET">
            <div class="flex flex-col lg:flex-row gap-4 items-end">
                
                {{-- Search --}}
                <div class="w-full lg:flex-1">
                    <label class="form-label">Pencarian</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="material-icons text-[18px]">search</i>
                        </span>
                        <input type="text" name="search" class="form-input pl-10" 
                               value="{{ request('search') }}" 
                               placeholder="Cari No. Payment, Klien, atau Ref...">
                    </div>
                </div>

                {{-- Toggle Advanced Filters (Mobile/Clean UI) --}}
                <div class="lg:hidden w-full">
                    <button type="button" @click="showFilters = !showFilters" class="btn btn-secondary w-full justify-between">
                        <span>Filter Lanjutan</span>
                        <i class="material-icons text-sm transition-transform duration-200" :class="showFilters ? 'rotate-180' : ''">expand_more</i>
                    </button>
                </div>

                {{-- Date Range & Status --}}
                <div class="w-full lg:w-auto flex flex-col lg:flex-row gap-4" 
                     x-show="showFilters" 
                     x-transition.opacity 
                     :class="{'hidden lg:flex': !showFilters, 'flex': showFilters}">
                    
                    <div>
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-input" value="{{ request('start_date') }}">
                    </div>
                    <div>
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-input" value="{{ request('end_date') }}">
                    </div>
                    <div class="min-w-[150px]">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Verifikasi</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        </select>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 w-full lg:w-auto">
                    <button type="submit" class="btn btn-secondary bg-slate-800 text-white border-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:border-slate-600 dark:hover:bg-slate-600 w-full lg:w-auto" title="Terapkan Filter">
                        <i class="material-icons text-lg">filter_list</i>
                    </button>
                    @if(request()->hasAny(['search', 'start_date', 'end_date', 'status']))
                        <a href="{{ route('admin.bulk-sales-payments.index') }}" class="btn btn-secondary text-rose-500 border-rose-200 hover:bg-rose-50 w-full lg:w-auto" title="Reset Filter">
                            <i class="material-icons text-lg">restart_alt</i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- DATA TABLE --}}
    <div class="card overflow-hidden">
        <div class="table-container border-0 shadow-none">
            <table class="table-modern">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th>No. Pembayaran</th>
                        <th>Tanggal</th>
                        <th>Klien</th>
                        <th>Metode</th>
                        <th class="text-right">Total Masuk</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($bulkSalesPayments as $payment)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                            <td>
                                <a href="{{ route('admin.bulk-sales-payments.show', $payment->bulk_sales_payment_id) }}" class="font-bold text-indigo-600 hover:underline">
                                    {{ $payment->payment_number }}
                                </a>
                                @if($payment->reference_number)
                                    <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1">
                                        <i class="material-icons text-[10px]">tag</i> {{ $payment->reference_number }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-sm text-slate-600 dark:text-slate-300">
                                {{ $payment->payment_date->format('d M Y') }}
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ substr($payment->client->client_name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium text-slate-700 dark:text-slate-200 truncate max-w-[150px]" title="{{ $payment->client->client_name }}">
                                            {{ $payment->client->client_name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm">
                                <div class="flex items-center gap-1">
                                    @if($payment->paymentMethod)
                                        <span class="text-slate-600 dark:text-slate-300">{{ $payment->paymentMethod->name }}</span>
                                        @if($payment->paymentMethod->type == 'gateway')
                                            <i class="material-icons text-[14px] text-indigo-500" title="Gateway Otomatis">bolt</i>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-right">
                                <span class="font-mono font-bold text-emerald-600">
                                    Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($payment->status) {
                                        'completed' => 'badge-success',
                                        'pending_verification' => 'badge-warning',
                                        'rejected' => 'badge-danger',
                                        default => 'badge-primary'
                                    };
                                    $statusLabel = match($payment->status) {
                                        'completed' => 'Selesai',
                                        'pending_verification' => 'Verifikasi',
                                        'rejected' => 'Ditolak',
                                        default => ucfirst($payment->status)
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.bulk-sales-payments.show', $payment->bulk_sales_payment_id) }}" 
                                       class="btn-action btn-action-view" 
                                       title="Lihat Detail">
                                        <i class="material-icons">visibility</i>
                                    </a>

                                    @can('delete-invoices') {{-- Asumsi permission delete sama --}}
                                        {{-- [FIX BUG DELETE] Menggunakan fungsi customDeleteBulk yang lebih robust --}}
                                        <button type="button" 
                                                onclick="customDeleteBulk('{{ route('admin.bulk-sales-payments.destroy', $payment->bulk_sales_payment_id) }}')"
                                                class="btn-action btn-action-delete"
                                                title="Hapus / Batalkan">
                                            <i class="material-icons">delete</i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-3xl text-slate-300">search_off</i>
                                    </div>
                                    <h3 class="text-slate-500 font-medium">Tidak ada data pembayaran.</h3>
                                    <p class="text-slate-400 text-sm mt-1">Coba ubah filter atau buat pembayaran baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            {{ $bulkSalesPayments->links('vendor.pagination.admin') }}
        </div>
    </div>

</div>

@push('scripts')
<script>
    /**
     * [FIX BUG DELETE]
     * Fungsi ini memastikan form delete dibuat dengan benar menggunakan CSRF Token dari Meta Tag.
     * Menggantikan confirmDelete global yang mungkin bermasalah dengan Blade directive di file JS.
     */
    function customDeleteBulk(url) {
        confirmDialog({
            title: 'Hapus Pembayaran Massal?',
            text: 'PERINGATAN: Semua pelunasan invoice terkait akan dibatalkan, dan deposit/jurnal akan dihapus permanen.',
            icon: 'warning',
            confirmText: 'Ya, Hapus & Batalkan',
            confirmColor: 'danger'
        }).then((result) => {
            if (result.isConfirmed) {
                // Buat form dinamis
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                
                // Ambil CSRF Token dari Meta Tag
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Input CSRF
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                
                // Input Method DELETE
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                
                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush

@endsection