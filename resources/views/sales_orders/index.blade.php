@extends("layouts.app")

@section("content")
    <div class="container-fluid">
        {{-- Header Halaman --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Daftar Pesanan Penjualan</h2>
            <a
                href="{{ route("sales-orders.create") }}"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-circle me-2"></i>
                Buat Pesanan Baru
            </a>
        </div>

        {{-- Notifikasi Sukses --}}
        @if (session("success"))
            <div class="alert alert-success">
                {{ session("success") }}
            </div>
        @endif

        <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('sales-orders.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Cari No. Pesanan / Klien..." value="{{ request('search') }}">
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
        
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Nomor Pesanan</th>
                                <th scope="col">Klien</th>
                                <th scope="col">Sales</th>
                                <th scope="col">Tanggal Pesan</th>
                                <th scope="col" class="text-end">Jumlah</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- ✅ BERUBAH: $salesOrders -> $orders --}}
                            @forelse ($orders as $order)
                                <tr>
                                    <th scope="row">
                                        {{-- ✅ BERUBAH: $salesOrders -> $orders --}}
                                        {{ $loop->iteration + $orders->firstItem() - 1 }}
                                    </th>
                                    <td>
                                        <span class="fw-semibold">
                                            {{ $order->order_number }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $order->client->client_name ?? "N/A" }}
                                    </td>
                                    <td>
                                        {{ $order->sales->full_name ?? "N/A" }}
                                    </td>
                                    <td>
                                        {{ $order->order_date->format("d M Y") }}
                                    </td>
                                    <td class="text-end">
                                        Rp
                                        {{ number_format($order->total_amount, 0, ",", ".") }}
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $statusClass = [
                                                'pending' => 'bg-secondary',
                                                'approved' => 'bg-info text-dark',
                                                'rejected' => 'bg-danger',
                                                'invoiced' => 'bg-success',
                                            ];
                                        @endphp
                                        <span class="badge {{ $statusClass[$order->status] ?? 'bg-light text-dark' }}">
                                            {{ Str::title(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="d-flex justify-content-center gap-2"
                                        >
                                            <a
                                                href="{{ route("sales-orders.show", $order->order_id) }}"
                                                class="btn btn-sm btn-outline-info"
                                                title="Detail"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            {{-- Logika @can sudah benar, karena $order adalah instance dari App\Models\Order --}}
                                            @if (!in_array($order->status, ['invoiced', 'rejected']))
                                                @can("update", $order)
                                                    <a href="{{ route("sales-orders.edit", $order->order_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                @endcan

                                                @can("delete", $order)
                                                    <form class="delete-form" action="{{ route("sales-orders.destroy", $order->order_id) }}" method="POST">
                                                        @csrf
                                                        @method("DELETE")
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <p class="mb-0">
                                            Belum ada pesanan yang dibuat.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 d-flex justify-content-center">
            {{-- ✅ BERUBAH: $salesOrders -> $orders --}}
            {{ $orders->links() }}
        </div>
    </div>
@endsection