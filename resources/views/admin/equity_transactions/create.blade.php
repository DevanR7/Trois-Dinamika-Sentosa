@extends('admin.layouts.app')

@section('title', 'Input Transaksi Modal')

@section('content')
<div class="flex flex-col gap-6 max-w-4xl mx-auto" x-data="equityForm()">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.equity-transactions.index') }}" class="flex items-center text-sm text-slate-500 hover:text-indigo-600 transition-colors mb-2">
                <i class="material-icons text-[16px] mr-1">arrow_back</i> Kembali
            </a>
            <h1 class="page-title">Input Transaksi Modal</h1>
        </div>
    </div>

    <form action="{{ route('admin.equity-transactions.store') }}" method="POST">
        @csrf

        <div class="card p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Tanggal --}}
                <div class="md:col-span-2">
                    <label for="transaction_date" class="form-label label-required">Tanggal Transaksi</label>
                    <input type="date" id="transaction_date" name="transaction_date" 
                           value="{{ old('transaction_date', date('Y-m-d')) }}" 
                           class="form-input w-full md:w-1/2 @error('transaction_date') is-invalid @enderror" required>
                    @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Akun Ekuitas (PENTING) --}}
                <div>
                    <label for="equity_account_id" class="form-label label-required">Akun Modal / Ekuitas</label>
                    <select id="equity_account_id" name="equity_account_id" 
                            class="tom-select"
                            x-model="selectedEquityAccount"
                            @change="detectType()"
                            required>
                        <option value="">Pilih Akun...</option>
                        @foreach($equityAccounts as $acc)
                            {{-- Kita simpan Normal Balance di data attribute untuk logika JS --}}
                            <option value="{{ $acc->account_id }}" data-balance="{{ $acc->normal_balance }}">
                                {{ $acc->account_number }} - {{ $acc->account_name }} ({{ $acc->normal_balance }})
                            </option>
                        @endforeach
                    </select>
                    @error('equity_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    
                    {{-- Hint Dinamis --}}
                    <div class="mt-2 text-sm p-3 rounded-lg border transition-all duration-300"
                         x-show="selectedEquityAccount"
                         :class="transactionType === 'investment' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700'"
                         style="display: none;">
                        <div class="flex gap-2">
                            <i class="material-icons text-[18px]" x-text="transactionType === 'investment' ? 'arrow_upward' : 'arrow_downward'"></i>
                            <div>
                                <span class="font-bold" x-text="transactionType === 'investment' ? 'Tipe: INVESTASI (Modal Masuk)' : 'Tipe: PRIVE (Penarikan Modal)'"></span>
                                <p class="text-xs opacity-80 mt-0.5">
                                    Sistem mendeteksi tipe berdasarkan posisi normal akun yang dipilih.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Akun Kas/Bank --}}
                <div>
                    <label for="cash_bank_account_id" class="form-label label-required">Sumber/Tujuan Dana (Kas/Bank)</label>
                    <select id="cash_bank_account_id" name="cash_bank_account_id" class="tom-select" required>
                        <option value="">Pilih Akun Aset...</option>
                        @foreach($cashAccounts as $acc)
                            <option value="{{ $acc->account_id }}" {{ old('cash_bank_account_id') == $acc->account_id ? 'selected' : '' }}>
                                {{ $acc->account_number }} - {{ $acc->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Pilih akun Harta Lancar (Kas/Bank) yang terpengaruh.</p>
                    @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Nominal --}}
                <div>
                    <label for="amount" class="form-label label-required">Nominal (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-slate-500 font-bold">Rp</span>
                        <input type="text" class="form-input pl-10 text-right font-mono font-bold autonumeric"
                               name="amount_visual"
                               data-an-synced="true"
                               placeholder="0" required>
                        <input type="hidden" name="amount" value="0">
                    </div>
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label for="description" class="form-label label-required">Keterangan Transaksi</label>
                    <textarea id="description" name="description" rows="3" 
                              class="form-textarea @error('description') is-invalid @enderror" 
                              placeholder="Contoh: Setoran modal awal Tuan A..." required>{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('admin.equity-transactions.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-2">save</i> Simpan Transaksi
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function equityForm() {
        return {
            selectedEquityAccount: '{{ old('equity_account_id') }}',
            transactionType: '', // 'investment' or 'drawing'

            init() {
                // Init Tom Select
                const selectEl = document.getElementById('equity_account_id');
                if (selectEl && !selectEl.tomselect) {
                    new TomSelect(selectEl, {
                        ...window.defaultTomSelectConfig,
                        onChange: (value) => {
                            this.selectedEquityAccount = value;
                            this.detectType();
                        }
                    });
                }
                
                // Jalankan deteksi jika ada old value
                if (this.selectedEquityAccount) {
                    this.detectType();
                }
            },

            detectType() {
                // Cari option yang dipilih untuk mengambil data-balance
                // Karena Tom Select menyembunyikan select asli, kita cari di options select asli
                const select = document.getElementById('equity_account_id');
                const option = select.querySelector(`option[value="${this.selectedEquityAccount}"]`);
                
                if (option) {
                    const normalBalance = option.getAttribute('data-balance');
                    // Logic Controller: Kredit = Investment, Debit = Drawing
                    if (normalBalance === 'Kredit') {
                        this.transactionType = 'investment';
                    } else {
                        this.transactionType = 'drawing';
                    }
                }
            }
        }
    }
</script>
@endpush
@endsection