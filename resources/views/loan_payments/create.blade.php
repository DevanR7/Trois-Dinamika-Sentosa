@extends('layouts.app')

@section('title', 'Catat Pembayaran Cicilan')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('loans.show', $loan) }}" class="hover:text-indigo-600 transition">Detail Pinjaman</a>
                <span>/</span>
                <span class="text-gray-800">Bayar Cicilan</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Catat Pembayaran Cicilan</h2>
            <p class="text-sm text-gray-500 mt-1">
                Untuk Pinjaman: <span class="font-bold text-indigo-600">{{ $loan->lender_name }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('loans.show', $loan) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    {{-- ALERT SISA POKOK --}}
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
            <i class="material-icons text-2xl">info</i>
        </div>
        <div>
            <p class="text-sm text-blue-800 font-medium uppercase tracking-wider">Sisa Pokok Pinjaman Saat Ini</p>
            <h3 class="text-2xl font-bold text-blue-900 mt-1">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</h3>
            <input type="hidden" id="max_payment" value="{{ $loan->remaining_balance }}">
        </div>
    </div>

    <form action="{{ route('loans.payments.store', $loan) }}" method="POST" id="payment-form">
        @csrf
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">payments</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Form Pembayaran</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Tanggal Bayar --}}
                <div class="md:col-span-2">
                    <label for="payment_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Bayar <span class="text-red-500">*</span></label>
                    <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    @error('payment_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Bayar Pokok --}}
                <div>
                    <label for="principal_paid_display" class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah Bayar Pokok (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">Rp</span>
                        </div>
                        <input type="text" id="principal_paid_display" class="form-input block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg font-bold text-gray-900" placeholder="0" required>
                        <input type="hidden" name="principal_paid" id="principal_paid" value="{{ old('principal_paid', 0) }}">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Akan mengurangi saldo utang: <span class="font-medium text-gray-700">{{ $loan->loanAccount->account_name ?? 'N/A' }}</span></p>
                    @error('principal_paid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Bayar Bunga --}}
                <div>
                    <label for="interest_paid_display" class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah Bayar Bunga (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">Rp</span>
                        </div>
                        <input type="text" id="interest_paid_display" class="form-input block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg font-bold text-gray-900" placeholder="0" required>
                        <input type="hidden" name="interest_paid" id="interest_paid" value="{{ old('interest_paid', 0) }}">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Dicatat sebagai Beban Bunga.</p>
                    @error('interest_paid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Akun Beban Bunga --}}
                <div>
                    <label for="interest_expense_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Beban Bunga (Debit) <span class="text-red-500">*</span></label>
                    <select name="interest_expense_account_id" id="interest_expense_account_id" class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="" disabled selected>-- Pilih Akun Beban --</option>
                        @foreach ($expenseAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('interest_expense_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Wajib dipilih jika ada pembayaran bunga.</p>
                    @error('interest_expense_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Sumber Dana --}}
                <div>
                    <label for="cash_bank_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Sumber Dana (Kredit) <span class="text-red-500">*</span></label>
                    <select name="cash_bank_account_id" id="cash_bank_account_id" class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                        @foreach ($cashAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Akun Kas/Bank yang berkurang.</p>
                    @error('cash_bank_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div class="md:col-span-2">
                    <label for="notes" class="block text-xs font-bold text-gray-500 uppercase mb-1">Catatan</label>
                    <textarea name="notes" id="notes" rows="2" class="form-textarea block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Pembayaran cicilan ke-1">{{ old('notes') }}</textarea>
                    @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('loans.show', $loan) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">check_circle</i> Simpan Pembayaran
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init Select2
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

        // Init AutoNumeric
        const anOptions = { decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', minimumValue: '0' };

        const principalDisplay = document.getElementById('principal_paid_display');
        const principalHidden = document.getElementById('principal_paid');
        if(principalDisplay) {
            new AutoNumeric(principalDisplay, anOptions);
            principalDisplay.addEventListener('autoNumeric:rawValueModified', e => principalHidden.value = e.detail.newRawValue);
        }

        const interestDisplay = document.getElementById('interest_paid_display');
        const interestHidden = document.getElementById('interest_paid');
        if(interestDisplay) {
            new AutoNumeric(interestDisplay, anOptions);
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
                    title: 'Error',
                    text: 'Total pembayaran (Pokok + Bunga) harus lebih dari 0.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            // 2. Cek Overpayment
            if (principal > maxPayment) {
                e.preventDefault();
                // Format Rupiah untuk pesan error
                const fmt = (val) => new Intl.NumberFormat('id-ID').format(val);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Pembayaran Berlebih!',
                    text: `Jumlah bayar pokok (Rp ${fmt(principal)}) tidak boleh melebihi sisa hutang (Rp ${fmt(maxPayment)}).`,
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            // 3. Cek Akun Bunga
            const interestAcc = document.getElementById('interest_expense_account_id').value;
            if(interest > 0 && !interestAcc) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Akun Bunga',
                    text: 'Silakan pilih Akun Beban Bunga karena Anda memasukkan nilai bunga.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }
        });
    });
</script>
@endpush