@extends('admin.layouts.app')

@section('title', 'Verifikasi Pembayaran Massal')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Verifikasi Bulk Payment</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar pembayaran massal yang menunggu konfirmasi (Pending).</p>
        </div>
        <a href="{{ route('admin.bulk-sales-payments.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Buat Pembayaran Baru</span>
        </a>
    </div>

    {{-- LIST CONTAINER --}}
    <div class="space-y-4">
        {{-- UBAH VARIABEL LOOP DI SINI --}}
        @forelse ($pendingBulkPayments as $bulk)
            <div class="dashboard-card p-0 overflow-hidden hover:shadow-lg transition-all duration-300 border-l-4 border-l-amber-400">
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                        
                        {{-- Kolom 1: Info Utama --}}
                        <div class="md:col-span-5 flex items-start gap-4">
                            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center border border-amber-100 flex-shrink-0">
                                <i class="material-icons text-2xl">hourglass_bottom</i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-slate-800 leading-tight mb-1">
                                    {{ $bulk->client->client_name ?? 'N/A' }}
                                </h4>
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <i class="material-icons text-[14px]">event</i>
                                    <span>{{ $bulk->created_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 2: Metode & Nominal --}}
                        <div class="md:col-span-4 border-l border-slate-100 pl-0 md:pl-6">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Metode & Nominal</p>
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ $bulk->paymentMethod->name ?? 'N/A' }}
                                </span>
                                <span class="text-lg font-bold text-emerald-600 font-mono">
                                    Rp {{ number_format($bulk->total_amount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        {{-- Kolom 3: Aksi --}}
                        <div class="md:col-span-3 flex justify-start md:justify-end">
                            {{-- GUNAKAN ID YANG BENAR ($bulk->bulk_sales_payment_id) --}}
                            <a href="{{ route('admin.bulk-sales-payments.showPending', $bulk->bulk_sales_payment_id) }}" 
                               class="h-[42px] px-6 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-lg text-sm font-bold hover:bg-indigo-600 hover:text-white hover:shadow-md transition-all flex items-center justify-center gap-2 group">
                                <span>Proses Verifikasi</span>
                                <i class="material-icons text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="dashboard-card p-12 text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                    <i class="material-icons text-slate-300 text-4xl">check_circle_outline</i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Tidak Ada Data Pending</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-xs">Semua pembayaran massal telah diproses.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $pendingBulkPayments->links() }}
    </div>
</div>
@endsection