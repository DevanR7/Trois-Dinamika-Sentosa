@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Form Stock Opname (Penyesuaian Stok)</h4>
                    <span class="badge bg-white text-primary">{{ now()->format('d M Y') }}</span>
                </div>
                <div class="card-body p-4">
                    
                    {{-- Alert Info --}}
                    <div class="alert alert-info border-0 d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Cara Penggunaan:</strong> Masukkan jumlah <u>Stok Fisik</u> (hasil hitung gudang) pada kolom input. 
                            Sistem akan otomatis menghitung selisih dan menjurnal penyesuaiannya.
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('stock-opnames.store') }}" method="POST" id="opname-form">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tanggal Opname</label>
                                <input type="date" name="opname_date" class="form-control" value="{{ old('opname_date', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Catatan / Keterangan</label>
                                <input type="text" name="notes" class="form-control" placeholder="Contoh: Opname rutin bulan November, atau Penyesuaian barang rusak" value="{{ old('notes') }}">
                            </div>
                        </div>

                        <hr>

                        {{-- Search Bar --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Daftar Barang</h5>
                            <div class="input-group" style="max-width: 300px;">
                                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                <input type="text" id="product-search" class="form-control" placeholder="Cari nama barang...">
                            </div>
                        </div>

                        {{-- Tabel Input --}}
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-bordered table-hover align-middle" id="opname-table">
                                <thead class="table-light sticky-top" style="z-index: 10;">
                                    <tr>
                                        <th style="width: 40%;">Nama Produk</th>
                                        <th style="width: 20%;" class="text-center bg-light">Stok Sistem</th>
                                        <th style="width: 20%;" class="text-center bg-primary text-white">Stok Fisik (Input)</th>
                                        <th style="width: 20%;" class="text-center">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $index => $product)
                                    <tr class="product-row">
                                        <td>
                                            <div class="fw-bold product-name">{{ $product->product_name }}</div>
                                            <small class="text-muted">{{ $product->product_code }}</small>
                                            {{-- Hidden Inputs --}}
                                            <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $product->product_id }}">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" class="form-control-plaintext text-center fw-bold system-qty" value="{{ $product->stock_quantity }}" readonly>
                                        </td>
                                        <td class="bg-primary bg-opacity-10">
                                            <input type="number" 
                                                   name="products[{{ $index }}][physical_qty]" 
                                                   class="form-control text-center fw-bold physical-qty-input" 
                                                   value="{{ $product->stock_quantity }}" 
                                                   min="0" required>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary fs-6 difference-badge">0</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('stock-opnames.index') }}" class="btn btn-light me-2">Batal</a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i> Simpan & Sesuaikan Stok
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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
        const physicalQty = parseInt(physicalQtyInput.value); // Bisa NaN jika kosong
        
        const badge = row.querySelector('.difference-badge');

        if (isNaN(physicalQty)) {
            badge.textContent = '-';
            badge.className = 'badge bg-secondary fs-6 difference-badge';
            return;
        }

        const diff = physicalQty - systemQty;
        
        if (diff > 0) {
            badge.textContent = '+' + diff;
            badge.className = 'badge bg-success fs-6 difference-badge'; // Hijau (Lebih)
        } else if (diff < 0) {
            badge.textContent = diff;
            badge.className = 'badge bg-danger fs-6 difference-badge'; // Merah (Kurang)
        } else {
            badge.textContent = '0';
            badge.className = 'badge bg-secondary fs-6 difference-badge'; // Abu (Sama)
        }
    }

    // Pasang event listener ke semua input
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            updateDifference(this.closest('tr'));
        });
        // Jalankan sekali saat load (jika ada old value)
        updateDifference(input.closest('tr'));
    });

    // 3. SweetAlert Konfirmasi Submit
    const form = document.getElementById('opname-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Hitung ringkasan perubahan
        let changedItems = 0;
        inputs.forEach(input => {
            const row = input.closest('tr');
            const system = parseInt(row.querySelector('.system-qty').value);
            const physical = parseInt(input.value);
            if (system !== physical) changedItems++;
        });

        Swal.fire({
            title: 'Simpan Stock Opname?',
            text: `Anda akan menyesuaikan stok untuk ${changedItems} barang yang memiliki selisih. Jurnal penyesuaian akan dibuat otomatis.`,
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