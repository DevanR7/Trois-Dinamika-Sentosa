@extends('layouts.app')

@section('title', 'Mulai Rekonsiliasi Baru')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('bank-reconciliations.index') }}" class="hover:text-indigo-600 transition">Rekonsiliasi</a>
                <span>/</span>
                <span class="text-gray-800">Baru</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Mulai Rekonsiliasi</h2>
            <p class="text-sm text-gray-500 mt-1">Cocokkan saldo sistem dengan rekening koran bank.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('bank-reconciliations.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <div class="p-6 border-b border-gray-200 bg-blue-50 flex items-start gap-3">
            <i class="material-icons text-blue-500 text-xl">info</i>
            <div class="text-sm text-blue-800">
                <p class="font-bold">Petunjuk:</p>
                <p>Siapkan <strong>Rekening Koran (Bank Statement)</strong> Anda. Masukkan saldo akhir yang tertera di dokumen tersebut ke form di bawah ini.</p>
            </div>
        </div>

        <div class="p-6">
            <form action="{{ route('bank-reconciliations.store') }}" method="POST" id="recon-create-form">
                @csrf
                
                <div class="space-y-6">
                    {{-- Pilih Akun --}}
                    <div>
                        <label for="company_bank_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Akun Bank <span class="text-red-500">*</span></label>
                        <select class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="company_bank_account_id" name="company_bank_account_id" required>
                            <option value="" disabled selected></option>
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
                            <label for="statement_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Akhir Statement <span class="text-red-500">*</span></label>
                            <input type="date" name="statement_date" id="statement_date" value="{{ old('statement_date', now()->endOfMonth()->toDateString()) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            <p class="mt-1 text-xs text-gray-500">Tanggal pisah batas (Cut-off).</p>
                            @error('statement_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Saldo Akhir --}}
                        <div>
                            <label for="statement_balance_display" class="block text-xs font-bold text-gray-500 uppercase mb-1">Saldo Akhir (di Bank) <span class="text-red-500">*</span></label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="statement_balance_display" class="form-input block w-full rounded-lg border-gray-300 pl-10 text-right font-bold text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="0" required>
                                <input type="hidden" name="statement_balance" id="statement_balance" value="{{ old('statement_balance') }}">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Nominal saldo akhir di PDF bank.</p>
                            @error('statement_balance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-gray-100 flex justify-end gap-3">
                    <a href="{{ route('bank-reconciliations.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed" {{ $bankAccounts->isEmpty() ? 'disabled' : '' }}>
                        <i class="material-icons text-lg mr-2">play_circle</i> Mulai Proses
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Init Select2
        $('.select2').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Akun --', width: '100%' });

        // Init AutoNumeric
        const displayInput = document.getElementById('statement_balance_display');
        const hiddenInput = document.getElementById('statement_balance');
        
        if(displayInput) {
            const anElement = new AutoNumeric(displayInput, {
                decimalPlaces: 0,
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                minimumValue: '-9999999999999', // Allow negative balance
            });

            if(hiddenInput.value) anElement.set(hiddenInput.value);

            displayInput.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenInput.value = e.detail.newRawValue;
            });
        }

        // Validasi Submit
        $('#recon-create-form').on('submit', function(e) {
            if (!hiddenInput.value || hiddenInput.value === '') {
                e.preventDefault();
                Swal.fire('Error', 'Saldo Akhir tidak boleh kosong!', 'error');
            }
        });
    });
</script>
@endpush