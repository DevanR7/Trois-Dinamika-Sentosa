@extends('layouts.app')

@section('title', 'Stock Opname Baru')

@section('content')
<div class="max-w-6xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Stock Opname Baru</h2>
            <p class="text-sm text-gray-500 mt-1">Lakukan penyesuaian stok fisik gudang dengan sistem.</p>
        </div>
        <a href="{{ route('stock-opnames.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stock-opnames.store') }}" method="POST" id="opname-form">
        @csrf
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            
            {{-- CARD HEADER --}}
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="bi bi-clipboard-check text-indigo-500"></i> Form Input Opname
                </h3>
                <span class="px-3 py-1 bg-white border border-gray-200 rounded text-xs font-medium text-gray-500 shadow-sm">
                    {{ now()->format('d M Y') }}
                </span>
            </div>
            
            <div class="p-6">
                
                {{-- FORM INPUT UTAMA --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Opname</label>
                        <input type="date" name="opname_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" value="{{ old('opname_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Catatan / Keterangan</label>
                        <input type="text" name="notes" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Contoh: Opname rutin bulan ini..." value="{{ old('notes') }}">
                    </div>
                </div>

                {{-- SEARCH BAR & INFO --}}
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4 bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                    <div class="flex items-center gap-3 text-indigo-700">
                        <i class="bi bi-info-circle-fill text-xl"></i>
                        <span class="text-sm font-medium">Isi kolom <span class="font-bold">"Fisik"</span> dengan jumlah riil di gudang.</span>
                    </div>
                    <div class="relative w-full md:w-72">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-search text-gray-400"></i>
                        </div>
                        <input type="text" id="product-search" class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Cari nama produk...">
                    </div>
                </div>

                {{-- TABEL INPUT (SCROLLABLE) --}}
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-y-auto max-h-[500px] custom-scrollbar">
                        <table class="min-w-full divide-y divide-gray-200" id="opname-table">
                            <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-5/12">Produk</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-2/12 bg-gray-100">System</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-indigo-700 uppercase tracking-wider w-3/12 bg-indigo-50 border-b border-indigo-100">Fisik (Input)</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-2/12">Selisih</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($products as $index => $product)
                                <tr class="product-row hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-3">
                                        <div class="text-sm font-bold text-gray-900 product-name">{{ $product->product_name }}</div>
                                        <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $product->product_code }}</div>
                                        <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $product->product_id }}">
                                    </td>
                                    <td class="px-6 py-3 text-center bg-gray-50">
                                        <input type="text" class="w-full bg-transparent text-center text-sm font-medium text-gray-500 border-0 focus:ring-0 p-0 system-qty cursor-default" value="{{ $product->stock_quantity }}" readonly tabindex="-1">
                                    </td>
                                    <td class="px-6 py-3 bg-indigo-50/30 p-2">
                                        <input type="number" 
                                               name="products[{{ $index }}][physical_qty]" 
                                               class="w-full text-center font-bold text-gray-900 border border-indigo-200 rounded-md focus:border-indigo-500 focus:ring-indigo-500 physical-qty-input py-1.5" 
                                               value="{{ $product->stock_quantity }}" 
                                               min="0" required>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 difference-badge border border-gray-200 min-w-[40px] justify-center">0</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- FOOTER ACTIONS --}}
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <a href="{{ route('stock-opnames.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center">
                        <i class="bi bi-check-circle mr-2"></i> Simpan Hasil Opname
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Filter Pencarian
    const searchInput = document.getElementById('product-search');
    const tableRows = document.querySelectorAll('.product-row');

    searchInput.addEventListener('keyup', function(e) {
        const term = e.target.value.toLowerCase();
        tableRows.forEach(row => {
            const name = row.querySelector('.product-name').textContent.toLowerCase();
            if (name.includes(term)) row.style.display = '';
            else row.style.display = 'none';
        });
    });

    // 2. Kalkulasi Selisih
    const inputs = document.querySelectorAll('.physical-qty-input');

    function updateDifference(row) {
        const systemQty = parseInt(row.querySelector('.system-qty').value) || 0;
        const physicalQtyInput = row.querySelector('.physical-qty-input');
        const physicalQty = parseInt(physicalQtyInput.value); 
        
        const badge = row.querySelector('.difference-badge');

        if (isNaN(physicalQty)) {
            badge.textContent = '-';
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200 min-w-[40px] justify-center difference-badge';
            return;
        }

        const diff = physicalQty - systemQty;
        
        if (diff > 0) {
            badge.textContent = '+' + diff;
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200 min-w-[40px] justify-center difference-badge'; 
        } else if (diff < 0) {
            badge.textContent = diff;
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200 min-w-[40px] justify-center difference-badge'; 
        } else {
            badge.textContent = '0';
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200 min-w-[40px] justify-center difference-badge'; 
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', function() { updateDifference(this.closest('tr')); });
        updateDifference(input.closest('tr')); // Init load
    });

    // 3. Konfirmasi Submit
    const form = document.getElementById('opname-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let changedItems = 0;
        inputs.forEach(input => {
            const row = input.closest('tr');
            const system = parseInt(row.querySelector('.system-qty').value);
            const physical = parseInt(input.value);
            if (system !== physical) changedItems++;
        });

        Swal.fire({
            title: 'Simpan Stock Opname?',
            text: `Anda akan menyesuaikan stok untuk ${changedItems} barang yang selisih.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5', // Indigo
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endpush