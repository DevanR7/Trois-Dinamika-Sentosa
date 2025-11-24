@extends('layouts.app')

@section('title', 'Buat Jurnal Umum Baru')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        /* Custom style agar Select2 menyatu dengan input Tailwind */
        .select2-container--bootstrap-5 .select2-selection {
            border-color: #d1d5db !important;
            padding-top: 0.3rem; padding-bottom: 0.3rem;
            border-radius: 0.5rem; min-height: 38px;
        }
    </style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('manual-journals.index') }}" class="hover:text-indigo-600 transition">Jurnal Umum</a>
                <span>/</span>
                <span class="text-gray-800">Baru</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Buat Jurnal Umum</h2>
            <p class="text-sm text-gray-500 mt-1">Catat transaksi penyesuaian atau jurnal manual lainnya.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('manual-journals.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 text-lg mt-0.5">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Gagal Menyimpan</h3>
                <p class="text-xs text-red-700 mt-1">Silakan periksa inputan Anda di bawah.</p>
            </div>
        </div>
    @endif

    <form action="{{ route('manual-journals.store') }}" method="POST" id="journal-form">
        @csrf
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">description</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Header Jurnal</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="entry_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Jurnal <span class="text-red-500">*</span></label>
                    <input type="date" name="entry_date" id="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    @error('entry_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi / Memo <span class="text-red-500">*</span></label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Penyesuaian stok opname..." required>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <i class="material-icons text-indigo-500">list</i>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Detail Akun</h3>
                </div>
                <button type="button" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition" id="add-entry-row">
                    <i class="material-icons text-sm mr-1">add</i> Tambah Baris
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-1/3">Akun (COA)</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-1/4">Keterangan Baris</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase w-32">Debit</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase w-32">Kredit</th>
                            <th class="px-4 py-3 text-center w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="journal-entries-body">
                        {{-- Rows injected via JS --}}
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200 font-bold text-sm">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right text-gray-600 uppercase text-xs">Total</td>
                            <td class="px-4 py-3 text-right text-indigo-600 font-mono" id="total-debit">Rp 0</td>
                            <td class="px-4 py-3 text-right text-indigo-600 font-mono" id="total-credit">Rp 0</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-right text-gray-600 uppercase text-xs border-t border-gray-200">Balance (Selisih)</td>
                            <td colspan="2" class="px-4 py-2 text-center border-t border-gray-200">
                                <span id="total-difference" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800">
                                    Rp 0
                                </span>
                            </td>
                            <td class="border-t border-gray-200"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('manual-journals.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed" id="btn-submit-journal" disabled>
                    <i class="material-icons text-lg mr-2">save</i> Simpan Jurnal
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Hidden Template --}}
<template id="journal-entry-template">
    <tr class="journal-entry-row hover:bg-gray-50 transition">
        <td class="px-4 py-2">
            <select class="form-select account-select block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" name="entries[__INDEX__][account_id]" required>
                <option value="" disabled selected>-- Cari Akun --</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->account_id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                @endforeach
            </select>
        </td>
        <td class="px-4 py-2">
            <input type="text" class="form-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" name="entries[__INDEX__][description]" placeholder="Opsional">
        </td>
        <td class="px-4 py-2">
            <input type="number" class="form-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-right font-mono debit-input" name="entries[__INDEX__][debit]" value="0" step="0.01">
        </td>
        <td class="px-4 py-2">
            <input type="number" class="form-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-right font-mono credit-input" name="entries[__INDEX__][credit]" value="0" step="0.01">
        </td>
        <td class="px-4 py-2 text-center">
            <button type="button" class="text-gray-400 hover:text-red-600 transition remove-entry-row">
                <i class="material-icons text-lg">delete</i>
            </button>
        </td>
    </tr>
</template>
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

    const formatRupiah = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

    const addRow = () => {
        let newRowHtml = template.replace(/__INDEX__/g, rowIndex);
        let $newRow = $(newRowHtml);
        tableBody.append($newRow);

        $newRow.find('.account-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $newRow
        });

        rowIndex++;
        calculateTotals();
    };

    tableBody.on('input', '.debit-input, .credit-input', function() {
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
            Swal.fire({ icon: 'info', title: 'Info', text: 'Minimal harus ada 2 baris akun.', confirmButtonColor: '#6366f1' });
        }
    });

    $('#add-entry-row').on('click', addRow);

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
        const submitBtn = $('#btn-submit-journal');

        if (Math.abs(diff) < 0.01 && totalDebit > 0) {
            diffEl.removeClass('bg-red-100 text-red-800').addClass('bg-green-100 text-green-800');
            diffEl.html('<i class="material-icons text-sm mr-1">check_circle</i> Balance');
            submitBtn.prop('disabled', false);
        } else {
            diffEl.removeClass('bg-green-100 text-green-800').addClass('bg-red-100 text-red-800');
            diffEl.html(`${formatRupiah(diff)} <span class="ml-1 text-[10px] uppercase">Not Balance</span>`);
            submitBtn.prop('disabled', true);
        }
    };

    $('#journal-form').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Simpan Jurnal?',
            text: "Pastikan data sudah benar. Aksi ini akan memposting jurnal ke buku besar.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading() }
                });
                e.target.submit();
            }
        });
    });

    addRow();
    addRow();
});
</script>
@endpush