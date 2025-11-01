@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Manajemen Supplier</h2>
        <div>
            {{-- TOMBOL LIHAT ARSIP --}}
            @if(request('status') === 'deleted')
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Supplier Aktif
                </a>
            @else
                <a href="{{ route('suppliers.index', ['status' => 'deleted']) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-archive me-2"></i>Lihat Arsip Supplier
                </a>
                <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Supplier Baru
                </a>
            @endif
        </div>
    </div>
    
    {{-- MENGGANTI TABLE DENGAN ACCORDION --}}
    <div class="accordion shadow-sm" id="supplierAccordion">

        @forelse ($suppliers as $supplier)
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{{ $supplier->supplier_id }}">
                {{-- TAMPILAN TERTUTUP (COLLAPSED) --}}
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#collapse-{{ $supplier->supplier_id }}" aria-expanded="false" 
                        aria-controls="collapse-{{ $supplier->supplier_id }}">
                    
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        
                        {{-- Kiri: Nama & PIC --}}
                        <div class="d-flex flex-column">
                            <strong class="fs-5">{{ $supplier->supplier_name }}</strong>
                            <span class="text-muted small">
                                <i class="bi bi-person me-1"></i>
                                {{ $supplier->person_in_charge ?? 'Belum ada PIC' }}
                            </span>
                        </div>
                        
                        {{-- Kanan: Telepon, Saldo Deposit & Status --}}
                        <div class="me-3 d-flex align-items-center">
                            <span class="text-muted me-3 d-none d-md-block">
                                <i class="bi bi-telephone me-1"></i>
                                {{ $supplier->phone_number ?? '-' }}
                            </span>

                            {{-- Saldo Deposit (Tampilan Collapsed) --}}
                            <div class="me-3 d-none d-lg-block">
                                {{-- Gunakan accessor 'balance' --}}
                                @if($supplier->balance > 0)
                                    <span class="text-success fw-bold">
                                        Rp {{ number_format($supplier->balance, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-muted">Rp 0</span>
                                @endif
                                <small class="d-block text-muted" style="font-size: 0.75rem;">Saldo Deposit</small>
                            </div>

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
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-md-5">
                            <strong>Detail Kontak:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="bi bi-person-badge me-2 text-muted"></i>{{ $supplier->person_in_charge ?? '-' }}</li>
                                <li><i class="bi bi-telephone me-2 text-muted"></i>{{ $supplier->phone_number ?? '-' }}</li>
                                <li><i class="bi bi-geo-alt me-2 text-muted"></i>{{ $supplier->address ?? '-' }}</li>
                            </ul>
                        </div>
                        <div class="col-md-5">
                            <strong>Detail Bank & Saldo:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="bi bi-card-heading me-2 text-muted"></i>NPWP: {{ $supplier->npwp ?? '-' }}</li>
                                <li><i class="bi bi-bank me-2 text-muted"></i>Bank: {{ $supplier->bank_name ?? '-' }}</li>
                                <li><i class="bi bi-hash me-2 text-muted"></i>No. Rek: {{ $supplier->account_number ?? '-' }}</li>
                                
                                {{-- =================================== --}}
                                {{-- ✅ PERBAIKAN BUG DI SINI --}}
                                {{-- =================================== --}}
                                <li class="mt-2">
                                    <i class="bi bi-wallet2 me-2 text-muted"></i>
                                    Saldo Deposit: 
                                    {{-- Ganti 'debit_balance' menjadi 'balance' --}}
                                    <strong class="{{ $supplier->balance > 0 ? 'text-success' : 'text-dark' }}">
                                        Rp {{ number_format($supplier->balance, 0, ',', '.') }}
                                    </strong>
                                </li>
                                {{-- =================================== --}}
                            </ul>
                        </div>
                        <div class="col-md-2 d-flex flex-column justify-content-center gap-2">
                            {{-- Tombol Aksi --}}
                            @if($supplier->trashed())
                                {{-- =================================== --}}
                                {{-- ✅ TAMBAHAN BARU DI SINI --}}
                                {{-- =================================== --}}
                                <a href="{{ route('suppliers.show', $supplier->supplier_id) }}" class="btn btn-outline-info w-100" title="Lihat Detail">
                                    <i class="bi bi-eye me-1"></i> Lihat
                                </a>
                                {{-- =================================== --}}

                                {{-- JIKA TERHAPUS: Tampilkan tombol RESTORE --}}
                                <form action="{{ route('suppliers.restore', $supplier->supplier_id) }}" method="POST" class="form-restore d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-info w-100" data-name="{{ $supplier->supplier_name }}">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                                    </button>
                                </form>
                            @else
                                {{-- =================================== --}}
                                {{-- ✅ TAMBAHAN BARU DI SINI --}}
                                {{-- =================================== --}}
                                <a href="{{ route('suppliers.show', $supplier->supplier_id) }}" class="btn btn-outline-info w-100" title="Lihat Detail">
                                    <i class="bi bi-eye me-1"></i> Lihat
                                </a>
                                {{-- =================================== --}}

                                {{-- JIKA AKTIF: Tampilkan tombol Edit & Arsipkan --}}
                                <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}" class="btn btn-outline-secondary w-100" title="Edit">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </a>
                                <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST" class="form-delete d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100" title="Arsipkan" data-name="{{ $supplier->supplier_name }}">
                                        <i class="bi bi-archive me-1"></i> Arsipkan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        {{-- Tampilan jika tidak ada supplier --}}
        <div class="accordion-item">
            <div class="accordion-header">
                <button class="accordion-button collapsed" type="button" disabled>
                    Tidak ada data supplier.
                </button>
            </div>
        </div>
        @endforelse

    </div>

    {{-- Pagination Links --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $suppliers->appends(request()->query())->links() }}
    </div>
</div>
@endsection

{{-- ✅ TAMBAHKAN SCRIPT SWEETALERT --}}
@push('scripts')
{{-- Pastikan Anda sudah memuat SweetAlert2 di layout utama --}}
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

        
        // --- 2. KONFIRMASI "DELETE" (ARSIPKAN) ---
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
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Arsipkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });

        // --- 3. KONFIRMASI "PULIHKAN/RESTORE" ---
        document.querySelectorAll('.form-restore').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const supplierName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Pulihkan Supplier Ini?',
                    text: `Anda akan memulihkan supplier "${supplierName}".`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#17a2b8', // Biru-Info
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Pulihkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });

    });
</script>
@endpush