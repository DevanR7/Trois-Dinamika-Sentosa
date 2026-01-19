@php
    $active = fn($r) => request()->routeIs($r) ? 'active' : '';
    $parentActive = fn($routes) => collect($routes)->contains(fn($r) => request()->routeIs($r)) ? 'active' : '';
    $groupOpen = fn($routes) => collect($routes)->contains(fn($r) => request()->routeIs($r)) ? 'open' : '';
@endphp

{{-- ================= SECTION 1: DASHBOARD ================= --}}
@can('view-dashboard')
    <div class="px-5 mt-2 mb-2 flex items-center hide-on-collapsed transition-opacity">
        <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Menu Utama</span>
    </div>
    <div class="show-on-collapsed h-px w-6 bg-slate-800 mx-auto mt-2 mb-2"></div>

    <ul class="space-y-1 mb-6">
        <li>
            <a href="{{ route('admin.dashboard') }}" class="menu-item {{ $active('admin.dashboard') }}" title="Dashboard">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">dashboard</i>
                </div>
                <div class="menu-text-col hide-on-collapsed">Dashboard</div>
            </a>
        </li>
    </ul>
@endcan

{{-- ================= SECTION 2: INVENTORI ================= --}}
@if(Auth::user()->canany(['view-products', 'manage-stock-opnames']))
    <div class="px-5 mb-2 flex items-center hide-on-collapsed transition-opacity">
        <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Inventori</span>
    </div>
    <div class="show-on-collapsed h-px w-6 bg-slate-800 mx-auto mb-2"></div>

    <ul class="space-y-1 mb-6">
        @php
            $prodRoutes = [
                'admin.products.*', 'admin.categories.*', 'admin.stock-opnames.*',
                'admin.reports.stock-card', 'admin.reports.product-history'
            ];
        @endphp
        <li>
            <div class="menu-item dropdown-toggle {{ $parentActive($prodRoutes) }}" title="Produk & Stok">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">inventory_2</i>
                </div>
                <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                    <span>Produk & Stok</span>
                    <i class="material-icons arrow-icon text-[18px] text-slate-500 transition-transform duration-300">keyboard_arrow_down</i>
                </div>
            </div>
            <div class="submenu-wrapper {{ $groupOpen($prodRoutes) }}">
                <div class="submenu-tree">
                    @can('view-products')
                        <a href="{{ route('admin.products.index') }}" class="submenu-link {{ $active('admin.products.*') }}">
                            <span class="dot-indicator"></span>
                            <span>Daftar Produk</span>
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="submenu-link {{ $active('admin.categories.*') }}">
                            <span class="dot-indicator"></span>
                            <span>Kategori</span>
                        </a>
                    @endcan
                    
                    @can('manage-stock-opnames')
                        <a href="{{ route('admin.stock-opnames.index') }}" class="submenu-link {{ $active('admin.stock-opnames.*') }}">
                            <span class="dot-indicator"></span>
                            <span>Stock Opname</span>
                        </a>
                    @endcan

                    {{-- Sub-header kecil di dalam menu --}}
                    @can('view-reports')
                        <div class="px-4 py-1.5 text-[9px] font-bold text-slate-500 uppercase tracking-wider mt-2 mb-1">Laporan Stok</div>
                        <a href="{{ route('admin.reports.stock-card') }}" class="submenu-link {{ $active('admin.reports.stock-card') }}">
                            <span class="dot-indicator"></span>
                            <span>Kartu Stok</span>
                        </a>
                        <a href="{{ route('admin.reports.product-history') }}" class="submenu-link {{ $active('admin.reports.product-history') }}">
                            <span class="dot-indicator"></span>
                            <span>Riwayat Produk</span>
                        </a>
                    @endcan
                </div>
            </div>
        </li>
    </ul>
@endif

{{-- ================= SECTION 3: PENJUALAN (SALES) ================= --}}
@if(Auth::user()->canany(['view-clients', 'view-sales-orders', 'view-invoices', 'view-sales-returns']))
    <div class="px-5 mb-2 flex items-center hide-on-collapsed transition-opacity">
        <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Penjualan</span>
    </div>
    <div class="show-on-collapsed h-px w-6 bg-slate-800 mx-auto mb-2"></div>

    <ul class="space-y-1 mb-6">
        
        {{-- GROUP 1: ORDER PENJUALAN --}}
        @php $salesRoutes = ['admin.clients.*', 'admin.sales-orders.*', 'admin.client-order-reviews.*', 'admin.order-change-requests.*']; @endphp
        <li>
            <div class="menu-item dropdown-toggle {{ $parentActive($salesRoutes) }}" title="Sales Order (SO)">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">storefront</i>
                </div>
                <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                    <span>Sales Order (SO)</span>
                    <i class="material-icons arrow-icon text-[18px] text-slate-500 transition-transform duration-300">keyboard_arrow_down</i>
                </div>
            </div>
            <div class="submenu-wrapper {{ $groupOpen($salesRoutes) }}">
                <div class="submenu-tree">
                    @can('view-clients')
                        <a href="{{ route('admin.clients.index') }}" class="submenu-link {{ $active('admin.clients.*') }}">
                            <span class="dot-indicator"></span>Data Klien
                        </a>
                    @endcan
                    @can('view-sales-orders')
                        <a href="{{ route('admin.sales-orders.index') }}" class="submenu-link flex justify-between items-center pr-3 {{ $active('admin.sales-orders.*') }}">
                            <div class="flex items-center">
                                <span class="dot-indicator"></span>Pesanan (SO)
                            </div>
                            @if(isset($pendingSalesOrderCount) && $pendingSalesOrderCount > 0)
                                <span class="bg-rose-500 text-white text-[9px] px-1.5 py-0.5 rounded font-bold shadow-sm leading-none">{{ $pendingSalesOrderCount }}</span>
                            @endif
                        </a>
                    @endcan
                    @can('review-client-orders')
                        <a href="{{ route('admin.client-order-reviews.index') }}" class="submenu-link {{ $active('admin.client-order-reviews.*') }}">
                            <span class="dot-indicator"></span>Review Portal Order
                        </a>
                    @endcan
                    @can('review-order-change-requests')
                        <a href="{{ route('admin.order-change-requests.index') }}" class="submenu-link {{ $active('admin.order-change-requests.*') }}">
                            <span class="dot-indicator"></span>Request Perubahan
                        </a>
                    @endcan
                </div>
            </div>
        </li>

        {{-- GROUP 2: FAKTUR / INVOICE --}}
        @php $invRoutes = ['admin.invoices.*', 'admin.invoice-adjustments.*', 'admin.sales-returns.*']; @endphp
        @if(Auth::user()->canany(['view-invoices', 'view-sales-returns']))
            <li>
                <div class="menu-item dropdown-toggle {{ $parentActive($invRoutes) }}" title="Faktur & Retur">
                    <div class="menu-icon-col">
                        <i class="material-icons text-[20px]">receipt_long</i>
                    </div>
                    <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                        <span>Faktur & Retur</span>
                        <i class="material-icons arrow-icon text-[18px] text-slate-500 transition-transform duration-300">keyboard_arrow_down</i>
                    </div>
                </div>
                <div class="submenu-wrapper {{ $groupOpen($invRoutes) }}">
                    <div class="submenu-tree">
                        @can('view-invoices')
                            <a href="{{ route('admin.invoices.index') }}" class="submenu-link {{ $active('admin.invoices.*') }}">
                                <span class="dot-indicator"></span>Daftar Invoice
                            </a>
                        @endcan
                        @can('create-invoice-adjustments')
                            <a href="{{ route('admin.invoice-adjustments.create') }}" class="submenu-link {{ $active('admin.invoice-adjustments.*') }}">
                                <span class="dot-indicator"></span>Penyesuaian (Nota)
                            </a>
                        @endcan
                        @can('view-sales-returns')
                            <a href="{{ route('admin.sales-returns.index') }}" class="submenu-link {{ $active('admin.sales-returns.*') }}">
                                <span class="dot-indicator"></span>Retur Penjualan
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
    <div class="px-5 mb-2 flex items-center hide-on-collapsed transition-opacity">
        <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Pembelian</span>
    </div>
    <div class="show-on-collapsed h-px w-6 bg-slate-800 mx-auto mb-2"></div>

    <ul class="space-y-1 mb-6">
        @php $purchRoutes = ['admin.suppliers.*', 'admin.purchase-orders.*', 'admin.purchase-order-adjustments.*', 'admin.purchase-returns.*']; @endphp
        <li>
            <div class="menu-item dropdown-toggle {{ $parentActive($purchRoutes) }}" title="Purchase Order">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">shopping_cart</i>
                </div>
                <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                    <span>Purchase Order</span>
                    <i class="material-icons arrow-icon text-[18px] text-slate-500 transition-transform duration-300">keyboard_arrow_down</i>
                </div>
            </div>
            <div class="submenu-wrapper {{ $groupOpen($purchRoutes) }}">
                <div class="submenu-tree">
                    @can('view-suppliers')
                        <a href="{{ route('admin.suppliers.index') }}" class="submenu-link {{ $active('admin.suppliers.*') }}">
                            <span class="dot-indicator"></span>Data Supplier
                        </a>
                    @endcan
                    @can('view-purchase-orders')
                        <a href="{{ route('admin.purchase-orders.index') }}" class="submenu-link {{ $active('admin.purchase-orders.*') }}">
                            <span class="dot-indicator"></span>Pesanan (PO)
                        </a>
                    @endcan
                    @can('create-purchase-adjustments')
                        <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="submenu-link {{ $active('admin.purchase-order-adjustments.*') }}">
                            <span class="dot-indicator"></span>Penyesuaian PO
                        </a>
                    @endcan
                    @can('view-purchase-returns')
                        <a href="{{ route('admin.purchase-returns.index') }}" class="submenu-link {{ $active('admin.purchase-returns.*') }}">
                            <span class="dot-indicator"></span>Retur Pembelian
                        </a>
                    @endcan
                </div>
            </div>
        </li>
    </ul>
@endif

{{-- ================= SECTION 5: KEUANGAN (FINANCE) ================= --}}
@if(Auth::user()->canany(['create-bulk-payments', 'create-bulk-purchase-payments', 'manage-payment-clearance', 'view-expenses', 'view-loans']))
    <div class="px-5 mb-2 flex items-center hide-on-collapsed transition-opacity">
        <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Keuangan</span>
    </div>
    <div class="show-on-collapsed h-px w-6 bg-slate-800 mx-auto mb-2"></div>

    <ul class="space-y-1 mb-6">
        
        {{-- TRANSAKSI KAS --}}
        @php $cashRoutes = ['admin.bulk-sales-payments.*', 'admin.bulk-purchase-payments.*', 'admin.payment-clearance.*', 'admin.expenses.*']; @endphp
        <li>
            <div class="menu-item dropdown-toggle {{ $parentActive($cashRoutes) }}" title="Arus Kas & Biaya">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">payments</i>
                </div>
                <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                    <span>Arus Kas & Biaya</span>
                    <i class="material-icons arrow-icon text-[18px] text-slate-500 transition-transform duration-300">keyboard_arrow_down</i>
                </div>
            </div>
            <div class="submenu-wrapper {{ $groupOpen($cashRoutes) }}">
                <div class="submenu-tree">
                    @can('create-bulk-payments')
                        <a href="{{ route('admin.bulk-sales-payments.index') }}" class="submenu-link {{ $active('admin.bulk-sales-payments.index') }}">
                            <span class="dot-indicator"></span>Terima Piutang (Bulk)
                        </a>
                        <a href="{{ route('admin.bulk-sales-payments.pending') }}" class="submenu-link {{ $active('admin.bulk-sales-payments.pending') }}">
                            <span class="dot-indicator"></span>Verifikasi Bayar
                        </a>
                    @endcan

                    @can('create-bulk-purchase-payments')
                        <a href="{{ route('admin.bulk-purchase-payments.create') }}" class="submenu-link {{ $active('admin.bulk-purchase-payments.*') }}">
                            <span class="dot-indicator"></span>Bayar Hutang (Bulk)
                        </a>
                    @endcan

                    @can('manage-payment-clearance')
                        <a href="{{ route('admin.payment-clearance.index') }}" class="submenu-link {{ $active('admin.payment-clearance.*') }}">
                            <span class="dot-indicator"></span>Kliring Pembayaran
                        </a>
                    @endcan

                    @can('view-expenses')
                        <a href="{{ route('admin.expenses.index') }}" class="submenu-link {{ $active('admin.expenses.*') }}">
                            <span class="dot-indicator"></span>Biaya Ops (Expense)
                        </a>
                    @endcan
                </div>
            </div>
        </li>

        {{-- PINJAMAN --}}
        @can('view-loans')
            <li>
                <a href="{{ route('admin.loans.index') }}" class="menu-item {{ $active('admin.loans.*') }}" title="Pinjaman">
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
@if(Auth::user()->canany(['manage-settings', 'view-reports']))
    <div class="px-5 mb-2 flex items-center hide-on-collapsed transition-opacity">
        <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Akuntansi</span>
    </div>
    <div class="show-on-collapsed h-px w-6 bg-slate-800 mx-auto mb-2"></div>

    <ul class="space-y-1 mb-6">
        {{-- PEMBUKUAN --}}
        @php $accRoutes = ['admin.fixed-assets.*', 'admin.equity-transactions.*', 'admin.manual-journals.*', 'admin.bank-reconciliations.*', 'admin.closing-book.*', 'admin.chart-of-accounts.*']; @endphp
        <li>
            <div class="menu-item dropdown-toggle {{ $parentActive($accRoutes) }}" title="Buku Besar">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">account_balance</i>
                </div>
                <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                    <span>Buku Besar</span>
                    <i class="material-icons arrow-icon text-[18px] text-slate-500 transition-transform duration-300">keyboard_arrow_down</i>
                </div>
            </div>
            <div class="submenu-wrapper {{ $groupOpen($accRoutes) }}">
                <div class="submenu-tree">
                    <a href="{{ route('admin.manual-journals.index') }}" class="submenu-link {{ $active('admin.manual-journals.*') }}">
                        <span class="dot-indicator"></span>Jurnal Manual
                    </a>
                    <a href="{{ route('admin.chart-of-accounts.index') }}" class="submenu-link {{ $active('admin.chart-of-accounts.*') }}">
                        <span class="dot-indicator"></span>Chart of Accounts
                    </a>
                    <a href="{{ route('admin.fixed-assets.index') }}" class="submenu-link {{ $active('admin.fixed-assets.*') }}">
                        <span class="dot-indicator"></span>Aset Tetap
                    </a>
                    <a href="{{ route('admin.equity-transactions.index') }}" class="submenu-link {{ $active('admin.equity-transactions.*') }}">
                        <span class="dot-indicator"></span>Modal & Prive
                    </a>
                    <a href="{{ route('admin.bank-reconciliations.index') }}" class="submenu-link {{ $active('admin.bank-reconciliations.*') }}">
                        <span class="dot-indicator"></span>Rekonsiliasi
                    </a>
                    <a href="{{ route('admin.closing-book.index') }}" class="submenu-link {{ $active('admin.closing-book.*') }}">
                        <span class="dot-indicator"></span>Tutup Buku
                    </a>
                </div>
            </div>
        </li>

        {{-- LAPORAN --}}
        @php $repRoutes = ['admin.reports.index', 'admin.reports.general-ledger', 'admin.reports.aging-schedule']; @endphp
        <li>
            <div class="menu-item dropdown-toggle {{ $parentActive($repRoutes) }}" title="Laporan">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">summarize</i>
                </div>
                <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                    <span>Laporan</span>
                    <i class="material-icons arrow-icon text-[18px] text-slate-500 transition-transform duration-300">keyboard_arrow_down</i>
                </div>
            </div>
            <div class="submenu-wrapper {{ $groupOpen($repRoutes) }}">
                <div class="submenu-tree">
                    <a href="{{ route('admin.reports.index') }}" class="submenu-link {{ $active('admin.reports.index') }}">
                        <span class="dot-indicator"></span>Pusat Laporan
                    </a>
                    <a href="{{ route('admin.reports.general-ledger') }}" class="submenu-link {{ $active('admin.reports.general-ledger') }}">
                        <span class="dot-indicator"></span>Jurnal Umum
                    </a>
                    <a href="{{ route('admin.reports.aging-schedule') }}" class="submenu-link {{ $active('admin.reports.aging-schedule') }}">
                        <span class="dot-indicator"></span>Umur Piutang/Hutang
                    </a>
                </div>
            </div>
        </li>
    </ul>
@endif

{{-- ================= SECTION 7: SYSTEM ================= --}}
<div class="px-5 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">System</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-800 mx-auto mb-2"></div>

<ul class="space-y-1 pb-20">
    @if(Auth::user()->canany(["manage-settings", "manage-users", "manage-payment-methods"]))
        @php $sysRoutes = ['admin.settings.*', 'admin.units.*', 'admin.taxes.*', 'admin.payment-methods.*', 'admin.users.*', 'admin.roles.*', 'admin.company-bank-accounts.*', 'admin.audit-logs.*']; @endphp
        <li>
            <div class="menu-item dropdown-toggle {{ $parentActive($sysRoutes) }}" title="Pengaturan">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">settings</i>
                </div>
                <div class="menu-text-col hide-on-collapsed flex justify-between items-center pr-2">
                    <span>Pengaturan</span>
                    <i class="material-icons arrow-icon text-[18px] text-slate-500 transition-transform duration-300">keyboard_arrow_down</i>
                </div>
            </div>
            <div class="submenu-wrapper {{ $groupOpen($sysRoutes) }}">
                <div class="submenu-tree">
                    @can("manage-settings")
                        <a href="{{ route('admin.settings.index') }}" class="submenu-link {{ $active('admin.settings.*') }}">
                            <span class="dot-indicator"></span>Profil PT
                        </a>
                        <a href="{{ route('admin.company-bank-accounts.index') }}" class="submenu-link {{ $active('admin.company-bank-accounts.*') }}">
                            <span class="dot-indicator"></span>Akun Bank
                        </a>
                        <a href="{{ route('admin.units.index') }}" class="submenu-link {{ $active('admin.units.*') }}">
                            <span class="dot-indicator"></span>Satuan
                        </a>
                        <a href="{{ route('admin.taxes.index') }}" class="submenu-link {{ $active('admin.taxes.*') }}">
                            <span class="dot-indicator"></span>Pajak
                        </a>
                        <a href="{{ route('admin.audit-logs.index') }}" class="submenu-link {{ $active('admin.audit-logs.*') }}">
                            <span class="dot-indicator"></span>Audit Logs
                        </a>
                    @endcan

                    @can("manage-payment-methods")
                        <a href="{{ route('admin.payment-methods.index') }}" class="submenu-link {{ $active('admin.payment-methods.*') }}">
                            <span class="dot-indicator"></span>Metode Bayar
                        </a>
                    @endcan

                    @can("manage-users")
                        <a href="{{ route('admin.users.index') }}" class="submenu-link {{ $active('admin.users.*') }}">
                            <span class="dot-indicator"></span>User
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="submenu-link {{ $active('admin.roles.*') }}">
                            <span class="dot-indicator"></span>Role
                        </a>
                    @endcan
                </div>
            </div>
        </li>
    @endif

    @can("manage-settings")
        <li>
            <a href="{{ route('admin.migration.index') }}" class="menu-item {{ $active('admin.migration.*') }}" title="Migrasi Data">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">move_to_inbox</i>
                </div>
                <div class="menu-text-col hide-on-collapsed">Migrasi Data</div>
            </a>
        </li>
    @endcan

    @can("manage-announcements")
        <li>
            <a href="{{ route('admin.announcements.index') }}" class="menu-item {{ $active('admin.announcements.*') }}" title="Pengumuman">
                <div class="menu-icon-col">
                    <i class="material-icons text-[20px]">campaign</i>
                </div>
                <div class="menu-text-col hide-on-collapsed">Pengumuman</div>
            </a>
        </li>
    @endcan

    <li>
        <div class="menu-item cursor-default !bg-transparent hover:!bg-transparent pointer-events-none opacity-60">
            <div class="menu-icon-col">
                <i class="material-icons text-[18px] text-slate-600">info</i>
            </div>
            <div class="menu-text-col hide-on-collapsed text-slate-500 text-[10px] font-mono mt-1">
                v{{ $systemVersion ?? '1.0.0' }}
            </div>
        </div>
    </li>
</ul>