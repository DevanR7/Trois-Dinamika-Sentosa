@extends('admin.layouts.app')

@section('title', 'Rekening Perusahaan')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Rekening Perusahaan</h1>
            <p class="page-subtitle">Kelola akun bank/kas yang digunakan untuk transaksi.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.company-bank-accounts.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add_card</i> Tambah Rekening
            </a>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th>Bank & Atas Nama</th>
                        <th>Nomor Rekening</th>
                        <th>Terhubung ke Akun (COA)</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $index => $account)
                        <tr>
                            <td class="text-center text-slate-500">{{ $index + 1 }}</td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $account->bank_name }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    A.n {{ $account->account_name }}
                                </div>
                            </td>
                            <td class="font-mono text-slate-600 dark:text-slate-300">
                                {{ $account->account_number ?? '-' }}
                            </td>
                            <td>
                                @if($account->account)
                                    <div class="flex items-center gap-2">
                                        <span class="badge badge-primary font-mono text-[10px]">
                                            {{ $account->account->account_number }}
                                        </span>
                                        <span class="text-sm text-slate-700 dark:text-slate-300">
                                            {{ $account->account->account_name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="badge badge-danger">Belum Terhubung</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($account->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Edit Button --}}
                                    <a href="{{ route('admin.company-bank-accounts.edit', $account->company_bank_account_id) }}" 
                                       class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                       title="Edit">
                                        <i class="material-icons text-[18px] leading-none">edit</i>
                                    </a>

                                    {{-- Delete Button --}}
                                    <button type="button" onclick="confirmDelete('{{ $account->company_bank_account_id }}', '{{ $account->bank_name }}')" 
                                            class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                            title="Hapus">
                                        <i class="material-icons text-[18px] leading-none">delete</i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $account->company_bank_account_id }}" 
                                          action="{{ route('admin.company-bank-accounts.destroy', $account->company_bank_account_id) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">account_balance</i>
                                    <span>Belum ada rekening perusahaan.</span>
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
    function confirmDelete(id, name) {
        window.confirmDialog({
            title: 'Hapus Rekening?',
            text: "Rekening '" + name + "' akan dihapus. Pastikan tidak ada transaksi yang terkait.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush