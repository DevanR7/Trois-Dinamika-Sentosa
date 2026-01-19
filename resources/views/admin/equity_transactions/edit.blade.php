@extends('admin.layouts.app')

@section('title', 'Edit Transaksi Modal')

@section('content')
<div class="flex flex-col gap-6 max-w-4xl mx-auto" x-data="equityEditForm()">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.equity-transactions.index') }}" class="flex items-center text-sm text-slate-500 hover:text-indigo-600 transition-colors mb-2">
                <i class="material-icons text-[16px] mr-1">arrow_back</i> Kembali
            </a>
            <h1 class="page-title">Edit Transaksi <span class="text-slate-400">#{{ $transaction->transaction_id }}</span></h1>
        </div>
    </div>

    {{-- Alert Warning --}}
    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="material-icons text-amber-400">warning</i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-amber-700">
                    Mengubah transaksi ini akan <strong>merevisi jurnal akuntansi</strong> secara otomatis. Pastikan periode buku belum ditutup.
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.equity-transactions.update', $transaction->transaction_id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Tanggal --}}
                <div class="md:col-span-2">
                    <label for="transaction_date" class="form-label label-required">Tanggal Transaksi</label>
                    <input type="date" id="transaction_date" name="transaction_date" 
                           value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" 
                           class="form-input w-full md:w-1/2 @error('transaction_date') is-invalid @enderror" required>
                    @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Akun Ekuitas --}}
                <div>
                    <label for="equity_account_id" class="form-label label-required">Akun Modal / Ekuitas</label>
                    <select id="equity_account_id" name="equity_account_id" 
                            class="tom-select"
                            x-model="selectedEquityAccount"
                            required>
                        <option value="">Pilih Akun...</option>
                        @foreach($equityAccounts as $acc)
                            <option value="{{ $acc->account_id }}" 
                                    data-balance="{{ $acc->normal_balance }}"
                                    {{ old('equity_account_id', $transaction->equity_account_id) == $acc->account_id ? 'selected' : '' }}>
                                {{ $acc->account_number }} - {{ $acc->account_name }} ({{ $acc->normal_balance }})
                            </option>
                        @endforeach
                    </select>
                    @error('equity_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    
                    {{-- Hint Dinamis --}}
                    <div class="mt-2 text-sm p-3 rounded-lg border transition-all duration-300"
                         x-show="selectedEquityAccount"
                         :class="transactionType === 'investment' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700'">
                        <div class="flex gap-2">
                            <i class="material-icons text-[18px]" x-text="transactionType === 'investment' ? 'arrow_upward' : 'arrow_downward'"></i>
                            <div>
                                <span class="font-bold" x-text="transactionType === 'investment' ? 'Tipe: INVESTASI (Modal Masuk)' : 'Tipe: PRIVE (Penarikan Modal)'"></span>
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
                            <option value="{{ $acc->account_id }}" 
                                {{ old('cash_bank_account_id', $transaction->cash_bank_account_id) == $acc->account_id ? 'selected' : '' }}>
                                {{ $acc->account_number }} - {{ $acc->account_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Nominal --}}
                <div>
                    <label for="amount" class="form-label label-required">Nominal (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-slate-500 font-bold">Rp</span>
                        {{-- Menggunakan class autonumeric agar app.js otomatis menginisialisasi dengan nilai awal --}}
                        <input type="text" class="form-input pl-10 text-right font-mono font-bold autonumeric"
                               name="amount_visual"
                               data-an-synced="true"
                               value="{{ old('amount', $transaction->amount) }}"
                               placeholder="0" required>
                        {{-- Input hidden otomatis digenerate oleh app.js, tapi untuk edit kita perlu set value awal --}}
                        <input type="hidden" name="amount" value="{{ old('amount', $transaction->amount) }}">
                    </div>
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label for="description" class="form-label label-required">Keterangan Transaksi</label>
                    <textarea id="description" name="description" rows="3" 
                              class="form-textarea @error('description') is-invalid @enderror" 
                              required>{{ old('description', $transaction->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('admin.equity-transactions.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-2">save</i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function equityEditForm() {
        return {
            selectedEquityAccount: '{{ old('equity_account_id', $transaction->equity_account_id) }}',
            transactionType: '', 

            init() {
                // Inisialisasi Tom Select dengan listener on change
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
                
                // Deteksi tipe awal saat halaman dimuat
                if (this.selectedEquityAccount) {
                    // Beri sedikit delay agar DOM option sudah siap dibaca
                    setTimeout(() => { this.detectType(); }, 100);
                }
            },

            detectType() {
                const select = document.getElementById('equity_account_id');
                // Cari di dalam instance tomselect atau DOM asli
                const option = select.querySelector(`option[value="${this.selectedEquityAccount}"]`);
                
                if (option) {
                    const normalBalance = option.getAttribute('data-balance');
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