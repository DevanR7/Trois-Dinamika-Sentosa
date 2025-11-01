@extends('layouts.app')

@section('content')
<div class="container-fluid">

    {{-- Judul Halaman dan Tombol Kembali --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            Detail Supplier: 
            <span class="fw-bold">{{ $supplier->supplier_name }}</span>
        </h2>
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary d-flex align-items-center">
            <span class="material-icons me-1">arrow_back</span>
            Kembali
        </a>
    </div>

    <div class="row">
        {{-- Kolom Kiri: Informasi Detail Supplier --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Supplier</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Nama Supplier</dt>
                        <dd class="col-sm-8">{{ $supplier->supplier_name }}</dd>

                        <dt class="col-sm-4">Telepon</dt>
                        <dd class="col-sm-8">{{ $supplier->phone_number ?? '-' }}</dd>

                        <dt class="col-sm-4">Person in Charge (PIC)</dt>
                        <dd class="col-sm-8">{{ $supplier->person_in_charge ?? '-' }}</dd>

                        <dt class="col-sm-4">Alamat</dt>
                        <dd class="col-sm-8">{{ $supplier->address ?? '-' }}</dd>
                        
                        <dt class="col-sm-4 pt-2 border-top mt-2">NPWP</dt>
                        <dd class="col-sm-8 pt-2 border-top mt-2">{{ $supplier->npwp ?? '-' }}</dd>
                        
                        <dt class="col-sm-4">Bank</dt>
                        <dd class="col-sm-8">{{ $supplier->bank_name ?? '-' }}</dd>
                        
                        <dt class="col-sm-4">No. Rekening</dt>
                        <dd class="col-sm-8">{{ $supplier->account_number ?? '-' }}</dd>
                        
                        <dt class="col-sm-4 pt-2 border-top mt-2">Saldo Deposit</dt>
                        <dd class="col-sm-8 pt-2 border-top mt-2">
                            {{-- Menggunakan accessor 'balance' baru --}}
                            <span class="fw-bold {{ $supplier->balance > 0 ? 'text-success' : '' }}">
                                Rp {{ number_format($supplier->balance ?? 0, 0, ',', '.') }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Card untuk Riwayat Purchase Order --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Riwayat Purchase Order (5 Terbaru)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. PO</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status Bayar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($supplier->purchaseOrders()->latest('order_date')->take(5)->get() as $po)
                                    <tr>
                                        <td>{{ $po->po_number }}</td>
                                        <td>{{ $po->order_date->format('d M Y') }}</td>
                                        <td class="text-end">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                                        <td>
                                            @if($po->payment_status == 'paid')
                                                <span class="badge bg-success">Lunas</span>
                                            @elseif($po->payment_status == 'partially_paid')
                                                <span class="badge bg-warning text-dark">Sebagian</span>
                                            @else
                                                <span class="badge bg-danger">Belum Lunas</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('purchase-orders.show', $po->po_id) }}" class="btn btn-sm btn-info" title="Lihat PO">
                                                <span class="material-icons" style="font-size: 1rem;">visibility</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Belum ada riwayat PO untuk supplier ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($supplier->purchaseOrders()->count() > 0)
                <div class="card-footer text-center">
                    <a href="{{ route('purchase-orders.index', ['search' => $supplier->supplier_name]) }}">Lihat Semua PO Supplier Ini</a>
                </div>
                @endif
            </div>

            {{-- Card untuk Riwayat Saldo (Ledger) --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Riwayat Saldo Deposit (Ledger)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Kredit (Masuk)</th>
                                    <th class="text-end">Debit (Keluar)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledgers as $ledger)
                                    <tr>
                                        <td>{{ $ledger->transaction_date->format('d M Y') }}</td>
                                        <td>
                                            {{ $ledger->description }}
                                            {{-- Link ke referensi --}}
                                            @if($ledger->reference_type === \App\Models\PurchaseReturn::class && $ledger->reference)
                                                <a href="{{ route('purchase-returns.show', $ledger->reference_id) }}" class="d-block small" target="_blank">Lihat Retur</a>
                                            @elseif($ledger->reference_type === \App\Models\PurchaseOrderPayment::class && $ledger->reference)
                                                <a href="{{ route('purchase-orders.show', $ledger->reference->po_id) }}" class="d-block small" target="_blank">Lihat PO #{{ $ledger->reference->purchaseOrder->po_number }}</a>
                                            @elseif($ledger->reference_type === \App\Models\BatchPurchasePayment::class && $ledger->reference)
                                                <span class="d-block small text-muted">Ref: Pembayaran Hutang Batch #{{ $ledger->reference_id }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-success">
                                            @if($ledger->type == 'credit' && $ledger->amount > 0)
                                                Rp {{ number_format($ledger->amount, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end text-danger">
                                            @if($ledger->type == 'debit' && $ledger->amount < 0)
                                                Rp {{ number_format(abs($ledger->amount), 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">Belum ada riwayat transaksi saldo deposit.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($ledgers->hasPages())
                <div class="card-footer">
                    {{-- Tampilkan link paginasi (menggunakan nama 'ledger_page' kustom) --}}
                    {{ $ledgers->links() }}
                </div>
                @endif
            </div>

        </div> {{-- Tutup col-lg-8 --}}

        {{-- Kolom Kanan: Status dan Tombol Tindakan --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status & Tindakan</h5>
                </div>
                <div class="card-body">
                    {{-- Bagian Status --}}
                    <h6 class="mb-3">Status Akun</h6>
                    <div class="mb-3">
                        @if($supplier->trashed())
                            <span class="badge bg-danger p-2 w-100 fs-6">
                                <span class="material-icons me-1" style="font-size: 1.1rem; vertical-align: middle;">archive</span>
                                DIARSIPKAN
                            </span>
                        @else
                            <span class="badge bg-success p-2 w-100 fs-6">
                                <span class="material-icons me-1" style="font-size: 1.1rem; vertical-align: middle;">check_circle</span>
                                AKTIF
                            </span>
                        @endif
                    </div>
                    
                    <hr>

                    {{-- Bagian Tindakan (Tombol) --}}
                    <h6 class="mb-3">Tindakan</h6>
                    <div class="d-grid gap-2">
                        
                        @if($supplier->trashed())
                            @can('restore', $supplier)
                            <form action="{{ route('suppliers.restore', $supplier->supplier_id) }}" method="POST" class="form-restore">
                                @csrf
                                @method('PATCH')
                                <button type="submit" data-name="{{ $supplier->supplier_name }}" class="btn btn-success w-100 d-flex align-items-center justify-content-center">
                                    <span class="material-icons me-1">restore_from_trash</span> Pulihkan
                                </button>
                            </form>
                            @endcan
                        
                        @else
                            @can('update', $supplier)
                            <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}" class="btn btn-primary d-flex align-items-center justify-content-center">
                                <span class="material-icons me-1">edit</span> Edit Supplier
                            </a>
                            @endcan

                            @can('delete', $supplier)
                            <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST" class="form-delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-name="{{ $supplier->supplier_name }}" class="btn btn-danger w-100 d-flex align-items-center justify-content-center">
                                    <span class="material-icons me-1">archive</span> Arsipkan
                                </button>
                            </form>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. NOTIFIKASI TOAST (SETELAH AKSI) ---
        @if(session('success'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        @endif
        @if(session('error'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '{{ session('error') }}',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        @endif

        // --- 2. KONFIRMASI "ARSIPKAN" (DELETE) ---
        const deleteForm = document.querySelector('.form-delete');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const supplierName = this.querySelector('button').dataset.name;

                Swal.fire({
                    title: 'Anda Yakin?',
                    text: `Anda akan mengarsipkan supplier "${supplierName}".`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Arsipkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }

        // --- 3. KONFIRMASI "PULIHKAN" (RESTORE) ---
        const restoreForm = document.querySelector('.form-restore');
        if (restoreForm) {
            restoreForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const supplierName = this.querySelector('button').dataset.name;

                Swal.fire({
                    title: 'Pulihkan Supplier Ini?',
                    text: `Anda akan memulihkan akun untuk supplier "${supplierName}".`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#17a2b8',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Pulihkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }
    });
</script>
@endpush