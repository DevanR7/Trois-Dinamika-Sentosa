@extends('layouts.client')

@section('content')
    <h2 class="fw-bold mb-4">Riwayat Pesanan Penjualan</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Tanggal</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- ✅ BERUBAH: $salesOrders -> $orders --}}
                        @forelse($orders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->order_date->format('d M Y') }}</td>
                                <td class="text-end">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    {{-- Status badge bisa dibuat lebih dinamis seperti di admin --}}
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
                                    {{-- ❗️ PERHATIAN: Nama route ini mungkin perlu diubah nanti --}}
                                    <a href="{{ route('client.orders.show', $order->order_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">Anda belum memiliki riwayat pesanan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                {{-- ✅ BERUBAH: $salesOrders -> $orders --}}
                {{ $orders->links() }}
            </div>
        </div>
    </div>
@endsection