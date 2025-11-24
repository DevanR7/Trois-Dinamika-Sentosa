@extends('layouts.app')

@section('title', 'Arsip Metode Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Arsip Metode</h3>
            <p class="text-sm text-gray-500 mt-1">Daftar metode pembayaran yang telah dinonaktifkan/dihapus sementara.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('payment-methods.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali ke Aktif
            </a>
        </div>
    </div>

    {{-- TABEL ARSIP --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/3">
                            Nama Metode
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Tipe
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Tanggal Arsip
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-40">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($archivedMethods as $method)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500">
                                {{ $method->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                    {{ Str::title($method->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $method->deleted_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center gap-2">
                                    {{-- Restore --}}
                                    <form action="{{ route('payment-methods.archived.restore', $method->payment_method_id) }}" method="POST" class="form-restore inline-block">
                                        @csrf
                                        <button type="submit" class="p-1.5 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition border border-green-200 shadow-sm" title="Pulihkan">
                                            <i class="material-icons text-lg leading-none">restore</i>
                                        </button>
                                    </form>
                                    
                                    {{-- Force Delete --}}
                                    <form action="{{ route('payment-methods.archived.forceDelete', $method->payment_method_id) }}" method="POST" class="form-force-delete inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition border border-red-200 shadow-sm" title="Hapus Permanen">
                                            <i class="material-icons text-lg leading-none">delete_forever</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl text-gray-300 mb-3">archive</i>
                                    <p class="text-base font-medium">Arsip Kosong</p>
                                    <p class="text-sm mt-1">Tidak ada metode pembayaran yang diarsipkan.</p>
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
    // Helper function for confirmation
    function confirmAction(selector, title, text, btnColor, btnText) {
        document.querySelectorAll(selector).forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: btnColor,
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: btnText,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) this.submit();
                });
            });
        });
    }

    confirmAction('.form-restore', 'Pulihkan Metode?', 'Metode ini akan kembali aktif dan bisa digunakan.', '#16a34a', 'Ya, Pulihkan!');
    confirmAction('.form-force-delete', 'Hapus Permanen?', 'Data akan hilang selamanya dan tidak bisa dikembalikan!', '#dc2626', 'Ya, Hapus!');
});
</script>
@endpush