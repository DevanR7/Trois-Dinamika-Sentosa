@extends('layouts.app')

@section('title', 'Koreksi Manual PO')

@section('content')
<div class="max-w-3xl mx-auto">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('purchase-order-adjustments.create') }}" class="hover:text-indigo-600 transition">Penyesuaian</a>
                <span>/</span>
                <span class="text-gray-800">Manual</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
                Koreksi Manual: <span class="text-indigo-600">{{ $purchaseOrder->po_number }}</span>
            </h2>
        </div>
        <a href="{{ route('purchase-order-adjustments.create') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    {{-- ERROR HANDLING --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- INFO SISA TAGIHAN --}}
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                <i class="bi bi-wallet2 text-xl"></i>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Sisa Tagihan Saat Ini</span>
                <span class="text-lg font-bold text-gray-900">Rp {{ number_format($purchaseOrder->remaining_balance, 0, ',', '.') }}</span>
            </div>
        </div>
        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full border border-indigo-100">Info</span>
    </div>

    {{-- FORM INPUT --}}
    <form action="{{ route('purchase-order-adjustments.store.manual') }}" method="POST" id="manual-adjustment-form">
        @csrf
        <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->po_id }}">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="bi bi-input-cursor-text text-indigo-500"></i> Form Input Manual
                </h3>
            </div>
            
            <div class="p-6 space-y-6">
                
                {{-- 1. TANGGAL --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Penyesuaian</label>
                    <input type="date" name="adjustment_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" required>
                </div>

                {{-- 2. JENIS KOREKSI --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jenis Koreksi</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Nota Kredit --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="type" id="type_credit_note" value="credit_note" class="peer sr-only" {{ old('type', 'credit_note') == 'credit_note' ? 'checked' : '' }}>
                            <div class="p-4 rounded-lg border-2 border-gray-200 hover:border-green-200 hover:bg-green-50 transition peer-checked:border-green-500 peer-checked:bg-green-50">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-green-700 flex items-center gap-2"><i class="bi bi-arrow-down-circle"></i> Nota Kredit</span>
                                    <i class="bi bi-check-circle-fill text-green-600 opacity-0 peer-checked:opacity-100 transition"></i>
                                </div>
                                <p class="text-xs text-gray-500">Potongan/Diskon (Mengurangi Utang)</p>
                            </div>
                        </label>

                        {{-- Nota Debit --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="type" id="type_debit_note" value="debit_note" class="peer sr-only" {{ old('type') == 'debit_note' ? 'checked' : '' }}>
                            <div class="p-4 rounded-lg border-2 border-gray-200 hover:border-red-200 hover:bg-red-50 transition peer-checked:border-red-500 peer-checked:bg-red-50">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-red-700 flex items-center gap-2"><i class="bi bi-arrow-up-circle"></i> Nota Debit</span>
                                    <i class="bi bi-check-circle-fill text-red-600 opacity-0 peer-checked:opacity-100 transition"></i>
                                </div>
                                <p class="text-xs text-gray-500">Tagihan Tambahan (Menambah Utang)</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- 3. NOMINAL (AUTONUMERIC) --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nilai Penyesuaian (Rp)</label>
                    <div class="relative">
                        {{-- Input Text untuk Display (Format Rupiah) --}}
                        <input type="text" id="amount-formatted" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg font-bold text-gray-800 py-2 px-3" placeholder="0" required>
                        {{-- Input Hidden untuk Value Asli ke Backend --}}
                        <input type="hidden" name="amount" id="amount-hidden" value="{{ old('amount') }}">
                    </div>
                </div>

                {{-- 4. ALASAN --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Alasan (Wajib)</label>
                    <textarea name="reason" id="reason" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Contoh: Kesalahan input harga manual..." required>{{ old('reason') }}</textarea>
                </div>

                {{-- 5. OPSI KELEBIHAN BAYAR --}}
                <div id="overpayment-section" class="hidden bg-blue-50 border border-blue-100 rounded-lg p-4">
                    <div class="flex gap-3 mb-3">
                        <i class="bi bi-info-circle-fill text-blue-600 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-bold text-blue-800">Penanganan Kelebihan Bayar</h4>
                            <p class="text-xs text-blue-600 mt-0.5">Jika Nota Kredit ini membuat tagihan lunas menjadi minus.</p>
                        </div>
                    </div>
                    <div class="space-y-2 ml-8">
                        <div class="flex items-center">
                            <input type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked class="text-blue-600 focus:ring-blue-500 border-gray-300">
                            <label for="overpayment_deposit" class="ml-2 text-sm text-gray-700 font-medium cursor-pointer">Simpan ke Deposit Supplier</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="overpayment_action" id="overpayment_refund" value="refund" class="text-blue-600 focus:ring-blue-500 border-gray-300">
                            <label for="overpayment_refund" class="ml-2 text-sm text-gray-700 font-medium cursor-pointer">Biarkan Minus (Refund Manual)</label>
                        </div>
                    </div>
                </div>

            </div>

            {{-- FOOTER --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <a href="{{ route('purchase-order-adjustments.create') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center">
                    <i class="bi bi-check-circle mr-2"></i> Simpan Penyesuaian
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 1. AutoNumeric Implementation
    const amountFormatted = document.getElementById('amount-formatted');
    const amountHidden = document.getElementById('amount-hidden');
    
    if(amountFormatted) {
        const autoNumericInstance = new AutoNumeric(amountFormatted, {
            decimalCharacter: ',', 
            digitGroupSeparator: '.', 
            decimalPlaces: 0, 
            minimumValue: '0',
            emptyInputBehavior: 'zero'
        });

        // Update hidden input saat mengetik
        amountFormatted.addEventListener('autoNumeric:rawValueModified', function(event) {
            amountHidden.value = event.detail.newRawValue;
        });

        // Set nilai lama jika ada (saat validasi gagal)
        if (amountHidden.value) {
            autoNumericInstance.set(amountHidden.value);
        }
    }

    // 2. Toggle Overpayment Logic
    const overpaymentSection = document.getElementById('overpayment-section');
    const creditNoteRadio = document.getElementById('type_credit_note');
    const debitNoteRadio = document.getElementById('type_debit_note');

    function toggleOverpaymentSection() {
        if (creditNoteRadio && creditNoteRadio.checked) {
            overpaymentSection.classList.remove('hidden');
        } else {
            overpaymentSection.classList.add('hidden');
        }
    }

    if(creditNoteRadio && debitNoteRadio) {
        creditNoteRadio.addEventListener('change', toggleOverpaymentSection);
        debitNoteRadio.addEventListener('change', toggleOverpaymentSection);
        
        // Init state on load
        toggleOverpaymentSection();
    }

    // 3. Client-side Validation
    const form = document.getElementById('manual-adjustment-form');
    if(form) {
        form.addEventListener('submit', function(event) {
            const amountValue = parseFloat(amountHidden.value) || 0;
            const reason = document.getElementById('reason').value.trim();
            
            if (amountValue <= 0) {
                event.preventDefault();
                Swal.fire('Error', 'Nilai penyesuaian harus lebih dari 0.', 'error');
                return;
            }
            
            if (!reason) {
                event.preventDefault();
                Swal.fire('Error', 'Silakan isi alasan penyesuaian.', 'warning');
                return;
            }
        });
    }
});
</script>
@endpush