@extends('layouts.app')

@section('title', 'Lembar Kerja Rekonsiliasi')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <form action="{{ route('bank-reconciliations.update', $bankReconciliation) }}" method="POST" id="recon-form">
        @csrf
        @method('PUT')

        {{-- HEADER --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div>
                <div class="flex items-center gap-2">
                    <i class="material-icons text-indigo-600 text-2xl">account_balance</i>
                    <h4 class="text-xl font-bold text-gray-900">{{ $bankReconciliation->account->account_name }}</h4>
                </div>
                <p class="text-sm text-gray-500 mt-1 ml-8">Periode Laporan: <span class="font-medium text-gray-900">{{ $bankReconciliation->statement_date->format('d F Y') }}</span></p>
            </div>
            
            <div class="mt-4 sm:mt-0 flex gap-3">
                <a href="{{ route('bank-reconciliations.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                    <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
                </a>

                @if($bankReconciliation->status == 'draft')
                    <button type="submit" name="action" value="save_draft" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-gray-900 focus:outline-none shadow-md transition">
                        <i class="material-icons text-lg mr-2">save</i> Simpan Draft
                    </button>
                    <button type="submit" name="action" value="reconcile" id="btn-finish" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-green-700 focus:outline-none shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <i class="material-icons text-lg mr-2">check_circle</i> Selesaikan
                    </button>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 border border-green-200">
                        <i class="material-icons text-sm mr-1">lock</i> Status: Reconciled
                    </span>
                @endif
            </div>
        </div>

        {{-- STICKY SUMMARY CARD --}}
        <div class="sticky top-4 z-30 mb-6">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-center divide-y md:divide-y-0 md:divide-x divide-gray-100">
                    
                    {{-- 1. TARGET --}}
                    <div class="pb-2 md:pb-0">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Saldo Rekening Koran</p>
                        <h3 class="text-2xl font-bold text-indigo-600 font-mono">Rp <span id="display-statement">{{ number_format($bankReconciliation->statement_balance, 0, ',', '.') }}</span></h3>
                    </div>

                    {{-- 2. LIVE CALC --}}
                    <div class="py-2 md:py-0 flex flex-col items-center justify-center relative">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Saldo Sistem (Cleared)</p>
                        <h3 class="text-2xl font-bold text-gray-900 font-mono">Rp <span id="display-cleared">0</span></h3>
                        {{-- Math Symbols Decoration --}}
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-200 text-2xl hidden md:block font-bold">-</span>
                    </div>

                    {{-- 3. DIFFERENCE --}}
                    <div class="pt-2 md:pt-0 relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-200 text-2xl hidden md:block font-bold">=</span>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Selisih (Target: 0)</p>
                        <h3 class="text-2xl font-bold text-red-600 font-mono" id="display-difference">Rp 0</h3>
                        <span id="status-badge" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 mt-1">BELUM SEIMBANG</span>
                    </div>
                </div>
                {{-- Progress Bar --}}
                <div class="h-1.5 w-full bg-gray-100">
                    <div id="recon-progress" class="h-1.5 bg-green-500 transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>

        {{-- WORKSPACE --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            {{-- KOLOM KIRI: DEPOSITS --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full max-h-[600px]">
                <div class="px-4 py-3 bg-green-600 text-white flex justify-between items-center">
                    <h5 class="font-bold text-sm flex items-center gap-2"><i class="material-icons text-lg">arrow_circle_down</i> Pemasukan (Debit)</h5>
                    <span class="bg-white text-green-700 text-xs font-bold px-2 py-0.5 rounded" id="sum-debit">Rp 0</span>
                </div>
                <div class="flex-1 overflow-y-auto p-0">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2 text-center w-10"><input type="checkbox" class="rounded text-green-600 focus:ring-green-500" id="check-all-debit"></th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            @php 
                                $allDeposits = $cleared_deposits->merge($unreconciled_deposits)->sortBy('entry_date');
                            @endphp
                            @forelse ($allDeposits as $entry)
                                @php $isChecked = in_array($entry->ledger_id, $cleared_deposits->pluck('ledger_id')->toArray()); @endphp
                                <tr class="hover:bg-green-50 transition-colors {{ $isChecked ? 'bg-green-50' : '' }}">
                                    <td class="px-4 py-2 text-center">
                                        <input type="checkbox" name="cleared_entries[]" value="{{ $entry->ledger_id }}" data-amount="{{ $entry->debit }}" data-type="debit" {{ $isChecked ? 'checked' : '' }} class="recon-check check-debit rounded text-green-600 focus:ring-green-500 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-600 whitespace-nowrap">{{ $entry->entry_date->format('d/m/y') }}</td>
                                    <td class="px-4 py-2 text-xs text-gray-500 truncate max-w-[150px]" title="{{ $entry->description }}">{{ $entry->description }}</td>
                                    <td class="px-4 py-2 text-xs text-right font-mono font-bold text-green-600">{{ number_format($entry->debit, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 text-xs">Tidak ada data pemasukan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- KOLOM KANAN: PAYMENTS --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full max-h-[600px]">
                <div class="px-4 py-3 bg-red-600 text-white flex justify-between items-center">
                    <h5 class="font-bold text-sm flex items-center gap-2"><i class="material-icons text-lg">arrow_circle_up</i> Pengeluaran (Kredit)</h5>
                    <span class="bg-white text-red-700 text-xs font-bold px-2 py-0.5 rounded" id="sum-credit">Rp 0</span>
                </div>
                <div class="flex-1 overflow-y-auto p-0">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2 text-center w-10"><input type="checkbox" class="rounded text-red-600 focus:ring-red-500" id="check-all-credit"></th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            @php $allPayments = $cleared_payments->merge($unreconciled_payments)->sortBy('entry_date'); @endphp
                            @forelse ($allPayments as $entry)
                                @php $isChecked = in_array($entry->ledger_id, $cleared_payments->pluck('ledger_id')->toArray()); @endphp
                                <tr class="hover:bg-red-50 transition-colors {{ $isChecked ? 'bg-red-50' : '' }}">
                                    <td class="px-4 py-2 text-center">
                                        <input type="checkbox" name="cleared_entries[]" value="{{ $entry->ledger_id }}" data-amount="{{ $entry->credit }}" data-type="credit" {{ $isChecked ? 'checked' : '' }} class="recon-check check-credit rounded text-red-600 focus:ring-red-500 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-600 whitespace-nowrap">{{ $entry->entry_date->format('d/m/y') }}</td>
                                    <td class="px-4 py-2 text-xs text-gray-500 truncate max-w-[150px]" title="{{ $entry->description }}">{{ $entry->description }}</td>
                                    <td class="px-4 py-2 text-xs text-right font-mono font-bold text-red-600">{{ number_format($entry->credit, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 text-xs">Tidak ada data pengeluaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- FOOTER INFO --}}
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-3 text-yellow-800 text-sm">
                <i class="material-icons text-yellow-600">lightbulb</i>
                <span><strong>Tips:</strong> Ada transaksi bank (Biaya Admin/Bunga) yang belum tercatat di sistem?</span>
            </div>
            <a href="{{ route('manual-journals.create') }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-yellow-100 text-yellow-800 rounded-md text-xs font-bold hover:bg-yellow-200 transition">
                <i class="material-icons text-sm mr-1">add</i> Buat Jurnal Penyesuaian
            </a>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // VARIABLES
    const statementBalance = {{ $bankReconciliation->statement_balance }};
    const openingBalance = {{ $calcOpeningBalance ?? ($bankReconciliation->closing_balance - ($cleared_deposits->sum('debit') - $cleared_payments->sum('credit'))) }};
    
    const checkboxes = document.querySelectorAll('.recon-check');
    const displayCleared = document.getElementById('display-cleared');
    const displayDifference = document.getElementById('display-difference');
    const sumDebitEl = document.getElementById('sum-debit');
    const sumCreditEl = document.getElementById('sum-credit');
    const btnFinish = document.getElementById('btn-finish');
    const statusBadge = document.getElementById('status-badge');
    const reconProgress = document.getElementById('recon-progress');

    const fmt = (num) => new Intl.NumberFormat('id-ID').format(num);

    // CALCULATION
    function calculate() {
        let totalDebit = 0;
        let totalCredit = 0;

        checkboxes.forEach(cb => {
            const tr = cb.closest('tr');
            if (cb.checked) {
                const amt = parseFloat(cb.dataset.amount);
                if (cb.dataset.type === 'debit') {
                    totalDebit += amt;
                    tr.classList.add('bg-green-50');
                } else {
                    totalCredit += amt;
                    tr.classList.add('bg-red-50');
                }
            } else {
                tr.classList.remove('bg-green-50', 'bg-red-50');
            }
        });

        const currentClearedBalance = openingBalance + totalDebit - totalCredit;
        const difference = statementBalance - currentClearedBalance;

        // UI Update
        displayCleared.innerText = fmt(currentClearedBalance);
        sumDebitEl.innerText = '+ Rp ' + fmt(totalDebit);
        sumCreditEl.innerText = '- Rp ' + fmt(totalCredit);

        const diffAbs = Math.abs(difference);
        displayDifference.innerText = 'Rp ' + fmt(difference);

        if (diffAbs < 1) {
            // BALANCE
            displayDifference.classList.remove('text-red-600');
            displayDifference.classList.add('text-green-600');
            
            statusBadge.className = 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 mt-1';
            statusBadge.innerText = 'SEIMBANG (MATCH)';
            
            if(btnFinish) btnFinish.disabled = false;
            reconProgress.className = 'h-1.5 bg-green-500 transition-all duration-500';
            reconProgress.style.width = '100%';
        } else {
            // NOT BALANCE
            displayDifference.classList.remove('text-green-600');
            displayDifference.classList.add('text-red-600');
            
            statusBadge.className = 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 mt-1';
            statusBadge.innerText = 'BELUM SEIMBANG';
            
            if(btnFinish) btnFinish.disabled = true;
            reconProgress.className = 'h-1.5 bg-red-500 transition-all duration-500';
            reconProgress.style.width = '50%';
        }
    }

    checkboxes.forEach(cb => cb.addEventListener('change', calculate));

    // Check All Logic
    const checkAllDebit = document.getElementById('check-all-debit');
    if(checkAllDebit){
        checkAllDebit.addEventListener('change', function() {
            document.querySelectorAll('.check-debit').forEach(cb => cb.checked = this.checked);
            calculate();
        });
    }
    const checkAllCredit = document.getElementById('check-all-credit');
    if(checkAllCredit){
        checkAllCredit.addEventListener('change', function() {
            document.querySelectorAll('.check-credit').forEach(cb => cb.checked = this.checked);
            calculate();
        });
    }

    // Submit Confirmation
    const form = document.getElementById('recon-form');
    if(btnFinish) {
        btnFinish.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Selesaikan Rekonsiliasi?',
                html: "Saldo sudah cocok.<br>Aksi ini akan <b>mengunci</b> periode ini.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Posting!',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'action';
                    input.value = 'reconcile';
                    form.appendChild(input);
                    form.submit();
                }
            });
        });
    }

    // Initial Calc
    calculate();
});
</script>
@endpush