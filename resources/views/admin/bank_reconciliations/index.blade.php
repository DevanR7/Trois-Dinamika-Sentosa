@extends('admin.layouts.app')

@section('title', 'Rekonsiliasi Bank')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Rekonsiliasi Bank</h1>
                <p class="page-subtitle">Cocokkan saldo catatan buku besar dengan rekening koran bank.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('admin.bank-reconciliations.create') }}" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-1">post_add</i> Buat Rekonsiliasi
                </a>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card card-plain">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Periode / Tanggal</th>
                            <th>Akun Bank (COA)</th>
                            <th class="text-right">Saldo Bank (Statement)</th>
                            <th class="text-right">Saldo Buku</th>
                            <th class="text-right">Selisih</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reconciliations as $recon)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ $recon->statement_date->translatedFormat('d F Y') }}
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                            {{ $recon->account->account_name ?? '-' }}
                                        </span>
                                        <span class="text-xs text-slate-500 font-mono">
                                            {{ $recon->account->account_number ?? '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-right font-mono text-slate-600 dark:text-slate-300">
                                    {{ number_format($recon->statement_balance, 2, ',', '.') }}
                                </td>
                                <td class="text-right font-mono text-slate-600 dark:text-slate-300">
                                    {{ number_format($recon->closing_balance, 2, ',', '.') }}
                                </td>
                                <td class="text-right font-mono font-bold {{ abs($recon->difference) > 0.01 ? 'text-rose-500' : 'text-emerald-500' }}">
                                    {{ number_format($recon->difference, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($recon->status === 'reconciled')
                                        <span class="badge badge-success">
                                            <i class="material-icons text-[14px] mr-1">lock</i> Selesai
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            <i class="material-icons text-[14px] mr-1">edit_note</i> Draft
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Tombol Lanjutkan/Lihat --}}
                                        <a href="{{ route('admin.bank-reconciliations.show', $recon->reconciliation_id) }}" 
                                           class="btn-action {{ $recon->status === 'draft' ? 'btn-action-edit' : 'btn-action-view' }}"
                                           title="{{ $recon->status === 'draft' ? 'Lanjutkan Rekonsiliasi' : 'Lihat Detail' }}">
                                            <i class="material-icons">{{ $recon->status === 'draft' ? 'play_arrow' : 'visibility' }}</i>
                                        </a>

                                        {{-- Tombol Hapus (Hanya Draft) --}}
                                        @if($recon->status === 'draft')
                                            <form action="{{ route('admin.bank-reconciliations.destroy', $recon->reconciliation_id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action btn-action-delete" 
                                                        data-confirm-delete="true"
                                                        data-message="Menghapus draft ini akan me-reset status rekonsiliasi transaksi terkait.">
                                                    <i class="material-icons">delete_outline</i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4 text-slate-400">
                                            <i class="material-icons text-3xl">account_balance</i>
                                        </div>
                                        <p class="text-slate-500 font-medium">Belum ada riwayat rekonsiliasi.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $reconciliations->links('vendor.pagination.admin') }}
            </div>
        </div>
    </div>
@endsection