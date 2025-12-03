@extends('admin.layouts.app')

@section('title', 'Rekonsiliasi Bank')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Rekonsiliasi Bank</h1>
            <p class="text-slate-500 text-sm mt-1">Riwayat pencocokan saldo bank dan sistem.</p>
        </div>
        <a href="{{ route('admin.bank-reconciliations.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Mulai Baru</span>
        </a>
    </div>

    {{-- TABEL --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6">Tgl. Statement</th>
                        <th>Akun Bank</th>
                        <th class="text-right">Saldo Bank</th>
                        <th class="text-right">Saldo Sistem</th>
                        <th class="text-right">Selisih</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($reconciliations as $recon)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="pl-6 py-4">
                                <div class="flex items-center gap-2 text-slate-600 text-sm">
                                    <i class="material-icons text-slate-400 text-[16px]">event</i>
                                    {{ $recon->statement_date->format('d M Y') }}
                                </div>
                            </td>
                            <td class="py-4">
                                <div class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition">{{ $recon->account->account_name ?? 'N/A' }}</div>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">{{ $recon->account->account_number ?? '' }}</div>
                            </td>
                            <td class="py-4 text-right font-mono text-sm font-bold text-slate-800">
                                Rp {{ number_format($recon->statement_balance, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-right font-mono text-sm text-slate-600">
                                Rp {{ number_format($recon->closing_balance, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-right text-sm font-bold font-mono">
                                @php $diff = $recon->difference; @endphp
                                @if($diff == 0)
                                    <span class="text-emerald-600 flex justify-end items-center gap-1"><i class="material-icons text-[14px]">check</i> 0</span>
                                @else
                                    <span class="text-red-600">Rp {{ number_format(abs($diff), 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="py-4 text-center">
                                @if ($recon->status == 'reconciled')
                                    <span class="status-completed flex items-center justify-center gap-1 w-fit mx-auto">
                                        <i class="material-icons text-[12px]">lock</i> Selesai
                                    </span>
                                @else
                                    <span class="status-draft flex items-center justify-center gap-1 w-fit mx-auto">
                                        <i class="material-icons text-[12px]">edit</i> Draft
                                    </span>
                                @endif
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('admin.bank-reconciliations.show', $recon) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-200 transition shadow-sm" 
                                       title="{{ $recon->status == 'draft' ? 'Lanjutkan' : 'Lihat' }}">
                                        <i class="material-icons text-[18px]">{{ $recon->status == 'draft' ? 'play_arrow' : 'visibility' }}</i>
                                    </a>
                                    
                                    @if($recon->status == 'draft')
                                    {{-- Global Delete Handler --}}
                                    <form action="{{ route('admin.bank-reconciliations.destroy', $recon) }}" method="POST" class="delete-form">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                data-title="Hapus Draft?"
                                                data-text="Progress rekonsiliasi ini akan hilang."
                                                data-btn-text="Ya, Hapus Draft"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-sm" 
                                                title="Hapus">
                                            <i class="material-icons text-[18px]">delete</i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-4xl opacity-30">account_balance_wallet</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-700">Belum ada riwayat</h3>
                                    <p class="text-sm mt-1">Silakan mulai rekonsiliasi baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($reconciliations->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $reconciliations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush