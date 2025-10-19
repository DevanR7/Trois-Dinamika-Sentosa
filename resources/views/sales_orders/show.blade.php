@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        {{-- ✅ BERUBAH: $salesOrder -> $order --}}
        <h2 class="fw-bold mb-0">Detail Pesanan: {{ $order->order_number }}</h2>
    
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>

            {{-- Tombol untuk membuat Invoice dari Sales Order ini --}}
            @can('create', App\Models\SalesInvoice::class)
                {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                @if($order->status !== 'invoiced' && $order->status !== 'rejected')
                    {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                    <a href="{{ route('invoices.createFromOrder', $order) }}" class="btn btn-primary">
                        <i class="bi bi-receipt-cutoff me-1"></i> Buat Invoice
                    </a>
                @endif
            @endcan
            
            {{-- Dropdown untuk Opsi Edit & Hapus --}}
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear"></i> Opsi
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    {{-- Tombol Edit & Hapus hanya muncul jika statusnya belum final --}}
                    {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                    @if (!in_array($order->status, ['invoiced', 'rejected']))
                        {{-- ✅ BERUBAH: $salesOrder -> $order (logika @can sudah benar) --}}
                        @can("update", $order)
                        <li>
                            {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                            <a class="dropdown-item" href="{{ route('sales-orders.edit', $order->order_id) }}">
                                <i class="bi bi-pencil-square me-2"></i> Edit Pesanan
                            </a>
                        </li>
                        @endcan
                        @can("delete", $order)
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                            <form class="delete-form" action="{{ route('sales-orders.destroy', $order->order_id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-trash me-2"></i> Hapus Pesanan
                                    </button>
                                </form>
                        </li>
                        @endcan
                    @endif
                    {{-- Jika tidak ada menu di atas, bisa tampilkan pesan --}}
                    {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                    @if (in_array($order->status, ['invoiced', 'rejected']) && auth()->user()->cannot('update', $order) && auth()->user()->cannot('delete', $order))
                        <li><span class="dropdown-item disabled text-muted">Tidak ada aksi tersedia</span></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- KARTU DETAIL --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            {{-- Info Klien & Status --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold">Klien:</h5>
                    {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                    <p class="mb-0">{{ $order->client->client_name }}</p>
                    <p class="text-muted">{{ $order->client->address ?? 'Alamat tidak tersedia' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                    <p class="mb-1"><strong>Tanggal Pesanan:</strong> {{ optional($order->order_date)->format('d F Y') }}</p>
                    <p class="mb-1"><strong>Sales:</strong> {{ $order->sales->full_name ?? 'N/A' }} ({{ $order->sales->sales_code ?? '' }})</p>
                    <p class="mb-1"><strong>Status:</strong>
                        {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                        @if($order->status == 'invoiced')
                            <span class="badge bg-success">Sudah Dibuat Invoice</span>
                        @elseif($order->status == 'rejected')
                            <span class="badge bg-danger">Ditolak</span>
                        @else
                            <span class="badge bg-secondary">{{ Str::title($order->status) }}</span>
                        @endif
                    </p>
                </div>
            </div>
            <hr>

            {{-- Tabel Rincian Item --}}
            <h5 class="fw-semibold mt-4">Rincian Item Dipesan</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th class="text-center">Kuantitas</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                        @forelse($order->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="text-center">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">Tidak ada item dalam pesanan ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold fs-5">Total Pesanan</td>
                            {{-- ✅ BERUBAH: $salesOrder -> $order --}}
                            <td class="text-end fw-bold fs-5">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- ✅ BERUBAH: $salesOrder -> $order --}}
            @if($order->notes)
            <div class="mt-4">
                <h6 class="fw-semibold">Catatan:</h6>
                <p class="text-muted fst-italic">{{ $order->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
@push('scripts')
{{-- JavaScript di file ini tidak perlu diubah --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.querySelector('.delete-form');

    if (deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault(); 
            
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Pesanan yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    }
});
</script>
@endpush