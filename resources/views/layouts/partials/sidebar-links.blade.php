{{-- ======================================================================== --}}
{{-- 1. BERANDA --}}
{{-- ======================================================================== --}}
@can('view-dashboard')
<div class="menu_title flex">
    <span class="title">Menu Utama</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    <li class="item">
        <a class="link flex {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="material-icons">dashboard</i>
            <span>Dashboard</span>
        </a>
    </li>
</ul>
@endcan

{{-- ======================================================================== --}}
{{-- 2. MANAJEMEN PRODUK --}}
{{-- ======================================================================== --}}
@can('view-products')
<ul class="menu_item">
    @php
        $isActiveProduct = request()->routeIs('products.*') || request()->routeIs('stock-opnames.*');
    @endphp
    
    <li class="item has-submenu {{ $isActiveProduct ? 'open' : '' }}">
        <a class="link flex" href="#">
            <i class="material-icons">inventory_2</i>
            <span>Manajemen Produk</span>
            <i class="material-icons dropdown-icon">chevron_right</i>
        </a>
        <ul class="submenu">
            <li class="item">
                <a class="link flex {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <i class="material-icons">list</i>
                    <span>Daftar Produk</span>
                </a>
            </li>
            <li class="item">
                <a class="link flex {{ request()->routeIs('stock-opnames.*') ? 'active' : '' }}" href="{{ route('stock-opnames.index') }}">
                    <i class="material-icons">assignment_turned_in</i>
                    <span>Stock Opname</span>
                </a>
            </li>
        </ul>
    </li>
</ul>
@endcan

{{-- ======================================================================== --}}
{{-- 3. PEMBELIAN & SUPPLIER --}}
{{-- ======================================================================== --}}
@if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-purchase-returns']))
<ul class="menu_item">
    @php
        $isActivePurchase = request()->routeIs('suppliers.*') || 
                            request()->routeIs('purchase-orders.*') || 
                            request()->routeIs('purchase-order-adjustments.*') ||
                            request()->routeIs('batch-purchase-payments.*') ||
                            request()->routeIs('purchase-returns.*');
    @endphp

    <li class="item has-submenu {{ $isActivePurchase ? 'open' : '' }}">
        <a class="link flex" href="#">
            <i class="material-icons">shopping_cart</i>
            <span>Pembelian & Supplier</span>
            <i class="material-icons dropdown-icon">chevron_right</i>
        </a>
        <ul class="submenu">
            @can('view-suppliers')
            <li class="item">
                <a class="link flex {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                    <i class="material-icons">local_shipping</i>
                    <span>Data Supplier</span>
                </a>
            </li>
            @endcan

            @can('view-purchase-orders')
            <li class="item">
                <a class="link flex {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
                    <i class="material-icons">receipt</i>
                    <span>Pesanan Pembelian (PO)</span>
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
                    <i class="material-icons">checklist</i>
                    <span>Bayar Hutang (Bulk)</span>
                </a>
            </li>
            @endcan

            @can('view-purchase-returns')
            <li class="item">
                <a class="link flex {{ request()->routeIs("purchase-returns.*") ? "active" : "" }}" href="{{ route('purchase-returns.index') }}">
                    <i class="material-icons">assignment_return</i>
                    <span>Retur Pembelian</span>
                </a>
            </li>
            @endcan
        </ul>
    </li>
</ul>
@endif

{{-- ======================================================================== --}}
{{-- 4. PENJUALAN & KLIEN --}}
{{-- ======================================================================== --}}
@if(Auth::user()->canany(['view-clients', 'view-sales-orders', 'review-client-orders', 'view-sales-returns']))
<ul class="menu_item">
    @php
        $isActiveSales = request()->routeIs('clients.*') || 
                         request()->routeIs('sales-orders.*') || 
                         request()->routeIs('client-order-reviews.*') ||
                         request()->routeIs('order-change-requests.*') ||
                         request()->routeIs('sales-returns.*');
    @endphp

    <li class="item has-submenu {{ $isActiveSales ? 'open' : '' }}">
        <a class="link flex" href="#">
            <i class="material-icons">storefront</i>
            <span>Penjualan & Klien</span>
            <i class="material-icons dropdown-icon">chevron_right</i>
        </a>
        <ul class="submenu">
            @can('view-clients')
            <li class="item">
                <a class="link flex {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">
                    <i class="material-icons">people</i>
                    <span>Data Klien</span>
                </a>
            </li>
            @endcan

            @can('view-sales-orders')
            <li class="item">
                <a class="link flex {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}" href="{{ route('sales-orders.index') }}">
                    <i class="material-icons">description</i>
                    <span>Pesanan Sales</span>
                    @if(isset($pendingSalesOrderCount) && $pendingSalesOrderCount > 0)
                        <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.7em">{{ $pendingSalesOrderCount }}</span>
                    @endif
                </a>
            </li>
            @endcan

            @can('review-client-orders')
            <li class="item">
                <a class="link flex {{ request()->routeIs('client-order-reviews.*') ? 'active' : '' }}" href="{{ route('client-order-reviews.index') }}">
                    <i class="material-icons">rate_review</i>
                    <span>Review Order Klien</span>
                </a>
            </li>
            @endcan
            
            @can('review-order-change-requests')
            <li class="item">
                <a class="link flex {{ request()->routeIs('order-change-requests.*') ? 'active' : '' }}" href="{{ route('order-change-requests.index') }}">
                    <i class="material-icons">sync_problem</i>
                    <span>Request Perubahan</span>
                </a>
            </li>
            @endcan

            @can('view-sales-returns')
            <li class="item">
                <a class="link flex {{ request()->routeIs("sales-returns.*") ? "active" : "" }}" href="{{ route('sales-returns.index') }}">
                    <i class="material-icons">keyboard_return</i>
                    <span>Retur Penjualan</span>
                </a>
            </li>
            @endcan
        </ul>
    </li>
</ul>
@endif

{{-- ======================================================================== --}}
{{-- 5. KEUANGAN & INVOICE --}}
{{-- ======================================================================== --}}
@if(Auth::user()->canany(['view-invoices', 'create-batch-payments', 'manage-payment-clearance']))
<ul class="menu_item">
    @php
        $isActiveFinance = request()->routeIs('invoices.*') || 
                           request()->routeIs('invoice-adjustments.*') ||
                           request()->routeIs('batch-payments.*') ||
                           request()->routeIs('payment-clearance.*');
    @endphp

    <li class="item has-submenu {{ $isActiveFinance ? 'open' : '' }}">
        <a class="link flex" href="#">
            <i class="material-icons">monetization_on</i>
            <span>Keuangan & Invoice</span>
            <i class="material-icons dropdown-icon">chevron_right</i>
        </a>
        <ul class="submenu">
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
                    <i class="material-icons">playlist_add_check</i>
                    <span>Penyesuaian Invoice</span>
                </a>
            </li>
            @endcan

            @can("create-batch-payments")
            <li class="item">
                <a class="link flex {{ (request()->routeIs('batch-payments.*') && !request()->routeIs('batch-payments.pending')) ? 'active' : '' }}" href="{{ route('batch-payments.create') }}">
                    <i class="material-icons">price_check</i>
                    <span>Terima Invoice (Bulk)</span>
                </a>
            </li>
            @endcan

            @can("review-batch-payments")
            <li class="item">
                <a class="link flex {{ request()->routeIs('batch-payments.pending') ? 'active' : '' }}" href="{{ route('batch-payments.pending') }}">
                    <i class="material-icons">verified</i>
                    <span>Verifikasi Pembayaran</span>
                </a>
            </li>
            @endcan

            @can("manage-payment-clearance")
            <li class="item">
                <a class="link flex {{ request()->routeIs('payment-clearance.*') ? 'active' : '' }}" href="{{ route('payment-clearance.index') }}">
                    <i class="material-icons">hourglass_bottom</i>
                    <span>Kliring Pembayaran</span>
                </a>
            </li>
            @endcan
        </ul>
    </li>
</ul>
@endif

{{-- ======================================================================== --}}
{{-- 6. AKUNTANSI --}}
{{-- ======================================================================== --}}
@if(Auth::user()->canany(['view-reports', 'manage-settings', 'manage-bank-accounts']))
<ul class="menu_item">
    @php
        $isActiveAccounting = request()->routeIs('expenses.*') || 
                              request()->routeIs('fixed-assets.*') ||
                              request()->routeIs('equity-transactions.*') ||
                              request()->routeIs('loans.*') ||
                              request()->routeIs('manual-journals.*') ||
                              request()->routeIs('bank-reconciliations.*') ||
                              request()->routeIs('closing-book.*') ||
                              request()->routeIs('chart-of-accounts.*') ||
                              request()->routeIs('reports.*'); // Sudah mencakup general-ledger
    @endphp

    <li class="item has-submenu {{ $isActiveAccounting ? 'open' : '' }}">
        <a class="link flex" href="#">
            <i class="material-icons">account_balance</i>
            <span>Akuntansi</span>
            <i class="material-icons dropdown-icon">chevron_right</i>
        </a>
        <ul class="submenu">
            <li class="item">
                <a class="link flex {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                    <i class="material-icons">payments</i>
                    <span>Beban Operasional</span>
                </a>
            </li>
            <li class="item">
                <a class="link flex {{ request()->routeIs('fixed-assets.*') ? 'active' : '' }}" href="{{ route('fixed-assets.index') }}">
                    <i class="material-icons">domain</i>
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
                    <i class="material-icons">credit_score</i>
                    <span>Pinjaman / Utang</span>
                </a>
            </li>

            @can('manage-settings')
            <li class="item">
                <a class="link flex {{ request()->routeIs("manual-journals.*") ? "active" : "" }}" href="{{ route("manual-journals.index") }}">
                    <i class="material-icons">book</i>
                    <span>Jurnal Umum</span>
                </a>
            </li>
            <li class="item">
                <a class="link flex {{ request()->routeIs("bank-reconciliations.*") ? "active" : "" }}" href="{{ route("bank-reconciliations.index") }}">
                    <i class="material-icons">compare_arrows</i>
                    <span>Rekonsiliasi Bank</span>
                </a>
            </li>
            <li class="item">
                <a class="link flex {{ request()->routeIs("closing-book.*") ? "active" : "" }}" href="{{ route("closing-book.index") }}">
                    <i class="material-icons">lock_clock</i>
                    <span>Tutup Buku</span>
                </a>
            </li>
            @endcan

            <li class="item">
                <a class="link flex {{ request()->routeIs("chart-of-accounts.*") ? "active" : "" }}" href="{{ route("chart-of-accounts.index") }}">
                    <i class="material-icons">account_tree</i>
                    <span>Daftar Akun (COA)</span>
                </a>
            </li>
            
            @can('view-reports')
            {{-- Mengubah Laporan Keuangan menjadi sub-menu untuk menampung Buku Besar --}}
            @php
                $isActiveReports = request()->routeIs('reports.index') || request()->routeIs('reports.general-ledger');
            @endphp
            <li class="item has-submenu {{ $isActiveReports ? 'open' : '' }}">
                <a class="link flex" href="#">
                    <i class="material-icons">analytics</i>
                    <span>Laporan Keuangan</span>
                    <i class="material-icons dropdown-icon">chevron_right</i>
                </a>
                <ul class="submenu">
                    <li class="item">
                        <a class="link flex {{ request()->routeIs("reports.index") ? "active" : "" }}" href="{{ route("reports.index") }}">
                            <i class="material-icons">summarize</i>
                            <span>Ringkasan Laporan</span>
                        </a>
                    </li>
                    <li class="item">
                        <a class="link flex {{ request()->routeIs("reports.general-ledger") ? "active" : "" }}" href="{{ route("reports.general-ledger") }}">
                            <i class="material-icons">book_online</i>
                            <span>Buku Besar</span> {{-- Link yang diminta --}}
                        </a>
                    </li>
                </ul>
            </li>
            @endcan
        </ul>
    </li>
</ul>
@endif

{{-- ======================================================================== --}}
{{-- 7. PENGATURAN --}}
{{-- ======================================================================== --}}
@if(Auth::user()->canany(["manage-settings", "manage-payment-methods", "manage-users", "manage-roles"]))
<ul class="menu_item">
    @php
        $isActiveSettings = request()->routeIs('settings.*') || 
                            request()->routeIs('units.*') ||
                            request()->routeIs('taxes.*') ||
                            request()->routeIs('payment-methods.*') ||
                            request()->routeIs('users.*') ||
                            request()->routeIs('roles.*') || 
                            request()->routeIs('company-bank-accounts.*');
    @endphp

    <li class="item has-submenu {{ $isActiveSettings ? 'open' : '' }}">
        <a class="link flex" href="#">
            <i class="material-icons">settings</i>
            <span>Pengaturan</span>
            <i class="material-icons dropdown-icon">chevron_right</i>
        </a>
        <ul class="submenu">
            @can("manage-settings")
            <li class="item">
                <a class="link flex {{ request()->routeIs("settings.*") ? "active" : "" }}" href="{{ route("settings.index") }}">
                    <i class="material-icons">business</i>
                    <span>Profil Perusahaan</span>
                </a>
            </li>
            <li class="item">
                <a class="link flex {{ request()->routeIs("company-bank-accounts.*") ? "active" : "" }}" href="{{ route("company-bank-accounts.index") }}">
                    <i class="material-icons">account_balance_wallet</i>
                    <span>Akun Bank</span>
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
                    <i class="material-icons">credit_card</i>
                    <span>Metode Pembayaran</span>
                </a>
            </li>
            @endcan

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
                    <i class="material-icons">manage_accounts</i>
                    <span>Manajemen Role</span>
                </a>
            </li>
            @endcan
        </ul>
    </li>
</ul>
@endif

{{-- ======================================================================== --}}
{{-- 8. UTILITIES --}}
{{-- ======================================================================== --}}
@can("manage-announcements")
<div class="menu_title flex">
    <span class="title">Lainnya</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    <li class="item">
        <a class="link flex {{ request()->routeIs("announcements.*") ? "active" : "" }}" href="{{ route("announcements.index") }}">
            <i class="material-icons">campaign</i>
            <span>Pengumuman</span>
        </a>
    </li>
</ul>
@endcan

@can('manage-settings')
<ul class="menu_item">
    <li class="item">
        <a class="link flex {{ request()->routeIs("migration.*") ? "active" : "" }}" href="{{ route("migration.index") }}">
            <i class="material-icons">cloud_upload</i>
            <span>Migrasi Data</span>
        </a>
    </li>
</ul>
@endcan

<ul class="menu_item">
    <li class="item">
        <a class="link flex" href="#" style="cursor: default; background: none; box-shadow: none; opacity: 0.7;">
            <i class="material-icons">info</i>
            <span>Versi: {{ $systemVersion ?? '1.0.0' }}</span>
        </a>
    </li>
</ul>