@extends('admin.layouts.app')

@section('title', 'Rekonsiliasi Bank')

@section('content')

    <div class="max-w-7xl mx-auto">
        <div class="page-header flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="page-title">Rekonsiliasi Bank</h1>
                <p class="page-subtitle">Cocokkan catatan buku besar dengan rekening koran bank.</p>
            </div>
            <a href="{{ route('admin.bank-reconciliations.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-2">add</i> Mulai Rekonsiliasi
            </a>
        </div>

        <div class="card">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Tanggal Laporan</th>
                            <th>Akun Bank</th>
                            <th class="text-right">Saldo Laporan (Bank)</th>
                            <th class="text-right">Saldo Buku (Sistem)</th>
                            <th class="text-right">Selisih</th>
                            <th class="text-center">Status</th>
                            <th class="text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reconciliations as $recon)
                            <tr>
                                <td>
                                    <div class="font-bold text-slate-700 dark:text-slate-200">
                                        {{ $recon->statement_date->format('d M Y') }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        ID: #{{ $recon->reconciliation_id }}
                                    </div>
                                </td>
                                <td>
                                    <div class="font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ $recon->account->account_name }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $recon->account->account_number }}
                                    </div>
                                </td>
                                <td class="text-right font-medium">
                                    Rp {{ number_format($recon->statement_balance, 0, ',', '.') }}
                                </td>
                                <td class="text-right font-medium">
                                    Rp {{ number_format($recon->closing_balance, 0, ',', '.') }}
                                </td>
                                <td class="text-right">
                                    @if(abs($recon->difference) < 1)
                                        <span class="text-emerald-600 font-bold flex items-center justify-end gap-1">
                                            <i class="material-icons text-xs">check_circle</i> 0
                                        </span>
                                    @else
                                        <span class="text-rose-600 font-bold">
                                            {{ number_format($recon->difference, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($recon->status == 'reconciled')
                                        <span class="badge badge-success">Selesai</span>
                                    @else
                                        <span class="badge badge-warning">Draft</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        {{-- ✅ PERBAIKAN: Tambahkan 'flex items-center justify-center' dan 'leading-none' --}}
                                        <a href="{{ route('admin.bank-reconciliations.show', $recon->reconciliation_id) }}" 
                                           class="btn-icon btn-sm btn-secondary text-indigo-600 flex items-center justify-center" 
                                           title="{{ $recon->status == 'draft' ? 'Lanjutkan' : 'Lihat Detail' }}">
                                            <i class="material-icons text-sm leading-none">{{ $recon->status == 'draft' ? 'edit' : 'visibility' }}</i>
                                        </a>

                                        @if($recon->status == 'draft')
                                            {{-- ✅ PERBAIKAN: Terapkan juga pada tombol hapus agar konsisten --}}
                                            <button onclick="confirmDelete('{{ $recon->reconciliation_id }}')" 
                                                    class="btn-icon btn-sm btn-danger flex items-center justify-center" title="Hapus Draft">
                                                <i class="material-icons text-sm leading-none">delete</i>
                                            </button>
                                            <form id="delete-form-{{ $recon->reconciliation_id }}" 
                                                  action="{{ route('admin.bank-reconciliations.destroy', $recon->reconciliation_id) }}" 
                                                  method="POST" class="hidden">
                                                @csrf @method('DELETE')
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-500 italic">Belum ada data rekonsiliasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                {{ $reconciliations->links('vendor.pagination.admin') }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function confirmDelete(id) {
            window.confirmDialog({
                title: 'Hapus Draft?',
                text: 'Data draft rekonsiliasi akan dihapus. Transaksi jurnal tidak akan terhapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
    @endpush

@endsection