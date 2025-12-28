@extends('admin.layouts.app')

@section('title', 'Penyesuaian Manual')

@section('content')

    <div class="max-w-3xl mx-auto">
        
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Penyesuaian Manual</h1>
                <p class="page-subtitle">Invoice: <strong>{{ $invoice->invoice_number }}</strong></p>
            </div>
            <a href="{{ route('admin.invoice-adjustments.create') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Ganti Metode
            </a>
        </div>

        <form action="{{ route('admin.invoice-adjustments.store.manual') }}" method="POST">
            @csrf
            <input type="hidden" name="sales_invoice_id" value="{{ $invoice->invoice_id }}">

            <div class="space-y-6">
                
                {{-- INFO INVOICE --}}
                <div class="card bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <div class="card-body grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="block text-xs text-slate-400 font-bold uppercase">Pelanggan</span>
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $invoice->client->client_name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-400 font-bold uppercase">Total Tagihan</span>
                            <span class="font-medium text-slate-700 dark:text-slate-200">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-400 font-bold uppercase">Sudah Dibayar</span>
                            <span class="font-medium text-emerald-600">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-400 font-bold uppercase">Sisa Tagihan</span>
                            <span class="font-bold text-rose-600">Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- FORM INPUT --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Detail Koreksi</h3>
                    </div>
                    <div class="card-body space-y-6" x-data="{ type: 'credit_note' }">

                        {{-- Tanggal --}}
                        <div>
                            <label class="form-label label-required">Tanggal Penyesuaian</label>
                            <input type="date" name="adjustment_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                        </div>

                        {{-- Tipe & Nominal --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- Tipe --}}
                            <div>
                                <label class="form-label label-required">Jenis Nota</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="type" value="credit_note" class="peer sr-only" x-model="type">
                                        <div class="p-3 rounded-xl border border-slate-200 text-center transition-all hover:bg-slate-50 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700">
                                            <div class="font-bold text-sm">Credit Note</div>
                                            <div class="text-[10px]">Potongan / Diskon</div>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="type" value="debit_note" class="peer sr-only" x-model="type">
                                        <div class="p-3 rounded-xl border border-slate-200 text-center transition-all hover:bg-slate-50 peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-700">
                                            <div class="font-bold text-sm">Debit Note</div>
                                            <div class="text-[10px]">Tambahan Biaya</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Nominal --}}
                            <div>
                                <label class="form-label label-required">Nominal Penyesuaian</label>
                                <div class="input-group">
                                    <span class="input-group-text font-bold" 
                                          :class="type === 'credit_note' ? 'text-emerald-600' : 'text-rose-600'" 
                                          x-text="type === 'credit_note' ? '- Rp' : '+ Rp'">Rp</span>
                                    <input type="text" name="amount" class="form-input text-right font-bold text-lg autonumeric" placeholder="0" required>
                                </div>
                                <div class="form-hint" x-text="type === 'credit_note' ? 'Mengurangi total tagihan.' : 'Menambah total tagihan.'"></div>
                            </div>

                        </div>

                        {{-- Alasan --}}
                        <div>
                            <label class="form-label label-required">Alasan Koreksi</label>
                            <textarea name="reason" class="form-textarea" rows="3" placeholder="Contoh: Diskon khusus akhir tahun, Koreksi salah harga, dll..." required></textarea>
                        </div>

                        {{-- Kelebihan Bayar Action --}}
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-100" x-show="type === 'credit_note'">
                            <div class="flex items-start gap-3">
                                <i class="material-icons text-amber-500 mt-0.5">info</i>
                                <div>
                                    <p class="text-sm font-bold text-amber-800 mb-2">Jika terjadi Kelebihan Bayar (Overpayment)</p>
                                    <div class="flex flex-col gap-2">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="overpayment_action" value="deposit" class="form-radio text-amber-600" checked>
                                            <span class="ml-2 text-sm text-slate-700">Simpan sebagai <b>Deposit Pelanggan</b> (Disarankan)</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="overpayment_action" value="refund" class="form-radio text-amber-600">
                                            <span class="ml-2 text-sm text-slate-700">Biarkan (Akan diproses Refund Manual nanti)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex justify-end pt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="material-icons text-sm mr-2">save</i> Simpan Penyesuaian
                    </button>
                </div>

            </div>
        </form>
    </div>

@endsection