{{-- ======================================================================== --}}
{{-- SECTION 1: DASHBOARD (UTAMA) --}}
{{-- ======================================================================== --}}
@can('view-dashboard')
<div class="mb-2">
    <div class="menu_title">
        <span>Menu Utama</span>
    </div>
    <ul class="menu_item">
        <li class="item">
            {{-- PERBAIKAN: routeIs ditambahkan prefix admin. --}}
            <a class="link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <i class="material-icons">dashboard</i>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>
</div>
@endcan

{{-- ======================================================================== --}}
{{-- SECTION 2: OPERASIONAL (PRODUK) --}}
{{-- ======================================================================== --}}
@can('view-products')
<div class="mb-2">
    <div class="menu_title">
        <span>Operasional</span>
    </div>
    <ul class="menu_item">
        @php 
            // PERBAIKAN: Logic PHP diperbarui ke admin.
            $isActiveProduct = request()->routeIs('admin.products.*') || request()->routeIs('admin.stock-opnames.*'); 
        @endphp
        
        <li class="item has-submenu {{ $isActiveProduct ? 'open' : '' }}">
            <a class="link {{ $isActiveProduct ? 'active' : '' }}" href="#">
                <i class="material-icons">inventory_2</i>
                <span>Produk & Stok</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                <li class="item">
                    <a class="link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                        <span>Daftar Produk</span>
                    </a>
                </li>
                <li class="item">
                    <a class="link {{ request()->routeIs('admin.stock-opnames.*') ? 'active' : '' }}" href="{{ route('admin.stock-opnames.index') }}">
                        <span>Stock Opname</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</div>
@endcan

{{-- ======================================================================== --}}
{{-- SECTION 3: TRANSAKSI (PEMBELIAN & PENJUALAN) --}}
{{-- ======================================================================== --}}
@if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-clients', 'view-sales-orders']))
<div class="mb-2">
    <div class="menu_title">
        <span>Transaksi</span>
    </div>
    <ul class="menu_item">
        
        {{-- A. PEMBELIAN --}}
        @if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders']))
        @php
            // PERBAIKAN: Semua routeIs ditambah admin.
            $isActivePurchase = request()->routeIs('admin.suppliers.*') || 
                                request()->routeIs('admin.purchase-orders.*') || 
                                request()->routeIs('admin.purchase-order-adjustments.*') ||
                                request()->routeIs('admin.batch-purchase-payments.*') ||
                                request()->routeIs('admin.purchase-returns.*');
        @endphp
        <li class="item has-submenu {{ $isActivePurchase ? 'open' : '' }}">
            <a class="link {{ $isActivePurchase ? 'active' : '' }}" href="#">
                <i class="material-icons">shopping_cart</i>
                <span>Pembelian</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                @can('view-suppliers')
                <li class="item"><a class="link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}" href="{{ route('admin.suppliers.index') }}"><span>Data Supplier</span></a></li>
                @endcan
                
                @can('view-purchase-orders')
                <li class="item"><a class="link {{ request()->routeIs('admin.purchase-orders.*') ? 'active' : '' }}" href="{{ route('admin.purchase-orders.index') }}"><span>Pesanan (PO)</span></a></li>
                @endcan

                @can('create-purchase-adjustments')
                <li class="item"><a class="link {{ request()->routeIs('admin.purchase-order-adjustments.*') ? 'active' : '' }}" href="{{ route('admin.purchase-order-adjustments.create') }}"><span>Penyesuaian PO</span></a></li>
                @endcan

                @can('create-batch-purchase-payments')
                <li class="item"><a class="link {{ request()->routeIs('admin.bulk-purchase-payments.*') ? 'active' : '' }}" href="{{ route('admin.bulk-purchase-payments.create') }}"><span>Bayar Hutang</span></a></li>
                @endcan
                
                @can('view-purchase-returns')
                <li class="item"><a class="link {{ request()->routeIs('admin.purchase-returns.*') ? 'active' : '' }}" href="{{ route('admin.purchase-returns.index') }}"><span>Retur Pembelian</span></a></li>
                @endcan
            </ul>
        </li>
        @endif

        {{-- B. PENJUALAN --}}
        @if(Auth::user()->canany(['view-clients', 'view-sales-orders']))
        @php
            // PERBAIKAN: Semua routeIs ditambah admin.
            $isActiveSales = request()->routeIs('admin.clients.*') || 
                             request()->routeIs('admin.sales-orders.*') || 
                             request()->routeIs('admin.client-order-reviews.*') ||
                             request()->routeIs('admin.order-change-requests.*') ||
                             request()->routeIs('admin.sales-returns.*');
        @endphp
        <li class="item has-submenu {{ $isActiveSales ? 'open' : '' }}">
            <a class="link {{ $isActiveSales ? 'active' : '' }}" href="#">
                <i class="material-icons">storefront</i>
                <span>Penjualan</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                @can('view-clients')
                <li class="item"><a class="link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}" href="{{ route('admin.clients.index') }}"><span>Data Klien</span></a></li>
                @endcan
                
                @can('view-sales-orders')
                <li class="item">
                    <a class="link {{ request()->routeIs('admin.sales-orders.*') ? 'active' : '' }}" href="{{ route('admin.sales-orders.index') }}">
                        <span>Pesanan Sales</span>
                        {{-- Badge Notification --}}
                        @if(isset($pendingSalesOrderCount) && $pendingSalesOrderCount > 0)
                            <span class="ml-auto bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full leading-none">{{ $pendingSalesOrderCount }}</span>
                        @endif
                    </a>
                </li>
                @endcan

                @can('review-client-orders')
                <li class="item"><a class="link {{ request()->routeIs('admin.client-order-reviews.*') ? 'active' : '' }}" href="{{ route('admin.client-order-reviews.index') }}"><span>Review Order</span></a></li>
                @endcan

                @can('review-order-change-requests')
                <li class="item"><a class="link {{ request()->routeIs('admin.order-change-requests.*') ? 'active' : '' }}" href="{{ route('admin.order-change-requests.index') }}"><span>Request Perubahan</span></a></li>
                @endcan

                @can('view-sales-returns')
                <li class="item"><a class="link {{ request()->routeIs('admin.sales-returns.*') ? 'active' : '' }}" href="{{ route('admin.sales-returns.index') }}"><span>Retur Penjualan</span></a></li>
                @endcan
            </ul>
        </li>
        @endif

    </ul>
</div>
@endif

{{-- ======================================================================== --}}
{{-- SECTION 4: KEUANGAN & AKUNTANSI --}}
{{-- ======================================================================== --}}
@if(Auth::user()->canany(['view-invoices', 'manage-settings']))
<div class="mb-2">
    <div class="menu_title">
        <span>Finance & Acc</span>
    </div>
    <ul class="menu_item">
        
        {{-- A. INVOICE & PEMBAYARAN --}}
        @if(Auth::user()->canany(['view-invoices', 'create-batch-payments', 'manage-payment-clearance']))
        @php 
            // PERBAIKAN: Semua routeIs ditambah admin.
            $isActiveInv = request()->routeIs('admin.invoices.*') || 
                    request()->routeIs('admin.invoice-adjustments.*') ||
                    request()->routeIs('admin.bulk-sales-payments.*') ||
                    request()->routeIs('admin.payment-clearance.*'); 
        @endphp
        <li class="item has-submenu {{ $isActiveInv ? 'open' : '' }}">
            <a class="link {{ $isActiveInv ? 'active' : '' }}" href="#">
                <i class="material-icons">payments</i>
                <span>Keuangan</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                @can('view-invoices')
                <li class="item"><a class="link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><span>Daftar Invoice</span></a></li>
                @endcan
                
                @can('create-invoice-adjustments')
                <li class="item"><a class="link {{ request()->routeIs('admin.invoice-adjustments.*') ? 'active' : '' }}" href="{{ route('admin.invoice-adjustments.create') }}"><span>Penyesuaian Invoice</span></a></li>
                @endcan

                @can('create-bulk-sales-payments')
                <li class="item"><a class="link {{ request()->routeIs('admin.bulk-sales-payments.index') ? 'active' : '' }}" href="{{ route('admin.bulk-sales-payments.index') }}"><span>Terima Bulk (Bayar)</span></a></li>
                @endcan

                @can('review-bulk-sales-payments')
                <li class="item"><a class="link {{ request()->routeIs('admin.bulk-sales-payments.pending') ? 'active' : '' }}" href="{{ route('admin.bulk-sales-payments.pending') }}"><span>Verifikasi Bayar</span></a></li>
                @endcan

                @can('manage-payment-clearance')
                <li class="item"><a class="link {{ request()->routeIs('admin.payment-clearance.*') ? 'active' : '' }}" href="{{ route('admin.payment-clearance.index') }}"><span>Kliring</span></a></li>
                @endcan
            </ul>
        </li>
        @endif

        {{-- B. AKUNTANSI --}}
        @if(Auth::user()->canany(['manage-settings']))
        @php 
            // PERBAIKAN: Semua routeIs ditambah admin.
            $isActiveAcc = request()->routeIs('admin.expenses.*') || 
                            request()->routeIs('admin.fixed-assets.*') ||
                            request()->routeIs('admin.equity-transactions.*') ||
                            request()->routeIs('admin.loans.*') ||
                            request()->routeIs('admin.manual-journals.*') || 
                            request()->routeIs('admin.bank-reconciliations.*') ||
                            request()->routeIs('admin.closing-book.*') ||
                            request()->routeIs('admin.chart-of-accounts.*'); 
        @endphp
        <li class="item has-submenu {{ $isActiveAcc ? 'open' : '' }}">
            <a class="link {{ $isActiveAcc ? 'active' : '' }}" href="#">
                <i class="material-icons">account_balance</i>
                <span>Akuntansi</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                <li class="item"><a class="link {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}" href="{{ route('admin.expenses.index') }}"><span>Beban Operasional</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('admin.fixed-assets.*') ? 'active' : '' }}" href="{{ route('admin.fixed-assets.index') }}"><span>Aset Tetap</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('admin.equity-transactions.*') ? 'active' : '' }}" href="{{ route('admin.equity-transactions.index') }}"><span>Modal & Prive</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('admin.loans.*') ? 'active' : '' }}" href="{{ route('admin.loans.index') }}"><span>Pinjaman</span></a></li>
                
                @can('manage-settings')
                <li class="item">
                    <a class="link {{ request()->routeIs('admin.manual-journals.*') ? 'active' : '' }}" href="{{ route('admin.manual-journals.index') }}">
                        <span>Jurnal Manual</span>
                    </a>
                </li>

                <li class="item"><a class="link {{ request()->routeIs('admin.bank-reconciliations.*') ? 'active' : '' }}" href="{{ route('admin.bank-reconciliations.index') }}"><span>Rekonsiliasi Bank</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('admin.closing-book.*') ? 'active' : '' }}" href="{{ route('admin.closing-book.index') }}"><span>Tutup Buku</span></a></li>
                @endcan

                <li class="item"><a class="link {{ request()->routeIs('admin.chart-of-accounts.*') ? 'active' : '' }}" href="{{ route('admin.chart-of-accounts.index') }}"><span>Chart of Accounts</span></a></li>
            </ul>
        </li>
        @endif

    </ul>
</div>
@endif

{{-- ======================================================================== --}}
{{-- SECTION 5: LAPORAN (TERPISAH) --}}
{{-- ======================================================================== --}}
@can('view-reports')
<div class="mb-2">
    <div class="menu_title">
        <span>Laporan</span>
    </div>
    <ul class="menu_item">
        @php
            // PERBAIKAN: routeIs ditambah admin.
            $isActiveReports = request()->routeIs('admin.reports.index') || request()->routeIs('admin.reports.general-ledger');
        @endphp
        <li class="item has-submenu {{ $isActiveReports ? 'open' : '' }}">
            <a class="link {{ $isActiveReports ? 'active' : '' }}" href="#">
                <i class="material-icons">analytics</i>
                <span>Laporan Keuangan</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                <li class="item">
                    <a class="link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                        <span>Ringkasan</span>
                    </a>
                </li>
                <li class="item">
                    <a class="link {{ request()->routeIs('admin.reports.general-ledger') ? 'active' : '' }}" href="{{ route('admin.reports.general-ledger') }}">
                        <span>Buku Besar</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</div>
@endcan

{{-- ======================================================================== --}}
{{-- SECTION 6: SYSTEM (PENGATURAN & LAINNYA) --}}
{{-- ======================================================================== --}}
<div class="mb-6">
    <div class="menu_title">
        <span>System</span>
    </div>
    <ul class="menu_item">
        
        @if(Auth::user()->canany(["manage-settings", "manage-users", "manage-payment-methods"]))
        @php
            // PERBAIKAN: Semua routeIs ditambah admin.
            $isActiveSettings = request()->routeIs('admin.settings.*') || 
                                 request()->routeIs('admin.units.*') ||
                                 request()->routeIs('admin.taxes.*') ||
                                 request()->routeIs('admin.payment-methods.*') ||
                                 request()->routeIs('admin.users.*') || 
                                 request()->routeIs('admin.roles.*') || 
                                 request()->routeIs('admin.company-bank-accounts.*');
        @endphp
        <li class="item has-submenu {{ $isActiveSettings ? 'open' : '' }}">
            <a class="link {{ $isActiveSettings ? 'active' : '' }}" href="#">
                <i class="material-icons">settings</i>
                <span>Pengaturan</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                @can("manage-settings")
                <li class="item"><a class="link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}"><span>Profil Perusahaan</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('admin.company-bank-accounts.*') ? 'active' : '' }}" href="{{ route('admin.company-bank-accounts.index') }}"><span>Akun Bank</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('admin.units.*') ? 'active' : '' }}" href="{{ route('admin.units.index') }}"><span>Satuan</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('admin.taxes.*') ? 'active' : '' }}" href="{{ route('admin.taxes.index') }}"><span>Pajak</span></a></li>
                @endcan

                @can("manage-payment-methods")
                <li class="item"><a class="link {{ request()->routeIs('admin.payment-methods.*') ? 'active' : '' }}" href="{{ route('admin.payment-methods.index') }}"><span>Metode Bayar</span></a></li>
                @endcan
                
                @can("manage-users")
                <li class="item"><a class="link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span>Manajemen User</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}"><span>Manajemen Role</span></a></li>
                @endcan
            </ul>
        </li>
        @endif

        {{-- UTILITIES --}}
        @can("manage-announcements")
        <li class="item">
            <a class="link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}" href="{{ route('admin.announcements.index') }}">
                <i class="material-icons">campaign</i>
                <span>Pengumuman</span>
            </a>
        </li>
        @endcan

        @can('manage-settings')
        <li class="item">
            <a class="link {{ request()->routeIs('admin.migration.*') ? 'active' : '' }}" href="{{ route('admin.migration.index') }}">
                <i class="material-icons">cloud_upload</i>
                <span>Migrasi Data</span>
            </a>
        </li>
        @endcan

        {{-- VERSION DISPLAY --}}
        <li class="item">
            <a class="link cursor-default hover:bg-transparent" href="#">
                <i class="material-icons text-gray-600">info</i>
                <span class="text-gray-500">Versi: {{ $systemVersion ?? '1.0.0' }}</span>
            </a>
        </li>
    </ul>
</div>