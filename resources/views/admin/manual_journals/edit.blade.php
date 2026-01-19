@extends('admin.layouts.app')

@section('title', 'Edit Jurnal Manual #' . $manualJournal->journal_number)

@section('content')
<div class="flex flex-col gap-6" x-data="manualJournalEditForm()">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.manual-journals.index') }}" class="flex items-center text-sm text-slate-500 hover:text-indigo-600 transition-colors mb-2">
                <i class="material-icons text-[16px] mr-1">arrow_back</i> Kembali ke Daftar
            </a>
            <h1 class="page-title">Edit Jurnal <span class="text-slate-400">#{{ $manualJournal->journal_number }}</span></h1>
        </div>
    </div>

    {{-- Alert Warning --}}
    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="material-icons text-amber-400">info</i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-amber-700">
                    Mengedit jurnal akan menghapus entri buku besar lama dan membuat entri baru. Pastikan periode akuntansi belum ditutup.
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.manual-journals.update', $manualJournal->journal_id) }}" method="POST" id="journalForm">
        @csrf
        @method('PUT')

        {{-- 1. Informasi Dasar --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-header-title">Informasi Jurnal</h3>
            </div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="entry_date" class="form-label label-required">Tanggal Transaksi</label>
                    <input type="date" id="entry_date" name="entry_date" 
                           value="{{ old('entry_date', $manualJournal->entry_date->format('Y-m-d')) }}" 
                           class="form-input @error('entry_date') is-invalid @enderror" required>
                    @error('entry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="description" class="form-label label-required">Deskripsi / Keterangan</label>
                    <input type="text" id="description" name="description" 
                           value="{{ old('description', $manualJournal->description) }}" 
                           class="form-input @error('description') is-invalid @enderror" required>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- 2. Entri Jurnal --}}
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
                            <th class="px-4 py-3 w-[25%] min-w-[200px]">Deskripsi Baris</th>
                            <th class="px-4 py-3 w-[20%] min-w-[150px] text-right">Debit (Rp)</th>
                            <th class="px-4 py-3 w-[20%] min-w-[150px] text-right">Kredit (Rp)</th>
                            <th class="px-4 py-3 w-[5%] text-center">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                                {{-- Akun COA --}}
                                <td class="px-4 py-3 align-top">
                                    <select :name="`entries[${index}][account_id]`" 
                                            class="tom-select w-full"
                                            x-model="row.account_id"
                                            x-init="initTomSelect($el, row.account_id)"
                                            required>
                                        <option value="">Pilih Akun...</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->account_id }}">{{ $acc->account_number }} - {{ $acc->account_name }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Deskripsi Baris --}}
                                <td class="px-4 py-3 align-top">
                                    <input type="text" :name="`entries[${index}][description]`" 
                                           x-model="row.description"
                                           class="form-input h-[42px]">
                                </td>

                                {{-- Debit --}}
                                <td class="px-4 py-3 align-top">
                                    <input type="text" class="form-input text-right font-mono autonumeric"
                                           :name="`entries[${index}][debit_visual]`"
                                           x-model="row.debit_display"
                                           @input="updateAmount(index, 'debit', $event.target.value)"
                                           x-init="initAutoNumeric($el, index, 'debit', row.debit)"
                                           placeholder="0">
                                    <input type="hidden" :name="`entries[${index}][debit]`" :value="row.debit">
                                </td>

                                {{-- Kredit --}}
                                <td class="px-4 py-3 align-top">
                                    <input type="text" class="form-input text-right font-mono autonumeric"
                                           :name="`entries[${index}][credit_visual]`"
                                           x-model="row.credit_display"
                                           @input="updateAmount(index, 'credit', $event.target.value)"
                                           x-init="initAutoNumeric($el, index, 'credit', row.credit)"
                                           placeholder="0">
                                    <input type="hidden" :name="`entries[${index}][credit]`" :value="row.credit">
                                </td>

                                <td class="px-4 py-3 align-top text-center">
                                    <button type="button" @click="removeRow(index)" 
                                            class="text-slate-400 hover:text-rose-500 p-2 rounded-lg hover:bg-rose-50"
                                            :disabled="rows.length <= 2">
                                        <i class="material-icons text-[20px]">delete_outline</i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800/50 border-t-2 border-slate-200 dark:border-slate-700 font-bold text-sm">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right uppercase text-slate-500">Total</td>
                            <td class="px-4 py-3 text-right font-mono" :class="isBalanced ? 'text-emerald-600' : 'text-slate-700'">
                                <span x-text="formatCurrency(totalDebit)"></span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono" :class="isBalanced ? 'text-emerald-600' : 'text-slate-700'">
                                <span x-text="formatCurrency(totalCredit)"></span>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-right text-xs uppercase text-slate-400">Status</td>
                            <td colspan="2" class="px-4 py-2 text-center">
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold"
                                     :class="isBalanced ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                    <i class="material-icons text-[14px]" x-text="isBalanced ? 'check_circle' : 'warning'"></i>
                                    <span x-text="isBalanced ? 'SEIMBANG' : 'SELISIH: ' + formatCurrency(Math.abs(difference))"></span>
                                </div>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 mt-8">
            <a href="{{ route('admin.manual-journals.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="!isBalanced || totalDebit == 0">
                <i class="material-icons text-[18px] mr-2">save</i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

@push('scripts')
{{-- 
    PERBAIKAN: 
    Kita memproses data PHP di luar script JS untuk menghindari error parsing Blade 
    pada karakter kurung siku '[' di dalam closure function.
--}}
@php
    $formattedRows = $manualJournal->entries->map(function($entry) {
        return [
            'id' => $entry->entry_id,
            'account_id' => $entry->chart_of_account_id,
            'description' => $entry->description,
            'debit' => (float)$entry->debit,
            'credit' => (float)$entry->credit,
            'debit_display' => '', // Nanti diisi otomatis oleh AutoNumeric saat init
            'credit_display' => '' // Nanti diisi otomatis oleh AutoNumeric saat init
        ];
    });
@endphp

<script>
    function manualJournalEditForm() {
        return {
            // Load data yang sudah diformat di blok @php di atas
            rows: @json($formattedRows),
            
            get totalDebit() {
                return this.rows.reduce((sum, row) => sum + parseFloat(row.debit || 0), 0);
            },
            
            get totalCredit() {
                return this.rows.reduce((sum, row) => sum + parseFloat(row.credit || 0), 0);
            },
            
            get difference() { return this.totalDebit - this.totalCredit; },
            get isBalanced() { return Math.abs(this.difference) < 0.01 && this.totalDebit > 0; },

            addRow() {
                this.rows.push({ 
                    id: Date.now(), 
                    account_id: '', 
                    description: '', 
                    debit: 0, 
                    credit: 0, 
                    debit_display: '', 
                    credit_display: '' 
                });
            },

            removeRow(index) {
                if (this.rows.length > 2) this.rows.splice(index, 1);
                else showToast('Minimal 2 baris.', 'error');
            },

            initTomSelect(element, value) {
                if (element.tomselect) return;
                
                // Clone config agar tidak merusak referensi global
                let config = { ...window.defaultTomSelectConfig };
                
                let ts = new TomSelect(element, config);
                if(value) ts.setValue(value);
            },

            initAutoNumeric(element, index, field, initialValue = 0) {
                if (AutoNumeric.getAutoNumericElement(element)) return;
                
                const an = new AutoNumeric(element, window.defaultAutoNumericOptions);
                if (initialValue) an.set(initialValue);
                
                element.addEventListener('autoNumeric:rawValueModified', e => {
                    const val = e.detail.newRawValue;
                    this.updateAmount(index, field, val);
                    
                    // Logic Mutual Exclusion (Debit vs Kredit)
                    if (field === 'debit' && val > 0) {
                        this.rows[index].credit = 0;
                        // Reset visual input kredit di baris yang sama
                        let creditInput = element.closest('tr').querySelectorAll('.autonumeric')[1];
                        if(creditInput && AutoNumeric.getAutoNumericElement(creditInput)) {
                            AutoNumeric.getAutoNumericElement(creditInput).set(0);
                        }
                    } else if (field === 'credit' && val > 0) {
                        this.rows[index].debit = 0;
                        // Reset visual input debit di baris yang sama
                        let debitInput = element.closest('tr').querySelectorAll('.autonumeric')[0];
                        if(debitInput && AutoNumeric.getAutoNumericElement(debitInput)) {
                            AutoNumeric.getAutoNumericElement(debitInput).set(0);
                        }
                    }
                });
            },

            updateAmount(index, field, value) {
                this.rows[index][field] = value ? parseFloat(value) : 0;
            },

            formatCurrency(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
            }
        }
    }
</script>
@endpush
@endsection