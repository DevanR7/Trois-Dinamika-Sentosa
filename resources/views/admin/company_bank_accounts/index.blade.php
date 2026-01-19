@extends('admin.layouts.app')

@section('title', 'Akun Bank Perusahaan')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Akun Bank Perusahaan</h1>
            <p class="page-subtitle">Kelola rekening bank yang digunakan untuk transaksi pembayaran</p>
        </div>
        <div>
            <a href="{{ route('admin.company-bank-accounts.create') }}" class="btn btn-primary">
                <i class="material-icons text-[18px]">add</i>
                Tambah Rekening
            </a>
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                {{-- Tabs Status --}}
                <div class="flex bg-slate-100 dark:bg-slate-700/50 rounded-lg p-1">
                    <a href="{{ route('admin.company-bank-accounts.index') }}" 
                       class="px-4 py-2 text-xs font-bold rounded-md transition-all {{ request('status') !== 'trash' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Aktif
                    </a>
                    <a href="{{ route('admin.company-bank-accounts.index', ['status' => 'trash']) }}" 
                       class="px-4 py-2 text-xs font-bold rounded-md transition-all {{ request('status') === 'trash' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Sampah ({{ \App\Models\CompanyBankAccount::onlyTrashed()->count() }})
                    </a>
                </div>

                {{-- Search --}}
                <form action="{{ route('admin.company-bank-accounts.index') }}" method="GET" class="w-full md:w-auto">
                    @if(request('status') === 'trash')
                        <input type="hidden" name="status" value="trash">
                    @endif
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[18px]">search</i>
                        </div>
                        <input type="text" name="search" class="form-input pl-10 w-full md:w-64" 
                               placeholder="Cari nama bank / rek..." 
                               value="{{ request('search') }}">
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card card-plain">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16">#</th>
                        <th>Info Bank</th>
                        <th>Atas Nama</th>
                        <th>Akun Akuntansi (COA)</th>
                        <th>Status</th>
                        <th class="w-32 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $account->bank_name }}
                                </span>
                                <span class="text-xs text-slate-500 font-mono mt-0.5">
                                    {{ $account->account_number ?? '-' }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm font-medium text-slate-600 dark:text-slate-300">
                                {{ $account->account_name }}
                            </div>
                        </td>
                        <td>
                            @if($account->account)
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-[10px] font-mono font-bold text-slate-600 dark:text-slate-300">
                                        {{ $account->account->account_number }}
                                    </span>
                                    <span class="text-sm text-slate-600 dark:text-slate-300 truncate max-w-[200px]">
                                        {{ $account->account->account_name }}
                                    </span>
                                </div>
                            @else
                                <span class="text-red-500 text-xs italic">Belum terhubung</span>
                            @endif
                        </td>
                        <td>
                            @if($account->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-secondary">Non-Aktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="flex items-center justify-end gap-2">
                                @if(request('status') === 'trash')
                                    {{-- Restore Button --}}
                                    <form action="{{ route('admin.company-bank-accounts.restore', $account->company_bank_account_id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-action btn-action-restore" title="Pulihkan">
                                            <i class="material-icons">restore</i>
                                        </button>
                                    </form>
                                    
                                    {{-- Force Delete Button --}}
                                    <button type="button" 
                                            class="btn-action btn-action-delete"
                                            title="Hapus Permanen"
                                            onclick="confirmDialog({
                                                title: 'Hapus Permanen?',
                                                text: 'Data ini akan dihapus selamanya. Pastikan tidak ada transaksi terkait!',
                                                icon: 'warning',
                                                confirmText: 'Ya, Hapus Permanen',
                                                confirmColor: 'danger'
                                            }).then((result) => {
                                                if (result.isConfirmed) document.getElementById('force-delete-{{ $account->company_bank_account_id }}').submit();
                                            })">
                                        <i class="material-icons">delete_forever</i>
                                    </button>
                                    <form id="force-delete-{{ $account->company_bank_account_id }}" 
                                          action="{{ route('admin.company-bank-accounts.forceDelete', $account->company_bank_account_id) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @else
                                    {{-- Edit Button --}}
                                    <a href="{{ route('admin.company-bank-accounts.edit', $account->company_bank_account_id) }}" 
                                       class="btn-action btn-action-edit" 
                                       title="Edit">
                                        <i class="material-icons">edit</i>
                                    </a>

                                    {{-- Soft Delete Button --}}
                                    <button type="button" 
                                            class="btn-action btn-action-delete"
                                            title="Arsipkan"
                                            onclick="confirmDialog({
                                                title: 'Arsipkan Rekening?',
                                                text: 'Rekening ini akan dipindahkan ke sampah.',
                                                icon: 'question',
                                                confirmText: 'Ya, Arsipkan',
                                                confirmColor: 'danger'
                                            }).then((result) => {
                                                if (result.isConfirmed) document.getElementById('delete-form-{{ $account->company_bank_account_id }}').submit();
                                            })">
                                        <i class="material-icons">delete_outline</i>
                                    </button>
                                    <form id="delete-form-{{ $account->company_bank_account_id }}" 
                                          action="{{ route('admin.company-bank-accounts.destroy', $account->company_bank_account_id) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i class="material-icons text-4xl mb-2">account_balance_wallet</i>
                                <p class="text-sm">Belum ada akun bank yang didaftarkan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection