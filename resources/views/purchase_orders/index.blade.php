@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Pesanan Pembelian</h2>
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Buat Pesanan Baru
        </a>
    </div>

    {{-- FORM PENCARIAN & FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('purchase-orders.index') }}" method="GET">
    <div class="row g-2 align-items-center">
        {{-- Baris 1: Pencarian Utama --}}
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Cari No. PO / Supplier..." value="{{ request('search') }}">
        </div>
        <div class="col-md-4">
            <input type="text" name="supplier_invoice_number" class="form-control" placeholder="Cari No. Faktur Supplier..." value="{{ request('supplier_invoice_number') }}">
        </div>
        <div class="col-md-4">
            <select name="sort" class="form-select">
                <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Urutkan: Terbaru</option>
                <option value="terlama" @selected(request('sort') == 'terlama')>Urutkan: Terlama</option>
                <option value="supplier_az" @selected(request('sort') == 'supplier_az')>Urutkan: Supplier A-Z</option>
                <option value="supplier_za" @selected(request('sort') == 'supplier_za')>Urutkan: Supplier Z-A</option>
            </select>
        </div>

        {{-- Baris 2: Filter Tanggal & Status --}}
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text" style="font-size: 0.8rem;">Tgl. Pesan:</span>
                <input type="date" name="order_date" class="form-control" value="{{ request('order_date') }}" title="Filter Tanggal Pesanan">
            </div>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text" style="font-size: 0.8rem;">Jatuh Tempo:</span>
                <input type="date" name="due_date" class="form-control" value="{{ request('due_date') }}" title="Filter Tanggal Jatuh Tempo">
            </div>
        </div>
        <div class="col-md-2">
            <select name="payment_status" class="form-select">
                <option value="">-- Status Bayar --</option>
                <option value="unpaid" @selected(request('payment_status') == 'unpaid')>Belum Lunas</option>
                <option value="partially_paid" @selected(request('payment_status') == 'partially_paid')>Cicil</option>
                <option value="paid" @selected(request('payment_status') == 'paid')>Lunas</option>
            </select>
        </div>

        {{-- Tombol Aksi --}}
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-dark w-100">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary w-100" title="Reset Filter">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </div>
</form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>No. Pesanan</th>
                            <th>No. Faktur Supplier</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-end">Total Tagihan</th>
                            <th class="text-end">Sisa Tagihan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchaseOrders as $po)
                        <tr>
                            <td>{{ $loop->iteration + $purchaseOrders->firstItem() - 1 }}</td>
                            <td>
                                <a href="{{ route('purchase-orders.show', $po->po_id) }}" class="text-decoration-none fw-semibold">{{ $po->po_number ?? 'PO-' . $po->po_id }}</a>
                            </td>
                             <td>{{ $po->supplier_invoice_number ?? '-' }}</td>
                            <td>{{ $po->supplier->supplier_name ?? 'N/A' }}</td>
                            <td>{{ optional($po->order_date)->format('d M Y') }}</td>
                            <td>
                                @if($po->due_date)
                                    {{ optional($po->due_date)->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                            {{-- KOLOM BARU: SISA TAGIHAN --}}
                            <td class="text-end fw-bold {{ ($po->total_amount - ($po->amount_paid ?? 0)) > 0 ? 'text-danger' : 'text-success' }}">
                                Rp {{ number_format(($po->total_amount - ($po->amount_paid ?? 0)), 0, ',', '.') }}
                            </td>
                            {{-- KOLOM BARU: STATUS GANDA --}}
                            <td class="text-center">
                                <div>
                                    @if($po->status == 'completed') <span class="badge bg-success">Barang Diterima</span>
                                    @elseif($po->status == 'cancelled') <span class="badge bg-dark">Dibatalkan</span>
                                    @else <span class="badge bg-secondary">{{ Str::title($po->status) }}</span>
                                    @endif
                                </div>
                                <div class="mt-1">
                                     @if($po->payment_status == 'paid') <span class="badge bg-primary">Lunas</span>
                                     @elseif($po->payment_status == 'partially_paid') <span class="badge bg-info text-dark">Cicil</span>
                                     @else <span class="badge bg-danger">Belum Lunas</span>
                                     @endif
                                </div>
                            </td>
                            <td class="text-center">
    <div class="d-flex justify-content-center gap-1">
        {{-- Tombol Detail (diubah ke style outline) --}}
        <a href="{{ route('purchase-orders.show', $po->po_id) }}" class="btn btn-sm btn-outline-info" title="Detail">
            <i class="bi bi-eye"></i>
        </a>
        
        @if(in_array($po->status, ['draft', 'ordered']))
            {{-- Tombol Edit (diubah ke style outline) --}}
            <a href="{{ route('purchase-orders.edit', $po->po_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                <i class="bi bi-pencil-square"></i>
            </a>
            
            {{-- Tombol Batalkan (sudah benar) --}}
            <form action="{{ route('purchase-orders.cancel', $po->po_id) }}" method="POST" class="d-inline cancel-form">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan">
                    <i class="bi bi-x-circle"></i>
                </button>
            </form>
        @endif
    </div>
</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center">Tidak ada data pesanan pembelian ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-center">
        {{ $purchaseOrders->appends(request()->query())->links() }}
    </div>
</div>
@endsection