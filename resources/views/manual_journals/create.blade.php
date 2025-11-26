@extends('layouts.app')

@section('title', 'Buat Jurnal Umum Baru')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('manual-journals.index') }}" class="hover:text-indigo-600 transition-colors">Jurnal Umum</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Buat Jurnal Umum</h1>
            <p class="text-sm text-slate-500 mt-1">Catat transaksi penyesuaian atau jurnal manual lainnya.</p>
        </div>
        <a href="{{ route('manual-journals.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm animate-enter">
            <div class="flex items-start gap-3">
                <i class="material-icons text-red-600 text-xl mt-0.5">error_outline</i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Gagal Menyimpan</h3>
                    <ul class="mt-1 list-disc list-inside text-xs text-red-700">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('manual-journals.store') }}" method="POST" id="journal-form">
        @csrf
        
        {{-- HEADER JURNAL --}}
        <div class="dashboard-card p-0 overflow-hidden shadow-sm mb-6">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">description</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Header Jurnal</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="entry_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Jurnal <span class="text-red-500">*</span></label>
                    <input type="date" name="entry_date" id="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" class="form-input" required>
                    @error('entry_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Deskripsi / Memo <span class="text-red-500">*</span></label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}" class="form-input" placeholder="Contoh: Penyesuaian stok opname..." required>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- DETAIL AKUN --}}
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                        <i class="material-icons text-[20px]">list_alt</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Detail Akun</h3>
                </div>
                <button type="button" id="add-entry-row" class="h-[36px] px-4 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg text-xs font-bold hover:bg-indigo-100 transition flex items-center gap-1">
                    <i class="material-icons text-[16px]">add</i> Tambah Baris
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="dashboard-table min-w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="pl-6 w-4/12">Akun (COA)</th>
                            <th class="w-3/12">Keterangan Baris</th>
                            <th class="text-right w-2/12">Debit</th>
                            <th class="text-right w-2/12">Kredit</th>
                            <th class="text-center w-10 pr-6"></th>
                        </tr>
                    </thead>
                    <tbody id="journal-entries-body" class="divide-y divide-slate-100 bg-white">
                        {{-- Rows injected via JS --}}
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200">
                        <tr>
                            <td colspan="2" class="pl-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-indigo-700 font-mono" id="total-debit">Rp 0</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-indigo-700 font-mono" id="total-credit">Rp 0</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="pl-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider border-t border-slate-200">Balance</td>
                            <td colspan="2" class="px-4 py-3 text-center border-t border-slate-200">
                                <span id="total-difference" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-600 transition-all duration-300">
                                    Rp 0
                                </span>
                            </td>
                            <td class="border-t border-slate-200"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <a href="{{ route('manual-journals.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" id="btn-submit-journal" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">save</i> Simpan Jurnal
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Hidden Template --}}
<table class="hidden">
    <tbody id="journal-entry-template">
        <tr class="journal-entry-row hover:bg-slate-50 transition-colors group">
            <td class="pl-6 py-3 align-top">
                <select class="form-input account-select w-full text-sm" name="entries[__INDEX__][account_id]" required>
                    <option value="" disabled selected>-- Cari Akun --</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->account_id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="py-3 align-top">
                <input type="text" class="form-input w-full text-sm" name="entries[__INDEX__][description]" placeholder="Opsional">
            </td>
            <td class="py-3 align-top">
                <input type="number" class="form-input w-full text-sm text-right font-mono font-bold text-slate-800 debit-input" name="entries[__INDEX__][debit]" value="0" step="0.01" placeholder="0">
            </td>
            <td class="py-3 align-top">
                <input type="number" class="form-input w-full text-sm text-right font-mono font-bold text-slate-800 credit-input" name="entries[__INDEX__][credit]" value="0" step="0.01" placeholder="0">
            </td>
            <td class="pr-6 py-3 align-top text-center">
                <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition remove-entry-row">
                    <i class="material-icons text-[18px]">delete</i>
                </button>
            </td>
        </tr>
    </tbody>
</table>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
{{-- Tidak perlu import Swal lagi jika sudah ada di app.js --}}

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = $('#journal-entries-body');
    const template = $('#journal-entry-template').html();
    let rowIndex = 0;

    const formatRupiah = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

    const addRow = () => {
        let newRowHtml = template.replace(/__INDEX__/g, rowIndex);
        let $newRow = $(newRowHtml);
        tableBody.append($newRow);

        // Manual init Select2 untuk baris baru (karena dinamis)
        $newRow.find('.account-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $newRow,
            placeholder: '-- Cari Akun --',
            dropdownCssClass: 'select2-dropdown-clean'
        });

        rowIndex++;
        calculateTotals();
    };

    // Logic Input (Mutually Exclusive Debit/Credit)
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
            Swal.fire({ icon: 'info', title: 'Info', text: 'Minimal harus ada 2 baris akun.', confirmButtonColor: '#6366f1', customClass: { popup: 'colored-toast rounded-xl' } });
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

        // Toleransi float precision
        if (Math.abs(diff) < 0.01 && totalDebit > 0) {
            diffEl.removeClass('bg-red-100 text-red-800').addClass('bg-emerald-100 text-emerald-800');
            diffEl.html('<i class="material-icons text-[14px] mr-1">check_circle</i> Balance');
            submitBtn.prop('disabled', false);
        } else {
            diffEl.removeClass('bg-emerald-100 text-emerald-800').addClass('bg-red-100 text-red-800');
            diffEl.html(`${formatRupiah(diff)} <span class="ml-1 text-[10px] uppercase">Not Balance</span>`);
            submitBtn.prop('disabled', true);
        }
    };

    // Submit Confirmation (Manual Swal karena logic khusus)
    $('#journal-form').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Simpan Jurnal?',
            text: "Pastikan data sudah benar. Aksi ini akan memposting jurnal ke buku besar.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'colored-toast rounded-xl',
                confirmButton: 'px-6 py-2.5 rounded-lg font-bold',
                cancelButton: 'px-6 py-2.5 rounded-lg font-bold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                e.target.submit();
            }
        });
    });

    // Start with 2 rows
    addRow();
    addRow();
    
    // Toast dari App.js handle via Session
    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush