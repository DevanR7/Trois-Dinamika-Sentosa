@extends('admin.layouts.app')

@section('title', 'Riwayat Pembayaran Massal')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Riwayat Pembayaran Massal</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar seluruh transaksi pembayaran massal (Bulk Payment).</p>
        </div>
        <div class="flex gap-3">
            {{-- Tombol ke Halaman Verifikasi --}}
            <a href="{{ route('admin.bulk-sales-payments.pending') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">hourglass_top</i>
                <span>Verifikasi Pending</span>
            </a>

            {{-- Tombol Buat Baru --}}
            <a href="{{ route('admin.bulk-sales-payments.create') }}" class="h-[48px] px-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
                <span>Buat Baru</span>
            </a>
        </div>
    </div>

    {{-- TABEL DATA --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">ID Batch</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Klien</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Total Nominal</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Metode</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($bulkSalesPayments as $bulk)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-mono text-indigo-600 font-bold">
                                #{{ $bulk->bulk_sales_payment_id }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-800">{{ $bulk->client->client_name ?? 'N/A' }}</div>
                                <div class="text-xs text-slate-500">Diproses: {{ $bulk->processedByUser->full_name ?? 'System' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $bulk->payment_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-800 text-right font-mono">
                                Rp {{ number_format($bulk->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $bulk->paymentMethod->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($bulk->status == 'completed' || $bulk->status == 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                        Completed
                                    </span>
                                @elseif($bulk->status == 'rejected' || $bulk->status == 'failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                        {{ ucfirst($bulk->status) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                        {{ ucfirst(str_replace('_', ' ', $bulk->status)) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{-- Jika status pending, arahkan ke halaman verifikasi, jika tidak ke detail biasa --}}
                                @if($bulk->status == 'pending_verification')
                                    <a href="{{ route('admin.bulk-sales-payments.showPending', $bulk->bulk_sales_payment_id) }}" 
                                       class="text-amber-600 hover:text-amber-800 font-bold text-xs uppercase tracking-wide">
                                        Verifikasi
                                    </a>
                                @else
                                    <a href="{{ route('admin.bulk-sales-payments.show', $bulk->bulk_sales_payment_id) }}" 
                                       class="text-indigo-600 hover:text-indigo-800 font-bold text-xs uppercase tracking-wide">
                                        Detail
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <i class="material-icons text-4xl mb-2 opacity-20">receipt_long</i>
                                <p class="text-sm">Belum ada riwayat pembayaran massal.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if($bulkSalesPayments->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                {{ $bulkSalesPayments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection