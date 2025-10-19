@extends('layouts.app')

@section('content')
<div class="container-fluid py-4"> {{-- Menggunakan container-fluid seperti contoh Anda --}}
    {{-- Header Halaman --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Review Permintaan Perubahan Pesanan</h2>
        {{-- Tombol Tambah (jika diperlukan) bisa diletakkan di sini --}}
    </div>

    {{-- Notifikasi Sukses/Error --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- ✅ FORM FILTER BARU --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('order-change-requests.index') }}" method="GET" class="row g-3 align-items-end">
                {{-- Kolom Search --}}
                <div class="col-md-3">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="No. Request/Order/Klien..." value="{{ request('search') }}">
                </div>

                {{-- Kolom Filter Tanggal --}}
                <div class="col-md-2">
                    <label for="date_filter" class="form-label">Bulan/Tahun</label>
                    <select name="date_filter" id="date_filter" class="form-select form-select-sm">
                        <option value="">-- Semua Tanggal --</option>
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                 {{-- Kolom Filter Tipe --}}
                <div class="col-md-2">
                    <label for="type_filter" class="form-label">Tipe Request</label>
                    <select name="type_filter" id="type_filter" class="form-select form-select-sm">
                        <option value="">-- Semua Tipe --</option>
                        <option value="cancel" @selected(request('type_filter') == 'cancel')>Pembatalan</option>
                        <option value="modify" @selected(request('type_filter') == 'modify')>Modifikasi Item</option>
                    </select>
                </div>

                {{-- Kolom Urutkan --}}
                <div class="col-md-3">
                    <label for="sort" class="form-label">Urutkan</label>
                    <select name="sort" id="sort" class="form-select form-select-sm">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                </div>

                {{-- Tombol Filter & Reset --}}
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm w-100">Filter</button>
                    <a href="{{ route('order-change-requests.index') }}" class="btn btn-outline-secondary btn-sm w-100" title="Reset">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        {{-- Card Header (jika diinginkan) --}}
        {{-- <div class="card-header bg-white">
             <h5 class="mb-0 fw-semibold">Daftar Permintaan Pending</h5>
        </div> --}}
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle"> {{-- Meniru tabel dari sales_orders/index --}}
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">No. Request</th>
                            <th scope="col">No. Pesanan</th>
                            <th scope="col">Klien</th>
                            <th scope="col">Tipe Request</th>
                            <th scope="col">Tgl Diajukan</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($changeRequests as $request) {{-- Menggunakan variabel $changeRequests --}}
                            <tr>
                                <th scope="row">{{ $loop->iteration + $changeRequests->firstItem() - 1 }}</th>
                                <td>
                                    {{-- Link ke halaman detail request --}}
                                    <a href="{{ route('order-change-requests.show', $request->request_id) }}" class="fw-semibold"> {{-- Tambah fw-semibold --}}
                                        REQ-{{ str_pad($request->request_id, 5, '0', STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td>
                                    {{-- Link ke halaman detail order asli --}}
                                    @if($request->order)
                                        <a href="{{ route('sales-orders.show', $request->order->order_id) }}">{{ $request->order->order_number }}</a>
                                    @else
                                        <span class="text-muted">Order Dihapus</span>
                                    @endif
                                </td>
                                <td>{{ $request->client->client_name ?? 'Klien Dihapus' }}</td>
                                <td>{{ $request->request_type == 'cancel' ? 'Pembatalan' : 'Modifikasi Item' }}</td>
                                <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                                <td class="text-center">
                                    {{-- Status selalu 'pending' di halaman ini --}}
                                    <span class="badge bg-warning text-dark">{{ Str::title($request->status) }}</span>
                                </td>
                                <td class="text-center">
                                     <div class="d-flex justify-content-center gap-2"> {{-- Meniru tombol aksi sales_orders/index --}}
                                        <a href="{{ route('order-change-requests.show', $request->request_id) }}" class="btn btn-sm btn-outline-info" title="Lihat & Proses"> {{-- Ganti jadi info & icon mata --}}
                                            <i class="bi bi-eye"></i> {{-- Ganti icon jadi mata --}}
                                        </a>
                                         {{-- Tombol lain bisa ditambahkan di sini jika perlu --}}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4"> {{-- Sesuaikan colspan --}}
                                     <p class="mb-0 text-muted"> {{-- Tambah class text-muted --}}
                                        Tidak ada permintaan perubahan yang menunggu diproses.
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
        {{ $changeRequests->links() }} {{-- Pastikan menggunakan variabel $changeRequests --}}
    </div>
</div>
@endsection