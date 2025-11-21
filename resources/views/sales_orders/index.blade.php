@extends("layouts.app")

@section("content")
<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Daftar Pesanan Penjualan</h3>
            <p class="text-muted small mb-0">Kelola order masuk dari pelanggan</p>
        </div>
        <a href="{{ route('sales-orders.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Buat Pesanan Baru
        </a>
    </div>

    {{-- ALERT --}}
    @if (session("success"))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
            <div>{{ session("success") }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FILTER CARD --}}
    <div class="card card-transaction border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('sales-orders.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari No. Pesanan / Klien..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}" title="Filter Tanggal">
                </div>
                <div class="col-md-3">
                    <select name="sort" class="form-select">
                        <option value="terbaru" {{ request('sort', 'terbaru') == 'terbaru' ? 'selected' : '' }}>Urutkan: Terbaru</option>
                        <option value="terlama" {{ request('sort') == 'terlama' ? 'selected' : '' }}>Urutkan: Terlama</option>
                        <option value="klien_az" {{ request('sort') == 'klien_az' ? 'selected' : '' }}>Urutkan: Klien A-Z</option>
                        <option value="klien_za" {{ request('sort') == 'klien_za' ? 'selected' : '' }}>Urutkan: Klien Z-A</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    {{-- TABEL PESANAN --}}
    <div class="card card-transaction border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-transaction align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Nomor Pesanan</th>
                            <th>Klien</th>
                            <th>Sales</th>
                            <th>Tanggal Pesan</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="ps-4 text-muted">{{ $loop->iteration + $orders->firstItem() - 1 }}</td>
                                <td>
                                    <span class="fw-bold text-primary">{{ $order->order_number }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle me-2 d-flex justify-content-center align-items-center text-muted" style="width: 30px; height: 30px;">
                                            <i class="bi bi-building small"></i>
                                        </div>
                                        {{ $order->client->client_name ?? "N/A" }}
                                    </div>
                                </td>
                                <td>{{ $order->sales->full_name ?? "N/A" }}</td>
                                <td>{{ $order->order_date->format("d M Y") }}</td>
                                <td class="text-end fw-bold text-dark">
                                    Rp {{ number_format($order->total_amount, 0, ",", ".") }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusClass = [
                                            'pending' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                            'approved' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                            'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                            'invoiced' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                        ];
                                        $badgeClass = $statusClass[$order->status] ?? 'bg-light text-dark border';
                                    @endphp
                                    <span class="badge {{ $badgeClass }} rounded-pill px-3">
                                        {{ Str::title(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    {{-- Menggunakan d-flex gap-1 agar rapi --}}
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('sales-orders.show', $order->order_id) }}" class="btn btn-sm btn-light border text-primary shadow-sm" title="Detail" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if (!in_array($order->status, ['invoiced', 'rejected']))
                                            @can("update", $order)
                                                <a href="{{ route('sales-orders.edit', $order->order_id) }}" class="btn btn-sm btn-light border text-warning shadow-sm" title="Edit" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            @endcan

                                            @can("delete", $order)
                                                <form class="delete-form d-inline" action="{{ route('sales-orders.destroy', $order->order_id) }}" method="POST">
                                                    @csrf @method("DELETE")
                                                    <button type="submit" class="btn btn-sm btn-light border text-danger shadow-sm" title="Hapus" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-cart-x fs-1 d-block mb-2 opacity-25"></i>
                                    Belum ada pesanan yang dibuat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $orders->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Hapus Pesanan?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
@endpush