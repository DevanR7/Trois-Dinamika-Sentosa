@extends('admin.layouts.app')

@section('title', 'Input Stock Opname')

@section('content')

    {{-- Tambahkan ID pada form untuk selector JS --}}
    <form id="opnameForm" action="{{ route('admin.stock-opnames.store') }}" method="POST">
        @csrf
        
        {{-- HEADER --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="page-title">Input Stock Opname</h1>
                <p class="page-subtitle">Masukkan jumlah fisik barang yang dihitung.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-secondary">Batal</a>
                {{-- Hapus onclick native, ganti type jadi button atau biarkan submit tapi di-intercept JS --}}
                <button type="button" id="btnSubmit" class="btn btn-primary">
                    <i class="material-icons text-sm mr-2">save</i> Simpan & Proses
                </button>
            </div>
        </div>

        {{-- INFO SECTION --}}
        <div class="card mb-6">
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label label-required">Tanggal Opname</label>
                        <input type="date" name="opname_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label label-optional">Catatan</label>
                        <input type="text" name="notes" class="form-input" placeholder="Cth: Opname Tahunan Gudang A">
                    </div>
                </div>
            </div>
        </div>

        {{-- INPUT TABLE --}}
        <div class="card">
            {{-- Header Tabel & Search --}}
            <div class="card-header bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 p-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <h3 class="font-bold text-slate-700 dark:text-slate-200">Daftar Barang</h3>
                    
                    <div class="relative w-full sm:w-72">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-base">search</i>
                        </div>
                        <input type="text" id="productSearch" 
                               class="form-input pl-10 py-2 text-sm w-full" 
                               placeholder="Cari nama barang atau kode...">
                    </div>
                </div>
            </div>

            <div class="table-container max-h-[600px] overflow-y-auto custom-scrollbar">
                <table class="table-modern w-full" id="opnameTable">
                    <thead class="sticky top-0 z-10 shadow-sm bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th>Nama Produk</th>
                            <th class="text-center w-24">Satuan</th>
                            <th class="text-right w-32">Stok Sistem</th>
                            <th class="text-right w-40">Fisik (Input)</th>
                            <th class="text-right w-32">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                        @foreach($products as $index => $product)
                            <tr class="product-row hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <td class="text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                                <td>
                                    <div class="font-bold text-slate-700 dark:text-slate-200 product-name">
                                        {{ $product->product_name }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $product->product_code }}</div>
                                    
                                    <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $product->product_id }}">
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-primary">{{ $product->unit->name ?? '-' }}</span>
                                </td>
                                <td class="text-right font-mono text-sm text-slate-600 dark:text-slate-300">
                                    <span class="system-qty" data-qty="{{ $product->stock_quantity }}">
                                        {{ number_format($product->stock_quantity, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-right p-2">
                                    <input type="number" step="0.01" 
                                           name="products[{{ $index }}][physical_qty]" 
                                           class="form-input text-right font-bold focus:bg-indigo-50 physical-input"
                                           value="{{ $product->stock_quantity }}" 
                                           required>
                                </td>
                                <td class="text-right font-bold text-sm pr-6">
                                    <span class="diff-display text-slate-400">0</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-slate-50 dark:bg-slate-800 text-xs text-slate-500 text-center border-t border-slate-200 dark:border-slate-700">
                Menampilkan {{ count($products) }} produk.
            </div>
        </div>

    </form>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Search Filter Function
        const searchInput = document.getElementById('productSearch');
        const tableRows = document.querySelectorAll('.product-row');

        searchInput.addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase();
            tableRows.forEach(row => {
                const name = row.querySelector('.product-name').textContent.toLowerCase();
                const code = row.querySelector('.font-mono').textContent.toLowerCase();
                if (name.includes(term) || code.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // 2. Real-time Difference Calculation
        const physicalInputs = document.querySelectorAll('.physical-input');

        physicalInputs.forEach(input => {
            input.addEventListener('input', function() {
                const row = this.closest('tr');
                const systemQty = parseFloat(row.querySelector('.system-qty').getAttribute('data-qty'));
                const physicalQty = parseFloat(this.value) || 0;
                
                const diff = physicalQty - systemQty;
                const diffDisplay = row.querySelector('.diff-display');
                
                const formattedDiff = diff.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                
                if (diff > 0) {
                    diffDisplay.textContent = '+' + formattedDiff;
                    diffDisplay.className = 'diff-display text-emerald-600';
                } else if (diff < 0) {
                    diffDisplay.textContent = formattedDiff;
                    diffDisplay.className = 'diff-display text-rose-600';
                } else {
                    diffDisplay.textContent = '0';
                    diffDisplay.className = 'diff-display text-slate-400';
                }
            });
        });

        // 3. SWEETALERT CONFIRMATION (REVISI)
        const btnSubmit = document.getElementById('btnSubmit');
        const form = document.getElementById('opnameForm');

        btnSubmit.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah submit langsung

            window.confirmDialog({
                title: 'Proses Stock Opname?',
                text: "Pastikan data fisik sudah benar. Stok sistem akan langsung diperbarui dan jurnal penyesuaian akan dibuat.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0f172a', // Primary color
                cancelButtonColor: '#64748b',  // Slate color
                confirmButtonText: 'Ya, Proses Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tambahkan loading state manual agar user tahu sedang diproses
                    btnSubmit.classList.add('is-loading'); 
                    btnSubmit.disabled = true;
                    form.submit();
                }
            });
        });
    });
</script>
@endpush