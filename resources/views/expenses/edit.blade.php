@extends('layouts.app')

@section('title', 'Edit Pengeluaran')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('expenses.index') }}" class="hover:text-indigo-600 transition">Beban Operasional</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Pengeluaran</h2>
            <p class="text-sm text-gray-500 mt-1">Perbarui data transaksi pengeluaran.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('expenses.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('expenses.update', $expense) }}" method="POST" id="expense-form">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">edit_note</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Edit Data</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Tanggal --}}
                <div>
                    <label for="expense_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                </div>

                {{-- Jumlah --}}
                <div>
                    <label for="amount_display" class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">Rp</span>
                        </div>
                        <input type="text" id="amount_display" class="form-input block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg font-bold text-gray-900" required>
                        <input type="hidden" name="amount" id="amount" value="{{ old('amount', intval($expense->amount)) }}">
                    </div>
                </div>

                {{-- Kategori Beban --}}
                <div>
                    <label for="chart_of_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Kategori Beban <span class="text-red-500">*</span></label>
                    <select name="chart_of_account_id" id="chart_of_account_id" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="" disabled>-- Pilih Akun Beban --</option>
                        @foreach ($expenseAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('chart_of_account_id', $expense->chart_of_account_id) == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Sumber Dana --}}
                <div>
                    <label for="cash_bank_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Sumber Dana <span class="text-red-500">*</span></label>
                    <select name="cash_bank_account_id" id="cash_bank_account_id" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="" disabled>-- Pilih Akun Kas/Bank --</option>
                        @foreach ($cashAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id', $expense->cash_bank_account_id) == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Deskripsi --}}
                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="3" class="form-textarea block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>{{ old('description', $expense->description) }}</textarea>
                </div>

            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('expenses.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">check_circle</i> Update Pengeluaran
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
        const amountDisplay = document.getElementById('amount_display');
        const amountInput = document.getElementById('amount');
        
        // Init AutoNumeric
        const anElement = new AutoNumeric(amountDisplay, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: '0'
        });
        
        // Set Initial Value
        if(amountInput.value) {
            anElement.set(amountInput.value);
        }

        // Update hidden input
        amountDisplay.addEventListener('autoNumeric:rawValueModified', e => {
            amountInput.value = e.detail.newRawValue;
        });

        // Validasi Submit
        document.getElementById('expense-form').addEventListener('submit', function(e) {
            if(!amountInput.value || amountInput.value == 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Jumlah nominal tidak boleh kosong!',
                    confirmButtonColor: '#6366f1'
                });
            }
        });
    });
</script>
@endpush