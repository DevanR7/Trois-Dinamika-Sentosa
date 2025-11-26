@extends('layouts.app')

@section('title', 'Mulai Rekonsiliasi Baru')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-2xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('bank-reconciliations.index') }}" class="hover:text-indigo-600 transition-colors">Rekonsiliasi</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Mulai Rekonsiliasi Baru</h1>
        </div>
        <a href="{{ route('bank-reconciliations.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        
        {{-- Petunjuk --}}
        <div class="p-6 border-b border-slate-100 bg-blue-50/50 flex items-start gap-3">
            <i class="material-icons text-blue-600 text-xl mt-0.5">info</i>
            <div class="text-sm text-blue-800">
                <p class="font-bold mb-1">Petunjuk:</p>
                <p class="text-xs leading-relaxed opacity-80">Siapkan <strong>Rekening Koran (Bank Statement)</strong> Anda. Masukkan saldo akhir yang tertera di dokumen tersebut ke form di bawah ini untuk memulai pencocokan.</p>
            </div>
        </div>

        <div class="p-6 md:p-8">
            <form action="{{ route('bank-reconciliations.store') }}" method="POST" id="recon-create-form">
                @csrf
                
                @if ($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <ul class="list-disc list-inside text-xs text-red-600">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="space-y-6">
                    {{-- Pilih Akun --}}
                    <div>
                        <label for="company_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pilih Akun Bank <span class="text-red-500">*</span></label>
                        <select class="form-input select2-basic" id="company_bank_account_id" name="company_bank_account_id" required>
                            <option value="" selected></option>
                            @forelse ($bankAccounts as $account)
                                <option value="{{ $account->company_bank_account_id }}" @selected(old('company_bank_account_id') == $account->company_bank_account_id)>
                                    {{ $account->account->account_number ?? '' }} - {{ $account->account->account_name ?? $account->bank_name }}
                                </option>
                            @empty
                                <option value="" disabled>Belum ada akun bank terdaftar</option>
                            @endforelse
                        </select>
                        @error('company_bank_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Tanggal Laporan --}}
                        <div>
                            <label for="statement_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Akhir Statement <span class="text-red-500">*</span></label>
                            <input type="date" name="statement_date" id="statement_date" value="{{ old('statement_date', now()->endOfMonth()->toDateString()) }}" class="form-input" required>
                            <p class="mt-1.5 text-[11px] text-slate-400">Tanggal pisah batas (Cut-off).</p>
                            @error('statement_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Saldo Akhir --}}
                        <div>
                            <label for="statement_balance_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Saldo Akhir (di Bank) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-slate-400 text-sm font-bold">Rp</span>
                                </div>
                                <input type="text" id="statement_balance_display" class="form-input pl-10 text-right font-bold text-indigo-600 font-mono text-lg" placeholder="0" required>
                                <input type="hidden" name="statement_balance" id="statement_balance" value="{{ old('statement_balance') }}">
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-400">Nominal saldo akhir di PDF bank.</p>
                            @error('statement_balance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <a href="{{ route('bank-reconciliations.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-600 text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                        Batal
                    </a>
                    <button type="submit" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed group hover:-translate-y-0.5" {{ $bankAccounts->isEmpty() ? 'disabled' : '' }}>
                        <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">play_circle</i> Mulai Proses
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Init Select2
        $('.select2-basic').select2({ placeholder: '-- Pilih Akun --', allowClear: true, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // Init AutoNumeric (Manual karena id khusus)
        const displayInput = document.getElementById('statement_balance_display');
        const hiddenInput = document.getElementById('statement_balance');
        
        if(displayInput) {
            const anElement = new AutoNumeric(displayInput, {
                decimalPlaces: 0,
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                minimumValue: '-9999999999999', 
            });

            if(hiddenInput.value) anElement.set(hiddenInput.value);

            displayInput.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenInput.value = e.detail.newRawValue;
            });
            
            $('#recon-create-form').on('submit', function() {
                hiddenInput.value = anElement.getNumber();
            });
        }

        // Validasi Submit
        $('#recon-create-form').on('submit', function(e) {
            if (!hiddenInput.value || hiddenInput.value.trim() === '') {
                e.preventDefault();
                Swal.fire({
                    title: 'Input Diperlukan',
                    text: 'Saldo Akhir (di Bank) wajib diisi.',
                    icon: 'warning',
                    confirmButtonColor: '#6366f1',
                    customClass: { popup: 'colored-toast rounded-xl' }
                });
            }
        });
        
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush