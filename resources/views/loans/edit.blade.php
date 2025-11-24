@extends('layouts.app')

@section('title', 'Edit Pinjaman')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('loans.index') }}" class="hover:text-indigo-600 transition">Pinjaman</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Pinjaman</h2>
            <p class="text-sm text-gray-500 mt-1">Perbarui data pinjaman.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('loans.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    @if ($loan->payments()->exists())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-red-500 text-2xl">lock</i>
            <div>
                <strong class="text-red-800 text-sm font-bold block">Terkunci!</strong>
                <span class="text-red-700 text-xs">Pinjaman ini tidak bisa diedit karena sudah memiliki riwayat pembayaran.</span>
            </div>
        </div>
    @endif

    <form action="{{ route('loans.update', $loan) }}" method="POST" id="loan-form">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">edit_note</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Edit Data</h3>
            </div>
            
            <fieldset {{ $loan->payments()->exists() ? 'disabled' : '' }} class="group disabled:opacity-75 transition-opacity">
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="md:col-span-2">
                        <label for="lender_name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Pemberi Pinjaman <span class="text-red-500">*</span></label>
                        <input type="text" name="lender_name" id="lender_name" value="{{ old('lender_name', $loan->lender_name) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>

                    <div>
                        <label for="loan_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Diterima <span class="text-red-500">*</span></label>
                        <input type="date" name="loan_date" id="loan_date" value="{{ old('loan_date', $loan->loan_date->toDateString()) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>

                    <div>
                        <label for="principal_amount_display" class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah Pokok Pinjaman (Rp) <span class="text-red-500">*</span></label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="text" id="principal_amount_display" class="form-input block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg font-bold text-gray-900" required>
                            <input type="hidden" name="principal_amount" id="principal_amount" value="{{ old('principal_amount', intval($loan->principal_amount)) }}">
                        </div>
                    </div>

                    <div>
                        <label for="loan_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Utang (Kredit) <span class="text-red-500">*</span></label>
                        <select name="loan_account_id" id="loan_account_id" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @foreach ($loanAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('loan_account_id', $loan->loan_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="cash_bank_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Dana Diterima Ke (Debit) <span class="text-red-500">*</span></label>
                        <select name="cash_bank_account_id" id="cash_bank_account_id" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @foreach ($cashAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id', $loan->cash_bank_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi</label>
                        <textarea name="description" id="description" rows="3" class="form-textarea block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description', $loan->description) }}</textarea>
                    </div>

                </div>
            </fieldset>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('loans.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed" {{ $loan->payments()->exists() ? 'disabled' : '' }}>
                    <i class="material-icons text-lg mr-2">check_circle</i> Update Pinjaman
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
        
        const anElement = new AutoNumeric(amountDisplay, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: '0'
        });
        
        if(amountInput.value) anElement.set(amountInput.value);

        amountDisplay.addEventListener('autoNumeric:rawValueModified', e => {
            amountInput.value = e.detail.newRawValue;
        });

        document.getElementById('loan-form').addEventListener('submit', function(e) {
            if(!amountInput.value || amountInput.value == 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Jumlah pokok tidak boleh kosong!',
                    confirmButtonColor: '#6366f1'
                });
            }
        });
    });
</script>
@endpush