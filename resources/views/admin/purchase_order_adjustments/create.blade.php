@extends('admin.layouts.app')

@section('title', 'Buat Penyesuaian Pesanan Pembelian')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="page-title">Penyesuaian Pesanan Pembelian</h2>
                <p class="text-sm text-slate-500 mt-1">Pilih PO dan metode penyesuaian (Manual atau Otomatis).</p>
            </div>
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
                <i class="material-icons text-lg">arrow_back</i> Kembali
            </a>
        </div>

        {{-- Selection Form --}}
        <div class="card p-6" x-data="{ 
            selectedPo: '{{ $preselectedPurchaseOrderId ?? '' }}',
            method: 'manual', // manual or auto
            
            submitForm() {
                if (!this.selectedPo) {
                    showToast('Silakan pilih Nomor PO terlebih dahulu.', 'error');
                    return;
                }
                
                let url = '';
                if (this.method === 'manual') {
                    url = `{{ route('admin.purchase-order-adjustments.create.manual', ':id') }}`;
                } else {
                    url = `{{ route('admin.purchase-order-adjustments.create.auto', ':id') }}`;
                }
                
                url = url.replace(':id', this.selectedPo);
                window.location.href = url;
            }
        }">
            
            <div class="grid grid-cols-1 gap-6">
                
                {{-- 1. Pilih PO --}}
                <div>
                    <label class="form-label mb-2">Cari Nomor Purchase Order</label>
                    <select x-model="selectedPo" class="tom-select w-full" placeholder="Cari No. PO atau Supplier...">
                        <option value="">Pilih Purchase Order...</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->po_id }}" {{ $preselectedPurchaseOrderId == $po->po_id ? 'selected' : '' }}>
                                {{ $po->po_number }} - {{ $po->supplier->supplier_name }} 
                                ({{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Pilih Metode --}}
                <div>
                    <label class="form-label mb-3">Pilih Jenis Penyesuaian</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        {{-- Option Manual --}}
                        <label class="relative flex flex-col p-4 bg-white dark:bg-slate-800 border-2 rounded-xl cursor-pointer transition-all hover:border-indigo-500"
                            :class="method === 'manual' ? 'border-indigo-600 bg-indigo-50/30 dark:bg-indigo-900/20' : 'border-slate-200 dark:border-slate-700'">
                            <input type="radio" name="method" value="manual" x-model="method" class="sr-only">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                    <i class="material-icons">edit_note</i>
                                </div>
                                <span class="font-bold text-slate-800 dark:text-white">Penyesuaian Manual</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Input nominal koreksi secara langsung (Credit/Debit Note) tanpa mengubah detail item barang. Cocok untuk diskon susulan atau biaya tak terduga.
                            </p>
                        </label>

                        {{-- Option Auto --}}
                        <label class="relative flex flex-col p-4 bg-white dark:bg-slate-800 border-2 rounded-xl cursor-pointer transition-all hover:border-indigo-500"
                            :class="method === 'auto' ? 'border-indigo-600 bg-indigo-50/30 dark:bg-indigo-900/20' : 'border-slate-200 dark:border-slate-700'">
                            <input type="radio" name="method" value="auto" x-model="method" class="sr-only">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                                    <i class="material-icons">auto_fix_high</i>
                                </div>
                                <span class="font-bold text-slate-800 dark:text-white">Revisi Detail (Otomatis)</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Edit harga, jumlah, atau diskon pada item barang. Sistem akan menghitung selisih otomatis. Cocok untuk revisi harga atau retur parsial.
                            </p>
                        </label>

                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="submitForm()" class="btn btn-primary btn-lg px-8">
                        Lanjut <i class="material-icons text-lg ml-1">arrow_forward</i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection