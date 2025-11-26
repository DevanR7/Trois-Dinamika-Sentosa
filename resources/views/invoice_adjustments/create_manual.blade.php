@extends('layouts.app')

@section('title', 'Koreksi Manual Invoice')

@section('content')
<div class="max-w-3xl mx-auto animate-enter pb-10">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('invoice-adjustments.create') }}" class="hover:text-indigo-600 transition">Penyesuaian</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Manual</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">
                Koreksi Invoice <span class="text-indigo-600 font-mono text-xl ml-1">{{ $invoice->invoice_number }}</span>
            </h2>
        </div>
        <a href="{{ route('invoice-adjustments.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 text-sm font-bold hover:bg-slate-50 transition shadow-sm">
            <i class="material-icons text-base mr-2">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 mt-0.5">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-600">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- INFO SISA TAGIHAN --}}
    <div class="mb-6 dashboard-card p-5 flex items-center justify-between bg-white">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-indigo-50 rounded-full text-indigo-600 border border-indigo-100">
                <i class="material-icons text-2xl">account_balance_wallet</i>
            </div>
            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest">Sisa Tagihan Saat Ini</span>
                <span class="text-xl font-bold text-slate-800">Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</span>
            </div>
        </div>
        <span class="hidden sm:inline-block px-3 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-wider rounded-full border border-slate-200">
            Current Balance
        </span>
    </div>

    {{-- FORM INPUT --}}
    <form action="{{ route('invoice-adjustments.store.manual') }}" method="POST" id="manual-adjustment-form">
        @csrf
        <input type="hidden" name="sales_invoice_id" value="{{ $invoice->invoice_id }}">

        <div class="dashboard-card p-0">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                <i class="material-icons text-indigo-500 text-sm">edit_note</i>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Form Input Manual</h3>
            </div>
            
            <div class="p-8 space-y-8">
                
                {{-- 1. TANGGAL --}}
                <div class="max-w-xs">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-2 ml-1">Tanggal Penyesuaian</label>
                    <input type="date" name="adjustment_date" class="form-input" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" required>
                </div>

                {{-- 2. JENIS KOREKSI (RADIO CARDS) --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-3 ml-1">Jenis Koreksi</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        {{-- Nota Kredit --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="type" id="type_credit_note" value="credit_note" class="peer sr-only" checked>
                            <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-200 hover:bg-emerald-50/30 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50/50 peer-checked:shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-emerald-700 flex items-center gap-2">
                                        <i class="material-icons">arrow_circle_down</i> Nota Kredit
                                    </span>
                                    <i class="material-icons text-emerald-600 opacity-0 peer-checked:opacity-100 transition-opacity">check_circle</i>
                                </div>
                                <p class="text-xs text-slate-500 leading-snug">Mengurangi nilai tagihan (Potongan / Diskon).</p>
                            </div>
                        </label>

                        {{-- Nota Debit --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="type" id="type_debit_note" value="debit_note" class="peer sr-only">
                            <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-red-200 hover:bg-red-50/30 transition-all peer-checked:border-red-500 peer-checked:bg-red-50/50 peer-checked:shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-red-700 flex items-center gap-2">
                                        <i class="material-icons">arrow_circle_up</i> Nota Debit
                                    </span>
                                    <i class="material-icons text-red-600 opacity-0 peer-checked:opacity-100 transition-opacity">check_circle</i>
                                </div>
                                <p class="text-xs text-slate-500 leading-snug">Menambah nilai tagihan (Biaya Tambahan).</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- 3. NOMINAL (AUTONUMERIC) --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-2 ml-1">Nilai Penyesuaian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-slate-400 font-bold text-sm">Rp</span>
                        </div>
                        <input type="text" id="amount-formatted" class="form-input pl-10 text-lg font-bold text-slate-800 tracking-wide" placeholder="0" required>
                        <input type="hidden" name="amount" id="amount-hidden">
                    </div>
                </div>

                {{-- 4. ALASAN --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-2 ml-1">Alasan (Wajib)</label>
                    <textarea name="reason" id="reason" class="form-textarea" placeholder="Contoh: Kesalahan input harga manual, diskon tambahan..." required>{{ old('reason') }}</textarea>
                </div>

                {{-- 5. OPSI KELEBIHAN BAYAR --}}
                <div id="overpayment-section" class="hidden bg-blue-50/50 border border-blue-100 rounded-xl p-5 transition-all animate-enter">
                    <div class="flex gap-3 mb-3">
                        <i class="material-icons text-blue-600">info</i>
                        <div>
                            <h4 class="text-sm font-bold text-blue-800">Penanganan Kelebihan Bayar</h4>
                            <p class="text-xs text-blue-600/80 mt-1 leading-relaxed">
                                Jika Nota Kredit ini membuat tagihan lunas menjadi minus (lebih bayar), sisa uang mau dikemanakan?
                            </p>
                        </div>
                    </div>
                    <div class="space-y-3 ml-9 mt-2">
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="overpayment_action" id="op_deposit" value="deposit" checked class="text-indigo-600 focus:ring-indigo-500 border-gray-300 h-4 w-4">
                            <span class="ml-2 text-sm text-slate-700 font-medium group-hover:text-indigo-600 transition-colors">Simpan ke Saldo Kredit (Deposit Klien)</span>
                        </label>
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="overpayment_action" id="op_refund" value="refund" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 h-4 w-4">
                            <span class="ml-2 text-sm text-slate-700 font-medium group-hover:text-indigo-600 transition-colors">Biarkan Minus (Refund Manual Nanti)</span>
                        </label>
                    </div>
                </div>

            </div>

            {{-- FOOTER --}}
            <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex justify-end gap-3 rounded-b-xl">
                <a href="{{ route('invoice-adjustments.create') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-bold hover:bg-white hover:shadow-sm transition-all">
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 1. AutoNumeric Manual Init (Karena butuh update hidden input)
    const amountFormatted = document.getElementById('amount-formatted');
    const amountHidden = document.getElementById('amount-hidden');
    
    if(amountFormatted) {
        const an = new AutoNumeric(amountFormatted, {
            decimalCharacter: ',', 
            digitGroupSeparator: '.', 
            decimalPlaces: 0, 
            minimumValue: '0',
            emptyInputBehavior: 'zero',
            currencySymbol: '', // Simbol sudah ada di HTML
            unformatOnSubmit: true
        });

        amountFormatted.addEventListener('autoNumeric:rawValueModified', e => {
            amountHidden.value = e.detail.newRawValue;
        });
    }

    // 2. Toggle Overpayment Section
    const overpaymentSection = document.getElementById('overpayment-section');
    const creditNoteRadio = document.getElementById('type_credit_note');
    const debitNoteRadio = document.getElementById('type_debit_note');

    function toggleOverpayment() {
        if(creditNoteRadio.checked) {
            overpaymentSection.classList.remove('hidden');
        } else {
            overpaymentSection.classList.add('hidden');
        }
    }
    
    if(creditNoteRadio && debitNoteRadio) {
        creditNoteRadio.addEventListener('change', toggleOverpayment);
        debitNoteRadio.addEventListener('change', toggleOverpayment);
        toggleOverpayment(); // Init state
    }

    // 3. Client-side Validation (SweetAlert)
    const form = document.getElementById('manual-adjustment-form');
    if(form) {
        form.addEventListener('submit', function(e) {
            const val = parseFloat(amountHidden.value || 0);
            const reason = document.getElementById('reason').value.trim();
            
            if (val <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Nilai Kosong',
                    text: 'Nilai penyesuaian harus lebih dari 0.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }
            if (!reason) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Alasan Wajib',
                    text: 'Silakan isi alasan penyesuaian untuk keperluan audit.',
                    confirmButtonColor: '#fbbf24',
                    confirmButtonText: 'Oke, Saya Isi'
                });
                return;
            }
        });
    }
});
</script>
@endpush