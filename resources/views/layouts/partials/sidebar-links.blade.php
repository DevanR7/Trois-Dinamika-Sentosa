{{-- ======================================================================== --}}
{{--  1. BERANDA --}}
{{-- ======================================================================== --}}
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


{{-- ======================================================================== --}}
{{--  2. MANAJEMEN KLIEN --}}
{{-- ======================================================================== --}}
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


{{-- ======================================================================== --}}
{{--  3. MANAJEMEN PRODUK --}}
{{-- ======================================================================== --}}
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


{{-- ======================================================================== --}}
{{--  4. SUPPLIER & PEMBELIAN BARANG --}}
{{-- ======================================================================== --}}
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

    @can('create-purchase-adjustments')
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
            <i class='bx bxs-bank'></i>
            <span>Pembayaran Hutang</span>
        </a>
    </li>
    @endcan

</ul>
@endif


{{-- ======================================================================== --}}
{{--  5. TRANSAKSI SALES & KLIEN --}}
{{-- ======================================================================== --}}
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
                <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingSalesOrderCount }}</span>
            @endif
        </a>
    </li>
    @endcan

    @can('review-client-orders')
    <li class="item">
        <a class="link flex {{ request()->routeIs('client-order-reviews.*') ? 'active' : '' }}" href="{{ route('client-order-reviews.index') }}">
            <i class='material-icons'>reviews</i>
            <span>Review Pesanan Klien</span>
            @php
                $pendingClientOrdersCount = \App\Models\Order::where('order_source', 'client')
                    ->where('status', 'pending_review')
                    ->count();
            @endphp
            @if($pendingClientOrdersCount > 0)
                <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingClientOrdersCount }}</span>
            @endif
        </a>
    </li>
    @endcan

    @can('review-order-change-requests')
    <li class="item">
        <a class="link flex {{ request()->routeIs('order-change-requests.*') ? 'active' : '' }}" href="{{ route('order-change-requests.index') }}">
            <i class="material-icons">edit_notifications</i>
            <span>Permintaan Perubahan</span>
            @php
                $pendingRequestsCount = \App\Models\OrderChangeRequest::where('status', 'pending')->count();
            @endphp
            @if($pendingRequestsCount > 0)
                <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingRequestsCount }}</span>
            @endif
        </a>
    </li>
    @endcan

</ul>
@endif


{{-- ======================================================================== --}}
{{--  6. INVOICES & PIUTANG --}}
{{-- ======================================================================== --}}
@if(Auth::user()->canany([
    "view-invoices",
    "create-invoice-adjustments",
    "create-batch-payments",
    "review-batch-payments",
    "manage-payment-clearance"
]))
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

    @can("create-invoice-adjustments")
    <li class="item">
        <a class="link flex {{ request()->routeIs('invoice-adjustments.*') ? 'active' : '' }}" href="{{ route('invoice-adjustments.create') }}">
            <i class="material-icons">edit_note</i>
            <span>Penyesuaian Invoice</span>
        </a>
    </li>
    @endcan

    @can("create-batch-payments")
    <li class="item">
        <a class="link flex {{ (request()->routeIs('batch-payments.*') && !request()->routeIs('batch-payments.pending') && !request()->routeIs('batch-payments.showPending')) ? 'active' : '' }}" href="{{ route('batch-payments.create') }}">
            <i class="material-icons">payments</i>
            <span>Buat Pembayaran Batch</span>
        </a>
    </li>
    @endcan

    @can("review-batch-payments")
    <li class="item">
        <a class="link flex {{ request()->routeIs('batch-payments.pending') || request()->routeIs('batch-payments.showPending') ? 'active' : '' }}" href="{{ route('batch-payments.pending') }}">
            <i class="material-icons">fact_check</i>
            <span>Verifikasi Pembayaran</span>
            @php
                $pendingBatchCount = \App\Models\BatchPayment::where('status', 'pending_verification')->count();
            @endphp
            @if($pendingBatchCount > 0)
                <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingBatchCount }}</span>
            @endif
        </a>
    </li>
    @endcan

    @can("manage-payment-clearance")
    <li class="item">
        <a class="link flex {{ request()->routeIs('payment-clearance.*') ? 'active' : '' }}" href="{{ route('payment-clearance.index') }}">
            <i class="material-icons">pending_actions</i>
            <span>Kliring Pembayaran</span>
            @php
                $pendingClearanceSales = \App\Models\Payment::where('status', 'pending_clearance')->count();
                $pendingClearancePurchase = \App\Models\PurchaseOrderPayment::where('status', 'pending_clearance')->count();
                $totalPendingClearance = $pendingClearanceSales + $pendingClearancePurchase;
            @endphp
            @if($totalPendingClearance > 0)
                <span class="badge bg-warning text-dark rounded-pill ms-auto">{{ $totalPendingClearance }}</span>
            @endif
        </a>
    </li>
    @endcan

</ul>
@endif


{{-- ======================================================================== --}}
{{--  7. RETUR BARANG --}}
{{-- ======================================================================== --}}
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


{{-- ======================================================================== --}}
{{--  8. AKUNTANSI & KEUANGAN (GABUNGAN BARU) --}}
{{-- ======================================================================== --}}
{{-- Cek apakah user punya hak akses ke salah satu fitur akuntansi --}}
@if(Auth::user()->canany(['view-reports', 'manage-settings', 'manage-bank-accounts']))
<div class="menu_title flex">
    <span class="title">Akuntansi & Keuangan</span>
    <span class="line"></span>
</div>
<ul class="menu_item">

    {{-- A. Input Operasional --}}
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
            <span>Modal & Prive</span>
        </a>
    </li>

    <li class="item">
        <a class="link flex {{ request()->routeIs('loans.*') ? 'active' : '' }}" href="{{ route('loans.index') }}">
            <i class="material-icons">account_balance</i>
            <span>Pinjaman / Utang</span>
        </a>
    </li>

    {{-- B. Proses Akuntansi (Admin/Akuntan) --}}
    @can('manage-settings')
    <li class="item">
        <a class="link flex {{ request()->routeIs("manual-journals.*") ? "active" : "" }}" href="{{ route("manual-journals.index") }}">
            <i class="material-icons">post_add</i>
            <span>Jurnal Umum Manual</span>
        </a>
    </li>

    <li class="item">
        <a class="link flex {{ request()->routeIs("bank-reconciliations.*") ? "active" : "" }}" href="{{ route("bank-reconciliations.index") }}">
            <i class="material-icons">fact_check</i>
            <span>Rekonsiliasi Bank</span>
        </a>
    </li>
    
    <li class="item">
        <a class="link flex {{ request()->routeIs("closing-book.*") ? "active" : "" }}" href="{{ route("closing-book.index") }}">
            <i class="material-icons">lock_clock</i>
            <span>Tutup Buku Tahunan</span>
        </a>
    </li>
    @endcan

    {{-- C. Data Induk Akuntansi --}}
    <li class="item">
        <a class="link flex {{ request()->routeIs("chart-of-accounts.*") ? "active" : "" }}" href="{{ route("chart-of-accounts.index") }}">
            <i class="material-icons">account_tree</i>
            <span>Daftar Akun (COA)</span>
        </a>
    </li>

    @can("manage-bank-accounts")
    <li class="item">
        <a class="link flex {{ request()->routeIs("company-bank-accounts.*") ? "active" : "" }}" href="{{ route("company-bank-accounts.index") }}">
            <i class="material-icons">account_balance</i>
            <span>Akun Bank Perusahaan</span>
        </a>
    </li>
    @endcan

</ul>
@endif


{{-- ======================================================================== --}}
{{--  9. LAPORAN (KHUSUS OUTPUT) --}}
{{-- ======================================================================== --}}
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
        <a class="link flex {{ request()->routeIs("reports.general-ledger") ? "active" : "" }}" href="{{ route("reports.general-ledger") }}">
            <i class="material-icons">menu_book</i>
            <span>Buku Besar Detail</span>
        </a>
    </li>
</ul>
@endcan


{{-- ======================================================================== --}}
{{--  10. PENGATURAN SISTEM & UMUM --}}
{{-- ======================================================================== --}}
@if(Auth::user()->canany(["manage-settings", "manage-payment-methods", "manage-users", "manage-roles"]))
<div class="menu_title flex">
    <span class="title">Pengaturan</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    
    {{-- Pengaturan Bisnis --}}
    @can("manage-settings")
    <li class="item">
        <a class="link flex {{ request()->routeIs("settings.*") ? "active" : "" }}" href="{{ route("settings.index") }}">
            <i class="material-icons">business</i>
            <span>Profil Perusahaan</span>
        </a>
    </li>

    <li class="item">
        <a class="link flex {{ request()->routeIs("units.*") ? "active" : "" }}" href="{{ route("units.index") }}">
            <i class="material-icons">straighten</i>
            <span>Satuan</span>
        </a>
    </li>

    <li class="item">
        <a class="link flex {{ request()->routeIs("taxes.*") ? "active" : "" }}" href="{{ route("taxes.index") }}">
            <i class="material-icons">percent</i>
            <span>Pajak</span>
        </a>
    </li>
    @endcan

    @can("manage-payment-methods")
    <li class="item">
        <a class="link flex {{ request()->routeIs("payment-methods.*") ? "active" : "" }}" href="{{ route("payment-methods.index") }}">
            <i class="material-icons">payment</i>
            <span>Metode Pembayaran</span>
        </a>
    </li>
    @endcan

    {{-- Manajemen User & Role --}}
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


{{-- ======================================================================== --}}
{{--  11. KOMUNIKASI --}}
{{-- ======================================================================== --}}
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

<div class="menu_title flex">
    <span class="title">Sistem</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    <li class="item">
        <a class="link flex" href="#" style="cursor: default; background: none; box-shadow: none; opacity: 0.7;">
            <i class="material-icons">info_outline</i>
            <span>Versi: {{ $systemVersion ?? '1.0.0' }}</span>
        </a>
    </li>
</ul>