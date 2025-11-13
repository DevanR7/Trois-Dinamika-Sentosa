@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container .select2-selection--single { height: 38px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 2.4 !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { top: 0.45rem !important; }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Edit Jurnal Umum Manual: {{ $manualJournal->journal_number }}</h2>
                </div>
                <div class="card-body">
                    @if ($errors->any() || session('error'))
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            @if (session('error'))<li>{{ session('error') }}</li>@endif
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('manual-journals.update', $manualJournal) }}" method="POST" id="journal-form">
                        @csrf
                        @method('PUT')
                        {{-- Bagian Header Jurnal --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="entry_date" class="form-label">Tanggal Jurnal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('entry_date') is-invalid @enderror" id="entry_date" name="entry_date" value="{{ old('entry_date', $manualJournal->entry_date->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-8">
                                <label for="description" class="form-label">Deskripsi/Memo Jurnal <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description', $manualJournal->description) }}" required>
                            </div>
                        </div>

                        <hr>
                        
                        {{-- Bagian Entri Jurnal (Dinamis) --}}
                        <h5 class="fw-semibold">Entri Jurnal</h5>
                        <div class="table-responsive">
                            <table class="table table-sm" id="journal-entries-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 35%;">Akun (COA)</th>
                                        <th style="width: 25%;">Deskripsi Baris</th>
                                        <th style="width: 15%;" class="text-end">Debit</th>
                                        <th style="width: 15%;" class="text-end">Kredit</th>
                                        <th style="width: 10%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="journal-entries-body">
                                    {{-- Isi dengan data yang ada --}}
                                    @foreach (old('entries', $manualJournal->entries) as $index => $entry)
                                        <tr class="journal-entry-row">
                                            <td>
                                                <select class="form-select account-select" name="entries[{{ $index }}][account_id]" required>
                                                    <option value="" disabled>-- Pilih Akun --</option>
                                                    @foreach ($accounts as $account)
                                                        @php
                                                            // Handle data dari old() (array) vs $entry (object)
                                                            $selectedAccountId = is_array($entry) ? ($entry['account_id'] ?? null) : ($entry->chart_of_account_id ?? null);
                                                        @endphp
                                                        <option value="{{ $account->account_id }}" {{ $selectedAccountId == $account->account_id ? 'selected' : '' }}>
                                                            {{ $account->account_number }} - {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control" name="entries[{{ $index }}][description]" placeholder="Deskripsi baris (opsional)" value="{{ is_array($entry) ? ($entry['description'] ?? '') : ($entry->description ?? '') }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control text-end debit-input" name="entries[{{ $index }}][debit]" placeholder="0" min="0" step="0.01" value="{{ is_array($entry) ? ($entry['debit'] ?? 0) : ($entry->debit ?? 0) }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control text-end credit-input" name="entries[{{ $index }}][credit]" placeholder="0" min="0" step="0.01" value="{{ is_array($entry) ? ($entry['credit'] ?? 0) : ($entry->credit ?? 0) }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-entry-row">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-dark btn-sm" id="add-entry-row">
                            <i class="bi bi-plus-lg"></i> Tambah Baris
                        </button>

                        {{-- Bagian Footer (Total & Submit) --}}
                        <div class="row justify-content-end mt-4">
                            <div class="col-md-5">
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <th class="text-end">Total Debit:</th>
                                            <td class="text-end fw-bold fs-5" id="total-debit">Rp 0</td>
                                        </tr>
                                        <tr>
                                            <th class="text-end">Total Kredit:</th>
                                            <td class="text-end fw-bold fs-5" id="total-credit">Rp 0</td>
                                        </tr>
                                        <tr>
                                            <th class="text-end">Selisih:</th>
                                            <td class="text-end fw-bold fs-5 text-danger" id="total-difference">Rp 0</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-dark btn-lg" id="submit-journal">
                                        <i class="bi bi-save-fill"></i> Simpan Perubahan Jurnal
                                    </button>
                                    <a href="{{ route('manual-journals.index') }}" class="btn btn-outline-secondary">Batal</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Template Baris untuk JavaScript (Sama seperti create) --}}
    <script type="text/template" id="journal-entry-template">
        <tr class="journal-entry-row">
            <td>
                <select class="form-select account-select" name="entries[__INDEX__][account_id]" required>
                    <option value="" disabled selected>-- Pilih Akun --</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->account_id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="text" class="form-control" name="entries[__INDEX__][description]" placeholder="Deskripsi baris (opsional)">
            </td>
            <td>
                <input type="number" class="form-control text-end debit-input" name="entries[__INDEX__][debit]" placeholder="0" min="0" step="0.01">
            </td>
            <td>
                <input type="number" class="form-control text-end credit-input" name="entries[__INDEX__][credit]" placeholder="0" min="0" step="0.01">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-entry-row">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </td>
        </tr>
    </script>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('journal-entries-body');
    const addRowButton = document.getElementById('add-entry-row');
    const template = document.getElementById('journal-entry-template').innerHTML;
    const totalDebitEl = document.getElementById('total-debit');
    const totalCreditEl = document.getElementById('total-credit');
    const totalDifferenceEl = document.getElementById('total-difference');
    const submitButton = document.getElementById('submit-journal');
    
    // Tentukan rowIndex awal berdasarkan baris yang sudah ada dari server (untuk edit)
    let rowIndex = tableBody.querySelectorAll('.journal-entry-row').length;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    function initializeSelect2(context) {
        $(context).find('.account-select').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Pilih Akun --',
            width: '100%'
        });
    }
    
    function addRow() {
        const newRow = template.replace(/__INDEX__/g, rowIndex);
        tableBody.insertAdjacentHTML('beforeend', newRow);
        
        const newRowElement = tableBody.lastElementChild;
        initializeSelect2(newRowElement); // Inisialisasi Select2 pada baris baru
        attachRowListeners(newRowElement);
        rowIndex++;
    }

    function attachRowListeners(row) {
        row.querySelector('.remove-entry-row').addEventListener('click', function() {
            row.remove();
            calculateTotals();
        });

        row.querySelector('.debit-input').addEventListener('input', function(e) {
            const creditInput = row.querySelector('.credit-input');
            if (e.target.value > 0) {
                creditInput.value = 0;
            }
            calculateTotals();
        });
        
        row.querySelector('.credit-input').addEventListener('input', function(e) {
            const debitInput = row.querySelector('.debit-input');
            if (e.target.value > 0) {
                debitInput.value = 0;
            }
            calculateTotals();
        });
    }

    function calculateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;

        tableBody.querySelectorAll('.journal-entry-row').forEach(row => {
            const debit = parseFloat(row.querySelector('.debit-input').value) || 0;
            const credit = parseFloat(row.querySelector('.credit-input').value) || 0;
            totalDebit += debit;
            totalCredit += credit;
        });

        const difference = totalDebit - totalCredit;

        totalDebitEl.textContent = formatRupiah(totalDebit);
        totalCreditEl.textContent = formatRupiah(totalCredit);
        totalDifferenceEl.textContent = formatRupiah(difference);

        if (Math.abs(difference) < 0.01 && totalDebit > 0) {
            totalDifferenceEl.classList.remove('text-danger');
            totalDifferenceEl.classList.add('text-success');
            submitButton.disabled = false;
        } else {
            totalDifferenceEl.classList.add('text-danger');
            totalDifferenceEl.classList.remove('text-success');
            submitButton.disabled = true;
        }
    }

    addRowButton.addEventListener('click', addRow);

    // Inisialisasi untuk baris yang sudah ada (halaman edit)
    tableBody.querySelectorAll('.journal-entry-row').forEach(row => {
        attachRowListeners(row);
    });
    initializeSelect2(tableBody);

    // Jika tidak ada baris (halaman create), tambahkan 2 baris awal
    if(rowIndex === 0) {
        addRow();
        addRow();
    }
    
    calculateTotals(); // Hitung total saat halaman dimuat
});
</script>
@endpush