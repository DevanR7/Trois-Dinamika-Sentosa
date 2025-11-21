@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Manajemen Supplier</h3>
            <p class="text-muted small mb-0">Daftar vendor dan pemasok barang.</p>
        </div>
        <div>
            @if(request('status') === 'deleted')
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Aktif
                </a>
            @else
                <a href="{{ route('suppliers.index', ['status' => 'deleted']) }}" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-archive me-1"></i>Lihat Arsip
                </a>
            @endif
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Supplier
            </a>
        </div>
    </div>
    
    {{-- ACCORDION/LIST VIEW --}}
    <div class="accordion shadow-sm" id="supplierAccordion">

        @forelse ($suppliers as $supplier)
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{{ $supplier->supplier_id }}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#collapse-{{ $supplier->supplier_id }}" aria-expanded="false" 
                        aria-controls="collapse-{{ $supplier->supplier_id }}">
                    
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        
                        {{-- Kiri: Nama & PIC --}}
                        <div class="d-flex flex-column text-start me-3">
                            <strong class="fs-5 text-dark">{{ $supplier->supplier_name }}</strong>
                            <span class="text-muted small">
                                <i class="bi bi-person me-1"></i>
                                {{ $supplier->person_in_charge ?? 'Belum ada PIC' }}
                            </span>
                        </div>
                        
                        {{-- Kanan: Saldo Deposit, Status, dan Icon --}}
                        <div class="me-3 d-flex align-items-center text-end">

                            {{-- Saldo Deposit --}}
                            <div class="me-3 d-none d-lg-block text-end">
                                @if($supplier->balance > 0)
                                    <span class="text-success fw-bold">Rp {{ number_format($supplier->balance, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">Rp 0</span>
                                @endif
                                <small class="d-block text-muted" style="font-size: 0.75rem;">Saldo Deposit</small>
                            </div>

                            {{-- Status Badge --}}
                            @if($supplier->trashed())
                                <span class="badge bg-danger rounded-pill">Diarsipkan</span>
                            @else
                                <span class="badge bg-success rounded-pill">Aktif</span>
                            @endif
                        </div>
                    </div>
                </button>
            </h2>

            {{-- TAMPILAN TERBUKA (EXPANDED) --}}
            <div id="collapse-{{ $supplier->supplier_id }}" class="accordion-collapse collapse" 
                 aria-labelledby="heading-{{ $supplier->supplier_id }}" data-bs-parent="#supplierAccordion">
                <div class="accordion-body bg-light bg-opacity-25">
                    <div class="row">
                        <div class="col-md-5 small">
                            <strong>Detail Kontak:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="bi bi-telephone me-2 text-muted"></i>{{ $supplier->phone_number ?? '-' }}</li>
                                <li><i class="bi bi-geo-alt me-2 text-muted"></i>{{ $supplier->address ?? '-' }}</li>
                            </ul>
                        </div>
                        <div class="col-md-5 small">
                            <strong>Detail Bank:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="bi bi-card-heading me-2 text-muted"></i>NPWP: {{ $supplier->npwp ?? '-' }}</li>
                                <li><i class="bi bi-bank me-2 text-muted"></i>Bank: {{ $supplier->bank_name ?? '-' }}</li>
                                <li><i class="bi bi-hash me-2 text-muted"></i>No. Rek: {{ $supplier->account_number ?? '-' }}</li>
                            </ul>
                        </div>
                        <div class="col-md-2 d-flex flex-column justify-content-center gap-2">
                            <hr class="my-2 d-md-none">
                            {{-- Tombol Aksi --}}
                            @if($supplier->trashed())
                                <a href="{{ route('suppliers.show', $supplier->supplier_id) }}" class="btn btn-sm btn-outline-info w-100" title="Lihat Detail">
                                    <i class="bi bi-eye me-1"></i> Lihat
                                </a>
                                <form action="{{ route('suppliers.restore', $supplier->supplier_id) }}" method="POST" class="form-restore d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success w-100" data-name="{{ $supplier->supplier_name }}">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('suppliers.show', $supplier->supplier_id) }}" class="btn btn-sm btn-outline-info w-100" title="Lihat Detail">
                                    <i class="bi bi-eye me-1"></i> Lihat
                                </a>
                                <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}" class="btn btn-sm btn-outline-secondary w-100" title="Edit">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </a>
                                <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST" class="form-delete d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" title="Arsipkan" data-name="{{ $supplier->supplier_name }}">
                                        <i class="bi bi-archive me-1"></i> Arsipkan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
        {{-- Tampilan jika tidak ada supplier --}}
        <div class="alert alert-info text-center py-5 my-3">
            <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-50"></i>
            Tidak ada data supplier.
        </div>
        @endforelse
    </div>

    {{-- Pagination Links --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $suppliers->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- KONFIRMASI "DELETE" (ARSIPKAN) ---
        document.querySelectorAll('.form-delete').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const supplierName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Anda Yakin?',
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

        // --- KONFIRMASI "PULIHKAN" (RESTORE) ---
        document.querySelectorAll('.form-restore').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const supplierName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Pulihkan Supplier Ini?',
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

        // --- Accordion Icon Rotation ---
        document.querySelectorAll('.accordion-button').forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('.bi-chevron-right');
                if (icon) {
                    if (this.classList.contains('collapsed')) {
                        // Collapse
                        icon.style.transform = 'rotate(0deg)';
                    } else {
                        // Open
                        icon.style.transform = 'rotate(90deg)';
                    }
                }
            });
        });
    });
</script>
@endpush