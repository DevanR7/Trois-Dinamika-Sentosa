@extends('layouts.app')

@section('title', 'Catat Pinjaman Baru')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('loans.index') }}" class="hover:text-indigo-600 transition">Pinjaman</a>
                <span>/</span>
                <span class="text-gray-800">Baru</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Catat Pinjaman Baru</h2>
            <p class="text-sm text-gray-500 mt-1">Catat penerimaan pinjaman uang dari pihak luar (Bank/Koperasi).</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('loans.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('loans.store') }}" method="POST" id="loan-form">
        @csrf
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">account_balance_wallet</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Form Pinjaman</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Nama Pemberi --}}
                <div class="md:col-span-2">
                    <label for="lender_name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Pemberi Pinjaman <span class="text-red-500">*</span></label>
                    <input type="text" name="lender_name" id="lender_name" value="{{ old('lender_name') }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Bank BCA, Koperasi Mandiri" required>
                    @error('lender_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Tanggal --}}
                <div>
                    <label for="loan_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Diterima <span class="text-red-500">*</span></label>
                    <input type="date" name="loan_date" id="loan_date" value="{{ old('loan_date', now()->toDateString()) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    @error('loan_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Jumlah Pokok --}}
                <div>
                    <label for="principal_amount_display" class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah Pokok Pinjaman (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">Rp</span>
                        </div>
                        <input type="text" id="principal_amount_display" class="form-input block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg font-bold text-gray-900" placeholder="0" required>
                        <input type="hidden" name="principal_amount" id="principal_amount" value="{{ old('principal_amount') }}">
                    </div>
                    @error('principal_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Akun Utang --}}
                <div>
                    <label for="loan_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Utang (Kredit) <span class="text-red-500">*</span></label>
                    <select name="loan_account_id" id="loan_account_id" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="" disabled selected>-- Pilih Akun Liabilitas --</option>
                        @foreach ($loanAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('loan_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Akun Kewajiban yang akan bertambah.</p>
                    @error('loan_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Akun Kas --}}
                <div>
                    <label for="cash_bank_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Dana Diterima Ke (Debit) <span class="text-red-500">*</span></label>
                    <select name="cash_bank_account_id" id="cash_bank_account_id" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                        @foreach ($cashAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Akun Kas/Bank tempat uang masuk.</p>
                    @error('cash_bank_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Deskripsi --}}
                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi</label>
                    <textarea name="description" id="description" rows="3" class="form-textarea block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Catatan opsional (misal: Tenor 5 tahun, Bunga 10%)">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('loans.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">save</i> Simpan Pinjaman
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const amountDisplay = document.getElementById('principal_amount_display');
        const amountInput = document.getElementById('principal_amount');
        
        // Init AutoNumeric
        const anElement = new AutoNumeric(amountDisplay, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: '0'
        });

        // Update hidden input
        amountDisplay.addEventListener('autoNumeric:rawValueModified', e => {
            amountInput.value = e.detail.newRawValue;
        });

        // Validasi
        document.getElementById('loan-form').addEventListener('submit', function(e) {
            if(!amountInput.value || amountInput.value == 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Jumlah Pokok Pinjaman tidak boleh kosong atau nol!',
                    confirmButtonColor: '#6366f1'
                });
            }
        });
    });
</script>
@endpush