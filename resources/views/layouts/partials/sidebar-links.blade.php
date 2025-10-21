{{-- 1. Beranda --}}
@can('view-dashboard')
    <div class="menu_title flex">
        <span class="title">Beranda</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link flex {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bx bx-home-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>
@endcan

{{-- 2. Manajemen Klien --}}
@can('view-clients')
    <div class="menu_title flex">
        <span class="title">Manajemen Klien</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link flex {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">
                <i class="bx bxs-user-account"></i>
                <span>Klien</span>
            </a>
        </li>
    </ul>
@endcan

{{-- 3. Manajemen Produk --}}
@can('view-products')
    <div class="menu_title flex">
        <span class="title">Manajemen Produk</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link flex {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                <i class="bx bxs-box"></i>
                <span>Produk</span>
            </a>
        </li>
    </ul>
@endcan

{{-- 4. Manajemen Supplier dan Pembelian Barang --}}
@if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders']))
    <div class="menu_title flex">
        <span class="title">Supplier & Pembelian</span> {{-- Disingkat --}}
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        @can('view-suppliers')
        <li class="item">
            <a class="link flex {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                <i class="bx bxs-truck"></i>
                <span>Supplier</span>
            </a>
        </li>
        @endcan
        @can('view-purchase-orders')
        <li class="item">
            <a class="link flex {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
                <i class="bx bxs-cart-add"></i>
                <span>Pesanan Pembelian</span>
            </a>
        </li>
        @endcan
    </ul>
@endif

{{-- 5. Transaksi Sales --}}
@if(Auth::user()->canany(['view-sales-orders', 'review-order-change-requests']))
    <div class="menu_title flex">
        <span class="title">Transaksi Sales</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        @can('view-sales-orders')
        <li class="item">
            <a class="link flex {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}" href="{{ route('sales-orders.index') }}">
                <i class="bx bxs-file-doc"></i>
                <span>Pesanan Penjualan</span>
            </a>
        </li>
        @endcan
        @can('review-order-change-requests')
        <li class="item">
            <a class="link flex {{ request()->routeIs('order-change-requests.*') ? 'active' : '' }}" href="{{ route('order-change-requests.index') }}">
                <i class="bx bxs-bell-ring position-relative"></i>
                <span>Permintaan Perubahan</span>
                @php $pendingRequestsCount = \App\Models\OrderChangeRequest::where('status', 'pending')->count(); @endphp
                @if($pendingRequestsCount > 0)
                    <span class="badge bg-danger rounded-pill">
                        {{ $pendingRequestsCount }}
                    </span>
                @endif
            </a>
        </li>
        @endcan
    </ul>
@endif

{{-- 6. Invoices --}}
@can("view-invoices")
    <div class="menu_title flex">
        <span class="title">Invoices</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link flex {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                <i class="bx bx-receipt"></i>
                <span>Invoice</span>
            </a>
        </li>
    </ul>
@endcan

{{-- 7. Retur/ Pengembalian Barang --}}
@if(Auth::user()->canany(['view-sales-returns', 'view-purchase-returns']))
    <div class="menu_title flex">
        <span class="title">Retur Barang</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        @can('view-sales-returns')
        <li class="item">
            <a class="link flex {{ request()->routeIs("sales-returns.*") ? "active" : "" }}" href="{{ route('sales-returns.index') }}">
                <i class="bx bx-archive-in"></i>
                <span>Retur Penjualan</span>
            </a>
        </li>
        @endcan
        @can('view-purchase-returns')
        <li class="item">
            <a class="link flex {{ request()->routeIs("purchase-returns.*") ? "active" : "" }}" href="{{ route('purchase-returns.index') }}">
                <i class="bx bx-archive-out"></i>
                <span>Retur Pembelian</span>
            </a>
        </li>
        @endcan
    </ul>
@endif

{{-- 8. Laporan --}}
@can('view-reports')
    <div class="menu_title flex">
        <span class="title">Laporan</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link flex {{ request()->routeIs("reports.index") ? "active" : "" }}" href="{{ route("reports.index") }}">
                <i class="bx bxs-file-blank"></i>
                <span>Laporan Keuangan</span>
            </a>
        </li>
    </ul>
@endcan

{{-- 9. Pengaturan Satuan dan Pajak --}}
{{-- Asumsi keduanya memerlukan permission 'manage-settings' --}}
@can("manage-settings")
    <div class="menu_title flex">
        <span class="title">Satuan & Pajak</span> {{-- Disingkat --}}
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link flex {{ request()->routeIs("units.*") ? "active" : "" }}" href="{{ route("units.index") }}">
                <i class="bx bx-ruler"></i>
                <span>Pengaturan Satuan</span>
            </a>
        </li>
         <li class="item">
            <a class="link flex {{ request()->routeIs("taxes.*") ? "active" : "" }}" href="{{ route("taxes.index") }}">
                <i class="bx bx-file-blank"></i>
                <span>Pengaturan Pajak</span>
            </a>
        </li>
    </ul>
@endcan

{{-- 10. Manajemen Sistem --}}
@if(Auth::user()->canany(['manage-users', 'manage-roles']))
    <div class="menu_title flex">
        <span class="title">Manajemen Sistem</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        @can("manage-users")
        <li class="item">
            <a class="link flex {{ request()->routeIs("users.*") ? "active" : "" }}" href="{{ route("users.index") }}">
                <i class="bx bxs-group"></i>
                <span>Manajemen User</span>
            </a>
        </li>
        @endcan
        @can("manage-roles")
        <li class="item">
            <a class="link flex {{ request()->routeIs("roles.*") ? "active" : "" }}" href="{{ route("roles.index") }}">
                <i class="bx bxs-key"></i>
                <span>Manajemen Role</span>
            </a>
        </li>
        @endcan
    </ul>
@endif

{{-- 11. Manajemen Perusahaan --}}
@can("manage-settings")
    <div class="menu_title flex">
        <span class="title">Manajemen Perusahaan</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
         <li class="item">
            <a class="link flex {{ request()->routeIs("settings.*") ? "active" : "" }}" href="{{ route("settings.index") }}">
                <i class="bx bxs-buildings"></i>
                <span>Pengaturan Perusahaan</span>
            </a>
        </li>
    </ul>
@endcan