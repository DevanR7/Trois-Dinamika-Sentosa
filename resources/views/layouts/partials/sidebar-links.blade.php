{{-- Section Utama --}}
@can('view-dashboard')
    <li class="nav-heading">Utama</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-grid-1x2-fill me-2"></i> Dashboard
        </a>
    </li>
@endcan

{{-- PEMBATAS OTOMATIS: Muncul jika ada menu di atas DAN di bawahnya --}}
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
        <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
            <i class="bi bi-box-seam-fill me-2"></i> Produk
        </a>
    </li>
    @endcan
@endif

{{-- PEMBATAS OTOMATIS --}}
{{-- ✅ BERUBAH: Tambahkan permission baru 'review-order-change-requests' di kondisi ini --}}
@if (Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-products']) && Auth::user()->canany(['view-clients', 'view-sales-orders', 'review-order-change-requests', 'view-invoices']))
    <hr class="my-2">
@endif

{{-- Section Penjualan --}}
{{-- ✅ BERUBAH: Tambahkan permission baru 'review-order-change-requests' di kondisi ini --}}
@if(Auth::user()->canany(['view-clients', 'view-sales-orders', 'review-order-change-requests', 'view-invoices']))
    <li class="nav-heading">Penjualan</li>
    @can('view-clients')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">
            <i class="bi bi-person-lines-fill me-2"></i> Klien
        </a>
    </li>
    @endcan
    @can('view-sales-orders')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}" href="{{ route('sales-orders.index') }}">
            <i class="bi bi-journal-text me-2"></i> Pesanan Penjualan
        </a>
    </li>
    @endcan

    {{-- ✅ MENU BARU UNTUK REVIEW REQUEST --}}
    @can('review-order-change-requests')
    <li class="nav-item">
        {{-- Gunakan route name yang sudah kita definisikan --}}
        <a class="nav-link {{ request()->routeIs('order-change-requests.*') ? 'active' : '' }}" href="{{ route('order-change-requests.index') }}">
            <i class="bi bi-bell-fill me-2 position-relative"></i> Permintaan Perubahan
            {{-- Optional: Tambahkan badge jika ada request pending --}}
            {{-- @php $pendingRequestsCount = \App\Models\OrderChangeRequest::where('status', 'pending')->count(); @endphp
            @if($pendingRequestsCount > 0)
                <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle ms-2">
                    {{ $pendingRequestsCount }}
                </span>
            @endif --}}
        </a>
    </li>
    @endcan
    {{-- =================================== --}}

    @can("view-invoices")
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
            <i class="bi bi-receipt me-2"></i> Invoice
        </a>
    </li>
    @endcan
@endif

{{-- PEMBATAS OTOMATIS --}}
{{-- ✅ BERUBAH: Tambahkan permission baru 'review-order-change-requests' di kondisi ini --}}
@if (Auth::user()->canany(['view-clients', 'view-sales-orders', 'review-order-change-requests', 'view-invoices']) && Auth::user()->canany(['view-sales-returns', 'view-purchase-returns']))
    <hr class="my-2">
@endif

{{-- Section Retur --}}
@if(Auth::user()->canany(['view-sales-returns', 'view-purchase-returns']))
    <li class="nav-heading">Retur Barang</li>
    @can('view-sales-returns')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs("sales-returns.*") ? "active" : "" }}" href="{{ route('sales-returns.index') }}">
            <i class="bi bi-box-arrow-in-down me-2"></i> Retur Penjualan
        </a>
    </li>
    @endcan
    @can('view-purchase-returns')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs("purchase-returns.*") ? "active" : "" }}" href="{{ route('purchase-returns.index') }}">
            <i class="bi bi-box-arrow-up me-2"></i> Retur Pembelian
        </a>
    </li>
    @endcan
@endif

{{-- PEMBATAS OTOMATIS --}}
@if (Auth::user()->canany(['view-sales-returns', 'view-purchase-returns', 'view-reports']) && Auth::user()->canany(['manage-users', 'manage-roles', 'manage-settings']))
    <hr class="my-2">
@endif

{{-- Section Laporan & Sistem --}}
@if(Auth::user()->canany(['view-reports', 'manage-users', 'manage-roles', 'manage-settings']))
    <li class="nav-heading">Laporan & Sistem</li>
    @can('view-reports')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs("reports.index") ? "active" : "" }}" href="{{ route("reports.index") }}">
            <i class="bi bi-file-earmark-bar-graph-fill me-2"></i> Laporan Keuangan
        </a>
    </li>
    @endcan
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
     <li class="nav-item">
        <a class="nav-link {{ request()->routeIs("settings.*") ? "active" : "" }}" href="{{ route("settings.index") }}">
            <i class="bi bi-building-gear me-2"></i> Pengaturan Perusahaan
        </a>
    </li>
    @endcan
@endif