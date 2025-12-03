@extends('admin.layouts.app')

@section('title', 'Koreksi Manual PO')

@section('content')
<div class="max-w-3xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="hover:text-indigo-600 transition">Penyesuaian</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Manual</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">
                Koreksi Manual PO: <span class="text-indigo-600 font-mono ml-1">{{ $purchaseOrder->po_number }}</span>
            </h2>
        </div>
        <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 text-sm font-bold hover:bg-slate-50 transition shadow-sm">
            <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ERROR HANDLING --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 text-xl">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input:</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- INFO SISA TAGIHAN --}}
    <div class="mb-6 dashboard-card p-5 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-indigo-50 rounded-full text-indigo-600 border border-indigo-100">
                <i class="material-icons text-2xl">account_balance_wallet</i>
            </div>
            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest">Sisa Utang Saat Ini</span>
                <span class="text-xl font-bold text-slate-800">Rp {{ number_format($purchaseOrder->remaining_balance, 0, ',', '.') }}</span>
            </div>
        </div>
        <span class="px-3 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-full border border-slate-200 uppercase tracking-wide">Info</span>
    </div>

    {{-- FORM INPUT --}}
    <form action="{{ route('admin.purchase-order-adjustments.store.manual') }}" method="POST" id="manual-adjustment-form">
        @csrf
        <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->po_id }}">

        <div class="dashboard-card p-0 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="material-icons text-indigo-500 text-sm">keyboard</i> Form Input Manual
                </h3>
            </div>
            
            <div class="p-8 space-y-8">
                
                {{-- 1. TANGGAL --}}
                <div class="max-w-xs">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Tanggal Penyesuaian</label>
                    <input type="date" name="adjustment_date" class="form-input" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" required>
                </div>

                {{-- 2. JENIS KOREKSI (Radio Cards) --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-3 ml-1">Jenis Koreksi</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        {{-- Nota Kredit --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="type" id="type_credit_note" value="credit_note" class="peer sr-only" {{ old('type', 'credit_note') == 'credit_note' ? 'checked' : '' }}>
                            <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-200 hover:bg-emerald-50/30 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50/50 peer-checked:shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-emerald-700 flex items-center gap-2">
                                        <i class="material-icons">arrow_circle_down</i> Nota Kredit
                                    </span>
                                    <i class="material-icons text-emerald-600 opacity-0 peer-checked:opacity-100 transition-opacity">check_circle</i>
                                </div>
                                <p class="text-xs text-slate-500 leading-snug">Potongan/Diskon (Mengurangi Utang)</p>
                            </div>
                        </label>

                        {{-- Nota Debit --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="type" id="type_debit_note" value="debit_note" class="peer sr-only" {{ old('type') == 'debit_note' ? 'checked' : '' }}>
                            <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-red-200 hover:bg-red-50/30 transition-all peer-checked:border-red-500 peer-checked:bg-red-50/50 peer-checked:shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-red-700 flex items-center gap-2">
                                        <i class="material-icons">arrow_circle_up</i> Nota Debit
                                    </span>
                                    <i class="material-icons text-red-600 opacity-0 peer-checked:opacity-100 transition-opacity">check_circle</i>
                                </div>
                                <p class="text-xs text-slate-500 leading-snug">Tagihan Tambahan (Menambah Utang)</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- 3. NOMINAL (AUTONUMERIC) --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Nilai Penyesuaian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-slate-400 font-bold text-sm">Rp</span>
                        </div>
                        {{-- Input Text untuk Display (Format Rupiah) --}}
                        <input type="text" id="amount-formatted" class="form-input pl-10 text-lg font-bold text-slate-800" placeholder="0" required>
                        {{-- Input Hidden untuk Value Asli ke Backend --}}
                        <input type="hidden" name="amount" id="amount-hidden" value="{{ old('amount') }}">
                    </div>
                </div>

                {{-- 4. ALASAN --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Alasan (Wajib)</label>
                    <textarea name="reason" id="reason" rows="3" class="form-textarea" placeholder="Contoh: Kesalahan input harga manual..." required>{{ old('reason') }}</textarea>
                </div>

                {{-- 5. OPSI KELEBIHAN BAYAR --}}
                <div id="overpayment-section" class="hidden bg-blue-50/50 border border-blue-100 rounded-xl p-5 transition-all">
                    <div class="flex gap-3 mb-3">
                        <i class="material-icons text-blue-600">info</i>
                        <div>
                            <h4 class="text-sm font-bold text-blue-800">Penanganan Kelebihan Bayar</h4>
                            <p class="text-xs text-blue-600/80 mt-0.5">Jika Nota Kredit ini membuat tagihan lunas menjadi minus.</p>
                        </div>
                    </div>
                    <div class="space-y-2 ml-9 mt-2">
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked class="text-indigo-600 focus:ring-indigo-500 border-slate-300 h-4 w-4">
                            <span class="ml-2 text-sm text-slate-700 font-medium group-hover:text-indigo-600">Simpan ke Deposit Supplier</span>
                        </label>
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="overpayment_action" id="overpayment_refund" value="refund" class="text-indigo-600 focus:ring-indigo-500 border-slate-300 h-4 w-4">
                            <span class="ml-2 text-sm text-slate-700 font-medium group-hover:text-indigo-600">Biarkan Minus (Refund Manual)</span>
                        </label>
                    </div>
                </div>

            </div>

            {{-- FOOTER --}}
            <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex justify-end gap-3 rounded-b-xl">
                <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-bold hover:bg-white hover:shadow-sm transition-all">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                    <i class="material-icons text-sm">save</i> Simpan Penyesuaian
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
    let autoNumericInstance = null;
    
    if(amountFormatted) {
        autoNumericInstance = new AutoNumeric(amountFormatted, {
            decimalCharacter: ',', 
            digitGroupSeparator: '.', 
            decimalPlaces: 0, 
            minimumValue: '0',
            emptyInputBehavior: 'zero',
            unformatOnSubmit: false // PENTING: Kita kirim hidden input manual
        });

        // Update hidden input saat mengetik (optional, but good for live validation)
        amountFormatted.addEventListener('autoNumeric:rawValueModified', function(event) {
            amountHidden.value = event.detail.newRawValue;
        });

        // Set nilai lama jika ada (saat redirect back)
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
        toggleOverpaymentSection(); // Init state
    }

    // 3. Client-side Validation & Submit Handler
    const form = document.getElementById('manual-adjustment-form');
    if(form) {
        form.addEventListener('submit', function(event) {
            // VALIDASI 1: Pastikan nilai numerik terisi di hidden input
            if (autoNumericInstance) {
                const rawValue = autoNumericInstance.getNumber(); // Ambil angka murni
                amountHidden.value = rawValue; 
            }

            const amountValue = parseFloat(amountHidden.value) || 0;
            const reason = document.getElementById('reason').value.trim();
            
            if (amountValue <= 0) {
                event.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Nilai Kosong',
                    text: 'Nilai penyesuaian harus lebih dari 0.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }
            
            if (!reason) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Alasan Wajib',
                    text: 'Silakan isi alasan penyesuaian.',
                    confirmButtonColor: '#fbbf24',
                });
                return;
            }
            
            // Loading state button
            const btn = form.querySelector('button[type="submit"]');
            if(btn) {
                btn.innerHTML = '<i class="material-icons animate-spin text-sm mr-2">sync</i> Menyimpan...';
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
            }
        });
    }
});
</script>
@endpush