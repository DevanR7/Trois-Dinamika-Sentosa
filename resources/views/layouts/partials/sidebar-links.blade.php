{{-- 1. Beranda --}}
@can('view-dashboard')
    <div class="menu_title flex">
        <span class="title">Beranda</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link flex {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="material-icons">space_dashboard</i>
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
                <i class="material-icons">account_circle</i>
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
                <i class="material-icons">inventory_2</i>
                <span>Produk</span>
            </a>
        </li>
    </ul>
@endcan

{{-- 4. Manajemen Supplier dan Pembelian Barang --}}
@if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders']))
    <div class="menu_title flex">
        <span class="title">Supplier & Pembelian</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        @can('view-suppliers')
        <li class="item">
            <a class="link flex {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                <i class="material-icons">local_shipping</i>
                <span>Supplier</span>
            </a>
        </li>
        @endcan
        @can('view-purchase-orders')
        <li class="item">
            <a class="link flex {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
                <i class="material-icons">add_shopping_cart</i>
                <span>Pesanan Pembelian</span>
            </a>
        </li>
        @endcan
        @can('create-purchase-adjustments') {{-- (Kita akan buat permission ini) --}}
        <li class="item">
            <a class="link flex {{ request()->routeIs('purchase-order-adjustments.*') ? 'active' : '' }}" href="{{ route('purchase-order-adjustments.create') }}">
                <i class="material-icons">edit_note</i>
                <span>Penyesuaian PO</span>
            </a>
        </li>
        @endcan
        @can('create-batch-purchase-payments')
        <li class="item">
            <a class="link flex {{ request()->routeIs('batch-purchase-payments.*') ? 'active' : '' }}" href="{{ route('batch-purchase-payments.create') }}">
                <i class='bx bxs-bank'></i> {{-- Ikon Bank/Pembayaran --}}
                <span>Pembayaran Hutang</span>
            </a>
        </li>
        @endcan
    </ul>
@endif

{{-- 5. Transaksi Sales & Klien --}}
@if(Auth::user()->canany(['view-sales-orders', 'review-client-orders', 'review-order-change-requests']))
    <div class="menu_title flex">
        <span class="title">Transaksi Sales & Klien</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        @can('view-sales-orders')
        <li class="item">
            <a class="link flex {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}" href="{{ route('sales-orders.index') }}">
                <i class="material-icons">description</i>
                <span>Pesanan (dari Sales)</span>

                @if(isset($pendingSalesOrderCount) && $pendingSalesOrderCount > 0)
                    <span class="badge bg-danger rounded-pill ms-auto"> 
                        {{ $pendingSalesOrderCount }}
                    </span>
                @endif
            </a>
        </li>
        @endcan

        @can('review-client-orders')
        <li class="item">
            <a class="link flex {{ request()->routeIs('client-order-reviews.*') ? 'active' : '' }}" href="{{ route('client-order-reviews.index') }}">
                <i class='material-icons position-relative'>reviews</i>
                <span>Review Pesanan Klien</span>
                @php $pendingClientOrdersCount = \App\Models\Order::where('order_source', 'client')->where('status', 'pending_review')->count(); @endphp
                @if($pendingClientOrdersCount > 0)
                    <span class="badge bg-danger rounded-pill ms-auto">
                        {{ $pendingClientOrdersCount }}
                    </span>
                @endif
            </a>
        </li>
        @endcan

        @can('review-order-change-requests')
        <li class="item">
            <a class="link flex {{ request()->routeIs('order-change-requests.*') ? 'active' : '' }}" href="{{ route('order-change-requests.index') }}">
                <i class="material-icons position-relative">edit_notifications</i>
                <span>Permintaan Perubahan</span>
                @php $pendingRequestsCount = \App\Models\OrderChangeRequest::where('status', 'pending')->count(); @endphp
                @if($pendingRequestsCount > 0)
                    <span class="badge bg-danger rounded-pill ms-auto">
                        {{ $pendingRequestsCount }}
                    </span>
                @endif
            </a>
        </li>
        @endcan
    </ul>
@endif

{{-- ======================================================================== --}}
{{-- ✅ PERUBAHAN DIMULAI DARI SINI --}}
{{-- ======================================================================== --}}

{{-- 6. Invoices & Piutang --}}
{{-- Saya tambahkan permission 'create-invoice-adjustments' ke 'canany' --}}
@if(Auth::user()->canany(["view-invoices", "create-batch-payments", "create-invoice-adjustments"]))
    <div class="menu_title flex">
        <span class="title">Invoices & Piutang</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        @can("view-invoices")
        <li class="item">
            <a class="link flex {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                <i class="material-icons">receipt_long</i>
                <span>Daftar Invoice</span>
            </a>
        </li>
        @endcan
        
        {{-- ✅ INI LINK BARU ANDA --}}
        @can("create-invoice-adjustments") {{-- (Pastikan Anda membuat permission ini) --}}
        <li class="item">
            <a class="link flex {{ request()->routeIs('invoice-adjustments.*') ? 'active' : '' }}" href="{{ route('invoice-adjustments.create') }}">
                <i class="material-icons">edit_note</i>
                <span>Penyesuaian Invoice</span>
            </a>
        </li>
        @endcan
        {{-- ✅ AKHIR DARI LINK BARU --}}
        
        @can("create-batch-payments")
        <li class="item">
            <a class="link flex {{ request()->routeIs('batch-payments.*') ? 'active' : '' }}" href="{{ route('batch-payments.create') }}">
                <i class="material-icons">payments</i>
                <span>Pembayaran Piutang</span>
            </a>
        </li>
        @endcan
    </ul>
@endif

{{-- ======================================================================== --}}
{{-- ✅ PERUBAHAN SELESAI --}}
{{-- ======================================================================== --}}


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
                <i class="material-icons">move_to_inbox</i>
                <span>Retur Penjualan</span>
            </a>
        </li>
        @endcan
        @can('view-purchase-returns')
        <li class="item">
            <a class="link flex {{ request()->routeIs("purchase-returns.*") ? "active" : "" }}" href="{{ route('purchase-returns.index') }}">
                <i class="material-icons">outbox</i>
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
                <i class="material-icons">analytics</i>
                <span>Laporan Keuangan</span>
            </a>
        </li>
        <li class="item">
            <a class="link flex {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                <i class="material-icons">account_balance_wallet</i>
                <span>Beban Operasional</span>
            </a>
        </li>
        <li class="item">
            <a class="link flex {{ request()->routeIs('fixed-assets.*') ? 'active' : '' }}" href="{{ route('fixed-assets.index') }}">
                <i class="material-icons">apartment</i>
                <span>Aset Tetap</span>
            </a>
        </li>
        <li class="item">
            <a class="link flex {{ request()->routeIs('equity-transactions.*') ? 'active' : '' }}" href="{{ route('equity-transactions.index') }}">
                <i class="material-icons">savings</i>
                <span>Transaksi Modal</span>
            </a>
        </li>
        <li class="item">
            <a class="link flex {{ request()->routeIs('loans.*') ? 'active' : '' }}" href="{{ route('loans.index') }}">
                <i class="material-icons">account_balance</i>
                <span>Pinjaman</span>
            </a>
        </li>
    </ul>
@endcan

{{-- 9. Pengaturan Satuan dan Pajak --}}
@can("manage-settings")
    <div class="menu_title flex">
        <span class="title">Satuan & Pajak</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link flex {{ request()->routeIs("units.*") ? "active" : "" }}" href="{{ route("units.index") }}">
                <i class="material-icons">straighten</i>
                <span>Pengaturan Satuan</span>
            </a>
        </li>
         <li class="item">
            <a class="link flex {{ request()->routeIs("taxes.*") ? "active" : "" }}" href="{{ route("taxes.index") }}">
                <i class="material-icons">percent</i>
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
                <i class="material-icons">group</i>
                <span>Manajemen User</span>
            </a>
        </li>
        @endcan
        @can("manage-roles")
        <li class="item">
            <a class="link flex {{ request()->routeIs("roles.*") ? "active" : "" }}" href="{{ route("roles.index") }}">
                <i class="material-icons">vpn_key</i>
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
                <i class="material-icons">business</i>
                <span>Pengaturan Perusahaan</span>
            </a>
        </li>
    </ul>
@endcan

@can("manage-announcements")
    <div class="menu_title flex">
        <span class="title">Komunikasi</span>
        <span class="line"></span>
    </div>
    <ul class="menu_item">
         <li class="item">
            <a class="link flex {{ request()->routeIs("announcements.*") ? "active" : "" }}" href="{{ route("announcements.index") }}">
                <i class="material-icons">campaign</i>
                <span>Manajemen Pengumuman</span>
            </a>
        </li>
    </ul>
@endcan