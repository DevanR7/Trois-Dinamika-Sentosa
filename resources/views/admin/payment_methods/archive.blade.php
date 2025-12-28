@extends('admin.layouts.app')

@section('title', 'Arsip Metode Pembayaran')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Arsip Metode Pembayaran</h1>
            <p class="page-subtitle">Daftar metode yang dinonaktifkan (soft deleted).</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali ke Aktif
            </a>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card border-l-4 border-amber-500">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th>Nama Metode</th>
                        <th>Waktu Dihapus</th>
                        <th class="text-center w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($archivedMethods as $index => $method)
                        <tr class="bg-amber-50/30 dark:bg-amber-900/10">
                            <td class="text-center text-slate-500">{{ $index + 1 }}</td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $method->name }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ ucfirst($method->type) }}
                                </div>
                            </td>
                            <td>
                                <span class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ $method->deleted_at->format('d M Y, H:i') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Restore Button --}}
                                    <form action="{{ route('admin.payment-methods.archived.restore', $method->payment_method_id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-9 h-9 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors shadow-sm border border-transparent"
                                                title="Pulihkan">
                                            <i class="material-icons text-[18px] leading-none">restore</i>
                                        </button>
                                    </form>

                                    {{-- Force Delete Button --}}
                                    <button type="button" onclick="confirmForceDelete('{{ $method->payment_method_id }}')" 
                                            class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm border border-transparent"
                                            title="Hapus Permanen">
                                        <i class="material-icons text-[18px] leading-none">delete_forever</i>
                                    </button>
                                    
                                    <form id="force-delete-form-{{ $method->payment_method_id }}" 
                                          action="{{ route('admin.payment-methods.archived.forceDelete', $method->payment_method_id) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">folder_open</i>
                                    <span>Tidak ada arsip metode pembayaran.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmForceDelete(id) {
        window.confirmDialog({
            title: 'Hapus Permanen?',
            text: "Data ini akan hilang selamanya dan tidak dapat dikembalikan!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Permanen!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('force-delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush