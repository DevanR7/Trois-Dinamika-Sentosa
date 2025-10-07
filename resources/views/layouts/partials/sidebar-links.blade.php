{{-- Section Utama --}}
@can('view-dashboard')
<li class="nav-heading"><span class="menu-text">Utama</span></li>
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("dashboard") ? "active" : "" }}" href="{{ route("dashboard") }}">
        <i class="bi bi-grid-1x2-fill me-2"></i><span class="menu-text">Dashboard</span>
    </a>
</li>
@endcan

{{-- PEMBATAS OTOMATIS 1 --}}
@if (Auth::user()->can('view-dashboard') && Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-products']))
    <hr class="my-2">
@endif


{{-- Section Produk & Supplier --}}
@if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-products']))
<li class="nav-heading"><span class="menu-text">Gudang & Pembelian</span></li>
@can('view-suppliers')
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
        <i class="bi bi-truck me-2"></i><span class="menu-text">Supplier</span>
    </a>
</li>
@endcan
@can('view-purchase-orders')
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
        <i class="bi bi-cart-plus-fill me-2"></i><span class="menu-text">Pesanan Pembelian</span>
    </a>
</li>
@endcan
@can('view-products')
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("products.*") ? "active" : "" }}" href="{{ route("products.index") }}">
        <i class="bi bi-box-seam-fill me-2"></i><span class="menu-text">Produk</span>
    </a>
</li>
@endcan
@endif

{{-- PEMBATAS OTOMATIS 2 --}}
@if (Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-products']) && Auth::user()->canany(['view-clients', 'view-sales-orders', 'view-invoices']))
    <hr class="my-2">
@endif


{{-- Section Penjualan --}}
@if(Auth::user()->canany(['view-clients', 'view-sales-orders', 'view-invoices']))
<li class="nav-heading"><span class="menu-text">Penjualan</span></li>
@can('view-clients')
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("clients.*") ? "active" : "" }}" href="{{ route("clients.index") }}">
        <i class="bi bi-person-lines-fill me-2"></i><span class="menu-text">Klien</span>
    </a>
</li>
@endcan
@can('view-sales-orders')
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("sales-orders.*") ? "active" : "" }}" href="{{ route("sales-orders.index") }}">
        <i class="bi bi-journal-text me-2"></i><span class="menu-text">Pesanan Penjualan</span>
    </a>
</li>
@endcan
@can("view-invoices")
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("invoices.*") ? "active" : "" }}" href="{{ route("invoices.index") }}">
        <i class="bi bi-receipt me-2"></i><span class="menu-text">Invoice</span>
    </a>
</li>
@endcan
@endif

{{-- PEMBATAS OTOMATIS 3 --}}
@if (Auth::user()->canany(['view-clients', 'view-sales-orders', 'view-invoices']) && Auth::user()->canany(['manage-users', 'manage-roles', 'manage-settings']))
    <hr class="my-2">
@endif


{{-- Section Sistem --}}
@if(Auth::user()->canany(['manage-users', 'manage-roles', 'manage-settings']))
<li class="nav-heading"><span class="menu-text">Sistem</span></li>
@can("manage-users")
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("users.*") ? "active" : "" }}" href="{{ route("users.index") }}">
        <i class="bi bi-people me-2"></i><span class="menu-text">Manajemen User</span>
    </a>
</li>
@endcan
@can("manage-roles")
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("roles.*") ? "active" : "" }}" href="{{ route("roles.index") }}">
        <i class="bi bi-shield-lock me-2"></i><span class="menu-text">Manajemen Role</span>
    </a>
</li>
@endcan
@can("manage-settings")
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("taxes.*") ? "active" : "" }}" href="{{ route("taxes.index") }}">
        <i class="bi bi-percent me-2"></i><span class="menu-text">Pengaturan Pajak</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link text-white {{ request()->routeIs("units.*") ? "active" : "" }}" href="{{ route("units.index") }}">
        <i class="bi bi-rulers me-2"></i><span class="menu-text">Pengaturan Satuan</span>
    </a>
</li>
@endcan
@endif