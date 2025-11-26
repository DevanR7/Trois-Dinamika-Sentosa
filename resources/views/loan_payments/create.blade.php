@extends('layouts.app')

@section('title', 'Catat Pembayaran Cicilan')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-4xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('loans.show', $loan) }}" class="hover:text-indigo-600 transition-colors">Detail Pinjaman</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Bayar</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Catat Pembayaran Cicilan</h1>
            <p class="text-sm text-slate-500 mt-1">
                Untuk Pinjaman: <span class="font-bold text-indigo-600">{{ $loan->lender_name }}</span>
            </p>
        </div>
        <a href="{{ route('loans.show', $loan) }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ALERT SISA POKOK --}}
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-5 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-blue-600 flex-shrink-0 border border-blue-100 shadow-sm">
            <i class="material-icons text-2xl">account_balance</i>
        </div>
        <div>
            <p class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-1">Sisa Pokok Pinjaman Saat Ini</p>
            <h3 class="text-2xl font-bold text-blue-900 font-mono">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</h3>
            <input type="hidden" id="max_payment" value="{{ $loan->remaining_balance }}">
        </div>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
            <div class="flex items-start gap-3">
                <i class="material-icons text-red-600 text-xl mt-0.5">error_outline</i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                    <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('loans.payments.store', $loan) }}" method="POST" id="payment-form">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">payments</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Form Pembayaran</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Tanggal Bayar --}}
                <div class="md:col-span-2">
                    <label for="payment_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Bayar <span class="text-red-500">*</span></label>
                    <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-input w-full md:w-1/2" required>
                    @error('payment_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Bayar Pokok --}}
                <div>
                    <label for="principal_paid_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Jumlah Bayar Pokok (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-slate-400 font-bold text-sm">Rp</span>
                        </div>
                        <input type="text" id="principal_paid_display" class="form-input pl-10 text-lg font-bold text-slate-800 font-mono" placeholder="0" required>
                        <input type="hidden" name="principal_paid" id="principal_paid" value="{{ old('principal_paid', 0) }}">
                    </div>
                    <p class="mt-1.5 text-[11px] text-slate-500">Akan mengurangi saldo utang: <span class="font-bold">{{ $loan->loanAccount->account_name ?? 'N/A' }}</span></p>
                    @error('principal_paid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Bayar Bunga --}}
                <div>
                    <label for="interest_paid_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Jumlah Bayar Bunga (Rp)</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-slate-400 font-bold text-sm">Rp</span>
                        </div>
                        <input type="text" id="interest_paid_display" class="form-input pl-10 text-lg font-bold text-red-600 font-mono" placeholder="0">
                        <input type="hidden" name="interest_paid" id="interest_paid" value="{{ old('interest_paid', 0) }}">
                    </div>
                    <p class="mt-1.5 text-[11px] text-slate-500">Dicatat sebagai Beban Bunga.</p>
                    @error('interest_paid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Akun Beban Bunga --}}
                <div>
                    <label for="interest_expense_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Beban Bunga (Debit)</label>
                    <select name="interest_expense_account_id" id="interest_expense_account_id" class="form-input select2-basic">
                        <option value="" disabled selected>-- Pilih Akun Beban --</option>
                        @foreach ($expenseAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('interest_expense_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-slate-500">Wajib dipilih jika ada pembayaran bunga.</p>
                    @error('interest_expense_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Sumber Dana --}}
                <div>
                    <label for="cash_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sumber Dana (Kredit) <span class="text-red-500">*</span></label>
                    <select name="cash_bank_account_id" id="cash_bank_account_id" class="form-input select2-basic" required>
                        <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                        @foreach ($cashAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-slate-500">Akun Kas/Bank yang berkurang.</p>
                    @error('cash_bank_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div class="md:col-span-2">
                    <label for="notes" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Catatan</label>
                    <textarea name="notes" id="notes" rows="2" class="form-textarea" placeholder="Contoh: Pembayaran cicilan ke-1">{{ old('notes') }}</textarea>
                    @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('loans.show', $loan) }}" 
                   class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">check_circle</i> Simpan Pembayaran
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init Select2
        $('.select2-basic').select2({ placeholder: '-- Pilih --', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // Init AutoNumeric
        const anOptions = { decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', minimumValue: '0' };

        const principalDisplay = document.getElementById('principal_paid_display');
        const principalHidden = document.getElementById('principal_paid');
        if(principalDisplay) {
            const anPrincipal = new AutoNumeric(principalDisplay, anOptions);
            if(principalHidden.value) anPrincipal.set(principalHidden.value);
            principalDisplay.addEventListener('autoNumeric:rawValueModified', e => principalHidden.value = e.detail.newRawValue);
        }

        const interestDisplay = document.getElementById('interest_paid_display');
        const interestHidden = document.getElementById('interest_paid');
        if(interestDisplay) {
            const anInterest = new AutoNumeric(interestDisplay, anOptions);
            if(interestHidden.value) anInterest.set(interestHidden.value);
            interestDisplay.addEventListener('autoNumeric:rawValueModified', e => interestHidden.value = e.detail.newRawValue);
        }

        // Validasi Submit
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            const principal = parseFloat(principalHidden.value) || 0;
            const interest = parseFloat(interestHidden.value) || 0;
            const maxPayment = parseFloat(document.getElementById('max_payment').value);

            // 1. Cek Total > 0
            if (principal + interest <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Pembayaran Kosong',
                    text: 'Total pembayaran (Pokok + Bunga) harus lebih dari 0.',
                    confirmButtonColor: '#6366f1',
                    customClass: { popup: 'colored-toast rounded-xl' }
                });
                return;
            }

            // 2. Cek Overpayment
            if (principal > maxPayment) {
                e.preventDefault();
                const fmt = (val) => new Intl.NumberFormat('id-ID').format(val);
                Swal.fire({
                    icon: 'error',
                    title: 'Pembayaran Berlebih!',
                    text: `Jumlah bayar pokok (Rp ${fmt(principal)}) tidak boleh melebihi sisa hutang (Rp ${fmt(maxPayment)}).`,
                    confirmButtonColor: '#ef4444',
                    customClass: { popup: 'colored-toast rounded-xl' }
                });
                return;
            }

            // 3. Cek Akun Bunga
            const interestAcc = document.getElementById('interest_expense_account_id');
            // Kita cek nilai select2 (karena jQuery digunakan)
            const interestAccVal = $(interestAcc).val(); 
            
            if(interest > 0 && !interestAccVal) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Akun Bunga',
                    text: 'Silakan pilih Akun Beban Bunga karena Anda memasukkan nilai bunga.',
                    confirmButtonColor: '#f59e0b',
                    customClass: { popup: 'colored-toast rounded-xl' }
                });
                return;
            }
        });

        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush