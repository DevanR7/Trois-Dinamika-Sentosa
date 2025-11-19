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
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h2 class="fw-bold mb-0 fs-4"><i class="bi bi-journal-plus"></i> Buat Jurnal Umum Baru</h2>
                </div>
                <div class="card-body p-4">
                    
                    {{-- Error Handling Standard (Tetap ditampilkan untuk detail) --}}
                    @if ($errors->any())
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-octagon-fill fs-4 me-2"></i>
                        <div>
                            <strong>Gagal Menyimpan!</strong> Silakan periksa inputan Anda di bawah.
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('manual-journals.store') }}" method="POST" id="journal-form">
                        @csrf
                        
                        {{-- Header Jurnal --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="entry_date" class="form-label fw-semibold">Tanggal Jurnal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('entry_date') is-invalid @enderror" id="entry_date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required>
                                @error('entry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label for="description" class="form-label fw-semibold">Deskripsi / Memo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description') }}" placeholder="Contoh: Penyesuaian stok opname..." required>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <h5 class="fw-bold border-bottom pb-2 mb-3"><i class="bi bi-list-ul"></i> Detail Akun</h5>

                        {{-- Tabel Dinamis --}}
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-hover align-middle" id="journal-entries-table">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th style="width: 35%;">Akun (COA)</th>
                                        <th style="width: 25%;">Keterangan Baris</th>
                                        <th style="width: 15%;">Debit</th>
                                        <th style="width: 15%;">Kredit</th>
                                        <th style="width: 5%;"><i class="bi bi-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="journal-entries-body">
                                    {{-- Rows injected via JS --}}
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">TOTAL</td>
                                        <td class="text-end fw-bold" id="total-debit">Rp 0</td>
                                        <td class="text-end fw-bold" id="total-credit">Rp 0</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Balance (Selisih)</td>
                                        <td colspan="2" class="text-center fw-bold" id="total-difference">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-primary mb-4" id="add-entry-row">
                            <i class="bi bi-plus-circle-fill"></i> Tambah Baris Akun
                        </button>

                        {{-- Footer Actions --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('manual-journals.index') }}" class="btn btn-light border">Batal</a>
                            <button type="submit" class="btn btn-dark px-4" id="btn-submit-journal" disabled>
                                <i class="bi bi-save"></i> Simpan Jurnal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- JS Template --}}
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
            <td>
                <input type="text" class="form-control form-control-sm" name="entries[__INDEX__][description]" placeholder="Opsional">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end debit-input" name="entries[__INDEX__][debit]" placeholder="0" min="0" step="0.01">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end credit-input" name="entries[__INDEX__][credit]" placeholder="0" min="0" step="0.01">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-light text-danger border-0 remove-entry-row">
                    <i class="bi bi-x-circle-fill fs-5"></i>
                </button>
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
    const template = $('#journal-entry-template').html();
    let rowIndex = 0;

    // --- 1. Fungsi Helper Format Rupiah ---
    const formatRupiah = (num) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
    };

    // --- 2. Fungsi Menambah Baris ---
    const addRow = () => {
        let newRowHtml = template.replace(/__INDEX__/g, rowIndex);
        let $newRow = $(newRowHtml);
        tableBody.append($newRow);

        // Init Select2
        $newRow.find('.account-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $newRow // Penting agar dropdown nempel di row
        });

        rowIndex++;
        calculateTotals(); // Hitung ulang
    };

    // --- 3. Event Delegation untuk Input & Delete ---
    tableBody.on('input', '.debit-input, .credit-input', function() {
        // Logika: Jika isi debit, kredit jadi 0 (dan sebaliknya)
        let $row = $(this).closest('tr');
        if ($(this).hasClass('debit-input') && $(this).val() > 0) {
            $row.find('.credit-input').val(0);
        } else if ($(this).hasClass('credit-input') && $(this).val() > 0) {
            $row.find('.debit-input').val(0);
        }
        calculateTotals();
    });

    tableBody.on('click', '.remove-entry-row', function() {
        if(tableBody.find('tr').length > 2) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            Swal.fire('Info', 'Minimal harus ada 2 baris akun.', 'info');
        }
    });

    $('#add-entry-row').on('click', addRow);

    // --- 4. Kalkulasi Total & Validasi Balance ---
    const calculateTotals = () => {
        let totalDebit = 0;
        let totalCredit = 0;

        $('.journal-entry-row').each(function() {
            let d = parseFloat($(this).find('.debit-input').val()) || 0;
            let c = parseFloat($(this).find('.credit-input').val()) || 0;
            totalDebit += d;
            totalCredit += c;
        });

        let diff = totalDebit - totalCredit;

        $('#total-debit').text(formatRupiah(totalDebit));
        $('#total-credit').text(formatRupiah(totalCredit));
        
        const diffEl = $('#total-difference');
        diffEl.text(formatRupiah(diff));

        const submitBtn = $('#btn-submit-journal');

        if (Math.abs(diff) < 0.01 && totalDebit > 0) {
            diffEl.removeClass('text-danger bg-danger text-white badge').addClass('text-success');
            diffEl.html('<span class="badge bg-success"><i class="bi bi-check-circle"></i> Balance</span>');
            submitBtn.prop('disabled', false);
        } else {
            diffEl.removeClass('text-success').addClass('text-danger fw-bold');
            diffEl.html(`${formatRupiah(diff)} <span class="badge bg-danger">Not Balance</span>`);
            submitBtn.prop('disabled', true);
        }
    };

    // --- 5. SweetAlert pada Submit ---
    $('#journal-form').on('submit', function(e) {
        e.preventDefault(); // Stop submit asli
        
        Swal.fire({
            title: 'Simpan Jurnal?',
            html: "Pastikan data sudah benar.<br>Aksi ini akan memposting jurnal ke buku besar.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#212529', // Dark bootstrap
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading() }
                });
                
                e.target.submit(); // Submit form
            }
        });
    });

    // --- 6. Notifikasi Error Validasi (jika ada dari Controller) ---
    @if ($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Validasi Gagal',
            text: 'Mohon periksa kembali form inputan Anda.',
            confirmButtonColor: '#212529'
        });
    @endif

    // Init awal: 2 baris kosong
    addRow();
    addRow();
});
</script>
@endpush