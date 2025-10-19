@extends('layouts.client')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"> {{-- Tambah flex-wrap & gap --}}
        <a href="{{ route('client.orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
        <h2 class="fw-bold mb-0">Detail Pesanan: {{ $order->order_number }}</h2>

        {{-- ✅ TOMBOL AJUKAN PERUBAHAN BARU --}}
        <div class="ms-auto"> {{-- Agar tombol di kanan --}}
            @php
                // Cek apakah boleh mengajukan perubahan
                $canRequestChange = in_array($order->status, ['pending', 'approved']) && !$order->changeRequests()->where('status', 'pending')->exists();
            @endphp
            @if($canRequestChange)
                <a href="{{ route('client.orders.requestChange.create', $order->order_id) }}" class="btn btn-warning">
                    <i class="bi bi-pencil-fill me-1"></i> Ajukan Perubahan
                </a>
            @endif
        </div>
    </div>

     {{-- Tampilkan pesan warning jika ada request pending --}}
     @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
     @endif
     {{-- Tampilkan pesan error jika request gagal --}}
     @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
     @endif
      {{-- Tampilkan pesan sukses jika request berhasil --}}
     @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
     @endif

    <div class="card shadow-sm mb-4"> {{-- Tambah margin-bottom --}}
        {{-- ... (Isi card detail pesanan Anda yang sudah ada, tidak perlu diubah) ... --}}
         <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <strong>Klien:</strong> {{ $order->client->client_name }} <br>
                    <strong>Sales:</strong> {{ $order->sales->full_name ?? 'N/A' }}
                </div>
                <div class="col-md-6 text-md-end">
                    <strong>Tanggal Pesanan:</strong> {{ $order->order_date->format('d M Y') }} <br>
                    <strong>Status:</strong>
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
                </div>
            </div>
            <hr>
            <h5 class="fw-semibold mt-4">Rincian Item Pesanan</h5>
            <table class="table table-bordered">
                {{-- ... (Tabel item Anda) ... --}}
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
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="4" class="text-end fw-bold fs-5">Total Pesanan</td>
                        <td class="text-end fw-bold fs-5">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ✅ BAGIAN BARU: RIWAYAT PERMINTAAN PERUBAHAN --}}
    @if($order->changeRequests->isNotEmpty())
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-semibold">Riwayat Permintaan Perubahan</h5>
        </div>
        <div class="card-body">
            @foreach($order->changeRequests as $request)
                <div class="mb-3 border-bottom pb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold">
                            Permintaan {{ $request->request_type == 'cancel' ? 'Pembatalan' : 'Modifikasi Item' }}
                        </span>
                        <span>
                             @php
                                $reqStatusClass = [
                                    'pending' => 'bg-warning text-dark',
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                ];
                            @endphp
                            <span class="badge {{ $reqStatusClass[$request->status] ?? 'bg-secondary' }}">
                                {{ Str::title($request->status) }}
                            </span>
                        </span>
                    </div>
                    <small class="text-muted d-block mb-1">Diajukan pada: {{ $request->created_at->format('d M Y H:i') }}</small>
                    @if($request->client_notes)
                        <p class="mb-1 fst-italic"><strong>Catatan Anda:</strong> {{ $request->client_notes }}</p>
                    @endif

                    {{-- Tampilkan detail item jika tipe 'modify' --}}
                    @if($request->request_type == 'modify' && $request->items->isNotEmpty())
                        <ul class="list-group list-group-flush mt-2">
                             <li class="list-group-item list-group-item-light fw-bold">Detail Item Diminta:</li>
                            @foreach($request->items as $reqItem)
                                <li class="list-group-item small d-flex justify-content-between">
                                     <span>{{ $reqItem->product->product_name ?? 'N/A' }}</span>
                                     <span>
                                         @if($reqItem->action == 'add')
                                             Tambah: {{ $reqItem->requested_quantity }}
                                         @elseif($reqItem->action == 'remove')
                                             <strong class="text-danger">Hapus</strong> (Asli: {{ $reqItem->original_quantity }})
                                         @elseif($reqItem->action == 'update_qty')
                                              Ubah Qty: {{ $reqItem->original_quantity }} &rarr; <strong>{{ $reqItem->requested_quantity }}</strong>
                                         @endif
                                     </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($request->processed_at)
                        <small class="text-muted d-block mt-2">Diproses oleh {{ $request->processor->full_name ?? 'Sistem' }} pada {{ $request->processed_at->format('d M Y H:i') }}</small>
                    @endif
                     @if($request->admin_notes)
                        <p class="mt-1 mb-0 fst-italic"><strong class="text-primary">Catatan Admin:</strong> {{ $request->admin_notes }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection