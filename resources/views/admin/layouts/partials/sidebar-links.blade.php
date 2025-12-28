@php
    // Helper Functions untuk Active State
    if (!function_exists('isActive')) {
        function isActive($route) { return request()->routeIs($route) ? 'active' : ''; }
    }
    if (!function_exists('isParentActive')) {
        function isParentActive($routes) {
            foreach ($routes as $route) { if (request()->routeIs($route)) return 'active'; }
            return '';
        }
    }
    if (!function_exists('isGroupOpen')) {
        function isGroupOpen($routes) {
            foreach ($routes as $route) { if (request()->routeIs($route)) return 'open'; }
            return '';
        }
    }
@endphp

{{-- ================= SECTION 1: DASHBOARD ================= --}}
@can('view-dashboard')
<div class="px-5 mt-2 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Menu Utama</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-2 mb-2"></div>

<ul class="space-y-1.5">
    <li>
        <a href="{{ route('admin.dashboard') }}" class="menu-item {{ isActive('admin.dashboard') }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">dashboard</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Dashboard</div>
        </a>
    </li>
</ul>
@endcan

{{-- ================= SECTION 2: INVENTORI ================= --}}
@can('view-products')
<div class="px-5 mt-6 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Inventori</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-6 mb-2"></div>

<ul class="space-y-1.5">
    @php $prodRoutes = ['admin.products.*', 'admin.stock-opnames.*']; @endphp
    <li>
        <div class="menu-item dropdown-toggle {{ isParentActive($prodRoutes) }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">inventory_2</i>
            </div>
            <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                <span>Produk & Stok</span>
                <i class="material-icons arrow-icon text-[16px] text-slate-500 transition-transform duration-300">expand_more</i>
            </div>
        </div>
        <div class="submenu-wrapper {{ isGroupOpen($prodRoutes) }}">
            <div class="submenu-tree">
                <a href="{{ route('admin.products.index') }}" class="submenu-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>
                    <span>Daftar Produk</span>
                </a>
                <a href="{{ route('admin.stock-opnames.index') }}" class="submenu-link {{ request()->routeIs('admin.stock-opnames.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>
                    <span>Stock Opname</span>
                </a>
            </div>
        </div>
    </li>
</ul>
@endcan

{{-- ================= SECTION 3: PENJUALAN (SALES) ================= --}}
{{-- Menggabungkan Client, SO, Invoice, dan Retur Jual di sini --}}
@if(Auth::user()->canany(['view-clients', 'view-sales-orders', 'view-invoices', 'view-sales-returns']))
<div class="px-5 mt-6 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Penjualan</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-6 mb-2"></div>

<ul class="space-y-1.5">
    {{-- GROUP 1: ORDER PENJUALAN --}}
    @php $salesRoutes = ['admin.clients.*', 'admin.sales-orders.*', 'admin.client-order-reviews.*', 'admin.order-change-requests.*']; @endphp
    <li>
        <div class="menu-item dropdown-toggle {{ isParentActive($salesRoutes) }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">storefront</i>
            </div>
            <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                <span>Sales Order (SO)</span>
                <i class="material-icons arrow-icon text-[16px] text-slate-500 transition-transform duration-300">expand_more</i>
            </div>
        </div>
        <div class="submenu-wrapper {{ isGroupOpen($salesRoutes) }}">
            <div class="submenu-tree">
                @can('view-clients')
                <a href="{{ route('admin.clients.index') }}" class="submenu-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Data Klien
                </a>
                @endcan
                @can('view-sales-orders')
                <a href="{{ route('admin.sales-orders.index') }}" class="submenu-link flex justify-between items-center pr-2 {{ request()->routeIs('admin.sales-orders.*') ? 'active' : '' }}">
                    <div class="flex items-center">
                        <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Pesanan (SO)
                    </div>
                    @if(isset($pendingSalesOrderCount) && $pendingSalesOrderCount > 0)
                        <span class="bg-red-500 text-white text-[9px] px-1.5 py-0.5 rounded-full leading-none">{{ $pendingSalesOrderCount }}</span>
                    @endif
                </a>
                @endcan
                @can('review-client-orders')
                <a href="{{ route('admin.client-order-reviews.index') }}" class="submenu-link {{ request()->routeIs('admin.client-order-reviews.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Review Order
                </a>
                @endcan
                @can('review-order-change-requests')
                <a href="{{ route('admin.order-change-requests.index') }}" class="submenu-link {{ request()->routeIs('admin.order-change-requests.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Request Ubah
                </a>
                @endcan
            </div>
        </div>
    </li>

    {{-- GROUP 2: FAKTUR / INVOICE (DIPINDAHKAN KE SINI) --}}
    @php $invRoutes = ['admin.invoices.*', 'admin.invoice-adjustments.*', 'admin.sales-returns.*']; @endphp
    @if(Auth::user()->canany(['view-invoices', 'view-sales-returns']))
    <li>
        <div class="menu-item dropdown-toggle {{ isParentActive($invRoutes) }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">receipt_long</i>
            </div>
            <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                <span>Faktur & Retur</span>
                <i class="material-icons arrow-icon text-[16px] text-slate-500 transition-transform duration-300">expand_more</i>
            </div>
        </div>
        <div class="submenu-wrapper {{ isGroupOpen($invRoutes) }}">
            <div class="submenu-tree">
                @can('view-invoices')
                <a href="{{ route('admin.invoices.index') }}" class="submenu-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Daftar Invoice
                </a>
                @endcan
                @can('create-invoice-adjustments')
                <a href="{{ route('admin.invoice-adjustments.create') }}" class="submenu-link {{ request()->routeIs('admin.invoice-adjustments.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Penyesuaian Inv
                </a>
                @endcan
                @can('view-sales-returns')
                <a href="{{ route('admin.sales-returns.index') }}" class="submenu-link {{ request()->routeIs('admin.sales-returns.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Retur Penjualan
                </a>
                @endcan
            </div>
        </div>
    </li>
    @endif
</ul>
@endif

{{-- ================= SECTION 4: PEMBELIAN (PURCHASING) ================= --}}
@if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-purchase-returns']))
<div class="px-5 mt-6 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pembelian</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-6 mb-2"></div>

<ul class="space-y-1.5">
    @php $purchRoutes = ['admin.suppliers.*', 'admin.purchase-orders.*', 'admin.purchase-order-adjustments.*', 'admin.purchase-returns.*']; @endphp
    <li>
        <div class="menu-item dropdown-toggle {{ isParentActive($purchRoutes) }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">shopping_cart</i>
            </div>
            <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                <span>Purchase Order</span>
                <i class="material-icons arrow-icon text-[16px] text-slate-500 transition-transform duration-300">expand_more</i>
            </div>
        </div>
        <div class="submenu-wrapper {{ isGroupOpen($purchRoutes) }}">
            <div class="submenu-tree">
                @can('view-suppliers')
                <a href="{{ route('admin.suppliers.index') }}" class="submenu-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Data Supplier
                </a>
                @endcan
                @can('view-purchase-orders')
                <a href="{{ route('admin.purchase-orders.index') }}" class="submenu-link {{ request()->routeIs('admin.purchase-orders.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Pesanan (PO)
                </a>
                @endcan
                @can('create-purchase-adjustments')
                <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="submenu-link {{ request()->routeIs('admin.purchase-order-adjustments.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Penyesuaian PO
                </a>
                @endcan
                @can('view-purchase-returns')
                <a href="{{ route('admin.purchase-returns.index') }}" class="submenu-link {{ request()->routeIs('admin.purchase-returns.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Retur Pembelian
                </a>
                @endcan
            </div>
        </div>
    </li>
</ul>
@endif

{{-- ================= SECTION 5: KEUANGAN (FINANCE) ================= --}}
@if(Auth::user()->canany(['create-batch-payments', 'create-bulk-sales-payments', 'view-expenses', 'view-loans']))
<div class="px-5 mt-6 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Keuangan</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-6 mb-2"></div>

<ul class="space-y-1.5">
    
    {{-- TRANSAKSI KAS (PAYMENTS) --}}
    @php $cashRoutes = ['admin.bulk-sales-payments.*', 'admin.bulk-purchase-payments.*', 'admin.payment-clearance.*', 'admin.expenses.*']; @endphp
    <li>
        <div class="menu-item dropdown-toggle {{ isParentActive($cashRoutes) }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">payments</i>
            </div>
            <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                <span>Arus Kas & Biaya</span>
                <i class="material-icons arrow-icon text-[16px] text-slate-500 transition-transform duration-300">expand_more</i>
            </div>
        </div>
        <div class="submenu-wrapper {{ isGroupOpen($cashRoutes) }}">
            <div class="submenu-tree">
                @can('create-bulk-sales-payments')
                <a href="{{ route('admin.bulk-sales-payments.index') }}" class="submenu-link {{ request()->routeIs('admin.bulk-sales-payments.index') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Terima Piutang
                </a>
                <a href="{{ route('admin.bulk-sales-payments.pending') }}" class="submenu-link {{ request()->routeIs('admin.bulk-sales-payments.pending') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Verifikasi Bayar
                </a>
                @endcan

                @can('create-batch-purchase-payments')
                <a href="{{ route('admin.bulk-purchase-payments.create') }}" class="submenu-link {{ request()->routeIs('admin.bulk-purchase-payments.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Bayar Hutang
                </a>
                @endcan

                @can('manage-payment-clearance')
                <a href="{{ route('admin.payment-clearance.index') }}" class="submenu-link {{ request()->routeIs('admin.payment-clearance.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Kliring Pembayaran
                </a>
                @endcan

                @can('view-expenses')
                <a href="{{ route('admin.expenses.index') }}" class="submenu-link {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Biaya Ops (Expense)
                </a>
                @endcan
            </div>
        </div>
    </li>

    {{-- PINJAMAN --}}
    @can('view-loans')
    <li>
        <a href="{{ route('admin.loans.index') }}" class="menu-item {{ isActive('admin.loans.*') }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">credit_score</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Pinjaman</div>
        </a>
    </li>
    @endcan
</ul>
@endif

{{-- ================= SECTION 6: AKUNTANSI ================= --}}
@if(Auth::user()->canany(['manage-settings']))
<div class="px-5 mt-6 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Akuntansi</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-6 mb-2"></div>

<ul class="space-y-1.5">
    {{-- PEMBUKUAN --}}
    @php $accRoutes = ['admin.fixed-assets.*', 'admin.equity-transactions.*', 'admin.manual-journals.*', 'admin.bank-reconciliations.*', 'admin.closing-book.*', 'admin.chart-of-accounts.*']; @endphp
    <li>
        <div class="menu-item dropdown-toggle {{ isParentActive($accRoutes) }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">account_balance</i>
            </div>
            <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                <span>Buku Besar</span>
                <i class="material-icons arrow-icon text-[16px] text-slate-500 transition-transform duration-300">expand_more</i>
            </div>
        </div>
        <div class="submenu-wrapper {{ isGroupOpen($accRoutes) }}">
            <div class="submenu-tree">
                <a href="{{ route('admin.manual-journals.index') }}" class="submenu-link {{ request()->routeIs('admin.manual-journals.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Jurnal Manual
                </a>
                <a href="{{ route('admin.chart-of-accounts.index') }}" class="submenu-link {{ request()->routeIs('admin.chart-of-accounts.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Chart of Accounts
                </a>
                <a href="{{ route('admin.fixed-assets.index') }}" class="submenu-link {{ request()->routeIs('admin.fixed-assets.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Aset Tetap
                </a>
                <a href="{{ route('admin.equity-transactions.index') }}" class="submenu-link {{ request()->routeIs('admin.equity-transactions.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Modal & Prive
                </a>
                <a href="{{ route('admin.bank-reconciliations.index') }}" class="submenu-link {{ request()->routeIs('admin.bank-reconciliations.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Rekonsiliasi
                </a>
                <a href="{{ route('admin.closing-book.index') }}" class="submenu-link {{ request()->routeIs('admin.closing-book.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Tutup Buku
                </a>
            </div>
        </div>
    </li>

    {{-- LAPORAN --}}
    @php $repRoutes = ['admin.reports.index', 'admin.reports.general-ledger']; @endphp
    <li>
        <div class="menu-item dropdown-toggle {{ isParentActive($repRoutes) }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">summarize</i>
            </div>
            <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                <span>Laporan</span>
                <i class="material-icons arrow-icon text-[16px] text-slate-500 transition-transform duration-300">expand_more</i>
            </div>
        </div>
        <div class="submenu-wrapper {{ isGroupOpen($repRoutes) }}">
            <div class="submenu-tree">
                <a href="{{ route('admin.reports.index') }}" class="submenu-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Pusat Laporan
                </a>
                <a href="{{ route('admin.reports.general-ledger') }}" class="submenu-link {{ request()->routeIs('admin.reports.general-ledger') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Buku Besar
                </a>
            </div>
        </div>
    </li>
</ul>
@endif

{{-- ================= SECTION 7: SYSTEM ================= --}}
<div class="px-5 mt-6 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">System</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-6 mb-2"></div>

<ul class="space-y-1.5">
    @if(Auth::user()->canany(["manage-settings", "manage-users", "manage-payment-methods"]))
    @php $sysRoutes = ['admin.settings.*', 'admin.units.*', 'admin.taxes.*', 'admin.payment-methods.*', 'admin.users.*', 'admin.roles.*', 'admin.company-bank-accounts.*']; @endphp
    <li>
        <div class="menu-item dropdown-toggle {{ isParentActive($sysRoutes) }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">settings</i>
            </div>
            <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                <span>Pengaturan</span>
                <i class="material-icons arrow-icon text-[16px] text-slate-500 transition-transform duration-300">expand_more</i>
            </div>
        </div>
        <div class="submenu-wrapper {{ isGroupOpen($sysRoutes) }}">
            <div class="submenu-tree">
                @can("manage-settings")
                <a href="{{ route('admin.settings.index') }}" class="submenu-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Profil PT
                </a>
                <a href="{{ route('admin.company-bank-accounts.index') }}" class="submenu-link {{ request()->routeIs('admin.company-bank-accounts.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Akun Bank
                </a>
                <a href="{{ route('admin.units.index') }}" class="submenu-link {{ request()->routeIs('admin.units.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Satuan
                </a>
                <a href="{{ route('admin.taxes.index') }}" class="submenu-link {{ request()->routeIs('admin.taxes.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Pajak
                </a>
                @endcan

                @can("manage-payment-methods")
                <a href="{{ route('admin.payment-methods.index') }}" class="submenu-link {{ request()->routeIs('admin.payment-methods.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Metode Bayar
                </a>
                @endcan

                @can("manage-users")
                <a href="{{ route('admin.users.index') }}" class="submenu-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>User
                </a>
                <a href="{{ route('admin.roles.index') }}" class="submenu-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <span class="dot-indicator w-1 h-1 rounded-full bg-slate-600 mr-2.5 transition-colors"></span>Role
                </a>
                @endcan
            </div>
        </div>
    </li>
    @endif

    @can("manage-settings")
    <li>
        <a href="{{ route('admin.migration.index') }}" class="menu-item {{ isActive('admin.migration.*') }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">move_to_inbox</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Migrasi Data</div>
        </a>
    </li>
    @endcan

    @can("manage-announcements")
    <li>
        <a href="{{ route('admin.announcements.index') }}" class="menu-item {{ isActive('admin.announcements.*') }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">campaign</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Pengumuman</div>
        </a>
    </li>
    @endcan

    <li>
        <div class="menu-item cursor-default !bg-transparent hover:!bg-transparent">
            <div class="menu-icon-col">
                <i class="material-icons text-[18px] text-slate-600">info</i>
            </div>
            <div class="menu-text-col hide-on-collapsed text-slate-600 text-[10px]">
                v{{ $systemVersion ?? '1.0.0' }}
            </div>
        </div>
    </li>
</ul>