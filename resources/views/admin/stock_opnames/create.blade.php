@extends('admin.layouts.app')

@section('title', 'Stock Opname Baru')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.stock-opnames.index') }}" class="hover:text-indigo-600 transition-colors">Riwayat Opname</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Mulai Input</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Mulai Stock Opname Baru</h1>
        </div>
        <a href="{{ route('admin.stock-opnames.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <i class="material-icons text-red-500 mt-0.5">error_outline</i>
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.stock-opnames.store') }}" method="POST" id="opname-form">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden">
            
            {{-- CARD HEADER --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                    <i class="material-icons text-indigo-600 text-[20px]">inventory</i> Form Input Opname
                </h3>
                <span class="px-3 py-1 bg-white border border-slate-200 rounded-md text-xs font-bold text-slate-500 shadow-sm">
                    {{ now()->format('d M Y') }}
                </span>
            </div>
            
            <div class="p-6">
                
                {{-- FORM INPUT UTAMA --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div>
                        <label for="opname_date">Tanggal Opname <span class="text-red-500">*</span></label>
                        <input type="date" name="opname_date" id="opname_date" class="form-input" value="{{ old('opname_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes">Catatan / Keterangan</label>
                        <input type="text" name="notes" id="notes" class="form-input" placeholder="Contoh: Opname rutin bulan ini..." value="{{ old('notes') }}">
                    </div>
                </div>

                {{-- SEARCH BAR & INFO --}}
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4 bg-indigo-50 rounded-lg border border-indigo-100 p-4">
                    <div class="flex items-center gap-3 text-indigo-800">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                            <i class="material-icons text-[18px]">help_outline</i>
                        </div>
                        <span class="text-sm font-medium">Isi kolom <span class="font-bold border-b border-indigo-300">Fisik</span> dengan jumlah riil di gudang.</span>
                    </div>
                    <div class="relative w-full md:w-72">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" id="product-search" class="form-input pl-10 border-indigo-200 focus:border-indigo-500" placeholder="Cari nama produk...">
                    </div>
                </div>

                {{-- TABEL INPUT (SCROLLABLE) --}}
                <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm bg-white">
                    <div class="overflow-y-auto max-h-[500px] custom-scrollbar relative">
                        <table class="dashboard-table w-full" id="opname-table">
                            <thead class="bg-slate-50 sticky top-0 z-20 shadow-sm">
                                <tr>
                                    <th class="pl-6 w-5/12">Produk</th>
                                    <th class="text-center w-2/12 bg-slate-100/80">System</th>
                                    <th class="text-center w-3/12 bg-indigo-50 border-b-2 border-indigo-200 text-indigo-700">Fisik (Input)</th>
                                    <th class="text-center w-2/12 pr-6">Selisih</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($products as $index => $product)
                                <tr class="product-row hover:bg-slate-50/50 transition-colors">
                                    <td class="pl-6 py-3">
                                        <div class="text-sm font-bold text-slate-800 product-name">{{ $product->product_name }}</div>
                                        <div class="text-xs text-slate-500 font-mono mt-0.5 product-code">{{ $product->product_code }}</div>
                                        <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $product->product_id }}">
                                    </td>
                                    <td class="py-3 text-center bg-slate-50/30">
                                        <input type="text" class="w-full bg-transparent text-center text-sm font-medium text-slate-500 border-none focus:ring-0 p-0 system-qty cursor-default" 
                                               value="{{ $product->stock_quantity }}" readonly tabindex="-1">
                                    </td>
                                    <td class="py-3 bg-indigo-50/10">
                                        <input type="number" 
                                               name="products[{{ $index }}][physical_qty]" 
                                               class="form-input text-center font-bold text-slate-800 physical-qty-input h-10 w-full border-indigo-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" 
                                               value="{{ $product->stock_quantity }}" 
                                               min="0" required>
                                    </td>
                                    <td class="py-3 text-center pr-6">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200 min-w-[60px] justify-center difference-badge transition-all duration-300">0</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- FOOTER ACTIONS --}}
                <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-slate-100">
                    <a href="{{ route('admin.stock-opnames.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                        Batal
                    </a>
                    <button type="submit" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 hover:-translate-y-0.5">
                        <i class="material-icons text-[20px]">save</i> Simpan Hasil Opname
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
    
    // 1. Filter Pencarian (Optimized)
    const searchInput = document.getElementById('product-search');
    const tableRows = document.querySelectorAll('.product-row');

    searchInput.addEventListener('keyup', function(e) {
        const term = e.target.value.toLowerCase();
        tableRows.forEach(row => {
            const name = row.querySelector('.product-name').textContent.toLowerCase();
            const code = row.querySelector('.product-code').textContent.toLowerCase();
            row.style.display = (name.includes(term) || code.includes(term)) ? '' : 'none';
        });
    });

    // 2. Kalkulasi Selisih Real-time
    const inputs = document.querySelectorAll('.physical-qty-input');

    function updateDifference(row) {
        const systemQty = parseInt(row.querySelector('.system-qty').value) || 0;
        const physicalInput = row.querySelector('.physical-qty-input');
        const physicalQty = parseInt(physicalInput.value);
        const badge = row.querySelector('.difference-badge');

        if (isNaN(physicalQty) || physicalInput.value.trim() === '') {
            badge.textContent = '-';
            badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200 min-w-[60px] justify-center difference-badge transition-all duration-300';
            row.classList.remove('bg-red-50', 'bg-green-50');
            return;
        }

        const diff = physicalQty - systemQty;
        
        // Reset Style
        row.classList.remove('bg-red-50', 'bg-green-50');
        
        if (diff > 0) {
            badge.textContent = '+' + diff;
            badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200 min-w-[60px] justify-center difference-badge transition-all duration-300'; 
            row.classList.add('bg-green-50');
        } else if (diff < 0) {
            badge.textContent = diff;
            badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200 min-w-[60px] justify-center difference-badge transition-all duration-300'; 
            row.classList.add('bg-red-50');
        } else {
            badge.textContent = '0';
            badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200 min-w-[60px] justify-center difference-badge transition-all duration-300'; 
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', function() { updateDifference(this.closest('tr')); });
        // Init load tidak perlu dipanggil jika default value fisik = sistem
    });

    // 3. Custom Submit Logic (Validasi Selisih)
    const form = document.getElementById('opname-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        let changedItems = 0;
        let negativeDifference = false;
        
        inputs.forEach(input => {
            const row = input.closest('tr');
            // Skip jika row disembunyikan oleh search (opsional: tergantung kebutuhan bisnis)
            // if (row.style.display === 'none') return; 

            const system = parseInt(row.querySelector('.system-qty').value);
            const physical = parseInt(input.value);
            const diff = physical - system;

            if (diff !== 0) {
                changedItems++;
                if (diff < 0) negativeDifference = true;
            }
        });

        let title = 'Simpan Stock Opname?';
        let text = `Anda akan menyesuaikan stok untuk <b>${changedItems} barang</b> yang memiliki selisih.`;
        let icon = 'question';
        let confirmButtonText = 'Ya, Simpan!';
        let confirmButtonColor = '#4f46e5';

        if (changedItems === 0) {
            title = 'Tidak Ada Perubahan';
            text = "Semua stok fisik sama dengan sistem. Opname akan disimpan sebagai Balance.";
            icon = 'info';
        } else if (negativeDifference) {
            title = '⚠️ Terdapat Stok Kurang!';
            text = `Ditemukan selisih <b>KEKURANGAN</b> pada inventaris. Sistem akan mencatat penyesuaian negatif (Loss). Lanjutkan?`;
            icon = 'warning';
            confirmButtonColor = '#ef4444';
        }

        Swal.fire({
            title: title,
            html: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: confirmButtonColor,
            cancelButtonColor: '#94a3b8',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Batal',
            customClass: {
                // PERBAIKAN DISINI: Hapus 'colored-toast', ganti dengan style modal biasa
                popup: 'rounded-xl border border-slate-100 shadow-2xl p-6 bg-white', 
                title: 'text-xl font-bold text-slate-800',
                htmlContainer: 'text-sm text-slate-600 mt-2',
                confirmButton: 'px-6 py-2.5 rounded-lg font-bold shadow-md mx-1',
                cancelButton: 'px-6 py-2.5 rounded-lg font-bold hover:bg-slate-100 text-slate-600 mx-1'
            }
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endpush