@extends('admin.layouts.app')

@section('title', 'Edit Pengeluaran')

@section('content')

    <div class="max-w-3xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Edit Pengeluaran</h1>
                <p class="page-subtitle">Koreksi data biaya operasional.</p>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                    <i class="material-icons text-sm mr-1">delete</i> Hapus
                </button>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('admin.expenses.update', $expense->expense_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <h3 class="card-header-title">Formulir Biaya</h3>
                    <span class="badge badge-primary">ID: EXP-{{ $expense->expense_id }}</span>
                </div>
                <div class="card-body space-y-6">

                    {{-- Tanggal & Jumlah --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="form-label label-required">Tanggal</label>
                            <input type="date" name="expense_date" class="form-input" 
                                   value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
                            @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="form-label label-required">Jumlah (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                {{-- Value untuk edit: gunakan raw number --}}
                                <input type="text" name="amount" 
                                       class="form-input autonumeric text-right font-bold text-rose-600" 
                                       value="{{ old('amount', $expense->amount) }}" required>
                            </div>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Akun & Sumber Dana --}}
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- Akun Biaya --}}
                        <div>
                            <label class="form-label label-required text-indigo-600 dark:text-indigo-400">
                                <i class="material-icons text-[10px] align-middle mr-1">trending_up</i>
                                Untuk Biaya Apa? (Debit)
                            </label>
                            <select name="chart_of_account_id" class="tom-select" required>
                                <option value="">Pilih Akun Beban...</option>
                                @foreach($expenseAccounts as $coa)
                                    <option value="{{ $coa->account_id }}" {{ old('chart_of_account_id', $expense->chart_of_account_id) == $coa->account_id ? 'selected' : '' }}>
                                        {{ $coa->account_number }} - {{ $coa->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('chart_of_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Sumber Dana --}}
                        <div>
                            <label class="form-label label-required text-rose-600 dark:text-rose-400">
                                <i class="material-icons text-[10px] align-middle mr-1">account_balance_wallet</i>
                                Bayar Pakai Apa? (Kredit)
                            </label>
                            <select name="cash_bank_account_id" class="tom-select" required>
                                <option value="">Pilih Sumber Dana...</option>
                                @foreach($cashAccounts as $coa)
                                    <option value="{{ $coa->account_id }}" {{ old('cash_bank_account_id', $expense->cash_bank_account_id) == $coa->account_id ? 'selected' : '' }}>
                                        {{ $coa->account_number }} - {{ $coa->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label class="form-label label-required">Keterangan / Deskripsi</label>
                        <textarea name="description" class="form-textarea" rows="3" required>{{ old('description', $expense->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                </button>
            </div>

        </form>

        {{-- Hidden Delete Form --}}
        <form id="deleteForm" action="{{ route('admin.expenses.destroy', $expense->expense_id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete() {
        window.confirmDialog({
            title: 'Hapus Pengeluaran?',
            text: "Data ini akan dihapus dan jurnal akuntansi akan dibalik.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
</script>
@endpush