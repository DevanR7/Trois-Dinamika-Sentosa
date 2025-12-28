@extends('admin.layouts.app')

@section('title', 'Edit Transaksi Ekuitas')

@section('content')

    <div class="max-w-3xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Edit Transaksi</h1>
                <p class="page-subtitle">Koreksi data setoran atau prive.</p>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                    <i class="material-icons text-sm mr-1">delete</i> Hapus
                </button>
                <a href="{{ route('admin.equity-transactions.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('admin.equity-transactions.update', $transaction->transaction_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                
                {{-- CARD UTAMA --}}
                <div class="card">
                    <div class="card-header flex justify-between items-center">
                        <h3 class="card-header-title">Formulir Transaksi</h3>
                        <span class="badge badge-primary">ID: EQ-{{ $transaction->transaction_id }}</span>
                    </div>
                    <div class="card-body space-y-6">

                        {{-- Tanggal & Jumlah --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label label-required">Tanggal Transaksi</label>
                                <input type="date" name="transaction_date" class="form-input" 
                                       value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required>
                                @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label label-required">Nominal (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="amount" 
                                           class="form-input autonumeric text-right font-bold text-slate-700" 
                                           value="{{ old('amount', $transaction->amount) }}" required>
                                </div>
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-700"></div>

                        {{-- Konfigurasi Akun --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- Akun Ekuitas --}}
                            <div>
                                <label class="form-label label-required text-indigo-600 dark:text-indigo-400">
                                    Akun Ekuitas (Modal/Prive)
                                </label>
                                <select name="equity_account_id" class="tom-select" required>
                                    @foreach($equityAccounts as $coa)
                                        <option value="{{ $coa->account_id }}" {{ old('equity_account_id', $transaction->equity_account_id) == $coa->account_id ? 'selected' : '' }}>
                                            {{ $coa->account_number }} - {{ $coa->account_name }} ({{ $coa->normal_balance }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-hint mt-2 p-2 bg-slate-50 dark:bg-slate-800 rounded-lg text-xs text-slate-500 leading-relaxed">
                                    <span class="font-bold text-slate-600 dark:text-slate-300">Logika Sistem:</span><br>
                                    • Akun Kredit (Modal) = <b>Setoran</b>.<br>
                                    • Akun Debit (Prive) = <b>Penarikan</b>.
                                </div>
                                @error('equity_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Akun Kas/Bank --}}
                            <div>
                                <label class="form-label label-required text-emerald-600 dark:text-emerald-400">
                                    Akun Kas / Bank (Sumber/Tujuan)
                                </label>
                                <select name="cash_bank_account_id" class="tom-select" required>
                                    @foreach($cashAccounts as $coa)
                                        <option value="{{ $coa->account_id }}" {{ old('cash_bank_account_id', $transaction->cash_bank_account_id) == $coa->account_id ? 'selected' : '' }}>
                                            {{ $coa->account_number }} - {{ $coa->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label class="form-label label-required">Keterangan</label>
                            <textarea name="description" class="form-textarea" rows="3" required>{{ old('description', $transaction->description) }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

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

        <form id="deleteForm" action="{{ route('admin.equity-transactions.destroy', $transaction->transaction_id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete() {
        window.confirmDialog({
            title: 'Hapus Transaksi?',
            text: "Data ini akan dihapus permanen dan jurnal akuntansi akan dibalik.",
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