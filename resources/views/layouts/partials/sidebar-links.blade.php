{{-- Section Utama --}}
<li class="nav-heading">Utama</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("dashboard") ? "active" : "" }}" href="{{ route("dashboard") }}">
        <i class="bi bi-grid-1x2-fill me-2"></i> Dashboard
    </a>
</li>
<hr class="my-2">

{{-- Section Produk & Supplier --}}
<li class="nav-heading">Produk & Supplier</li>
@can('manage-suppliers')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
        <i class="bi bi-truck me-2"></i> Supplier
    </a>
</li>
@endcan
@can('manage-purchases')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
        <i class="bi bi-cart-plus-fill me-2"></i> Pesanan Pembelian
    </a>
</li>
@endcan
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("products.*") ? "active" : "" }}" href="{{ route("products.index") }}">
        <i class="bi bi-cart-fill me-2"></i> Produk
    </a>
</li>
<hr class="my-2">

{{-- Section Penjualan --}}
<li class="nav-heading">Penjualan</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("sales-orders.*") ? "active" : "" }}" href="{{ route("sales-orders.index") }}">
        <i class="bi bi-box-seam me-2"></i> Pesanan Penjualan
    </a>
</li>
@can("manage-invoices")
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("invoices.*") ? "active" : "" }}" href="{{ route("invoices.index") }}">
        <i class="bi bi-receipt me-2"></i> Invoice
    </a>
</li>
@endcan
<hr class="my-2">

{{-- Section Sistem --}}
<li class="nav-heading">Sistem</li>
@can("view-user-management")
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("users.*") ? "active" : "" }}" href="{{ route("users.index") }}">
        <i class="bi bi-people me-2"></i> Manajemen User
    </a>
</li>
@endcan
@can("manage-settings")
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("taxes.*") ? "active" : "" }}" href="{{ route("taxes.index") }}">
        <i class="bi bi-percent me-2"></i> Pengaturan Pajak
    </a>
</li>
@endcan
