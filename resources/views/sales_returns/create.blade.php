@extends('layouts.app')

@section('title', 'Buat Retur Penjualan')

@section('content')
<div class="max-w-6xl mx-auto">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Buat Retur Penjualan</h2>
            <p class="text-sm text-gray-500 mt-1">Catat pengembalian barang dari pelanggan (Klien).</p>
        </div>
        <a href="{{ route('sales-returns.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    {{-- ERROR ALERT --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-exclamation-triangle-fill text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input:</h3>
                    <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('sales-returns.store') }}" method="POST" id="return-form">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- ===================================================
                 KOLOM KIRI: FORM UTAMA (Span 8)
                 =================================================== --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- CARD 1: INFO UMUM --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                        <i class="bi bi-receipt text-indigo-500"></i>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Informasi Retur</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Pilih Invoice --}}
                        <div class="md:col-span-2">
                            <label for="sales_invoice_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Pilih Invoice Asal <span class="text-red-500">*</span></label>
                            <select name="sales_invoice_id" id="sales_invoice_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                                <option value="" disabled selected>-- Cari Nomor Invoice --</option>
                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->invoice_id }}">{{ $invoice->invoice_number }} - {{ $invoice->client->client_name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                <i class="bi bi-info-circle"></i> Pilih Invoice untuk memuat barang.
                            </p>
                        </div>

                        {{-- Tanggal Retur --}}
                        <div>
                            <label for="return_date" class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Retur</label>
                            <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" id="return_date" name="return_date" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: PILIH ITEM --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="bi bi-box-seam text-indigo-500"></i>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Item yang Diretur</h3>
                        </div>
                        <span class="text-xs text-gray-400 hidden" id="item-count-badge">0 Item</span>
                    </div>
                    
                    <div class="p-0">
                        {{-- Placeholder Instruction --}}
                        <div id="instruction-text" class="flex flex-col items-center justify-center py-12 text-gray-400 bg-gray-50/50">
                            <i class="bi bi-search text-4xl mb-2 opacity-30"></i>
                            <p class="text-sm font-medium">Silakan pilih Nomor Invoice di atas.</p>
                        </div>

                        {{-- Table Container --}}
                        <div id="items-table-container" class="hidden overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase w-5/12">Produk</th>
                                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center w-2/12">Qty Beli</th>
                                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right w-2/12">Harga (@)</th>
                                        <th class="px-6 py-3 text-xs font-bold text-indigo-600 uppercase w-3/12 text-center bg-indigo-50/50 border-b border-indigo-100">Qty Retur</th>
                                    </tr>
                                </thead>
                                <tbody id="return-items-body" class="divide-y divide-gray-100 bg-white">
                                    {{-- JS will inject rows here --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- ALASAN RETUR --}}
                    <div class="p-6 border-t border-gray-100 bg-yellow-50/30">
                        <label for="notes" class="block text-xs font-bold text-gray-700 uppercase mb-2">Alasan Retur (Catatan) <span class="text-red-500">*</span></label>
                        <textarea class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="notes" id="notes" rows="2" placeholder="Contoh: Salah kirim, barang cacat, rusak di jalan..." required></textarea>
                    </div>
                </div>

            </div>

            {{-- ===================================================
                 KOLOM KANAN: AKSI (Span 4)
                 =================================================== --}}
            <div class="lg:col-span-4 space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="bi bi-check-circle text-indigo-500"></i> Konfirmasi
                    </h3>

                    <div class="space-y-4">
                        {{-- Opsi 1 --}}
                        <label class="relative flex items-start p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition-all has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                            <div class="flex h-5 items-center">
                                <input type="radio" name="return_handling_type" value="deduct_invoice" checked class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </div>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Potong Tagihan Invoice</span>
                                <span class="block text-xs text-gray-500 mt-0.5 leading-snug">Mengurangi tagihan Invoice jika belum lunas.</span>
                            </div>
                        </label>

                        {{-- Opsi 2 --}}
                        <label class="relative flex items-start p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition-all has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                            <div class="flex h-5 items-center">
                                <input type="radio" name="return_handling_type" value="store_as_credit" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </div>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Simpan ke Saldo Kredit</span>
                                <span class="block text-xs text-gray-500 mt-0.5 leading-snug">Menambah saldo deposit klien untuk pesanan berikutnya.</span>
                            </div>
                        </label>

                        <div class="border-t border-dashed border-gray-200 my-4"></div>

                        <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2 group">
                            <i class="bi bi-save group-hover:scale-110 transition-transform"></i> Simpan Retur
                        </button>
                        
                        <a href="{{ route('sales-returns.index') }}" class="block w-full py-3 bg-white border border-gray-300 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-50 transition text-center shadow-sm">
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        theme: 'bootstrap-5',
        placeholder: '-- Cari dan Pilih Nomor Invoice --',
        width: '100%'
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
                <span class="text-sm text-gray-500 font-medium">Memuat data item...</span>
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
                            
                            // HTML Row (TAILWIND)
                            const rowHTML = `
                                <tr class="hover:bg-gray-50 transition-colors group border-b border-gray-50 last:border-0">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition">${item.product.product_name}</div>
                                        <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                            <span class="bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">Sudah diretur: ${item.quantity_returned || 0}</span>
                                            <span class="text-gray-400">/</span>
                                            <span>Total: ${item.quantity}</span>
                                        </div>
                                        <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                                    </td>
                                    <td class="px-6 py-4 text-center align-middle">
                                        <span class="inline-block px-2.5 py-1 bg-white text-gray-700 text-xs font-bold rounded border border-gray-300 shadow-sm">
                                            ${item.quantity}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right align-middle text-sm text-gray-600 font-mono">
                                        ${formatRupiah(priceAfterDiscount)}
                                    </td>
                                    <td class="px-6 py-4 bg-indigo-50/30">
                                        <div class="flex flex-col items-center">
                                            <div class="relative w-24">
                                                <input type="number" 
                                                       name="items[${index}][quantity]" 
                                                       class="return-qty-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-center font-bold h-9"
                                                       min="0" max="${maxQty}" placeholder="0">
                                            </div>
                                            <div class="text-[10px] text-gray-400 mt-1">Maks: ${maxQty}</div>
                                            <div class="text-red-500 text-[10px] mt-0.5 hidden qty-error-message font-bold animate-pulse">Melebihi batas!</div>
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
                                    input.addClass('border-red-500 focus:border-red-500 focus:ring-red-500 text-red-600 bg-red-50');
                                } else {
                                    errorMessage.addClass('hidden');
                                    input.removeClass('border-red-500 focus:border-red-500 focus:ring-red-500 text-red-600 bg-red-50');
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
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 mb-3">
                                    <i class="bi bi-check-lg text-green-600 text-xl"></i>
                                </div>
                                <h4 class="text-gray-900 font-medium mb-1">Semua Beres!</h4>
                                <p class="text-sm text-gray-500">Semua item pada Invoice ini sudah diretur sepenuhnya.</p>
                            </div>
                        `).show();
                        itemCountBadge.addClass('hidden');
                    }
                } else {
                    instructionText.html('<div class="text-gray-500">Tidak ada item ditemukan pada invoice ini.</div>').show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                instructionText.html('<div class="text-red-500">Gagal memuat data. Pastikan koneksi lancar.</div>').show();
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
            alert('Harap isi jumlah retur setidaknya pada satu item.');
        }
        
        if (hasError) {
            e.preventDefault();
            alert('Ada jumlah retur yang melebihi batas maksimal. Silakan periksa kembali.');
        }
    });
});
</script>
@endpush