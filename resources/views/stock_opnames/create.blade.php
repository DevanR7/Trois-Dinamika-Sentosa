@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Stock Opname Baru</h3>
            <p class="text-muted mb-0 small">Lakukan penyesuaian stok fisik gudang dengan sistem.</p>
        </div>
        <div>
            <a href="{{ route('stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-12">
            
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('stock-opnames.store') }}" method="POST" id="opname-form">
                @csrf
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                        <div class="form-section-title mb-0"><i class="bi bi-clipboard-check"></i> Form Input Opname</div>
                        <span class="badge bg-light text-dark border">{{ now()->format('d M Y') }}</span>
                    </div>
                    
                    <div class="card-body p-4">
                        
                        {{-- INFO & HEADER INPUT --}}
                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">TANGGAL OPNAME</label>
                                <input type="date" name="opname_date" class="form-control" value="{{ old('opname_date', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-muted">CATATAN / KETERANGAN</label>
                                <input type="text" name="notes" class="form-control" placeholder="Contoh: Opname rutin bulan ini..." value="{{ old('notes') }}">
                            </div>
                        </div>

                        {{-- SEARCH BAR --}}
                        <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-3 rounded border border-dashed">
                            <div class="d-flex align-items-center text-primary">
                                <i class="bi bi-info-circle me-2 fs-5"></i>
                                <span class="small fw-bold">Isi kolom "Fisik" dengan jumlah riil di gudang.</span>
                            </div>
                            <div class="input-group" style="max-width: 350px;">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="product-search" class="form-control border-start-0 ps-0" placeholder="Cari nama produk...">
                            </div>
                        </div>

                        {{-- TABEL INPUT --}}
                        <div class="table-responsive border rounded mb-4" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover table-transaction align-middle mb-0" id="opname-table">
                                <thead class="bg-white sticky-top" style="z-index: 10;">
                                    <tr>
                                        <th style="width: 40%;" class="ps-4">Produk</th>
                                        <th style="width: 15%;" class="text-center bg-light">System</th>
                                        <th style="width: 25%;" class="text-center bg-primary bg-opacity-10 text-primary border-bottom border-primary">Fisik (Input)</th>
                                        <th style="width: 20%;" class="text-center">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $index => $product)
                                    <tr class="product-row">
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark product-name">{{ $product->product_name }}</div>
                                            <small class="text-muted">{{ $product->product_code }}</small>
                                            <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $product->product_id }}">
                                        </td>
                                        <td class="text-center bg-light">
                                            <input type="text" class="form-control-plaintext text-center fw-bold text-muted system-qty" value="{{ $product->stock_quantity }}" readonly>
                                        </td>
                                        <td class="bg-primary bg-opacity-10 p-2">
                                            <input type="number" 
                                                   name="products[{{ $index }}][physical_qty]" 
                                                   class="form-control text-center fw-bold fs-5 border-primary physical-qty-input" 
                                                   value="{{ $product->stock_quantity }}" 
                                                   min="0" required>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-25 text-secondary border fs-6 difference-badge" style="min-width: 60px;">0</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end pt-3 border-top">
                            <a href="{{ route('stock-opnames.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                <i class="bi bi-check-circle me-2"></i> Simpan Hasil Opname
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Logic Pencarian Produk
    const searchInput = document.getElementById('product-search');
    const tableRows = document.querySelectorAll('.product-row');

    searchInput.addEventListener('keyup', function(e) {
        const term = e.target.value.toLowerCase();
        tableRows.forEach(row => {
            const name = row.querySelector('.product-name').textContent.toLowerCase();
            if (name.includes(term)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // 2. Logic Kalkulasi Selisih Real-time
    const inputs = document.querySelectorAll('.physical-qty-input');

    function updateDifference(row) {
        const systemQty = parseInt(row.querySelector('.system-qty').value) || 0;
        const physicalQtyInput = row.querySelector('.physical-qty-input');
        const physicalQty = parseInt(physicalQtyInput.value); 
        
        const badge = row.querySelector('.difference-badge');

        if (isNaN(physicalQty)) {
            badge.textContent = '-';
            badge.className = 'badge bg-secondary bg-opacity-25 text-secondary border fs-6 difference-badge';
            return;
        }

        const diff = physicalQty - systemQty;
        
        if (diff > 0) {
            badge.textContent = '+' + diff;
            badge.className = 'badge bg-success bg-opacity-10 text-success border border-success fs-6 difference-badge'; 
        } else if (diff < 0) {
            badge.textContent = diff;
            badge.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger fs-6 difference-badge'; 
        } else {
            badge.textContent = '0';
            badge.className = 'badge bg-secondary bg-opacity-25 text-secondary border fs-6 difference-badge'; 
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', function() {
            updateDifference(this.closest('tr'));
        });
        updateDifference(input.closest('tr'));
    });

    // 3. SweetAlert Konfirmasi Submit
    const form = document.getElementById('opname-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        let changedItems = 0;
        inputs.forEach(input => {
            const row = input.closest('tr');
            const system = parseInt(row.querySelector('.system-qty').value);
            const physical = parseInt(input.value);
            if (system !== physical) changedItems++;
        });

        Swal.fire({
            title: 'Simpan Stock Opname?',
            text: `Anda akan menyesuaikan stok untuk ${changedItems} barang yang memiliki selisih.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

});
</script>
@endpush