@extends('layouts.app')

@push('styles')
{{-- Select2 untuk search dropdown akun --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        line-height: 2.4 !important;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        top: 0.45rem !important;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Buat Jurnal Umum Manual Baru</h2>
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

                    <form action="{{ route('manual-journals.store') }}" method="POST" id="journal-form">
                        @csrf
                        {{-- Bagian Header Jurnal --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="entry_date" class="form-label">Tanggal Jurnal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('entry_date') is-invalid @enderror" id="entry_date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-md-8">
                                <label for="description" class="form-label">Deskripsi/Memo Jurnal <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description') }}" placeholder="Contoh: Jurnal penyusutan bulanan" required>
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
                                    {{-- Baris template untuk JS --}}
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
                                        <i class="bi bi-save-fill"></i> Simpan & Posting Jurnal
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

    {{-- Template Baris untuk JavaScript --}}
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
    let rowIndex = 0;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    function addRow() {
        const newRow = template.replace(/__INDEX__/g, rowIndex);
        tableBody.insertAdjacentHTML('beforeend', newRow);
        
        // Inisialisasi Select2 pada baris baru
        const newSelect = tableBody.querySelector(`select[name="entries[${rowIndex}][account_id]"]`);
        $(newSelect).select2({
            theme: 'bootstrap-5',
            placeholder: '-- Pilih Akun --',
            width: '100%'
        });

        attachRowListeners(tableBody.lastElementChild);
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

    // Tambahkan 2 baris awal
    addRow();
    addRow();
    calculateTotals();
});
</script>
@endpush