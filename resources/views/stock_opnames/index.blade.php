@extends('layouts.app')

@section('title', 'Riwayat Stock Opname')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Riwayat Stock Opname</h2>
            <p class="text-sm text-gray-500 mt-1">Audit dan penyesuaian stok gudang.</p>
        </div>
        
        <div class="flex gap-3">
            <a href="{{ route('stock-opnames.worksheet') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                <i class="bi bi-printer mr-2"></i> Cetak Worksheet
            </a>
            <a href="{{ route('stock-opnames.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                <i class="bi bi-plus-lg mr-2"></i> Mulai Opname
            </a>
        </div>
    </div>

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center">
        <div class="flex items-center gap-3">
            <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
            <span class="text-sm text-green-700 font-medium">{{ session('success') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="bi bi-x text-lg"></i></button>
    </div>
    @endif

    {{-- TABLE CARD --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Opname</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Petugas</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Catatan</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nilai Penyesuaian</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($opnames as $opname)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-indigo-600 font-mono">{{ $opname->opname_number }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $opname->opname_date->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-6 w-6 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 mr-2 border border-gray-200">
                                    <i class="bi bi-person-fill text-xs"></i>
                                </div>
                                <span class="text-sm text-gray-700">{{ $opname->user->full_name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 italic">
                            {{ Str::limit($opname->notes, 30) ?: '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="text-sm font-bold {{ $opname->total_adjustment_value < 0 ? 'text-red-600' : ($opname->total_adjustment_value > 0 ? 'text-green-600' : 'text-gray-500') }}">
                                {{ $opname->total_adjustment_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($opname->total_adjustment_value), 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($opname->status == 'completed')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Selesai</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">{{ ucfirst($opname->status) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('stock-opnames.show', $opname->opname_id) }}" class="p-1.5 bg-white border border-gray-300 rounded-md text-indigo-600 hover:bg-indigo-50 transition shadow-sm" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form action="{{ route('stock-opnames.destroy', $opname->opname_id) }}" method="POST" class="form-delete-opname inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 bg-white border border-gray-300 rounded-md text-red-600 hover:bg-red-50 transition shadow-sm" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                    <i class="bi bi-clipboard-x text-2xl text-gray-400"></i>
                                </div>
                                <p class="text-sm">Belum ada riwayat Stock Opname.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $opnames->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.form-delete-opname');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Batalkan Opname?',
                text: "Stok akan dikembalikan dan jurnal dihapus! Data tidak bisa dikembalikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});
</script>
@endpush