@extends('admin.layouts.app')

@section('title', 'Buat Rekonsiliasi Baru')

@section('content')
<div class="flex flex-col gap-6 max-w-3xl mx-auto">
    
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.bank-reconciliations.index') }}" class="flex items-center text-sm text-slate-500 hover:text-indigo-600 transition-colors mb-2">
                <i class="material-icons text-[16px] mr-1">arrow_back</i> Kembali
            </a>
            <h1 class="page-title">Mulai Rekonsiliasi</h1>
        </div>
    </div>

    <form action="{{ route('admin.bank-reconciliations.store') }}" method="POST">
        @csrf

        <div class="card p-6">
            <div class="grid grid-cols-1 gap-6">
                
                {{-- Pilih Akun Bank --}}
                <div>
                    <label for="company_bank_account_id" class="form-label label-required">Akun Bank Perusahaan</label>
                    <select id="company_bank_account_id" name="company_bank_account_id" class="tom-select" required>
                        <option value="">Pilih Akun Bank...</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->company_bank_account_id }}">
                                {{ $bank->bank_name }} - {{ $bank->account_number }} ({{ $bank->account->account_name ?? 'No COA' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Pastikan akun bank sudah terhubung dengan Chart of Account (COA).</p>
                    @error('company_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Tanggal Statement --}}
                <div>
                    <label for="statement_date" class="form-label label-required">Tanggal Rekening Koran (Statement Date)</label>
                    <input type="date" id="statement_date" name="statement_date" 
                           value="{{ old('statement_date', date('Y-m-d')) }}" 
                           class="form-input @error('statement_date') is-invalid @enderror" required>
                    <p class="text-[11px] text-slate-400 mt-1">Transaksi sampai tanggal ini akan ditarik untuk dicocokkan.</p>
                    @error('statement_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Saldo Akhir Bank --}}
                <div>
                    <label for="statement_balance" class="form-label label-required">Saldo Akhir di Rekening Koran</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-slate-500 font-bold">Rp</span>
                        <input type="text" class="form-input pl-10 text-right font-mono font-bold autonumeric"
                               name="statement_balance_visual"
                               data-an-synced="true"
                               placeholder="0" required>
                        <input type="hidden" name="statement_balance" value="0">
                    </div>
                    @error('statement_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>

            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('admin.bank-reconciliations.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-2">play_arrow</i> Proses Data
                </button>
            </div>
        </div>
    </form>
</div>
@endsection