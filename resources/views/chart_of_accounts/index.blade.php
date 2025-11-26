@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Daftar Akun (COA)</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola struktur akun buku besar perusahaan.</p>
        </div>
        <a href="{{ route('chart-of-accounts.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Tambah Akun</span>
        </a>
    </div>

    {{-- TABEL DATA --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-48">No. Akun</th>
                        <th>Nama Akun</th>
                        <th>Tipe</th>
                        <th class="text-center">Saldo Normal</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse ($parentAccounts as $parent)
                        
                        {{-- Baris Akun Induk --}}
                        <tr class="bg-slate-50/80 hover:bg-indigo-50/30 transition-colors group border-l-4 border-l-transparent hover:border-l-indigo-500">
                            <td class="pl-6 py-4 align-top">
                                <span class="text-sm font-bold text-indigo-700 font-mono bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                                    {{ $parent->account_number }}
                                </span>
                            </td>
                            <td class="py-4 align-top">
                                <span class="text-sm font-bold text-slate-800">{{ $parent->account_name }}</span>
                                @if($parent->description)
                                    <p class="text-[10px] text-slate-400 mt-0.5 italic">{{ Str::limit($parent->description, 50) }}</p>
                                @endif
                            </td>
                            <td class="py-4 align-top">
                                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ $parent->account_type }}</span>
                            </td>
                            <td class="py-4 align-top text-center text-xs font-medium text-slate-500">
                                {{ $parent->normal_balance }}
                            </td>
                            <td class="py-4 align-top text-center">
                                @if ($parent->is_active)
                                    <span class="status-completed px-2 py-0.5 text-[10px]">Aktif</span>
                                @else
                                    <span class="status-draft px-2 py-0.5 text-[10px]">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="pr-6 py-4 align-top text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('chart-of-accounts.edit', $parent) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition shadow-sm" title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    <form action="{{ route('chart-of-accounts.destroy', $parent) }}" method="POST" 
                                          class="form-delete-account inline-block" 
                                          data-account-name="{{ $parent->account_name }}" 
                                          data-is-parent="true">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-sm" title="Hapus">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        
                        {{-- Baris Akun Anak --}}
                        @foreach ($parent->children as $child)
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="pl-6 py-3 whitespace-nowrap text-sm text-slate-500 font-mono flex items-center">
                                <span class="w-6 text-right mr-2 opacity-30">L</span> 
                                {{ $child->account_number }}
                            </td>
                            <td class="py-3 text-sm text-slate-600 pl-8 border-l-2 border-l-slate-100 group-hover:border-l-indigo-300 transition-colors">
                                {{ $child->account_name }}
                            </td>
                            <td class="py-3 text-xs text-slate-500">
                                {{ $child->account_type }}
                            </td>
                            <td class="py-3 text-center text-xs text-slate-400">
                                {{ $child->normal_balance }}
                            </td>
                            <td class="py-3 text-center">
                                @if ($child->is_active)
                                    <span class="text-emerald-600 text-[10px] font-bold uppercase">Aktif</span>
                                @else
                                    <span class="text-slate-400 text-[10px] font-bold uppercase">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="pr-6 py-3 text-center">
                                <div class="flex justify-center gap-2 opacity-50 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('chart-of-accounts.edit', $child) }}" class="text-amber-600 hover:text-amber-800 p-1" title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    <form action="{{ route('chart-of-accounts.destroy', $child) }}" method="POST" 
                                          class="form-delete-account inline-block" 
                                          data-account-name="{{ $child->account_name }}" 
                                          data-is-parent="false">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 p-1" title="Hapus">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach

                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-4xl opacity-30">account_tree</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-700">Belum ada data akun</h3>
                                    <p class="text-sm mt-1">Silakan buat struktur akun baru.</p>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Konfirmasi Hapus Khusus COA
        // Kita tidak pakai global handler biasa karena butuh logic "isParent" warning
        const deleteForms = document.querySelectorAll('.form-delete-account');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault(); 
                
                const accountName = event.target.dataset.accountName;
                const isParent = event.target.dataset.isParent === 'true';
                
                let title = 'Hapus Akun?';
                let html = `Anda yakin ingin menghapus akun <b>"${accountName}"</b>?`;
                let confirmBtnColor = '#ef4444'; // Red

                if (isParent) {
                    title = '⚠️ Hapus Akun Induk?';
                    html = `Anda akan menghapus <b>AKUN INDUK "${accountName}"</b>.<br><br><span class="text-red-600 font-bold text-xs bg-red-50 p-2 rounded border border-red-200 block">PERHATIAN: Tindakan ini juga akan menghapus semua sub-akun di bawahnya!</span>`;
                    confirmBtnColor = '#b91c1c'; // Darker Red
                }
                
                Swal.fire({
                    title: title,
                    html: html,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: confirmBtnColor,
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        popup: 'bg-white rounded-xl border border-slate-100 shadow-2xl p-6',
                        title: 'text-xl font-bold text-slate-800',
                        htmlContainer: 'text-sm text-slate-600 mt-2',
                        confirmButton: 'px-5 py-2.5 rounded-lg font-bold shadow-md',
                        cancelButton: 'px-5 py-2.5 rounded-lg font-bold hover:bg-slate-100 text-slate-600'
                    }
                }).then((result) => {
                    if (result.isConfirmed) event.target.submit();
                });
            });
        });

        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush