@extends('admin.layouts.app')

@section('title', 'Buat Rekonsiliasi Baru')

@section('content')
    <div class="max-w-2xl mx-auto">
        
        <div class="mb-6">
            <a href="{{ route('admin.bank-reconciliations.index') }}" class="flex items-center gap-2 text-slate-500 hover:text-indigo-600 transition-colors w-fit mb-2">
                <i class="material-icons text-sm">arrow_back</i> Kembali
            </a>
            <h1 class="page-title">Mulai Rekonsiliasi Baru</h1>
            <p class="page-subtitle">Pilih akun bank dan periode laporan rekening koran.</p>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.bank-reconciliations.store') }}" method="POST">
                    @csrf

                    <div class="space-y-5">
                        {{-- 1. Pilih Akun Bank --}}
                        <div>
                            <label class="form-label label-required">Akun Bank Perusahaan</label>
                            <select name="company_bank_account_id" class="tom-select" required>
                                <option value="">Pilih Bank...</option>
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->company_bank_account_id }}">
                                        {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-500 mt-1">Hanya akun bank yang sudah terhubung dengan Chart of Account yang muncul.</p>
                        </div>

                        {{-- 2. Tanggal Laporan (Statement Date) --}}
                        <div>
                            <label class="form-label label-required">Tanggal Akhir Laporan (Statement Date)</label>
                            <input type="date" name="statement_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                            <p class="text-[10px] text-slate-500 mt-1">Masukkan tanggal akhir periode rekening koran (misal: 31 Jan 2025).</p>
                        </div>

                        {{-- 3. Saldo Akhir Laporan (Target) --}}
                        <div>
                            <label class="form-label label-required">Saldo Akhir di Rekening Koran (Statement Balance)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">Rp</div>
                                <input type="text" class="form-input pl-10 text-lg font-bold text-indigo-700 autonumeric" 
                                       name="statement_balance" placeholder="0" required data-an-synced="true">
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1">Masukkan saldo akhir yang tertera pada cetakan/PDF rekening koran bank.</p>
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons text-sm mr-2">play_arrow</i>
                                Mulai Proses
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection