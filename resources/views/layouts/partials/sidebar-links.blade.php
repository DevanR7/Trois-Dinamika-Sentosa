{{-- Section Utama --}}
@can('view-dashboard')
<li class="nav-heading">Utama</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("dashboard") ? "active" : "" }}" href="{{ route("dashboard") }}">
        <i class="bi bi-grid-1x2-fill me-2"></i> Dashboard
    </a>
</li>
@endcan

@if (Auth::user()->can('view-dashboard') && Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-products']))
    <hr class="my-2">
@endif

{{-- Section Gudang & Pembelian --}}
@if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-products']))
<li class="nav-heading">Gudang & Pembelian</li>
@can('view-suppliers')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
        <i class="bi bi-truck me-2"></i> Supplier
    </a>
</li>
@endcan
@can('view-purchase-orders')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
        <i class="bi bi-cart-plus-fill me-2"></i> Pesanan Pembelian
    </a>
</li>
@endcan
@can('view-products')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("products.*") ? "active" : "" }}" href="{{ route("products.index") }}">
        <i class="bi bi-box-seam-fill me-2"></i> Produk
    </a>
</li>
@endcan
@endif

@if (Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-products']) && Auth::user()->canany(['view-clients', 'view-sales-orders', 'view-invoices']))
    <hr class="my-2">
@endif

{{-- Section Penjualan --}}
@if(Auth::user()->canany(['view-clients', 'view-sales-orders', 'view-invoices']))
<li class="nav-heading">Penjualan</li>
@can('view-clients')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("clients.*") ? "active" : "" }}" href="{{ route("clients.index") }}">
        <i class="bi bi-person-lines-fill me-2"></i> Klien
    </a>
</li>
@endcan
@can('view-sales-orders')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("sales-orders.*") ? "active" : "" }}" href="{{ route("sales-orders.index") }}">
        <i class="bi bi-journal-text me-2"></i> Pesanan Penjualan
    </a>
</li>
@endcan
@can("view-invoices")
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("invoices.*") ? "active" : "" }}" href="{{ route("invoices.index") }}">
        <i class="bi bi-receipt me-2"></i> Invoice
    </a>
</li>
@endcan
@endif

@if(Auth::user()->canany(['view-sales-returns', 'view-purchase-returns']))
<li class="nav-heading">Retur Barang</li>
@can('view-sales-returns')
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("sales-returns.*") ? "active" : "" }}" href="{{ route('sales-returns.index') }}">
        <i class="bi bi-box-arrow-in-down me-2"></i><span class="menu-text">Retur Penjualan</span>
    </a>
</li>
@endcan
@can('view-purchase-returns')
<li class="nav-item">
      <a class="nav-link text-white {{ request()->routeIs("purchase-returns.*") ? "active" : "" }}" href="{{ route('purchase-returns.index') }}">
        <i class="bi bi-box-arrow-up me-2"></i><span class="menu-text">Retur Pembelian</span>
    </a>
</li>
@endcan
@endif

@if (Auth::user()->canany(['view-clients', 'view-sales-orders', 'view-invoices']) && Auth::user()->canany(['manage-users', 'manage-roles', 'manage-settings']))
    <hr class="my-2">
@endif

{{-- Section Sistem --}}
@if(Auth::user()->canany(['manage-users', 'manage-roles', 'manage-settings']))
<li class="nav-heading">Sistem</li>
@can("manage-users")
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("users.*") ? "active" : "" }}" href="{{ route("users.index") }}">
        <i class="bi bi-people me-2"></i> Manajemen User
    </a>
</li>
@endcan
@can("manage-roles")
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("roles.*") ? "active" : "" }}" href="{{ route("roles.index") }}">
        <i class="bi bi-shield-lock me-2"></i> Manajemen Role
    </a>
</li>
@endcan
@can("manage-settings")
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("taxes.*") ? "active" : "" }}" href="{{ route("taxes.index") }}">
        <i class="bi bi-percent me-2"></i> Pengaturan Pajak
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs("units.*") ? "active" : "" }}" href="{{ route("units.index") }}">
        <i class="bi bi-rulers me-2"></i> Pengaturan Satuan
    </a>
</li>
@endcan
@endif

@can("manage-settings")
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("settings.*") ? "active" : "" }}" href="{{ route("settings.index") }}">
        <i class="bi bi-building-gear me-2"></i><span class="menu-text">Pengaturan Perusahaan</span>
    </a>
</li>
@endcan