@extends('admin.layouts.app')

@section('title', 'Buat Retur Penjualan')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.sales-returns.index') }}" class="hover:text-indigo-600 transition-colors">Retur Penjualan</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Buat Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Buat Retur Penjualan</h1>
        </div>
        <a href="{{ route('admin.sales-returns.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ERROR ALERT --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-enter">
            <div class="flex items-start gap-3">
                <i class="material-icons text-red-500 mt-0.5">error_outline</i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                    <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.sales-returns.store') }}" method="POST" id="return-form">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            {{-- KOLOM KIRI (FORM UTAMA) --}}
            <div class="lg:col-span-8 space-y-8">
                
                {{-- CARD 1: INFO RETUR --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">assignment_return</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Retur</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="sales_invoice_id">Pilih Invoice Asal <span class="text-red-500">*</span></label>
                            <select name="sales_invoice_id" id="sales_invoice_id" class="form-input select2-basic" required>
                                <option value="" disabled selected>-- Cari Nomor Invoice --</option>
                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->invoice_id }}">{{ $invoice->invoice_number }} - {{ $invoice->client->client_name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1">
                                <i class="material-icons text-[14px]">info</i> Pilih Invoice untuk memuat daftar barang.
                            </p>
                        </div>

                        <div>
                            <label for="return_date">Tanggal Retur <span class="text-red-500">*</span></label>
                            <input type="date" class="form-input" id="return_date" name="return_date" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: PILIH ITEM --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm min-h-[300px]">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                <i class="material-icons text-[20px]">list_alt</i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Item yang Diretur</h3>
                        </div>
                        <span class="text-xs font-bold bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-md border border-indigo-200 hidden" id="item-count-badge">0 Item</span>
                    </div>
                    
                    <div class="p-0">
                        {{-- Placeholder --}}
                        <div id="instruction-text" class="flex flex-col items-center justify-center py-16 text-slate-400 bg-slate-50/30">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                <i class="material-icons text-3xl opacity-30">search</i>
                            </div>
                            <p class="text-sm font-medium">Silakan pilih Nomor Invoice di atas.</p>
                        </div>

                        {{-- Table Container --}}
                        <div id="items-table-container" class="hidden overflow-x-auto">
                            <table class="dashboard-table min-w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="pl-6 w-5/12">Produk</th>
                                        <th class="text-center w-2/12">Qty Beli</th>
                                        <th class="text-right w-2/12">Harga (@)</th>
                                        <th class="text-center w-3/12 bg-indigo-50 border-b border-indigo-200 text-indigo-700">Qty Retur</th>
                                    </tr>
                                </thead>
                                <tbody id="return-items-body" class="divide-y divide-slate-100 bg-white">
                                    {{-- JS will inject rows here --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Alasan Retur --}}
                    <div class="p-6 border-t border-slate-100 bg-slate-50/30">
                        <label for="notes">Alasan Retur (Catatan) <span class="text-red-500">*</span></label>
                        <textarea class="form-textarea bg-white" name="notes" id="notes" rows="2" placeholder="Contoh: Salah kirim, barang cacat, rusak di jalan..." required></textarea>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: AKSI (Span 4) --}}
            <div class="lg:col-span-4 space-y-6">
                
                <div class="dashboard-card p-6 shadow-lg sticky top-6 border-t-4 border-indigo-500">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="material-icons text-indigo-600 text-lg">settings</i> Opsi Pengembalian
                    </h3>

                    <div class="space-y-4">
                        {{-- Opsi 1 --}}
                        <label class="relative flex items-start p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition-all has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500">
                            <div class="flex h-5 items-center mt-0.5">
                                <input type="radio" name="return_handling_type" value="deduct_invoice" checked class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </div>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-slate-900">Potong Tagihan Invoice</span>
                                <span class="block text-xs text-slate-500 mt-0.5 leading-relaxed">Mengurangi total tagihan pada Invoice terkait jika statusnya belum lunas.</span>
                            </div>
                        </label>

                        {{-- Opsi 2 --}}
                        <label class="relative flex items-start p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition-all has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500">
                            <div class="flex h-5 items-center mt-0.5">
                                <input type="radio" name="return_handling_type" value="store_as_credit" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </div>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-slate-900">Simpan ke Saldo Kredit</span>
                                <span class="block text-xs text-slate-500 mt-0.5 leading-relaxed">Menambah saldo deposit (kredit) klien untuk digunakan pada pesanan berikutnya.</span>
                            </div>
                        </label>

                        <hr class="border-dashed border-slate-200 my-4">

                        <button type="submit" class="w-full h-[48px] bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex justify-center items-center gap-2 group hover:-translate-y-0.5">
                            <i class="material-icons text-[18px] group-hover:scale-110 transition-transform">save</i> Simpan Retur
                        </button>
                        
                        <a href="{{ route('admin.sales-returns.index') }}" class="w-full h-[48px] bg-white border border-slate-300 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-50 transition text-center shadow-sm flex items-center justify-center">
                            Batal
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const invoiceSelect = $('#sales_invoice_id');
    const itemsContainer = $('#return-items-body');
    const tableContainer = $('#items-table-container');
    const instructionText = $('#instruction-text');
    const itemCountBadge = $('#item-count-badge');

    // Init Select2
    invoiceSelect.select2({
        placeholder: '-- Cari Nomor Invoice --',
        width: '100%',
        dropdownCssClass: 'select2-dropdown-clean'
    });

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // Logic Load Items
    invoiceSelect.on('change', function() {
        const invoiceId = $(this).val();
        
        if (!invoiceId) {
            tableContainer.addClass('hidden');
            instructionText.show();
            itemsContainer.empty();
            itemCountBadge.addClass('hidden');
            return;
        }
        
        // Loading State
        instructionText.html(`
            <div class="flex flex-col items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mb-3"></div>
                <span class="text-sm text-slate-500 font-medium">Memuat data item...</span>
            </div>
        `).show();
        
        tableContainer.addClass('hidden');

        // Fetch API
        fetch(`/api/invoices/${invoiceId}/items`)
            .then(response => response.json())
            .then(data => {
                itemsContainer.empty();
                
                if (data.items && data.items.length > 0) {
                    let returnableCount = 0;
                    const discountRate = (data.invoice.discount_percentage || 0) / 100;

                    data.items.forEach((item, index) => {
                        // Hitung sisa qty yang bisa diretur
                        const maxQty = item.quantity - (item.quantity_returned || 0);
                        
                        if (maxQty > 0) {
                            returnableCount++;
                            const priceAfterDiscount = item.price_per_unit * (1 - discountRate);
                            
                            // HTML Row
                            const rowHTML = `
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition">${item.product.product_name}</div>
                                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                                            <span class="bg-slate-100 px-2 py-0.5 rounded text-[10px] font-bold border border-slate-200 text-slate-600">
                                                Sudah Retur: ${item.quantity_returned || 0}
                                            </span>
                                            <span class="text-slate-300">|</span>
                                            <span>Total Beli: ${item.quantity}</span>
                                        </div>
                                        <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                                    </td>
                                    <td class="px-6 py-4 text-center align-middle">
                                        <span class="inline-block px-2.5 py-1 bg-white text-slate-700 text-xs font-bold rounded border border-slate-300 shadow-sm">
                                            ${item.quantity}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right align-middle text-sm text-slate-600 font-mono">
                                        ${formatRupiah(priceAfterDiscount)}
                                    </td>
                                    <td class="px-6 py-4 bg-indigo-50/30">
                                        <div class="flex flex-col items-center">
                                            <div class="relative w-24">
                                                <input type="number" 
                                                       name="items[${index}][quantity]" 
                                                       class="return-qty-input form-input text-center font-bold text-slate-800 h-10 w-full border-indigo-200 focus:border-indigo-500 focus:ring-indigo-500"
                                                       min="0" max="${maxQty}" placeholder="0">
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-1 font-medium">Maks: ${maxQty}</div>
                                            <div class="text-red-500 text-[10px] mt-0.5 hidden qty-error-message font-bold">Melebihi batas!</div>
                                        </div>
                                    </td>
                                </tr>
                            `;
                            
                            const newRow = $(rowHTML);

                            // Validasi Real-time
                            newRow.find('.return-qty-input').on('input', function() {
                                const input = $(this);
                                const errorMessage = newRow.find('.qty-error-message');
                                const val = parseInt(input.val() || 0, 10);
                                const max = parseInt(input.attr('max'), 10);

                                if (val > max) {
                                    errorMessage.removeClass('hidden');
                                    input.addClass('!border-red-500 !bg-red-50 text-red-600');
                                } else {
                                    errorMessage.addClass('hidden');
                                    input.removeClass('!border-red-500 !bg-red-50 text-red-600');
                                }
                            });

                            itemsContainer.append(newRow);
                        }
                    });

                    if(returnableCount > 0){
                        instructionText.hide();
                        tableContainer.removeClass('hidden');
                        itemCountBadge.text(`${returnableCount} Item Tersedia`).removeClass('hidden');
                    } else {
                        instructionText.html(`
                            <div class="text-center py-8">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 mb-3 text-green-600">
                                    <i class="material-icons text-xl">check_circle</i>
                                </div>
                                <h4 class="text-slate-900 font-bold mb-1">Semua Beres!</h4>
                                <p class="text-sm text-slate-500">Semua item pada Invoice ini sudah diretur sepenuhnya.</p>
                            </div>
                        `).show();
                        itemCountBadge.addClass('hidden');
                    }
                } else {
                    instructionText.html('<div class="text-slate-500 py-8 text-sm">Tidak ada item ditemukan pada invoice ini.</div>').show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                instructionText.html('<div class="text-red-500 py-8 text-sm font-bold flex items-center gap-2 justify-center"><i class="material-icons">error</i> Gagal memuat data.</div>').show();
            });
    });

    // Client-side Validation Submit
    const form = document.getElementById('return-form');
    form.addEventListener('submit', function(e) {
        let hasInput = false;
        let hasError = false;

        document.querySelectorAll('.return-qty-input').forEach(input => {
            const val = parseInt(input.value || 0, 10);
            const max = parseInt(input.getAttribute('max'), 10);

            if (val > 0) hasInput = true;
            if (val > max) hasError = true;
        });
        
        if (!hasInput) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Belum ada item',
                text: 'Harap isi jumlah retur setidaknya pada satu item.',
                confirmButtonColor: '#f59e0b',
                customClass: { popup: 'colored-toast rounded-xl' }
            });
        }
        
        if (hasError) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Jumlah Invalid',
                text: 'Ada jumlah retur yang melebihi batas maksimal. Silakan periksa kembali.',
                confirmButtonColor: '#ef4444',
                customClass: { popup: 'colored-toast rounded-xl' }
            });
        }
    });
});
</script>
@endpush