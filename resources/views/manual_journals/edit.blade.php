@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container .select2-selection--single { height: 38px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 2.4 !important; }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h2 class="fw-bold mb-0 fs-4">Edit Jurnal: {{ $manualJournal->journal_number }}</h2>
                </div>
                <div class="card-body p-4">

                    <form action="{{ route('manual-journals.update', $manualJournal) }}" method="POST" id="journal-form">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tanggal Jurnal</label>
                                <input type="date" class="form-control" name="entry_date" value="{{ old('entry_date', $manualJournal->entry_date->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Deskripsi</label>
                                <input type="text" class="form-control" name="description" value="{{ old('description', $manualJournal->description) }}" required>
                            </div>
                        </div>

                        <h5 class="fw-bold border-bottom pb-2 mb-3">Entri Jurnal</h5>
                        
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle" id="journal-entries-table">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th style="width: 35%;">Akun</th>
                                        <th style="width: 25%;">Ket</th>
                                        <th style="width: 15%;">Debit</th>
                                        <th style="width: 15%;">Kredit</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="journal-entries-body">
                                    {{-- Loop Data Existing --}}
                                    @foreach (old('entries', $manualJournal->entries) as $index => $entry)
                                    <tr class="journal-entry-row">
                                        <td>
                                            <select class="form-select account-select" name="entries[{{ $index }}][account_id]" required>
                                                <option value="" disabled>-- Pilih Akun --</option>
                                                @foreach ($accounts as $account)
                                                    @php
                                                        $val = is_array($entry) ? ($entry['account_id'] ?? '') : $entry->chart_of_account_id;
                                                    @endphp
                                                    <option value="{{ $account->account_id }}" {{ $val == $account->account_id ? 'selected' : '' }}>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="entries[{{ $index }}][description]" 
                                                value="{{ is_array($entry) ? ($entry['description'] ?? '') : $entry->description }}">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-end debit-input" name="entries[{{ $index }}][debit]" 
                                                value="{{ is_array($entry) ? ($entry['debit'] ?? 0) : $entry->debit }}" step="0.01">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-end credit-input" name="entries[{{ $index }}][credit]" 
                                                value="{{ is_array($entry) ? ($entry['credit'] ?? 0) : $entry->credit }}" step="0.01">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-light text-danger border-0 remove-entry-row">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">TOTAL</td>
                                        <td class="text-end fw-bold" id="total-debit">0</td>
                                        <td class="text-end fw-bold" id="total-credit">0</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Balance</td>
                                        <td colspan="2" class="text-center fw-bold" id="total-difference">0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-primary mb-4" id="add-entry-row">
                            <i class="bi bi-plus-circle-fill"></i> Tambah Baris
                        </button>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('manual-journals.index') }}" class="btn btn-light border">Batal</a>
                            <button type="submit" class="btn btn-dark px-4" id="btn-submit-journal">
                                <i class="bi bi-save-fill"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Hidden Template for New Rows --}}
    <script type="text/template" id="journal-entry-template">
        <tr class="journal-entry-row">
            <td>
                <select class="form-select account-select" name="entries[__INDEX__][account_id]" required>
                    <option value="" disabled selected>-- Cari Akun --</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->account_id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm" name="entries[__INDEX__][description]"></td>
            <td><input type="number" class="form-control form-control-sm text-end debit-input" name="entries[__INDEX__][debit]" value="0" step="0.01"></td>
            <td><input type="number" class="form-control form-control-sm text-end credit-input" name="entries[__INDEX__][credit]" value="0" step="0.01"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-light text-danger border-0 remove-entry-row"><i class="bi bi-trash-fill"></i></button>
            </td>
        </tr>
    </script>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    const tableBody = $('#journal-entries-body');
    let rowIndex = {{ count(old('entries', $manualJournal->entries)) }}; // Start index dari jumlah data ada

    // Init Select2 untuk baris yang sudah ada (load dari server)
    $('.account-select').select2({ theme: 'bootstrap-5', width: '100%' });

    const formatRupiah = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

    const calculateTotals = () => {
        let totalDebit = 0;
        let totalCredit = 0;
        $('.journal-entry-row').each(function() {
            totalDebit += parseFloat($(this).find('.debit-input').val()) || 0;
            totalCredit += parseFloat($(this).find('.credit-input').val()) || 0;
        });

        let diff = totalDebit - totalCredit;
        $('#total-debit').text(formatRupiah(totalDebit));
        $('#total-credit').text(formatRupiah(totalCredit));
        
        const diffEl = $('#total-difference');
        const btn = $('#btn-submit-journal');

        if (Math.abs(diff) < 0.01 && totalDebit > 0) {
            diffEl.html('<span class="badge bg-success">Balance</span>');
            btn.prop('disabled', false);
        } else {
            diffEl.html(`${formatRupiah(diff)} <span class="badge bg-danger">Not Balance</span>`);
            btn.prop('disabled', true);
        }
    };

    // Add Row Logic
    $('#add-entry-row').click(function() {
        let template = $('#journal-entry-template').html().replace(/__INDEX__/g, rowIndex);
        let $row = $(template);
        tableBody.append($row);
        $row.find('.account-select').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $row });
        rowIndex++;
    });

    // Remove & Input Logic
    tableBody.on('click', '.remove-entry-row', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    tableBody.on('input', '.debit-input, .credit-input', function() {
        let $row = $(this).closest('tr');
        if ($(this).hasClass('debit-input') && $(this).val() > 0) $row.find('.credit-input').val(0);
        else if ($(this).hasClass('credit-input') && $(this).val() > 0) $row.find('.debit-input').val(0);
        calculateTotals();
    });

    // SweetAlert Submit
    $('#journal-form').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Simpan Perubahan?',
            text: "Data jurnal akan diperbarui.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Update',
            confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if(result.isConfirmed) e.target.submit();
        });
    });

    // Hitung total saat load halaman pertama kali
    calculateTotals();
});
</script>
@endpush