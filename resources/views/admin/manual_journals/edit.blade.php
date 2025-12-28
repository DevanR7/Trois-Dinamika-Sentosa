@extends('admin.layouts.app')

@section('title', 'Edit Jurnal Manual')

@section('content')

    <div class="max-w-6xl mx-auto">
        
        <form action="{{ route('admin.manual-journals.update', $manualJournal->journal_id) }}" method="POST" id="journalForm">
            @csrf
            @method('PUT')

            {{-- HEADER NAVIGATION --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="page-title">Edit Jurnal: {{ $manualJournal->journal_number }}</h1>
                    <p class="page-subtitle">Koreksi entri jurnal umum.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                        <i class="material-icons text-sm mr-1">delete</i> Hapus
                    </button>
                    <a href="{{ route('admin.manual-journals.index') }}" class="btn btn-secondary">
                        <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                    </a>
                    <button type="submit" id="btnSave" class="btn btn-primary" disabled>
                        <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                    </button>
                </div>
            </div>

            {{-- FORM HEADER --}}
            <div class="card mb-6">
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div>
                            <label class="form-label label-required">Tanggal Transaksi</label>
                            <input type="date" name="entry_date" class="form-input" 
                                   value="{{ old('entry_date', $manualJournal->entry_date->format('Y-m-d')) }}" required>
                            @error('entry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label label-required">Deskripsi / Memo</label>
                            <input type="text" name="description" class="form-input" 
                                   value="{{ old('description', $manualJournal->description) }}" required>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- FORM ENTRIES --}}
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <h3 class="card-header-title">Rincian Jurnal (Debit / Kredit)</h3>
                    <button type="button" id="btnAddRow" class="btn btn-sm btn-secondary text-indigo-600 bg-indigo-50 border-indigo-200 hover:bg-indigo-100">
                        <i class="material-icons text-sm mr-1">add</i> Tambah Baris
                    </button>
                </div>
                
                <div class="table-container overflow-visible">
                    <table class="table-modern w-full" id="entriesTable">
                        <thead>
                            <tr>
                                <th class="w-[30%]">Akun (COA)</th>
                                <th class="w-[25%]">Keterangan Baris (Opsional)</th>
                                <th class="w-[20%] text-right">Debit</th>
                                <th class="w-[20%] text-right">Kredit</th>
                                <th class="w-[5%] text-center"><i class="material-icons text-sm">delete</i></th>
                            </tr>
                        </thead>
                        <tbody id="entriesBody">
                            {{-- Rows populated by JS --}}
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-800 font-bold border-t border-slate-200 dark:border-slate-700">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-right uppercase text-xs tracking-wider text-slate-500">Total</td>
                                <td class="px-6 py-4 text-right">
                                    <span id="totalDebitDisplay" class="text-slate-700 dark:text-slate-200">0</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span id="totalCreditDisplay" class="text-slate-700 dark:text-slate-200">0</span>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="px-6 py-2 text-right uppercase text-xs tracking-wider text-slate-500">Balance (Selisih)</td>
                                <td colspan="2" class="px-6 py-2 text-center">
                                    <span id="balanceDisplay" class="badge badge-success w-full justify-center">Seimbang</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </form>

        <form id="deleteForm" action="{{ route('admin.manual-journals.destroy', $manualJournal->journal_id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const entriesBody = document.getElementById('entriesBody');
        const btnAddRow = document.getElementById('btnAddRow');
        const btnSave = document.getElementById('btnSave');
        
        const accountsData = @json($accounts); 
        // Data lama (existing entries) dari Controller
        const existingEntries = @json($manualJournal->entries);
        
        let rowCount = 0;

        function addRow(data = null) {
            rowCount++;
            
            // Siapkan nilai default jika data ada (mode edit)
            const selectedAccount = data ? data.chart_of_account_id : '';
            const descValue = data ? (data.description || '') : '';
            const debitValue = data ? data.debit : 0;
            const creditValue = data ? data.credit : 0;

            let optionsHtml = '<option value="">Pilih Akun...</option>';
            accountsData.forEach(acc => {
                const selected = acc.account_id == selectedAccount ? 'selected' : '';
                optionsHtml += `<option value="${acc.account_id}" ${selected}>${acc.account_number} - ${acc.account_name}</option>`;
            });

            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 dark:border-slate-700';
            tr.innerHTML = `
                <td class="p-2 align-top">
                    <select name="entries[${rowCount}][account_id]" class="tom-select-dynamic" required>
                        ${optionsHtml}
                    </select>
                </td>
                <td class="p-2 align-top">
                    <input type="text" name="entries[${rowCount}][description]" class="form-input text-sm" value="${descValue}" placeholder="Ket. khusus baris ini">
                </td>
                <td class="p-2 align-top">
                    <div class="input-group">
                        <span class="input-group-text px-2 text-xs">Rp</span>
                        <input type="text" name="entries[${rowCount}][debit]" class="form-input text-right autonumeric debit-input" value="${debitValue}">
                    </div>
                </td>
                <td class="p-2 align-top">
                    <div class="input-group">
                        <span class="input-group-text px-2 text-xs">Rp</span>
                        <input type="text" name="entries[${rowCount}][credit]" class="form-input text-right autonumeric credit-input" value="${creditValue}">
                    </div>
                </td>
                <td class="p-2 align-top text-center">
                    <button type="button" class="text-slate-400 hover:text-rose-500 transition-colors btn-remove-row">
                        <i class="material-icons text-lg">close</i>
                    </button>
                </td>
            `;
            
            entriesBody.appendChild(tr);

            // Init Plugins
            new TomSelect(tr.querySelector('.tom-select-dynamic'), {
                sortField: { field: "text", direction: "asc" },
                plugins: ['clear_button'],
                dropdownParent: 'body'
            });

            const anOptions = window.defaultAutoNumericOptions;
            new AutoNumeric(tr.querySelector('.debit-input'), anOptions);
            new AutoNumeric(tr.querySelector('.credit-input'), anOptions);
        }

        // --- Event Listener ---
        entriesBody.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove-row')) {
                e.target.closest('tr').remove();
                calculateTotals();
            }
        });

        entriesBody.addEventListener('autoNumeric:rawValueModified', function() {
            calculateTotals();
        });

        // --- Kalkulasi Total (Sama seperti create) ---
        function calculateTotals() {
            let totalDebit = 0;
            let totalCredit = 0;

            document.querySelectorAll('.debit-input').forEach(el => {
                totalDebit += parseFloat(AutoNumeric.getAutoNumericElement(el).getNumericString() || 0);
            });

            document.querySelectorAll('.credit-input').forEach(el => {
                totalCredit += parseFloat(AutoNumeric.getAutoNumericElement(el).getNumericString() || 0);
            });

            document.getElementById('totalDebitDisplay').innerText = 'Rp ' + totalDebit.toLocaleString('id-ID');
            document.getElementById('totalCreditDisplay').innerText = 'Rp ' + totalCredit.toLocaleString('id-ID');

            const diff = Math.abs(totalDebit - totalCredit);
            const balanceBadge = document.getElementById('balanceDisplay');

            if (diff < 1 && totalDebit > 0) {
                balanceBadge.className = 'badge badge-success w-full justify-center py-1';
                balanceBadge.innerText = 'Seimbang';
                btnSave.disabled = false;
            } else {
                balanceBadge.className = 'badge badge-danger w-full justify-center py-1';
                balanceBadge.innerText = 'Tidak Seimbang (Selisih: ' + diff.toLocaleString('id-ID') + ')';
                btnSave.disabled = true;
            }
        }

        btnAddRow.addEventListener('click', () => addRow());

        // --- Init Data Lama ---
        if (existingEntries && existingEntries.length > 0) {
            existingEntries.forEach(entry => {
                addRow(entry);
            });
            // Recalculate setelah data diload
            setTimeout(calculateTotals, 500); 
        } else {
            addRow();
            addRow();
        }
    });

    function confirmDelete() {
        window.confirmDialog({
            title: 'Hapus Jurnal?',
            text: "Jurnal ini akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
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