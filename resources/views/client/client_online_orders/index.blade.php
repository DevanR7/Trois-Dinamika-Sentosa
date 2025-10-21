@extends('layouts.client')

@section('content')
    <h2 class="fw-bold mb-4">Riwayat Pesanan Online Saya</h2> {{-- Judul diubah --}}

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

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
                        {{-- Gunakan variabel $myOrders --}}
                        @forelse($myOrders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->order_date->format('d M Y') }}</td>
                                <td class="text-end">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    {{-- Status badge --}}
                                    @php
                                        $statusClass = [
                                            'pending_review' => 'bg-info text-dark', // Status baru
                                            'pending' => 'bg-secondary',
                                            'approved' => 'bg-primary', // Ubah warna jika perlu
                                            'rejected' => 'bg-danger',
                                            'invoiced' => 'bg-success',
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusClass[$order->status] ?? 'bg-light text-dark' }}">
                                        {{ Str::title(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    {{-- Link detail ke route baru --}}
                                    <a href="{{ route('client.client-orders.show', $order->order_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    {{-- Tambah tombol batal jika status memungkinkan --}}
                                    {{-- @if(in_array($order->status, ['pending', 'pending_review']))
                                        <form action="#" method="POST" class="d-inline"> @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan pesanan ini?')"><i class="bi bi-x-circle"></i> Batal</button>
                                        </form>
                                    @endif --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">Anda belum memiliki riwayat pesanan online.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                {{-- Gunakan variabel $myOrders --}}
                {{ $myOrders->links() }}
            </div>
        </div>
    </div>
@endsection