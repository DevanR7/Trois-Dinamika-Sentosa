@extends("layouts.app")

@section("content")
    <div class="container-fluid py-4">
        {{-- Header Halaman --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Daftar Invoice</h2>
            <a href="{{ route("invoices.create") }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> Buat Invoice Baru
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- FORM PENCARIAN & FILTER --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route("invoices.index") }}" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Cari No. Invoice / Klien..." value="{{ request("search") }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="start_date" class="form-control" value="{{ request("start_date") }}" title="Tanggal Pesanan Mulai">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="end_date" class="form-control" value="{{ request("end_date") }}" title="Tanggal Pesanan Akhir">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="unpaid" @selected(request("status") == "unpaid")>Belum Lunas</option>
                            <option value="partially_paid" @selected(request("status") == "partially_paid")>Dibayar Sebagian</option>
                            <option value="paid" @selected(request("status") == "paid")>Lunas</option>
                            <option value="cancelled" @selected(request("status") == "cancelled")>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="sort" class="form-select">
                            <option value="terbaru" @selected(request('sort') == 'terbaru')>Urutkan: Terbaru</option>
                            <option value="terlama" @selected(request('sort') == 'terlama')>Urutkan: Terlama</option>
                            <option value="klien_az" @selected(request('sort') == 'klien_az')>Urutkan: Klien A-Z</option>
                            <option value="klien_za" @selected(request('sort') == 'klien_za')>Urutkan: Klien Z-A</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-dark w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABEL DAFTAR INVOICE --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Nomor Invoice</th>
                                <th>Pelanggan</th>
                                <th>Tanggal Pesanan</th>
                                <th>Tanggal Jatuh Tempo</th>
                                <th class="text-end">Total Tagihan</th>
                                <th class="text-end">Sisa Tagihan</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td>{{ $loop->iteration + $invoices->firstItem() - 1 }}</td> 
                                    <td>
                                        <a href="{{ route("invoices.show", $invoice->invoice_id) }}" class="text-decoration-none fw-semibold">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $invoice->client->client_name ?? "N/A" }}</td>
                                    {{-- PERBAIKAN: Menggunakan order_date dan optional() --}}
                                    <td>{{ optional($invoice->order_date)->format("d M Y") }}</td>
                                    <td>{{ optional($invoice->due_date)->format("d M Y") }}</td>
                                    <td class="text-end">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ ($invoice->total_amount - $invoice->amount_paid > 0) ? 'text-danger' : 'text-success' }}">
                                        Rp {{ number_format($invoice->total_amount - $invoice->amount_paid, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @if($invoice->status == 'paid') <span class="badge bg-success">LUNAS</span>
                                        @elseif($invoice->status == 'partially_paid') <span class="badge bg-info text-dark">CICIL</span>
                                        @elseif($invoice->status == 'cancelled') <span class="badge bg-dark">DIBATALKAN</span>
                                        @else <span class="badge bg-warning text-dark">BELUM LUNAS</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="bi bi-eye"></i></a>
                                            @if (!in_array($invoice->status, ['paid', 'cancelled']))
                                                <a href="{{ route('invoices.edit', $invoice->invoice_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                 <form class="cancel-form" action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST">
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
                                <tr>
                                    {{-- Disesuaikan menjadi 9 kolom --}}
                                    <td colspan="9" class="text-center py-4">
                                        <p class="mb-0">Tidak ada data invoice ditemukan.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $invoices->appends(request()->query())->links() }}
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ambil semua form pembatalan dengan class 'cancel-form'
    const cancelForms = document.querySelectorAll('.cancel-form');

    cancelForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            // Hentikan pengiriman form otomatis
            event.preventDefault(); 
            
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Invoice yang sudah dibatalkan tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                // Jika user menekan tombol "Ya"
                if (result.isConfirmed) {
                    // Lanjutkan pengiriman form
                    event.target.submit();
                }
            });
        });
    });
});
</script>
@endpush