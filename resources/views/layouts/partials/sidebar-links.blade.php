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
            <a class="link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
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
            $isActiveProduct = request()->routeIs('products.*') || request()->routeIs('stock-opnames.*'); 
        @endphp
        
        <li class="item has-submenu {{ $isActiveProduct ? 'open' : '' }}">
            <a class="link {{ $isActiveProduct ? 'active' : '' }}" href="#">
                <i class="material-icons">inventory_2</i>
                <span>Produk & Stok</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                <li class="item">
                    <a class="link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                        <span>Daftar Produk</span>
                    </a>
                </li>
                <li class="item">
                    <a class="link {{ request()->routeIs('stock-opnames.*') ? 'active' : '' }}" href="{{ route('stock-opnames.index') }}">
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
            $isActivePurchase = request()->routeIs('suppliers.*') || 
                                request()->routeIs('purchase-orders.*') || 
                                request()->routeIs('purchase-order-adjustments.*') ||
                                request()->routeIs('batch-purchase-payments.*') ||
                                request()->routeIs('purchase-returns.*');
        @endphp
        <li class="item has-submenu {{ $isActivePurchase ? 'open' : '' }}">
            <a class="link {{ $isActivePurchase ? 'active' : '' }}" href="#">
                <i class="material-icons">shopping_cart</i>
                <span>Pembelian</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                @can('view-suppliers')
                <li class="item"><a class="link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><span>Data Supplier</span></a></li>
                @endcan
                
                @can('view-purchase-orders')
                <li class="item"><a class="link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}"><span>Pesanan (PO)</span></a></li>
                @endcan

                @can('create-purchase-adjustments')
                <li class="item"><a class="link {{ request()->routeIs('purchase-order-adjustments.*') ? 'active' : '' }}" href="{{ route('purchase-order-adjustments.create') }}"><span>Penyesuaian PO</span></a></li>
                @endcan

                @can('create-batch-purchase-payments')
                <li class="item"><a class="link {{ request()->routeIs('batch-purchase-payments.*') ? 'active' : '' }}" href="{{ route('batch-purchase-payments.create') }}"><span>Bayar Hutang</span></a></li>
                @endcan
                
                @can('view-purchase-returns')
                <li class="item"><a class="link {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }}" href="{{ route('purchase-returns.index') }}"><span>Retur Pembelian</span></a></li>
                @endcan
            </ul>
        </li>
        @endif

        {{-- B. PENJUALAN --}}
        @if(Auth::user()->canany(['view-clients', 'view-sales-orders']))
        @php
            $isActiveSales = request()->routeIs('clients.*') || 
                             request()->routeIs('sales-orders.*') || 
                             request()->routeIs('client-order-reviews.*') ||
                             request()->routeIs('order-change-requests.*') ||
                             request()->routeIs('sales-returns.*');
        @endphp
        <li class="item has-submenu {{ $isActiveSales ? 'open' : '' }}">
            <a class="link {{ $isActiveSales ? 'active' : '' }}" href="#">
                <i class="material-icons">storefront</i>
                <span>Penjualan</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                @can('view-clients')
                <li class="item"><a class="link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}"><span>Data Klien</span></a></li>
                @endcan
                
                @can('view-sales-orders')
                <li class="item">
                    <a class="link {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}" href="{{ route('sales-orders.index') }}">
                        <span>Pesanan Sales</span>
                        {{-- Badge Notification --}}
                        @if(isset($pendingSalesOrderCount) && $pendingSalesOrderCount > 0)
                            <span class="ml-auto bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full leading-none">{{ $pendingSalesOrderCount }}</span>
                        @endif
                    </a>
                </li>
                @endcan

                @can('review-client-orders')
                <li class="item"><a class="link {{ request()->routeIs('client-order-reviews.*') ? 'active' : '' }}" href="{{ route('client-order-reviews.index') }}"><span>Review Order</span></a></li>
                @endcan

                @can('review-order-change-requests')
                <li class="item"><a class="link {{ request()->routeIs('order-change-requests.*') ? 'active' : '' }}" href="{{ route('order-change-requests.index') }}"><span>Request Perubahan</span></a></li>
                @endcan

                @can('view-sales-returns')
                <li class="item"><a class="link {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}" href="{{ route('sales-returns.index') }}"><span>Retur Penjualan</span></a></li>
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
            $isActiveInv = request()->routeIs('invoices.*') || 
                            request()->routeIs('invoice-adjustments.*') ||
                            request()->routeIs('batch-payments.*') ||
                            request()->routeIs('payment-clearance.*'); 
        @endphp
        <li class="item has-submenu {{ $isActiveInv ? 'open' : '' }}">
            <a class="link {{ $isActiveInv ? 'active' : '' }}" href="#">
                <i class="material-icons">payments</i>
                <span>Keuangan</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                @can('view-invoices')
                <li class="item"><a class="link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><span>Daftar Invoice</span></a></li>
                @endcan
                
                @can('create-invoice-adjustments')
                <li class="item"><a class="link {{ request()->routeIs('invoice-adjustments.*') ? 'active' : '' }}" href="{{ route('invoice-adjustments.create') }}"><span>Penyesuaian Invoice</span></a></li>
                @endcan

                @can('create-batch-payments')
                <li class="item"><a class="link {{ (request()->routeIs('batch-payments.*') && !request()->routeIs('batch-payments.pending')) ? 'active' : '' }}" href="{{ route('batch-payments.create') }}"><span>Terima Bulk (Bayar)</span></a></li>
                @endcan

                @can('review-batch-payments')
                <li class="item"><a class="link {{ request()->routeIs('batch-payments.pending') ? 'active' : '' }}" href="{{ route('batch-payments.pending') }}"><span>Verifikasi Bayar</span></a></li>
                @endcan

                @can('manage-payment-clearance')
                <li class="item"><a class="link {{ request()->routeIs('payment-clearance.*') ? 'active' : '' }}" href="{{ route('payment-clearance.index') }}"><span>Kliring</span></a></li>
                @endcan
            </ul>
        </li>
        @endif

        {{-- B. AKUNTANSI --}}
        @if(Auth::user()->canany(['manage-settings']))
        @php 
            $isActiveAcc = request()->routeIs('expenses.*') || 
                            request()->routeIs('fixed-assets.*') ||
                            request()->routeIs('equity-transactions.*') ||
                            request()->routeIs('loans.*') ||
                            request()->routeIs('manual-journals.*') || // Pastikan route ini ada
                            request()->routeIs('bank-reconciliations.*') ||
                            request()->routeIs('closing-book.*') ||
                            request()->routeIs('chart-of-accounts.*'); 
        @endphp
        <li class="item has-submenu {{ $isActiveAcc ? 'open' : '' }}">
            <a class="link {{ $isActiveAcc ? 'active' : '' }}" href="#">
                <i class="material-icons">account_balance</i>
                <span>Akuntansi</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                <li class="item"><a class="link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><span>Beban Operasional</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('fixed-assets.*') ? 'active' : '' }}" href="{{ route('fixed-assets.index') }}"><span>Aset Tetap</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('equity-transactions.*') ? 'active' : '' }}" href="{{ route('equity-transactions.index') }}"><span>Modal & Prive</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('loans.*') ? 'active' : '' }}" href="{{ route('loans.index') }}"><span>Pinjaman</span></a></li>
                
                @can('manage-settings')
                {{-- MENU JURNAL MANUAL DITAMBAHKAN DISINI --}}
                <li class="item">
                    <a class="link {{ request()->routeIs('manual-journals.*') ? 'active' : '' }}" href="{{ route('manual-journals.index') }}">
                        <span>Jurnal Manual</span>
                    </a>
                </li>

                <li class="item"><a class="link {{ request()->routeIs('bank-reconciliations.*') ? 'active' : '' }}" href="{{ route('bank-reconciliations.index') }}"><span>Rekonsiliasi Bank</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('closing-book.*') ? 'active' : '' }}" href="{{ route('closing-book.index') }}"><span>Tutup Buku</span></a></li>
                @endcan

                <li class="item"><a class="link {{ request()->routeIs('chart-of-accounts.*') ? 'active' : '' }}" href="{{ route('chart-of-accounts.index') }}"><span>Chart of Accounts</span></a></li>
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
            $isActiveReports = request()->routeIs('reports.index') || request()->routeIs('reports.general-ledger');
        @endphp
        <li class="item has-submenu {{ $isActiveReports ? 'open' : '' }}">
            <a class="link {{ $isActiveReports ? 'active' : '' }}" href="#">
                <i class="material-icons">analytics</i>
                <span>Laporan Keuangan</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                <li class="item">
                    <a class="link {{ request()->routeIs('reports.index') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                        <span>Ringkasan</span>
                    </a>
                </li>
                <li class="item">
                    <a class="link {{ request()->routeIs('reports.general-ledger') ? 'active' : '' }}" href="{{ route('reports.general-ledger') }}">
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
            $isActiveSettings = request()->routeIs('settings.*') || 
                                 request()->routeIs('units.*') ||
                                 request()->routeIs('taxes.*') ||
                                 request()->routeIs('payment-methods.*') ||
                                 request()->routeIs('users.*') || 
                                 request()->routeIs('roles.*') || 
                                 request()->routeIs('company-bank-accounts.*');
        @endphp
        <li class="item has-submenu {{ $isActiveSettings ? 'open' : '' }}">
            <a class="link {{ $isActiveSettings ? 'active' : '' }}" href="#">
                <i class="material-icons">settings</i>
                <span>Pengaturan</span>
                <i class="material-icons dropdown-icon">chevron_right</i>
            </a>
            <ul class="submenu">
                @can("manage-settings")
                <li class="item"><a class="link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><span>Profil Perusahaan</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('company-bank-accounts.*') ? 'active' : '' }}" href="{{ route('company-bank-accounts.index') }}"><span>Akun Bank</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}"><span>Satuan</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('taxes.*') ? 'active' : '' }}" href="{{ route('taxes.index') }}"><span>Pajak</span></a></li>
                @endcan

                @can("manage-payment-methods")
                <li class="item"><a class="link {{ request()->routeIs('payment-methods.*') ? 'active' : '' }}" href="{{ route('payment-methods.index') }}"><span>Metode Bayar</span></a></li>
                @endcan
                
                @can("manage-users")
                <li class="item"><a class="link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span>Manajemen User</span></a></li>
                <li class="item"><a class="link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><span>Manajemen Role</span></a></li>
                @endcan
            </ul>
        </li>
        @endif

        {{-- UTILITIES --}}
        @can("manage-announcements")
        <li class="item">
            <a class="link {{ request()->routeIs('announcements.*') ? 'active' : '' }}" href="{{ route('announcements.index') }}">
                <i class="material-icons">campaign</i>
                <span>Pengumuman</span>
            </a>
        </li>
        @endcan

        @can('manage-settings')
        <li class="item">
            <a class="link {{ request()->routeIs('migration.*') ? 'active' : '' }}" href="{{ route('migration.index') }}">
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