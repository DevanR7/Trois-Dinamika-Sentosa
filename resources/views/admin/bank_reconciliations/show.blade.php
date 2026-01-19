@extends('admin.layouts.app')

@section('title', 'Worksheet Rekonsiliasi')

@section('content')
{{-- 
    DATA UNTUK ALPINE JS 
    Kita persiapkan data JSON dari PHP agar Alpine bisa menghitung secara real-time.
    Menggabungkan item yang sudah cleared (draft) dan yang baru (unreconciled).
--}}
@php
    $allTransactions = collect([]);

    // 1. Cleared (Sudah dicentang sebelumnya/Draft)
    foreach($cleared_deposits as $item) {
        $allTransactions->push(['id' => $item->ledger_id, 'amount' => (float)$item->debit, 'type' => 'debit', 'checked' => true]);
    }
    foreach($cleared_payments as $item) {
        $allTransactions->push(['id' => $item->ledger_id, 'amount' => (float)$item->credit, 'type' => 'credit', 'checked' => true]);
    }

    // 2. Unreconciled (Belum dicentang)
    foreach($unreconciled_deposits as $item) {
        $allTransactions->push(['id' => $item->ledger_id, 'amount' => (float)$item->debit, 'type' => 'debit', 'checked' => false]);
    }
    foreach($unreconciled_payments as $item) {
        $allTransactions->push(['id' => $item->ledger_id, 'amount' => (float)$item->credit, 'type' => 'credit', 'checked' => false]);
    }
@endphp

<div class="flex flex-col gap-6" 
     x-data="reconciliationWorksheet(@json($allTransactions), {{ $statementBalance }}, {{ $closingBalance }})">

    {{-- HEADER STATISTIK (Sticky agar selalu terlihat saat scroll) --}}
    <div class="sticky top-[70px] z-20 bg-white dark:bg-[#0f172a] shadow-md rounded-xl border border-slate-200 dark:border-slate-700 p-4 mb-2 transition-all">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            {{-- Kiri: Info Akun --}}
            <div class="min-w-[200px]">
                <div class="flex items-center gap-2 text-xs text-slate-500 uppercase tracking-wider font-bold">
                    <span>{{ $bankReconciliation->account->account_name }}</span>
                    <span class="text-slate-300">|</span>
                    <span>{{ $bankReconciliation->statement_date->format('d M Y') }}</span>
                </div>
                <div class="text-sm mt-1 text-slate-600 dark:text-slate-400">
                    Target Saldo Bank: 
                    <span class="font-mono font-bold text-slate-800 dark:text-white">
                        {{ number_format($statementBalance, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- Tengah: Kalkulator Realtime --}}
            <div class="flex gap-4 sm:gap-6 text-center text-xs sm:text-sm">
                <div>
                    <div class="text-[10px] uppercase text-slate-400 font-bold">Saldo Buku</div>
                    <div class="font-mono text-slate-600 dark:text-slate-300">
                        {{ number_format($closingBalance, 2, ',', '.') }}
                    </div>
                </div>
                <div class="hidden sm:block text-slate-300 text-xl font-light">-</div>
                <div>
                    <div class="text-[10px] uppercase text-slate-400 font-bold">Belum Cair (Uncleared)</div>
                    <div class="font-mono font-bold text-amber-600">
                        <span x-text="formatMoney(totalUnclearedNet)"></span>
                    </div>
                </div>
                <div class="hidden sm:block text-slate-300 text-xl font-light">=</div>
                <div>
                    <div class="text-[10px] uppercase text-slate-400 font-bold">Saldo Terevisi</div>
                    <div class="font-mono font-bold text-indigo-600">
                        <span x-text="formatMoney(currentClearedBalance)"></span>
                    </div>
                </div>
            </div>

            {{-- Kanan: Difference Indicator --}}
            <div class="flex flex-col items-end min-w-[150px]">
                <div class="text-[10px] uppercase text-slate-400 font-bold mb-1">Selisih (Difference)</div>
                <div class="px-4 py-1.5 rounded-lg border-2 font-mono font-bold text-lg transition-colors duration-300 w-full text-right"
                     :class="isBalanced ? 'bg-emerald-50 border-emerald-500 text-emerald-600' : 'bg-rose-50 border-rose-500 text-rose-600 animate-pulse'">
                    <span x-text="formatMoney(difference)"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- WORKSHEET AREA --}}
    <form action="{{ route('admin.bank-reconciliations.update', $bankReconciliation->reconciliation_id) }}" method="POST" id="reconForm">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            {{-- KOLOM KIRI: DEPOSITS (Uang Masuk / Debit) --}}
            <div class="card h-full flex flex-col">
                <div class="card-header bg-emerald-50/50 dark:bg-emerald-900/10 border-b border-emerald-100 dark:border-emerald-800/30">
                    <h3 class="card-header-title text-emerald-800 dark:text-emerald-400 flex justify-between items-center w-full">
                        <span>Penerimaan (Debits)</span>
                        <span class="text-xs bg-emerald-200 dark:bg-emerald-800 px-2 py-1 rounded text-emerald-900 dark:text-white" x-text="countChecked('debit') + ' terpilih'"></span>
                    </h3>
                </div>
                <div class="p-0 flex-1 overflow-y-auto max-h-[600px] custom-scrollbar">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900 sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="w-10 px-4 py-3 text-center">
                                    <input type="checkbox" class="form-check-input" @change="toggleAll('debit', $event.target.checked)">
                                </th>
                                <th class="px-2 py-3 font-semibold text-slate-500">Tgl & Ket</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-500">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            {{-- Gabungkan list cleared & unreconciled --}}
                            @php 
                                $allDeposits = $cleared_deposits->concat($unreconciled_deposits)->sortBy('entry_date'); 
                            @endphp
                            
                            @forelse($allDeposits as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer group"
                                    :class="isChecked({{ $row->ledger_id }}) ? 'bg-emerald-50/30 dark:bg-emerald-900/10' : ''"
                                    @click="toggleItem({{ $row->ledger_id }}, 'debit')">
                                    
                                    <td class="px-4 py-3 text-center">
                                        {{-- Checkbox Alpine Binding --}}
                                        <input type="checkbox" 
                                               name="cleared_entries[]" 
                                               value="{{ $row->ledger_id }}"
                                               class="form-check-input pointer-events-none"
                                               :checked="isChecked({{ $row->ledger_id }})">
                                    </td>
                                    <td class="px-2 py-3">
                                        <div class="font-mono text-xs text-slate-400">{{ $row->entry_date->format('d/m/Y') }}</div>
                                        <div class="font-medium text-slate-700 dark:text-slate-200 truncate max-w-[200px]" title="{{ $row->description }}">
                                            {{ $row->description }}
                                        </div>
                                        @if($row->reference)
                                            <div class="text-[10px] text-indigo-500 font-mono">
                                                Ref: {{ class_basename($row->reference_type) }} #{{ $row->reference_id }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-medium text-slate-700 dark:text-slate-300">
                                        {{ number_format($row->debit, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="p-6 text-center text-slate-400 italic">Tidak ada data penerimaan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- KOLOM KANAN: PAYMENTS (Uang Keluar / Kredit) --}}
            <div class="card h-full flex flex-col">
                <div class="card-header bg-rose-50/50 dark:bg-rose-900/10 border-b border-rose-100 dark:border-rose-800/30">
                    <h3 class="card-header-title text-rose-800 dark:text-rose-400 flex justify-between items-center w-full">
                        <span>Pengeluaran (Credits)</span>
                        <span class="text-xs bg-rose-200 dark:bg-rose-800 px-2 py-1 rounded text-rose-900 dark:text-white" x-text="countChecked('credit') + ' terpilih'"></span>
                    </h3>
                </div>
                <div class="p-0 flex-1 overflow-y-auto max-h-[600px] custom-scrollbar">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900 sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="w-10 px-4 py-3 text-center">
                                    <input type="checkbox" class="form-check-input" @change="toggleAll('credit', $event.target.checked)">
                                </th>
                                <th class="px-2 py-3 font-semibold text-slate-500">Tgl & Ket</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-500">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @php 
                                $allPayments = $cleared_payments->concat($unreconciled_payments)->sortBy('entry_date'); 
                            @endphp

                            @forelse($allPayments as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer group"
                                    :class="isChecked({{ $row->ledger_id }}) ? 'bg-rose-50/30 dark:bg-rose-900/10' : ''"
                                    @click="toggleItem({{ $row->ledger_id }}, 'credit')">
                                    
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" 
                                               name="cleared_entries[]" 
                                               value="{{ $row->ledger_id }}"
                                               class="form-check-input pointer-events-none"
                                               :checked="isChecked({{ $row->ledger_id }})">
                                    </td>
                                    <td class="px-2 py-3">
                                        <div class="font-mono text-xs text-slate-400">{{ $row->entry_date->format('d/m/Y') }}</div>
                                        <div class="font-medium text-slate-700 dark:text-slate-200 truncate max-w-[200px]" title="{{ $row->description }}">
                                            {{ $row->description }}
                                        </div>
                                        @if($row->reference)
                                            <div class="text-[10px] text-indigo-500 font-mono">
                                                Ref: {{ class_basename($row->reference_type) }} #{{ $row->reference_id }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-medium text-slate-700 dark:text-slate-300">
                                        {{ number_format($row->credit, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="p-6 text-center text-slate-400 italic">Tidak ada data pengeluaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ACTION BAR (Sticky Bottom) --}}
        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white dark:bg-[#0f172a] border-t border-slate-200 dark:border-slate-800 shadow-lg z-30 lg:ml-64 transition-all duration-300">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="text-sm text-slate-500 hidden sm:block">
                    <i class="material-icons text-[16px] align-text-bottom mr-1">info</i>
                    Centang transaksi yang muncul di Rekening Koran Bank.
                </div>
                <div class="flex gap-3">
                    @if($bankReconciliation->status === 'draft')
                        <button type="submit" name="action" value="save_draft" class="btn btn-secondary">
                            <i class="material-icons text-[18px] mr-1">save</i> Simpan Draft
                        </button>
                        
                        <button type="button" @click="submitReconcile()" class="btn btn-primary" :disabled="!isBalanced">
                            <i class="material-icons text-[18px] mr-1">check_circle</i> Selesai & Rekonsiliasi
                        </button>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Hidden Input Action --}}
        <input type="hidden" name="action" id="formAction" value="save_draft">
    </form>
    
    {{-- Spacer agar konten tidak tertutup fixed bottom bar --}}
    <div class="h-20"></div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reconciliationWorksheet', (transactions, statementBalance, bookBalance) => ({
            transactions: transactions, // Array object dari PHP
            statementBalance: parseFloat(statementBalance),
            bookBalance: parseFloat(bookBalance),
            
            // Helpers
            isChecked(id) {
                return this.transactions.find(t => t.id === id)?.checked || false;
            },

            toggleItem(id, type) {
                const item = this.transactions.find(t => t.id === id);
                if(item) {
                    item.checked = !item.checked;
                }
            },

            toggleAll(type, status) {
                this.transactions.forEach(t => {
                    if (t.type === type) t.checked = status;
                });
            },

            countChecked(type) {
                return this.transactions.filter(t => t.type === type && t.checked).length;
            },

            // --- LOGIKA REKONSILIASI ---
            // Saldo Terevisi (Adjusted Balance) = Saldo Buku Akhir - Transaksi yang belum cair (Uncleared)
            // Transaksi yang belum cair = Yang TIDAK dicentang
            
            get totalUnclearedNet() {
                // Uncleared Deposit (Debit yang TIDAK dicentang)
                const unclearedDeposits = this.transactions
                    .filter(t => t.type === 'debit' && !t.checked)
                    .reduce((sum, t) => sum + t.amount, 0);
                
                // Uncleared Payment (Credit yang TIDAK dicentang)
                const unclearedPayments = this.transactions
                    .filter(t => t.type === 'credit' && !t.checked)
                    .reduce((sum, t) => sum + t.amount, 0);

                // Net Uncleared = Debit - Kredit
                return unclearedDeposits - unclearedPayments;
            },

            get currentClearedBalance() {
                // Saldo Terevisi = Saldo Buku - (Uncleared Deposit - Uncleared Payment)
                return this.bookBalance - this.totalUnclearedNet;
            },

            get difference() {
                // Selisih = Saldo Bank (Target) - Saldo Terevisi
                return this.statementBalance - this.currentClearedBalance;
            },

            get isBalanced() {
                // Toleransi selisih floating point
                return Math.abs(this.difference) < 0.01;
            },

            formatMoney(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 2
                }).format(amount);
            },

            submitReconcile() {
                if (!this.isBalanced) {
                    showToast('Selisih belum nol. Tidak dapat menyelesaikan rekonsiliasi.', 'error');
                    return;
                }
                
                window.confirmDialog({
                    title: 'Selesaikan Rekonsiliasi?',
                    text: 'Pastikan data sudah benar. Periode ini akan dikunci dan tidak dapat diubah lagi.',
                    icon: 'warning',
                    confirmText: 'Ya, Selesaikan',
                    confirmColor: 'success'
                }).then(result => {
                    if (result.isConfirmed) {
                        document.getElementById('formAction').value = 'reconcile';
                        document.getElementById('reconForm').submit();
                    }
                });
            }
        }));
    });
</script>
@endpush
@endsection