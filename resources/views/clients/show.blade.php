@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Detail Klien</h3>
            <p class="text-muted mb-0 small">Profil dan riwayat transaksi klien</p>
        </div>
        <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row g-4">
        {{-- KOLOM KIRI: PROFIL --}}
        <div class="col-lg-4">
            <div class="card card-transaction border-0 shadow-sm h-100">
                <div class="card-header bg-white p-3 border-bottom text-center">
                    <div class="bg-light rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center text-secondary" style="width: 80px; height: 80px;">
                        <i class="bi bi-building fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $client->client_name }}</h5>
                    <p class="text-muted small mb-2">{{ $client->person_in_charge ?? 'Tanpa PIC' }}</p>
                    
                    @if($client->trashed())
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Diarsipkan</span>
                    @elseif($client->is_locked)
                        <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25">Terkunci</span>
                    @elseif($client->is_approved)
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                    @else
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Menunggu Persetujuan</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted d-block">EMAIL</label>
                        <span>{{ $client->email ?? '-' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted d-block">TELEPON</label>
                        <span>{{ $client->phone_number ?? '-' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted d-block">ALAMAT</label>
                        <span class="text-dark">{{ $client->address ?? '-' }}</span>
                    </div>
                    <div class="mb-0">
                        <label class="small fw-bold text-muted d-block">TERDAFTAR SEJAK</label>
                        <span>{{ $client->created_at->format('d F Y') }}</span>
                    </div>

                    <hr class="border-dashed my-4">

                    <div class="p-3 rounded bg-success bg-opacity-10 border border-success border-opacity-25 text-center">
                        <small class="text-success fw-bold text-uppercase d-block mb-1">Saldo Kredit (Deposit)</small>
                        <h3 class="fw-bold text-success mb-0">Rp {{ number_format($client->balance ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: RIWAYAT --}}
        <div class="col-lg-8">
            
            {{-- INVOICE TERBARU --}}
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="form-section-title mb-0"><i class="bi bi-receipt"></i> 5 Invoice Terbaru</div>
                    @if($client->salesInvoices()->count() > 0)
                        <a href="{{ route('invoices.index', ['search' => $client->client_name]) }}" class="btn btn-sm btn-link text-decoration-none">Lihat Semua</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-transaction align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">No. Invoice</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($client->salesInvoices()->latest('order_date')->take(5)->get() as $invoice)
                                <tr>
                                    <td class="ps-4 fw-medium text-primary">{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->order_date->format('d M Y') }}</td>
                                    <td class="text-end">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($invoice->status == 'paid') <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Lunas</span>
                                        @elseif($invoice->status == 'partially_paid') <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Cicilan</span>
                                        @elseif($invoice->status == 'unpaid') <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Belum Lunas</span>
                                        @else <span class="badge bg-secondary">{{ $invoice->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-light border text-primary shadow-sm" title="Lihat"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted fst-italic">Belum ada invoice.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- RIWAYAT SALDO (LEDGER) --}}
            <div class="card card-transaction border-0 shadow-sm">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-journal-text"></i> Riwayat Saldo (Ledger)</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-transaction align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Tanggal</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Kredit (Masuk)</th>
                                    <th class="text-end pe-4">Debit (Keluar)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledgers as $ledger)
                                <tr>
                                    <td class="ps-4">{{ $ledger->transaction_date->format('d M Y') }}</td>
                                    <td style="max-width: 250px;">
                                        <span class="d-block text-truncate">{{ $ledger->description }}</span>
                                        @if($ledger->reference_type === \App\Models\SalesReturn::class && $ledger->reference)
                                            <a href="{{ route('sales-returns.show', $ledger->reference_id) }}" class="small text-decoration-none">Lihat Retur</a>
                                        @elseif($ledger->reference_type === \App\Models\Payment::class && $ledger->reference)
                                            <a href="{{ route('invoices.show', $ledger->reference->salesInvoice->invoice_id) }}" class="small text-decoration-none">Lihat Invoice</a>
                                        @endif
                                    </td>
                                    <td class="text-end text-success">
                                        {{ $ledger->type == 'credit' && $ledger->amount > 0 ? 'Rp '.number_format($ledger->amount, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end pe-4 text-danger">
                                        {{ $ledger->type == 'debit' && $ledger->amount < 0 ? 'Rp '.number_format(abs($ledger->amount), 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted fst-italic">Belum ada riwayat transaksi saldo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($ledgers->hasPages())
                <div class="card-footer bg-white border-top-0">
                    {{ $ledgers->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection