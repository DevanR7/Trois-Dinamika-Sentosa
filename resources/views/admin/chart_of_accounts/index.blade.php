@extends('admin.layouts.app')

@section('title', 'Bagan Akun (COA)')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Bagan Akun (COA)</h1>
            <p class="page-subtitle">Daftar akun akuntansi untuk pengelompokan transaksi jurnal.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.chart-of-accounts.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">post_add</i> Tambah Akun
            </a>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-32">Kode Akun</th>
                        <th>Nama Akun</th>
                        <th>Kategori</th>
                        <th>Saldo Normal</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parentAccounts as $parent)
                        {{-- PARENT ROW --}}
                        <tr class="bg-slate-50 dark:bg-slate-800/50">
                            <td class="font-mono font-bold text-slate-700 dark:text-slate-200">
                                {{ $parent->account_number }}
                            </td>
                            <td>
                                <div class="font-bold text-slate-800 dark:text-white uppercase tracking-wide">
                                    {{ $parent->account_name }}
                                </div>
                                @if($parent->description)
                                    <div class="text-[10px] text-slate-500">{{ Str::limit($parent->description, 40) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-primary">{{ $parent->account_type }}</span>
                            </td>
                            <td>
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-600 px-2 py-0.5 rounded">
                                    {{ $parent->normal_balance }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($parent->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.chart-of-accounts.edit', $parent->account_id) }}" 
                                       class="w-8 h-8 rounded-full flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-300 transition-colors shadow-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400"
                                       title="Edit">
                                        <i class="material-icons text-[18px] leading-none">edit</i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        {{-- CHILDREN ROWS --}}
                        @foreach($parent->children as $child)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                <td class="font-mono text-slate-600 dark:text-slate-400 pl-6">
                                    <span class="text-slate-300 mr-1">↳</span> {{ $child->account_number }}
                                </td>
                                <td class="pl-8">
                                    <div class="font-medium text-slate-700 dark:text-slate-300">
                                        {{ $child->account_name }}
                                    </div>
                                    @if($child->description)
                                        <div class="text-[10px] text-slate-400 italic">{{ Str::limit($child->description, 40) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-xs text-slate-500">{{ $child->account_type }}</span>
                                </td>
                                <td>
                                    <span class="text-xs text-slate-500">{{ $child->normal_balance }}</span>
                                </td>
                                <td class="text-center">
                                    @if($child->is_active)
                                        <i class="material-icons text-emerald-500 text-sm" title="Aktif">check_circle</i>
                                    @else
                                        <i class="material-icons text-slate-300 text-sm" title="Non-Aktif">cancel</i>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Edit --}}
                                        <a href="{{ route('admin.chart-of-accounts.edit', $child->account_id) }}" 
                                           class="w-8 h-8 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                           title="Edit">
                                            <i class="material-icons text-[18px] leading-none">edit</i>
                                        </a>

                                        {{-- Delete (Hanya Child) --}}
                                        <button type="button" onclick="confirmDelete('{{ $child->account_id }}', '{{ $child->account_name }}')" 
                                                class="w-8 h-8 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                                title="Hapus">
                                            <i class="material-icons text-[18px] leading-none">delete</i>
                                        </button>
                                        
                                        <form id="delete-form-{{ $child->account_id }}" 
                                              action="{{ route('admin.chart-of-accounts.destroy', $child->account_id) }}" 
                                              method="POST" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">account_tree</i>
                                    <span>Belum ada akun yang terdaftar.</span>
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
            title: 'Hapus Akun?',
            text: "Akun '" + name + "' akan dihapus. Pastikan akun ini belum digunakan dalam jurnal transaksi.",
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