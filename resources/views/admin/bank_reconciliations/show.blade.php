@extends('admin.layouts.app')

@section('title', 'Lembar Kerja Rekonsiliasi')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">

    <form action="{{ route('admin.bank-reconciliations.update', $bankReconciliation) }}" method="POST" id="recon-form">
        @csrf
        @method('PUT')

        {{-- HEADER --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <nav class="flex text-sm text-slate-500 mb-1">
                    <a href="{{ route('admin.bank-reconciliations.index') }}" class="hover:text-indigo-600 transition">Rekonsiliasi</a>
                    <span class="mx-2 text-slate-300">/</span>
                    <span class="text-slate-800 font-semibold">Worksheet</span>
                </nav>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <span class="font-mono text-indigo-600">{{ $bankReconciliation->account->account_name }}</span>
                </h1>
            </div>
            
            <div class="flex gap-3 w-full sm:w-auto">
                <a href="{{ route('admin.bank-reconciliations.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                    <i class="material-icons text-[18px]">arrow_back</i> Kembali
                </a>

                @if($bankReconciliation->status == 'draft')
                    <button type="submit" name="action" value="save_draft" class="h-[48px] px-6 bg-slate-800 hover:bg-slate-900 border border-transparent rounded-lg font-bold text-sm text-white shadow-sm transition-all flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">save</i> Simpan Draft
                    </button>
                    <button type="submit" name="action" value="reconcile" id="btn-finish" class="h-[48px] px-6 bg-emerald-600 hover:bg-emerald-700 border border-transparent rounded-lg font-bold text-sm text-white shadow-md transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed group hover:-translate-y-0.5" disabled>
                        <i class="material-icons text-[18px] group-hover:scale-110 transition">check_circle</i> Selesaikan
                    </button>
                @else
                    <span class="status-completed h-[48px] px-6 rounded-lg text-sm font-bold flex items-center shadow-sm border border-emerald-200 bg-emerald-50 text-emerald-700">
                        <i class="material-icons text-[18px] mr-2">lock</i> Status: Selesai
                    </span>
                @endif
            </div>
        </div>

        {{-- STICKY SUMMARY CARD --}}
        <div class="sticky top-6 z-30 mb-8 transition-all duration-300">
            <div class="bg-white rounded-t-xl shadow-lg border border-slate-200 p-0 overflow-hidden">
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-slate-100">
                    
                    {{-- 1. TARGET --}}
                    <div class="pb-2 md:pb-0">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Saldo Rekening Koran</p>
                        <h3 class="text-2xl font-bold text-indigo-600 font-mono">Rp <span id="display-statement">{{ number_format($bankReconciliation->statement_balance, 0, ',', '.') }}</span></h3>
                    </div>

                    {{-- 2. LIVE CALC --}}
                    <div class="py-2 md:py-0 flex flex-col items-center justify-center">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Saldo Sistem (Cleared)</p>
                        <h3 class="text-2xl font-bold text-slate-800 font-mono">Rp <span id="display-cleared">0</span></h3>
                    </div>

                    {{-- 3. DIFFERENCE --}}
                    <div class="pt-2 md:pt-0">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Selisih (Target: 0)</p>
                        <h3 class="text-2xl font-bold font-mono text-red-600 transition-colors duration-300" id="display-difference">Rp 0</h3>
                        <span id="status-badge" class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 mt-2 uppercase tracking-wide transition-all duration-300">
                            Belum Seimbang
                        </span>
                    </div>
                </div>
            </div>
            {{-- Progress Bar --}}
            <div class="h-1.5 w-full bg-slate-100 rounded-b-xl overflow-hidden shadow-sm border-x border-b border-slate-200">
                <div id="recon-progress" class="h-full bg-red-500 transition-all duration-500 ease-out" style="width: 0%"></div>
            </div>
        </div>

        {{-- WORKSPACE --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            @php $isLocked = $bankReconciliation->status != 'draft'; @endphp

            {{-- KOLOM KIRI: DEPOSITS (PEMASUKAN) --}}
            <div class="dashboard-card p-0 overflow-hidden flex flex-col h-[600px] shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-4 bg-emerald-600 text-white flex justify-between items-center flex-shrink-0 shadow-sm z-10">
                    <h5 class="font-bold text-sm flex items-center gap-2 uppercase tracking-wide">
                        <i class="material-icons text-lg">arrow_circle_down</i> Pemasukan (Debit)
                    </h5>
                    <span class="bg-emerald-700/50 border border-emerald-500 text-white text-xs font-mono font-bold px-3 py-1 rounded-md shadow-sm" id="sum-debit">Rp 0</span>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar bg-white relative">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-center w-12 bg-slate-50 border-b border-slate-200">
                                    <input type="checkbox" class="rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer border-slate-300" id="check-all-debit" {{ $isLocked ? 'disabled' : '' }}>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase bg-slate-50 border-b border-slate-200">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase bg-slate-50 border-b border-slate-200">Deskripsi</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase bg-slate-50 border-b border-slate-200">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-50">
                            @php $allDeposits = $cleared_deposits->merge($unreconciled_deposits)->sortBy('entry_date'); @endphp
                            @forelse ($allDeposits as $entry)
                                @php $isChecked = in_array($entry->ledger_id, $cleared_deposits->pluck('ledger_id')->toArray()); @endphp
                                <tr class="hover:bg-emerald-50/30 transition-colors group {{ $isChecked ? 'bg-emerald-50/20' : '' }}">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="cleared_entries[]" value="{{ $entry->ledger_id }}" 
                                               data-amount="{{ $entry->debit }}" data-type="debit" {{ $isChecked ? 'checked' : '' }} 
                                               class="recon-check check-debit rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer border-slate-300" {{ $isLocked ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600 font-mono whitespace-nowrap">{{ $entry->entry_date->format('d/m/y') }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600 truncate max-w-[180px]" title="{{ $entry->description }}">{{ $entry->description }}</td>
                                    <td class="px-4 py-3 text-xs text-right font-mono font-bold text-emerald-600 group-hover:text-emerald-700 transition">
                                        {{ number_format($entry->debit, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-12 text-center text-slate-400 text-sm italic">Tidak ada data pemasukan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- KOLOM KANAN: PAYMENTS (PENGELUARAN) --}}
            <div class="dashboard-card p-0 overflow-hidden flex flex-col h-[600px] shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-3 bg-red-600 text-white flex justify-between items-center flex-shrink-0 shadow-sm z-10">
                    <h5 class="font-bold text-sm flex items-center gap-2 uppercase tracking-wide">
                        <i class="material-icons text-lg">arrow_circle_up</i> Pengeluaran (Kredit)
                    </h5>
                    <span class="bg-red-700/50 border border-red-500 text-white text-xs font-mono font-bold px-3 py-1 rounded-md shadow-sm" id="sum-credit">Rp 0</span>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar bg-white relative">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-center w-12 bg-slate-50 border-b border-slate-200">
                                    <input type="checkbox" class="rounded text-red-600 focus:ring-red-500 cursor-pointer border-slate-300" id="check-all-credit" {{ $isLocked ? 'disabled' : '' }}>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase bg-slate-50 border-b border-slate-200">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase bg-slate-50 border-b border-slate-200">Deskripsi</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase bg-slate-50 border-b border-slate-200">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-50">
                            @php $allPayments = $cleared_payments->merge($unreconciled_payments)->sortBy('entry_date'); @endphp
                            @forelse ($allPayments as $entry)
                                @php $isChecked = in_array($entry->ledger_id, $cleared_payments->pluck('ledger_id')->toArray()); @endphp
                                <tr class="hover:bg-red-50/30 transition-colors group {{ $isChecked ? 'bg-red-50/20' : '' }}">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="cleared_entries[]" value="{{ $entry->ledger_id }}" 
                                               data-amount="{{ $entry->credit }}" data-type="credit" {{ $isChecked ? 'checked' : '' }} 
                                               class="recon-check check-credit rounded text-red-600 focus:ring-red-500 cursor-pointer border-slate-300" {{ $isLocked ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap font-mono">{{ $entry->entry_date->format('d/m/y') }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600 truncate max-w-[180px]" title="{{ $entry->description }}">{{ $entry->description }}</td>
                                    <td class="px-4 py-3 text-xs text-right font-mono font-bold text-red-600 group-hover:text-red-700 transition">
                                        {{ number_format($entry->credit, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-12 text-center text-slate-400 text-sm italic">Tidak ada data pengeluaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- FOOTER INFO --}}
        <div class="mt-8 bg-amber-50 border border-amber-200 rounded-xl p-5 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm">
            <div class="flex items-center gap-3 text-amber-900 text-sm">
                <div class="bg-amber-100 p-2 rounded-full text-amber-600"><i class="material-icons text-xl">lightbulb</i></div>
                <div>
                    <strong class="block mb-0.5">Tips Rekonsiliasi:</strong>
                    <span class="opacity-80">Jika ada transaksi bank (Biaya Admin/Bunga) yang belum tercatat di sistem, buat jurnal penyesuaian terlebih dahulu.</span>
                </div>
            </div>
            @if(!$isLocked)
            <a href="{{ route('admin.manual-journals.create') }}" target="_blank" class="inline-flex items-center px-5 py-2.5 bg-white border border-amber-300 text-amber-700 rounded-lg text-sm font-bold hover:bg-amber-100 transition shadow-sm whitespace-nowrap">
                <i class="material-icons text-[18px] mr-2">add_circle</i> Buat Jurnal Penyesuaian
            </a>
            @endif
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif

    // --- VARIABLES ---
    const statementBalance = {{ $bankReconciliation->statement_balance }};
    const openingBalance = {{ $calcOpeningBalance ?? 0 }}; 

    const checkboxes = document.querySelectorAll('.recon-check');
    const displayCleared = document.getElementById('display-cleared');
    const displayDifference = document.getElementById('display-difference');
    const sumDebitEl = document.getElementById('sum-debit');
    const sumCreditEl = document.getElementById('sum-credit');
    const btnFinish = document.getElementById('btn-finish');
    const statusBadge = document.getElementById('status-badge');
    const reconProgress = document.getElementById('recon-progress');

    const fmt = (num) => new Intl.NumberFormat('id-ID').format(num);

    // --- CALCULATION LOGIC ---
    function calculate() {
        let totalDebit = 0;
        let totalCredit = 0;

        checkboxes.forEach(cb => {
            const tr = cb.closest('tr');
            tr.classList.remove('bg-emerald-50/20', 'bg-red-50/20');
            
            if (cb.checked) {
                const amt = parseFloat(cb.dataset.amount);
                if (cb.dataset.type === 'debit') {
                    totalDebit += amt;
                    tr.classList.add('bg-emerald-50/20');
                } else {
                    totalCredit += amt;
                    tr.classList.add('bg-red-50/20');
                }
            }
        });

        const currentClearedBalance = openingBalance + totalDebit - totalCredit;
        const difference = statementBalance - currentClearedBalance;
        const diffAbs = Math.abs(difference);

        // Update UI
        displayCleared.innerText = fmt(currentClearedBalance);
        sumDebitEl.innerText = 'Rp ' + fmt(totalDebit);
        sumCreditEl.innerText = 'Rp ' + fmt(totalCredit);
        
        displayDifference.innerText = 'Rp ' + fmt(difference);

        // Status Logic
        if (diffAbs < 1) { // Toleransi pembulatan
            // BALANCE
            displayDifference.classList.remove('text-red-600');
            displayDifference.classList.add('text-emerald-600');
            
            statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 mt-2 uppercase tracking-wide transition-all duration-300';
            statusBadge.innerHTML = '<i class="material-icons text-[14px] mr-1">check_circle</i> SEIMBANG';
            
            if(btnFinish) btnFinish.disabled = false;
            
            reconProgress.classList.remove('bg-red-500');
            reconProgress.classList.add('bg-emerald-500');
            reconProgress.style.width = '100%';
        } else {
            // NOT BALANCE
            displayDifference.classList.remove('text-emerald-600');
            displayDifference.classList.add('text-red-600');
            
            statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 mt-2 uppercase tracking-wide transition-all duration-300';
            statusBadge.innerHTML = '<i class="material-icons text-[14px] mr-1">warning</i> BELUM SEIMBANG';
            
            if(btnFinish) btnFinish.disabled = true;
            
            reconProgress.classList.remove('bg-emerald-500');
            reconProgress.classList.add('bg-red-500');
            reconProgress.style.width = '50%'; // Visual indikator belum selesai
        }
    }

    checkboxes.forEach(cb => cb.addEventListener('change', calculate));

    // Check All Logic
    const checkAllDebit = document.getElementById('check-all-debit');
    if(checkAllDebit){
        checkAllDebit.addEventListener('change', function() {
            document.querySelectorAll('.check-debit:not([disabled])').forEach(cb => cb.checked = this.checked);
            calculate();
        });
    }
    const checkAllCredit = document.getElementById('check-all-credit');
    if(checkAllCredit){
        checkAllCredit.addEventListener('change', function() {
            document.querySelectorAll('.check-credit:not([disabled])').forEach(cb => cb.checked = this.checked);
            calculate();
        });
    }

    // Submit Confirmation
    const form = document.getElementById('recon-form');
    if(btnFinish && form) {
        btnFinish.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Selesaikan Rekonsiliasi?',
                html: "Pastikan semua data sudah benar.<br>Aksi ini akan <b>mengunci periode ini</b>.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesaikan!',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'bg-white rounded-xl border border-slate-100 shadow-2xl p-6',
                    title: 'text-xl font-bold text-slate-800',
                    htmlContainer: 'text-sm text-slate-600 mt-2',
                    confirmButton: 'px-5 py-2.5 rounded-lg font-bold shadow-md',
                    cancelButton: 'px-5 py-2.5 rounded-lg font-bold hover:bg-slate-100 text-slate-600'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'reconcile';
                    form.appendChild(actionInput);
                    form.submit();
                }
            });
        });
    }

    // Init Calculation
    calculate();
});
</script>
@endpush