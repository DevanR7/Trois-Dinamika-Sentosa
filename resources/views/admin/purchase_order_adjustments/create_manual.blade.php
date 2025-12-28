@extends('admin.layouts.app')

@section('title', 'Penyesuaian Manual PO #' . $purchaseOrder->po_number)

@section('content')
    <div class="max-w-3xl mx-auto pb-20">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="page-title">Penyesuaian Manual</h2>
                <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                    <span class="font-medium text-slate-700 dark:text-white">PO #{{ $purchaseOrder->po_number }}</span>
                    <span>&bull;</span>
                    <span>{{ $purchaseOrder->supplier->supplier_name }}</span>
                </div>
            </div>
            <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="btn btn-secondary">
                <i class="material-icons text-lg">arrow_back</i> Ganti Metode
            </a>
        </div>

        {{-- Main Form --}}
        <form action="{{ route('admin.purchase-order-adjustments.store.manual') }}" method="POST" 
              class="card p-6 space-y-6"
              x-data="{ 
                  type: 'credit_note', 
                  amount: ''
              }">
            @csrf
            
            <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->po_id }}">

            {{-- 1. Tanggal & Tipe --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Tanggal Penyesuaian <span class="text-red-500">*</span></label>
                    <input type="date" name="adjustment_date" value="{{ date('Y-m-d') }}" class="form-input" required>
                </div>
                
                <div>
                    <label class="form-label">Jenis Nota <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            {{-- Perhatikan x-model="type" disini --}}
                            <input type="radio" name="type" value="credit_note" x-model="type" class="peer sr-only">
                            <div class="p-2.5 text-center rounded-lg border border-slate-200 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition-all dark:border-slate-700 dark:peer-checked:bg-emerald-900/20 dark:peer-checked:text-emerald-400">
                                <span class="font-bold text-sm block">Credit Note</span>
                                <span class="text-[10px] text-slate-500">(Potong Hutang)</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="debit_note" x-model="type" class="peer sr-only">
                            <div class="p-2.5 text-center rounded-lg border border-slate-200 peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-700 transition-all dark:border-slate-700 dark:peer-checked:bg-rose-900/20 dark:peer-checked:text-rose-400">
                                <span class="font-bold text-sm block">Debit Note</span>
                                <span class="text-[10px] text-slate-500">(Tambah Hutang)</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- 2. Nominal --}}
            <div>
                <label class="form-label">Nominal Penyesuaian (Rp) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-slate-500 font-bold">Rp</span>
                    </div>
                    <input type="text" 
                           class="form-input pl-10 text-lg font-bold autonumeric" 
                           placeholder="0" required
                           data-a-sep="." data-a-dec="," 
                           autocomplete="off">
                     {{-- Input hidden otomatis dibuat oleh app.js (data-an-synced) --}}
                    <input type="hidden" name="amount">
                </div>
                {{-- Penjelasan Dinamis --}}
                <p class="text-xs text-emerald-600 mt-1" x-show="type === 'credit_note'">
                    <i class="material-icons text-[12px] align-middle">remove_circle_outline</i> Nominal ini akan <b>mengurangi</b> total hutang ke Supplier.
                </p>
                <p class="text-xs text-rose-600 mt-1" x-show="type === 'debit_note'">
                    <i class="material-icons text-[12px] align-middle">add_circle_outline</i> Nominal ini akan <b>menambah</b> total hutang ke Supplier (Biaya tambahan).
                </p>
            </div>

            {{-- 3. Alasan --}}
            <div>
                <label class="form-label">Alasan / Keterangan <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" class="form-input w-full" placeholder="Contoh: Diskon khusus volume pembelian..." required></textarea>
            </div>

            {{-- 4. Overpayment Action (Hanya Tampil Jika CREDIT NOTE) --}}
            <div x-show="type === 'credit_note'" x-transition 
                 class="bg-indigo-50 dark:bg-slate-800 p-4 rounded-lg border border-indigo-100 dark:border-slate-700">
                <label class="form-label mb-2 flex items-center gap-2 text-indigo-700 dark:text-indigo-300">
                    <i class="material-icons text-sm">account_balance_wallet</i>
                    Jika terjadi kelebihan bayar (Overpayment):
                </label>
                <div class="flex flex-col gap-2">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="overpayment_action" value="deposit" class="form-radio text-indigo-600" checked>
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Simpan sebagai Deposit Supplier</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="overpayment_action" value="refund" class="form-radio text-indigo-600">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Biarkan (Akan diproses Refund manual)</span>
                    </label>
                </div>
                <p class="text-[10px] text-slate-500 mt-2 ml-6">
                    Opsi ini berlaku jika PO sudah lunas atau nilai koreksi melebihi sisa hutang.
                </p>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="btn btn-primary w-full md:w-auto">
                    Simpan Penyesuaian
                </button>
            </div>

        </form>
    </div>
@endsection