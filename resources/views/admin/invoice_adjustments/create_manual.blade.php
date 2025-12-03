@extends('admin.layouts.app')

@section('title', 'Koreksi Manual Invoice')

@section('content')
<div class="max-w-4xl mx-auto animate-enter pb-20">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('admin.invoices.show', $invoice->invoice_id) }}" class="hover:text-indigo-600 transition">Invoice</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Koreksi Manual</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">
                Koreksi Manual <span class="text-indigo-600 font-mono bg-indigo-50 px-2 rounded">{{ $invoice->invoice_number }}</span>
            </h2>
            <p class="text-sm text-slate-500 mt-1">Input nominal koreksi secara langsung (Lumpsum) tanpa merinci item.</p>
        </div>
        <a href="{{ route('admin.invoice-adjustments.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 text-sm font-bold hover:bg-slate-50 transition shadow-sm">
            <i class="material-icons text-base mr-2">arrow_back</i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 text-xl">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI: FORM --}}
        <div class="lg:col-span-2 space-y-6">
            <form action="{{ route('admin.invoice-adjustments.store.manual') }}" method="POST" id="manual-adjustment-form">
                @csrf
                <input type="hidden" name="sales_invoice_id" value="{{ $invoice->invoice_id }}">

                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                        <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                            <i class="material-icons text-lg">edit_note</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Formulir Koreksi</h3>
                    </div>
                    
                    <div class="p-8 space-y-6">
                        
                        {{-- TANGGAL --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 ml-1">Tanggal Penyesuaian</label>
                            <input type="date" name="adjustment_date" class="form-input max-w-xs" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" required>
                        </div>

                        {{-- JENIS KOREKSI --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-3 ml-1">Jenis Koreksi</label>
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
                                        <p class="text-xs text-slate-500 leading-snug">Mengurangi tagihan (Potongan/Refund).</p>
                                    </div>
                                </label>

                                {{-- Nota Debit --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="type" id="type_debit_note" value="debit_note" class="peer sr-only">
                                    <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-amber-200 hover:bg-amber-50/30 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50/50 peer-checked:shadow-sm">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-bold text-amber-700 flex items-center gap-2">
                                                <i class="material-icons">arrow_circle_up</i> Nota Debit
                                            </span>
                                            <i class="material-icons text-amber-600 opacity-0 peer-checked:opacity-100 transition-opacity">check_circle</i>
                                        </div>
                                        <p class="text-xs text-slate-500 leading-snug">Menambah tagihan (Kurang Bayar).</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- NOMINAL --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 ml-1">Nilai (Rp)</label>
                            <input type="text" id="amount-formatted" class="form-input text-xl font-bold text-slate-800 tracking-wide pl-4 h-12 border-2" placeholder="0" required>
                            <input type="hidden" name="amount" id="amount-hidden">
                        </div>

                        {{-- ALASAN --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 ml-1">Alasan (Wajib)</label>
                            <textarea name="reason" id="reason" class="form-textarea" placeholder="Contoh: Kesalahan input harga manual, diskon tambahan..." required>{{ old('reason') }}</textarea>
                        </div>

                        {{-- OPSI OVERPAYMENT --}}
                        <div id="overpayment-section" class="hidden bg-blue-50/50 border border-blue-100 rounded-xl p-5 transition-all animate-enter">
                            <div class="flex gap-3 mb-3">
                                <i class="material-icons text-blue-600">info</i>
                                <div>
                                    <h4 class="text-sm font-bold text-blue-800">Penanganan Kelebihan Bayar</h4>
                                    <p class="text-xs text-blue-600/80 mt-1 leading-relaxed">
                                        Jika Nota Kredit ini membuat tagihan lunas menjadi minus, sisa uang mau dikemanakan?
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-3 ml-9 mt-2">
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked class="text-indigo-600 focus:ring-indigo-500 border-gray-300 h-4 w-4">
                                    <span class="ml-2 text-sm text-slate-700 font-medium group-hover:text-indigo-600 transition-colors">Simpan ke Saldo Kredit (Deposit Klien)</span>
                                </label>
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="overpayment_action" id="overpayment_refund" value="refund" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 h-4 w-4">
                                    <span class="ml-2 text-sm text-slate-700 font-medium group-hover:text-indigo-600 transition-colors">Biarkan Minus (Refund Manual Nanti)</span>
                                </label>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                            <button type="submit" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5 flex items-center gap-2">
                                <i class="material-icons text-lg">save</i> Simpan Koreksi
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </div>

        {{-- KOLOM KANAN: INFO RINGKAS --}}
        <div class="lg:col-span-1">
            <div class="dashboard-card p-6 sticky top-6">
                <h4 class="text-xs font-bold text-slate-400 uppercase mb-4">Status Invoice</h4>
                
                <div class="mb-6 text-center p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Sisa Tagihan</span>
                    <span class="text-2xl font-mono font-bold text-slate-800">Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</span>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Total Invoice</span>
                        <span class="font-bold text-slate-700">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-600">
                        <span>Sudah Dibayar</span>
                        <span class="font-bold">- Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 1. AutoNumeric
    const amountFormatted = document.getElementById('amount-formatted');
    const amountHidden = document.getElementById('amount-hidden');
    
    if(amountFormatted) {
        const an = new AutoNumeric(amountFormatted, {
            decimalCharacter: ',', 
            digitGroupSeparator: '.', 
            decimalPlaces: 0, 
            minimumValue: '0',
            emptyInputBehavior: 'zero',
            currencySymbol: 'Rp ',
            currencySymbolPlacement: 'p'
        });

        amountFormatted.addEventListener('autoNumeric:rawValueModified', e => {
            amountHidden.value = e.detail.newRawValue;
        });
    }

    // 2. Toggle Overpayment
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
        toggleOverpayment();
    }

    // 3. Validasi Submit
    const form = document.getElementById('manual-adjustment-form');
    if(form) {
        form.addEventListener('submit', function(event) {
            const val = parseFloat(amountHidden.value || 0);
            const reason = document.getElementById('reason').value.trim();
            
            if (val <= 0) {
                event.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Nilai Kosong',
                    text: 'Nilai penyesuaian harus lebih dari 0.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }
        });
    }
});
</script>
@endpush