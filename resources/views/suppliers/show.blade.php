@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Detail Supplier</h3>
            <p class="text-muted mb-0 small">Profil lengkap: <span class="text-primary fw-bold">{{ $supplier->supplier_name }}</span></p>
        </div>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row g-4">
        
        {{-- KOLOM KIRI: INFORMASI DETAIL --}}
        <div class="col-lg-7">
            <div class="card card-transaction border-0 shadow-sm h-100">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-info-circle"></i> Informasi Detail</div>
                </div>
                <div class="card-body p-4">
                    
                    <dl class="row g-3">
                        <dt class="col-sm-4 small text-muted text-uppercase fw-bold">Nama Supplier</dt>
                        <dd class="col-sm-8 fw-semibold text-dark">{{ $supplier->supplier_name }}</dd>

                        <dt class="col-sm-4 small text-muted text-uppercase fw-bold">Narahubung (PIC)</dt>
                        <dd class="col-sm-8">{{ $supplier->person_in_charge ?? '-' }}</dd>

                        <dt class="col-sm-4 small text-muted text-uppercase fw-bold">No. Telepon</dt>
                        <dd class="col-sm-8">{{ $supplier->phone_number ?? '-' }}</dd>

                        <dt class="col-sm-4 small text-muted text-uppercase fw-bold">Alamat</dt>
                        <dd class="col-sm-8">{{ $supplier->address ?? '-' }}</dd>
                        
                        <dt class="col-sm-12"><hr class="my-0 border-dashed"></dt>
                        
                        <dt class="col-sm-4 small text-muted text-uppercase fw-bold">NPWP</dt>
                        <dd class="col-sm-8">{{ $supplier->npwp ?? '-' }}</dd>

                        <dt class="col-sm-4 small text-muted text-uppercase fw-bold">Nama Bank</dt>
                        <dd class="col-sm-8">{{ $supplier->bank_name ?? '-' }}</dd>
                        
                        <dt class="col-sm-4 small text-muted text-uppercase fw-bold">No. Rekening</dt>
                        <dd class="col-sm-8">{{ $supplier->account_number ?? '-' }}</dd>

                    </dl>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: RINGKASAN & AKSI --}}
        <div class="col-lg-5">
            <div class="card card-transaction border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-wallet-fill"></i> Saldo & Tindakan</div>
                </div>
                <div class="card-body p-4">
                    
                    {{-- SALDO DEPOSIT --}}
                    <div class="p-3 rounded mb-4 {{ $supplier->balance > 0 ? 'bg-success bg-opacity-10 border border-success' : 'bg-light border' }} text-center">
                        <small class="small text-muted fw-bold d-block">SALDO DEPOSIT</small>
                        <h4 class="fw-bold {{ $supplier->balance > 0 ? 'text-success' : 'text-dark' }} mb-0">
                             Rp {{ number_format($supplier->balance ?? 0, 0, ',', '.') }}
                        </h4>
                    </div>

                    {{-- RIWAYAT TERBARU (Placeholder) --}}
                    <h6 class="fw-bold text-dark mb-3">Tindakan Cepat</h6>
                    <div class="d-grid gap-2">
                        @if($supplier->trashed())
                            <form action="{{ route('suppliers.restore', $supplier->supplier_id) }}" method="POST" class="form-restore">
                                @csrf @method('PATCH')
                                <button type="submit" data-name="{{ $supplier->supplier_name }}" class="btn btn-success w-100 fw-bold">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan Supplier
                                </button>
                            </form>
                        @else
                            <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}" class="btn btn-primary w-100 fw-bold">
                                <i class="bi bi-pencil-square me-1"></i> Edit Supplier
                            </a>
                            <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST" class="form-delete">
                                @csrf @method('DELETE')
                                <button type="submit" data-name="{{ $supplier->supplier_name }}" class="btn btn-danger w-100 fw-bold">
                                    <i class="bi bi-archive me-1"></i> Arsipkan Supplier
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- KONFIRMASI "ARSIPKAN" ---
        document.querySelectorAll('.form-delete').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const supplierName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Arsipkan Supplier?',
                    text: `Anda akan mengarsipkan supplier "${supplierName}".`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Arsipkan!',
                }).then((result) => {
                    if (result.isConfirmed) this.submit();
                });
            });
        });

        // --- KONFIRMASI "PULIHKAN" ---
        document.querySelectorAll('.form-restore').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const supplierName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Pulihkan Supplier?',
                    text: `Anda akan memulihkan supplier "${supplierName}".`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#17a2b8',
                    confirmButtonText: 'Ya, Pulihkan!',
                }).then((result) => {
                    if (result.isConfirmed) this.submit();
                });
            });
        });
    });
</script>
@endpush