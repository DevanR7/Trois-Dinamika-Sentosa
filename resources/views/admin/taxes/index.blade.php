@extends('admin.layouts.app')

@section('title', 'Manajemen Pajak')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Tarif Pajak</h1>
            <p class="page-subtitle">Atur persentase pajak (PPN, PPh) untuk transaksi.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.taxes.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add</i> Tambah Pajak
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
                        <th>Nama Pajak</th>
                        <th class="text-right">Persentase (%)</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxes as $index => $tax)
                        <tr>
                            <td class="text-center text-slate-500">{{ $taxes->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $tax->name }}
                                </div>
                            </td>
                            <td class="text-right font-mono font-semibold text-slate-700 dark:text-slate-300">
                                {{ number_format($tax->rate, 2) }}%
                            </td>
                            <td class="text-center">
                                @if($tax->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Edit Button --}}
                                    <a href="{{ route('admin.taxes.edit', $tax->id) }}" 
                                       class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                       title="Edit">
                                        <i class="material-icons text-[18px] leading-none">edit</i>
                                    </a>

                                    {{-- Delete Button --}}
                                    <button type="button" onclick="confirmDelete('{{ $tax->id }}', '{{ $tax->name }}')" 
                                            class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                            title="Hapus">
                                        <i class="material-icons text-[18px] leading-none">delete</i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $tax->id }}" 
                                          action="{{ route('admin.taxes.destroy', $tax->id) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">percent</i>
                                    <span>Belum ada data pajak.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $taxes->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id, name) {
        window.confirmDialog({
            title: 'Hapus Pajak?',
            text: "Data pajak '" + name + "' akan dihapus permanen.",
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