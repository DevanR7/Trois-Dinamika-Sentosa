@extends('admin.layouts.app')

@section('title', 'Edit Pengeluaran')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-3xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.expenses.index') }}" class="hover:text-indigo-600 transition-colors">Beban Operasional</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Edit</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Pengeluaran</h1>
        </div>
        <a href="{{ route('admin.expenses.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.expenses.update', $expense) }}" method="POST" id="expense-form">
        @csrf
        @method('PUT')
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">edit_note</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Edit Data</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Tanggal --}}
                <div>
                    <label for="expense_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" class="form-input" required>
                </div>

                {{-- Jumlah --}}
                <div>
                    <label for="amount_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Jumlah (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-slate-400 font-bold text-sm">Rp</span>
                        </div>
                        <input type="text" id="amount_display" class="form-input pl-10 text-lg font-bold text-slate-800 font-mono" required>
                        <input type="hidden" name="amount" id="amount" value="{{ old('amount', intval($expense->amount)) }}">
                    </div>
                </div>

                {{-- Kategori Beban --}}
                <div>
                    <label for="chart_of_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Kategori Beban <span class="text-red-500">*</span></label>
                    <select name="chart_of_account_id" id="chart_of_account_id" class="form-input select2-basic" required>
                        @foreach ($expenseAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('chart_of_account_id', $expense->chart_of_account_id) == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Sumber Dana --}}
                <div>
                    <label for="cash_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sumber Dana <span class="text-red-500">*</span></label>
                    <select name="cash_bank_account_id" id="cash_bank_account_id" class="form-input select2-basic" required>
                        @foreach ($cashAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id', $expense->cash_bank_account_id) == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Deskripsi --}}
                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Deskripsi <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="3" class="form-textarea" required>{{ old('description', $expense->description) }}</textarea>
                </div>

            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('admin.expenses.index') }}" 
                   class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">check_circle</i> Update
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
        $('.select2-basic').select2({ placeholder: '-- Pilih --', allowClear: false, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // Init AutoNumeric
        const amountDisplay = document.getElementById('amount_display');
        const amountInput = document.getElementById('amount');
        
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

        document.getElementById('expense-form').addEventListener('submit', function(e) {
            if(!amountInput.value || amountInput.value == 0) {
                e.preventDefault();
                window.showToast('Jumlah nominal tidak boleh kosong!', 'error');
                amountDisplay.focus();
            }
        });

        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush