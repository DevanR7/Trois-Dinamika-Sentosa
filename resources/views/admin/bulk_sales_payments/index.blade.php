@extends('admin.layouts.app')

@section('title', 'Riwayat Pembayaran Massal')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Riwayat Bulk Payment</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Daftar pembayaran gabungan dari pelanggan.</p>
    </div>
    <div class="flex gap-2">
        @can('review-batch-payments')
            <a href="{{ route('admin.bulk-sales-payments.pending') }}" class="btn btn-secondary relative">
                <i class="material-icons text-lg mr-1">pending_actions</i>
                Verifikasi Pending
            </a>
        @endcan
        @can('create-batch-payments')
            <a href="{{ route('admin.bulk-sales-payments.create') }}" class="btn btn-primary">
                <i class="material-icons text-lg mr-1">add</i>
                Buat Pembayaran Baru
            </a>
        @endcan
    </div>
</div>

{{-- SECTION FILTER --}}
<div class="card mb-6">
    <div class="card-body">
        <form action="{{ route('admin.bulk-sales-payments.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search --}}
                <div class="md:col-span-4">
                    <label class="form-label">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-lg">search</i>
                        </div>
                        <input type="text" name="search" class="form-input pl-10" 
                               placeholder="Cari ID, Ref, atau Nama Klien..." 
                               value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Tanggal Mulai --}}
                <div class="md:col-span-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-input" 
                           value="{{ request('start_date') }}">
                </div>

                {{-- Tanggal Sampai --}}
                <div class="md:col-span-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-input" 
                           value="{{ request('end_date') }}">
                </div>

                {{-- Status --}}
                <div class="md:col-span-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Verifikasi</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="btn btn-primary w-full justify-center">
                        <i class="material-icons text-lg">filter_list</i>
                    </button>
                    <a href="{{ route('admin.bulk-sales-payments.index') }}" 
                       class="btn btn-secondary w-full justify-center" 
                       title="Reset Filter">
                        <i class="material-icons text-lg text-slate-500">refresh</i>
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- SECTION TABLE --}}
<div class="card">
    <div class="table-container">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Metode</th>
                    <th class="text-right">Total Bayar</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bulkSalesPayments as $payment)
                    <tr>
                        <td>
                            <span class="font-mono font-medium text-indigo-600 dark:text-indigo-400">
                                #{{ $payment->bulk_sales_payment_id }}
                            </span>
                            @if($payment->reference_number)
                                <div class="text-xs text-slate-500 mt-0.5">Ref: {{ $payment->reference_number }}</div>
                            @endif
                        </td>
                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                        <td>
                            <div class="font-medium">{{ $payment->client->client_name }}</div>
                        </td>
                        <td>
                            @if($payment->paymentMethod)
                                {{ $payment->paymentMethod->name }}
                            @else
                                <span class="text-slate-400 italic">Kredit/Deposit</span>
                            @endif
                        </td>
                        <td class="text-right font-bold text-slate-700 dark:text-slate-200">
                            Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                        </td>
                        <td>
                            @if($payment->status === 'completed' || $payment->status === 'approved')
                                <span class="badge badge-success">Selesai</span>
                            @elseif($payment->status === 'pending_verification' || $payment->status === 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @elseif($payment->status === 'rejected' || $payment->status === 'failed')
                                <span class="badge badge-danger">Ditolak</span>
                            @else
                                <span class="badge badge-secondary">{{ $payment->status }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            {{-- BUTTON SHOW DIPERBAIKI (Tidak kecil lagi) --}}
                            <a href="{{ route('admin.bulk-sales-payments.show', $payment->bulk_sales_payment_id) }}" 
                               class="btn btn-secondary btn-sm h-8 w-8 p-0 rounded-lg inline-flex items-center justify-center"
                               data-tooltip-target="tooltip-view-{{ $payment->bulk_sales_payment_id }}">
                                <i class="material-icons text-base">visibility</i>
                            </a>
                            <div id="tooltip-view-{{ $payment->bulk_sales_payment_id }}" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                                Detail
                                <div class="tooltip-arrow" data-popper-arrow></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="material-icons text-4xl mb-2 text-slate-300">receipt_long</i>
                                <p>Data pembayaran tidak ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($bulkSalesPayments->hasPages())
        <div class="card-footer">
            {{ $bulkSalesPayments->links() }}
        </div>
    @endif
</div>
@endsection