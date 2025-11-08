@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Daftar Invoice</h2>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Buat Invoice Baru
        </a>
    </div>

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route("invoices.index") }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Cari No. Invoice / Klien..." value="{{ request("search") }}"></div>
                <div class="col-md-2"><input type="date" name="start_date" class="form-control" value="{{ request("start_date") }}" title="Tanggal Mulai"></div>
                <div class="col-md-2"><input type="date" name="end_date" class="form-control" value="{{ request("end_date") }}" title="Tanggal Akhir"></div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">-- Semua Status --</option>
                        {{-- ✅ TAMBAHKAN OPSI 'DRAFT' DI FILTER --}}
                        <option value="draft" @selected(request("status") == "draft")>Draft</option>
                        <option value="unpaid" @selected(request("status") == "unpaid")>Belum Lunas</option>
                        <option value="partially_paid" @selected(request("status") == "partially_paid")>Cicil</option>
                        <option value="paid" @selected(request("status") == "paid")>Lunas</option>
                        <option value="cancelled" @selected(request("status") == "cancelled")>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-dark w-100">Filter</button></div>
                <div class="col-md-1"><a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary w-100" title="Reset">Reset</a></div>
            </form>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- TAMPILAN DAFTAR INVOICE MODEL DROPDOWN BARU --}}
    <div class="list-group">
        @forelse ($invoices as $invoice)
            @php
                // ✅ Gunakan Accessor 'remaining_balance' yang sudah ada
                $sisaPiutang = $invoice->remaining_balance;
                $totalRetur = $invoice->returns->sum('total_amount'); // Ini hanya untuk info
            @endphp
            <div class="list-group-item list-group-item-action mb-2 shadow-sm border-0 rounded">
                {{-- Bagian Header yang Selalu Terlihat --}}
                <a class="d-flex w-100 justify-content-between align-items-center text-decoration-none" data-bs-toggle="collapse" href="#collapse-{{ $invoice->invoice_id }}" role="button" aria-expanded="false" aria-controls="collapse-{{ $invoice->invoice_id }}">
                    <div class="row w-100 align-items-center">
                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <strong class="text-primary">{{ $invoice->invoice_number }}</strong>
                            <small class="d-block text-muted">{{ $invoice->client->client_name ?? 'N/A' }}</small>
                        </div>
                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <span class="text-dark">{{ optional($invoice->order_date)->format('d M Y') }}</span>
                            <small class="d-block text-muted">Tgl. Invoice</small>
                        </div>
                        <div class="col-md-3 col-6">
                            <span class="text-dark {{ optional($invoice->due_date)->isPast() && $invoice->status != 'paid' ? 'text-danger fw-bold' : '' }}">
                                {{ optional($invoice->due_date)->format('d M Y') ?? '-' }}
                            </span>
                            <small class="d-block text-muted">Jatuh Tempo</small>
                        </div>
                        <div class="col-md-3 col-6 text-md-end">
                            {{-- Badge status Anda sudah benar --}}
                            @if($invoice->status == 'paid')
                                <span class="badge bg-success">Lunas</span>
                            @elseif($invoice->status == 'partially_paid')
                                <span class="badge bg-info text-dark">Cicil</span>
                            @elseif($invoice->status == 'cancelled')
                                <span class="badge bg-danger">Dibatalkan</span>
                            @elseif($invoice->status == 'unpaid')
                                <span class="badge bg-warning text-dark">Belum Lunas</span>
                            @elseif($invoice->status == 'draft')
                                <span class="badge bg-secondary">Draft</span>
                            @else
                                <span class="badge bg-secondary">{{ $invoice->status }}</span>
                            @endif
                        </div>
                    </div>
                </a>

                {{-- Bagian Detail yang Bisa Dibuka-Tutup --}}
                <div class="collapse" id="collapse-{{ $invoice->invoice_id }}">
                    <hr>
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h6 class="fw-semibold mb-0">{{ $invoice->sales->full_name ?? 'Umum' }}</h6>
                            <small class="text-muted d-block">Sales</small>
                        </div>
                        <div class="col-md-6">
                            <div class="row text-md-end">
                                <div class="col">
                                    <h6 class="fw-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</h6>
                                    <small class="text-muted d-block">Total Tagihan</small>
                                </div>
                                <div class="col">
                                    <h6 class="fw-semibold text-warning">Rp {{ number_format($totalRetur, 0, ',', '.') }}</h6>
                                    <small class="text-muted d-block">Total Retur</small>
                                </div>
                                <div class="col">
                                    <h6 class="fw-bold {{ $sisaPiutang > 0.01 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($sisaPiutang, 0, ',', '.') }}</h6>
                                    <small class="text-muted d-block">Sisa Piutang</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-1 mt-3">
                        
                        {{-- ====================================================== --}}
                        {{-- ✅ TOMBOL KONFIRMASI BARU DITAMBAHKAN DI SINI --}}
                        {{-- ====================================================== --}}
                        @if($invoice->status == 'draft')
                            <form class="confirm-form" action="{{ route('invoices.confirm', $invoice->invoice_id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary fw-bold" title="Konfirmasi Invoice">
                                    <i class="bi bi-check-circle-fill"></i> Konfirmasi
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="bi bi-eye"></i> Detail Lengkap</a>
                        
                        {{-- ✅ PERBAIKI LOGIKA @if --}}
                        {{-- Tombol Edit/Batal hanya muncul jika BUKAN Lunas atau Batal --}}
                        @if(!in_array($invoice->status, ['paid', 'cancelled']))
                            <a href="{{ route('invoices.edit', $invoice->invoice_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square"></i> Edit</a>
                            
                            {{-- Tombol Batal hanya muncul jika BUKAN Draft --}}
                            @if($invoice->status != 'draft')
                                <form class="cancel-form" action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Batalkan"><i class="bi bi-x-circle"></i> Batalkan</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info text-center">
                Tidak ada data invoice ditemukan.
            </div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $invoices->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Script untuk Tombol Batal (Cancel)
    const cancelForms = document.querySelectorAll('.cancel-form');
    cancelForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Invoice yang dibatalkan akan mengubah statusnya!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });

    // ======================================================
    // ✅ SCRIPT BARU UNTUK TOMBOL KONFIRMASI
    // ======================================================
    const confirmForms = document.querySelectorAll('.confirm-form');
    confirmForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Invoice Ini?',
                text: "Stok akan diperiksa dan dikurangi. Status akan berubah menjadi 'Belum Lunas'.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754', // Warna hijau
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Konfirmasi!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });
});
</script>
@endpush