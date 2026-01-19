@extends('admin.layouts.app')

@section('title', 'Arsip Metode Pembayaran')

@section('content')

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Arsip Metode Pembayaran</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Daftar metode yang dinonaktifkan sementara (Soft Deleted).
            </p>
        </div>

        <div>
            <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i>
                <span>Kembali ke Daftar Aktif</span>
            </a>
        </div>
    </div>

    {{-- 2. SEARCH --}}
    <div class="card mb-6 border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('admin.payment-methods.archived.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="material-icons text-slate-400">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="form-input pl-10 w-full" 
                           placeholder="Cari nama metode yang dihapus...">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.payment-methods.archived.index') }}" class="btn btn-secondary btn-icon" title="Reset">
                            <i class="material-icons">refresh</i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- 3. DATA TABLE --}}
    <div class="card border-0 shadow-none bg-transparent">
        <div class="table-container bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        <th>Metode Pembayaran</th>
                        <th>Waktu Dihapus</th>
                        <th class="text-right w-40 px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentMethods as $index => $method)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            
                            {{-- No --}}
                            <td class="text-center text-slate-400 text-xs">
                                {{ $paymentMethods->firstItem() + $index }}
                            </td>

                            {{-- Info Metode --}}
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 text-sm">
                                        {{ $method->name }}
                                    </span>
                                    
                                    {{-- Badge Tipe --}}
                                    <div class="mt-1">
                                        @if($method->type == 'gateway')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200">
                                                <i class="material-icons text-[10px]">hub</i> Gateway
                                            </span>
                                        @elseif($method->type == 'pending')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                                <i class="material-icons text-[10px]">hourglass_empty</i> Pending
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                <i class="material-icons text-[10px]">payments</i> Direct
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Tanggal Hapus --}}
                            <td>
                                <div class="flex flex-col">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">
                                        {{ $method->deleted_at->translatedFormat('d M Y') }}
                                    </span>
                                    <span class="text-[11px] text-slate-400">
                                        Pukul {{ $method->deleted_at->format('H:i') }}
                                    </span>
                                </div>
                            </td>

                            {{-- Aksi --}}
                            <td class="text-right px-4">
                                <div class="flex items-center justify-end gap-2">
                                    
                                    @can('manage-payment-methods')
                                        {{-- Restore --}}
                                        <form action="{{ route('admin.payment-methods.restore', $method->payment_method_id) }}" method="POST" class="inline-block m-0 p-0">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" 
                                                    class="btn-action btn-action-restore" 
                                                    title="Pulihkan (Restore)"
                                                    onclick="handleAction(this, 'Pulihkan Metode?', 'Metode ini akan aktif kembali di daftar utama.', 'success')">
                                                <i class="material-icons">restore_from_trash</i>
                                            </button>
                                        </form>

                                        {{-- Force Delete --}}
                                        <form action="{{ route('admin.payment-methods.force-delete', $method->payment_method_id) }}" method="POST" class="inline-block m-0 p-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" 
                                                    class="btn-action btn-action-delete" 
                                                    title="Hapus Permanen"
                                                    onclick="handleAction(this, 'Hapus Permanen?', 'Data akan hilang selamanya dan tidak bisa dikembalikan!', 'danger')">
                                                <i class="material-icons">delete_forever</i>
                                            </button>
                                        </form>
                                    @endcan

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-16">
                                <div class="flex flex-col items-center justify-center opacity-60">
                                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-3xl text-slate-400">delete_outline</i>
                                    </div>
                                    <p class="text-slate-500 text-sm">Tidak ada metode pembayaran di arsip.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $paymentMethods->links('vendor.pagination.admin') }}
        </div>
    </div>

    {{-- Script Handle Action --}}
    @push('scripts')
    <script>
        function handleAction(button, title, text, type) {
            // Menggunakan helper global confirmDialog dari app.js
            if (typeof window.confirmDialog === 'function') {
                window.confirmDialog({
                    title: title,
                    text: text,
                    icon: type === 'danger' ? 'error' : 'question',
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: type
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.closest('form').submit();
                    }
                });
            } else {
                // Fallback jika confirmDialog belum ready
                if(confirm(text)) {
                    button.closest('form').submit();
                }
            }
        }
    </script>
    @endpush

@endsection