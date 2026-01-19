@extends('admin.layouts.app')

@section('title', 'Buat Jurnal Manual')

@section('content')
<div class="flex flex-col gap-6" x-data="manualJournalForm()">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.manual-journals.index') }}" class="flex items-center text-sm text-slate-500 hover:text-indigo-600 transition-colors mb-2">
                <i class="material-icons text-[16px] mr-1">arrow_back</i> Kembali ke Daftar
            </a>
            <h1 class="page-title">Buat Jurnal Manual</h1>
        </div>
    </div>

    <form action="{{ route('admin.manual-journals.store') }}" method="POST" id="journalForm">
        @csrf

        {{-- 1. Informasi Dasar --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-header-title">Informasi Jurnal</h3>
            </div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Tanggal --}}
                <div>
                    <label for="entry_date" class="form-label label-required">Tanggal Transaksi</label>
                    <input type="date" id="entry_date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" 
                           class="form-input @error('entry_date') is-invalid @enderror" required>
                    @error('entry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Deskripsi Utama --}}
                <div>
                    <label for="description" class="form-label label-required">Deskripsi / Keterangan</label>
                    <input type="text" id="description" name="description" value="{{ old('description') }}" 
                           class="form-input @error('description') is-invalid @enderror" 
                           placeholder="Contoh: Penyesuaian stok opname bulan Januari" required>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- 2. Entri Jurnal (Dynamic Rows) --}}
        <div class="card mb-6 overflow-visible"> 
            <div class="card-header flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <h3 class="card-header-title">Detail Entri Akun</h3>
                <button type="button" @click="addRow()" class="btn btn-sm btn-secondary">
                    <i class="material-icons text-[16px] mr-1">add</i> Tambah Baris
                </button>
            </div>
            
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 text-xs uppercase text-slate-500 font-bold">
                        <tr>
                            <th class="px-4 py-3 w-[30%] min-w-[250px]">Akun COA <span class="text-red-500">*</span></th>
                            <th class="px-4 py-3 w-[25%] min-w-[200px]">Deskripsi Baris (Opsional)</th>
                            <th class="px-4 py-3 w-[20%] min-w-[150px] text-right">Debit (Rp)</th>
                            <th class="px-4 py-3 w-[20%] min-w-[150px] text-right">Kredit (Rp)</th>
                            <th class="px-4 py-3 w-[5%] text-center">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors" :id="'row-' + index">
                                {{-- Akun COA (Tom Select) --}}
                                <td class="px-4 py-3 align-top">
                                    <select :name="`entries[${index}][account_id]`" 
                                            class="tom-select w-full"
                                            x-init="initTomSelect($el)"
                                            required>
                                        <option value="">Pilih Akun...</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->account_id }}" data-type="{{ $acc->account_type }}">
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Deskripsi Baris --}}
                                <td class="px-4 py-3 align-top">
                                    <input type="text" :name="`entries[${index}][description]`" 
                                           class="form-input h-[42px]" 
                                           placeholder="Ket. tambahan...">
                                </td>

                                {{-- Debit --}}
                                <td class="px-4 py-3 align-top">
                                    <input type="text" class="form-input text-right font-mono autonumeric"
                                           :name="`entries[${index}][debit_visual]`"
                                           x-model="row.debit_display"
                                           @input="updateAmount(index, 'debit', $event.target.value)"
                                           x-init="initAutoNumeric($el, index, 'debit')"
                                           placeholder="0">
                                    <input type="hidden" :name="`entries[${index}][debit]`" :value="row.debit">
                                </td>

                                {{-- Kredit --}}
                                <td class="px-4 py-3 align-top">
                                    <input type="text" class="form-input text-right font-mono autonumeric"
                                           :name="`entries[${index}][credit_visual]`"
                                           x-model="row.credit_display"
                                           @input="updateAmount(index, 'credit', $event.target.value)"
                                           x-init="initAutoNumeric($el, index, 'credit')"
                                           placeholder="0">
                                    <input type="hidden" :name="`entries[${index}][credit]`" :value="row.credit">
                                </td>

                                {{-- Hapus --}}
                                <td class="px-4 py-3 align-top text-center">
                                    <button type="button" @click="removeRow(index)" 
                                            class="text-slate-400 hover:text-rose-500 transition-colors p-2 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                            :disabled="rows.length <= 2"
                                            title="Hapus Baris">
                                        <i class="material-icons text-[20px]">delete_outline</i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800/50 border-t-2 border-slate-200 dark:border-slate-700 font-bold text-sm">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right uppercase tracking-wider text-slate-500">Total</td>
                            <td class="px-4 py-3 text-right font-mono" 
                                :class="{'text-emerald-600': isBalanced, 'text-slate-700 dark:text-slate-200': !isBalanced}">
                                <span x-text="formatCurrency(totalDebit)"></span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono" 
                                :class="{'text-emerald-600': isBalanced, 'text-slate-700 dark:text-slate-200': !isBalanced}">
                                <span x-text="formatCurrency(totalCredit)"></span>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-right text-xs uppercase tracking-wider text-slate-400 border-t border-slate-200 dark:border-slate-700">Selisih (Balance)</td>
                            <td colspan="2" class="px-4 py-2 text-center border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-center gap-2 px-3 py-1 rounded-full text-xs font-bold transition-all duration-300"
                                     :class="isBalanced ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'">
                                    <i class="material-icons text-[14px]" x-text="isBalanced ? 'check_circle' : 'warning'"></i>
                                    <span x-text="isBalanced ? 'SEIMBANG (BALANCED)' : 'TIDAK SEIMBANG (' + formatCurrency(Math.abs(difference)) + ')'"></span>
                                </div>
                            </td>
                            <td class="border-t border-slate-200 dark:border-slate-700"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="flex items-center justify-end gap-3 mt-8">
            <a href="{{ route('admin.manual-journals.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="!isBalanced || totalDebit == 0">
                <i class="material-icons text-[18px] mr-2">save</i> Simpan Jurnal
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function manualJournalForm() {
        return {
            rows: [
                { id: 1, debit: 0, credit: 0, debit_display: '', credit_display: '' },
                { id: 2, debit: 0, credit: 0, debit_display: '', credit_display: '' }
            ],
            
            get totalDebit() {
                return this.rows.reduce((sum, row) => sum + parseFloat(row.debit || 0), 0);
            },
            
            get totalCredit() {
                return this.rows.reduce((sum, row) => sum + parseFloat(row.credit || 0), 0);
            },
            
            get difference() {
                return this.totalDebit - this.totalCredit;
            },
            
            get isBalanced() {
                // Toleransi koma kecil
                return Math.abs(this.difference) < 0.01 && this.totalDebit > 0;
            },

            addRow() {
                this.rows.push({
                    id: Date.now(), 
                    debit: 0, 
                    credit: 0, 
                    debit_display: '', 
                    credit_display: '' 
                });
            },

            removeRow(index) {
                if (this.rows.length > 2) {
                    this.rows.splice(index, 1);
                } else {
                    showToast('Minimal harus ada 2 baris jurnal (Debit & Kredit)', 'error');
                }
            },

            // Init Tom Select untuk baris dinamis
            initTomSelect(element) {
                if (element.tomselect) return; // Prevent double init
                new TomSelect(element, window.defaultTomSelectConfig);
            },

            // Init AutoNumeric & Handle Value Sync
            initAutoNumeric(element, index, field) {
                if (AutoNumeric.getAutoNumericElement(element)) return;

                const an = new AutoNumeric(element, window.defaultAutoNumericOptions);
                
                // Listener saat user mengetik
                element.addEventListener('autoNumeric:rawValueModified', e => {
                    const val = e.detail.newRawValue;
                    this.updateAmount(index, field, val);
                    
                    // Logic: Jika isi Debit, kosongkan Kredit (dan sebaliknya) di baris yg sama
                    if (field === 'debit' && val > 0) {
                        this.rows[index].credit = 0;
                        this.rows[index].credit_display = '';
                        AutoNumeric.getAutoNumericElement(element.closest('tr').querySelectorAll('.autonumeric')[1]).set('');
                    } else if (field === 'credit' && val > 0) {
                        this.rows[index].debit = 0;
                        this.rows[index].debit_display = '';
                        AutoNumeric.getAutoNumericElement(element.closest('tr').querySelectorAll('.autonumeric')[0]).set('');
                    }
                });
            },

            updateAmount(index, field, value) {
                this.rows[index][field] = value ? parseFloat(value) : 0;
            },

            formatCurrency(value) {
                return new Intl.NumberFormat('id-ID', { 
                    style: 'currency', 
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2 
                }).format(value);
            }
        }
    }
</script>
@endpush
@endsection