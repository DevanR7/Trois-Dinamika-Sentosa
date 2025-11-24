@extends('layouts.app')

@section('title', 'Kelola Satuan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Kelola Satuan</h3>
            <p class="text-sm text-gray-500 mt-1">Daftar satuan ukur untuk produk (Unit of Measurement).</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('units.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition">
                <i class="material-icons text-base mr-2">add</i> Tambah Satuan
            </a>
        </div>
    </div>

    {{-- ALERT --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-green-500">check_circle</i>
            <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-red-500">error</i>
            <span class="text-sm text-red-800 font-medium">{{ session('error') }}</span>
        </div>
    @endif

    {{-- TABLE CARD --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-16">
                            No.
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Nama Satuan
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($units as $unit)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $loop->iteration + $units->firstItem() - 1 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded border border-gray-200">
                                    {{ $unit->name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('units.edit', $unit->unit_id) }}" class="p-1.5 bg-white text-yellow-600 rounded-lg hover:bg-yellow-50 transition border border-gray-200 shadow-sm hover:border-yellow-200" title="Edit">
                                        <i class="material-icons text-lg leading-none">edit</i>
                                    </a>
                                    
                                    <form action="{{ route('units.destroy', $unit->unit_id) }}" method="POST" class="delete-form inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-white text-red-600 rounded-lg hover:bg-red-50 transition border border-gray-200 shadow-sm hover:border-red-200" title="Hapus">
                                            <i class="material-icons text-lg leading-none">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl text-gray-300 mb-3">straighten</i>
                                    <p class="text-base font-medium">Belum ada data</p>
                                    <p class="text-sm mt-1">Silakan tambahkan satuan baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $units->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Hapus Satuan?',
                text: "Pastikan satuan ini tidak sedang digunakan oleh produk manapun.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626', // red-600
                cancelButtonColor: '#6b7280', // gray-500
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });
});
</script>
@endpush