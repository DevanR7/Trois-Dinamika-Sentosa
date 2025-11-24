@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Daftar Akun (Chart of Accounts)</h3>
            <p class="text-sm text-gray-500 mt-1">Kelola struktur akun buku besar perusahaan.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('chart-of-accounts.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition">
                <i class="material-icons text-base mr-2">add</i> Tambah Akun
            </a>
        </div>
    </div>

    {{-- TABEL DATA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                            No. Akun
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Nama Akun
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Tipe
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Saldo Normal
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($parentAccounts as $parent)
                        
                        {{-- Baris Akun Induk --}}
                        <tr class="bg-gray-50 group">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900 font-mono">
                                {{ $parent->account_number }}
                            </td>
                            <td class="px-6 py-3 text-sm font-bold text-gray-900">
                                {{ $parent->account_name }}
                            </td>
                            <td class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">
                                {{ $parent->account_type }}
                            </td>
                            <td class="px-6 py-3 text-center text-xs text-gray-500">
                                {{ $parent->normal_balance }}
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if ($parent->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center text-sm font-medium">
                                <div class="flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('chart-of-accounts.edit', $parent) }}" class="p-1 text-gray-400 hover:text-indigo-600 transition" title="Edit">
                                        <i class="material-icons text-lg">edit</i>
                                    </a>
                                    <form action="{{ route('chart-of-accounts.destroy', $parent) }}" method="POST" 
                                          class="d-inline form-delete-account" 
                                          data-account-name="{{ $parent->account_name }}" 
                                          data-is-parent="true">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1 text-gray-400 hover:text-red-600 transition" title="Hapus">
                                            <i class="material-icons text-lg">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        
                        {{-- Baris Akun Anak --}}
                        @foreach ($parent->children as $child)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 font-mono pl-10 border-l-4 border-transparent group-hover:border-indigo-200">
                                <i class="material-icons text-[12px] mr-2 text-gray-300 align-middle">subdirectory_arrow_right</i>
                                {{ $child->account_number }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700 pl-10">
                                {{ $child->account_name }}
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-500 uppercase">
                                {{ $child->account_type }}
                            </td>
                            <td class="px-6 py-3 text-center text-xs text-gray-500">
                                {{ $child->normal_balance }}
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if ($child->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 border border-green-100">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center text-sm font-medium">
                                <div class="flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('chart-of-accounts.edit', $child) }}" class="p-1 text-gray-400 hover:text-indigo-600 transition" title="Edit">
                                        <i class="material-icons text-lg">edit</i>
                                    </a>
                                    <form action="{{ route('chart-of-accounts.destroy', $child) }}" method="POST" 
                                          class="d-inline form-delete-account" 
                                          data-account-name="{{ $child->account_name }}" 
                                          data-is-parent="false">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1 text-gray-400 hover:text-red-600 transition" title="Hapus">
                                            <i class="material-icons text-lg">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach

                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl text-gray-300 mb-3">account_tree</i>
                                    <p class="text-base font-medium">Belum ada data akun</p>
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
        // Konfirmasi Hapus
        const deleteForms = document.querySelectorAll('.form-delete-account');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault(); 
                
                const accountName = event.target.dataset.accountName;
                const isParent = event.target.dataset.isParent === 'true';
                
                let warningHtml = `Anda yakin ingin menghapus akun <b>"${accountName}"</b>?`;
                if (isParent) {
                    warningHtml = `Anda akan menghapus <b>AKUN INDUK "${accountName}"</b>.<br><br><span class="text-red-600 font-bold text-xs">PERHATIAN: Ini MUNGKIN akan menghapus semua akun anaknya!</span>`;
                }
                
                Swal.fire({
                    title: 'Hapus Akun?',
                    html: warningHtml,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) event.target.submit();
                });
            });
        });

        // Notifikasi
        @if(session('success')) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 3000, showConfirmButton: false }); @endif
        @if(session('error')) Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Gagal!', text: "{{ session('error') }}" }); @endif
    });
</script>
@endpush