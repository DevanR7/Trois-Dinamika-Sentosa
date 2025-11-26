@extends('layouts.app')

@section('title', 'Akun Bank Perusahaan')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Akun Bank Perusahaan</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar rekening bank dan akun kas yang digunakan dalam transaksi.</p>
        </div>
        <a href="{{ route('company-bank-accounts.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Tambah Akun</span>
        </a>
    </div>

    {{-- TABLE CARD --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6">Nama Bank</th>
                        <th>Atas Nama</th>
                        <th>No. Rekening</th>
                        <th>Akun COA</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pr-6 w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse ($accounts as $account)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="pl-6 py-4">
                                <span class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">
                                    {{ $account->bank_name }}
                                </span>
                            </td>
                            <td class="py-4 text-sm text-slate-600">
                                {{ $account->account_name }}
                            </td>
                            <td class="py-4 text-sm font-mono text-slate-700">
                                {{ $account->account_number ?? '-' }}
                            </td>
                            <td class="py-4">
                                @if($account->account)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100 font-mono">
                                        {{ $account->account->account_number }}
                                    </span>
                                    <span class="text-xs text-slate-500 ml-1">{{ $account->account->account_name }}</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                        <i class="material-icons text-[12px] mr-1">link_off</i> Terputus
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 text-center">
                                @if ($account->is_active)
                                    <span class="status-completed flex items-center justify-center gap-1 w-fit mx-auto px-2 py-0.5">
                                        <i class="material-icons text-[12px]">check_circle</i> Aktif
                                    </span>
                                @else
                                    <span class="status-draft flex items-center justify-center gap-1 w-fit mx-auto px-2 py-0.5">
                                        <i class="material-icons text-[12px]">block</i> Non-Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center items-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('company-bank-accounts.edit', $account) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition shadow-sm" 
                                       title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    
                                    {{-- Global Delete Handler --}}
                                    <form action="{{ route('company-bank-accounts.destroy', $account) }}" method="POST" class="delete-form">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                data-name="{{ $account->bank_name }} ({{ $account->account_name }})" 
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-sm" 
                                                title="Hapus">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-4xl opacity-30">account_balance</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Belum ada akun bank</h3>
                                    <p class="text-sm mt-1">Silakan tambahkan akun untuk memulai transaksi.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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