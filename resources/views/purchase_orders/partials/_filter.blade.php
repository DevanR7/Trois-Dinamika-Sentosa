<form action="{{ route('purchase-orders.index') }}" method="GET">
    <div class="row g-2 align-items-center">
        {{-- [DIUBAH] Satu kolom pencarian untuk semua --}}
        <div class="col-md-2">
            <input type="text" name="search" class="form-control" placeholder="Cari No. PO / Supplier / No. Faktur..." value="{{ request('search') }}">
        </div>

        {{-- Filter tanggal --}}
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text" style="font-size: 0.8rem;">Tgl. Pesan:</span>
                <input type="date" name="order_date" class="form-control" value="{{ request('order_date') }}">
            </div>
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text" style="font-size: 0.8rem;">Jatuh Tempo:</span>
                <input type="date" name="due_date" class="form-control" value="{{ request('due_date') }}">
            </div>
        </div>

        {{-- Filter status --}}
        <div class="col-md-2">
            <select name="payment_status" class="form-select">
                <option value="">-- Status Bayar --</option>
                <option value="unpaid" @selected(request('payment_status') == 'unpaid')>Belum Lunas</option>
                <option value="partially_paid" @selected(request('payment_status') == 'partially_paid')>Cicil</option>
                <option value="paid" @selected(request('payment_status') == 'paid')>Lunas</option>
            </select>
        </div>

        {{-- Tombol Aksi --}}
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-dark w-100" title="Filter">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary w-100" title="Reset Filter">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </div>
</form>