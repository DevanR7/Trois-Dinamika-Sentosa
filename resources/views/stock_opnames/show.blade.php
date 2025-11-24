@extends('layouts.app')

@section('title', 'Detail Opname')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('stock-opnames.index') }}" class="hover:text-indigo-600 transition">Opname</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
                No. Dokumen: <span class="text-indigo-600">{{ $stockOpname->opname_number }}</span>
            </h2>
        </div>
        
        <div class="flex gap-3">
            <form action="{{ route('stock-opnames.destroy', $stockOpname->opname_id) }}" method="POST" class="form-delete-opname-detail">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg text-sm font-medium hover:bg-red-100 transition">
                    <i class="bi bi-trash mr-1"></i> Batalkan Opname
                </button>
            </form>

            <a href="{{ route('stock-opnames.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KOLOM KIRI: INFO UMUM --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-info-circle text-indigo-500"></i> Informasi Umum
                    </h3>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <span class="block text-xs font-medium text-gray-500 uppercase">Tanggal</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $stockOpname->opname_date->format('d F Y') }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-500 uppercase">Petugas</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $stockOpname->user->full_name ?? 'System' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-500 uppercase">Status</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200 mt-1">Selesai</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-500 uppercase mb-1">Catatan</span>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-sm text-gray-600 italic">
                            {{ $stockOpname->notes ?: 'Tidak ada catatan.' }}
                        </div>
                    </div>
                    
                    <div class="border-t border-dashed border-gray-200 pt-4 mt-2 text-center">
                        <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Nilai Penyesuaian</span>
                        <div class="text-2xl font-bold {{ $stockOpname->total_adjustment_value < 0 ? 'text-red-600' : ($stockOpname->total_adjustment_value > 0 ? 'text-green-600' : 'text-gray-400') }}">
                            {{ $stockOpname->total_adjustment_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($stockOpname->total_adjustment_value), 0, ',', '.') }}
                        </div>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $stockOpname->total_adjustment_value < 0 ? 'bg-red-50 text-red-600 border-red-100' : ($stockOpname->total_adjustment_value > 0 ? 'bg-green-50 text-green-600 border-green-100' : 'bg-gray-50 text-gray-500 border-gray-200') }}">
                            @if($stockOpname->total_adjustment_value < 0) Selisih Kurang (Rugi)
                            @elseif($stockOpname->total_adjustment_value > 0) Selisih Lebih (Untung)
                            @else Sesuai (Balance)
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: RINCIAN BARANG --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-list-check text-indigo-500"></i> Rincian Barang
                    </h3>
                    <span class="text-xs text-gray-400 font-medium">{{ $stockOpname->items->count() }} Item</span>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar max-h-[600px]">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Produk</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">System</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Fisik</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Selisih</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nilai (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stockOpname->items as $item)
                            <tr class="hover:bg-gray-50 transition-colors {{ $item->difference != 0 ? ($item->difference < 0 ? 'bg-red-50/30' : 'bg-green-50/30') : '' }}">
                                <td class="px-6 py-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $item->product->product_name }}</div>
                                    <div class="text-xs text-gray-500">HPP: Rp {{ number_format($item->cost_per_unit, 0, ',', '.') }}</div>
                                </td>
                                <td class="px-6 py-3 text-center text-sm text-gray-500">
                                    {{ $item->system_qty }}
                                </td>
                                <td class="px-6 py-3 text-center text-sm font-bold text-gray-800">
                                    {{ $item->physical_qty }}
                                </td>
                                <td class="px-6 py-3 text-center text-sm font-bold">
                                    @if($item->difference > 0) <span class="text-green-600">+{{ $item->difference }}</span>
                                    @elseif($item->difference < 0) <span class="text-red-600">{{ $item->difference }}</span>
                                    @else <span class="text-gray-400">0</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-medium">
                                    @if($item->adjustment_value != 0)
                                        <span class="{{ $item->adjustment_value < 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $item->adjustment_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($item->adjustment_value), 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.querySelector('.form-delete-opname-detail');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Batalkan Opname?',
                text: "Stok akan dikembalikan dan jurnal dihapus! Aksi ini tidak dapat dibatalkan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) deleteForm.submit();
            });
        });
    }
});
</script>
@endpush