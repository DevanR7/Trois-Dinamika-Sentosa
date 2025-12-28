@extends('admin.layouts.app')

@section('title', 'Manajemen Satuan')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Satuan Unit</h1>
            <p class="page-subtitle">Kelola satuan barang (Pcs, Kg, Box, dll).</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.units.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add</i> Tambah Satuan
            </a>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card max-w-4xl">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th>Nama Satuan</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $index => $unit)
                        <tr>
                            <td class="text-center text-slate-500">{{ $units->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $unit->name }}
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Edit Button --}}
                                    <a href="{{ route('admin.units.edit', $unit->unit_id) }}" 
                                       class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                       title="Edit">
                                        <i class="material-icons text-[18px] leading-none">edit</i>
                                    </a>

                                    {{-- Delete Button --}}
                                    <button type="button" onclick="confirmDelete('{{ $unit->unit_id }}', '{{ $unit->name }}')" 
                                            class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                            title="Hapus">
                                        <i class="material-icons text-[18px] leading-none">delete</i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $unit->unit_id }}" 
                                          action="{{ route('admin.units.destroy', $unit->unit_id) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">straighten</i>
                                    <span>Belum ada data satuan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $units->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id, name) {
        window.confirmDialog({
            title: 'Hapus Satuan?',
            text: "Satuan '" + name + "' akan dihapus permanen. Pastikan tidak ada produk yang menggunakannya.",
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