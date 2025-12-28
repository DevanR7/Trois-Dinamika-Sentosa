@extends('admin.layouts.app')

@section('title', 'Buat Jurnal Manual')

@section('content')

    <div class="max-w-6xl mx-auto">
        
        <form action="{{ route('admin.manual-journals.store') }}" method="POST" id="journalForm">
            @csrf

            {{-- HEADER NAVIGATION --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="page-title">Buat Jurnal Manual</h1>
                    <p class="page-subtitle">Pastikan Debit dan Kredit seimbang.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.manual-journals.index') }}" class="btn btn-secondary">
                        <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                    </a>
                    <button type="submit" id="btnSave" class="btn btn-primary" disabled>
                        <i class="material-icons text-sm mr-2">save</i> Posting Jurnal
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
                                   value="{{ old('entry_date', date('Y-m-d')) }}" required>
                            @error('entry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label label-required">Deskripsi / Memo</label>
                            <input type="text" name="description" class="form-input" 
                                   placeholder="Contoh: Penyesuaian stok opname bulan Januari..." 
                                   value="{{ old('description') }}" required>
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
                
                <div class="table-container overflow-visible"> {{-- Overflow visible agar dropdown tomselect tidak terpotong --}}
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
                            {{-- Rows will be added here by JS --}}
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
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const entriesBody = document.getElementById('entriesBody');
        const btnAddRow = document.getElementById('btnAddRow');
        const btnSave = document.getElementById('btnSave');
        
        // Data akun dari controller untuk dropdown
        const accountsData = @json($accounts); 
        
        let rowCount = 0;

        // --- 1. Fungsi Tambah Baris ---
        function addRow() {
            rowCount++;
            
            // Build Options HTML
            let optionsHtml = '<option value="">Pilih Akun...</option>';
            accountsData.forEach(acc => {
                optionsHtml += `<option value="${acc.account_id}">${acc.account_number} - ${acc.account_name}</option>`;
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
                    <input type="text" name="entries[${rowCount}][description]" class="form-input text-sm" placeholder="Ket. khusus baris ini">
                </td>
                <td class="p-2 align-top">
                    <div class="input-group">
                        <span class="input-group-text px-2 text-xs">Rp</span>
                        <input type="text" name="entries[${rowCount}][debit]" class="form-input text-right autonumeric debit-input" placeholder="0">
                    </div>
                </td>
                <td class="p-2 align-top">
                    <div class="input-group">
                        <span class="input-group-text px-2 text-xs">Rp</span>
                        <input type="text" name="entries[${rowCount}][credit]" class="form-input text-right autonumeric credit-input" placeholder="0">
                    </div>
                </td>
                <td class="p-2 align-top text-center">
                    <button type="button" class="text-slate-400 hover:text-rose-500 transition-colors btn-remove-row">
                        <i class="material-icons text-lg">close</i>
                    </button>
                </td>
            `;
            
            entriesBody.appendChild(tr);

            // Init Plugins pada elemen baru
            // 1. Tom Select
            const selectEl = tr.querySelector('.tom-select-dynamic');
            new TomSelect(selectEl, {
                sortField: { field: "text", direction: "asc" },
                plugins: ['clear_button'],
                dropdownParent: 'body' // Penting agar tidak terpotong di tabel
            });

            // 2. AutoNumeric
            const anOptions = window.defaultAutoNumericOptions;
            new AutoNumeric(tr.querySelector('.debit-input'), anOptions);
            new AutoNumeric(tr.querySelector('.credit-input'), anOptions);
        }

        // --- 2. Event Listener Global (Delegation) ---
        entriesBody.addEventListener('click', function(e) {
            // Hapus Baris
            if (e.target.closest('.btn-remove-row')) {
                const row = e.target.closest('tr');
                if (entriesBody.children.length > 2) { // Minimal 2 baris (Debit & Kredit)
                    row.remove();
                    calculateTotals();
                } else {
                    // Opsional: Alert minimal 2 baris
                    window.confirmDialog({ icon: 'info', title: 'Minimal 2 Baris', text: 'Jurnal membutuhkan minimal satu debit dan satu kredit.', timer: 2000, showConfirmButton: false });
                }
            }
        });

        // Hitung ulang saat input berubah (menggunakan event 'keyup' atau 'autoNumeric:rawValueModified')
        entriesBody.addEventListener('autoNumeric:rawValueModified', function() {
            calculateTotals();
        });

        // --- 3. Kalkulasi Total & Validasi ---
        function calculateTotals() {
            let totalDebit = 0;
            let totalCredit = 0;

            document.querySelectorAll('.debit-input').forEach(el => {
                totalDebit += parseFloat(AutoNumeric.getAutoNumericElement(el).getNumericString() || 0);
            });

            document.querySelectorAll('.credit-input').forEach(el => {
                totalCredit += parseFloat(AutoNumeric.getAutoNumericElement(el).getNumericString() || 0);
            });

            // Update Tampilan
            document.getElementById('totalDebitDisplay').innerText = 'Rp ' + totalDebit.toLocaleString('id-ID');
            document.getElementById('totalCreditDisplay').innerText = 'Rp ' + totalCredit.toLocaleString('id-ID');

            const diff = Math.abs(totalDebit - totalCredit);
            const balanceBadge = document.getElementById('balanceDisplay');

            if (diff < 1 && totalDebit > 0) { // Toleransi 1 rupiah, dan harus ada isi
                balanceBadge.className = 'badge badge-success w-full justify-center py-1';
                balanceBadge.innerText = 'Seimbang';
                btnSave.disabled = false;
            } else {
                balanceBadge.className = 'badge badge-danger w-full justify-center py-1';
                balanceBadge.innerText = 'Tidak Seimbang (Selisih: ' + diff.toLocaleString('id-ID') + ')';
                btnSave.disabled = true;
            }

            // Logic tambahan: Cegah input Debit & Kredit di baris yang sama
            // (Opsional, tapi validasi controller Anda sudah handle ini)
        }

        // --- 4. Init Awal ---
        // Tambahkan 2 baris kosong secara default
        addRow();
        addRow();
        
        // Bind tombol tambah baris
        btnAddRow.addEventListener('click', addRow);
    });
</script>
@endpush